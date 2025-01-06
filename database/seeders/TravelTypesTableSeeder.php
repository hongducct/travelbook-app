<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TravelType;

class TravelTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $travelTypes = [
            ['name' => 'Adventure', 'description' => 'Travel that involves some physical challenge, specialized skills, and often some risk (real or perceived).', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Cultural', 'description' => 'Travel focused on experiencing the history, art, architecture, and traditions of a place.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Eco-tourism', 'description' => 'Responsible travel to natural areas that conserves the environment and improves the well-being of local people.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Luxury', 'description' => 'Travel that offers the highest level of comfort, service, and exclusivity.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Relaxation', 'description' => 'Travel focused on rest, rejuvenation, and escaping the stresses of everyday life.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Religious', 'description' => 'Travel to religious sites and destinations for pilgrimage or spiritual purposes.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Solo', 'description' => 'Travel undertaken by a person traveling alone.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Backpacking', 'description' => 'Independent, budget-friendly travel, often involving carrying a backpack and staying in hostels.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Business', 'description' => 'Travel for work-related purposes, such as attending conferences, meetings, or trade shows.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Culinary', 'description' => 'Travel focused on exploring and experiencing the food and cuisine of a region.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Volunteer', 'description' => 'Travel combined with volunteering work, often in developing countries or for a specific cause.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Family', 'description' => 'Travel undertaken with family members, often including children.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Road Trip', 'description' => 'Travel by car, often with multiple stops along a route, exploring different places along the way.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'City Break', 'description' => 'Short trips to cities, often for a weekend or a few days, to explore urban attractions.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Wellness', 'description' => 'Travel focused on improving physical and mental health, often involving activities like yoga, meditation, and spa treatments.', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Wildlife', 'description' => 'Travel to observe animals in their natural habitat, often involving safaris, birdwatching, or whale watching.', 'created_at' => now(), 'updated_at' => now()],

        ];

        TravelType::insert($travelTypes);
    }
}