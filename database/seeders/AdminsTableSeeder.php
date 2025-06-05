<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\Admin;

class AdminsTableSeeder extends Seeder
{
    public function run()
    {
        $avatarUrl = 'https://i.pravatar.cc/300?u=' . uniqid();
        Admin::create([
            'username' => 'superadmin',
            'email' => 'travelbooking@hongducct.id.vn',
            'password' => Hash::make('superadmin'),
            'first_name' => 'Super',
            'last_name' => 'Admin',
            'phone_number' => '0799076901',
            'avatar' => $avatarUrl,
            'admin_status' => 'active',
        ]);
    }
}
