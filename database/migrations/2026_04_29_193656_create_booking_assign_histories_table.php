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
        Schema::create('booking_assign_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->onDelete('cascade');
            $table->foreignId('salon_id')->nullable()->constrained('users')->nullable()->onDelete('cascade');
            $table->foreignId('barber_id')->nullable()->constrained('users')->nullable()->onDelete('cascade');
                $table->enum('booking_type',['salon_auto','salon_barber','home_barber','as_soon_possible','online','custom','cod','loyalty'])->default('salon_auto');
                $table->enum('payment_type',['online','onsite','cod','loyalty'])->default('online');

                $table->timestamp('assigned_at')->useCurrent();
                $table->timestamp('unassigned_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_assign_histories');
    }
};
