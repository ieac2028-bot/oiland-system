<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database in dependency order: catalog first, then
     * the branch network and its staff, then the transactional demo data that
     * references both.
     *
     * Model events stay enabled because the `Ticket` and `Announcement` models
     * generate their reference and slug in `booted()`.
     */
    public function run(): void
    {
        $this->call([
            CatalogSeeder::class,
            NetworkSeeder::class,
            OperationsSeeder::class,
            NetworkFuelOfferingsSeeder::class,
        ]);
    }
}
