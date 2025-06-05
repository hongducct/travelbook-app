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
        Schema::create('news_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên category
            $table->string('slug')->unique(); // URL slug
            $table->text('description')->nullable(); // Mô tả
            $table->string('color', 7)->default('#3B82F6'); // Màu hex
            $table->string('icon', 50)->nullable(); // Tên icon Heroicons
            $table->boolean('is_active')->default(true); // Trạng thái
            $table->unsignedInteger('sort_order')->default(0); // Thứ tự sắp xếp
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('is_active');
            $table->index('sort_order');
        });

        // Thêm foreign key constraint cho news table
        Schema::table('news', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('news_categories')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Drop foreign key trước
        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
        });
        
        // Drop table
        Schema::dropIfExists('news_categories');
    }
};
