<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->manager()->create([
            'name' => 'Manager User',
            'email' => 'manager@example.com',
        ]);

        User::factory()->employee()->create([
            'name' => 'Employee One',
            'email' => 'employee1@example.com',
        ]);

        User::factory()->employee()->create([
            'name' => 'Employee Two',
            'email' => 'employee2@example.com',
        ]);
    }
}
