<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Itinerary;
use App\Models\ItineraryImage;

class ItineraryImagesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $itineraries = Itinerary::all();

        foreach ($itineraries as $itinerary) {
            for ($i = 0; $i < $faker->numberBetween(1, 3); $i++) { // 1-3 images per itinerary
                ItineraryImage::create([
                    'itinerary_id' => $itinerary->id,
                    'image_path' => $faker->randomElement([
                        'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747664770/lwnt0iuygqnh9oap4vom.jpg',
                        'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747664770/jpwxqiqnio8v1ifstpuz.png',
                        'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747664769/mycuepx9mbdo2euwltvu.png',
                        'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747505256/k89qj8jxmaq3bxhycgr2.jpg',
                        'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747505113/w6sbnrr6rcq4jpfmvvez.jpg',
                        'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747502638/iodica5heynyrbs71pea.jpg',
                        'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747427309/slt8snddxblxs6vwdisa.jpg',
                    ]),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}