<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Faker\Factory as Faker;
use App\Models\User;
use App\Models\Tour;
use App\Models\Booking;
use App\Models\Price;
use App\Models\TourAvailability;
use App\Models\Voucher;
use App\Models\Payment;
use Carbon\Carbon;

class BookingsTableSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create();

        // Đảm bảo có dữ liệu liên quan
        // Users
        if (User::count() == 0) {
            $this->command->info('Không có User. Tạo 5 User mẫu.');
            for ($i = 0; $i < 5; $i++) {
                User::create([
                    'name' => $faker->name,
                    'email' => $faker->unique()->safeEmail,
                    'password' => bcrypt('password'),
                ]);
            }
        }

        // Tours
        if (Tour::count() == 0) {
            $this->command->info('Không có Tour. Vui lòng seed Tour trước.');
            return;
        }

        // Tour Availabilities
        if (TourAvailability::count() == 0) {
            $this->command->info('Không có TourAvailability. Tạo dữ liệu mẫu.');
            $tours = Tour::all();
            foreach ($tours as $tour) {
                TourAvailability::create([
                    'tour_id' => $tour->id,
                    'date' => $faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
                    'max_guests' => $faker->numberBetween(20, 50),
                    'available_slots' => $faker->numberBetween(10, 50),
                    'is_active' => true,
                ]);
            }
        }

        // Prices
        if (Price::count() == 0) {
            $this->command->info('Không có Price. Tạo dữ liệu mẫu.');
            $tours = Tour::all();
            foreach ($tours as $tour) {
                Price::create([
                    'tour_id' => $tour->id,
                    'date' => $faker->dateTimeBetween('now', '+1 month')->format('Y-m-d'),
                    'price' => $tour->price ?? $faker->randomFloat(2, 500000, 2000000),
                ]);
            }
        }

        // Vouchers
        if (Voucher::count() == 0) {
            $this->command->info('Không có Voucher. Tạo 2 Voucher mẫu.');
            $tours = Tour::all()->pluck('id')->toArray();
            Voucher::create([
                'code' => 'SUMMER2025',
                'discount_percentage' => 10,
                'start_date' => now(),
                'end_date' => now()->addMonths(6),
                'usage_limit' => 100,
                'applicable_tour_ids' => json_encode($faker->randomElements($tours, 2)),
            ]);
            Voucher::create([
                'code' => 'WINTER2025',
                'discount_percentage' => 15,
                'start_date' => now(),
                'end_date' => now()->addMonths(3),
                'usage_limit' => 50,
                'applicable_tour_ids' => json_encode($faker->randomElements($tours, 1)),
            ]);
        }

        // Lấy dữ liệu
        $users = User::all();
        $tours = Tour::all();
        $availabilities = TourAvailability::all();
        $vouchers = Voucher::all();

        if ($availabilities->isEmpty()) {
            $this->command->info('Không có TourAvailability hợp lệ. Vui lòng seed TourAvailability trước.');
            return;
        }

        foreach ($users as $user) {
            for ($i = 0; $i < 5; $i++) {
                $tour = $faker->randomElement($tours);
                $availability = $faker->randomElement($availabilities->where('tour_id', $tour->id));
                
                if (!$availability) {
                    $this->command->info("Không tìm thấy TourAvailability cho tour {$tour->id}. Bỏ qua.");
                    continue;
                }

                $startDate = Carbon::parse($availability->date);
                $days = $tour->days ?? 1;
                $endDate = $days === 0 ? $startDate : $startDate->copy()->addDays($days);

                $adults = $faker->numberBetween(1, 4);
                $children = $faker->numberBetween(0, 3);

                // Lấy giá
                $price = Price::where('tour_id', $tour->id)
                    ->where('date', '<=', $startDate->format('Y-m-d'))
                    ->orderBy('date', 'desc')
                    ->first()?->price ?? $tour->price ?? 1000000;

                // Áp dụng voucher (30% cơ hội)
                $voucher = $faker->optional(0.3)->randomElement($vouchers);
                $basePrice = $price * ($adults + $children * 0.5);
                $discount = $voucher ? $basePrice * ($voucher->discount_percentage / 100) : 0;
                $totalPrice = max(0, $basePrice - $discount);

                // Kiểm tra tính khả dụng
                if ($availability->available_slots < ($adults + $children)) {
                    $this->command->info("Không đủ chỗ cho tour {$tour->id} vào ngày {$startDate->format('Y-m-d')}. Bỏ qua.");
                    continue;
                }

                // Tạo Payment
                $payment = Payment::create([
                    'user_id' => $user->id,
                    'amount' => $totalPrice,
                    'method' => $faker->randomElement(['credit_card', 'bank_transfer', 'paypal', 'cash']),
                    'status' => $faker->randomElement(['pending', 'completed', 'failed']),
                    'transaction_id' => 'TXN_' . $faker->uuid,
                ]);

                // Tạo Booking
                Booking::create([
                    'user_id' => $user->id,
                    'bookable_id' => $tour->id,
                    'bookable_type' => 'App\\Models\\Tour',
                    'start_date' => $startDate->format('Y-m-d'),
                    'end_date' => $endDate->format('Y-m-d'),
                    'number_of_guests_adults' => $adults,
                    'number_of_children' => $children,
                    'total_price' => $totalPrice,
                    'status' => $faker->randomElement(['pending', 'confirmed', 'cancelled']),
                    'voucher_id' => $voucher?->id,
                    'special_requests' => $faker->optional(0.5)->sentence(),
                    'contact_phone' => $faker->optional(0.8)->phoneNumber(),
                    'payment_id' => $payment->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}