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
        Schema::create('review_ratings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barbar_id');
            $table->unsignedBigInteger('salon_id')->nullable();
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('booking_id');
            $table->text('review');
            $table->integer('rating');

           $table->foreign('barbar_id')->references('id')->on('users')->onDelete('cascade');
           $table->foreign('salon_id')->references('id')->on('users')->onDelete('cascade');
           $table->foreign('customer_id')->references('id')->on('users')->onDelete('cascade');
           $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('cascade');



            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_ratings');
    }
};
