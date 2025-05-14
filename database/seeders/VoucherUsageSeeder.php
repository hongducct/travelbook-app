<?php

namespace Database\Seeders;

use App\Models\VoucherUsage;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class VoucherUsageSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 5; $i++) { // Tạo 5 bản ghi để tránh lặp quá nhiều
            VoucherUsage::create([
                'voucher_id' => $faker->numberBetween(1, 10),
                'booking_id' => $faker->numberBetween(1, 5),
                'user_id' => $faker->numberBetween(1, 10),
                'discount_applied' => $faker->randomFloat(2, 5, 100),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}