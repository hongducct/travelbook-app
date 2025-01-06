<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Location;

class LocationsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        $locations = [
            [
                'name' => 'Eiffel Tower',
                'description' => 'Iconic wrought-iron lattice tower located in Paris.',
                'country' => 'France',
                'city' => 'Paris',
                'image' => 'eiffel_tower.jpg', // Example image name
                'latitude' => 48.8584,
                'longitude' => 2.2945,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Machu Picchu',
                'description' => '15th-century Inca citadel in the Andes Mountains.',
                'country' => 'Peru',
                'city' => 'Cusco',
                'image' => 'machu_picchu.jpg', // Example image name
                'latitude' => -13.1631,
                'longitude' => -72.5450,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Great Barrier Reef',
                'description' => 'The world\'s largest coral reef system.',
                'country' => 'Australia',
                'city' => 'Queensland',
                'image' => null,
                'latitude' => -18.2871,
                'longitude' => 147.6992,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Grand Canyon',
                'description' => 'A steep-sided canyon carved by the Colorado River.',
                'country' => 'USA',
                'city' => 'Arizona',
                'image' => null,
                'latitude' => 36.1069,
                'longitude' => -112.1129,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Santorini',
                'description' => 'A beautiful island in the Aegean Sea known for its stunning sunsets and white-washed buildings.',
                'country' => 'Greece',
                'city' => 'Santorini',
                'image' => null,
                'latitude' => 36.3932,
                'longitude' => 25.4615,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];
        Location::insert($locations);
    }
}