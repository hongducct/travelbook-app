<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\TravelType;

class TravelTypesTableSeeder extends Seeder
{
    /**
     * Chạy seeder để tạo dữ liệu mẫu cho bảng travel_types.
     * Tạo các loại hình du lịch phổ biến phù hợp với thị trường Việt Nam.
     *
     * @return void
     */
    public function run(): void
    {
        // Xóa dữ liệu cũ trước khi seed
        // DB::table('travel_types')->truncate();

        $travelTypes = [
            [
                'name' => 'Du lịch mạo hiểm',
                'description' => 'Hình thức du lịch bao gồm các thử thách thể chất, kỹ năng chuyên môn và thường có yếu tố rủi ro như leo núi, lặn biển, nhảy bungee.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch văn hóa',
                'description' => 'Du lịch tập trung vào trải nghiệm lịch sử, nghệ thuật, kiến trúc và truyền thống của một vùng miền như thăm di tích, làng nghề truyền thống.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch sinh thái',
                'description' => 'Du lịch có trách nhiệm đến các khu vực tự nhiên, bảo tồn môi trường và cải thiện đời sống của người dân địa phương như thăm vườn quốc gia, khu bảo tồn.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch cao cấp',
                'description' => 'Du lịch mang đến mức độ thoải mái, dịch vụ và độ độc quyền cao nhất với resort 5 sao, dịch vụ butler, du thuyền riêng.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch nghỉ dưỡng',
                'description' => 'Du lịch tập trung vào việc nghỉ ngơi, phục hồi sức khỏe và thoát khỏi căng thẳng cuộc sống hàng ngày tại spa, resort biển, suối nước nóng.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch tâm linh',
                'description' => 'Du lịch đến các địa điểm tôn giáo và tâm linh để hành hương hoặc tìm kiếm giá trị tinh thần như chùa, đền, núi thiêng.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch một mình',
                'description' => 'Hình thức du lịch được thực hiện bởi một người đi du lịch một mình, mang lại sự tự do và khám phá bản thân.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch bụi',
                'description' => 'Du lịch độc lập, tiết kiệm chi phí, thường mang theo ba lô và ở trong các nhà trọ, hostel, khám phá nhiều địa điểm với ngân sách ít.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch công vụ',
                'description' => 'Du lịch phục vụ mục đích công việc như tham dự hội nghị, họp mặt doanh nghiệp, triển lãm thương mại.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch ẩm thực',
                'description' => 'Du lịch tập trung vào việc khám phá và trải nghiệm ẩm thực và món ăn đặc trưng của từng vùng miền.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch tình nguyện',
                'description' => 'Du lịch kết hợp với công việc tình nguyện, thường ở các nước đang phát triển hoặc cho một mục đích cụ thể như xây trường, dạy học.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch gia đình',
                'description' => 'Du lịch cùng với các thành viên trong gia đình, thường bao gồm trẻ em với các hoạt động phù hợp cho mọi lứa tuổi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch bằng xe hơi',
                'description' => 'Du lịch bằng ô tô với nhiều điểm dừng chân dọc theo tuyến đường, khám phá các địa điểm khác nhau trên đường đi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch thành phố ngắn ngày',
                'description' => 'Các chuyến đi ngắn đến thành phố, thường trong cuối tuần hoặc vài ngày để khám phá các điểm du lịch đô thị.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch chăm sóc sức khỏe',
                'description' => 'Du lịch tập trung vào cải thiện sức khỏe thể chất và tinh thần thông qua yoga, thiền định, spa và các liệu pháp chữa lành.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch quan sát động vật hoang dã',
                'description' => 'Du lịch để quan sát động vật trong môi trường sống tự nhiên của chúng như safari, ngắm chim, ngắm cá voi.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch biển đảo',
                'description' => 'Du lịch tập trung vào các hoạt động biển như tắm biển, lặn ngắm san hô, các môn thể thao nước và khám phá đảo.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch miền núi',
                'description' => 'Du lịch khám phá vùng núi với các hoạt động như trekking, cắm trại, ngắm cảnh thiên nhiên và tìm hiểu văn hóa dân tộc.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch kết hợp học tập',
                'description' => 'Du lịch có mục đích giáo dục, học tập ngôn ngữ, kỹ năng mới hoặc tham gia các khóa học ngắn hạn.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Du lịch nhiếp ảnh',
                'description' => 'Du lịch chuyên để chụp ảnh cảnh đẹp, động vật, con người với mục đích tạo ra những tác phẩm nghệ thuật.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Sử dụng insert để tăng hiệu suất
        DB::table('travel_types')->insert($travelTypes);

        // Hoặc sử dụng Eloquent nếu cần trigger events
        // foreach ($travelTypes as $travelType) {
        //     TravelType::create($travelType);
        // }

        $this->command->info('Đã tạo thành công ' . count($travelTypes) . ' loại hình du lịch.');
        $this->command->line('Tất cả loại hình du lịch đã được dịch sang tiếng Việt.');
    }
}
