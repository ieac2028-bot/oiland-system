<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->string('type', 20)->default('notice')->after('body')->index();
        });

        DB::table('announcements')->where('slug', 'employee-events-and-branch-notices')->update(['type' => 'event']);
        DB::table('announcements')->where('slug', 'station-staff-hiring-notices')->update([
            'type' => 'job',
            'is_published' => false,
        ]);
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table): void {
            $table->dropIndex(['type']);
            $table->dropColumn('type');
        });
    }
};
