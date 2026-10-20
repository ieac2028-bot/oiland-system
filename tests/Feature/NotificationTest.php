<?php

namespace Tests\Feature;

use App\Enums\PriceChangeStatus;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Price;
use App\Models\PriceChangeRequest;
use App\Models\User;
use App\Notifications\BranchPriceChangedNotification;
use App\Notifications\PriceChangeDecidedNotification;
use App\Notifications\PriceChangeSubmittedNotification;
use App\Services\PriceApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_notification_inbox_lists_a_customers_notifications(): void
    {
        $customer = User::factory()->customer()->create();
        $item = CatalogItem::factory()->product()->create();
        $branch = Branch::factory()->create();
        $branch->catalogItems()->attach($item, ['is_available' => true]);

        $price = Price::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'price' => 2.50,
            'is_active' => true,
        ]);

        $customer->notify(new BranchPriceChangedNotification($price, 2.40));

        $this->actingAs($customer)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee($branch->name)
            ->assertSee($item->displayName());
    }

    public function test_opening_a_notification_marks_it_read_and_continues_to_its_target(): void
    {
        $customer = User::factory()->customer()->create();
        $item = CatalogItem::factory()->product()->create();
        $branch = Branch::factory()->create();
        $branch->catalogItems()->attach($item, ['is_available' => true]);

        $price = Price::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'price' => 2.50,
            'is_active' => true,
        ]);

        $customer->notify(new BranchPriceChangedNotification($price, 2.40));
        $id = $customer->notifications()->first()->id;

        // The notification deep-links straight to the branch price board.
        $this->actingAs($customer)
            ->get(route('notifications.read', $id))
            ->assertRedirect(route('branches.show', $branch).'#prices');

        $this->assertNotNull($customer->fresh()->notifications()->first()->read_at);
    }

    public function test_reading_someone_elses_notification_is_not_found(): void
    {
        $owner = User::factory()->customer()->create();
        $intruder = User::factory()->customer()->create();

        $owner->notify(new BranchPriceChangedNotification(
            Price::factory()->create(['price' => 2.50, 'is_active' => true]),
            2.40,
        ));
        $id = $owner->notifications()->first()->id;

        // Scoped to the viewer's own notifications, so a missing row is a 404
        // rather than a 403 that would confirm the notification exists.
        $this->actingAs($intruder)->get(route('notifications.read', $id))->assertNotFound();

        $this->assertNull($owner->fresh()->notifications()->first()->read_at);
    }

    public function test_read_all_clears_every_unread_notification(): void
    {
        $customer = User::factory()->customer()->create();

        $customer->notify(new BranchPriceChangedNotification(
            Price::factory()->create(['price' => 2.50, 'is_active' => true]),
            2.40,
        ));
        $customer->notify(new BranchPriceChangedNotification(
            Price::factory()->create(['price' => 2.60, 'is_active' => true]),
            2.50,
        ));

        $this->assertSame(2, $customer->unreadNotifications()->count());

        $this->actingAs($customer)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertSame(0, $customer->fresh()->unreadNotifications()->count());
    }

    public function test_a_submitted_price_change_notifies_head_office_with_a_readable_mail_body(): void
    {
        Notification::fake();

        $branch = Branch::factory()->create();
        $item = CatalogItem::factory()->product()->create();
        $reviewer = User::factory()->superAdmin()->create();

        $change = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'status' => PriceChangeStatus::Pending,
        ]);

        $notification = new PriceChangeSubmittedNotification($change);
        $mail = $notification->toMail($reviewer);
        $lines = array_map('strval', $mail->introLines);

        $this->assertContains('Item: '.$item->displayName(), $lines);

        // The old price is nullable on a first-time listing, so the mail must
        // fall back to a dash rather than interpolate an empty string.
        if ($change->old_price === null) {
            $this->assertContains('From: —', $lines);
        }
    }

    public function test_approving_a_price_change_notifies_the_requester(): void
    {
        Notification::fake();

        $branch = Branch::factory()->create();
        $item = CatalogItem::factory()->product()->create();
        $approver = User::factory()->superAdmin()->create();

        $change = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'status' => PriceChangeStatus::Pending,
        ]);
        $requester = $change->requester;

        app(PriceApprovalService::class)->approve($change, $approver);

        Notification::assertSentTo(
            $requester,
            PriceChangeDecidedNotification::class,
            fn (PriceChangeDecidedNotification $notification, array $channels): bool => $notification->toArray($requester)['status'] === PriceChangeStatus::Approved->value
        );
    }

    public function test_rejecting_a_price_change_notifies_the_requester_with_the_reason(): void
    {
        Notification::fake();

        $branch = Branch::factory()->create();
        $item = CatalogItem::factory()->product()->create();
        $approver = User::factory()->superAdmin()->create();

        $change = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'status' => PriceChangeStatus::Pending,
        ]);
        $requester = $change->requester;

        app(PriceApprovalService::class)->reject($change, $approver, 'Above the regional cap.');

        Notification::assertSentTo(
            $requester,
            PriceChangeDecidedNotification::class,
            function (PriceChangeDecidedNotification $notification, array $channels) use ($requester): bool {
                $payload = $notification->toArray($requester);

                return $payload['status'] === PriceChangeStatus::Rejected->value
                    && $payload['review_note'] === 'Above the regional cap.';
            }
        );
    }

    public function test_approving_a_price_change_retires_the_old_price_and_publishes_the_new_one(): void
    {
        $branch = Branch::factory()->create();
        $item = CatalogItem::factory()->product()->create();
        $approver = User::factory()->superAdmin()->create();

        Price::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'price' => 2.50,
            'is_active' => true,
        ]);

        $change = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'status' => PriceChangeStatus::Pending,
            'old_price' => 2.50,
            'new_price' => 2.75,
        ]);

        app(PriceApprovalService::class)->approve($change, $approver);

        // Exactly one price stays live for the item, and the history chain
        // records the retired amount rather than the client-submitted one.
        $this->assertSame(1, Price::query()
            ->where('branch_id', $branch->id)
            ->where('catalog_item_id', $item->id)
            ->where('is_active', true)
            ->count());

        $this->assertDatabaseHas('prices', [
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'price' => 2.75,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('price_histories', [
            'price_change_request_id' => $change->id,
            'previous_price' => 2.50,
            'price' => 2.75,
        ]);

        $this->assertDatabaseHas('price_change_requests', [
            'id' => $change->id,
            'status' => PriceChangeStatus::Approved->value,
        ]);
    }

    public function test_approving_an_already_decided_request_is_refused(): void
    {
        $branch = Branch::factory()->create();
        $item = CatalogItem::factory()->product()->create();
        $approver = User::factory()->superAdmin()->create();

        $change = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'status' => PriceChangeStatus::Approved,
        ]);

        $this->expectException(\LogicException::class);

        app(PriceApprovalService::class)->approve($change, $approver);
    }

    public function test_rejecting_an_already_approved_request_is_refused(): void
    {
        $branch = Branch::factory()->create();
        $item = CatalogItem::factory()->product()->create();
        $approver = User::factory()->superAdmin()->create();

        $change = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'status' => PriceChangeStatus::Approved,
        ]);

        $this->expectException(\LogicException::class);

        app(PriceApprovalService::class)->reject($change, $approver, 'Too late.');
    }

    public function test_approving_publishes_a_price_even_when_no_live_price_existed_before(): void
    {
        $branch = Branch::factory()->create();
        $item = CatalogItem::factory()->product()->create();
        $approver = User::factory()->superAdmin()->create();

        $change = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'status' => PriceChangeStatus::Pending,
            'old_price' => null,
            'new_price' => 2.75,
        ]);

        app(PriceApprovalService::class)->approve($change, $approver);

        $this->assertDatabaseHas('prices', [
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'price' => 2.75,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('price_histories', [
            'price_change_request_id' => $change->id,
            'previous_price' => null,
            'price' => 2.75,
        ]);
    }
}
