<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Payment;

class PaymentSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        $users = User::all();

        // Tạo 10 bản ghi payment
        foreach (range(1, 10) as $index) {
            Payment::create([
                'user_id' => $faker->randomElement($users)->id,
                'amount' => $faker->randomFloat(2, 500000, 5000000), // Giá từ 500k đến 5M
                'method' => $faker->randomElement(['vnpay', 'cash']),
                'status' => $faker->randomElement(['pending', 'completed', 'failed']),
                'transaction_id' => 'TXN_' . $faker->uuid,
            ]);
        }
    }
}