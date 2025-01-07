<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Location;

class LocationsTableSeeder extends Seeder
{
    public function run()
    {
        $locations = [
            [
                'name' => 'Hạ Long Bay',
                'description' => 'Di sản thiên nhiên thế giới với hàng ngàn hòn đảo đá vôi.',
                'country' => 'Vietnam',
                'city' => 'Quảng Ninh',
                'image' => 'ha_long_bay.jpg',
                'latitude' => 20.9067,
                'longitude' => 107.0750,
            ],
            [
                'name' => 'Hội An Ancient Town',
                'description' => 'Phố cổ được bảo tồn tốt, di sản văn hóa thế giới.',
                'country' => 'Vietnam',
                'city' => 'Quảng Nam',
                'image' => 'hoi_an.jpg',
                'latitude' => 15.8800,
                'longitude' => 108.3350,
            ],
            [
                'name' => 'Sa Pa',
                'description' => 'Thị trấn vùng cao với cảnh quan núi non hùng vĩ và ruộng bậc thang.',
                'country' => 'Vietnam',
                'city' => 'Lào Cai',
                'image' => 'sa_pa.jpg',
                'latitude' => 22.3333,
                'longitude' => 103.8333,
            ],
            [
                'name' => 'Đà Lạt',
                'description' => 'Thành phố ngàn hoa với khí hậu mát mẻ.',
                'country' => 'Vietnam',
                'city' => 'Lâm Đồng',
                'image' => 'da_lat.jpg',
                'latitude' => 11.9400,
                'longitude' => 108.4500,
            ],
            [
                'name' => 'Nha Trang',
                'description' => 'Thành phố biển xinh đẹp với bãi biển trải dài.',
                'country' => 'Vietnam',
                'city' => 'Khánh Hòa',
                'image' => 'nha_trang.jpg',
                'latitude' => 12.2500,
                'longitude' => 109.1800,
            ],
            [
                'name' => 'Đà Nẵng',
                'description' => 'Thành phố biển năng động với nhiều điểm du lịch hấp dẫn.',
                'country' => 'Vietnam',
                'city' => 'Đà Nẵng',
                'image' => 'da_nang.jpg',
                'latitude' => 16.0479,
                'longitude' => 108.2209,
            ],
            [
                'name' => 'Phú Quốc',
                'description' => 'Đảo ngọc với những bãi biển tuyệt đẹp và rừng nguyên sinh.',
                'country' => 'Vietnam',
                'city' => 'Kiên Giang',
                'image' => 'phu_quoc.jpg',
                'latitude' => 10.2300,
                'longitude' => 103.9600,
            ],
            [
                'name' => 'Huế',
                'description' => 'Cố đô với nhiều di tích lịch sử và văn hóa.',
                'country' => 'Vietnam',
                'city' => 'Thừa Thiên Huế',
                'image' => 'hue.jpg',
                'latitude' => 16.4637,
                'longitude' => 107.5908,
            ],
            [
                'name' => 'Mũi Né',
                'description' => 'Địa điểm du lịch biển nổi tiếng với đồi cát và bãi biển đẹp.',
                'country' => 'Vietnam',
                'city' => 'Bình Thuận',
                'image' => 'mui_ne.jpg',
                'latitude' => 10.9333,
                'longitude' => 108.2833,
            ],
            [
                'name' => 'Cần Thơ',
                'description' => 'Thành phố miền Tây với chợ nổi Cái Răng và vườn trái cây.',
                'country' => 'Vietnam',
                'city' => 'Cần Thơ',
                'image' => 'can_tho.jpg',
                'latitude' => 10.0350,
                'longitude' => 105.7850,
            ],
        ];

        Location::insert($locations);
    }
}