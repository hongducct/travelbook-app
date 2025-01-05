<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Package;
use App\Models\Location;
use App\Models\Accommodation;
use App\Models\Tour;
use App\Models\Homestay;
use App\Models\Vehicle;
use App\Models\Boat;
use App\Models\Event;
use App\Models\News;
use App\Models\Booking;
use App\Models\Favorite;
use App\Models\Review;

class DatabaseSeeder extends Seeder
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
        $users = [];
        for ($i = 0; $i < 10; $i++) {
            $users[] = [
                'email' => $faker->unique()->safeEmail, // Add email here
                'username' => $faker->unique()->userName,
                'password' => Hash::make('password'),
                'first_name' => $faker->firstName,
                'last_name' => $faker->lastName,
                'phone_number' => $faker->phoneNumber,
                'date_of_birth' => $faker->date,
                'description' => $faker->sentence,
                'avatar' => $faker->imageUrl,
                'address' => $faker->address,
                'role' => $faker->randomElement(['user', 'vendor']),
                'is_vendor' => $faker->boolean,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        User::insert($users);
    
        // Create Admin User
        User::create([
            'email' => 'admin@example.com', // Add email here
            'username' => 'admin',
            'password' => Hash::make('admin'),
            'first_name' => 'Admin',
            'last_name' => 'User',
            'phone_number' => '1234567890',
            'date_of_birth' => '1990-01-01',
            'description' => 'Administrator account',
            'avatar' => null,
            'address' => 'Admin Address',
            'role' => 'user',
            'is_vendor' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Seed Packages
        $packages = [
            [
                'name' => 'Basic',
                'price' => 9.99,
                'duration' => 'monthly',
                'max_listings' => 5,
                'featured_listings' => 0,
                'listing_duration' => 30,
            ],
            [
                'name' => 'Standard',
                'price' => 29.99,
                'duration' => 'monthly',
                'max_listings' => 20,
                'featured_listings' => 5,
                'listing_duration' => 60,
            ],
            [
                'name' => 'Premium',
                'price' => 99.99,
                'duration' => 'yearly',
                'max_listings' => 100,
                'featured_listings' => 20,
                'listing_duration' => 90,
            ],
        ];
        Package::insert($packages);

        // Seed Vendors
        $vendorUsers = User::where('is_vendor', true)->get();
        foreach ($vendorUsers as $user) {
            Vendor::create([
                'user_id' => $user->id,
                'company_name' => $faker->company,
                'business_license' => $faker->uuid,
                'package_id' => $faker->randomElement(Package::pluck('id')->toArray()),
                'package_expiry_date' => $faker->dateTimeBetween('now', '+1 year'),
            ]);
        }
        
        // Seed Locations
        $locations = [];
        for ($i = 0; $i < 5; $i++) {
            $locations[] = [
                'name' => $faker->city,
                'description' => $faker->paragraph,
                'country' => $faker->country,
                'city' => $faker->city,
                'image' => $faker->imageUrl(640, 480, 'city', true),
                'latitude' => $faker->latitude,
                'longitude' => $faker->longitude,
            ];
        }
        Location::insert($locations);

        // Seed Accommodations, Tours, Homestays, Vehicles, Boats, Events, News
        $vendors = Vendor::all();
        foreach ($vendors as $vendor) {
            for ($i = 0; $i < 3; $i++) {
                Accommodation::create([
                    'vendor_id' => $vendor->id,
                    'location_id' => $faker->randomElement(Location::pluck('id')->toArray()),
                    'name' => $faker->company . " Hotel",
                    'description' => $faker->paragraph,
                    'address' => $faker->address,
                    'star_rating' => $faker->numberBetween(1, 5),
                    'image' => $faker->imageUrl(640, 480, 'hotel', true),
                    'price_per_night' => $faker->randomFloat(2, 50, 500),
                    'type' => $faker->randomElement(['hotel', 'hostel', 'apartment', 'villa']),
                    'features' => json_encode($faker->randomElements(['wifi', 'pool', 'gym', 'spa', 'restaurant'], $faker->numberBetween(1, 5))),
                ]);

                Tour::create([
                    'vendor_id' => $vendor->id,
                    'location_id' => $faker->randomElement(Location::pluck('id')->toArray()),
                    'name' => $faker->sentence(3) . " Tour",
                    'description' => $faker->paragraph,
                    'duration' => $faker->numberBetween(1, 7),
                    'price' => $faker->randomFloat(2, 20, 200),
                    'image' => $faker->imageUrl(640, 480, 'nature', true),
                    'features' => json_encode($faker->randomElements(['guide', 'transport', 'meals', 'entrance fees'], $faker->numberBetween(1, 4))),
                ]);

                Homestay::create([
                    'vendor_id' => $vendor->id,
                    'location_id' => $faker->randomElement(Location::pluck('id')->toArray()),
                    'name' => $faker->name . " Homestay",
                    'description' => $faker->paragraph,
                    'address' => $faker->address,
                    'price_per_night' => $faker->randomFloat(2, 20, 100),
                    'image' => $faker->imageUrl(640, 480, 'house', true),
                    'features' => json_encode($faker->randomElements(['wifi', 'breakfast', 'kitchen', 'parking'], $faker->numberBetween(1, 4))),
                ]);

                Vehicle::create([
                    'vendor_id' => $vendor->id,
                    'vehicle_type' => $faker->randomElement(['car', 'bus', 'motorbike']),
                    'model' => $faker->word,
                    'capacity' => $faker->numberBetween(1, 50),
                    'price_per_day' => $faker->randomFloat(2, 10, 150),
                    'image' => $faker->imageUrl(640, 480, 'transport', true),
                    'features' => json_encode($faker->randomElements(['AC', 'GPS', 'Audio System'], $faker->numberBetween(1, 3))),
                ]);

                Boat::create([
                    'vendor_id' => $vendor->id,
                    'boat_type' => $faker->randomElement(['Speedboat', 'Sailboat', 'Yacht']),
                    'capacity' => $faker->numberBetween(1, 20),
                    'price_per_hour' => $faker->randomFloat(2, 30, 300),
                    'image' => $faker->imageUrl(640, 480, 'boat', true),
                    'features' => json_encode($faker->randomElements(['Life jackets', 'Navigation', 'Sound System'], $faker->numberBetween(1, 3))),
                ]);

                Event::create([
                    'vendor_id' => $vendor->id,
                    'location_id' => $faker->randomElement(Location::pluck('id')->toArray()),
                    'name' => $faker->sentence(4) . " Event",
                    'description' => $faker->paragraph,
                    'start_date' => $faker->dateTimeBetween('now', '+3 months'),
                    'end_date' => $faker->dateTimeBetween('+3 months', '+6 months'),
                    'price' => $faker->randomFloat(2, 5, 100),
                    'image' => $faker->imageUrl(640, 480, 'nightlife', true),
                ]);

                News::create([
                    'vendor_id' => $vendor->id,
                    'title' => $faker->sentence,
                    'content' => $faker->paragraphs(3, true),
                    'image' => $faker->imageUrl(640, 480, 'business', true),
                    'published_at' => $faker->dateTimeBetween('-1 year', 'now'),
                ]);
            }
        }

// Seed Bookings
$users = User::all();
$bookableTypes = ['App\Models\Accommodation', 'App\Models\Tour', 'App\Models\Homestay', 'App\Models\Vehicle', 'App\Models\Boat', 'App\Models\Event'];
foreach ($users as $user) {
    for ($i = 0; $i < 5; $i++) {
        $bookableType = $faker->randomElement($bookableTypes);
        $bookable = null;

        switch ($bookableType) {
            case 'App\Models\Accommodation':
                $bookable = Accommodation::inRandomOrder()->first();
                break;
            case 'App\Models\Tour':
                $bookable = Tour::inRandomOrder()->first();
                break;
            case 'App\Models\Homestay':
                $bookable = Homestay::inRandomOrder()->first();
                break;
            case 'App\Models\Vehicle':
                $bookable = Vehicle::inRandomOrder()->first();
                break;
            case 'App\Models\Boat':
                $bookable = Boat::inRandomOrder()->first();
                break;
            case 'App\Models\Event':
                $bookable = Event::inRandomOrder()->first();
                break;
        }

        if ($bookable) {
            $startDate = $faker->dateTimeBetween('now', '+1 month');
            $endDate = null;
            $totalPrice = 0;
            $numberOfGuests = $faker->numberBetween(1, 5);

            if ($bookableType === 'App\Models\Accommodation' || $bookableType === 'App\Models\Homestay') {
                // Ensure end date is after start date
                $endDate = $faker->dateTimeBetween($startDate, (clone $startDate)->modify('+7 days'));
                $totalPrice = $bookable->price_per_night * $startDate->diff($endDate)->days * $numberOfGuests;
            } else if ($bookableType === 'App\Models\Tour') {
                $totalPrice = $bookable->price * $numberOfGuests;
            } else if ($bookableType === 'App\Models\Vehicle') {
                // Ensure end date is after start date
                $endDate = $faker->dateTimeBetween($startDate, (clone $startDate)->modify('+7 days'));
                $totalPrice = $bookable->price_per_day * $startDate->diff($endDate)->days;
            } else if ($bookableType === 'App\Models\Boat') {
                // Ensure end date is after start date, and limit to a reasonable duration for a boat rental
                $endDate = $faker->dateTimeBetween($startDate, (clone $startDate)->modify('+1 day'));
                $totalPrice = $bookable->price_per_hour * ($startDate->diff($endDate)->h + ($startDate->diff($endDate)->days * 24));
            } else if ($bookableType === 'App\Models\Event') {
                // Use the event's start and end dates
                $startDate = $bookable->start_date;
                $endDate = $bookable->end_date;
                $totalPrice = $bookable->price * $numberOfGuests;
            }

            Booking::create([
                'user_id' => $user->id,
                'bookable_id' => $bookable->id,
                'bookable_type' => $bookableType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'number_of_guests' => $numberOfGuests,
                'total_price' => $totalPrice,
                'status' => $faker->randomElement(['pending', 'confirmed', 'cancelled']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

        // Seed Favorites
        foreach ($users as $user) {
            for ($i = 0; $i < 10; $i++) {
                $favoritableType = $faker->randomElement($bookableTypes);
                $favoritable = null;
                switch ($favoritableType) {
                  case 'App\Models\Accommodation':
                    $favoritable = Accommodation::inRandomOrder()->first();
                    break;
                  case 'App\Models\Tour':
                    $favoritable = Tour::inRandomOrder()->first();
                    break;
                  case 'App\Models\Homestay':
                    $favoritable = Homestay::inRandomOrder()->first();
                    break;
                  case 'App\Models\Vehicle':
                    $favoritable = Vehicle::inRandomOrder()->first();
                    break;
                  case 'App\Models\Boat':
                    $favoritable = Boat::inRandomOrder()->first();
                    break;
                  case 'App\Models\Event':
                    $favoritable = Event::inRandomOrder()->first();
                    break;
                  case 'App\Models\Location':
                    $favoritableType = 'App\Models\Location';
                    $favoritable = Location::inRandomOrder()->first();
                    break;
                }
                if ($favoritable) {
                  Favorite::create([
                      'user_id' => $user->id,
                      'favoritable_id' => $favoritable->id,
                      'favoritable_type' => $favoritableType,
                  ]);
                }
            }
        }

        // Seed Reviews
        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                $reviewableType = $faker->randomElement($bookableTypes);
                $reviewable = null;
                switch ($reviewableType) {
                    case 'App\Models\Accommodation':
                        $reviewable = Accommodation::inRandomOrder()->first();
                        break;
                    case 'App\Models\Tour':
                        $reviewable = Tour::inRandomOrder()->first();
                        break;
                    case 'App\Models\Homestay':
                        $reviewable = Homestay::inRandomOrder()->first();
                        break;
                    case 'App\Models\Vehicle':
                        $reviewable = Vehicle::inRandomOrder()->first();
                        break;
                    case 'App\Models\Boat':
                        $reviewable = Boat::inRandomOrder()->first();
                        break;
                    case 'App\Models\Event':
                        $reviewable = Event::inRandomOrder()->first();
                        break;
                }

                if ($reviewable) {
                    Review::create([
                        'user_id' => $user->id,
                        'reviewable_id' => $reviewable->id,
                        'reviewable_type' => $reviewableType,
                        'rating' => $faker->numberBetween(1, 5),
                        'comment' => $faker->paragraph,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }
}