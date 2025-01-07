<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Tour;
use App\Models\Booking;
use App\Models\Price;
use Carbon\Carbon;

class BookingsTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();
        $users = User::all();
        $tours = Tour::all();

         if ($users->isEmpty() || $tours->isEmpty()) {
            $this->command->info('Không có User hoặc Tour nào để tạo Booking. Hãy seed User và Tour trước.');
            return;
        }

        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                $tour = $faker->randomElement($tours);
                $startDate = $faker->dateTimeBetween('now', '+1 month');

                $days = $tour->days;
                if ($days === 0) {
                    $endDate = clone $startDate;
                } else {
                    $endDate = (clone $startDate)->modify('+' . $days . ' days');
                }

                $adults = $faker->numberBetween(1, 4);
                $children = $faker->numberBetween(0, 3);

                $price = Price::where('tour_id', $tour->id)
                    ->where('date', $startDate->format('Y-m-d'))
                    ->value('price');

                if (!$price) {
                    $this->command->info("Không tìm thấy giá cho tour {$tour->id} vào ngày {$startDate->format('Y-m-d')}.");
                    continue;
                }

                $totalPrice = $price * ($adults + $children * 0.5); // Ví dụ: trẻ em tính 50% giá

                Booking::create([
                    'user_id' => $user->id,
                    'bookable_id' => $tour->id,
                    'bookable_type' => 'App\Models\Tour',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'number_of_guests_adults' => $adults, // Sử dụng trường mới
                    'number_of_children' => $children, // Sử dụng trường mới
                    'total_price' => $totalPrice,
                    'status' => $faker->randomElement(['pending', 'confirmed', 'cancelled']),
                ]);
            }
        }
    }
}