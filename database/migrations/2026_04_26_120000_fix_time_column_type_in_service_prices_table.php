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
        if (!Schema::hasTable('service_prices') || !Schema::hasColumn('service_prices', 'time')) {
            return;
        }

        if (Schema::getColumnType('service_prices', 'time') !== 'time') {
            Schema::table('service_prices', function (Blueprint $table) {
                $table->time('time')->nullable()->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('service_prices') || !Schema::hasColumn('service_prices', 'time')) {
            return;
        }

        if (Schema::getColumnType('service_prices', 'time') === 'time') {
            Schema::table('service_prices', function (Blueprint $table) {
                $table->integer('time')->nullable()->change();
            });
        }
    }
};
