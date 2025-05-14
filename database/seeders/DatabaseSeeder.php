<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            UsersTableSeeder::class,
            PackagesTableSeeder::class,
            VendorsTableSeeder::class,
            LocationsTableSeeder::class,
            ToursTableSeeder::class,
            PricesTableSeeder::class,
            NewsTableSeeder::class,
            VouchersTableSeeder::class,
            FavoritesTableSeeder::class,
            ItinerariesTableSeeder::class,
            ItineraryImagesTableSeeder::class,
            TourImagesTableSeeder::class,
            TourAvailabilitySeeder::class, // Add TourAvailability seeder
            TravelTypesTableSeeder::class, // Add TravelTypes seeder
            AmenitiesTableSeeder::class, // Add Amenities seeder
            AmenityTourTableSeeder::class,
            AdminsTableSeeder::class, // Add Admins seeder
            PaymentSeeder::class, // Add Payment seeder
            BookingsTableSeeder::class,
            VoucherUsageSeeder::class, // Add VoucherUsage seeder
            ReviewsTableSeeder::class,
        ]);
    }
}