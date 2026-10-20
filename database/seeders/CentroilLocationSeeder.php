<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class CentroilLocationSeeder extends Seeder
{
    public function run(): void
    {
        $branch = Branch::query()->where('code', 'OIL-006')->firstOrFail();

        $branch->update([
            'address_line1' => 'Tondo, Manila',
            'city' => 'Manila',
            'region' => 'Metro Manila',
            'postal_code' => null,
            'phone' => null,
            'email' => null,
            'latitude' => 14.6327013,
            'longitude' => 120.9750395,
            'longitude' => 120.9750395,
            'description' => 'Oiland Tondo (Centro Oil), located in Tondo, Manila. Diesel, Premium, and Green are available.',
        ]);
    }
}
