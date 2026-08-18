<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\PagoPrestamo;
use App\Models\Prestamo;
use App\Models\ProductoVale;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Database\Seeder;

class PrestamoSeeder extends Seeder
{
    public function run(): void
    {
        $rolDist = Rol::whereIn('nombre', ['Distribuidor', 'Distribuidora'])->first();
        $sucursal = Sucursal::first();

        $distribuidor = User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']))
            ->first();

        if (!$distribuidor) {
            $distribuidor = User::firstOrCreate(
                ['email' => 'distribuidora@prestafacil.com'],
                [
                    'name' => 'Distribuidora Principal',
                    'password' => bcrypt('password123'),
                    'rol_id' => $rolDist?->id,
                    'sucursal_id' => $sucursal?->id,
                    'referencia_pago_distribuidor' => 'REF-DIST-00000001',
                    'limite_credito' => 50000.00,
                    'categoria_distribuidor' => 'plata',
                    'activo' => true,
                ]
            );
        }

        // Obtener o crear clientes asociados a este distribuidor
        $cliente1 = Cliente::firstOrCreate(
            ['curp' => 'HEVG850412MDFRRN01'],
            [
                'nombre' => 'Guadalupe Hernández Vázquez',
                'rfc' => 'HEVG850412AB1',
                'fecha_nacimiento' => '1985-04-12',
                'lugar_nacimiento' => 'Ciudad de México',
                'calle' => 'Av. Insurgentes Sur #450 Int 302',
                'colonia' => 'Roma Sur',
                'codigo_postal' => '06760',
                'ciudad' => 'Cuauhtémoc',
                'estado' => 'CDMX',
                'activo' => true,
                'created_by_user_id' => $distribuidor->id,
            ]
        );

        $cliente2 = Cliente::firstOrCreate(
            ['curp' => 'CARF901125HNTNN02'],
            [
                'nombre' => 'Fernando Castro Ruiz',
                'rfc' => 'CARF901125CD2',
                'fecha_nacimiento' => '1990-11-25',
                'lugar_nacimiento' => 'Monterrey, Nuevo León',
                'calle' => 'Calle Benito Juárez #120',
                'colonia' => 'Centro',
                'codigo_postal' => '64000',
                'ciudad' => 'Monterrey',
                'estado' => 'Nuevo León',
                'activo' => true,
                'created_by_user_id' => $distribuidor->id,
            ]
        );

        // Asegurar que pertenezcan al distribuidor
        $cliente1->update(['created_by_user_id' => $distribuidor->id]);
        $cliente2->update(['created_by_user_id' => $distribuidor->id]);

        // Obtener o crear catálogo de producto vales
        $vale1 = ProductoVale::firstOrCreate(
            ['clave' => 'VALE-3000'],
            [
                'nombre' => 'Vale Personal $3,000',
                'monto_prestamo' => 3000.00,
                'costo_seguro' => 100.00,
                'comision_apertura' => 0.00,
                'tasa_interes_quincenal' => 2.50,
                'plazo_quincenas' => 14,
                'activo' => true,
            ]
        );

        $vale2 = ProductoVale::firstOrCreate(
            ['clave' => 'VALE-5000'],
            [
                'nombre' => 'Vale Plus $5,000',
                'monto_prestamo' => 5000.00,
                'costo_seguro' => 150.00,
                'comision_apertura' => 0.00,
                'tasa_interes_quincenal' => 2.50,
                'plazo_quincenas' => 14,
                'activo' => true,
            ]
        );

        // 1. Préstamo Activo Entregado (con 2 abonos registrados)
        $ref1 = "REF-PREVALE-20260805-101";
        $cuota1 = floatval($vale1->cuota_quincenal);
        $totalPagar1 = floatval($vale1->monto_total_pagar);
        $abonos1 = $cuota1 * 2;
        $adeudo1 = max(0, $totalPagar1 - $abonos1);

        $prestamo1 = Prestamo::updateOrCreate(
            ['referencia' => $ref1],
            [
                'cliente_id' => $cliente1->id,
                'producto_vale_id' => $vale1->id,
                'tipo' => 'prevale',
                'monto_prestamo' => $vale1->monto_prestamo,
                'cuota_quincenal' => $cuota1,
                'pagos_totales' => $vale1->plazo_quincenas,
                'pagos_realizados' => 2,
                'monto_total_pagar' => $totalPagar1,
                'adeudo_pendiente' => $adeudo1,
                'pagos_recibidos' => $abonos1,
                'multas' => 0.00,
                'estado' => 'activo',
                'estado_entrega' => 'entregado',
                'entregado_at' => now()->subDays(30),
                'monto_depositado' => $vale1->monto_prestamo,
                'activo' => true,
                'created_by_user_id' => $distribuidor->id,
            ]
        );

        // Registrar 2 abonos para préstamo 1
        PagoPrestamo::updateOrCreate(
            ['folio_pago' => 'PAGO-20260801-01'],
            [
                'prestamo_id' => $prestamo1->id,
                'numero_quincena' => 1,
                'monto_abonado' => $cuota1,
                'monto_multa' => 0,
                'metodo_pago' => 'Efectivo',
                'observaciones' => 'Primer abono quincenal puntual.',
                'registrado_por_user_id' => $distribuidor->id,
            ]
        );

        PagoPrestamo::updateOrCreate(
            ['folio_pago' => 'PAGO-20260805-02'],
            [
                'prestamo_id' => $prestamo1->id,
                'numero_quincena' => 2,
                'monto_abonado' => $cuota1,
                'monto_multa' => 0.00,
                'metodo_pago' => 'Transferencia',
                'observaciones' => 'Abono quincena 2.',
                'registrado_por_user_id' => $distribuidor->id,
            ]
        );

        // 2. Préstamo Activo Entregado (sin abonos)
        $ref2 = "REF-VALE-20260805-202";
        $cuota2 = floatval($vale2->cuota_quincenal);
        $totalPagar2 = floatval($vale2->monto_total_pagar);

        Prestamo::updateOrCreate(
            ['referencia' => $ref2],
            [
                'cliente_id' => $cliente2->id,
                'producto_vale_id' => $vale2->id,
                'tipo' => 'vale',
                'monto_prestamo' => $vale2->monto_prestamo,
                'cuota_quincenal' => $cuota2,
                'pagos_totales' => $vale2->plazo_quincenas,
                'pagos_realizados' => 0,
                'monto_total_pagar' => $totalPagar2,
                'adeudo_pendiente' => $totalPagar2,
                'pagos_recibidos' => 0.00,
                'multas' => 0.00,
                'estado' => 'activo',
                'estado_entrega' => 'entregado',
                'entregado_at' => now()->subDays(15),
                'monto_depositado' => $vale2->monto_prestamo,
                'activo' => true,
                'created_by_user_id' => $distribuidor->id,
            ]
        );

        // 3. Préstamo Pendiente de Entrega en Caja
        $ref3 = "REF-PREVALE-PENDIENTE-01";
        Prestamo::updateOrCreate(
            ['referencia' => $ref3],
            [
                'cliente_id' => $cliente1->id,
                'producto_vale_id' => $vale1->id,
                'tipo' => 'prevale',
                'monto_prestamo' => $vale1->monto_prestamo,
                'cuota_quincenal' => $cuota1,
                'pagos_totales' => $vale1->plazo_quincenas,
                'pagos_realizados' => 0,
                'monto_total_pagar' => $totalPagar1,
                'adeudo_pendiente' => $totalPagar1,
                'pagos_recibidos' => 0.00,
                'multas' => 0.00,
                'estado' => 'pendiente',
                'estado_entrega' => 'pendiente',
                'activo' => true,
                'created_by_user_id' => $distribuidor->id,
            ]
        );
    }
}
