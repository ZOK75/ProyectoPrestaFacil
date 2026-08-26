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
        $coordinador = Rol::where('nombre', 'Coordinador')->first();
        $cajero = Rol::where('nombre', 'Cajero')->first();
        $verificador = Rol::where('nombre', 'Verificador')->first();
        $administrador = Rol::where('nombre', 'Administrador')->first();

        $sucursalNorte = Sucursal::where('nombre', 'Sucursal Norte')->first();

        $usuarios = [
            // Administrador del Sistema
            [
                'name' => 'Soporte Técnico',
                'email' => 'admin.sistema@prestafacil.com',
                'password' => Hash::make('N5f5p#342V8lYg5jaky};]'),
                'email_verified_at' => now(),
                'rol_id' => $administrador?->id,
                'sucursal_id' => null,
                'activo' => true,
            ],
            // Gerente General
            [
                'name' => 'Gerente General',
                'email' => 'gerente.general@prestafacil.com',
                'password' => Hash::make('x4_#opSX]1/<[>T/0Z4B(z'),
                'email_verified_at' => now(),
                'rol_id' => $gerenteGeneral?->id,
                'sucursal_id' => null,
                'activo' => true,
            ],
            // Gerente Norte
            [
                'name' => 'Gerente Norte',
                'email' => 'gerente.norte@prestafacil.com',
                'password' => Hash::make('mQ7<DMxL4exf6Q.p1K7-]l'),
                'email_verified_at' => now(),
                'rol_id' => $gerenteSucursal?->id,
                'sucursal_id' => $sucursalNorte?->id,
                'activo' => true,
            ],
            // Coordinador Norte
            [
                'name' => 'Coordinador Norte',
                'email' => 'coordinador.norte@prestafacil.com',
                'password' => Hash::make("Vkodo'Yo6Z(`u3y64yxi01"),
                'email_verified_at' => now(),
                'rol_id' => $coordinador?->id,
                'sucursal_id' => $sucursalNorte?->id,
                'activo' => true,
            ],
            // Cajero Norte
            [
                'name' => 'Cajero Norte',
                'email' => 'cajero.norte@prestafacil.com',
                'password' => Hash::make('\y$;8F6&+N%Vz2IB=siTr<'),
                'email_verified_at' => now(),
                'rol_id' => $cajero?->id,
                'sucursal_id' => $sucursalNorte?->id,
                'activo' => true,
            ],
            // Verificador Norte
            [
                'name' => 'Verificador Norte',
                'email' => 'verificador.norte@prestafacil.com',
                'password' => Hash::make('sF1i{?1yOx:P2jA(TdN0jq'),
                'email_verified_at' => now(),
                'rol_id' => $verificador?->id,
                'sucursal_id' => $sucursalNorte?->id,
                'activo' => true,
            ],
        ];

        foreach ($usuarios as $usuario) {
            User::updateOrCreate(['email' => $usuario['email']], $usuario);
        }
    }
}