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
            NewsTableSeeder::class,
            BookingsTableSeeder::class,
            FavoritesTableSeeder::class,
            ReviewsTableSeeder::class,
            ItinerariesTableSeeder::class,
            PricesTableSeeder::class,
            VouchersTableSeeder::class,
            ItineraryImagesTableSeeder::class,
            TourImagesTableSeeder::class,
            TravelTypesTableSeeder::class, // Add TravelTypes seeder
            AmenitiesTableSeeder::class, // Add Amenities seeder
            AmenityTourTableSeeder::class,
        ]);
    }
}