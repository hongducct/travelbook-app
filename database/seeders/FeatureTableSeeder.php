<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FeatureTableSeeder extends Seeder
{
    /**
     * Chạy seeder để tạo dữ liệu mẫu cho bảng features.
     *
     * @return void
     */
    public function run(): void
    {
        // Xóa dữ liệu cũ trước khi seed (nếu cần)
        // DB::table('features')->truncate();

        $features = [
            [
                'name' => 'Tham quan du lịch',
                'description' => 'Bao gồm các tour tham quan thành phố có hướng dẫn viên',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hoạt động giải trí',
                'description' => 'Các hoạt động ngoài trời hoặc phiêu lưu mạo hiểm',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Đưa đón tận nơi',
                'description' => 'Dịch vụ vận chuyển từ và đến các địa điểm du lịch',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Phí vào cổng',
                'description' => 'Chi phí vé vào các điểm tham quan và khu du lịch',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Phương tiện di chuyển',
                'description' => 'Bao gồm tất cả phương tiện vận chuyển trong suốt chuyến tour',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bữa ăn được bao gồm',
                'description' => 'Cung cấp các bữa ăn như một phần của chuyến tour',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Bảo hiểm du lịch',
                'description' => 'Bảo hiểm bao phủ các rủi ro trong chuyến du lịch',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Hướng dẫn viên chuyên nghiệp',
                'description' => 'Hướng dẫn viên du lịch có kinh nghiệm và am hiểu địa phương',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Chỗ ở được sắp xếp',
                'description' => 'Khách sạn hoặc nơi lưu trú được đặt trước cho khách hàng',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Wifi miễn phí',
                'description' => 'Kết nối internet không dây miễn phí trong suốt chuyến tour',
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Sử dụng insert để tăng hiệu suất thay vì create từng item
        DB::table('features')->insert($features);

        // Hoặc nếu muốn sử dụng Eloquent model để trigger events
        // foreach ($features as $feature) {
        //     Feature::create($feature);
        // }

        $this->command->info('Đã tạo thành công ' . count($features) . ' tính năng tour du lịch.');
    }
}
