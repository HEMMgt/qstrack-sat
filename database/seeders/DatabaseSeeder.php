<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@qstrack.test'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'role' => Role::Admin,
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            ['email' => 'operador@qstrack.test'],
            [
                'name' => 'Operador SAT',
                'password' => 'password',
                'role' => Role::Operador,
                'is_active' => true,
            ],
        );

        User::updateOrCreate(
            ['email' => 'auditor@qstrack.test'],
            [
                'name' => 'Auditor',
                'password' => 'password',
                'role' => Role::Auditor,
                'is_active' => true,
            ],
        );
    }
}
