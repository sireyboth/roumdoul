<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@roumdoul.shop'],
            ['name' => 'ROUMDOUL Admin', 'password' => 'Roumdoul@2026', 'role' => 'admin']
        );

        $this->call(CatalogSeeder::class);
    }
}
