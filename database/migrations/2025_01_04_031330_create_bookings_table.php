<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('bookable_id')->constrained('tours')->onDelete('cascade');
            $table->string('bookable_type')->default('App\\Models\\Tour');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->unsignedInteger('number_of_guests_adults')->default(1);
            $table->unsignedInteger('number_of_children')->nullable()->default(0);
            $table->decimal('total_price', 12, 2);
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('pending');
            $table->foreignId('voucher_id')->nullable()->constrained('vouchers')->onDelete('set null');
            $table->text('special_requests')->nullable();
            $table->string('contact_phone')->nullable();
            $table->foreignId('payment_id')->nullable()->constrained('payments')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};