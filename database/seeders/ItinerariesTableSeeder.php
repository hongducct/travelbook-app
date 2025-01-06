<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Tour;
use App\Models\Itinerary;

class ItinerariesTableSeeder extends Seeder
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
            for ($day = 1; $day <= $tour->duration; $day++) {
                Itinerary::create([
                    'tour_id' => $tour->id,
                    'day' => $day,
                    'title' => "Day $day: " . $faker->sentence,
                    'description' => $faker->paragraph,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}