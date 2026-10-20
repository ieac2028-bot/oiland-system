<?php

namespace Tests\Feature;

use App\Enums\FuelType;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Price;
use App\Models\PriceChangeRequest;
use App\Models\PriceHistory;
use App\Models\Sale;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BranchIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_cannot_reach_any_admin_route(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)->get(route('admin.dashboard'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.branches.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.sales.index'))->assertForbidden();
        $this->actingAs($customer)->get(route('admin.users.index'))->assertForbidden();
    }

    public function test_branch_staff_cannot_see_another_branches_ticket(): void
    {
        $otherBranch = Branch::factory()->create();
        $staff = User::factory()->branchStaff()->create();
        $ticket = Ticket::factory()->create(['branch_id' => $otherBranch->id]);

        $this->actingAs($staff)
            ->get(route('admin.tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_branch_staff_cannot_change_another_branches_ticket_status(): void
    {
        $otherBranch = Branch::factory()->create();
        $staff = User::factory()->branchStaff()->create();
        $ticket = Ticket::factory()->create(['branch_id' => $otherBranch->id]);

        $this->actingAs($staff)
            ->patch(route('admin.tickets.status', $ticket), ['status' => 'resolved'])
            ->assertForbidden();

        $this->assertSame('pending', $ticket->fresh()->status->value);
    }

    public function test_branch_staff_can_manage_their_own_branchs_ticket(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->branchStaff($branch)->create();
        $ticket = Ticket::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($staff)
            ->patch(route('admin.tickets.status', $ticket), ['status' => 'resolved'])
            ->assertRedirect();

        $ticket->refresh();
        $this->assertSame('resolved', $ticket->status->value);
        $this->assertNotNull($ticket->resolved_at);
    }

    public function test_head_office_cannot_record_sales_because_it_owns_no_branch(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $item = CatalogItem::factory()->product(FuelType::Unleaded)->create();

        $this->actingAs($admin)
            ->post(route('admin.sales.store'), [
                'date' => today()->toDateString(),
                'rows' => [[
                    'catalog_item_id' => $item->id,
                    'quantity' => 100,
                    'unit_price' => 2.50,
                    'payment_method' => 'cash',
                ]],
            ])
            ->assertForbidden();

        $this->assertSame(0, Sale::query()->count());
    }

    public function test_recorded_sales_are_pinned_to_the_recording_users_own_branch(): void
    {
        $ownBranch = Branch::factory()->create();
        $otherBranch = Branch::factory()->create();
        $staff = User::factory()->branchStaff($ownBranch)->create();
        $item = CatalogItem::factory()->product(FuelType::Unleaded)->create();
        $otherBranch->catalogItems()->attach($item, ['is_available' => true]);

        $this->actingAs($staff)
            ->post(route('admin.sales.store'), [
                'date' => today()->toDateString(),
                'rows' => [[
                    'catalog_item_id' => $item->id,
                    'quantity' => 120,
                    'unit_price' => 2.50,
                    'payment_method' => 'cash',
                ]],
            ])
            ->assertRedirect();

        // The branch always comes from the actor, so a sale posted while signed
        // in at one station can never land on another station's books.
        $this->assertDatabaseHas('sales', [
            'branch_id' => $ownBranch->id,
            'catalog_item_id' => $item->id,
            'recorded_by' => $staff->id,
        ]);

        $this->assertDatabaseMissing('sales', [
            'branch_id' => $otherBranch->id,
        ]);
    }

    public function test_recording_sales_twice_for_one_day_replaces_rather_than_duplicates(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->branchStaff($branch)->create();
        $item = CatalogItem::factory()->product(FuelType::Diesel)->create();

        $payload = [
            'date' => today()->toDateString(),
            'replace_existing' => '1',
            'rows' => [[
                'catalog_item_id' => $item->id,
                'quantity' => 100,
                'unit_price' => 2.60,
                'payment_method' => 'card',
            ]],
        ];

        $this->actingAs($staff)->post(route('admin.sales.store'), $payload)->assertRedirect();
        $this->actingAs($staff)->post(route('admin.sales.store'), $payload)->assertRedirect();

        $this->assertSame(1, Sale::query()->count());
    }

    public function test_price_change_requests_cannot_be_submitted_for_another_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $staff = User::factory()->branchStaff()->create();
        $item = CatalogItem::factory()->product(FuelType::Diesel)->create();

        $this->actingAs($staff)
            ->post(route('admin.price-changes.store'), [
                'branch_id' => $otherBranch->id,
                'catalog_item_id' => $item->id,
                'new_price' => 2.90,
            ])
            ->assertForbidden();

        $this->assertSame(0, PriceChangeRequest::query()->count());
    }

    public function test_branch_staff_cannot_approve_their_own_price_change(): void
    {
        $branch = Branch::factory()->create();
        $manager = User::factory()->branchAdmin($branch)->create();
        $item = CatalogItem::factory()->product(FuelType::Diesel)->create();

        $request = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'requested_by' => $manager->id,
            'status' => 'pending',
        ]);

        $this->actingAs($manager)
            ->post(route('admin.price-changes.approve', $request))
            ->assertForbidden();

        $this->assertSame('pending', $request->fresh()->status->value);
    }

    public function test_price_change_requests_are_reused_rather_than_duplicated_for_the_same_item(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->branchStaff($branch)->create();
        $item = CatalogItem::factory()->product(FuelType::Diesel)->create();

        PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'status' => 'pending',
            'requested_by' => $staff->id,
        ]);

        $this->actingAs($staff)
            ->post(route('admin.price-changes.store'), [
                'branch_id' => $branch->id,
                'catalog_item_id' => $item->id,
                'new_price' => 2.75,
                'note' => 'Revised after checking the supplier note.',
            ])
            ->assertRedirect();

        $this->assertSame(1, PriceChangeRequest::query()->count());
        $this->assertSame('2.75', PriceChangeRequest::query()->firstOrFail()->new_price);
    }

    public function test_approving_a_price_change_publishes_the_new_price_and_records_history(): void
    {
        Notification::fake();

        $branch = Branch::factory()->create();
        $staff = User::factory()->branchStaff($branch)->create();
        $admin = User::factory()->superAdmin()->create();
        $item = CatalogItem::factory()->product(FuelType::Unleaded)->create();

        $oldPrice = Price::create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'price' => 2.50,
            'currency' => 'PHP',
            'effective_from' => today()->subMonth(),
            'is_active' => true,
        ]);

        $request = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'requested_by' => $staff->id,
            'status' => 'pending',
            'old_price' => 2.50,
            'new_price' => 2.66,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.price-changes.approve', $request), ['review_note' => 'Matches the wholesale sheet.'])
            ->assertRedirect();

        $request->refresh();
        $this->assertSame('approved', $request->status->value);
        $this->assertSame($admin->id, $request->reviewed_by);

        $oldPrice->refresh();
        $this->assertFalse($oldPrice->is_active, 'The superseded price should be retired.');
        $this->assertNotNull($oldPrice->effective_to);

        $newPrice = Price::query()
            ->where('branch_id', $branch->id)
            ->where('catalog_item_id', $item->id)
            ->where('is_active', true)
            ->firstOrFail();

        $this->assertSame('2.66', $newPrice->price);
        $this->assertSame($admin->id, $newPrice->approved_by);

        $this->assertDatabaseHas('price_histories', [
            'price_id' => $newPrice->id,
            'price_change_request_id' => $request->id,
            'previous_price' => 2.50,
            'price' => 2.66,
        ]);
    }

    public function test_rejecting_a_price_change_leaves_the_live_price_untouched(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->branchStaff($branch)->create();
        $admin = User::factory()->superAdmin()->create();
        $item = CatalogItem::factory()->product(FuelType::Diesel)->create();

        $oldPrice = Price::create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'price' => 2.60,
            'currency' => 'PHP',
            'effective_from' => today()->subMonth(),
            'is_active' => true,
        ]);

        $request = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'requested_by' => $staff->id,
            'status' => 'pending',
            'new_price' => 3.10,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.price-changes.reject', $request), ['review_note' => 'Too high for this quarter.'])
            ->assertRedirect();

        $this->assertSame('rejected', $request->fresh()->status->value);
        $this->assertTrue($oldPrice->fresh()->is_active, 'A rejected change must not touch the live price.');
        $this->assertSame(1, Price::query()->count());
        $this->assertSame(0, PriceHistory::query()->count());
    }

    public function test_rejecting_a_price_change_requires_a_reason(): void
    {
        $branch = Branch::factory()->create();
        $admin = User::factory()->superAdmin()->create();
        $request = PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'pending',
        ]);

        $this->actingAs($admin)
            ->post(route('admin.price-changes.reject', $request))
            ->assertSessionHasErrors('review_note');

        $this->assertSame('pending', $request->fresh()->status->value);
    }

    public function test_an_already_decided_request_cannot_be_approved_again(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $request = PriceChangeRequest::factory()->create(['status' => 'approved']);

        $this->actingAs($admin)
            ->post(route('admin.price-changes.approve', $request))
            ->assertForbidden();
    }

    public function test_branch_staff_cannot_publish_a_price_directly(): void
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->branchStaff($branch)->create();
        $item = CatalogItem::factory()->product(FuelType::Unleaded)->create();

        $this->actingAs($staff)
            ->post(route('admin.prices.store'), [
                'branch_id' => $branch->id,
                'catalog_item_id' => $item->id,
                'price' => 9.99,
                'effective_from' => today()->toDateString(),
            ])
            ->assertForbidden();

        $this->assertSame(0, Price::query()->count());
    }
}
