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
        Schema::create('feature_tour', function (Blueprint $table) {
            $table->unsignedBigInteger('tour_id');
            $table->unsignedBigInteger('feature_id');
            $table->foreign('tour_id')->references('id')->on('tours')->onDelete('cascade');
            $table->foreign('feature_id')->references('id')->on('features')->onDelete('cascade');
            $table->primary(['tour_id', 'feature_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feature_tour');
    }
};