<?php

namespace Database\Seeders;

use App\Models\User;
use App\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        abort_if(app()->isProduction(), 403, 'Demo accounts cannot be seeded in production.');

        User::updateOrCreate(
            ['email' => 'admin@mocktest.test'],
            ['name' => 'Demo Administrator', 'role' => UserRole::Admin, 'password' => Hash::make('Admin123!')],
        );

        User::updateOrCreate(
            ['email' => 'student@mocktest.test'],
            [
                'name' => 'Demo Student',
                'role' => UserRole::Student,
                'password' => Hash::make('Student123!'),
                'phone' => '+977 9800000000',
                'bio' => 'A demo student account for local Phase 2 testing.',
            ],
        );

        User::updateOrCreate(
            ['email' => 'instructor@mocktest.test'],
            ['name' => 'Demo Instructor', 'role' => UserRole::Instructor, 'password' => Hash::make('Instructor123!')],
        );
    }
}
