<?php

namespace Database\Seeders;

use App\Models\TourImage;
use Illuminate\Database\Seeder;
use Faker\Factory as Faker;

class TourImagesTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        for ($i = 0; $i < 20; $i++) { // Tạo 20 hình ảnh, mỗi tour có thể có 1-2 hình
            TourImage::create([
                'tour_id' => $faker->numberBetween(1, 10),
                // 'image_url' => $faker->imageUrl(640, 480, 'travel'),
                // random các url sau:
                'image_url' => $faker->randomElement([
                    'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747664770/lwnt0iuygqnh9oap4vom.jpg',
                    'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747664770/jpwxqiqnio8v1ifstpuz.png',
                    'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747664769/mycuepx9mbdo2euwltvu.png',
                    'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747505256/k89qj8jxmaq3bxhycgr2.jpg',
                    'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747505113/w6sbnrr6rcq4jpfmvvez.jpg',
                    'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747502638/iodica5heynyrbs71pea.jpg',
                    'https://res.cloudinary.com/dlhra4ihw/image/upload/v1747427309/slt8snddxblxs6vwdisa.jpg',
                ]),
                'caption' => $faker->optional(0.8)->sentence(),
                'is_primary' => $faker->boolean(20), // 20% cơ hội là ảnh chính
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}