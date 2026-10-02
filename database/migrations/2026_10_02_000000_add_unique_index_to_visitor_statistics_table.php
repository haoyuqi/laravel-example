<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        });

        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('CREATE UNIQUE INDEX visitor_statistics_type_date_unique ON visitor_statistics (type, date) WHERE deleted_at IS NULL');
        } else {
            Schema::table('visitor_statistics', function (Blueprint $table) {
                $table->unique(['type', 'date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['pgsql', 'sqlite'], true)) {
            DB::statement('DROP INDEX IF EXISTS visitor_statistics_type_date_unique');
        } else {
            Schema::table('visitor_statistics', function (Blueprint $table) {
                $table->dropUnique(['type', 'date']);
            });
        }

        Schema::table('visitor_statistics', function (Blueprint $table) {
            $table->index(['type', 'date']);
        });
    }
};
