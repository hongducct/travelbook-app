<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Itinerary;
use App\Models\ItineraryImage;

class ItineraryImagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $itineraries = Itinerary::all();

        foreach ($itineraries as $itinerary) {
            for ($i = 0; $i < $faker->numberBetween(1, 3); $i++) { // 1-3 images per itinerary
                ItineraryImage::create([
                    'itinerary_id' => $itinerary->id,
                    'image_path' => $faker->imageUrl(640, 480, 'city', true), // Using city images as an example
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}