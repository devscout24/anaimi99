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
        if (!Schema::hasColumn('service_prices', 'time')) {
            Schema::table('service_prices', function (Blueprint $table) {
                $table->time('time')->nullable()->after('discount');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('service_prices', 'time')) {
            Schema::table('service_prices', function (Blueprint $table) {
                $table->dropColumn('time');
            });
        }
    }
};