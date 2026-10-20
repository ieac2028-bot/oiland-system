<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branch_catalog_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('catalog_item_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_available')->default(true);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'catalog_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_catalog_item');
    }
};
