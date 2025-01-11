<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use App\Models\User;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();

        // Seed Users
        for ($i = 0; $i < 10; $i++) {
            User::create([
                'email' => $faker->unique()->safeEmail,
                'username' => $faker->unique()->userName,
                'password' => Hash::make('password'),
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'phone_number' => $faker->phoneNumber,
                'date_of_birth' => $faker->date,
                'description' => $faker->sentence,
                'avatar' => $faker->imageUrl(640, 480, 'people', true),
                'address' => $faker->address,
                // 'role' => $faker->randomElement(['user', 'vendor']),
                'is_vendor' => $faker->boolean(30), // 30% chance of being a vendor
                'gender' => $faker->randomElement(['male', 'female', 'other']), // Add gender field
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Create Admin User
        User::create([
            'email' => 'admin@example.com',
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'phone_number' => '1234567890',
            'date_of_birth' => '1990-01-01',
            'description' => 'Administrator account',
            'avatar' => null,
            'address' => 'Admin Address',
            // 'role' => 'user', // You might want to create a separate 'admin' role in a real app
            'is_vendor' => false,
            'gender' => $faker->randomElement(['male', 'female', 'other']), // Add gender field for admin user
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}