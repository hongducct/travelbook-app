<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Voucher;

class VouchersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 10; $i++) {
            Voucher::create([
                'code' => $faker->unique()->regexify('[A-Z0-9]{8}'),
                'discount' => $faker->optional(0.5)->randomFloat(2, 5, 50),
                'discount_percentage' => $faker->optional(0.5)->numberBetween(5, 25),
                'start_date' => $faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
                'end_date' => $faker->dateTimeBetween('+1 month', '+6 months')->format('Y-m-d'),
                'usage_limit' => $faker->optional(0.7)->numberBetween(10, 100),
                'applicable_tour_ids' => json_encode($faker->randomElements(range(1, 10), $faker->numberBetween(1, 5))),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // for ($i = 0; $i < 10; $i++) {
        //     Voucher::create([
        //         'code' => $faker->unique()->regexify('[A-Z0-9]{8}'), // Generate a random 8-character alphanumeric code
        //         'discount' => $faker->optional(0.5)->randomFloat(2, 5, 50), // 50% chance of having a fixed discount
        //         'discount_percentage' => $faker->optional(0.5)->numberBetween(5, 25), // 50% chance of having a percentage discount
        //         'start_date' => $faker->dateTimeBetween('now', '+1 month'),
        //         'end_date' => $faker->dateTimeBetween('+1 month', '+6 months'),
        //         'usage_limit' => $faker->optional(0.7)->numberBetween(10, 100), // 70% chance of having a usage limit
        //         'created_at' => now(),
        //         'updated_at' => now(),
        //     ]);
        // }
        
    }
}