<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Seeder;

class BranchLocationUpdatesSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            'OIL-004' => [
                'address_line1' => '2 Commercial Development Center, La Loma',
                'city' => 'Quezon City',
                'region' => 'Metro Manila',
                'postal_code' => null,
                // Approximate N.S. Amoranto / La Loma area coordinate.
                'latitude' => 14.632399,
                'longitude' => 120.993275,
            ],
            'OIL-009' => [
                'address_line1' => '4W3R+4JR, Mahogany Ave',
                'city' => 'Tagaytay City',
                'region' => 'Cavite',
                'postal_code' => null,
                'latitude' => 14.1028625,
                'longitude' => 120.9915156,
            ],
            'OIL-028' => [
                'address_line1' => 'QGGV+H32, National Rd',
                'city' => 'Samal',
                'region' => 'Bataan',
                'postal_code' => null,
                'latitude' => 14.7763875,
                'longitude' => 120.5426406,
            ],
            'OIL-030' => [
                'address_line1' => '897 Santa Maria - Tungkong Mangga Rd',
                'city' => 'Santa Maria',
                'region' => 'Bulacan',
                'postal_code' => '3020',
                // Approximate San Vicente area pin; exact house-number geocoding was unavailable.
                'latitude' => 14.80783,
                'longitude' => 121.00263,
            ],
            'OIL-021' => [
                'address_line1' => '69G7+9FV, Calumpang Road, Calumpang',
                'city' => 'Pila',
                'region' => 'Laguna',
                'postal_code' => '4010',
                'latitude' => 14.2259875,
                'longitude' => 121.3637031,
            ],
        ];

        foreach ($locations as $code => $attributes) {
            Branch::query()->where('code', $code)->firstOrFail()->update($attributes);
        }
    }
}
