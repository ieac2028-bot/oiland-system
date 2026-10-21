<?php

namespace Tests\Feature;

use App\Enums\FuelType;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Price;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_renders_for_guests(): void
    {
        Branch::factory()->count(3)->create();
        Review::factory()->count(2)->create();

        $this->get(route('home'))
            ->assertOk()
            ->assertSee(config('app.name'), false);
    }

    public function test_branch_directory_renders_and_lists_active_branches(): void
    {
        $visible = Branch::factory()->create(['name' => 'Visible Station']);
        Branch::factory()->inactive()->create(['name' => 'Retired Station']);

        $this->get(route('branches.index'))
            ->assertOk()
            ->assertSee('Visible Station')
            ->assertDontSee('Retired Station');
    }

    public function test_branch_directory_filters_by_city_and_search_term(): void
    {
        Branch::factory()->create(['name' => 'Marina Pump', 'city' => 'Dubai', 'region' => 'Dubai']);
        Branch::factory()->create(['name' => 'Falcon Station', 'city' => 'Sharjah', 'region' => 'Sharjah']);

        $this->get(route('branches.index', ['city' => 'Dubai']))
            ->assertOk()
            ->assertSee('Marina Pump')
            ->assertDontSee('Falcon Station');

        $this->get(route('branches.index', ['q' => 'Falcon']))
            ->assertOk()
            ->assertSee('Falcon Station')
            ->assertDontSee('Marina Pump');
    }

    public function test_branch_detail_renders_prices_products_and_reviews(): void
    {
        $branch = Branch::factory()->create(['name' => 'Sunset Forecourt']);
        $item = CatalogItem::factory()->product(FuelType::Unleaded)->create(['name' => 'Green']);

        $branch->catalogItems()->attach($item, ['is_available' => true]);

        Price::create([
            'branch_id' => $branch->id,
            'catalog_item_id' => $item->id,
            'price' => 2.55,
            'currency' => 'PHP',
            'effective_from' => today(),
            'is_active' => true,
        ]);

        Review::factory()->create([
            'branch_id' => $branch->id,
            'status' => 'published',
            'is_flagged' => false,
        ]);

        $this->get(route('branches.show', $branch))
            ->assertOk()
            ->assertSee('Sunset Forecourt')
            ->assertSee('Green')
            ->assertSee('2.55');
    }

    public function test_inactive_branches_are_not_reachable_from_the_directory(): void
    {
        $branch = Branch::factory()->inactive()->create();

        $this->get(route('branches.show', $branch))->assertNotFound();
    }

    public function test_branch_directory_exposes_openstreetmap_configuration_to_the_locator(): void
    {
        config()->set('maps.tile_url', 'https://tiles.example.test/{z}/{x}/{y}.png');
        config()->set('maps.tile_attribution', 'Test attribution');
        config()->set('maps.max_zoom', 17);

        Branch::factory()->create();

        $this->get(route('branches.index'))
            ->assertOk()
            ->assertSee('data-tile-url="https://tiles.example.test/{z}/{x}/{y}.png"', false)
            ->assertSee('data-tile-attribution="Test attribution"', false)
            ->assertSee('data-max-zoom="17"', false)
            ->assertSee('data-osrm-url="'.config('maps.osrm_url').'"', false)
            ->assertSee('data-nominatim-url="'.config('maps.nominatim_search_url').'"', false);
    }

    public function test_branch_directory_falls_back_to_philippine_coordinates_when_map_payload_is_empty(): void
    {
        // No branches, so the map has nothing to average and must fall back to
        // the configured Philippine default rather than a stale Gulf coordinate.
        $this->get(route('branches.index'))
            ->assertOk()
            ->assertSee('data-initial-lat="'.config('maps.default_latitude').'"', false)
            ->assertSee('data-initial-lng="'.config('maps.default_longitude').'"', false);
    }

    public function test_branch_directory_needs_no_api_key_to_render_its_map(): void
    {
        // OpenStreetMap is keyless, so the locator must always render ready and
        // must never fall back to a "configure a key" placeholder.
        $this->get(route('branches.index'))
            ->assertOk()
            ->assertSee('data-locator', false)
            ->assertSee('data-map-state="ready"', false)
            ->assertSee(config('maps.tile_url'), false)
            ->assertDontSee('data-map-placeholder', false)
            ->assertDontSee('Map preview unavailable');
    }

    public function test_branch_route_endpoint_validates_coordinates(): void
    {
        $branch = Branch::factory()->create();

        $this->getJson(route('branches.route', $branch))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lat', 'lng']);
    }

    public function test_branch_route_endpoint_refuses_branches_without_coordinates(): void
    {
        $branch = Branch::factory()->create(['latitude' => null, 'longitude' => null]);

        $this->getJson(route('branches.route', [$branch, 'lat' => 24.4, 'lng' => 54.4]))
            ->assertStatus(422)
            ->assertJsonPath('message', 'This branch has no coordinates yet, so directions cannot be plotted.');
    }

    public function test_branch_route_endpoint_proxies_osrm_results(): void
    {
        Http::fake([
            config('maps.osrm_url').'/*' => Http::response([
                'routes' => [[
                    'distance' => 15234.5,
                    'duration' => 987.6,
                    'geometry' => ['type' => 'LineString', 'coordinates' => [[54.4, 24.4]]],
                ]],
            ]),
        ]);

        $branch = Branch::factory()->create(['latitude' => 24.4539, 'longitude' => 54.4112]);

        $this->getJson(route('branches.route', [$branch, 'lat' => 24.5, 'lng' => 54.5]))
            ->assertOk()
            ->assertJsonPath('ok', true)
            ->assertJsonPath('provider', 'OSRM')
            ->assertJsonPath('distance', 15234.5);
    }

    public function test_branch_route_endpoint_degrades_when_osrm_is_unavailable(): void
    {
        Http::fake([
            config('maps.osrm_url').'/*' => Http::response(['error' => 'unavailable'], 500),
        ]);

        $branch = Branch::factory()->create();

        $this->getJson(route('branches.route', [$branch, 'lat' => 24.5, 'lng' => 54.5]))
            ->assertOk()
            ->assertJsonPath('ok', false)
            ->assertJsonPath('message', 'The routing service returned an error.');
    }

    public function test_branch_route_endpoint_rejects_unknown_profiles(): void
    {
        Http::fake();

        $branch = Branch::factory()->create();

        $this->getJson(route('branches.route', [$branch, 'lat' => 24.5, 'lng' => 54.5, 'profile' => '../../admin']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['profile']);

        Http::assertNothingSent();
    }

    public function test_branch_route_endpoint_is_rate_limited(): void
    {
        Http::fake([
            config('maps.osrm_url').'/*' => Http::response(['routes' => []]),
        ]);

        $branch = Branch::factory()->create();
        $url = route('branches.route', [$branch, 'lat' => 24.5, 'lng' => 54.5]);

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->getJson($url)->assertOk();
        }

        $this->getJson($url)->assertStatus(429);
    }

    public function test_sales_totals_are_not_leaked_to_the_public_directory(): void
    {
        $this->get(route('branches.index'))->assertDontSee('total_amount');
    }
}
