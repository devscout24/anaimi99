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
            $table->enum('status', [
                'pending',
                'search_barber',
                'waiting_for_payment',
                'accepted',
                'confirmed',
                'on_the_way',
                'arrived',
                'completed',
                'cancelled'
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'search_barber',
                'accepted',
                'confirmed',
                'on_the_way',
                'arrived',
                'completed',
                'cancelled'
            ])->default('pending')->change();
        });
    }
};
