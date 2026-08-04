<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $gerenteGeneral = Rol::where('nombre', 'Gerente General')->first();
        $gerenteSucursal = Rol::where('nombre', 'Gerente de Sucursal')->first();

        $sucursalCentro = Sucursal::where('nombre', 'Sucursal Centro')->first();

        User::updateOrCreate(
            ['email' => 'leomendez@misvales.com'],
            [
                'name' => 'Leonardo Mendez',
                'password' => Hash::make('ContraseñaMI$vAL3S1234'),
                'email_verified_at' => now(),
                'rol_id' => $gerenteGeneral?->id,
                'sucursal_id' => null,
                'activo' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'antoniolopez@misvales.com'],
            [
                'name' => 'Antonio Lopez',
                'password' => Hash::make('ContraseñaMI$vAL3S1234'),
                'email_verified_at' => now(),
                'rol_id' => $gerenteSucursal?->id,
                'sucursal_id' => $sucursalCentro?->id,
                'activo' => true,
            ]
        );
    }
}
