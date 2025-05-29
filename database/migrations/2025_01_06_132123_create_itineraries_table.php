<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('itineraries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tour_id');
            $table->integer('day'); // Ngày thứ mấy (1, 2, 3...)
            $table->string('title'); // Tiêu đề ngày (VD: "HÀ NỘI – VÂN ĐỒN – QUAN LẠN")
            $table->text('description')->nullable(); // Mô tả chi tiết
            $table->json('activities')->nullable(); // Các hoạt động trong ngày (JSON format)
            $table->string('accommodation')->nullable(); // Nơi nghỉ đêm
            $table->string('meals')->nullable(); // Bữa ăn (VD: "Ăn sáng, trưa, tối")
            $table->time('start_time')->nullable(); // Thời gian bắt đầu
            $table->time('end_time')->nullable(); // Thời gian kết thúc
            $table->text('notes')->nullable(); // Ghi chú thêm
            $table->foreign('tour_id')->references('id')->on('tours')->onDelete('cascade');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('itineraries');
    }
};
