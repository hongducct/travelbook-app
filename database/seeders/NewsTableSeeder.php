<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\Vendor;
use App\Models\Admin;
use App\Models\News;
use App\Models\NewsCategory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class NewsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create('vi_VN');
        $vendors = Vendor::all();
        $admins = Admin::all();
        $categories = NewsCategory::all();
        
        // Get existing users for view records
        $existingUsers = User::pluck('id')->toArray();

        if ($vendors->isEmpty() && $admins->isEmpty()) {
            $this->command->warn('No vendors or admins found. Please seed them first.');
            return;
        }

        if ($categories->isEmpty()) {
            $this->command->warn('No categories found. Please run NewsCategorySeeder first.');
            return;
        }

        // Travel-related tags
        $travelTags = [
            // Địa điểm Việt Nam
            'Hà Nội', 'Hồ Chí Minh', 'Đà Nẵng', 'Hội An', 'Nha Trang', 'Phú Quốc', 'Sapa', 'Hạ Long',
            'Đà Lạt', 'Cần Thơ', 'Huế', 'Quy Nhon', 'Vũng Tàu', 'Phan Thiết', 'Ninh Bình', 'Tam Cốc',
            'Mù Cang Chải', 'Cao Bằng', 'Lào Cai', 'Điện Biên', 'Hà Giang', 'Lai Châu', 'Yên Bái',
            
            // Quốc gia
            'Thái Lan', 'Singapore', 'Malaysia', 'Indonesia', 'Philippines', 'Campuchia', 'Lào',
            'Nhật Bản', 'Hàn Quốc', 'Trung Quốc', 'Đài Loan', 'Hong Kong', 'Macao',
            'Úc', 'New Zealand', 'Mỹ', 'Canada', 'Pháp', 'Ý', 'Tây Ban Nha', 'Đức',
            
            // Loại hình du lịch
            'Du lịch biển', 'Du lịch núi', 'Du lịch văn hóa', 'Du lịch tâm linh', 'Du lịch ẩm thực',
            'Du lịch sinh thái', 'Du lịch mạo hiểm', 'Du lịch nghỉ dưỡng', 'Du lịch khám phá',
            'Phượt', 'Backpacking', 'Camping', 'Trekking', 'Diving', 'Snorkeling',
            
            // Mùa du lịch
            'Mùa xuân', 'Mùa hè', 'Mùa thu', 'Mùa đông', 'Tết Nguyên Đán', 'Lễ 30/4', 'Lễ 2/9',
            
            // Ngân sách
            'Du lịch tiết kiệm', 'Du lịch cao cấp', 'Du lịch bình dân', 'All-inclusive',
            
            // Phương tiện
            'Máy bay', 'Xe khách', 'Xe máy', 'Ô tô', 'Tàu hỏa', 'Tàu thủy', 'Xe đạp',
            
            // Ẩm thực
            'Phở', 'Bánh mì', 'Bún bò Huế', 'Cao lầu', 'Mì Quảng', 'Bánh xèo', 'Gỏi cuốn',
            'Chả cá', 'Bún chả', 'Bánh cuốn', 'Chè', 'Cà phê', 'Bia hơi',
            
            // Hoạt động
            'Chụp ảnh', 'Check-in', 'Shopping', 'Massage', 'Spa', 'Karaoke', 'Bar',
            'Tham quan', 'Khám phá', 'Trải nghiệm', 'Học tập', 'Tình nguyện',
        ];

        // Vietnamese travel blog titles
        $travelTitles = [
            'Top 10 điểm đến không thể bỏ qua khi đến {destination}',
            'Kinh nghiệm du lịch {destination} tự túc từ A đến Z',
            'Ẩm thực {destination}: Những món ăn phải thử một lần trong đời',
            'Hành trình khám phá {destination} trong {duration} ngày',
            'Du lịch {destination} với ngân sách chỉ {budget} triệu đồng',
            'Review chi tiết chuyến du lịch {destination} vừa qua',
            'Những điều cần biết trước khi đến {destination}',
            'Lịch trình du lịch {destination} hoàn hảo cho gia đình',
            'Phượt {destination}: Cung đường đẹp nhất Việt Nam',
            'Khám phá vẻ đẹp hoang sơ của {destination}',
            'Du lịch {destination} mùa {season}: Trải nghiệm tuyệt vời',
            'Những resort đẹp nhất tại {destination}',
            'Chụp ảnh sống ảo tại {destination}: Góc nào cũng đẹp',
            'Lễ hội truyền thống độc đáo tại {destination}',
            'Mẹo tiết kiệm chi phí khi du lịch {destination}',
            'Văn hóa và con người {destination} qua góc nhìn du khách',
            'Những trải nghiệm không thể bỏ qua tại {destination}',
            'Du lịch {destination} một mình: An toàn và thú vị',
            'Cẩm nang du lịch {destination} cho người lần đầu',
            'Khách sạn giá rẻ chất lượng tốt tại {destination}',
        ];

        // Vietnamese destinations
        $destinations = [
            'Hà Nội', 'Hồ Chí Minh', 'Đà Nẵng', 'Hội An', 'Nha Trang', 'Phú Quốc', 'Sapa', 'Hạ Long',
            'Đà Lạt', 'Cần Thơ', 'Huế', 'Quy Nhon', 'Vũng Tàu', 'Phan Thiết', 'Ninh Bình', 'Tam Cốc',
            'Mù Cang Chải', 'Cao Bằng', 'Lào Cai', 'Hà Giang', 'Phuket', 'Bangkok', 'Singapore',
            'Kuala Lumpur', 'Bali', 'Tokyo', 'Seoul', 'Jeju', 'Taipei', 'Hong Kong', 'Macao',
        ];

        // Travel seasons
        $seasons = ['xuân', 'hè', 'thu', 'đông', 'mưa', 'khô'];

        // Content templates for travel blogs
        $contentTemplates = [
            '{destination} là một trong những điểm đến du lịch hấp dẫn nhất {region}. Với vẻ đẹp tự nhiên hoang sơ, văn hóa độc đáo và ẩm thực phong phú, {destination} luôn thu hút hàng triệu du khách mỗi năm.',
            
            'Chuyến du lịch {destination} của tôi thực sự là một trải nghiệm khó quên. Từ những cảnh đẹp ngoạn mục đến những con người thân thiện, mọi thứ đều khiến tôi muốn quay lại lần nữa.',
            
            'Nếu bạn đang lên kế hoạch du lịch {destination}, bài viết này sẽ cung cấp cho bạn những thông tin hữu ích nhất. Từ lịch trình chi tiết đến những mẹo tiết kiệm chi phí.',
            
            'Ẩm thực {destination} thực sự đa dạng và phong phú. Mỗi món ăn đều mang trong mình câu chuyện văn hóa riêng, tạo nên bản sắc ẩm thực độc đáo của vùng đất này.',
            
            'Du lịch {destination} không chỉ là việc tham quan các điểm du lịch nổi tiếng mà còn là cơ hội để bạn khám phá văn hóa, lịch sử và con người nơi đây.',
        ];

        // Create authors array
        $authors = [];
        if (!$vendors->isEmpty()) {
            foreach ($vendors as $vendor) {
                $authors[] = ['type' => 'vendor', 'id' => $vendor->id];
            }
        }
        if (!$admins->isEmpty()) {
            foreach ($admins as $admin) {
                $authors[] = ['type' => 'admin', 'id' => $admin->id];
            }
        }

        // Create 60 travel blog posts
        $totalPosts = 20;
        $this->command->info("Creating {$totalPosts} travel blog posts...");

        for ($i = 0; $i < $totalPosts; $i++) {
            $author = $faker->randomElement($authors);
            $category = $faker->randomElement($categories);
            $destination = $faker->randomElement($destinations);
            $season = $faker->randomElement($seasons);
            $duration = $faker->numberBetween(2, 14);
            $budget = $faker->numberBetween(2, 50);
            
            // Generate title
            $titleTemplate = $faker->randomElement($travelTitles);
            $title = str_replace(['{destination}', '{season}', '{duration}', '{budget}'], 
                               [$destination, $season, $duration, $budget], $titleTemplate);
            
            // Generate slug
            $slug = Str::slug($title);
            
            // Ensure unique slug
            $originalSlug = $slug;
            $counter = 1;
            while (News::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
            
            // Generate content
            $contentTemplate = $faker->randomElement($contentTemplates);
            $region = $this->getRegion($destination);
            $content = str_replace(['{destination}', '{region}'], [$destination, $region], $contentTemplate);
            $content .= "\n\n" . $faker->paragraphs(rand(5, 12), true);
            
            // Add travel-specific content
            $content .= "\n\n## Thông tin hữu ích:\n";
            $content .= "- **Thời gian tốt nhất**: " . ucfirst($season) . "\n";
            $content .= "- **Thời gian du lịch**: {$duration} ngày\n";
            $content .= "- **Ngân sách ước tính**: " . number_format($budget * 1000000) . " VNĐ\n";
            
            // Generate excerpt
            $excerpt = "Khám phá {$destination} với những trải nghiệm tuyệt vời và ngân sách chỉ từ " . number_format($budget * 1000000) . " VNĐ cho {$duration} ngày.";
            
            // Random tags (4-8 tags per post)
            $postTags = $faker->randomElements($travelTags, rand(4, 8));
            // Always include destination as tag
            if (!in_array($destination, $postTags)) {
                $postTags[] = $destination;
            }
            
            // Random publish date (last 8 months)
            $publishedAt = $faker->dateTimeBetween('-8 months', 'now');
            $createdAt = Carbon::instance($publishedAt)->subDays(rand(0, 7));
            
            // Status distribution: 75% published, 12% draft, 8% pending, 5% others
            $statusWeights = [
                'published' => 75,
                'draft' => 12,
                'pending' => 8,
                'rejected' => 3,
                'archived' => 2
            ];
            $status = $faker->randomElement(
                array_merge(...array_map(
                    fn($status, $weight) => array_fill(0, $weight, $status),
                    array_keys($statusWeights),
                    array_values($statusWeights)
                ))
            );
            
            // Featured posts (15% chance)
            $isFeatured = $faker->boolean(15);
            
            // View count (higher for older and published posts)
            $viewCount = 0;
            if ($status === 'published') {
                $daysSincePublished = Carbon::instance($publishedAt)->diffInDays(now());
                $baseViews = max(50, 2000 - ($daysSincePublished * 8));
                $viewCount = $faker->numberBetween($baseViews * 0.6, $baseViews * 1.4);
                if ($isFeatured) {
                    $viewCount *= 1.8; // Featured posts get more views
                }
            }
            
            // Reading time based on content length
            $wordCount = str_word_count(strip_tags($content));
            $readingTime = max(2, ceil($wordCount / 200)); // 200 words per minute
            
            // Last viewed at (for published posts)
            $lastViewedAt = null;
            if ($status === 'published' && $viewCount > 0) {
                $lastViewedAt = $faker->dateTimeBetween($publishedAt, 'now');
            }
            
            // Travel season
            $travelSeasonMap = [
                'xuân' => 'spring',
                'hè' => 'summer', 
                'thu' => 'autumn',
                'đông' => 'winter',
                'mưa' => 'summer',
                'khô' => 'winter'
            ];
            $travelSeason = $travelSeasonMap[$season] ?? 'all_year';
            
            // Coordinates for destination (mock data)
            $coordinates = $this->getCoordinates($destination);
            
            // Travel tips
            $travelTips = [
                'Mang theo kem chống nắng và nón',
                'Chuẩn bị thuốc cá nhân cần thiết',
                'Học vài câu tiếng địa phương cơ bản',
                'Mang theo tiền mặt cho những nơi không có ATM',
                'Kiểm tra thời tiết trước khi đi',
                'Đặt chỗ trước trong mùa cao điểm',
                'Tôn trọng văn hóa và phong tục địa phương',
                'Mang theo sạc dự phòng cho điện thoại',
            ];
            $selectedTips = $faker->randomElements($travelTips, rand(3, 6));

            $news = News::create([
                'author_type' => $author['type'],
                'admin_id' => $author['type'] === 'admin' ? $author['id'] : null,
                'vendor_id' => $author['type'] === 'vendor' ? $author['id'] : null,
                'category_id' => $category->id,
                'title' => $title,
                'slug' => $slug,
                'content' => $content,
                'excerpt' => $excerpt,
                'tags' => json_encode($postTags),
                'image' => $this->getTravelImage($destination),
                'published_at' => $status === 'published' ? $publishedAt : null,
                'blog_status' => $status,
                'is_featured' => $isFeatured,
                'view_count' => $viewCount,
                'reading_time' => $readingTime,
                'last_viewed_at' => $lastViewedAt,
                'meta_description' => Str::limit($excerpt, 155),
                'meta_keywords' => implode(', ', array_slice($postTags, 0, 8)),
                'destination' => $destination,
                'latitude' => $coordinates['lat'],
                'longitude' => $coordinates['lng'],
                'travel_season' => $travelSeason,
                'travel_tips' => json_encode($selectedTips),
                'estimated_budget' => $budget * 1000000, // Convert to VND
                'duration_days' => $duration,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            // Create some view records for published posts
            if ($status === 'published' && $viewCount > 0) {
                $this->createViewRecords($news, $viewCount, $publishedAt, $existingUsers);
            }

            if (($i + 1) % 10 === 0) {
                $this->command->info("Created " . ($i + 1) . " posts...");
            }
        }

        $this->command->info("Successfully created {$totalPosts} travel blog posts!");
    }

    /**
     * Get region for destination
     */
    private function getRegion($destination)
    {
        $regions = [
            'Hà Nội' => 'miền Bắc Việt Nam',
            'Hồ Chí Minh' => 'miền Nam Việt Nam',
            'Đà Nẵng' => 'miền Trung Việt Nam',
            'Hội An' => 'miền Trung Việt Nam',
            'Nha Trang' => 'miền Trung Việt Nam',
            'Phú Quốc' => 'miền Nam Việt Nam',
            'Sapa' => 'miền Bắc Việt Nam',
            'Hạ Long' => 'miền Bắc Việt Nam',
            'Đà Lạt' => 'miền Nam Việt Nam',
            'Cần Thơ' => 'miền Nam Việt Nam',
            'Huế' => 'miền Trung Việt Nam',
            'Phuket' => 'Thái Lan',
            'Bangkok' => 'Thái Lan',
            'Singapore' => 'Đông Nam Á',
            'Tokyo' => 'Nhật Bản',
            'Seoul' => 'Hàn Quốc',
        ];
        
        return $regions[$destination] ?? 'châu Á';
    }

    /**
     * Get mock coordinates for destination
     */
    private function getCoordinates($destination)
    {
        $coordinates = [
            'Hà Nội' => ['lat' => 21.0285, 'lng' => 105.8542],
            'Hồ Chí Minh' => ['lat' => 10.8231, 'lng' => 106.6297],
            'Đà Nẵng' => ['lat' => 16.0471, 'lng' => 108.2068],
            'Hội An' => ['lat' => 15.8801, 'lng' => 108.3380],
            'Nha Trang' => ['lat' => 12.2388, 'lng' => 109.1967],
            'Phú Quốc' => ['lat' => 10.2899, 'lng' => 103.9840],
            'Sapa' => ['lat' => 22.3380, 'lng' => 103.8442],
            'Hạ Long' => ['lat' => 20.9101, 'lng' => 107.1839],
            'Đà Lạt' => ['lat' => 11.9404, 'lng' => 108.4583],
            'Cần Thơ' => ['lat' => 10.0452, 'lng' => 105.7469],
            'Huế' => ['lat' => 16.4637, 'lng' => 107.5909],
            'Phuket' => ['lat' => 7.8804, 'lng' => 98.3923],
            'Bangkok' => ['lat' => 13.7563, 'lng' => 100.5018],
            'Singapore' => ['lat' => 1.3521, 'lng' => 103.8198],
            'Tokyo' => ['lat' => 35.6762, 'lng' => 139.6503],
            'Seoul' => ['lat' => 37.5665, 'lng' => 126.9780],
        ];
        
        return $coordinates[$destination] ?? ['lat' => 16.0471, 'lng' => 108.2068];
    }

    /**
     * Get travel-related image URL
     */
    private function getTravelImage($destination)
    {
        $imageKeywords = [
            'travel', 'vacation', 'landscape', 'beach', 'mountain', 'city', 'culture', 'food',
            'temple', 'nature', 'sunset', 'adventure', 'tourism', 'destination'
        ];
        
        $keyword = $imageKeywords[array_rand($imageKeywords)];
        return "https://picsum.photos/800/600?random=" . rand(1, 1000) . "&blur=0";
    }

    /**
     * Create view records for a news post
     * Fixed to only use existing user IDs
     */
    private function createViewRecords($news, $viewCount, $publishedAt, $existingUsers)
    {
        $faker = Faker::create();
        
        // Create 25-60% of actual view records
        $recordsToCreate = rand($viewCount * 0.25, $viewCount * 0.6);
        
        for ($i = 0; $i < $recordsToCreate; $i++) {
            $viewedAt = $faker->dateTimeBetween($publishedAt, 'now');
            
            // Device types distribution for travel content
            $deviceTypes = ['mobile' => 55, 'desktop' => 35, 'tablet' => 10];
            $deviceType = $faker->randomElement(
                array_merge(...array_map(
                    fn($device, $weight) => array_fill(0, $weight, $device),
                    array_keys($deviceTypes),
                    array_values($deviceTypes)
                ))
            );
            
            // Browser distribution
            $browsers = ['Chrome', 'Safari', 'Firefox', 'Edge', 'Samsung Internet'];
            $browser = $faker->randomElement($browsers);
            
            // Countries (travel blog readers)
            $countries = ['VN' => 70, 'US' => 8, 'JP' => 5, 'KR' => 4, 'SG' => 3, 'TH' => 3, 'AU' => 2, 'CA' => 2, 'GB' => 2, 'DE' => 1];
            $country = $faker->randomElement(
                array_merge(...array_map(
                    fn($country, $weight) => array_fill(0, $weight, $country),
                    array_keys($countries),
                    array_values($countries)
                ))
            );
            
            // Cities based on country
            $cities = [
                'VN' => ['Ho Chi Minh City', 'Hanoi', 'Da Nang', 'Can Tho', 'Hai Phong', 'Nha Trang', 'Hue', 'Da Lat'],
                'US' => ['New York', 'Los Angeles', 'San Francisco', 'Seattle', 'Miami'],
                'JP' => ['Tokyo', 'Osaka', 'Kyoto', 'Yokohama', 'Nagoya'],
                'KR' => ['Seoul', 'Busan', 'Incheon', 'Daegu'],
                'SG' => ['Singapore'],
                'TH' => ['Bangkok', 'Chiang Mai', 'Phuket', 'Pattaya'],
                'AU' => ['Sydney', 'Melbourne', 'Brisbane', 'Perth'],
                'CA' => ['Toronto', 'Vancouver', 'Montreal', 'Calgary'],
                'GB' => ['London', 'Manchester', 'Birmingham', 'Liverpool'],
                'DE' => ['Berlin', 'Munich', 'Hamburg', 'Frankfurt']
            ];
            $city = $faker->randomElement($cities[$country] ?? ['Unknown']);

            // FIX: Only use existing user IDs or null
            $userId = null;
            if (!empty($existingUsers) && $faker->boolean(25)) { // 25% chance of logged in user
                $userId = $faker->randomElement($existingUsers);
            }

            DB::table('news_views')->insert([
                'news_id' => $news->id,
                'user_id' => $userId, // Fixed: Only use existing user IDs or null
                'admin_id' => null,
                'ip_address' => $faker->ipv4,
                'user_agent' => $this->generateUserAgent($browser, $deviceType),
                'referer' => $faker->boolean(60) ? $this->generateReferer() : null,
                'country' => $country,
                'city' => $city,
                'device_type' => $deviceType,
                'browser' => $browser,
                'viewed_at' => $viewedAt,
                'created_at' => $viewedAt,
                'updated_at' => $viewedAt,
            ]);
        }
    }

    /**
     * Generate realistic user agent string
     */
    private function generateUserAgent($browser, $deviceType)
    {
        $userAgents = [
            'Chrome' => [
                'desktop' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'mobile' => 'Mozilla/5.0 (Linux; Android 10; SM-G975F) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Mobile Safari/537.36',
                'tablet' => 'Mozilla/5.0 (Linux; Android 10; SM-T870) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
            ],
            'Safari' => [
                'desktop' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Safari/605.1.15',
                'mobile' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1',
                'tablet' => 'Mozilla/5.0 (iPad; CPU OS 17_1 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.1 Mobile/15E148 Safari/604.1'
            ],
            'Firefox' => [
                'desktop' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/121.0',
                'mobile' => 'Mozilla/5.0 (Mobile; rv:109.0) Gecko/121.0 Firefox/121.0',
                'tablet' => 'Mozilla/5.0 (Android 10; Tablet; rv:109.0) Gecko/121.0 Firefox/121.0'
            ]
        ];

        return $userAgents[$browser][$deviceType] ?? $userAgents['Chrome']['desktop'];
    }

    /**
     * Generate realistic referer URLs for travel content
     */
    private function generateReferer()
    {
        $referers = [
            'https://www.google.com/search?q=du+lich',
            'https://www.facebook.com/',
            'https://www.instagram.com/',
            'https://www.youtube.com/',
            'https://foody.vn/',
            'https://www.tripadvisor.com/',
            'https://www.booking.com/',
            'https://www.agoda.com/',
            'https://tiki.vn/',
            'https://shopee.vn/',
            'https://vnexpress.net/',
            'https://dantri.com.vn/',
            'https://kenh14.vn/',
        ];
        
        return $referers[array_rand($referers)];
    }
}
