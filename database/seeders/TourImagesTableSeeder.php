<?php

namespace Database\Seeders;

use App\Models\TourImage;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TourImagesTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 20; $i++) { // Tạo 20 hình ảnh, mỗi tour có thể có 1-2 hình
            TourImage::create([
                'tour_id' => $faker->numberBetween(1, 10),
                'image_url' => $faker->imageUrl(640, 480, 'travel'),
                'caption' => $faker->optional(0.8)->sentence(),
                'is_primary' => $faker->boolean(20), // 20% cơ hội là ảnh chính
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}