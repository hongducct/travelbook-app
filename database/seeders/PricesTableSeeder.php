<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Tour;
use App\Models\Price;
use Carbon\Carbon;

class PricesTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $tours = Tour::all();

        foreach ($tours as $tour) {
            // Tạo giá cho 6 tháng tới
            $startDate = Carbon::now();
            $endDate = Carbon::now()->addMonths(6);

            while ($startDate <= $endDate) {
                Price::create([
                    'tour_id' => $tour->id,
                    'date' => $startDate->format('Y-m-d'),
                    'price' => $faker->randomFloat(2, 100, 1000), // Giá ngẫu nhiên từ 100 đến 1000
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                // Tăng ngày lên 5-10 ngày
                $startDate->addDays($faker->numberBetween(5, 10));
            }
        }
    }
}