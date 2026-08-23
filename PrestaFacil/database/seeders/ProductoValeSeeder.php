<?php

namespace Database\Seeders;

use App\Models\ProductoVale;
use Illuminate\Database\Seeder;

class ProductoValeSeeder extends Seeder
{
    public function run(): void
    {
        $vales = [
            [
                'clave' => 'VLT-3K-6Q',
                'nombre' => 'Vale Exprès $3,000 (6 Quincenas)',
                'monto_prestamo' => 3000.00,
                'costo_seguro' => 120.00,
                'comision_apertura' => 0.00,
                'plazo_quincenas' => 6,
                'tasa_interes_quincenal' => 2.50,
                'multa' => 150.00,
                'activo' => true,
                'desactivado_at' => null,
                'descripcion' => 'Vale de préstamo rápido por transferencia bancaria directa para montos iniciales.',
            ],
            [
                'clave' => 'VLT-5K-12Q',
                'nombre' => 'Vale Nómina Standard $5,000 (12 Quincenas)',
                'monto_prestamo' => 5000.00,
                'costo_seguro' => 200.00,
                'comision_apertura' => 0.00,
                'plazo_quincenas' => 12,
                'tasa_interes_quincenal' => 2.20,
                'multa' => 200.00,
                'activo' => true,
                'desactivado_at' => null,
                'descripcion' => 'Préstamo por transferencia ideal para compras o emergencias medianas con seguro de desempleo.',
            ],
            [
                'clave' => 'VLT-10K-24Q',
                'nombre' => 'Vale Plus $10,000 (24 Quincenas)',
                'monto_prestamo' => 10000.00,
                'costo_seguro' => 450.00,
                'comision_apertura' => 0.00,
                'plazo_quincenas' => 24,
                'tasa_interes_quincenal' => 1.95,
                'multa' => 300.00,
                'activo' => true,
                'desactivado_at' => null,
                'descripcion' => 'Vale de monto alto para empleados de nómina con plazo extendido a 1 año (24 quincenas).',
            ],
            [
                'clave' => 'VLT-20K-36Q',
                'nombre' => 'Vale Premium $20,000 (36 Quincenas)',
                'monto_prestamo' => 20000.00,
                'costo_seguro' => 850.00,
                'comision_apertura' => 0.00,
                'plazo_quincenas' => 36,
                'tasa_interes_quincenal' => 1.75,
                'multa' => 500.00,
                'activo' => false,
                'desactivado_at' => now()->subDays(2),
                'descripcion' => 'Vale especial sujeto a aprobación crediticia avanzada y seguro de vida completo.',
            ],
        ];

        foreach ($vales as $vale) {
            ProductoVale::updateOrCreate(['clave' => $vale['clave']], $vale);
        }
    }
}
