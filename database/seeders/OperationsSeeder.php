<?php

namespace Database\Seeders;

use App\Enums\AnnouncementScope;
use App\Enums\CatalogItemType;
use App\Enums\FuelType;
use App\Enums\PaymentMethod;
use App\Enums\PriceChangeStatus;
use App\Enums\ReviewStatus;
use App\Enums\SaleSource;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Price;
use App\Models\PriceChangeRequest;
use App\Models\PriceHistory;
use App\Models\Review;
use App\Models\Sale;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class OperationsSeeder extends Seeder
{
    /**
     * Demo customer accounts. Emails are fixed so that seeding is repeatable.
     *
     * @var array<int, array{name: string, email: string, phone: string}>
     */
    private const CUSTOMERS = [
        ['name' => 'Josefina Lim', 'email' => 'josefina.lim@example.test', 'phone' => '+63 917 555 1101'],
        ['name' => 'Rafael Santos', 'email' => 'rafael.santos@example.test', 'phone' => '+63 917 555 1102'],
        ['name' => 'Maria Concepcion Bautista', 'email' => 'maria.bautista@example.test', 'phone' => '+63 918 555 1103'],
        ['name' => 'Angelo Dela Cruz', 'email' => 'angelo.delacruz@example.test', 'phone' => '+63 918 555 1104'],
        ['name' => 'Kristine Fernandez', 'email' => 'kristine.fernandez@example.test', 'phone' => '+63 919 555 1105'],
        ['name' => 'Noel Villanueva', 'email' => 'noel.villanueva@example.test', 'phone' => '+63 919 555 1106'],
        ['name' => 'Grace Mendoza', 'email' => 'grace.mendoza@example.test', 'phone' => '+63 920 555 1107'],
        ['name' => 'Carlo Aquino', 'email' => 'carlo.aquino@example.test', 'phone' => '+63 920 555 1108'],
    ];

    /**
     * How many customers leave a review on each branch.
     */
    private const REVIEWS_PER_BRANCH = 3;

    public function run(): void
    {
        $branches = Branch::query()->where('is_active', true)->orderBy('id')->get();
        $superAdmin = User::query()->where('role', UserRole::SuperAdmin->value)->firstOrFail();
        $fuels = CatalogItem::query()
            ->where('type', CatalogItemType::Product->value)
            ->where('is_active', true)
            ->whereIn('sku', ['FUEL-DSL', 'FUEL-P95', 'FUEL-U91'])
            ->whereNotNull('fuel_type')
            ->orderBy('id')
            ->get();

        $customers = $this->seedCustomers();

        $this->seedPriceBoards($branches, $fuels, $superAdmin);
        $this->seedPendingPriceChangeRequests($branches, $fuels);
        $this->seedSales($branches);
        $this->seedAnnouncements($superAdmin, $branches);
        $this->seedReviews($branches, $customers, $superAdmin);
        $this->seedTickets($branches, $customers);
        $this->seedFollows($branches, $customers);
    }

    /**
     * Demo customers with stable addresses so that re-running the seeder updates
     * the same accounts instead of piling up duplicates.
     *
     * @return Collection<int, User>
     */
    private function seedCustomers(): Collection
    {
        $customers = new Collection;

        foreach (self::CUSTOMERS as $index => $definition) {
            $customers->push(User::query()->updateOrCreate(
                ['email' => $definition['email']],
                [
                    'name' => $definition['name'],
                    'email_verified_at' => now(),
                    'password' => NetworkSeeder::demoPasswordHash(),
                    'role' => UserRole::Customer->value,
                    'branch_id' => null,
                    'phone' => $definition['phone'],
                ],
            ));
        }

        return $customers;
    }

    /**
     * Give every branch a live price for each fuel grade, plus a short history so
     * the trend charts and audit trails have data to render.
     *
     * @param  Collection<int, Branch>  $branches
     * @param  Collection<int, CatalogItem>  $fuels
     */
    private function seedPriceBoards(Collection $branches, Collection $fuels, User $superAdmin): void
    {
        foreach ($branches as $branch) {
            foreach ($fuels as $fuel) {
                $basePrice = CatalogSeeder::FUEL_PRICES[$fuel->fuel_type->value] ?? 61.50;
                $branchPrice = strtolower($branch->name) === 'abucay'
                    ? $basePrice
                    : round($basePrice + (((abs(crc32($branch->code.'|'.$fuel->sku)) % 5) - 2) * 0.25), 2);

                $price = Price::query()->updateOrCreate(
                    [
                        'branch_id' => $branch->id,
                        'catalog_item_id' => $fuel->id,
                        'effective_from' => today()->toDateString(),
                    ],
                    [
                        'price' => $branchPrice,
                        'currency' => config('app.currency'),
                        'effective_to' => null,
                        'is_active' => true,
                        'approved_by' => $superAdmin->id,
                        'approved_at' => now(),
                        'created_by' => $superAdmin->id,
                    ],
                );

                $previousPrice = round($branchPrice - 0.50, 2);

                PriceHistory::query()->updateOrCreate(
                    [
                        'price_id' => $price->id,
                        'price_change_request_id' => null,
                    ],
                    [
                        'branch_id' => $branch->id,
                        'catalog_item_id' => $fuel->id,
                        'previous_price' => $previousPrice,
                        'price' => $branchPrice,
                        'currency' => config('app.currency'),
                        'effective_from' => $price->effective_from,
                        'changed_by' => $superAdmin->id,
                    ],
                );
            }
        }
    }

    /**
     * @param  Collection<int, Branch>  $branches
     * @param  Collection<int, CatalogItem>  $fuels
     */
    private function seedPendingPriceChangeRequests(Collection $branches, Collection $fuels): void
    {
        $operators = User::query()
            ->where('role', UserRole::BranchStaff->value)
            ->orderBy('id')
            ->get()
            ->keyBy('branch_id');

        foreach ($branches as $branch) {
            $operator = $operators->get($branch->id);

            if ($operator === null) {
                continue;
            }

            $fuel = $fuels->firstWhere('fuel_type', FuelType::Diesel) ?? $fuels->first();
            $currentPrice = Price::query()
                ->where('branch_id', $branch->id)
                ->where('catalog_item_id', $fuel->id)
                ->where('is_active', true)
                ->latest('effective_from')
                ->first();

            PriceChangeRequest::query()->updateOrCreate(
                [
                    'branch_id' => $branch->id,
                    'catalog_item_id' => $fuel->id,
                    'status' => PriceChangeStatus::Pending->value,
                ],
                [
                    'old_price' => $currentPrice?->price,
                    'new_price' => round((float) ($currentPrice?->price ?? 66.40) + 0.75, 2),
                    'note' => 'Supplier notified a wholesale increase. Requesting approval before the next morning shift.',
                    'requested_by' => $operator->id,
                ],
            );
        }
    }

    /**
     * Six weeks of sales per branch so the dashboard charts, daily comparison
     * report and CSV export all have meaningful volumes. Only fuels that the
     * branch actually stocks are used, so the figures reconcile with the
     * branch price board.
     *
     * The fuel, volume and payment method are derived from the branch and date
     * rather than randomised, so re-running the seeder converges on exactly the
     * same set of rows instead of accumulating new combinations.
     *
     * @param  Collection<int, Branch>  $branches
     */
    private function seedSales(Collection $branches): void
    {
        $staff = User::query()
            ->whereIn('role', [UserRole::BranchStaff->value, UserRole::BranchAdmin->value])
            ->orderBy('id')
            ->get()
            ->groupBy('branch_id');

        foreach ($branches as $branch) {
            $recorder = $staff->get($branch->id)?->first();
            $branchFuels = $branch->availableProducts()
                ->whereNotNull('catalog_items.fuel_type')
                ->orderBy('catalog_items.id')
                ->get();

            if ($recorder === null || $branchFuels->isEmpty()) {
                continue;
            }

            $fuelCount = $branchFuels->count();
            $paymentMethods = PaymentMethod::values();

            for ($daysAgo = 41; $daysAgo >= 0; $daysAgo--) {
                $date = today()->subDays($daysAgo);
                $dayIndex = 41 - $daysAgo;

                // Two sales lines per day so a branch shows mixed volume.
                for ($line = 0; $line < 2; $line++) {
                    $seed = $branch->id * 100 + $dayIndex * 10 + $line;
                    $fuel = $branchFuels[$seed % $fuelCount];
                    $unitPrice = CatalogSeeder::FUEL_PRICES[$fuel->fuel_type->value] ?? 61.50;

                    $quantity = 240 + (($seed * 37) % 1410);
                    $trend = 1 + ($dayIndex * 0.004);

                    if (in_array($date->dayOfWeek, [Carbon::FRIDAY, Carbon::SATURDAY], true)) {
                        $trend *= 1.25;
                    }

                    Sale::query()->updateOrCreate(
                        [
                            'branch_id' => $branch->id,
                            'catalog_item_id' => $fuel->id,
                            'sale_date' => $date->toDateString(),
                            'payment_method' => $paymentMethods[$seed % count($paymentMethods)],
                        ],
                        [
                            'item_name' => $fuel->name,
                            'quantity' => (int) round($quantity * $trend),
                            'unit_price' => $unitPrice,
                            'total_amount' => round((int) round($quantity * $trend) * $unitPrice, 2),
                            'currency' => config('app.currency'),
                            'source' => SaleSource::Manual->value,
                            'notes' => null,
                            'recorded_by' => $recorder->id,
                        ],
                    );
                }
            }
        }
    }

    private function seedAnnouncements(User $superAdmin, Collection $branches): void
    {
        $announcements = [
            [
                'title' => 'Employee events and branch notices',
                'body' => 'Head office updates about employee events, staff activities, participating branches, and event details will be posted here.',
                'type' => 'event',
                'scope' => AnnouncementScope::Global,
                'is_featured' => true,
                'publish_at' => now()->subDay(),
                'expires_at' => now()->addDays(30),
                'is_published' => true,
            ],
            [
                'title' => 'Station staff hiring notices',
                'body' => 'Head office will post station roles, hiring locations, and application details here when positions are available.',
                'type' => 'job',
                'scope' => AnnouncementScope::Global,
                'is_featured' => true,
                'publish_at' => now()->subDay(),
                'expires_at' => now()->addDays(30),
                'is_published' => false,
            ],
        ];

        $slugs = array_map(
            fn (array $definition): string => Str::slug($definition['title']),
            $announcements,
        );

        // Announcements are keyed by slug, so retitling one would otherwise
        // leave the superseded copy published alongside the new one. Restricted
        // to rows owned by the seeding author, plus rows orphaned when an
        // earlier author account was replaced, so announcements written by
        // staff are never removed.
        Announcement::query()
            ->where(function (Builder $query) use ($superAdmin): void {
                $query->where('author_id', $superAdmin->id)->orWhereNull('author_id');
            })
            ->whereNotIn('slug', $slugs)
            ->delete();

        foreach ($announcements as $offset => $definition) {
            $announcement = Announcement::query()->updateOrCreate(
                ['slug' => Str::slug($definition['title'])],
                array_merge($definition, [
                    'author_id' => $superAdmin->id,
                    'image_path' => null,
                ]),
            );

            // A fixed slice of the network rather than a random draw, so the
            // target branches stay put when the seeder is re-run.
            $announcement->branches()->sync(
                $definition['scope'] === AnnouncementScope::Global
                    ? []
                    : $branches->slice($offset % 3, 2)->pluck('id')->all(),
            );
        }
    }

    /**
     * @param  Collection<int, Branch>  $branches
     * @param  Collection<int, User>  $customers
     */
    private function seedReviews(Collection $branches, Collection $customers, User $moderator): void
    {
        $comments = [
            ['rating' => 5, 'title' => 'Fast and friendly forecourt', 'body' => 'Attendants were quick on a Friday evening and the pumps were clean. Diesel prices matched the app exactly.'],
            ['rating' => 4, 'title' => 'Great for early mornings', 'body' => 'Reliable 24-hour access which is what our delivery fleet needs. The diesel queue was short throughout the night.'],
            ['rating' => 4, 'title' => 'Helpful forecourt staff', 'body' => 'Attendants handled the busy queue efficiently. The forecourt was tidy and the staff were helpful.'],
            ['rating' => 3, 'title' => 'Busy at peak time', 'body' => 'Service was fine once we got a pump. Parking is difficult around lunchtime so allow extra time.'],
            ['rating' => 5, 'title' => 'Transparent invoicing', 'body' => 'Our monthly fleet invoice matched the litre counts to the last decimal. Nothing to query, which is rare.'],
            ['rating' => 2, 'title' => 'Payment terminal issue', 'body' => 'Card terminal declined twice before staff brought a working one. Station was otherwise very busy.'],
        ];

        foreach ($branches as $branch) {
            // Rotating window over the customer list rather than a random draw,
            // so each branch always ends up with the same three reviewers.
            for ($index = 0; $index < self::REVIEWS_PER_BRANCH; $index++) {
                $customer = $customers[($branch->id + $index) % $customers->count()];
                $comment = $comments[($branch->id + $index) % count($comments)];
                $status = match ($index) {
                    0, 1 => ReviewStatus::Published,
                    default => ReviewStatus::Pending,
                };

                $review = Review::query()->updateOrCreate(
                    ['branch_id' => $branch->id, 'user_id' => $customer->id],
                    [
                        'rating' => $comment['rating'],
                        'title' => $comment['title'],
                        'body' => $comment['body'],
                        'status' => $status->value,
                        'is_flagged' => $comment['rating'] <= 2,
                        'flagged_at' => $comment['rating'] <= 2 ? now()->subHours(6) : null,
                        'flagged_by' => $comment['rating'] <= 2 ? $moderator->id : null,
                        'moderated_by' => $status === ReviewStatus::Published ? $moderator->id : null,
                        'moderated_at' => $status === ReviewStatus::Published ? now()->subDays(1) : null,
                    ],
                );

                if ($review->wasRecentlyCreated) {
                    $review->forceFill([
                        'created_at' => now()->subDays(1 + (($branch->id + $index) % 25)),
                    ])->save();
                }
            }
        }
    }

    /**
     * @param  Collection<int, Branch>  $branches
     * @param  Collection<int, User>  $customers
     */
    private function seedTickets(Collection $branches, Collection $customers): void
    {
        $templates = [
            ['category' => 'billing', 'priority' => TicketPriority::High, 'status' => TicketStatus::InProgress, 'subject' => 'Invoice shows an extra 40 litres', 'message' => 'Our fleet invoice for this month lists 40 litres more than our fuel cards recorded. Could you check the pump totals and send a corrected copy?'],
            ['category' => 'fuel_quality', 'priority' => TicketPriority::Normal, 'status' => TicketStatus::Pending, 'subject' => 'Possible water in diesel', 'message' => 'A driver reported a slight drop in power on diesel from one of your Cavite stations this morning. We would like the diesel quality checked and the result shared.'],
            ['category' => 'service_quality', 'priority' => TicketPriority::Low, 'status' => TicketStatus::Resolved, 'subject' => 'Compliment for the night shift', 'message' => 'Just wanted to pass on thanks to the attendants on the night shift. They helped us with three trucks and stayed on time.'],
            ['category' => 'general', 'priority' => TicketPriority::Normal, 'status' => TicketStatus::Resolved, 'subject' => 'Do you offer tyre storage over summer?', 'message' => 'We are planning a summer trip and wondered whether the station stores tyres, and if so what the cost would be for a set of four.'],
        ];

        $staff = User::query()
            ->whereIn('role', [UserRole::BranchStaff->value, UserRole::BranchAdmin->value])
            ->orderBy('id')
            ->get()
            ->groupBy('branch_id');

        foreach ($branches as $branchIndex => $branch) {
            $agent = $staff->get($branch->id)?->first();

            if ($agent === null) {
                continue;
            }

            foreach ($templates as $offset => $template) {
                $customer = $customers[($branchIndex * 2 + $offset) % count($customers)];
                $status = $template['status'];

                $ticket = Ticket::query()->updateOrCreate(
                    [
                        'customer_id' => $customer->id,
                        'branch_id' => $branch->id,
                        'subject' => $template['subject'],
                    ],
                    [
                        'assigned_to' => $status === TicketStatus::Pending ? null : $agent->id,
                        'message' => $template['message'],
                        'category' => $template['category'],
                        'priority' => $template['priority']->value,
                        'status' => $status->value,
                        'resolved_at' => $status === TicketStatus::Resolved ? now()->subDays(2) : null,
                        'resolved_by' => $status === TicketStatus::Resolved ? $agent->id : null,
                    ],
                );

                if (! $ticket->wasRecentlyCreated) {
                    continue;
                }

                $ticket->forceFill(['created_at' => now()->subDays(fake()->numberBetween(2, 12))])->save();

                TicketReply::query()->create([
                    'ticket_id' => $ticket->id,
                    'user_id' => $agent->id,
                    'message' => $status === TicketStatus::Pending
                        ? 'Thanks for the details — we have pulled the pump totals and will come back to you shortly.'
                        : 'Thank you for reaching out. We have reviewed this with the shift supervisor and the details below.',
                    'is_internal_note' => $status === TicketStatus::Pending,
                ]);

                if ($status === TicketStatus::Resolved) {
                    TicketReply::query()->create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $agent->id,
                        'message' => 'We have resolved this and adjusted your account accordingly. The corrected document is attached to your account history.',
                        'is_internal_note' => false,
                    ]);
                }
            }
        }
    }

    /**
     * @param  Collection<int, Branch>  $branches
     * @param  Collection<int, User>  $customers
     */
    private function seedFollows(Collection $branches, Collection $customers): void
    {
        foreach ($customers as $index => $customer) {
            $branches
                ->filter(fn (Branch $branch): bool => ($branch->id + $index) % 3 !== 0)
                ->each(fn (Branch $branch) => $customer->followedBranches()->syncWithoutDetaching([
                    'branch_id' => $branch->id,
                ]));
        }
    }
}
