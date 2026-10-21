<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SalesImportTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    private CatalogItem $item;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create();
        $this->item = CatalogItem::factory()->product()->create(['name' => 'Green']);
        $this->branch->catalogItems()->attach($this->item, ['is_available' => true]);
        $this->staff = User::factory()->branchStaff($this->branch)->create();
    }

    private function upload(string $csv)
    {
        return $this->actingAs($this->staff)->post(route('admin.sales.import.store'), [
            'file' => UploadedFile::fake()->createWithContent('sales.csv', $csv),
        ]);
    }

    public function test_a_valid_file_is_imported_against_the_staff_members_branch(): void
    {
        $date = today()->subDay()->toDateString();

        $this->upload("sale_date,product,quantity,unit_price,payment_method\n{$date},Green,120,2.45,cash\n")
            ->assertOk();

        $this->assertDatabaseCount('sales', 1);
        $this->assertSame($this->branch->id, Sale::first()->branch_id);
    }

    public function test_reuploading_the_same_file_does_not_double_count(): void
    {
        $date = today()->subDay()->toDateString();
        $csv = "sale_date,product,quantity,unit_price,payment_method\n{$date},Green,120,2.45,cash\n";

        $this->upload($csv)->assertOk();
        $this->upload($csv)
            ->assertOk()
            ->assertSee('already has sales recorded');

        $this->assertDatabaseCount('sales', 1);
    }

    public function test_rows_with_the_wrong_number_of_columns_are_reported_with_their_line_number(): void
    {
        $date = today()->subDay()->toDateString();

        $this->upload("sale_date,product,quantity,unit_price,payment_method\n{$date},Green,120,2.45,cash\nbroken,row\n")
            ->assertOk()
            ->assertSee('Row 3: has the wrong number of columns');

        $this->assertDatabaseCount('sales', 1);
    }

    public function test_future_dates_are_rejected(): void
    {
        $date = today()->addDay()->toDateString();

        $this->upload("sale_date,product,quantity,unit_price,payment_method\n{$date},Green,120,2.45,cash\n")
            ->assertOk()
            ->assertSee('is in the future');

        $this->assertDatabaseCount('sales', 0);
    }

    public function test_a_header_only_file_shows_a_message_instead_of_failing(): void
    {
        $this->upload("sale_date,product,quantity,unit_price,payment_method\n")
            ->assertOk()
            ->assertSee('no usable data rows');
    }

    public function test_posting_without_a_file_is_a_validation_error(): void
    {
        $this->actingAs($this->staff)
            ->post(route('admin.sales.import.store'))
            ->assertSessionHasErrors('file');
    }

    public function test_the_csv_export_neutralises_formula_cells(): void
    {
        Sale::factory()->create([
            'branch_id' => $this->branch->id,
            'catalog_item_id' => $this->item->id,
            'item_name' => '=HYPERLINK("http://example.test","click")',
            'sale_date' => today(),
        ]);

        $response = $this->actingAs($this->staff)->get(route('admin.sales.export.csv'));

        $response->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
    }
}
