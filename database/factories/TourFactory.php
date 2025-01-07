<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Tour;
use App\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class TourFactory extends Factory
{
    public function definition(): array
    {
        $categories = ['Adventure', 'Cultural', 'Relaxation', 'Food', 'Historical', 'Spiritual', 'Nature', 'Family', 'Honeymoon', 'City Breaks', 'Eco-tourism', 'Volunteer'];
        $days = $this->faker->numberBetween(1, 14);
        $nights = $this->faker->numberBetween(0, $days -1 ); // Số đêm không thể lớn hơn số ngày và có thể bằng 0(tour trong ngày)
        return [
            'vendor_id' => Vendor::factory(),
            'location_id' => Location::factory(),
            'name' => $this->faker->sentence(3) . " Tour",
            'description' => $this->faker->paragraphs(3, true),
            'days' => $days,
            'nights' => $nights,
            'category' => $this->faker->randomElement($categories),
            'features' => json_encode($this->faker->randomElements([
                'Guided Tours', 'Meals Included', 'Accommodation',
                'Transportation', 'Entrance Fees', 'Local Guide',
                'Pick-up/Drop-off', 'Activities', 'Sightseeing', 'Insurance'
            ], $this->faker->numberBetween(1, 8))),
        ];
    }
}