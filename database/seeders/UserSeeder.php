<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Your ICT Admin Account
        User::create([
            'name' => 'Vincent Barrientos',
            'email' => 'admin@rmph.gov.ph',
            'password' => Hash::make('password123'),
            'role' => 'admin',
        ]);

        // The QMO Approver Account
        User::create([
            'name' => 'Jhoanna Cruz-am',
            'email' => 'qmo@rmph.gov.ph',
            'password' => Hash::make('password123'),
            'role' => 'approver',
        ]);
    }
}