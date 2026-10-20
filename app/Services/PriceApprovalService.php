<?php

namespace App\Services;

use App\Enums\PriceChangeStatus;
use App\Models\Price;
use App\Models\PriceChangeRequest;
use App\Models\PriceHistory;
use App\Models\User;
use App\Notifications\BranchPriceChangedNotification;
use App\Notifications\PriceChangeDecidedNotification;
use App\Notifications\PriceChangeSubmittedNotification;
use App\Support\ActivityAction;
use Illuminate\Support\Facades\DB;

/**
 * The multi-tier pricing workflow.
 *
 * Branch staff submit a request, head office decides. Approving a request
 * retires the previous live price, writes a new one, appends to the price
 * history and notifies the customers following that branch.
 */
class PriceApprovalService
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /**
     * Submit a pending price change for head office review.
     *
     * @param  array{old_price: string|null, new_price: string, note: string|null}  $attributes
     */
    public function requestPriceChange(PriceChangeRequest $priceChangeRequest, array $attributes, User $requester): PriceChangeRequest
    {
        return DB::transaction(function () use ($priceChangeRequest, $attributes, $requester): PriceChangeRequest {
            $priceChangeRequest->update([
                'old_price' => $attributes['old_price'],
                'new_price' => $attributes['new_price'],
                'note' => $attributes['note'] ?? null,
                'status' => PriceChangeStatus::Pending->value,
                'requested_by' => $requester->id,
                'reviewed_by' => null,
                'reviewed_at' => null,
                'review_note' => null,
            ]);

            $this->activityLogger->log(
                ActivityAction::PRICE_CHANGE_REQUESTED,
                sprintf(
                    '%s requested %s to move from %s to %s at %s.',
                    $requester->name,
                    $priceChangeRequest->catalogItem->displayName(),
                    $attributes['old_price'] ?? '—',
                    $attributes['new_price'],
                    $priceChangeRequest->branch->name,
                ),
                $priceChangeRequest,
                [
                    'old_price' => $attributes['old_price'],
                    'new_price' => $attributes['new_price'],
                ],
                $requester,
                $priceChangeRequest->branch,
            );

            $this->notifySuperAdmins($priceChangeRequest);

            return $priceChangeRequest->refresh();
        });
    }

    public function approve(PriceChangeRequest $priceChangeRequest, User $reviewer, ?string $note = null): PriceChangeRequest
    {
        return DB::transaction(function () use ($priceChangeRequest, $reviewer, $note): PriceChangeRequest {
            $priceChangeRequest->loadMissing('branch', 'catalogItem', 'requester');

            $this->guardIsPending($priceChangeRequest);

            $previousPrice = Price::query()
                ->where('branch_id', $priceChangeRequest->branch_id)
                ->where('catalog_item_id', $priceChangeRequest->catalog_item_id)
                ->where('is_active', true)
                ->orderByDesc('effective_from')
                ->lockForUpdate()
                ->first();

            $previousAmount = $previousPrice !== null ? (float) $previousPrice->price : null;

            if ($previousPrice !== null) {
                $previousPrice->update([
                    'is_active' => false,
                    'effective_to' => today(),
                ]);
            }

            $price = Price::create([
                'branch_id' => $priceChangeRequest->branch_id,
                'catalog_item_id' => $priceChangeRequest->catalog_item_id,
                'price' => $priceChangeRequest->new_price,
                'currency' => $previousPrice?->currency ?? config('app.currency'),
                'effective_from' => today(),
                'effective_to' => null,
                'is_active' => true,
                'approved_by' => $reviewer->id,
                'approved_at' => now(),
                'created_by' => $priceChangeRequest->requester_id,
            ]);

            PriceHistory::create([
                'branch_id' => $price->branch_id,
                'catalog_item_id' => $price->catalog_item_id,
                'price_id' => $price->id,
                'price_change_request_id' => $priceChangeRequest->id,
                'previous_price' => $previousAmount,
                'price' => $price->price,
                'currency' => $price->currency,
                'effective_from' => $price->effective_from,
                'changed_by' => $reviewer->id,
            ]);

            $priceChangeRequest->update([
                'status' => PriceChangeStatus::Approved->value,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            $this->activityLogger->log(
                ActivityAction::PRICE_CHANGE_APPROVED,
                sprintf(
                    '%s approved %s at %s for %s (now %s).',
                    $reviewer->name,
                    $priceChangeRequest->catalogItem->displayName(),
                    $priceChangeRequest->branch->name,
                    $previousAmount ?? '—',
                    $price->price,
                ),
                $priceChangeRequest,
                [
                    'previous_price' => $previousAmount,
                    'new_price' => (float) $price->price,
                    'review_note' => $note,
                ],
                $reviewer,
                $priceChangeRequest->branch,
            );

            $priceChangeRequest->requester?->notify(
                new PriceChangeDecidedNotification($priceChangeRequest->refresh())
            );

            $this->notifyFollowers($price, $previousAmount);

            return $priceChangeRequest->refresh();
        });
    }

    public function reject(PriceChangeRequest $priceChangeRequest, User $reviewer, ?string $note = null): PriceChangeRequest
    {
        return DB::transaction(function () use ($priceChangeRequest, $reviewer, $note): PriceChangeRequest {
            $priceChangeRequest->loadMissing('branch', 'catalogItem', 'requester');

            $this->guardIsPending($priceChangeRequest);

            $priceChangeRequest->update([
                'status' => PriceChangeStatus::Rejected->value,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'review_note' => $note,
            ]);

            $this->activityLogger->log(
                ActivityAction::PRICE_CHANGE_REJECTED,
                sprintf(
                    '%s rejected the request to change %s at %s to %s.',
                    $reviewer->name,
                    $priceChangeRequest->catalogItem->displayName(),
                    $priceChangeRequest->branch->name,
                    $priceChangeRequest->new_price,
                ),
                $priceChangeRequest,
                ['new_price' => (float) $priceChangeRequest->new_price, 'review_note' => $note],
                $reviewer,
                $priceChangeRequest->branch,
            );

            $priceChangeRequest->requester?->notify(
                new PriceChangeDecidedNotification($priceChangeRequest->refresh())
            );

            return $priceChangeRequest->refresh();
        });
    }

    /**
     * Publish a price directly, without going through the approval workflow.
     * Reserved for head office when correcting a live figure.
     */
    public function publishDirectly(
        int $branchId,
        int $catalogItemId,
        float $amount,
        User $actor,
        ?string $note = null,
    ): Price {
        return DB::transaction(function () use ($branchId, $catalogItemId, $amount, $actor, $note): Price {
            $previous = Price::query()
                ->where('branch_id', $branchId)
                ->where('catalog_item_id', $catalogItemId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            $previousAmount = $previous !== null ? (float) $previous->price : null;

            if ($previous !== null) {
                $previous->update(['is_active' => false, 'effective_to' => today()]);
            }

            $price = Price::create([
                'branch_id' => $branchId,
                'catalog_item_id' => $catalogItemId,
                'price' => $amount,
                'currency' => $previous?->currency ?? config('app.currency'),
                'effective_from' => today(),
                'effective_to' => null,
                'is_active' => true,
                'approved_by' => $actor->id,
                'approved_at' => now(),
                'created_by' => $actor->id,
            ]);

            PriceHistory::create([
                'branch_id' => $branchId,
                'catalog_item_id' => $catalogItemId,
                'price_id' => $price->id,
                'previous_price' => $previousAmount,
                'price' => $amount,
                'currency' => $price->currency,
                'effective_from' => $price->effective_from,
                'changed_by' => $actor->id,
            ]);

            $this->activityLogger->log(
                ActivityAction::PRICE_SET_DIRECTLY,
                "{$actor->name} published a direct price change to {$amount}.",
                $price,
                ['previous_price' => $previousAmount, 'new_price' => $amount, 'note' => $note],
                $actor,
            );

            $this->notifyFollowers($price, $previousAmount);

            return $price;
        });
    }

    /**
     * A request can only be decided once. Approving twice would publish a second
     * active price for the same item and corrupt the history chain, so the
     * invariant is enforced here rather than trusting every caller to check.
     */
    protected function guardIsPending(PriceChangeRequest $priceChangeRequest): void
    {
        if (! $priceChangeRequest->isPending()) {
            throw new \LogicException('Only a pending price change can be approved or rejected.');
        }
    }

    protected function notifySuperAdmins(PriceChangeRequest $priceChangeRequest): void
    {
        User::where('role', 'super_admin')
            ->get()
            ->each(fn (User $admin) => $admin->notify(
                new PriceChangeSubmittedNotification($priceChangeRequest)
            ));
    }

    /**
     * Customers following the branch are told about every live price move.
     */
    protected function notifyFollowers(Price $price, ?float $previousAmount): void
    {
        $price->loadMissing('branch', 'catalogItem');

        $price->branch->followers()->get()
            ->each(fn (User $follower) => $follower->notify(
                new BranchPriceChangedNotification($price, $previousAmount)
            ));
    }
}
