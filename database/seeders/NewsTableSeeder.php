<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Vendor;
use App\Models\Admin;
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
        $admins = Admin::all();

        // Ensure there are vendors and admins to seed
        if ($vendors->isEmpty() && $admins->isEmpty()) {
            return;
        }

        // Create 3 news articles per vendor or admin
        $authors = [];
        if (!$vendors->isEmpty()) {
            $authors = array_merge($authors, $vendors->map(fn($vendor) => ['type' => 'vendor', 'id' => $vendor->id])->toArray());
        }
        if (!$admins->isEmpty()) {
            $authors = array_merge($authors, $admins->map(fn($admin) => ['type' => 'admin', 'id' => $admin->id])->toArray());
        }

        foreach ($authors as $author) {
            for ($i = 0; $i < 3; $i++) {
                News::create([
                    'author_type' => $author['type'],
                    'admin_id' => $author['type'] === 'admin' ? $author['id'] : null,
                    'vendor_id' => $author['type'] === 'vendor' ? $author['id'] : null,
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
