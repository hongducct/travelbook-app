<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Package;

class VendorsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $vendorUsers = User::where('is_vendor', true)->get();

        foreach ($vendorUsers as $user) {
            Vendor::create([
                'user_id' => $user->id,
                'company_name' => $faker->company,
                'business_license' => $faker->uuid,
                'package_id' => $faker->randomElement(Package::pluck('id')->toArray()),
                'package_expiry_date' => $faker->dateTimeBetween('now', '+1 year'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}