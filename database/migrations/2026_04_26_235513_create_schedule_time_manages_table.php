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
        Schema::create('schedule_time_manages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->unsignedBigInteger('schedule_day_id');
            $table->time('scheduled_start_time');
            $table->time('scheduled_end_time');

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('schedule_day_id')->references('id')->on('schedule_days')->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_time_manages');
    }
};
