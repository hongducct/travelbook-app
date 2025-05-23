<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();      
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable(); // Cho phép password là null cho tài khoản Google
            $table->string('google_id')->nullable();
            $table->rememberToken();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('phone_number')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->text('description')->nullable();
            $table->string('avatar')->nullable();
            $table->text('address')->nullable();
            // $table->enum('role', ['user', 'vendor'])->default('user');
            $table->enum('gender', ['male', 'female', 'other'])->nullable();
            $table->boolean('is_vendor')->default(false);
            $table->enum('user_status', ['active', 'inactive', 'banned'])->default('active'); // <-- thêm trường này
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('users');
    }
};
