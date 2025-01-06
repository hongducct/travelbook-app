<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Package;

class PackagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $packages = [
            [
                'name' => 'Basic Monthly',
                'price' => 29.99,
                'duration' => 'monthly',
                'max_listings' => 10,
                'featured_listings' => 2,
                'listing_duration' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Premium Yearly',
                'price' => 299.99,
                'duration' => 'yearly',
                'max_listings' => 100,
                'featured_listings' => 20,
                'listing_duration' => 365,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Standard Monthly',
                'price' => 59.99,
                'duration' => 'monthly',
                'max_listings' => 25,
                'featured_listings' => 5,
                'listing_duration' => 30,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Standard Yearly',
                'price' => 599.99,
                'duration' => 'yearly',
                'max_listings' => 250,
                'featured_listings' => 50,
                'listing_duration' => 365,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'Premium Monthly',
                'price' => 99.99,
                'duration' => 'monthly',
                'max_listings' => 50,
                'featured_listings' => 10,
                'listing_duration' => 30,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];
        Package::insert($packages);
    }
}