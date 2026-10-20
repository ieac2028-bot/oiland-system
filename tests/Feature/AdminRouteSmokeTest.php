<?php

namespace Tests\Feature;

use App\Enums\UserRole;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Walks every GET route the staff console registers and proves it renders.
 *
 * The point is coverage, not behaviour: a template that is referenced by a
 * controller but missing from disk only fails the moment somebody clicks the
 * link, and `view:cache` will happily compile a template that references an
 * undefined variable. Behaviour and authorisation live in the feature tests.
 */
class AdminRouteSmokeTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Placeholder values for the route parameters, keyed by parameter name.
     *
     * @var array<string, int>
     */
    private array $parameters = [];

    protected function setUp(): void
    {
        parent::setUp();

        $branch = Branch::factory()->create(['name' => 'Smoke Station']);

        $product = CatalogItem::factory()->product()->create();
        $service = CatalogItem::factory()->service()->create();
        $branch->catalogItems()->attach([$product->id, $service->id], ['is_available' => true]);

        Price::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $product->id,
            'price' => 2.50,
            'is_active' => true,
        ]);

        Sale::factory()->create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $product->id,
        ]);

        $this->parameters = [
            // Branch, announcement, and catalog items bind by slug, so the
            // route key has to be used rather than the primary key.
            'branch' => $branch->slug,
            'user' => User::factory()->superAdmin()->create()->id,
            'price' => Price::firstOrFail()->id,
            'priceChangeRequest' => PriceChangeRequest::factory()->create()->id,
            'priceChange' => PriceChangeRequest::firstOrFail()->id,
            'ticket' => Ticket::factory()->create(['branch_id' => $branch->id])->id,
            'review' => Review::factory()->create(['branch_id' => $branch->id])->id,
            'announcement' => Announcement::factory()->create()->slug,
            'catalogItem' => $product->slug,
            'activityLog' => $this->firstActivityLogId(),
            'sale' => Sale::firstOrFail()->id,
        ];
    }

    public function test_every_staff_console_get_route_renders_for_head_office(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->assertAllRender($this->consoleRoutes(), $admin, [
            // Raising a price change is a branch action; head office only
            // approves. See PriceChangeRequestPolicy::create().
            'admin.price-changes.create',
            // Booking and importing figures both need a branch to book
            // against, which head office does not have.
            'admin.sales.create',
            'admin.sales.import',
            'admin.sales.import.template',
        ]);

        $this->assertGreaterThanOrEqual(30, $this->consoleRoutes()->count(), 'Expected the staff console to expose at least 30 GET routes.');
    }

    public function test_every_branch_scoped_get_route_renders_for_branch_staff(): void
    {
        $staff = User::factory()->branchStaff(Branch::first())->create();

        $this->assertAllRender($this->branchScopedRoutes(), $staff);
    }

    /**
     * Assert every route in the collection returns 200, reporting the whole
     * list of offenders rather than only the first one.
     *
     * @param  Collection<string, \Illuminate\Routing\Route>  $routes
     * @param  array<int, string>  $expectedForbidden  route names that must return 403 instead
     */
    private function assertAllRender(Collection $routes, User $actor, array $expectedForbidden = []): void
    {
        $broken = [];
        $forbidden = [];

        foreach ($routes as $name => $route) {
            $url = $this->urlFor($route, $name);
            $status = $this->actingAs($actor)->get($url)->getStatusCode();

            if (in_array($name, $expectedForbidden, true)) {
                if ($status !== 403) {
                    $forbidden[] = "{$name} ({$url}) => {$status}, expected 403";
                }

                continue;
            }

            if ($status !== 200) {
                $broken[] = "{$name} ({$url}) => {$status}";
            }
        }

        $this->assertSame([], $broken, "These staff console routes did not render:\n".implode("\n", $broken));
        $this->assertSame([], $forbidden, "These routes were expected to be forbidden:\n".implode("\n", $forbidden));
    }

    /**
     * Every named GET route in the staff console group.
     *
     * @return Collection<string, \Illuminate\Routing\Route>
     */
    private function consoleRoutes(): Collection
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => in_array('GET', $route->methods(), true))
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'admin.'))
            ->keyBy(fn ($route) => $route->getName());
    }

    /**
     * The subset a branch staff member is allowed to open: everything that is
     * not inside the head office only group.
     *
     * @return Collection<string, \Illuminate\Routing\Route>
     */
    private function branchScopedRoutes(): Collection
    {
        return $this->consoleRoutes()
            ->reject(fn ($route) => str_contains(
                implode(',', $route->gatherMiddleware()),
                'role:'.UserRole::SuperAdmin->value,
            ));
    }

    /**
     * Fill the route's parameters with the seeded model identifiers.
     */
    private function urlFor(\Illuminate\Routing\Route $route, string $name): string
    {
        $parameters = [];

        foreach ($route->parameterNames() as $parameter) {
            $parameters[$parameter] = $this->parameters[$parameter] ?? 1;
        }

        return route($name, $parameters);
    }

    private function firstActivityLogId(): int
    {
        if (ActivityLog::query()->exists()) {
            return ActivityLog::firstOrFail()->id;
        }

        app(ActivityLogger::class)->log(
            ActivityAction::BRANCH_CREATED,
            'Recorded an entry so the activity log screen has something to show.',
            Branch::first(),
        );

        return ActivityLog::firstOrFail()->id;
    }
}
