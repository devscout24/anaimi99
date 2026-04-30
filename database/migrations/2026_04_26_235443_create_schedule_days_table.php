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
        Schema::create('schedule_days', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('provider_id');
            $table->decimal('buffer_time', 8, 2)->default(0);
            $table->decimal('break_time', 8, 2)->default(0);
            $table->decimal('schedule_duration', 8, 2)->default(0);
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('provider_type', ['home_barber', 'salon'])->default('home_barber ');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->foreign('provider_id')->references('id')->on('users')->onDelete('cascade');
          
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_days');
    }
};
