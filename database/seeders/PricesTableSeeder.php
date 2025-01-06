<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Tour;
use App\Models\Price;

class PricesTableSeeder extends Seeder
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
            for ($i = 0; $i < 7; $i++) { // Create prices for 7 days
                Price::create([
                    'tour_id' => $tour->id,
                    'date' => $faker->dateTimeBetween('now', '+6 months')->format('Y-m-d'),
                    'price' => $faker->randomFloat(2, $tour->price * 0.8, $tour->price * 1.2), // Price can vary +/- 20%
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}