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
        Schema::create('providerprofiles', function (Blueprint $table) {
             $table->id();

                $table->unsignedBigInteger('user_id')->unique()->comment('Link to users table');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

                $table->enum('user_type', [
                    'salon',
                    'home_barbar',
                    'salon_barbar'
                ]);

                // BUSINESS INFO
                $table->string('business_name')->nullable();
                $table->string('representative_name')->nullable();
                $table->string('since')->nullable();
                $table->longText('about')->nullable();
                $table->text('street_number')->nullable();
                $table->string('vat_number')->nullable();
                $table->integer('experience')->nullable();
                $table->string('postal_code')->nullable();
                $table->longText('salon_address')->nullable();
                $table->boolean('available')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('providerprofiles');
    }
};