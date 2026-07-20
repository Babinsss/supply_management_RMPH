<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder; // <--- This was the missing piece!
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SupplyStaffSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $staff = [
            ['name' => 'Marline Erandio', 'email' => 'merandio@rmph.gov.ph'],
            ['name' => 'Raphael Martin Asis', 'email' => 'rasis@rmph.gov.ph'],
            ['name' => 'Lady Ortalez Luces', 'email' => 'lluces@rmph.gov.ph'],
            ['name' => 'Syrone Armada', 'email' => 'sarmada@rmph.gov.ph'],
            ['name' => 'Michael Peñaroyo', 'email' => 'mpenaroyo@rmph.gov.ph'],
        ];

        foreach ($staff as $person) {
            User::create([
                'name' => $person['name'],
                'email' => $person['email'],
                'password' => Hash::make('password123'),
                'role' => 'admin', // Grants them access to the main Supply Dashboard
            ]);
        }
    }
}