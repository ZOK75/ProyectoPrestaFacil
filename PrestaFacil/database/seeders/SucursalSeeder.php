<?php

namespace Database\Seeders;

use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class SucursalSeeder extends Seeder
{
    public function run(): void
    {
        $sucursales = [
            [
                'nombre' => 'Sucursal Centro',
                'direccion' => 'Av. Principal #100, Col. Centro',
                'telefono' => '555-100-0001',
                'activo' => true,
            ],
            [
                'nombre' => 'Sucursal Norte',
                'direccion' => 'Blvd. Norte #250, Col. Industrial',
                'telefono' => '555-200-0002',
                'activo' => true,
            ],
            [
                'nombre' => 'Sucursal Sur',
                'direccion' => 'Calle Sur #80, Col. Jardines',
                'telefono' => '555-300-0003',
                'activo' => true,
            ],
        ];

        foreach ($sucursales as $sucursal) {
            Sucursal::updateOrCreate(['nombre' => $sucursal['nombre']], $sucursal);
        }
    }
}
