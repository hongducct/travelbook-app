<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('booking_id')->nullable();
            $table->morphs('reviewable'); // reviewable_id, reviewable_type
            $table->string('title')->nullable(); // Tiêu đề đánh giá
            $table->unsignedInteger('rating')->between(1, 5);
            $table->text('comment')->nullable();
            $table->enum('status', ['approved', 'pending', 'rejected'])->default('pending');
            $table->timestamp('replied_at')->nullable(); // Thời điểm nhà cung cấp phản hồi
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('booking_id')->references('id')->on('bookings')->onDelete('set null');
            $table->index(['reviewable_id', 'reviewable_type']); // Chỉ mục cho hiệu suất
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};