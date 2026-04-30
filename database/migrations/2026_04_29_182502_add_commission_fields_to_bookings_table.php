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
        Schema::table('bookings', function (Blueprint $table) {
            $table->decimal('commission_rate', 5, 2)->default(0)->after('total_price');
            $table->decimal('admin_commission', 10, 2)->default(0)->after('commission_rate');
            $table->decimal('provider_earnings', 10, 2)->default(0)->after('admin_commission');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['commission_rate', 'admin_commission', 'provider_earnings']);
        });
    }
};
