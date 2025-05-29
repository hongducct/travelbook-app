<?php

namespace Database\Seeders;

use App\Models\Itinerary;
use App\Models\Tour;
use Illuminate\Database\Seeder;

class ItinerarySeeder extends Seeder
{
    public function run()
    {
        // Lấy tour ID = 4 từ dữ liệu JSON bạn cung cấp
        $tour = Tour::find(4);

        if ($tour) {
            $itineraries = [
                [
                    'tour_id' => 4,
                    'day' => 1,
                    'title' => 'HÀ NỘI – VÂN ĐỒN – QUAN LẠN (Ăn trưa, tối)',
                    'description' => 'Lộ trình chi tiết cho 3 ngày hành trình Tour của bạn.',
                    'activities' => [
                        '06h00: Xe và HDV Công ty du lịch Cattour đón Quý khách bắt đầu tour du lịch Quan Lạn Quảng Ninh, trên đường quý khách dừng nghỉ tại Sao Đỏ, Hải Dương, quý khách dùng bữa sáng tự túc.',
                        '11h00: Đến cảng Cái Rồng, quý khách dùng bữa trưa & nghỉ ngơi.',
                        'Chiều: Quý khách lên tàu cao tốc ra đảo Quan Lạn. Trên đường Quý khách có cơ hội ngắm nhìn phong cảnh du lịch tuyệt đẹp của vịnh Bái Tử Long hùng vĩ.',
                        'Đến nơi, đoàn nhận phòng nghỉ ngơi, tắm biển, hoặc chơi các trò chơi bãi biển do HDV tổ chức, tự do dạo chơi trải nghiệm trong khu du lịch Quan Lạn.',
                        '18h30: Ăn tối nhà hàng. Nghỉ đêm tại thị trấn.'
                    ],
                    'accommodation' => 'Nghỉ đêm tại thị trấn',
                    'meals' => 'Ăn trưa, tối',
                    'start_time' => '06:00',
                    'end_time' => '18:30',
                    'notes' => null,
                ],
                [
                    'tour_id' => 4,
                    'day' => 2,
                    'title' => 'KHÁM PHÁ ĐẢO QUAN LẠN (Ăn sáng, trưa, tối)',
                    'description' => null,
                    'activities' => [
                        'Sáng: Quý khách ăn sáng & tự do tắm biển.',
                        'Xe Túc Túc sẽ đón quý khách và gia đình, xe Túc Túc (Một loại xe 3 bánh đặc trưng trên đảo) đưa quý khách đi một số danh thắng trên đảo: Đền, miếu, đình, nghè Quan Lạn..., tìm hiểu cuộc sống của dân cư trên đảo hát lèn.',
                        'Chiều: Quý khách tự do tắm biển.',
                        'Tối: Ăn tối nhà hàng. Sau bữa tối quý khách sẽ được tham gia chương trình GALA DINNER đặc sắc, chắc chắn sẽ đem lại một kỷ niệm khó quên với du khách du lịch Quan Lạn. Quý khách nghỉ đêm tại khách sạn.'
                    ],
                    'accommodation' => 'Nghỉ đêm tại khách sạn',
                    'meals' => 'Ăn sáng, trưa, tối',
                    'start_time' => null, // Not specified in the image
                    'end_time' => null, // Not specified in the image
                    'notes' => 'Quý khách tự do tắm biển.',
                ],
                [
                    'tour_id' => 4,
                    'day' => 3,
                    'title' => 'QUAN LẠN – VÂN ĐỒN – HÀ NỘI (Ăn sáng, trưa)',
                    'description' => null,
                    'activities' => [
                        'Sáng: Ăn sáng, sau đó Quý khách tự do tắm biển, mua sắm đồ hải sản hoặc tham gia các trò chơi thể thao trên biển: bóng đá, bóng chuyền, cầu lông...',
                        '11h00: Ăn trưa tại khách sạn, sau đó trả phòng khách sạn. Lên tàu trở về đất liền. Xe đón quý khách tại cảng Cái Rồng đưa về Hà Nội, trên đường dừng chân nghỉ tại Hải Dương, mua đặc sản địa phương về làm quà du lịch QUAN LẠN Quảng Ninh cho những người thân yêu.',
                        '18h00: Xe tiễn Hà Nội, kết thúc chương trình tour du lịch Quan Lạn 3 ngày 2 đêm. HDV chia tay đoàn và hẹn gặp lại.'
                    ],
                    'accommodation' => null, // No accommodation as the tour ends
                    'meals' => 'Ăn sáng, trưa',
                    'start_time' => null, // Not specified in the image
                    'end_time' => '18:00',
                    'notes' => 'HDV chia tay đoàn và hẹn gặp lại.',
                ],
            ];

            foreach ($itineraries as $itinerary) {
                Itinerary::create($itinerary);
            }
        }
    }
}
