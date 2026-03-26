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

        User::create([
            'name' => "Admin",
            'email' => "Admin@gmail.com",
            'password' => Hash::make("password"),
            'role' => "Admin",
        ]);
        User::create([
            'name' => "Petugas",
            'email' => "Petugas@gmail.com",
            'password' => Hash::make("password"),
            'role' => "Petugas",
        ]);
        User::create([
            'name' => "Manajer",
            'email' => "Manajer@gmail.com",
            'password' => Hash::make("password"),
            'role' => "Manajer",
        ]);
    }
}
