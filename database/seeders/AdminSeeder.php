<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::unguarded(function () {
            return User::updateOrCreate(
                ['email' => 'admin@anahaw.test'],
                [
                    'name' => 'admin',
                    'password' => Hash::make('admin123'),
                    'role' => 'admin',
                ]
            );
        });

        $admin->role = 'admin';
        $admin->save();

        Booking::whereNull('user_id')->update(['user_id' => $admin->id]);
    }
}

