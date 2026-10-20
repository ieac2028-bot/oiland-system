<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The network moved from the UAE to the Philippines, so amounts stored against
 * the old AED default are relabelled and the column defaults change to PHP.
 * Existing rows are relabelled rather than converted, because the seeded
 * figures are representative demo data rather than a converted ledger.
 */
return new class extends Migration
{
    /**
     * Tables that carry a three letter currency code.
     *
     * @var list<string>
     */
    private const TABLES = ['prices', 'price_histories', 'sales'];

    public function up(): void
    {
        $this->switchCurrency('AED', 'PHP');
    }

    public function down(): void
    {
        $this->switchCurrency('PHP', 'AED');
    }

    /**
     * Relabel rows from one currency code to another and move the column
     * default with it. Uses the schema builder so the same migration applies to
     * MySQL locally and to the SQLite connection used by the test suite.
     */
    private function switchCurrency(string $from, string $to): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'currency')) {
                continue;
            }

            DB::table($table)->where('currency', $from)->update(['currency' => $to]);

            Schema::table($table, function (Blueprint $blueprint) use ($to): void {
                $blueprint->string('currency', 3)->default($to)->change();
            });
        }
    }
};
