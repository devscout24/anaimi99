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
        // Fix bookings table
        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'last_assigned_at')) {
                $table->timestamp('last_assigned_at')->nullable()->after('status');
            }
            if (!Schema::hasColumn('bookings', 'address')) {
                $table->text('address')->nullable();
            }
            if (!Schema::hasColumn('bookings', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable();
            }
            if (!Schema::hasColumn('bookings', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable();
            }
        });

        // Fix booking_assign_histories table
        Schema::table('booking_assign_histories', function (Blueprint $table) {
            if (!Schema::hasColumn('booking_assign_histories', 'booking_id')) {
                $table->foreignId('booking_id')->after('id')->nullable()->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('booking_assign_histories', 'salon_id')) {
                $table->foreignId('salon_id')->after('booking_id')->nullable()->constrained('users')->onDelete('cascade');
            }
            if (!Schema::hasColumn('booking_assign_histories', 'barber_id')) {
                $table->foreignId('barber_id')->after('salon_id')->nullable()->constrained('users')->onDelete('cascade');
            }
            if (!Schema::hasColumn('booking_assign_histories', 'booking_type')) {
                $table->enum('booking_type', ['salon_auto', 'salon_barber', 'home_barber', 'as_soon_possible', 'online', 'custom', 'cod', 'loyalty'])->default('salon_auto')->after('barber_id');
            }
            if (!Schema::hasColumn('booking_assign_histories', 'payment_type')) {
                $table->enum('payment_type', ['online', 'onsite', 'cod', 'loyalty'])->default('online')->after('booking_type');
            }
            if (!Schema::hasColumn('booking_assign_histories', 'status')) {
                $table->enum('status', ['pending', 'accepted', 'rejected', 'timeout'])->default('pending')->after('payment_type');
            }
            if (!Schema::hasColumn('booking_assign_histories', 'assigned_at')) {
                $table->timestamp('assigned_at')->useCurrent()->after('status');
            }
            if (!Schema::hasColumn('booking_assign_histories', 'unassigned_at')) {
                $table->timestamp('unassigned_at')->nullable()->after('assigned_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['last_assigned_at', 'address', 'latitude', 'longitude']);
        });

        Schema::table('booking_assign_histories', function (Blueprint $table) {
            // Drop foreign keys first if they were added
            $table->dropForeign(['booking_id']);
            $table->dropForeign(['salon_id']);
            $table->dropForeign(['barber_id']);
            $table->dropColumn(['booking_id', 'salon_id', 'barber_id', 'booking_type', 'payment_type', 'status', 'assigned_at', 'unassigned_at']);
        });
    }
};
