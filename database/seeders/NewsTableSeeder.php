<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Vendor;
use App\Models\News;

class NewsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $vendors = Vendor::all();

        foreach ($vendors as $vendor) {
            for ($i = 0; $i < 3; $i++) { // Create 3 news articles per vendor
                News::create([
                    'vendor_id' => $vendor->id,
                    'title' => $faker->sentence,
                    'content' => $faker->paragraphs(3, true),
                    'image' => $faker->imageUrl(640, 480, 'business', true),
                    'published_at' => $faker->dateTimeBetween('-1 year', 'now'),
                    'blog_status' => $faker->randomElement(['draft', 'pending', 'rejected', 'published', 'archived']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);                
            }
        }
    }
}