<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureTableSeeder extends Seeder
{
    /**
     * Run the seeder.
     */
    public function run(): void
    {
        $features = [
            ['name' => 'Sightseeing', 'description' => 'Includes guided city tours', 'is_active' => true],
            ['name' => 'Activities', 'description' => 'Outdoor or adventure activities', 'is_active' => true],
            ['name' => 'Pick-up/Drop-off', 'description' => 'Transportation to and from locations', 'is_active' => true],
            ['name' => 'Entrance Fees', 'description' => 'Covers entry to attractions', 'is_active' => true],
            ['name' => 'Transportation', 'description' => 'Includes all transport during the tour', 'is_active' => true],
            ['name' => 'Meals Included', 'description' => 'Provides meals as part of the tour', 'is_active' => true],
            ['name' => 'Insurance', 'description' => 'Travel insurance coverage', 'is_active' => true],
        ];

        foreach ($features as $feature) {
            Feature::create($feature);
        }
    }
}