<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Vendor;
use App\Models\Location;
use App\Models\TravelType;
use App\Models\Tour;

class ToursTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        $vendorIds = Vendor::pluck('id')->toArray();
        $locationIds = Location::pluck('id')->toArray();
        $travelTypeIds = TravelType::pluck('id')->toArray();

        if (empty($vendorIds) || empty($locationIds) || empty($travelTypeIds)) {
            $this->command->info('Không có Vendor, Location hoặc TravelType nào để tạo Tour. Hãy seed Vendor, Location và TravelType trước.');
            return;
        }

        for ($i = 0; $i < 50; $i++) {
            // Tạo số ngày ngẫu nhiên từ 1 đến 14
            $days = $faker->numberBetween(1, 14);

            // Tạo số đêm sao cho chênh lệch không quá 1
            if ($days == 1) {
                $nights = $faker->numberBetween(0, 1); // 0 hoặc 1 đêm
            } else {
                $nights = $faker->numberBetween($days - 1, $days); // Số đêm từ days - 1 đến days
            }

            Tour::create([
                'vendor_id' => $faker->randomElement($vendorIds),
                'location_id' => $faker->randomElement($locationIds),
                'travel_type_id' => $faker->randomElement($travelTypeIds), // Replaced category with travel_type_id
                'name' => $faker->sentence(3) . " Tour",
                'description' => $faker->paragraphs(3, true),
                'days' => $days,
                'nights' => $nights,
                // 'features' => json_encode($faker->randomElements([
                //     'Guided Tours', 'Meals Included', 'Accommodation',
                //     'Transportation', 'Entrance Fees', 'Local Guide',
                //     'Pick-up/Drop-off', 'Activities', 'Sightseeing', 'Insurance'
                // ], $faker->numberBetween(1, 8))),
            ]);
        }
    }
}