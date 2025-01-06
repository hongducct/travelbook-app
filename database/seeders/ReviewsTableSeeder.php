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

        foreach ($users as $user) {
            $reviewedTours = $faker->randomElements($tours, $faker->numberBetween(1, 5)); // Each user reviews 1-5 random tours

            foreach ($reviewedTours as $tour) {
                Review::create([
                    'user_id' => $user->id,
                    'reviewable_id' => $tour->id,
                    'reviewable_type' => 'App\Models\Tour',
                    'rating' => $faker->numberBetween(1, 5),
                    'comment' => $faker->optional(0.7)->paragraph, // 70% chance of having a comment
                    'tour_id' => $tour->id, // Redundant with reviewable_id and reviewable_type
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}