<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Tour;
use App\Models\Amenity;
use Illuminate\Support\Facades\DB;

class AmenityTourTableSeeder extends Seeder
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
        $amenities = Amenity::all();

        foreach ($tours as $tour) {
            // Get a random number of amenities to attach (between 1 and 5)
            $numAmenities = $faker->numberBetween(1, 5);

            // Get a random subset of amenities
            $tourAmenities = $amenities->random($numAmenities);

            // Attach the amenities to the tour
            foreach ($tourAmenities as $amenity) {
                DB::table('amenity_tour')->insert([
                    'amenity_id' => $amenity->id,
                    'tour_id' => $tour->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}