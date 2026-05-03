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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');

            $table->foreignId('salon_id')->nullable()->constrained('users')->nullable()->onDelete('cascade');
            $table->foreignId('barber_id')->nullable()->constrained('users')->nullable()->onDelete('cascade');


            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->integer('total_service_quantity')->default(0);
             $table->decimal('total_price', 10, 2)->default(0);



            $table->enum('booking_type',['salon_auto','salon_barber','home_barber','as_soon_possible','online','custom','cod'])->default('salon_auto');
            $table->enum('payment_type',['online','onsite','cod'])->default('online');


            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
           $table->enum('status', [
                                'pending',
                                'search_barber',
                                'accepted',
                                'confirmed',
                                'on_the_way',
                                'arrived',
                                'completed',
                                'cancelled'         
                            ])->default('pending');


            $table->date('booking_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
