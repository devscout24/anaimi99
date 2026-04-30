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
        Schema::create('slot_block_barbers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('salon_id')->nullable();
            $table->unsignedBigInteger('barber_id')->nullable();
            $table->timestamp('block_date');
            $table->unsignedBigInteger('slot_id');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreign('salon_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('barber_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('slot_id')->references('id')->on('schedule_time_manages')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('slot_block_barbers');
    }
};
