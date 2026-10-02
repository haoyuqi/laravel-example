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
        Schema::table('visitor_statistics', function (Blueprint $table) {
            $table->dropIndex(['type', 'date']);
            $table->unique(['type', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visitor_statistics', function (Blueprint $table) {
            $table->dropUnique(['type', 'date']);
            $table->index(['type', 'date']);
        });
    }
};
