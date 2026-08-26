<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $administrador = Rol::where('nombre', 'Administrador')->first();

        User::updateOrCreate(
            ['email' => 'admin.sistema@prestafacil.com'],
            [
                'name' => 'Soporte Técnico',
                'password' => Hash::make('N5f5p#342V8lYg5jaky};]'),
                'email_verified_at' => now(),
                'rol_id' => $administrador?->id,
                'sucursal_id' => null,
                'activo' => true,
            ]
        );
    }
}