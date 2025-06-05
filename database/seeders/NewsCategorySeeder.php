<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NewsCategory;

class NewsCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            [
                'name' => 'Điểm đến hot',
                'slug' => 'diem-den-hot',
                'description' => 'Những điểm đến du lịch hot nhất hiện tại',
                'color' => '#EF4444',
                'icon' => 'FireIcon',
                'sort_order' => 1,
            ],
            [
                'name' => 'Kinh nghiệm du lịch',
                'slug' => 'kinh-nghiem-du-lich',
                'description' => 'Chia sẻ kinh nghiệm và mẹo hay khi đi du lịch',
                'color' => '#10B981',
                'icon' => 'LightBulbIcon',
                'sort_order' => 2,
            ],
            [
                'name' => 'Ẩm thực địa phương',
                'slug' => 'am-thuc-dia-phuong',
                'description' => 'Khám phá ẩm thực đặc sản các vùng miền',
                'color' => '#F59E0B',
                'icon' => 'CakeIcon',
                'sort_order' => 3,
            ],
            [
                'name' => 'Du lịch bụi',
                'slug' => 'du-lich-bui',
                'description' => 'Hướng dẫn du lịch bụi tiết kiệm và tự do',
                'color' => '#8B5CF6',
                'icon' => 'BackpackIcon',
                'sort_order' => 4,
            ],
            [
                'name' => 'Resort & Khách sạn',
                'slug' => 'resort-khach-san',
                'description' => 'Review và giới thiệu các resort, khách sạn đẹp',
                'color' => '#EC4899',
                'icon' => 'BuildingOffice2Icon',
                'sort_order' => 5,
            ],
            [
                'name' => 'Du lịch gia đình',
                'slug' => 'du-lich-gia-dinh',
                'description' => 'Gợi ý cho những chuyến du lịch cùng gia đình',
                'color' => '#06B6D4',
                'icon' => 'HomeIcon',
                'sort_order' => 6,
            ],
            [
                'name' => 'Phượt xe máy',
                'slug' => 'phuot-xe-may',
                'description' => 'Những hành trình phượt bằng xe máy đáng nhớ',
                'color' => '#84CC16',
                'icon' => 'TruckIcon',
                'sort_order' => 7,
            ],
            [
                'name' => 'Du lịch nước ngoài',
                'slug' => 'du-lich-nuoc-ngoai',
                'description' => 'Kinh nghiệm du lịch các quốc gia trên thế giới',
                'color' => '#3B82F6',
                'icon' => 'GlobeAsiaAustraliaIcon',
                'sort_order' => 8,
            ],
            [
                'name' => 'Lễ hội & Sự kiện',
                'slug' => 'le-hoi-su-kien',
                'description' => 'Thông tin về các lễ hội và sự kiện du lịch',
                'color' => '#F97316',
                'icon' => 'MusicalNoteIcon',
                'sort_order' => 9,
            ],
            [
                'name' => 'Mẹo tiết kiệm',
                'slug' => 'meo-tiet-kiem',
                'description' => 'Những mẹo hay để du lịch tiết kiệm chi phí',
                'color' => '#059669',
                'icon' => 'CurrencyDollarIcon',
                'sort_order' => 10,
            ],
            [
                'name' => 'Văn hóa & Lịch sử',
                'slug' => 'van-hoa-lich-su',
                'description' => 'Tìm hiểu văn hóa và lịch sử các vùng đất',
                'color' => '#7C3AED',
                'icon' => 'BookOpenIcon',
                'sort_order' => 11,
            ],
            [
                'name' => 'Chụp ảnh du lịch',
                'slug' => 'chup-anh-du-lich',
                'description' => 'Kỹ thuật và địa điểm chụp ảnh đẹp khi du lịch',
                'color' => '#DC2626',
                'icon' => 'CameraIcon',
                'sort_order' => 12,
            ],
        ];

        foreach ($categories as $category) {
            NewsCategory::create($category);
        }
    }
}
