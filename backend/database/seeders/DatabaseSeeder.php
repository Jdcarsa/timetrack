<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin
        User::create([
            'name'        => 'Administrador',
            'email'       => 'admin@timetrack.com',
            'password'    => bcrypt('password'),
            'role'        => 'admin',
            'hourly_rate' => 0,
        ]);

        // Empleados de ejemplo
        User::create([
            'name'        => 'Juan Pérez',
            'email'       => 'juan@timetrack.com',
            'password'    => bcrypt('password'),
            'role'        => 'employee',
            'hourly_rate' => 15000,
        ]);

        User::create([
            'name'        => 'María López',
            'email'       => 'maria@timetrack.com',
            'password'    => bcrypt('password'),
            'role'        => 'employee',
            'hourly_rate' => 18000,
        ]);

        User::create([
            'name'        => 'Carlos Ruiz',
            'email'       => 'carlos@timetrack.com',
            'password'    => bcrypt('password'),
            'role'        => 'employee',
            'hourly_rate' => 12000,
        ]);
    }
}
