<?php

namespace Database\Seeders;

use App\Models\TourAvailability;
use App\Models\Tour;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TourAvailabilitySeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Lấy tất cả các tour_id đã có
        $tourIds = Tour::pluck('id')->toArray();

        if (empty($tourIds)) {
            return; // Không có tour nào để tạo availability
        }

        for ($i = 0; $i < 50; $i++) {
            TourAvailability::create([
                'tour_id' => $faker->randomElement($tourIds),
                'date' => $faker->dateTimeBetween('2025-06-01', '2025-12-31')->format('Y-m-d'),
                'max_guests' => $faker->numberBetween(50, 80),
                'available_slots' => $faker->numberBetween(5, 50),
                'is_active' => $faker->boolean(90),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
