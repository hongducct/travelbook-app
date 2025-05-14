<?php

namespace Database\Seeders;

use App\Models\TourAvailability;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TourAvailabilitySeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 20; $i++) { // Tạo 20 bản ghi khả dụng
            TourAvailability::create([
                'tour_id' => $faker->numberBetween(1, 10),
                'date' => $faker->dateTimeBetween('2025-06-01', '2025-12-31')->format('Y-m-d'),
                'max_guests' => $faker->numberBetween(10, 50),
                'available_slots' => $faker->numberBetween(5, 50),
                'is_active' => $faker->boolean(90), // 90% cơ hội là active
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}