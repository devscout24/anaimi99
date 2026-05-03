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
        Schema::create('loyality_adds', function (Blueprint $table) {
            $table->id();
            // $table->unsignedBigInteger('customer_id');
            // $table->unsignedBigInteger('salon_id')->nullable();
            // $table->unsignedBigInteger('barber_id');
            // $table->unsignedBigInteger('booking_id');
            $table->decimal('per_booking_loyality_point',10,2)->default(0.00);
            $table->decimal('remaining_loyality_point',10,2)->default(0.00);
            $table->decimal('costing_loyality_point',10,2)->default(0.00);

            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('salon_id')->nullable()->constrained('users')->onDelete('cascade')->nullable();
            $table->foreignId('barber_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');

            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loyality_adds');
    }
};
