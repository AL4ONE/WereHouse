<?php

namespace Database\Seeders;

use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // === Production Accounts ===
        User::firstOrCreate(
            ['email' => "Admin@gmail.com"],
            [
                'name' => "Admin",
                'password' => Hash::make("password"),
                'role' => "Admin",
            ]
        );
        User::firstOrCreate(
            ['email' => "Petugas@gmail.com"],
            [
                'name' => "Petugas",
                'password' => Hash::make("password"),
                'role' => "Petugas",
            ]
        );
        User::firstOrCreate(
            ['email' => "Manajer@gmail.com"],
            [
                'name' => "Manajer",
                'password' => Hash::make("password"),
                'role' => "Manajer",
            ]
        );

        // === Training Accounts ===
        User::firstOrCreate(
            ['email' => "AdminLatihan@gmail.com"],
            [
                'name' => "Admin Latihan",
                'password' => Hash::make("password"),
                'role' => "Admin Latihan",
            ]
        );
        User::firstOrCreate(
            ['email' => "PetugasLatihan@gmail.com"],
            [
                'name' => "Petugas Latihan",
                'password' => Hash::make("password"),
                'role' => "Petugas Latihan",
            ]
        );
        User::firstOrCreate(
            ['email' => "ManajerLatihan@gmail.com"],
            [
                'name' => "Manajer Latihan",
                'password' => Hash::make("password"),
                'role' => "Manajer Latihan",
            ]
        );

        // === Default Training Company Profile ===
        CompanyProfile::firstOrCreate(
            ['is_training' => true],
            [
                'company_name' => 'PT LATIHAN JAYA',
                'company_address' => 'Jln. Contoh No. 1, Kota Latihan',
                'company_phone' => '021-0000000',
                'company_logo_initials' => 'LJ',
            ]
        );
    }
}
