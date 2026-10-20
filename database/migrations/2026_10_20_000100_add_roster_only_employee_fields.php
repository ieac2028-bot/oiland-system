<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->string('name')->nullable()->after('user_id');
            $table->foreignId('branch_id')->nullable()->after('name')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->nullable()->change();
            $table->date('date_hired')->nullable()->change();
        });

        DB::table('employees')
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->update([
                'employees.name' => DB::raw('users.name'),
                'employees.branch_id' => DB::raw('users.branch_id'),
            ]);
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table): void {
            $table->dropForeign(['branch_id']);
            $table->dropColumn(['name', 'branch_id']);
            $table->foreignId('user_id')->nullable(false)->change();
            $table->date('date_hired')->nullable(false)->change();
        });
    }
};
