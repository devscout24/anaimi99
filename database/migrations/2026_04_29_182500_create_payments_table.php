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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('barber_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignId('salon_id')->nullable()->constrained('users')->onDelete('cascade');

            $table->decimal('amount', 10, 2)->default(0);
            $table->decimal('admin_commission', 10, 2)->default(0);
            $table->decimal('provider_earnings', 10, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0);

            $table->enum('payment_type', ['online', 'onsite', 'cod', 'custom'])->default('cod');
            $table->enum('booking_type', ['salon_auto', 'salon_barber', 'home_barber', 'as_soon_possible', 'custom'])->default('salon_auto');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');

            $table->string('transaction_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
