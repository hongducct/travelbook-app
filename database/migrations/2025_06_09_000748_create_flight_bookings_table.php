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
        Schema::create('flight_bookings', function (Blueprint $table) {
            $table->id();
            $table->string('booking_reference')->unique();
            $table->json('flight_data'); // Store flight information
            $table->json('passenger_data'); // Store passenger information
            $table->json('contact_data'); // Store contact information
            $table->json('search_params'); // Store original search parameters
            $table->json('preferences')->nullable(); // Store preferences like seat, special requests
            $table->string('payment_method'); // credit_card, vnpay, momo, bank_transfer
            $table->decimal('total_amount', 12, 2);
            $table->string('currency', 3)->default('VND');
            $table->enum('status', ['pending', 'confirmed', 'cancelled', 'completed'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            $table->string('payment_transaction_id')->nullable();
            $table->text('payment_details')->nullable(); // Store payment gateway response
            $table->text('notes')->nullable();
            $table->timestamp('booking_date')->useCurrent();
            $table->timestamps();

            $table->index(['booking_reference']);
            $table->index(['status']);
            $table->index(['payment_status']);
            $table->index(['booking_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flight_bookings');
    }
};
