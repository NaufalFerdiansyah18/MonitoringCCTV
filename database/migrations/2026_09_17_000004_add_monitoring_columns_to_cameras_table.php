<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->boolean('is_online')->default(false);
            $table->timestamp('last_checked_at')->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('cameras', function (Blueprint $table) {
            $table->dropColumn(['is_online', 'last_checked_at', 'latency_ms']);
        });
    }
};
