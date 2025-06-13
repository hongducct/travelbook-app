<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Chat conversations table
        Schema::create('chat_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Cho phép null
            $table->enum('status', ['active', 'closed', 'archived'])->default('active');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('last_activity')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Chat messages table
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->enum('sender_type', ['user', 'admin', 'ai', 'system']);
            $table->string('sender_id');
            $table->text('message');
            $table->json('metadata')->nullable();
            $table->timestamp('timestamp');
            $table->boolean('is_read')->default(false);
            $table->timestamps();

            $table->index(['conversation_id', 'timestamp']);
            $table->index(['sender_type', 'sender_id']);
        });

        // User search history table
        Schema::create('user_search_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Cho phép null
            $table->text('search_query');
            $table->string('search_type')->nullable();
            $table->json('extracted_entities')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        // Tour recommendations table
        Schema::create('tour_recommendations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable(); // Cho phép null
            $table->foreignId('tour_id')->constrained()->onDelete('cascade');
            $table->decimal('recommendation_score', 8, 4);
            $table->string('recommendation_reason')->nullable();
            $table->json('preference_factors')->nullable();
            $table->timestamp('generated_at');
            $table->boolean('is_clicked')->default(false);
            $table->boolean('is_booked')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'recommendation_score']);
            $table->unique(['user_id', 'tour_id', 'generated_at']);
        });

        // Admin notifications table
        Schema::create('admin_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('chat_conversations')->onDelete('cascade');
            $table->string('user_id')->nullable(); // Cho phép null
            $table->text('message');
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])->default('normal');
            $table->enum('status', ['pending', 'assigned', 'resolved'])->default('pending');
            $table->foreignId('assigned_admin_id')->nullable()->constrained('admins')->onDelete('set null');
            $table->timestamp('notified_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'priority', 'notified_at']);
        });

        // Add last_activity to admins table if it doesn't exist
        if (!Schema::hasColumn('admins', 'last_activity')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->timestamp('last_activity')->nullable();
                $table->boolean('is_active')->default(true);
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('admin_notifications');
        Schema::dropIfExists('tour_recommendations');
        Schema::dropIfExists('user_search_history');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('chat_conversations');

        if (Schema::hasColumn('admins', 'last_activity')) {
            Schema::table('admins', function (Blueprint $table) {
                $table->dropColumn(['last_activity', 'is_active']);
            });
        }
    }
};
