<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\PagoPrestamo;
use App\Models\Prestamo;
use App\Models\ProductoVale;
use App\Models\User;
use Illuminate\Database\Seeder;

class PrestamoSeeder extends Seeder
{
    public function run(): void
    {
        $distribuidor = User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']))
            ->first() ?? User::first();

        $cliente1 = Cliente::where('curp', 'HEVG850412MDFRRN01')->first() ?? Cliente::first();
        $cliente2 = Cliente::where('curp', 'CARF901125HNTNN02')->first();

        $vale1 = ProductoVale::where('activo', true)->first();
        $vale2 = ProductoVale::where('activo', true)->skip(1)->first() ?? $vale1;

        if ($cliente1 && $vale1) {
            $ref1 = "REF-PREVALE-20260805-101";
            $prestamo1 = Prestamo::updateOrCreate(
                ['referencia' => $ref1],
                [
                    'cliente_id' => $cliente1->id,
                    'producto_vale_id' => $vale1->id,
                    'tipo' => 'prevale',
                    'monto_prestamo' => $vale1->monto_prestamo,
                    'cuota_quincenal' => $vale1->cuota_quincenal,
                    'pagos_totales' => $vale1->plazo_quincenas,
                    'pagos_realizados' => 2,
                    'monto_total_pagar' => $vale1->monto_total_pagar,
                    'adeudo_pendiente' => max(0, $vale1->monto_total_pagar - ($vale1->cuota_quincenal * 2)),
                    'pagos_recibidos' => $vale1->cuota_quincenal * 2,
                    'multas' => 50.00,
                    'estado' => 'activo',
                    'activo' => true,
                    'created_by_user_id' => $distribuidor?->id,
                ]
            );

            // Registrar 2 abonos
            PagoPrestamo::updateOrCreate(
                ['folio_pago' => 'PAGO-20260801-01'],
                [
                    'prestamo_id' => $prestamo1->id,
                    'numero_quincena' => 1,
                    'monto_abonado' => $vale1->cuota_quincenal,
                    'monto_multa' => 0,
                    'metodo_pago' => 'Efectivo',
                    'observaciones' => 'Primer abono quincenal puntual.',
                    'registrado_por_user_id' => $distribuidor?->id,
                ]
            );

            PagoPrestamo::updateOrCreate(
                ['folio_pago' => 'PAGO-20260805-02'],
                [
                    'prestamo_id' => $prestamo1->id,
                    'numero_quincena' => 2,
                    'monto_abonado' => $vale1->cuota_quincenal,
                    'monto_multa' => 50.00,
                    'metodo_pago' => 'Transferencia',
                    'observaciones' => 'Abono quincena 2 con recargo por mora.',
                    'registrado_por_user_id' => $distribuidor?->id,
                ]
            );
        }

        if ($cliente2 && $vale2) {
            $ref2 = "REF-VALE-20260805-202";
            Prestamo::updateOrCreate(
                ['referencia' => $ref2],
                [
                    'cliente_id' => $cliente2->id,
                    'producto_vale_id' => $vale2->id,
                    'tipo' => 'vale',
                    'monto_prestamo' => $vale2->monto_prestamo,
                    'cuota_quincenal' => $vale2->cuota_quincenal,
                    'pagos_totales' => $vale2->plazo_quincenas,
                    'pagos_realizados' => 0,
                    'monto_total_pagar' => $vale2->monto_total_pagar,
                    'adeudo_pendiente' => $vale2->monto_total_pagar,
                    'pagos_recibidos' => 0,
                    'multas' => 0,
                    'estado' => 'activo',
                    'activo' => true,
                    'created_by_user_id' => $distribuidor?->id,
                ]
            );
        }
    }
}
