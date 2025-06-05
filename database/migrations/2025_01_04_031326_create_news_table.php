<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('news', function (Blueprint $table) {
            $table->id();

            // Author information
            $table->enum('author_type', ['admin', 'vendor']);
            $table->unsignedBigInteger('admin_id')->nullable();
            $table->unsignedBigInteger('vendor_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();

            // Content fields
            $table->string('title');
            $table->longText('content')->nullable();
            $table->text('excerpt')->nullable(); // Tóm tắt ngắn
            $table->json('tags')->nullable(); // Mảng tags
            $table->string('image')->nullable();

            // Publishing
            $table->timestamp('published_at')->nullable();
            $table->enum('blog_status', ['draft', 'pending', 'rejected', 'published', 'archived'])->default('draft');

            // Features
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedTinyInteger('reading_time')->default(1); // phút
            $table->timestamp('last_viewed_at')->nullable();

            // SEO
            $table->string('meta_description', 160)->nullable();
            $table->string('meta_keywords')->nullable();
            $table->string('slug')->unique()->nullable();

            // Travel specific fields
            $table->string('destination')->nullable(); // Điểm đến
            $table->decimal('latitude', 10, 8)->nullable(); // Tọa độ
            $table->decimal('longitude', 11, 8)->nullable();
            $table->enum('travel_season', ['spring', 'summer', 'autumn', 'winter', 'all_year'])->nullable();
            $table->json('travel_tips')->nullable(); // Mẹo du lịch
            $table->decimal('estimated_budget', 12, 2)->nullable(); // Ngân sách ước tính (VND)
            $table->unsignedTinyInteger('duration_days')->nullable(); // Số ngày du lịch

            $table->timestamps();

            // Foreign keys
            if (Schema::hasTable('admins')) {
                $table->foreign('admin_id')->references('id')->on('admins')->onDelete('set null');
            }
            if (Schema::hasTable('vendors')) {
                $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('set null');
            }

            // Indexes
            $table->index('blog_status');
            $table->index('is_featured');
            $table->index('published_at');
            $table->index('created_at');
            $table->index('view_count');
            $table->index('last_viewed_at');
            $table->index('destination');
            $table->index('travel_season');
            $table->index('slug');

            // Composite indexes
            $table->index(['blog_status', 'is_featured']);
            $table->index(['blog_status', 'published_at']);
            $table->index(['blog_status', 'view_count']);
            $table->index(['destination', 'blog_status']);
            $table->index(['travel_season', 'blog_status']);
        });

        // Add full-text search index (MySQL 5.7+)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE news ADD FULLTEXT(title, content, destination)');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('news');
    }
};
