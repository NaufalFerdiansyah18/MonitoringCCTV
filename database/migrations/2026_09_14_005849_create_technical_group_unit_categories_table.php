<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('technical_group_unit_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technical_group_id')->constrained('technical_groups')->cascadeOnDelete();
            $table->enum('kategori', ['kebun', 'pks', 'ro']);
            $table->timestamps();

            $table->unique(['technical_group_id', 'kategori']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_group_unit_categories');
    }
};
