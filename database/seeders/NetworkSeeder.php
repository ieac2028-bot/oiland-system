<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Branch;
use App\Models\CatalogItem;
use App\Models\User;
use Database\Factories\BranchFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class NetworkSeeder extends Seeder
{
    /**
     * Head office account used for every sign-in across the platform.
     */
    public const SUPER_ADMIN_EMAIL = 'admin@oiland.test';

    /**
     * Shared demo password for all seeded accounts.
     */
    public const DEMO_PASSWORD = 'password';

    /**
     * A single pre-computed hash of {@see self::DEMO_PASSWORD}. Re-hashing for
     * every seeded account would dominate the seeder runtime.
     */
    public static function demoPasswordHash(): string
    {
        static $hash = null;

        return $hash ??= Hash::make(self::DEMO_PASSWORD);
    }

    /**
     * Network footprint across Luzon and Mindanao. Addresses and coordinates
     * are representative of each municipality and are meant to be corrected
     * from the admin branch editor with the exact site details.
     *
     * @var array<int, array<string, mixed>>
     */
    private const BRANCHES = [
        [
            'name' => 'Abucay',
            'code' => 'OIL-001',
            'address_line1' => 'Rizal Avenue, Abucay',
            'city' => 'Abucay',
            'region' => 'Bataan',
            'postal_code' => '1114',
            'phone' => '+63 47 555 1001',
            'email' => 'abucay@oiland.test',
            'latitude' => 14.6656,
            'longitude' => 120.5323,
            'is_24_hours' => true,
            'description' => 'Flagship 24-hour station on Rizal Avenue with eight forecourts, a full car wash and diesel bays for light commercial vehicles serving the Bataan corridor.',
        ],
        [
            'name' => 'Alfonso',
            'code' => 'OIL-002',
            'address_line1' => 'Alfonso-Silang Road, Poblacion',
            'city' => 'Alfonso',
            'region' => 'Cavite',
            'postal_code' => '4124',
            'phone' => '+63 49 555 1002',
            'email' => 'alfonso@oiland.test',
            'latitude' => 14.0667,
            'longitude' => 120.8578,
            'is_24_hours' => false,
            'description' => 'Tagaytay approach station with premium and diesel pumps, convenience retail and two EV charging bays near the Alfonso tourism belt.',
        ],
        [
            'name' => 'Amadeo',
            'code' => 'OIL-003',
            'address_line1' => 'Rizal Highway, Barangay Poblacion',
            'city' => 'Amadeo',
            'region' => 'Cavite',
            'postal_code' => '4122',
            'phone' => '+63 49 555 1003',
            'email' => 'amadeo@oiland.test',
            'latitude' => 14.0039,
            'longitude' => 120.9736,
            'is_24_hours' => false,
            'description' => 'Rizal Highway commuter station on the Cavite-Batangas route, popular with tricycles and weekend visitors to the ridge resorts.',
        ],
        [
            'name' => 'Amoranto',
            'code' => 'OIL-004',
            'address_line1' => '2 Commercial Development Center, La Loma',
            'city' => 'Quezon City',
            'region' => 'Metro Manila',
            'postal_code' => null,
            'phone' => '+63 53 555 1004',
            'email' => 'amoranto@oiland.test',
            'latitude' => 14.632399,
            'longitude' => 120.993275,
            'is_24_hours' => true,
            'description' => 'Round-the-clock station on the Ormoc national road serving cargo fleets, fishing vessels and intercity passengers.',
        ],
        [
            'name' => 'Baras',
            'code' => 'OIL-005',
            'address_line1' => 'Baras-GTan Road, Barangay Bayanan',
            'city' => 'Baras',
            'region' => 'Rizal',
            'postal_code' => '1210',
            'phone' => '+63 8 555 1005',
            'email' => 'baras@oiland.test',
            'latitude' => 14.5086,
            'longitude' => 121.4375,
            'is_24_hours' => true,
            'description' => 'Modern inland station with a workshop bay, alignment rack and premium 95 performance fuel on the Baras bypass.',
        ],
        [
            'name' => 'Centroil',
            'code' => 'OIL-006',
            'address_line1' => 'Tondo, Manila',
            'city' => 'Manila',
            'region' => 'Metro Manila',
            'postal_code' => null,
            'phone' => null,
            'email' => null,
            'latitude' => 14.6327013,
            'longitude' => 120.9750395,
            'is_24_hours' => true,
            'description' => 'Oiland Tondo (Centro Oil), located in Tondo, Manila.',
        ],
        [
            'name' => 'Dagatan',
            'code' => 'OIL-007',
            'address_line1' => 'Bulacan-Tarlac Road, Barangay Dagatan',
            'city' => 'Dagatan',
            'region' => 'Bulacan',
            'postal_code' => '3026',
            'phone' => '+63 44 555 1007',
            'email' => 'dagatan@oiland.test',
            'latitude' => 14.9333,
            'longitude' => 120.9333,
            'is_24_hours' => false,
            'description' => 'Family friendly station near the Dagatan market with a full service centre, car wash and convenience store.',
        ],
        [
            'name' => 'Deparo',
            'code' => 'OIL-008',
            'address_line1' => 'Deparo-Camias Road, Barangay 164',
            'city' => 'Caloocan City',
            'region' => 'Metro Manila',
            'postal_code' => '1100',
            'phone' => '+63 8 555 1008',
            'email' => 'deparo@oiland.test',
            'latitude' => 14.6833,
            'longitude' => 120.9833,
            'is_24_hours' => true,
            'description' => 'High-volume station on the Deparo-Camias road serving North Caloocan residential and light trade traffic.',
        ],
        [
            'name' => 'Drising',
            'code' => 'OIL-009',
            'address_line1' => '4W3R+4JR, Mahogany Ave',
            'city' => 'Tagaytay City',
            'region' => 'Cavite',
            'postal_code' => null,
            'phone' => '+63 83 555 1009',
            'email' => 'drising@oiland.test',
            'latitude' => 14.1028625,
            'longitude' => 120.9915156,
            'is_24_hours' => true,
            'description' => 'Open 24 hours on Mahogany Avenue in Tagaytay City.',
        ],
        [
            'name' => 'Gentri 1',
            'code' => 'OIL-010',
            'address_line1' => 'Guentri-Bacoan Road, Bancal',
            'city' => 'Bacoor',
            'region' => 'Cavite',
            'postal_code' => '4101',
            'phone' => '+63 8 555 1010',
            'email' => 'gentri1@oiland.test',
            'latitude' => 14.1667,
            'longitude' => 120.9333,
            'is_24_hours' => false,
            'description' => 'First of the Guentri cluster stations, a busy commuter stop on the Bacoor-Montecillo road with premium and diesel pumps.',
        ],
        [
            'name' => 'Gentri 2',
            'code' => 'OIL-011',
            'address_line1' => 'Mambog-Molino Road, Mambog',
            'city' => 'Bacoor',
            'region' => 'Cavite',
            'postal_code' => '4101',
            'phone' => '+63 8 555 1011',
            'email' => 'gentri2@oiland.test',
            'latitude' => 14.1736,
            'longitude' => 120.9411,
            'is_24_hours' => true,
            'description' => 'Round-the-clock sister station to Gentri 1, positioned to cover overnight traffic between Las Piñas and Bacoor.',
        ],
        [
            'name' => 'Guiguinto',
            'code' => 'OIL-012',
            'address_line1' => 'MacArthur Highway, Guiguinto',
            'city' => 'Guiguinto',
            'region' => 'Bulacan',
            'postal_code' => '3027',
            'phone' => '+63 44 555 1012',
            'email' => 'guiguinto@oiland.test',
            'latitude' => 14.8333,
            'longitude' => 120.9167,
            'is_24_hours' => true,
            'description' => 'Major MacArthur Highway station with truck parking, 24-hour diesel and convenience retail serving the North Bulacan corridor.',
        ],
        [
            'name' => 'Langkiwa New',
            'code' => 'OIL-013',
            'address_line1' => 'Dasmariñas-Batangas Road, Langka',
            'city' => 'Dasmariñas',
            'region' => 'Cavite',
            'postal_code' => '4122',
            'phone' => '+63 49 555 1013',
            'email' => 'langkiwa@oiland.test',
            'latitude' => 14.2039,
            'longitude' => 120.9822,
            'is_24_hours' => false,
            'description' => 'Modern station serving the dense Dasmariñas residential belt, with a car wash and two EV charging bays.',
        ],
        [
            'name' => 'Llano',
            'code' => 'OIL-014',
            'address_line1' => '69G7+9FV, Calumpang Road, Calumpang',
            'city' => 'Llano',
            'region' => 'Batangas',
            'postal_code' => '4217',
            'phone' => '+63 49 555 1014',
            'email' => 'llano@oiland.test',
            'latitude' => 13.9333,
            'longitude' => 121.1667,
            'is_24_hours' => false,
            'description' => 'Nasugbu-bound station with premium fuel and a small diner, popular with travellers to the Batangas coastal road.',
        ],
        [
            'name' => 'Marilao',
            'code' => 'OIL-015',
            'address_line1' => 'Bulacan-Manila Road, Santa Maria',
            'city' => 'Marilao',
            'region' => 'Bulacan',
            'postal_code' => '3010',
            'phone' => '+63 44 555 1015',
            'email' => 'marilao@oiland.test',
            'latitude' => 14.7833,
            'longitude' => 120.9833,
            'is_24_hours' => true,
            'description' => '24-hour station on the Marilao highway with high-flow diesel and a service centre for provincial bus operators.',
        ],
        [
            'name' => 'Mendez',
            'code' => 'OIL-016',
            'address_line1' => 'Mendez-Tagaytay Road, Mendez',
            'city' => 'General Emilio Aguinaldo',
            'region' => 'Cavite',
            'postal_code' => '4124',
            'phone' => '+63 49 555 1016',
            'email' => 'mendez@oiland.test',
            'latitude' => 14.1333,
            'longitude' => 120.9417,
            'is_24_hours' => false,
            'description' => 'Gateway station at the Mendez tagaytay road with premium fuel and a convenience retail outlet.',
        ],
        [
            'name' => 'Natatas',
            'code' => 'OIL-017',
            'address_line1' => 'Lipa-Batangas Road, Barangay Natatas',
            'city' => 'Lipa City',
            'region' => 'Batangas',
            'postal_code' => '4217',
            'phone' => '+63 49 555 1017',
            'email' => 'natatas@oiland.test',
            'latitude' => 13.9167,
            'longitude' => 121.1667,
            'is_24_hours' => false,
            'description' => 'Busy Lipa corridor station with diesel bays for agricultural trucks and a retail annex.',
        ],
        [
            'name' => 'Norzagaray',
            'code' => 'OIL-018',
            'address_line1' => 'Norzagaray Road, Poblacion',
            'city' => 'Norzagaray',
            'region' => 'Bulacan',
            'postal_code' => '3013',
            'phone' => '+63 44 555 1018',
            'email' => 'norzagaray@oiland.test',
            'latitude' => 14.8167,
            'longitude' => 120.9489,
            'is_24_hours' => true,
            'description' => '24-hour station on the Norzagaray road serving the Angat dam municipal area and provincial commuters.',
        ],
        [
            'name' => 'Pateros 1',
            'code' => 'OIL-019',
            'address_line1' => 'J.P. Rizal Street, Barangay San Roque',
            'city' => 'Pateros',
            'region' => 'Metro Manila',
            'postal_code' => '1110',
            'phone' => '+63 8 555 1019',
            'email' => 'pateros1@oiland.test',
            'latitude' => 14.5333,
            'longitude' => 121.0667,
            'is_24_hours' => true,
            'description' => 'Compact 24-hour station in the heart of Pateros, built for high-turnover city traffic and delivery fleets.',
        ],
        [
            'name' => 'Pateros 2',
            'code' => 'OIL-020',
            'address_line1' => 'Muntinlupa Road, Barangay Aguho',
            'city' => 'Pateros',
            'region' => 'Metro Manila',
            'postal_code' => '1110',
            'phone' => '+63 8 555 1020',
            'email' => 'pateros2@oiland.test',
            'latitude' => 14.5389,
            'longitude' => 121.0722,
            'is_24_hours' => false,
            'description' => 'Second Pateros site on the Aguho side, with convenience retail and premium fuel for city commuters.',
        ],
        [
            'name' => 'Pila',
            'code' => 'OIL-021',
            'address_line1' => 'National Highway, Barangay Poblacion',
            'city' => 'Pila',
            'region' => 'Laguna',
            'postal_code' => '4010',
            'phone' => '+63 49 555 1021',
            'email' => 'pila@oiland.test',
            'latitude' => 14.2259875,
            'longitude' => 121.3637031,
            'is_24_hours' => false,
            'description' => 'Laguna-bound station on the Pila national highway with premium diesel and a small retail outlet.',
        ],
        [
            'name' => 'Primoil',
            'code' => 'OIL-022',
            'address_line1' => 'P. Burgos Street, Batangas City',
            'city' => 'Batangas City',
            'region' => 'Batangas',
            'postal_code' => '4200',
            'phone' => '+63 42 555 1022',
            'email' => 'primoil@oiland.test',
            'latitude' => 13.7560,
            'longitude' => 121.0630,
            'is_24_hours' => true,
            'description' => 'Port-adjacent station serving the Batangas City industrial and cargo traffic with 24-hour diesel and a truck lounge.',
        ],
        [
            'name' => 'Pugad Baboy',
            'code' => 'OIL-023',
            'address_line1' => 'Rizal Avenue Extension, Pugad Baboy',
            'city' => 'Caloocan City',
            'region' => 'Metro Manila',
            'postal_code' => '1100',
            'phone' => '+63 8 555 1023',
            'email' => 'pugadbaboy@oiland.test',
            'latitude' => 14.7000,
            'longitude' => 120.9667,
            'is_24_hours' => true,
            'description' => '24-hour station in the dense Pugad Baboy residential area, with premium fuel and a full car wash.',
        ],
        [
            'name' => 'Pulong Buhangin',
            'code' => 'OIL-024',
            'address_line1' => 'MacArthur Highway, Pulong Buhangin',
            'city' => 'Caloocan City',
            'region' => 'Metro Manila',
            'postal_code' => '1100',
            'phone' => '+63 8 555 1024',
            'email' => 'pulongbuhangin@oiland.test',
            'latitude' => 14.7000,
            'longitude' => 120.9500,
            'is_24_hours' => false,
            'description' => 'MacArthur Highway station with diesel lanes for provincial buses and a convenience retail outlet.',
        ],
        [
            'name' => 'Punta 1',
            'code' => 'OIL-025',
            'address_line1' => 'Masangkay Street, Barangay Punta',
            'city' => 'Parañaque City',
            'region' => 'Metro Manila',
            'postal_code' => '1700',
            'phone' => '+63 8 555 1025',
            'email' => 'punta1@oiland.test',
            'latitude' => 14.4667,
            'longitude' => 120.9833,
            'is_24_hours' => true,
            'description' => 'First Punta site serving Parañaque and the airport corridor, open 24 hours with EV charging bays.',
        ],
        [
            'name' => 'Rosario',
            'code' => 'OIL-026',
            'address_line1' => 'Rizal Street, Barangay Poblacion',
            'city' => 'Rosario',
            'region' => 'Cavite',
            'postal_code' => '4106',
            'phone' => '+63 49 555 1026',
            'email' => 'rosario@oiland.test',
            'latitude' => 14.4167,
            'longitude' => 121.1833,
            'is_24_hours' => false,
            'description' => 'Historic Cavite town station with premium and diesel pumps, a workshop bay and convenience retail.',
        ],
        [
            'name' => 'Rsouth',
            'code' => 'OIL-027',
            'address_line1' => 'South Boulevard, Baclaran',
            'city' => 'Parañaque City',
            'region' => 'Metro Manila',
            'postal_code' => '1700',
            'phone' => '+63 8 555 1027',
            'email' => 'rsouth@oiland.test',
            'latitude' => 14.4811,
            'longitude' => 120.9911,
            'is_24_hours' => true,
            'description' => '24-hour station on the Parañaque south corridor, positioned to cover airport and residential traffic.',
        ],
        [
            'name' => 'Samal',
            'code' => 'OIL-028',
            'address_line1' => 'QGGV+H32, National Rd',
            'city' => 'Samal',
            'region' => 'Bataan',
            'postal_code' => null,
            'phone' => '+63 82 555 1028',
            'email' => 'samal@oiland.test',
            'latitude' => 14.7763875,
            'longitude' => 120.5426406,
            'is_24_hours' => false,
            'description' => 'Resort island station near the Samal bridge approach, tuned for tourist traffic and ferry queues.',
        ],
        [
            'name' => 'San Pablo',
            'code' => 'OIL-029',
            'address_line1' => 'Rizal Street, Barangay Poblacion',
            'city' => 'San Pablo',
            'region' => 'Laguna',
            'postal_code' => '4050',
            'phone' => '+63 49 555 1029',
            'email' => 'sanpablo@oiland.test',
            'latitude' => 14.0700,
            'longitude' => 121.1900,
            'is_24_hours' => true,
            'description' => 'High-volume 24-hour station in the heart of San Pablo, with a service centre and convenience retail.',
        ],
        [
            'name' => 'San Vicente',
            'code' => 'OIL-030',
            'address_line1' => '897 Santa Maria - Tungkong Mangga Rd',
            'city' => 'Santa Maria',
            'region' => 'Bulacan',
            'postal_code' => '3020',
            'phone' => '+63 45 555 1030',
            'email' => 'sanvicente@oiland.test',
            // Approximate San Vicente area pin; exact house-number geocoding was unavailable.
            'latitude' => 14.80783,
            'longitude' => 121.00263,
            'is_24_hours' => false,
            'description' => 'San Vicente station on Santa Maria - Tungkong Mangga Road in Santa Maria, Bulacan.',
        ],
        [
            'name' => 'Silang',
            'code' => 'OIL-031',
            'address_line1' => 'Tagaytay Road, Barangay VI',
            'city' => 'Silang',
            'region' => 'Cavite',
            'postal_code' => '4118',
            'phone' => '+63 49 555 1031',
            'email' => 'silang@oiland.test',
            'latitude' => 14.0845,
            'longitude' => 120.8421,
            'is_24_hours' => false,
            'description' => 'Tagaytay approach station with premium and diesel pumps and a popular retail forecourt.',
        ],
        [
            'name' => 'SJDM 1',
            'code' => 'OIL-032',
            'address_line1' => 'Bulacan-Manila Road, Barangay Bagong Silang',
            'city' => 'San Jose del Monte',
            'region' => 'Bulacan',
            'postal_code' => '3023',
            'phone' => '+63 44 555 1032',
            'email' => 'sjdm1@oiland.test',
            'latitude' => 14.7667,
            'longitude' => 120.9833,
            'is_24_hours' => true,
            'description' => 'First San Jose del Monte station, open 24 hours on the Bulacan-Manila road with truck parking and retail.',
        ],
        [
            'name' => 'SJDM 2',
            'code' => 'OIL-033',
            'address_line1' => 'Sampaloc Road, Barangay Manggahan',
            'city' => 'San Jose del Monte',
            'region' => 'Bulacan',
            'postal_code' => '3023',
            'phone' => '+63 44 555 1033',
            'email' => 'sjdm2@oiland.test',
            'latitude' => 14.7736,
            'longitude' => 120.9900,
            'is_24_hours' => false,
            'description' => 'Second San Jose del Monte site positioned for the eastern residential and industrial growth corridor.',
        ],
        [
            'name' => 'Sta. Maria',
            'code' => 'OIL-034',
            'address_line1' => 'Santa Maria Bypass Road, Poblacion',
            'city' => 'Santa Maria',
            'region' => 'Bulacan',
            'postal_code' => '3022',
            'phone' => '+63 44 555 1034',
            'email' => 'stamaria@oiland.test',
            'latitude' => 14.7833,
            'longitude' => 121.1167,
            'is_24_hours' => false,
            'description' => 'Bypass-road station serving the Santa Maria rice and poultry belt with diesel and convenience retail.',
        ],
        [
            'name' => 'Tanza',
            'code' => 'OIL-035',
            'address_line1' => 'Tanza-Laguna Road, Poblacion',
            'city' => 'Tanza',
            'region' => 'Batangas',
            'postal_code' => '4230',
            'phone' => '+63 49 555 1035',
            'email' => 'tanza@oiland.test',
            'latitude' => 14.1000,
            'longitude' => 120.8500,
            'is_24_hours' => false,
            'description' => 'Tanza town station on the Cavite-Laguna road, a busy stop for agricultural and provincial traffic.',
        ],
        [
            'name' => 'Ternate Old',
            'code' => 'OIL-036',
            'address_line1' => 'Ternate Naverlove Road, Poblacion',
            'city' => 'Ternate',
            'region' => 'Cavite',
            'postal_code' => '4113',
            'phone' => '+63 49 555 1036',
            'email' => 'ternateold@oiland.test',
            'latitude' => 14.2333,
            'longitude' => 120.8333,
            'is_24_hours' => false,
            'description' => 'Original Ternate site retained as a 24-hour diesel stop for the Naverlove and Cavite-Batangas routes.',
        ],
        [
            'name' => 'Trapiche',
            'code' => 'OIL-037',
            'address_line1' => 'Dasmariñas-Marikina Road, Trapiche',
            'city' => 'Dasmariñas',
            'region' => 'Cavite',
            'postal_code' => '4122',
            'phone' => '+63 49 555 1037',
            'email' => 'trapiche@oiland.test',
            'latitude' => 14.2500,
            'longitude' => 120.9500,
            'is_24_hours' => false,
            'description' => 'Station serving the Trapiche residential and agro-industrial area with a workshop and car wash.',
        ],
        [
            'name' => 'Trece',
            'code' => 'OIL-038',
            'address_line1' => 'Trece-Tagaytay Road, Poblacion',
            'city' => 'Trece Martires',
            'region' => 'Cavite',
            'postal_code' => '4122',
            'phone' => '+63 49 555 1038',
            'email' => 'trece@oiland.test',
            'latitude' => 14.0614,
            'longitude' => 120.7681,
            'is_24_hours' => true,
            'description' => 'Flagship 24-hour station on the Trece-Tagaytay road, the main gateway to the Tagaytay ridge.',
        ],
    ];

    public function run(): void
    {
        $this->pruneStaleNetwork();

        $branches = $this->seedBranches();
        $catalogItems = CatalogItem::query()
            ->where('is_active', true)
            ->whereIn('sku', ['FUEL-DSL', 'FUEL-P95', 'FUEL-U91'])
            ->get();

        $this->seedSuperAdmins();
        $this->seedBranchStaff($branches);

        foreach ($branches as $branch) {
            $this->attachCatalog($branch, $catalogItems->all());
        }
    }

    /**
     * Drop branches and staff accounts left behind by an earlier version of the
     * network definition. Every branch foreign key cascades, so dependent
     * prices, histories, sales, reviews, tickets, follows and pivot rows are
     * removed along with the branch.
     */
    private function pruneStaleNetwork(): void
    {
        $staleBranchIds = Branch::query()
            ->whereNotIn('code', array_column(self::BRANCHES, 'code'))
            ->pluck('id');

        if ($staleBranchIds->isNotEmpty()) {
            User::query()->whereIn('branch_id', $staleBranchIds)->update(['branch_id' => null]);
            Branch::query()->whereIn('id', $staleBranchIds)->delete();
        }

        // Staff accounts created by a superseded definition are left without a
        // branch once it is deleted. Remove only the accounts this seeder owns
        // rather than every unassigned staff login in the system.
        User::query()
            ->whereNull('branch_id')
            ->whereIn('role', [UserRole::BranchAdmin->value, UserRole::BranchStaff->value])
            ->where('email', 'like', '%@fuelnetwork.test')
            ->delete();

        // The head office account is keyed by email, so a rebrand under a new
        // domain would otherwise leave the previous super admin signed in.
        User::query()
            ->where('role', UserRole::SuperAdmin->value)
            ->where('email', '!=', self::SUPER_ADMIN_EMAIL)
            ->where('email', 'like', '%@fuelnetwork.test')
            ->delete();
    }

    /**
     * @return list<Branch>
     */
    private function seedBranches(): array
    {
        return array_map(function (array $definition): Branch {
            return Branch::query()->updateOrCreate(
                ['code' => $definition['code']],
                [
                    'name' => $definition['name'],
                    'slug' => Str::slug($definition['name']),
                    'address_line1' => $definition['address_line1'],
                    'address_line2' => null,
                    'city' => $definition['city'],
                    'region' => $definition['region'],
                    'postal_code' => $definition['postal_code'],
                    'country' => 'Philippines',
                    'phone' => $definition['phone'],
                    'email' => $definition['email'],
                    'latitude' => $definition['latitude'],
                    'longitude' => $definition['longitude'],
                    'is_24_hours' => $definition['is_24_hours'],
                    'opening_hours' => BranchFactory::openingHours(),
                    'photos' => [],
                    'description' => sprintf(
                        'Oiland %s serves %s, %s with Diesel, Premium, and Green.',
                        $definition['name'],
                        $definition['city'],
                        $definition['region'],
                    ),
                    'is_active' => true,
                ],
            );
        }, self::BRANCHES);
    }

    private function seedSuperAdmins(): void
    {
        foreach ([
            ['email' => self::SUPER_ADMIN_EMAIL, 'name' => 'Jerome Tabilon'],
            ['email' => 'jan.davide@oiland.test', 'name' => 'Trixie Constantino'],
        ] as $account) {
            User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                'email_verified_at' => now(),
                'password' => self::demoPasswordHash(),
                'role' => UserRole::SuperAdmin->value,
                'branch_id' => null,
                'phone' => '+63 917 555 0000',
                ],
            );
        }
    }

    /**
     * @param  list<Branch>  $branches
     */
    private function seedBranchStaff(array $branches): void
    {
        $oicNames = [
            'OIL-001' => 'Shane Cañeso',
            'OIL-002' => 'Jhucelle Mojica',
        ];

        foreach ($branches as $index => $branch) {
            User::query()->updateOrCreate(
                ['email' => "manager.{$branch->code}@oiland.test"],
                [
                    'name' => $oicNames[$branch->code] ?? 'Officer in Charge '.($index + 1),
                    'email_verified_at' => now(),
                    'password' => self::demoPasswordHash(),
                    'role' => UserRole::BranchAdmin->value,
                    'branch_id' => $branch->id,
                    'phone' => $branch->phone,
                ],
            );

            User::query()->updateOrCreate(
                ['email' => "operator.{$branch->code}@oiland.test"],
                [
                    'name' => 'Forecourt Operator '.($index + 1),
                    'email_verified_at' => now(),
                    'password' => self::demoPasswordHash(),
                    'role' => UserRole::BranchStaff->value,
                    'branch_id' => $branch->id,
                    'phone' => $branch->phone,
                ],
            );
        }
    }

    /**
     * Attach the three confirmed fuel grades as available at every branch.
     *
     * @param  list<CatalogItem>  $catalogItems
     */
    private function attachCatalog(Branch $branch, array $catalogItems): void
    {
        $now = now();
        $rows = [];

        DB::table('branch_catalog_item')->where('branch_id', $branch->id)->update([
            'is_available' => false,
            'updated_at' => $now,
        ]);

        foreach ($catalogItems as $catalogItem) {
            $rows[] = [
                'branch_id' => $branch->id,
                'catalog_item_id' => $catalogItem->id,
                'is_available' => true,
                'notes' => $catalogItem->sku === 'FUEL-U91' ? 'Known at this station as Green.' : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('branch_catalog_item')->upsert(
            $rows,
            ['branch_id', 'catalog_item_id'],
            ['is_available', 'notes', 'updated_at'],
        );
    }
}
