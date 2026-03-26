<?php

namespace Database\Seeders;

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
    }
}
