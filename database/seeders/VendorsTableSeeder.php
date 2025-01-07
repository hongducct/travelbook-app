<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Package;

class VendorsTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Đảm bảo có ít nhất một Package tồn tại trước khi tạo Vendor
        if (Package::count() == 0) {
            Package::create([
                'name' => 'Basic Package',
                'description' => 'Gói cơ bản',
                'price' => 0,
                'duration' => 30,
            ]);
        }
        $packages = Package::pluck('id')->toArray();

        // Tạo vendor cho những user có is_vendor = true
        $vendorUsers = User::where('role', 'vendor')->get();

        foreach ($vendorUsers as $user) {
            Vendor::create([
                'user_id' => $user->id,
                'company_name' => $faker->company,
                'business_license' => $faker->uuid,
                'package_id' => $faker->randomElement($packages),
                'package_expiry_date' => $faker->dateTimeBetween('now', '+1 year'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}