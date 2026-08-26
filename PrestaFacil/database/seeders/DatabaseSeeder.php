<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use App\Models\Rol;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear Roles si no existen
        $roles = [
            'Gerente General',
            'Gerente de Sucursal',
            'Coordinador',
            'Distribuidor',
            'Cajero',
            'Verificador',
            'Administrador',
        ];

        foreach ($roles as $nombreRol) {
            Rol::firstOrCreate(['nombre' => $nombreRol]);
        }

        // 2. Crear Sucursales iniciales
        $sucursales = [
            'Sucursal Centro',
            'Sucursal Norte',
            'Sucursal Sur',
        ];

        foreach ($sucursales as $nombreSucursal) {
            Sucursal::firstOrCreate(['nombre' => $nombreSucursal], ['activo' => true]);
        }

        // 3. Crear Configuración General si no existe
        if (!Configuracion::exists()) {
            Configuracion::create([
                'dia_corte' => 15,
                'hora_corte' => '20:00',
                'dia_limite_pago' => 18,
                'hora_limite_pago' => '20:00',
                'multa_adeudo' => 200.00,
                'comision_cobre' => 5.00,
                'comision_plata' => 8.00,
                'comision_oro' => 12.00,
                'monto_base_puntos' => 1000.00,
                'puntos_por_monto_base' => 1,
                'valor_punto' => 1.00,
                'porcentaje_regla_prevale' => 80.00,
                'tolerancia_regla_prevale' => 5.00,
                'puntos_por_relacion' => 10,
                'penalizacion_morosidad_puntos' => 5,
                'multiplo_canje_puntos' => 100,
                'multiplo_producto' => 500,
                'strikes_morosidad' => 3,
            ]);
        }

        // 4. Ejecutar UserSeeder (Administrador)
        $this->call([
            UserSeeder::class,
        ]);
    }
}
