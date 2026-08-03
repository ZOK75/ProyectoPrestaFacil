<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['nombre' => 'Gerente General'],
            ['nombre' => 'Gerente de Sucursal'],
            ['nombre' => 'Distribuidor'],
            ['nombre' => 'Cajero'],
            ['nombre' => 'Coordinador'],
            ['nombre' => 'verificador'],
            ['nombre' => 'Administrador'],
        ];

        foreach ($roles as $rol) {
            Rol::updateOrCreate(['nombre' => $rol['nombre']], $rol);
        }
    }
}
