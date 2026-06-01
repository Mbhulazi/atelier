<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('email', 'admin@atelier.com')->exists()) {
            return;
        }

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@atelier.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'slug' => 'admin-' . time(),
        ]);

        $admin->assignRole('admin');

        $painter = User::create([
            'name' => 'Maria Gonzalez',
            'email' => 'maria@atelier.com',
            'password' => Hash::make('password'),
            'role' => 'painter',
            'bio' => 'Contemporary oil painter inspired by the California coast.',
            'slug' => 'maria-gonzalez',
        ]);

        $painter->assignRole('painter');

        $collector = User::create([
            'name' => 'James Wilson',
            'email' => 'james@atelier.com',
            'password' => Hash::make('password'),
            'role' => 'collector',
            'slug' => 'james-wilson',
        ]);

        $collector->assignRole('collector');
    }
}
