<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Vendor;
use App\Models\Location;
use App\Models\Tour;

class ToursTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $vendors = Vendor::all();
        $locations = Location::all();

        foreach ($vendors as $vendor) {
            for ($i = 0; $i < 5; $i++) { // Creating 5 tours per vendor
                $location = $faker->randomElement($locations);
                Tour::create([
                    'vendor_id' => $vendor->id,
                    'location_id' => $location->id,
                    'name' => $faker->sentence(4) . " Tour",
                    'description' => $faker->paragraph,
                    'duration' => $faker->numberBetween(1, 7), // Duration in days
                    'price' => $faker->randomFloat(2, 50, 500), // Price between 50 and 500
                    'image' => $faker->imageUrl(640, 480, 'city', true),
                    'features' => json_encode($faker->randomElements(['Guided Tours', 'Meals Included', 'Accommodation', 'Transportation', 'Entrance Fees'], $faker->numberBetween(1, 5))),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}