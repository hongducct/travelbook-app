<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Tour;
use App\Models\Booking;

class BookingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $users = User::all();
        $tours = Tour::all();
        $bookableTypes = ['App\Models\Tour']; // Only using Tour for bookings based on the original migrations

        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) { // Create 5 bookings per user
                $tour = $faker->randomElement($tours);

                $startDate = $faker->dateTimeBetween('now', '+1 month');
                $endDate = $faker->dateTimeBetween($startDate, (clone $startDate)->modify('+' . $tour->duration . ' days'));
                $numberOfGuests = $faker->numberBetween(1, 5);
                $totalPrice = $tour->price * $numberOfGuests;

                Booking::create([
                    'user_id' => $user->id,
                    'bookable_id' => $tour->id,
                    'bookable_type' => 'App\Models\Tour',
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'number_of_guests' => $numberOfGuests,
                    'total_price' => $totalPrice,
                    'status' => $faker->randomElement(['pending', 'confirmed', 'cancelled']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}