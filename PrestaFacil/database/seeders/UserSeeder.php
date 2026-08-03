<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $gerenteGeneral = Rol::where('nombre', 'Gerente General')->first();
        $gerenteSucursal = Rol::where('nombre', 'Gerente de Sucursal')->first();
        $distribuidor = Rol::where('nombre', 'Distribuidor')->first();
        $cajero = Rol::where('nombre', 'Cajero')->first();
        $verificador = Rol::where('nombre', 'Verificador')->first();
        $administrador = Rol::where('nombre', 'Administrador')->first();

        $sucursalCentro = Sucursal::where('nombre', 'Sucursal Centro')->first();
        $sucursalNorte = Sucursal::where('nombre', 'Sucursal Norte')->first();
        $sucursalSur = Sucursal::where('nombre', 'Sucursal Sur')->first();

        $adminGeneral = User::updateOrCreate(
            ['email' => 'gerente.general@prestafacil.com'],
            [
                'name' => 'Admin General',
                'password' => Hash::make('password'),
                'rol_id' => $gerenteGeneral?->id,
                'sucursal_id' => null,
                'activo' => true,
            ]
        );

        $usuarios = [
            [
                'name' => 'Carlos López',
                'email' => 'gerente.centro@prestafacil.com',
                'password' => Hash::make('password'),
                'rol_id' => $gerenteSucursal?->id,
                'sucursal_id' => $sucursalCentro?->id,
                'activo' => true,
            ],
            [
                'name' => 'María García',
                'email' => 'gerente.norte@prestafacil.com',
                'password' => Hash::make('password'),
                'rol_id' => $gerenteSucursal?->id,
                'sucursal_id' => $sucursalNorte?->id,
                'activo' => true,
            ],
            [
                'name' => 'Ana Martínez',
                'email' => 'distribuidor.centro@prestafacil.com',
                'password' => Hash::make('password'),
                'rol_id' => $distribuidor?->id,
                'sucursal_id' => $sucursalCentro?->id,
                'activo' => true,
            ],
            [
                'name' => 'Pedro Sánchez',
                'email' => 'cajero.norte@prestafacil.com',
                'password' => Hash::make('password'),
                'rol_id' => $cajero?->id,
                'sucursal_id' => $sucursalNorte?->id,
                'activo' => true,
            ],
            [
                'name' => 'Luis Ramírez',
                'email' => 'verificador.sur@prestafacil.com',
                'password' => Hash::make('password'),
                'rol_id' => $verificador?->id,
                'sucursal_id' => $sucursalSur?->id,
                'activo' => true,
            ],
            [
                'name' => 'Roberto Gómez (Baja)',
                'email' => 'roberto.inactivo@prestafacil.com',
                'password' => Hash::make('password'),
                'rol_id' => $distribuidor?->id,
                'sucursal_id' => $sucursalCentro?->id,
                'activo' => false,
                'desactivado_at' => now()->subDays(3),
                'desactivado_by_user_id' => $adminGeneral->id,
            ],
        ];

        foreach ($usuarios as $usuario) {
            User::updateOrCreate(['email' => $usuario['email']], $usuario);
        }
    }
}
