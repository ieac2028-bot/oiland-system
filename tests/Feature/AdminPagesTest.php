<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Announcement;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Price;
use App\Models\PriceChangeRequest;
use App\Models\Review;
use App\Models\Sale;
use App\Models\Ticket;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\ActivityAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Every staff console screen must render for a signed-in staff member.
 *
 * These are deliberately shallow: the point is to prove each Blade template
 * compiles and receives the variables its controller passes. Behaviour and
 * authorisation are covered by the dedicated feature tests.
 */
class AdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private function seedFixtures(): void
    {
        $branch = Branch::factory()->create(['name' => 'Test Station']);
        $item = CatalogItem::factory()->product()->create();
        $branch->catalogItems()->attach($item, ['is_available' => true]);

        Price::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'price' => 2.50,
            'is_active' => true,
        ]);

        PriceChangeRequest::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
        ]);

        Sale::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
        ]);

        Review::factory()->create([
            'branch_id' => $branch->id,
            'user_id' => User::factory()->customer()->create()->id,
        ]);

        Ticket::factory()->create([
            'branch_id' => $branch->id,
            'customer_id' => User::factory()->customer()->create()->id,
        ]);

        Announcement::factory()->create();

        // No factory exists for the audit trail, so record one the way the
        // application does.
        if (ActivityLog::query()->count() === 0) {
            app(ActivityLogger::class)->log(
                ActivityAction::BRANCH_CREATED,
                'Seeded an activity log entry for the admin screen tests.',
                $branch,
            );
        }
    }

    public function test_the_dashboard_renders_for_head_office(): void
    {
        $this->seedFixtures();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_the_dashboard_renders_for_a_branch_manager(): void
    {
        $this->seedFixtures();

        $this->actingAs(User::factory()->branchAdmin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk();
    }

    public function test_the_catalog_screens_render(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $item = CatalogItem::first();

        $this->actingAs($admin)->get(route('admin.catalog.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.catalog.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.catalog.edit', $item))->assertOk();
    }

    public function test_the_catalog_index_respects_its_filters(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('admin.catalog.index', ['q' => 'Test', 'type' => 'product', 'status' => 'active']))
            ->assertOk();
    }

    public function test_the_price_screens_render(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $price = Price::first();

        $this->actingAs($admin)->get(route('admin.prices.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.prices.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.prices.show', $price))->assertOk();
    }

    public function test_the_price_index_respects_its_filters(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $price = Price::first();

        $this->actingAs($admin)
            ->get(route('admin.prices.index', [
                'branch_id' => $price->branch_id,
                'catalog_item_id' => $price->catalog_item_id,
            ]))
            ->assertOk();
    }

    public function test_the_price_change_screens_render_for_head_office(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $change = PriceChangeRequest::first();

        $this->actingAs($admin)->get(route('admin.price-changes.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.price-changes.show', $change))->assertOk();

        // Head office publishes prices directly, so it never raises a request
        // against its own approval queue.
        $this->actingAs($admin)->get(route('admin.price-changes.create'))->assertForbidden();
    }

    public function test_the_price_change_screens_render_for_a_branch_manager(): void
    {
        $this->seedFixtures();
        $manager = User::factory()->branchAdmin(Branch::first())->create();
        $change = PriceChangeRequest::first();

        $this->actingAs($manager)->get(route('admin.price-changes.index'))->assertOk();
        $this->actingAs($manager)->get(route('admin.price-changes.create'))->assertOk();
        $this->actingAs($manager)->get(route('admin.price-changes.show', $change))->assertOk();
    }

    public function test_the_price_change_index_respects_its_filters(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $change = PriceChangeRequest::first();

        $this->actingAs($admin)
            ->get(route('admin.price-changes.index', [
                'status' => $change->status->value,
                'branch_id' => $change->branch_id,
            ]))
            ->assertOk();
    }

    public function test_the_ticket_screens_render_for_a_branch(): void
    {
        $this->seedFixtures();
        $staff = User::factory()->branchStaff(Branch::first())->create();
        $ticket = Ticket::first();

        $this->actingAs($staff)->get(route('admin.tickets.index'))->assertOk();
        $this->actingAs($staff)->get(route('admin.tickets.show', $ticket))->assertOk();
    }

    public function test_the_ticket_index_renders_with_filters(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $ticket = Ticket::first();

        $this->actingAs($admin)
            ->get(route('admin.tickets.index', [
                'status' => $ticket->status->value,
                'priority' => $ticket->priority->value,
                'q' => $ticket->reference,
            ]))
            ->assertOk()
            ->assertSee($ticket->reference);
    }

    public function test_the_review_screens_render(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $review = Review::first();

        $this->actingAs($admin)->get(route('admin.reviews.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.reviews.show', $review))->assertOk();
    }

    public function test_the_review_index_renders_with_filters(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $review = Review::first();

        $this->actingAs($admin)
            ->get(route('admin.reviews.index', [
                'status' => $review->status->value,
                'branch_id' => $review->branch_id,
                'flagged' => 1,
            ]))
            ->assertOk();
    }

    public function test_the_branch_screens_render(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $branch = Branch::first();

        $this->actingAs($admin)->get(route('admin.branches.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.branches.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.branches.show', $branch))->assertOk();
        $this->actingAs($admin)->get(route('admin.branches.edit', $branch))->assertOk();
        $this->actingAs($admin)->get(route('admin.branches.availability.edit', $branch))->assertOk();
    }

    public function test_the_branch_index_renders_with_filters(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $branch = Branch::first();

        $this->actingAs($admin)
            ->get(route('admin.branches.index', [
                'q' => $branch->name,
                'region' => $branch->region,
                'status' => 'active',
            ]))
            ->assertOk()
            ->assertSee($branch->name);
    }

    public function test_branch_staff_only_ever_see_their_own_station_in_the_admin_list(): void
    {
        $this->seedFixtures();
        $theirs = Branch::factory()->create(['name' => 'Their Own Station']);
        $staff = User::factory()->branchStaff($theirs)->create();

        $this->actingAs($staff)
            ->get(route('admin.branches.index'))
            ->assertOk()
            ->assertSee('Their Own Station')
            ->assertDontSee('Test Station');
    }

    public function test_the_photo_textarea_is_stored_as_a_clean_array(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();

        // Coordinates are blank, so the controller looks the address up.
        Http::fake(['nominatim.openstreetmap.org/*' => Http::response([], 404)]);

        $response = $this->actingAs($admin)->post(route('admin.branches.store'), [
            'name' => 'Downtown Station',
            'code' => 'DXB-999',
            'address_line1' => 'Sheikh Zayed Road',
            'city' => 'Dubai',
            'region' => 'Dubai',
            'is_active' => '1',
            // A textarea arrives as one string; blank lines must be dropped.
            'photos' => "https://example.com/one.jpg\n\n  https://example.com/two.jpg  \n",
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertSame(
            ['https://example.com/one.jpg', 'https://example.com/two.jpg'],
            Branch::where('code', 'DXB-999')->firstOrFail()->photos,
        );
    }

    public function test_a_station_code_cannot_be_reused(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $branch = Branch::first();

        $this->actingAs($admin)->post(route('admin.branches.store'), [
            'name' => 'Copycat Station',
            'code' => $branch->code,
            'address_line1' => 'Somewhere',
            'city' => 'Dubai',
            'region' => 'Dubai',
        ])->assertSessionHasErrors('code');

        // Editing a station must not trip the unique check against itself.
        $this->actingAs($admin)->put(route('admin.branches.update', $branch), [
            'name' => $branch->name,
            'code' => $branch->code,
            'address_line1' => $branch->address_line1,
            'city' => $branch->city,
            'region' => $branch->region,
        ])->assertSessionHasNoErrors();
    }

    public function test_the_user_and_announcement_screens_render(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $announcement = Announcement::first();
        $staff = User::factory()->branchStaff(Branch::first())->create();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.users.edit', $staff))->assertOk();

        $this->actingAs($admin)->get(route('admin.announcements.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.announcements.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.announcements.edit', $announcement))->assertOk();
    }

    public function test_the_activity_log_screens_render(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('admin.activity-log.index'))->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.activity-log.show', ActivityLog::firstOrFail()))
            ->assertOk();
    }

    public function test_the_activity_log_index_renders_with_filters(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();
        $log = ActivityLog::firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.activity-log.index', [
                'q' => $log->user_name,
                'action' => $log->action,
                'branch_id' => $log->branch_id,
            ]))
            ->assertOk();
    }

    public function test_a_branch_manager_cannot_see_another_branchs_activity(): void
    {
        $this->seedFixtures();
        $other = Branch::factory()->create(['name' => 'Somewhere Else']);
        app(ActivityLogger::class)->log(
            ActivityAction::BRANCH_CREATED,
            'Logged against a different station.',
            $other,
        );

        $manager = User::factory()->branchAdmin(Branch::first())->create();

        $this->actingAs($manager)
            ->get(route('admin.activity-log.index'))
            ->assertOk()
            ->assertDontSee('Somewhere Else');
    }

    public function test_the_sales_screens_render_for_a_branch(): void
    {
        $this->seedFixtures();
        $staff = User::factory()->branchStaff(Branch::first())->create();

        $this->actingAs($staff)->get(route('admin.sales.index'))->assertOk();
        $this->actingAs($staff)->get(route('admin.sales.create'))->assertOk();
        $this->actingAs($staff)->get(route('admin.sales.import'))->assertOk();
        $this->actingAs($staff)->get(route('admin.sales.dashboard'))->assertOk();
    }

    public function test_the_sales_screens_render_for_head_office(): void
    {
        $this->seedFixtures();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('admin.sales.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.sales.dashboard'))->assertOk();

        // Head office owns no branch, so it cannot book figures against one.
        $this->actingAs($admin)->get(route('admin.sales.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('admin.sales.import'))->assertForbidden();
    }
}
