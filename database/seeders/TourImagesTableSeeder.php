<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Tour;
use App\Models\TourImage;

class TourImagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $tours = Tour::all();

        foreach ($tours as $tour) {
            for ($i = 0; $i < $faker->numberBetween(1, 5); $i++) { // 1-5 images per tour
                TourImage::create([
                    'tour_id' => $tour->id,
                    'image_path' => $faker->imageUrl(640, 480, 'nature', true),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}