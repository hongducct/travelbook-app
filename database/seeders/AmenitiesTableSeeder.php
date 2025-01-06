<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Amenity;

class AmenitiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $amenities = [
            // Accommodation Amenities
            ['name' => 'Free Wi-Fi', 'icon' => 'fa-wifi', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Air Conditioning', 'icon' => 'fa-snowflake', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Heating', 'icon' => 'fa-fire', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Breakfast Included', 'icon' => 'fa-coffee', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Room Service', 'icon' => 'fa-bell', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Private Bathroom', 'icon' => 'fa-bath', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Shared Bathroom', 'icon' => 'fa-shower', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kitchen/Kitchenette', 'icon' => 'fa-utensils', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'TV', 'icon' => 'fa-tv', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Balcony/Terrace', 'icon' => 'fa-sun', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pool', 'icon' => 'fa-swimming-pool', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gym/Fitness Center', 'icon' => 'fa-dumbbell', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Spa', 'icon' => 'fa-spa', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Parking', 'icon' => 'fa-parking', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Elevator/Lift', 'icon' => 'fa-caret-square-up', 'created_at' => now(), 'updated_at' => now()],
            ['name' => '24-Hour Front Desk', 'icon' => 'fa-concierge-bell', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Non-Smoking Rooms', 'icon' => 'fa-smoking-ban', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Family Rooms', 'icon' => 'fa-users', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pet-Friendly', 'icon' => 'fa-paw', 'created_at' => now(), 'updated_at' => now()],

            // Tour Amenities
            ['name' => 'Guided Tours', 'icon' => 'fa-map-signs', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Audio Guides', 'icon' => 'fa-headphones', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Transportation Included', 'icon' => 'fa-bus', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Meals Included', 'icon' => 'fa-utensils', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Entrance Fees Included', 'icon' => 'fa-ticket-alt', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Bottled Water', 'icon' => 'fa-bottle-water', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Snacks', 'icon' => 'fa-cookie', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'First Aid Kit', 'icon' => 'fa-first-aid', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Photography Allowed', 'icon' => 'fa-camera', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Wheelchair Accessible', 'icon' => 'fa-wheelchair', 'created_at' => now(), 'updated_at' => now()],

        ];

        Amenity::insert($amenities);
    }
}