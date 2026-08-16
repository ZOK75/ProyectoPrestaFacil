<?php

namespace Database\Seeders;

use App\Models\Configuracion;
use Illuminate\Database\Seeder;

class ConfiguracionCajeroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $config = Configuracion::first();

        if ($config) {
            $config->update([
                'porcentaje_regla_prevale' => 50.00,
                'tolerancia_regla_prevale' => 500.00,
                'valor_punto' => 10.00,
                'puntos_por_relacion' => 5,
                'penalizacion_morosidad_puntos' => 20.00,
                'multiplo_canje_puntos' => 20,
                'multiplo_producto' => 100,
                'strikes_morosidad' => 3,
                'fecha_pago_2' => now()->addDays(30)->startOfDay(),
            ]);
        } else {
            Configuracion::actual();
        }
    }
}
