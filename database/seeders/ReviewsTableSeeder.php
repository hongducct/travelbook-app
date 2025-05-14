<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Tour;
use App\Models\Review;

class ReviewsTableSeeder extends Seeder
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
        $statuses = ['approved', 'pending', 'rejected'];

        foreach ($users as $user) {
            $reviewedTours = $faker->randomElements($tours->toArray(), $faker->numberBetween(1, min(5, $tours->count())));

            foreach ($reviewedTours as $tour) {
                Review::create([
                    'user_id'       => $user->id,
                    'booking_id'    => $faker->optional(0.8)->numberBetween(1, 5), // Giữ nguyên
                    'reviewable_id'   => $tour['id'],
                    'reviewable_type' => 'App\Models\Tour',
                    'title'         => $faker->sentence(3), // Giữ nguyên
                    'rating'        => $faker->numberBetween(1, 5),
                    'comment'       => $faker->optional(0.7)->paragraph,
                    'status'        => $faker->randomElement($statuses),
                    'replied_at'    => $faker->optional(0.3)->dateTimeThisYear(), // Giữ nguyên
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);
            }
        }
    }
}
