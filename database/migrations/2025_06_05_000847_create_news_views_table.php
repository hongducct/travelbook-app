<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news_views', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('news_id'); // Bài viết được xem
            $table->unsignedBigInteger('user_id')->nullable(); // User đã đăng nhập
            $table->unsignedBigInteger('admin_id')->nullable(); // Admin đã đăng nhập
            $table->string('ip_address', 45); // IP address (hỗ trợ IPv6)
            $table->text('user_agent')->nullable(); // Browser info
            $table->string('referer', 500)->nullable(); // Trang giới thiệu
            $table->string('country', 2)->nullable(); // Mã quốc gia
            $table->string('city', 100)->nullable(); // Thành phố
            $table->string('device_type', 20)->nullable(); // mobile/desktop/tablet
            $table->string('browser', 50)->nullable(); // Chrome/Firefox/Safari
            $table->timestamp('viewed_at'); // Thời gian xem
            $table->timestamps();

            // Foreign keys
            $table->foreign('news_id')->references('id')->on('news')->onDelete('cascade');

            // Kiểm tra bảng tồn tại trước khi tạo foreign key
            if (Schema::hasTable('users')) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            }

            if (Schema::hasTable('admins')) {
                $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            }

            // Indexes để tối ưu query
            $table->index(['news_id', 'viewed_at']);
            $table->index(['user_id', 'viewed_at']);
            $table->index(['admin_id', 'viewed_at']);
            $table->index(['ip_address', 'viewed_at']);
            $table->index('viewed_at');
            $table->index('device_type');
            $table->index('country');

            // Composite indexes cho analytics
            $table->index(['news_id', 'ip_address', 'viewed_at']);
            $table->index(['news_id', 'user_id', 'viewed_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news_views');
    }
};
