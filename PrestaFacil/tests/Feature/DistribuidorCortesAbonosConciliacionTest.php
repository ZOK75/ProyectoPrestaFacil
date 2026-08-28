<?php

namespace Tests\Feature;

use App\Models\Configuracion;
use App\Models\NotificacionCajero;
use App\Models\PagoPrestamo;
use App\Models\Prestamo;
use App\Models\ProductoVale;
use App\Models\RelacionCobranza;
use App\Models\Rol;
use App\Models\SolicitudAutorizacion;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\CorteCobranzaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DistribuidorCortesAbonosConciliacionTest extends TestCase
{
    use RefreshDatabase;

    private $rolDistribuidor;
    private $rolCajero;
    private $rolCoordinador;
    private $sucursal;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-08-25 12:00:00'));

        $this->rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $this->rolCajero = Rol::firstOrCreate(['nombre' => 'Cajero']);
        $this->rolCoordinador = Rol::firstOrCreate(['nombre' => 'Coordinador']);
        $this->sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Central'], ['activo' => true]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_new_distribuidor_ignores_past_cuts_and_zero_balance_is_taken_as_paid()
    {
        // 1. Corte configurado en el pasado (hace 5 días)
        $diaPasado = now()->subDays(5)->day;
        $config = Configuracion::firstOrCreate([], [
            'dias_limite_pago' => 5,
            'multa_adeudo' => 350.00,
        ]);
        $config->update([
            'dia_corte' => $diaPasado,
            'hora_corte' => '10:00:00',
            'dia_limite_pago' => now()->subDays(2)->day,
            'hora_limite_pago' => '10:00:00',
            'multa_adeudo' => 350.00,
        ]);

        // Distribuidor creado HOY (después del corte)
        $nuevoDistribuidor = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'created_at' => now(),
            'activo' => true,
            'multas' => 0.00,
        ]);

        $service = app(CorteCobranzaService::class);
        $service->verificarYProcesarCortesYVencimientos();

        // El nuevo distribuidor NO debe tener multas ni notificaciones de adeudo por el corte anterior a su creación
        $nuevoDistribuidor->refresh();
        $this->assertEquals(0.00, $nuevoDistribuidor->multas);
        $this->assertDatabaseMissing('relaciones_cobranza', [
            'distribuidora_id' => $nuevoDistribuidor->id,
            'estado_pago' => 'pendiente',
        ]);
        $this->assertDatabaseMissing('notificaciones_cajero', [
            'user_id' => $nuevoDistribuidor->id,
            'tipo' => 'multa_adeudo_aplicada',
        ]);

        // 2. Ahora simulamos un corte que ocurre después del alta del distribuidor, con saldo 0.00
        $corteHoy = now()->subMinutes(10);
        $limiteFuturo = now()->addDays(3);
        $config->update([
            'dia_corte' => $corteHoy->day,
            'hora_corte' => $corteHoy->format('H:i:s'),
            'dia_limite_pago' => $limiteFuturo->day,
            'hora_limite_pago' => $limiteFuturo->format('H:i:s'),
        ]);

        $service->verificarYProcesarCortesYVencimientos();

        // Debe registrar la relación como pagada / al corriente automáticamente
        $this->assertDatabaseHas('relaciones_cobranza', [
            'distribuidora_id' => $nuevoDistribuidor->id,
            'adeudo_pendiente' => 0.00,
            'estado_pago' => 'pago_a_tiempo',
        ]);
    }

    public function test_multas_are_applied_per_distribuidora_on_past_due_date()
    {
        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'created_at' => now()->subDays(10),
            'activo' => true,
            'multas' => 0.00,
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Cliente Con Adeudo',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1990-01-01',
            'lugar_nacimiento' => 'Torreón',
            'calle' => 'Calle 1',
            'colonia' => 'Centro',
            'codigo_postal' => '27000',
            'ciudad' => 'Torreón',
            'estado' => 'Coahuila',
            'activo' => true,
            'created_by_user_id' => $distribuidora->id,
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-3000-' . uniqid(),
            'nombre' => 'Vale $3,000',
            'monto_prestamo' => 3000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 14,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-TEST-' . uniqid(),
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 3000.00,
            'cuota_quincenal' => 350.00,
            'monto_total_pagar' => 4900.00,
            'pagos_totales' => 14,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 3000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        $config = Configuracion::firstOrCreate([], [
            'dias_limite_pago' => 5,
            'multa_adeudo' => 300.00,
        ]);
        $cortePasado = now()->subHours(2);
        $limitePasado = now()->subHour();
        $config->update([
            'dia_corte' => $cortePasado->day,
            'hora_corte' => $cortePasado->format('H:i:s'),
            'dia_limite_pago' => $limitePasado->day,
            'hora_limite_pago' => $limitePasado->format('H:i:s'),
            'multa_adeudo' => 300.00,
        ]);

        $service = app(CorteCobranzaService::class);
        $service->verificarYProcesarCortesYVencimientos();

        // La multa debe quedar registrada en la distribuidora
        $distribuidora->refresh();
        $this->assertEquals(300.00, $distribuidora->multas);
        $this->assertEquals(3300.00, $distribuidora->totalAdeudoGlobal());

        $this->assertDatabaseHas('relaciones_cobranza', [
            'distribuidora_id' => $distribuidora->id,
            'multa_aplicada' => 300.00,
        ]);
    }

    public function test_cashier_registers_abono_by_distribuidora_with_reference_verification()
    {
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'referencia_pago_distribuidor' => 'REF-DIST-00000099',
            'activo' => true,
            'multas' => 100.00,
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Cliente Pagador',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1992-02-02',
            'lugar_nacimiento' => 'Mérida',
            'calle' => 'Calle 2',
            'colonia' => 'Centro',
            'codigo_postal' => '97000',
            'ciudad' => 'Mérida',
            'estado' => 'Yucatán',
            'activo' => true,
            'created_by_user_id' => $distribuidora->id,
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-1000-' . uniqid(),
            'nombre' => 'Vale $1,000',
            'monto_prestamo' => 1000.00,
            'costo_seguro' => 50.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 10,
            'multa' => 100.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-PAG-' . uniqid(),
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 1000.00,
            'cuota_quincenal' => 125.00,
            'monto_total_pagar' => 1250.00,
            'pagos_totales' => 10,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 1000.00,
            'multas' => 100.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        // 1. Intento con referencia errónea -> Rechazar
        $responseError = $this->actingAs($cajero)->post(route('cajero.abonos.distribuidora.store', $distribuidora), [
            'referencia_pago' => 'REF-DIST-INCORRECTA',
            'monto_abonado' => 500.00,
            'metodo_pago' => 'transferencia',
        ]);
        $responseError->assertSessionHasErrors('referencia_pago');

        // 2. Abono con referencia correcta de $600 ($100 a multas + $500 al préstamo)
        $responseSuccess = $this->actingAs($cajero)->post(route('cajero.abonos.distribuidora.store', $distribuidora), [
            'referencia_pago' => 'REF-DIST-00000099',
            'monto_abonado' => 600.00,
            'metodo_pago' => 'transferencia',
        ]);
        $responseSuccess->assertSessionHasNoErrors();

        $distribuidora->refresh();
        $this->assertEquals(0.00, $distribuidora->multas); // Multas liquidadas

        $prestamo->refresh();
        $this->assertEquals(500.00, $prestamo->adeudo_pendiente); // 1000 - 500
        $this->assertEquals(500.00, $prestamo->pagos_recibidos);
        $this->assertEquals(1, $prestamo->pagos_realizados);
    }

    public function test_conciliacion_flow_with_corrected_reference_and_audit_timestamp()
    {
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $coordinador = User::factory()->create([
            'rol_id' => $this->rolCoordinador->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        // Cajero crea solicitud de conciliación
        $responseSolicitar = $this->actingAs($cajero)->post(route('cajero.conciliaciones.store'), [
            'distribuidora_id' => $distribuidora->id,
            'referencia_original' => 'REF-ERRONEA-123',
            'referencia_conciliacion' => 'REF-CORRECTA-456',
            'fecha_pago' => '2026-08-17',
            'monto_original' => 500.00,
            'monto_corregido' => 5000.00,
            'motivo' => 'Se capturó referencia equivocada en ficha bancaria.',
        ]);
        $responseSolicitar->assertSessionHasNoErrors();

        $conciliacion = \App\Models\Conciliacion::where('referencia_conciliacion', 'REF-CORRECTA-456')->first();
        $this->assertNotNull($conciliacion);
        $this->assertEquals('pendiente_coordinador', $conciliacion->estado);

        $solicitud = SolicitudAutorizacion::where('entidad_id', $conciliacion->id)->first();
        $this->assertNotNull($solicitud);

        // Coordinador aprueba la conciliación
        $responseAprobar = $this->actingAs($coordinador)->post(route('autorizaciones.aprobar', $solicitud), [
            'observaciones' => 'Conciliación bancaria verificada contra estado de cuenta.',
        ]);
        $responseAprobar->assertRedirect(route('autorizaciones.index'));

        $conciliacion->refresh();
        $this->assertEquals('conciliado', $conciliacion->estado);
        $this->assertEquals($coordinador->id, $conciliacion->conciliado_por_user_id);
        $this->assertNotNull($conciliacion->conciliado_at);
    }

    public function test_cashier_verification_views_show_pdf_links()
    {
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Cliente Con Expedientes',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1993-03-03',
            'lugar_nacimiento' => 'Torreón',
            'calle' => 'Calle 3',
            'colonia' => 'Centro',
            'codigo_postal' => '27000',
            'ciudad' => 'Torreón',
            'estado' => 'Coahuila',
            'activo' => true,
            'created_by_user_id' => $distribuidora->id,
            'path_ine_pdf' => 'expedientes_clientes/ine/ine_test.pdf',
            'path_comprobante_pdf' => 'expedientes_clientes/comprobantes/comprobante_test.pdf',
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-5000-' . uniqid(),
            'nombre' => 'Vale $5,000',
            'monto_prestamo' => 5000.00,
            'costo_seguro' => 150.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 14,
            'activo' => true,
        ]);

        $prevale = Prestamo::create([
            'referencia' => 'PREV-VIEW-' . uniqid(),
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'prevale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 550.00,
            'monto_total_pagar' => 7700.00,
            'pagos_totales' => 14,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'pendiente',
            'estado_entrega' => 'pendiente',
        ]);

        // Verificación de Prevale debe mostrar enlace a INE y Comprobante
        $responsePrevale = $this->actingAs($cajero)->get(route('cajero.prevale.verificar', $prevale));
        $responsePrevale->assertStatus(200);
        $responsePrevale->assertSee('Ver PDF INE');
        $responsePrevale->assertSee('Ver Comprobante');

        $vale = Prestamo::create([
            'referencia' => 'VALE-VIEW-' . uniqid(),
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 550.00,
            'monto_total_pagar' => 7700.00,
            'pagos_totales' => 14,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'pendiente',
            'estado_entrega' => 'pendiente',
        ]);

        // Verificación de Vale Digital debe mostrar enlace a INE
        $responseVale = $this->actingAs($cajero)->get(route('cajero.vale.verificar', $vale));
        $responseVale->assertStatus(200);
        $responseVale->assertSee('Ver PDF INE');
    }

    public function test_relacion_cobranza_is_updated_on_cut_due_date_and_abono()
    {
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'referencia_pago_distribuidor' => 'REF-DIST-00000077',
            'created_at' => now()->subDays(20),
            'activo' => true,
            'multas' => 0.00,
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Cliente Relacion',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1995-05-05',
            'lugar_nacimiento' => 'Torreón',
            'calle' => 'Calle 5',
            'colonia' => 'Centro',
            'codigo_postal' => '27000',
            'ciudad' => 'Torreón',
            'estado' => 'Coahuila',
            'activo' => true,
            'created_by_user_id' => $distribuidora->id,
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-2000-' . uniqid(),
            'nombre' => 'Vale $2,000',
            'monto_prestamo' => 2000.00,
            'costo_seguro' => 80.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 10,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-REL-' . uniqid(),
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 2000.00,
            'cuota_quincenal' => 250.00,
            'monto_total_pagar' => 2500.00,
            'pagos_totales' => 10,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 2000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        // 1. Corte automático: se crea/actualiza la relación con monto del periodo y adeudo
        $config = Configuracion::firstOrCreate([], [
            'dias_limite_pago' => 5,
            'multa_adeudo' => 300.00,
        ]);
        $cortePasado = now()->subHours(2);
        $limiteFuturo = now()->addDays(5);
        $config->update([
            'dia_corte' => $cortePasado->day,
            'hora_corte' => $cortePasado->format('H:i:s'),
            'dia_limite_pago' => $limiteFuturo->day,
            'hora_limite_pago' => '23:59:00',
        ]);

        $service = app(CorteCobranzaService::class);
        $service->verificarYProcesarCortesYVencimientos();

        $relacion = RelacionCobranza::where('distribuidora_id', $distribuidora->id)->first();
        $this->assertNotNull($relacion);
        // Cuota neta con comisión de distribuidora descontada: 250 - ((4% * 2000)/10) = 242.00
        $cuotaNetaEsperada = $distribuidora->totalCuotaQuincenalNeta();
        $this->assertEquals($cuotaNetaEsperada, $relacion->monto_total_periodo);
        $this->assertEquals($cuotaNetaEsperada, $relacion->adeudo_pendiente);
        $this->assertEquals(0.00, $relacion->monto_pagado);

        // 2. Abono en ventanilla de $100: se actualiza la relación
        $this->actingAs($cajero)->post(route('cajero.abonos.distribuidora.store', $distribuidora), [
            'referencia_pago' => 'REF-DIST-00000077',
            'monto_abonado' => 100.00,
            'metodo_pago' => 'efectivo',
        ]);

        $relacion->refresh();
        $this->assertEquals(100.00, $relacion->monto_pagado);
        $this->assertEquals($cuotaNetaEsperada - 100.00, $relacion->adeudo_pendiente);

        // 3. Vencimiento de fecha límite: se aplica multa y se actualiza la relación
        $limitePasado = now()->subHour();
        $config->update([
            'dia_limite_pago' => $limitePasado->day,
            'hora_limite_pago' => $limitePasado->format('H:i:s'),
            'multa_adeudo' => 300.00,
        ]);

        $service->verificarYProcesarCortesYVencimientos();

        $relacion->refresh();
        $this->assertEquals(300.00, $relacion->multa_aplicada);
        $this->assertEquals(($cuotaNetaEsperada - 100.00) + 300.00, $relacion->adeudo_pendiente);
    }

    public function test_gerente_general_and_gerente_sucursal_see_notification_bell_in_navbar()
    {
        $rolGG = Rol::firstOrCreate(['nombre' => 'Gerente General']);
        $rolGS = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);

        $gerenteGeneral = User::factory()->create([
            'rol_id' => $rolGG->id,
            'activo' => true,
        ]);

        $gerenteSucursal = User::factory()->create([
            'rol_id' => $rolGS->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        // Crear notificación no leída para Gerente General
        NotificacionCajero::create([
            'user_id' => $gerenteGeneral->id,
            'tipo' => 'solicitud_transferencia',
            'titulo' => 'Solicitud de Transferencia',
            'mensaje' => 'Hay una solicitud pendiente',
            'leida' => false,
        ]);

        // Crear notificación no leída para Gerente de Sucursal
        NotificacionCajero::create([
            'user_id' => $gerenteSucursal->id,
            'tipo' => 'nueva_solicitud',
            'titulo' => 'Nueva Solicitud',
            'mensaje' => 'Revisión requerida',
            'leida' => false,
        ]);

        // Gerente General debe ver campana con badge '1' y enlace a notificaciones
        $responseGG = $this->actingAs($gerenteGeneral)->get(route('gerente-general.dashboard'));
        $responseGG->assertStatus(200);
        $responseGG->assertSee(route('notificaciones.index'));

        // Gerente de Sucursal debe ver campana con badge '1' y enlace a notificaciones
        $responseGS = $this->actingAs($gerenteSucursal)->get(route('gerente-sucursal.dashboard'));
        $responseGS->assertStatus(200);
        $responseGS->assertSee(route('notificaciones.index'));
    }

    public function test_corte_calculates_points_only_if_cut_is_liquidated_before_cut_date()
    {
        $config = Configuracion::firstOrCreate([], [
            'dias_limite_pago' => 5,
            'multa_adeudo' => 300.00,
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
        ]);
        $cortePasado = now()->subHours(2);
        $limiteFuturo = now()->addDays(5);
        $config->update([
            'dia_corte' => $cortePasado->day,
            'hora_corte' => $cortePasado->format('H:i:s'),
            'dia_limite_pago' => $limiteFuturo->day,
            'hora_limite_pago' => '23:59:00',
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
        ]);

        // Distribuidora A: colocó $2,400 en préstamos con cuota quincenal de $300.
        // Tiene saldo neto total pendiente ($2,700), pero ya liquidó su cuota quincenal ($300) antes del corte
        $distA = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'created_at' => now()->subDays(10),
            'activo' => true,
            'puntos' => 0,
            'multas' => 0.00,
        ]);

        $clienteA = \App\Models\Cliente::create([
            'nombre' => 'Cliente A',
            'curp' => substr('CURPA' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1990-01-01',
            'lugar_nacimiento' => 'Torreón',
            'calle' => 'Calle A',
            'colonia' => 'Centro',
            'codigo_postal' => '27000',
            'ciudad' => 'Torreón',
            'estado' => 'Coahuila',
            'activo' => true,
            'created_by_user_id' => $distA->id,
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-2400-' . uniqid(),
            'nombre' => 'Vale $2,400',
            'monto_prestamo' => 2400.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 10,
            'activo' => true,
        ]);

        Prestamo::create([
            'referencia' => 'VAL-A-' . uniqid(),
            'cliente_id' => $clienteA->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distA->id,
            'tipo' => 'vale',
            'monto_prestamo' => 2400.00,
            'cuota_quincenal' => 300.00,
            'monto_total_pagar' => 3000.00,
            'pagos_totales' => 10,
            'pagos_realizados' => 1,
            'pagos_recibidos' => 300.00,
            'adeudo_pendiente' => 2700.00, // Mantiene adeudo neto de $2,700 en quincenas futuras
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        // La relación de cobranza previa al corte registra el pago quincenal cubierto ($300 pagados de $300 exigibles)
        RelacionCobranza::create([
            'distribuidora_id' => $distA->id,
            'fecha_corte' => $config->fecha_corte,
            'fecha_limite_pago' => $config->fecha_limite_pago,
            'monto_total_periodo' => 300.00,
            'monto_pagado' => 300.00,
            'adeudo_pendiente' => 0.00,
            'estado_pago' => 'pago_anticipado',
            'puntos_ganados' => 0,
        ]);

        // Distribuidora B: colocó $2,400 con cuota de $300 pero NO ha pagado su cuota ($0 pagados)
        $distB = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'created_at' => now()->subDays(10),
            'activo' => true,
            'puntos' => 0,
            'multas' => 0.00,
        ]);

        $clienteB = \App\Models\Cliente::create([
            'nombre' => 'Cliente B',
            'curp' => substr('CURPB' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1991-01-01',
            'lugar_nacimiento' => 'Torreón',
            'calle' => 'Calle B',
            'colonia' => 'Centro',
            'codigo_postal' => '27000',
            'ciudad' => 'Torreón',
            'estado' => 'Coahuila',
            'activo' => true,
            'created_by_user_id' => $distB->id,
        ]);

        Prestamo::create([
            'referencia' => 'VAL-B-' . uniqid(),
            'cliente_id' => $clienteB->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distB->id,
            'tipo' => 'vale',
            'monto_prestamo' => 2400.00,
            'cuota_quincenal' => 300.00,
            'monto_total_pagar' => 3000.00,
            'pagos_totales' => 10,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 3000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        // Ejecutar el proceso de corte
        $service = app(CorteCobranzaService::class);
        $service->verificarYProcesarCortesYVencimientos();

        // Distribuidora A: Ganó 6 puntos (floor(2400/1200) * 3) por liquidar su total 15nal ($300) antes del corte
        $distA->refresh();
        $this->assertEquals(6, $distA->puntos);

        $relacionA = RelacionCobranza::where('distribuidora_id', $distA->id)->first();
        $this->assertNotNull($relacionA);
        $this->assertEquals('pago_anticipado', $relacionA->estado_pago);
        $this->assertEquals(6, $relacionA->puntos_ganados);
        $this->assertEquals(0, $relacionA->puntos_descontados);

        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $distA->id,
            'tipo' => 'pago_anticipado',
        ]);

        // Distribuidora B: No liquidó su cuota 15nal antes del corte -> 0 puntos, estado pendiente
        $distB->refresh();
        $this->assertEquals(0, $distB->puntos);

        $relacionB = RelacionCobranza::where('distribuidora_id', $distB->id)->first();
        $this->assertNotNull($relacionB);
        $this->assertEquals('pendiente', $relacionB->estado_pago);
        $this->assertEquals(0, $relacionB->puntos_ganados);
        $this->assertEquals($distB->totalCuotaQuincenalNeta(), $relacionB->adeudo_pendiente);
    }

    public function test_points_are_not_awarded_on_abono_only_on_cut_including_prior_conciliations()
    {
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'referencia_pago_distribuidor' => 'REF-DIST-00000088',
            'created_at' => now()->subDays(15),
            'activo' => true,
            'puntos' => 0,
            'multas' => 0.00,
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Cliente Conciliado',
            'curp' => substr('CURPC' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1992-02-02',
            'lugar_nacimiento' => 'Torreón',
            'calle' => 'Calle C',
            'colonia' => 'Centro',
            'codigo_postal' => '27000',
            'ciudad' => 'Torreón',
            'estado' => 'Coahuila',
            'activo' => true,
            'created_by_user_id' => $distribuidora->id,
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-3600-' . uniqid(),
            'nombre' => 'Vale $3,600',
            'monto_prestamo' => 3600.00,
            'costo_seguro' => 120.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 12,
            'activo' => true,
        ]);

        Prestamo::create([
            'referencia' => 'VAL-CONC-' . uniqid(),
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 3600.00,
            'cuota_quincenal' => 400.00,
            'monto_total_pagar' => 4800.00,
            'pagos_totales' => 12,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 4800.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        $config = Configuracion::firstOrCreate([], [
            'dias_limite_pago' => 5,
            'multa_adeudo' => 300.00,
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
        ]);
        $config->update([
            'dia_corte' => now()->day,
            'hora_corte' => now()->addHours(2)->format('H:i:s'), // Corte futuro (más tarde hoy)
            'dia_limite_pago' => now()->addDays(5)->day,
            'hora_limite_pago' => '23:59:00',
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
        ]);

        // 1. Abono normal registrado en ventanilla antes del corte: NO debe sumar puntos en este momento
        $this->actingAs($cajero)->post(route('cajero.abonos.distribuidora.store', $distribuidora), [
            'referencia_pago' => 'REF-DIST-00000088',
            'monto_abonado' => 200.00,
            'metodo_pago' => 'efectivo',
        ]);

        $distribuidora->refresh();
        $this->assertEquals(0, $distribuidora->puntos, 'El abono NO debe otorgar puntos de inmediato.');

        // 2. Conciliación aprobada de $200 con fecha_pago de ayer (anterior al corte)
        \App\Models\Conciliacion::create([
            'distribuidora_id' => $distribuidora->id,
            'solicitante_id' => $cajero->id,
            'fecha_pago' => now()->subDay()->format('Y-m-d'),
            'monto_original' => 200.00,
            'monto_corregido' => 200.00,
            'referencia_original' => 'REF-ORIG',
            'referencia_conciliacion' => 'REF-DIST-00000088',
            'motivo' => 'Depósito bancario conciliado',
            'estado' => 'conciliado',
            'conciliado_at' => now(),
        ]);

        // 3. Simular que llega la hora del corte
        $config->update([
            'hora_corte' => now()->subMinutes(10)->format('H:i:s'),
        ]);

        $service = app(CorteCobranzaService::class);
        $service->verificarYProcesarCortesYVencimientos();

        // Al procesar el corte: $200 (abono) + $200 (conciliación previa al corte) = $400 (cuota 15nal cubierta)
        // Puntos: floor(3600 / 1200) * 3 = 9 puntos
        $distribuidora->refresh();
        $this->assertEquals(9, $distribuidora->puntos, 'Debe ganar 9 puntos calculados al momento del corte por liquidar antes de la fecha.');

        $relacion = RelacionCobranza::where('distribuidora_id', $distribuidora->id)->first();
        $this->assertNotNull($relacion);
        $this->assertEquals('pago_anticipado', $relacion->estado_pago);
        $this->assertEquals(9, $relacion->puntos_ganados);
    }

    private function crearClienteTest(string $nombre, ?User $distribuidora = null): \App\Models\Cliente
    {
        return \App\Models\Cliente::create([
            'nombre' => $nombre,
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1990-01-01',
            'lugar_nacimiento' => 'Torreón',
            'calle' => 'Calle 1',
            'colonia' => 'Centro',
            'codigo_postal' => '27000',
            'ciudad' => 'Torreón',
            'estado' => 'Coahuila',
            'activo' => true,
            'distribuidor_id' => $distribuidora?->id,
            'created_by_user_id' => $distribuidora?->id,
        ]);
    }

    public function test_relacion_cobranza_ordenada_por_cliente_y_progresion_de_pagos()
    {
        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        $clienteB = $this->crearClienteTest('Bernardo Benitez', $distribuidora);
        $clienteA = $this->crearClienteTest('Ana Alvarez', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-8Q'], [
            'nombre' => 'Vale 8 Quincenas',
            'monto_prestamo' => 8000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 1010.00,
            'multa' => 20.00,
            'comision_distribuidor' => 10.00,
            'activo' => true,
        ]);

        $prestamoB = Prestamo::create([
            'referencia' => 'VALE-B',
            'cliente_id' => $clienteB->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 8000.00,
            'cuota_quincenal' => 1010.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 8080.00,
            'adeudo_pendiente' => 8080.00,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);

        $prestamoA = Prestamo::create([
            'referencia' => 'VALE-A',
            'cliente_id' => $clienteA->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 8000.00,
            'cuota_quincenal' => 1010.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 8080.00,
            'adeudo_pendiente' => 8080.00,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);

        $service = app(CorteCobranzaService::class);
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        // Ana Alvarez debe aparecer primero y Bernardo Benitez después
        $this->assertNotEmpty($filas);
        $this->assertEquals('Ana Alvarez', $filas[0]['cliente']);
        $this->assertEquals('1/8', $filas[0]['numero_pago']);
        $this->assertEquals('Bernardo Benitez', $filas[1]['cliente']);
    }

    public function test_relacion_cobranza_calculo_atraso_y_recargos()
    {
        Configuracion::actual()->update(['comision_cobre' => 10.00]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        $cliente = $this->crearClienteTest('Juan Perez', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-TEST'], [
            'nombre' => 'Vale 1',
            'monto_prestamo' => 800.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 1010.00,
            'multa' => 20.00,
            'comision_distribuidor' => 1.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VALE-JUAN-1',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 800.00,
            'cuota_quincenal' => 1010.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 8080.00,
            'adeudo_pendiente' => 8080.00,
            'multas' => 20.00,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);
        $prestamo->timestamps = false;
        $prestamo->created_at = now()->subDays(20);
        $prestamo->save();
        $prestamo->timestamps = true;

        // Crear 2 relaciones históricas (2 cortes transcurridos)
        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now()->subDays(15),
            'fecha_limite_pago' => now()->subDays(10),
            'monto_total_periodo' => 1010.00,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 1010.00,
            'estado_pago' => 'pago_atrasado',
        ]);
        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now(),
            'fecha_limite_pago' => now()->addDays(5),
            'monto_total_periodo' => 2030.00,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 2030.00,
            'estado_pago' => 'pendiente',
        ]);

        $service = app(CorteCobranzaService::class);
        $filas = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(2, $filas);
        // Fila 1 (Corte 1 / 1/8): 1010.00 (impago)
        $this->assertEquals(1, $filas[0]['numero']);
        $this->assertEquals('1/8', $filas[0]['numero_pago']);
        $this->assertEquals(1010.00, $filas[0]['pago']);
        $this->assertEquals(1010.00, $filas[0]['total']);

        // Fila 2 (Corte 2 / 2/8): 2030.00 (1000 cuota neta + 20 recargos + 1010 adeudo anterior)
        $this->assertEquals(2, $filas[1]['numero']);
        $this->assertEquals('2/8', $filas[1]['numero_pago']);
        $this->assertEquals(20.00, $filas[1]['recargos']);
        $this->assertEquals(2030.00, $filas[1]['total']);

        // Último valor del vale: 2030.00
        $this->assertEquals(2030.00, $filas[1]['total']);
    }

    public function test_relacion_cobranza_pago_parcial_y_excedente()
    {
        Configuracion::actual()->update(['comision_cobre' => 1.00]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        $clienteParcial = $this->crearClienteTest('Cliente Parcial', $distribuidora);
        $clienteExcedente = $this->crearClienteTest('Cliente Excedente', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-8Q-EXP'], [
            'nombre' => 'Vale 1',
            'monto_prestamo' => 8000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 1010.00,
            'multa' => 20.00,
            'comision_distribuidor' => 1.00,
            'activo' => true,
        ]);

        // Préstamo 1: Con pago parcial (990) en corte 1
        $prestamoParcial = Prestamo::create([
            'referencia' => 'VALE-PARCIAL',
            'cliente_id' => $clienteParcial->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 8000.00,
            'cuota_quincenal' => 1010.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 8080.00,
            'adeudo_pendiente' => 7090.00,
            'multas' => 20.00,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);
        $prestamoParcial->timestamps = false;
        $prestamoParcial->created_at = now()->subDays(20);
        $prestamoParcial->save();
        $prestamoParcial->timestamps = true;

        PagoPrestamo::create([
            'prestamo_id' => $prestamoParcial->id,
            'folio_pago' => 'PAGO-P1',
            'numero_quincena' => 1,
            'monto_abonado' => 990.00,
            'monto_multa' => 0,
            'metodo_pago' => 'Efectivo',
            'created_at' => now()->subDays(16),
        ]);

        // Préstamo 2: Con pago excedente (1010) en corte 1
        $prestamoExcedente = Prestamo::create([
            'referencia' => 'VALE-EXCEDENTE',
            'cliente_id' => $clienteExcedente->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 8000.00,
            'cuota_quincenal' => 1010.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 1,
            'monto_total_pagar' => 8080.00,
            'adeudo_pendiente' => 7070.00,
            'multas' => 0,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);
        $prestamoExcedente->timestamps = false;
        $prestamoExcedente->created_at = now()->subDays(20);
        $prestamoExcedente->save();
        $prestamoExcedente->timestamps = true;
        PagoPrestamo::create([
            'prestamo_id' => $prestamoExcedente->id,
            'folio_pago' => 'PAGO-E1',
            'numero_quincena' => 1,
            'monto_abonado' => 1010.00,
            'monto_multa' => 0,
            'metodo_pago' => 'Efectivo',
            'created_at' => now()->subDays(16),
        ]);

        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now()->subDays(15),
            'fecha_limite_pago' => now()->subDays(10),
            'monto_total_periodo' => 2000.00,
            'monto_pagado' => 2000.00,
            'adeudo_pendiente' => 0.00,
            'estado_pago' => 'pago_a_tiempo',
        ]);

        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now(),
            'fecha_limite_pago' => now()->addDays(5),
            'monto_total_periodo' => 2000.00,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 2000.00,
            'estado_pago' => 'pendiente',
        ]);

        $service = app(CorteCobranzaService::class);
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        // Buscar filas del cliente excedente
        $filasExcedente = array_values(array_filter($filas, fn($f) => $f['cliente'] === 'Cliente Excedente'));
        $this->assertEquals(1, $filasExcedente[0]['numero']);
        $this->assertEquals(-10.00, $filasExcedente[0]['total'], 'El corte 1 con excedente de 10 debe mostrar -10.00');
        $this->assertEquals(2, $filasExcedente[1]['numero']);
        $this->assertEquals(990.00, $filasExcedente[1]['total']); // 1000 - 10 excedente

        // Buscar filas del cliente parcial
        $filasParcial = array_values(array_filter($filas, fn($f) => $f['cliente'] === 'Cliente Parcial'));
        $this->assertEquals(1, $filasParcial[0]['numero']);
        $this->assertEquals(1010.00, $filasParcial[0]['total']); // 1010 cuota bruta
        $this->assertEquals(2, $filasParcial[1]['numero']);
        $this->assertEquals(1040.00, $filasParcial[1]['total']); // 1000 cuota neta + 20 recargos + 20 adeudo anterior
    }

    public function test_relacion_cobranza_dos_clientes_juan_atrasado_y_jose_al_corriente_total_3030()
    {
        Configuracion::actual()->update(['comision_cobre' => 1.00]); // 1% de 8000 / 8 = $10.00 exactos de comision por quincena

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        $clienteJuan = $this->crearClienteTest('Juan', $distribuidora);
        $clienteJose = $this->crearClienteTest('Jose', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-1010'], [
            'nombre' => 'vale 1',
            'monto_prestamo' => 8000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 1010.00,
            'multa' => 20.00,
            'comision_distribuidor' => 10.00,
            'activo' => true,
        ]);

        // Prestamo Juan: no pagó corte 1 (arrastra adeudo de $1010)
        $prestamoJuan = Prestamo::create([
            'referencia' => 'VALE-JUAN',
            'cliente_id' => $clienteJuan->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 8000.00,
            'cuota_quincenal' => 1010.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 8080.00,
            'adeudo_pendiente' => 8080.00,
            'multas' => 20.00,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);
        $prestamoJuan->timestamps = false;
        $prestamoJuan->created_at = now()->subDays(30);
        $prestamoJuan->save();
        $prestamoJuan->timestamps = true;

        // Prestamo Jose: sólo 1 corte transcurrido y pagará a tiempo ($1000)
        $prestamoJose = Prestamo::create([
            'referencia' => 'VALE-JOSE',
            'cliente_id' => $clienteJose->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 8000.00,
            'cuota_quincenal' => 1010.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 8080.00,
            'adeudo_pendiente' => 8080.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);
        $prestamoJose->timestamps = false;
        $prestamoJose->created_at = now()->subDays(5);
        $prestamoJose->save();
        $prestamoJose->timestamps = true;

        // Cortes de la distribuidora: Corte 1 (hace 15 días) y Corte 2 (hoy)
        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now()->subDays(15),
            'fecha_limite_pago' => now()->subDays(10),
            'monto_total_periodo' => 1010.00,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 1010.00,
            'estado_pago' => 'pago_atrasado',
        ]);

        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now(),
            'fecha_limite_pago' => now()->addDays(5),
            'monto_total_periodo' => 3030.00,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 3030.00,
            'estado_pago' => 'pendiente',
        ]);

        $service = app(CorteCobranzaService::class);
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        // Filas de Jose (orden alfabético: Jose antes de Juan)
        $filasJose = array_values(array_filter($filas, fn($f) => $f['cliente'] === 'Jose'));
        $this->assertCount(1, $filasJose);
        $this->assertEquals(1, $filasJose[0]['numero']);
        $this->assertEquals('1/8', $filasJose[0]['numero_pago']);
        $this->assertEquals(10.00, round($filasJose[0]['comision'], 2));
        $this->assertEquals(1010.00, $filasJose[0]['pago']);
        $this->assertEquals(0.00, $filasJose[0]['recargos']);
        $this->assertEquals(1000.00, round($filasJose[0]['total'], 2));

        // Filas de Juan
        $filasJuan = array_values(array_filter($filas, fn($f) => $f['cliente'] === 'Juan'));
        $this->assertCount(2, $filasJuan);
        // Juan Corte 1: 1, vale 1, Juan, 1/8, 10.00, 1010.00, 00.00, 1010.00
        $this->assertEquals(1, $filasJuan[0]['numero']);
        $this->assertEquals('1/8', $filasJuan[0]['numero_pago']);
        $this->assertEquals(10.00, round($filasJuan[0]['comision'], 2));
        $this->assertEquals(1010.00, $filasJuan[0]['pago']);
        $this->assertEquals(0.00, $filasJuan[0]['recargos']);
        $this->assertEquals(1010.00, round($filasJuan[0]['total'], 2));

        // Juan Corte 2: 2, vale 1, Juan, 2/8, 10.00, 1010.00, 20.00, 2030.00
        $this->assertEquals(2, $filasJuan[1]['numero']);
        $this->assertEquals('2/8', $filasJuan[1]['numero_pago']);
        $this->assertEquals(10.00, round($filasJuan[1]['comision'], 2));
        $this->assertEquals(1010.00, $filasJuan[1]['pago']);
        $this->assertEquals(20.00, $filasJuan[1]['recargos']);
        $this->assertEquals(2030.00, round($filasJuan[1]['total'], 2));

        // Suma de últimos valores: Jose ($1000.00) + Juan ($2030.00) = $3030.00
        $ultimoJose = end($filasJose)['total'];
        $ultimoJuan = end($filasJuan)['total'];
        $this->assertEquals(3030.00, round($ultimoJose + $ultimoJuan, 2));
    }

    public function test_relacion_cobranza_caso_usuario_imagen_totales_exactos()
    {
        Configuracion::actual()->update(['comision_cobre' => 3.00]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        $cliente = $this->crearClienteTest('nacy rodriguez', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-5K-IMG'], [
            'nombre' => '5/8',
            'monto_prestamo' => 5000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VALE-NACY-1',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 300.00,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);
        $prestamo->timestamps = false;
        $prestamo->created_at = now()->subDays(20);
        $prestamo->save();
        $prestamo->timestamps = true;

        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now()->subDays(15),
            'fecha_limite_pago' => now()->subDays(10),
            'monto_total_periodo' => 950.00,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 950.00,
            'estado_pago' => 'pago_atrasado',
        ]);

        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now(),
            'fecha_limite_pago' => now()->addDays(5),
            'monto_total_periodo' => 2181.25,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 2181.25,
            'estado_pago' => 'pendiente',
        ]);

        $service = app(CorteCobranzaService::class);
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        $this->assertCount(2, $filas);

        // Fila 1 (Corte 1 / 1/8): 950.00 (impago)
        $this->assertEquals(1, $filas[0]['numero']);
        $this->assertEquals('1/8', $filas[0]['numero_pago']);
        $this->assertEquals(18.75, $filas[0]['comision']);
        $this->assertEquals(950.00, $filas[0]['pago']);
        $this->assertEquals(0.00, $filas[0]['recargos']);
        $this->assertEquals(950.00, $filas[0]['total']);

        // Fila 2 (Corte 2 / 2/8): 2181.25 (931.25 cuota neta + 300 recargos + 950 adeudo anterior)
        $this->assertEquals(2, $filas[1]['numero']);
        $this->assertEquals('2/8', $filas[1]['numero_pago']);
        $this->assertEquals(18.75, $filas[1]['comision']);
        $this->assertEquals(950.00, $filas[1]['pago']);
        $this->assertEquals(300.00, $filas[1]['recargos']);
        $this->assertEquals(2181.25, $filas[1]['total']);

        // Último valor del vale: 2181.25
        $this->assertEquals(2181.25, $filas[1]['total']);
    }

    public function test_historico_cortes_accesible_para_gerentes_y_administrador()
    {
        $rolGerenteGeneral = Rol::firstOrCreate(['nombre' => 'Gerente General']);
        $rolGerenteSucursal = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador']);

        $gg = User::factory()->create(['rol_id' => $rolGerenteGeneral->id]);
        $gs = User::factory()->create(['rol_id' => $rolGerenteSucursal->id, 'sucursal_id' => $this->sucursal->id]);
        $admin = User::factory()->create(['rol_id' => $rolAdmin->id]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $corte = RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now()->subDays(15),
            'fecha_limite_pago' => now()->subDays(10),
            'monto_total_periodo' => 1500.00,
            'monto_pagado' => 1500.00,
            'adeudo_pendiente' => 0.00,
            'estado_pago' => 'pago_a_tiempo',
        ]);

        // Gerente General accede al PDF del corte histórico
        $resGG = $this->actingAs($gg)->get(route('prestamos.relacion-pdf', ['corte_id' => $corte->id]));
        $resGG->assertStatus(200);
        $resGG->assertSee('Relación de Cobranza Oficial');

        // Gerente Sucursal accede al PDF del corte histórico
        $resGS = $this->actingAs($gs)->get(route('prestamos.relacion-pdf', ['corte_id' => $corte->id]));
        $resGS->assertStatus(200);

        // Administrador accede al PDF del corte histórico
        $resAdmin = $this->actingAs($admin)->get(route('prestamos.relacion-pdf', ['corte_id' => $corte->id]));
        $resAdmin->assertStatus(200);
    }

    public function test_relacion_cobranza_se_detiene_en_limite_quincenas()
    {
        Configuracion::actual()->update(['comision_cobre' => 10.00]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        $cliente = $this->crearClienteTest('Cliente Plazo 4', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-4Q'], [
            'nombre' => 'Vale 4 Quincenas',
            'monto_prestamo' => 400.00,
            'plazo_quincenas' => 4,
            'cuota_quincenal' => 1010.00,
            'multa' => 20.00,
            'comision_distribuidor' => 1.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VALE-4Q-1',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 400.00,
            'cuota_quincenal' => 1010.00,
            'pagos_totales' => 4,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 4040.00,
            'adeudo_pendiente' => 4040.00,
            'multas' => 80.00,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);
        $prestamo->timestamps = false;
        $prestamo->created_at = now()->subDays(90);
        $prestamo->save();
        $prestamo->timestamps = true;

        // Crear 6 cortes transcurridos
        for ($i = 6; $i >= 1; $i--) {
            RelacionCobranza::create([
                'distribuidora_id' => $distribuidora->id,
                'fecha_corte' => now()->subDays($i * 15),
                'fecha_limite_pago' => now()->subDays($i * 15 - 5),
                'monto_total_periodo' => 1010.00,
                'monto_pagado' => 0.00,
                'adeudo_pendiente' => 1010.00,
                'estado_pago' => 'pago_atrasado',
            ]);
        }

        $service = app(CorteCobranzaService::class);
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        $this->assertCount(6, $filas);
        $this->assertEquals('1/4', $filas[0]['numero_pago']);
        $this->assertEquals('2/4', $filas[1]['numero_pago']);
        $this->assertEquals('3/4', $filas[2]['numero_pago']);
        $this->assertEquals('4/4', $filas[3]['numero_pago']);
        
        // Cortes 5 y 6 se detienen en el límite 4/4 y suman las multas al total acumulado anterior
        $this->assertEquals('4/4', $filas[4]['numero_pago']);
        $this->assertEquals(0.00, $filas[4]['comision']);
        $this->assertEquals(0.00, $filas[4]['pago']);
        $this->assertEquals(20.00, $filas[4]['recargos']);
        $this->assertEquals(4090.00, $filas[4]['total']);

        $this->assertEquals('4/4', $filas[5]['numero_pago']);
        $this->assertEquals(0.00, $filas[5]['comision']);
        $this->assertEquals(0.00, $filas[5]['pago']);
        $this->assertEquals(20.00, $filas[5]['recargos']);
        $this->assertEquals(4110.00, $filas[5]['total']);
    }

    public function test_relacion_cobranza_corte_nueve_post_limite_ocho_lleva_solo_multas()
    {
        Configuracion::actual()->update(['comision_cobre' => 3.00]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        $cliente = $this->crearClienteTest('Juan', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-8Q-JUAN'], [
            'nombre' => 'vale 1',
            'monto_prestamo' => 5000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VALE-JUAN-1',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 300.00,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);
        $prestamo->timestamps = false;
        $prestamo->created_at = now()->subDays(150);
        $prestamo->save();
        $prestamo->timestamps = true;

        // 9 cortes transcurridos
        for ($i = 9; $i >= 1; $i--) {
            RelacionCobranza::create([
                'distribuidora_id' => $distribuidora->id,
                'fecha_corte' => now()->subDays($i * 15),
                'fecha_limite_pago' => now()->subDays($i * 15 - 5),
                'monto_total_periodo' => 950.00,
                'monto_pagado' => 0.00,
                'adeudo_pendiente' => 950.00,
                'estado_pago' => 'pago_atrasado',
            ]);
        }

        $service = app(CorteCobranzaService::class);
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        $this->assertCount(9, $filas);

        // Fila 8: 8/8 con 9568.75
        $fila8 = $filas[7];
        $this->assertEquals(8, $fila8['numero']);
        $this->assertEquals('8/8', $fila8['numero_pago']);
        $this->assertEquals(9568.75, $fila8['total']);

        // Fila 9: 9, vale 1, Juan, 8/8, 00.00, 00.00, 300.00, 9868.75 (9568.75 + 300.00)
        $fila9 = $filas[8];
        $this->assertEquals(9, $fila9['numero']);
        $this->assertEquals('vale 1', $fila9['producto']);
        $this->assertEquals('Juan', $fila9['cliente']);
        $this->assertEquals('8/8', $fila9['numero_pago']);
        $this->assertEquals(0.00, $fila9['comision']);
        $this->assertEquals(0.00, $fila9['pago']);
        $this->assertEquals(300.00, $fila9['recargos']);
        $this->assertEquals(9868.75, $fila9['total']);
    }

    public function test_prestamo_recien_asignado_muestra_solo_una_fila_aunque_distribuidora_tenga_muchos_cortes_historicos()
    {
        Configuracion::actual()->update(['comision_cobre' => 3.00]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        // Simular 21 cortes históricos antiguos de la distribuidora
        for ($i = 21; $i >= 1; $i--) {
            RelacionCobranza::create([
                'distribuidora_id' => $distribuidora->id,
                'fecha_corte' => now()->subDays($i * 15),
                'fecha_limite_pago' => now()->subDays($i * 15 - 5),
                'monto_total_periodo' => 950.00,
                'monto_pagado' => 950.00,
                'adeudo_pendiente' => 0.00,
                'estado_pago' => 'pago_a_tiempo',
            ]);
        }

        $cliente = $this->crearClienteTest('Maria Garcia', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-5K-MARIA'], [
            'nombre' => '5/8',
            'monto_prestamo' => 5000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'activo' => true,
        ]);

        // Préstamo recién creado hoy
        $prestamo = Prestamo::create([
            'referencia' => 'VALE-MARIA-NEW',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);

        $service = app(CorteCobranzaService::class);
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        // Debe tener exactamente 1 fila (Corte 1 / 1/8)
        $this->assertCount(1, $filas);
        $this->assertEquals(1, $filas[0]['numero']);
        $this->assertEquals('5/8', $filas[0]['producto']);
        $this->assertEquals('Maria Garcia', $filas[0]['cliente']);
        $this->assertEquals('1/8', $filas[0]['numero_pago']);
        $this->assertEquals(18.75, $filas[0]['comision']);
        $this->assertEquals(950.00, $filas[0]['pago']);
        $this->assertEquals(0.00, $filas[0]['recargos']);
        $this->assertEquals(931.25, $filas[0]['total']);
    }

    public function test_relacion_cobranza_elimina_prestamos_liquidados()
    {
        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        $clienteLiquidado = $this->crearClienteTest('Cliente Liquidado', $distribuidora);
        $clienteActivo = $this->crearClienteTest('Cliente Activo', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-TEST-LIQ'], [
            'nombre' => 'Vale Test',
            'monto_prestamo' => 800.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 1010.00,
            'multa' => 20.00,
            'comision_distribuidor' => 1.00,
            'activo' => true,
        ]);

        // Préstamo liquidado en el pasado
        $prestamoLiq = Prestamo::create([
            'referencia' => 'VALE-LIQ',
            'cliente_id' => $clienteLiquidado->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 800.00,
            'cuota_quincenal' => 1010.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 8,
            'monto_total_pagar' => 8080.00,
            'adeudo_pendiente' => 0.00,
            'multas' => 0,
            'estado' => 'finalizado',
            'created_by_user_id' => $distribuidora->id,
        ]);
        $prestamoLiq->timestamps = false;
        $prestamoLiq->created_at = now()->subDays(60);
        $prestamoLiq->updated_at = now()->subDays(30);
        $prestamoLiq->save();
        $prestamoLiq->timestamps = true;

        // Préstamo activo
        $prestamoActivo = Prestamo::create([
            'referencia' => 'VALE-ACTIVO',
            'cliente_id' => $clienteActivo->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 800.00,
            'cuota_quincenal' => 1010.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 8080.00,
            'adeudo_pendiente' => 8080.00,
            'multas' => 0,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);

        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now(),
            'fecha_limite_pago' => now()->addDays(5),
            'monto_total_periodo' => 1000.00,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 1000.00,
            'estado_pago' => 'pendiente',
        ]);

        $service = app(CorteCobranzaService::class);
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        // Solo debe figurar el préstamo activo; el liquidado no aparece
        $this->assertCount(1, $filas);
        $this->assertEquals('Cliente Activo', $filas[0]['cliente']);
    }

    public function test_asignar_vale_hacer_corte_y_luego_asignar_segundo_vale_segundo_no_se_ve_afectado()
    {
        Configuracion::actual()->update(['comision_cobre' => 3.00]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        $clienteLeo = $this->crearClienteTest('Leo', $distribuidora);
        $clienteMaria = $this->crearClienteTest('Maria Garcia', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-5K-TEST-FLOW'], [
            'nombre' => '5/8',
            'monto_prestamo' => 5000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'activo' => true,
        ]);

        // 1. Asignar vale a Leo hace 20 días
        $prestamoLeo = Prestamo::create([
            'referencia' => 'VALE-LEO-1',
            'cliente_id' => $clienteLeo->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 300.00, // No pagó corte 1
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);
        $prestamoLeo->timestamps = false;
        $prestamoLeo->created_at = now()->subDays(20);
        $prestamoLeo->save();
        $prestamoLeo->timestamps = true;

        // Corte 1 de la distribuidora (hace 15 días)
        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now()->subDays(15),
            'fecha_limite_pago' => now()->subDays(10),
            'monto_total_periodo' => 950.00,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 950.00,
            'estado_pago' => 'pago_atrasado',
        ]);

        // Corte 2 de la distribuidora (hoy)
        $relacionActual = RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => now(),
            'fecha_limite_pago' => now()->addDays(5),
            'monto_total_periodo' => 3112.50,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 3112.50,
            'estado_pago' => 'pendiente',
        ]);

        // 2. DESPUÉS del primer corte se asigna vale a Maria Garcia (hoy)
        $prestamoMaria = Prestamo::create([
            'referencia' => 'VALE-MARIA-2',
            'cliente_id' => $clienteMaria->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 0.00, // Recién asignado, 0 multas
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
        ]);

        $service = app(CorteCobranzaService::class);
        $filas = $service->generarFilasRelacionCobranza($distribuidora, $relacionActual);

        // Leo tiene 2 filas (estuvo en corte 1 y corte 2)
        $filasLeo = array_values(array_filter($filas, fn($f) => $f['cliente'] === 'Leo'));
        $this->assertCount(2, $filasLeo);
        $this->assertEquals(1, $filasLeo[0]['numero']);
        $this->assertEquals('1/8', $filasLeo[0]['numero_pago']);
        $this->assertEquals(950.00, $filasLeo[0]['total']);
        $this->assertEquals(2, $filasLeo[1]['numero']);
        $this->assertEquals('2/8', $filasLeo[1]['numero_pago']);
        $this->assertEquals(300.00, $filasLeo[1]['recargos']);
        $this->assertEquals(2181.25, $filasLeo[1]['total']);

        // Maria Garcia tiene ÚNICAMENTE 1 fila (asignada en el ciclo actual, 0 multas, con bonificación de comisión)
        $filasMaria = array_values(array_filter($filas, fn($f) => $f['cliente'] === 'Maria Garcia'));
        $this->assertCount(1, $filasMaria);
        $this->assertEquals(1, $filasMaria[0]['numero']);
        $this->assertEquals('1/8', $filasMaria[0]['numero_pago']);
        $this->assertEquals(18.75, $filasMaria[0]['comision']);
        $this->assertEquals(950.00, $filasMaria[0]['pago']);
        $this->assertEquals(0.00, $filasMaria[0]['recargos']);
        $this->assertEquals(931.25, $filasMaria[0]['total']);
    }

    public function test_simular_corte_marca_fecha_exacta_y_segundo_vale_posterior_no_se_ve_afectado()
    {
        Configuracion::actual()->update(['comision_cobre' => 3.00]);

        $distribuidora = \App\Models\User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'limite_credito' => 50000.00,
            'categoria_distribuidor' => 'cobre',
            'multas' => 0.00,
        ]);

        $clienteLeo = $this->crearClienteTest('Leo', $distribuidora);
        $clienteMaria = $this->crearClienteTest('Maria Garcia', $distribuidora);

        $producto = \App\Models\ProductoVale::firstOrCreate(['clave' => 'VALE-5K-EXACT-TEST'], [
            'nombre' => '5/8',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $service = app(\App\Services\CorteCobranzaService::class);

        // 1. Leo es creado y cobrado en caja a las 12:00:00
        $t1 = \Carbon\Carbon::parse('2026-08-28 12:00:00');
        \Carbon\Carbon::setTestNow($t1);

        $prestamoLeo = \App\Models\Prestamo::create([
            'referencia' => 'VALE-LEO-EXACT',
            'cliente_id' => $clienteLeo->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'entregado_at' => $t1,
            'created_by_user_id' => $distribuidora->id,
        ]);

        // 2. Se presiona el botón "Simular Corte" a las 12:05:00
        $t2 = \Carbon\Carbon::parse('2026-08-28 12:05:00');
        \Carbon\Carbon::setTestNow($t2);
        $service->simularSiguienteCorte();

        // Leo recibió su multa por no pagar en corte 1
        $prestamoLeo->refresh();
        $this->assertEquals(300.00, floatval($prestamoLeo->multas));

        // 3. Después del corte, a las 12:10:00 se asigna y cobra vale a Maria Garcia
        $t3 = \Carbon\Carbon::parse('2026-08-28 12:10:00');
        \Carbon\Carbon::setTestNow($t3);

        $prestamoMaria = \App\Models\Prestamo::create([
            'referencia' => 'VALE-MARIA-EXACT',
            'cliente_id' => $clienteMaria->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'entregado_at' => $t3,
            'created_by_user_id' => $distribuidora->id,
        ]);

        // 4. Se genera la relación de cobranza en el ciclo activo
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        // Leo tiene 2 filas (estuvo antes del corte)
        $filasLeo = array_values(array_filter($filas, fn($f) => $f['cliente'] === 'Leo'));
        $this->assertCount(2, $filasLeo);
        $this->assertEquals(1, $filasLeo[0]['numero']);
        $this->assertEquals('1/8', $filasLeo[0]['numero_pago']);
        $this->assertEquals(950.00, $filasLeo[0]['total']);
        $this->assertEquals(2, $filasLeo[1]['numero']);
        $this->assertEquals('2/8', $filasLeo[1]['numero_pago']);
        $this->assertEquals(300.00, $filasLeo[1]['recargos']);
        $this->assertEquals(2181.25, $filasLeo[1]['total']);

        // Maria Garcia tiene ÚNICAMENTE 1 fila limpia (entregada posterior al corte)
        $filasMaria = array_values(array_filter($filas, fn($f) => $f['cliente'] === 'Maria Garcia'));
        $this->assertCount(1, $filasMaria);
        $this->assertEquals(1, $filasMaria[0]['numero']);
        $this->assertEquals('1/8', $filasMaria[0]['numero_pago']);
        $this->assertEquals(18.75, $filasMaria[0]['comision']);
        $this->assertEquals(950.00, $filasMaria[0]['pago']);
        $this->assertEquals(0.00, $filasMaria[0]['recargos']);
        $this->assertEquals(931.25, $filasMaria[0]['total']);

        \Carbon\Carbon::setTestNow(); // reset
    }

    public function test_abono_individual_a_vale_con_tres_cortes_liquida_ultimo_corte_a_cero_y_suma_total_solo_restante()
    {
        Configuracion::actual()->update(['comision_cobre' => 3.00]);

        $distribuidora = \App\Models\User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'limite_credito' => 50000.00,
            'categoria_distribuidor' => 'cobre',
            'multas' => 0.00,
        ]);

        $cajero = \App\Models\User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $clienteLeo = $this->crearClienteTest('Leo', $distribuidora);
        $clienteMaria = $this->crearClienteTest('Maria Garcia', $distribuidora);

        $producto = \App\Models\ProductoVale::firstOrCreate(['clave' => 'VALE-5K-EXACT-ABONO'], [
            'nombre' => '5/8',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $service = app(\App\Services\CorteCobranzaService::class);

        // 1. Maria tiene 3 cortes (creada hace 35 días)
        $t0 = \Carbon\Carbon::parse('2026-08-01 10:00:00');
        \Carbon\Carbon::setTestNow($t0);
        $prestamoMaria = \App\Models\Prestamo::create([
            'referencia' => 'VALE-MARIA-3C',
            'cliente_id' => $clienteMaria->id,
            'producto_vale_id' => $producto->id,
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 600.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'entregado_at' => $t0,
            'created_at' => $t0,
            'created_by_user_id' => $distribuidora->id,
        ]);

        // Simular corte 1
        $t1 = \Carbon\Carbon::parse('2026-08-10 12:00:00');
        \Carbon\Carbon::setTestNow($t1);
        \App\Models\RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => $t1,
            'fecha_limite_pago' => $t1->copy()->addDays(5),
            'monto_total_periodo' => 950.00,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 950.00,
            'estado_pago' => 'pago_atrasado',
        ]);

        // 2. Leo es creado antes del corte 2
        $t2 = \Carbon\Carbon::parse('2026-08-15 10:00:00');
        \Carbon\Carbon::setTestNow($t2);
        $prestamoLeo = \App\Models\Prestamo::create([
            'referencia' => 'VALE-LEO-2C',
            'cliente_id' => $clienteLeo->id,
            'producto_vale_id' => $producto->id,
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 300.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'entregado_at' => $t2,
            'created_at' => $t2,
            'created_by_user_id' => $distribuidora->id,
        ]);

        // Simular corte 2
        $t2_corte = \Carbon\Carbon::parse('2026-08-25 12:00:00');
        \Carbon\Carbon::setTestNow($t2_corte);
        \App\Models\RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => $t2_corte,
            'fecha_limite_pago' => $t2_corte->copy()->addDays(5),
            'monto_total_periodo' => 3131.25,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 3131.25,
            'estado_pago' => 'pago_atrasado',
        ]);

        // 3. Corte activo 3 (2026-08-28)
        $t3 = \Carbon\Carbon::parse('2026-08-28 12:00:00');
        \Carbon\Carbon::setTestNow($t3);
        $fechaCorteActiva = $t3->copy()->addDays(12);
        $fechaLimiteActiva = $t3->copy()->addDays(17);
        Configuracion::actual()->update([
            'fecha_corte' => $fechaCorteActiva,
            'fecha_limite_pago' => $fechaLimiteActiva,
        ]);
        $relacionActiva = \App\Models\RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => $fechaCorteActiva,
            'fecha_limite_pago' => $fechaLimiteActiva,
            'monto_total_periodo' => 5593.75,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 5593.75,
            'estado_pago' => 'pendiente',
        ]);

        // ANTES DE PAGAR:
        // Maria tiene 3 filas ($950, $2,181.25, $3,412.50)
        // Leo tiene 2 filas ($950, $2,181.25)
        $filasAntes = $service->generarFilasRelacionCobranza($distribuidora, $relacionActiva);
        $filasMariaAntes = array_values(array_filter($filasAntes, fn($f) => $f['cliente'] === 'Maria Garcia'));
        $this->assertCount(3, $filasMariaAntes);
        $this->assertEquals(950.00, $filasMariaAntes[0]['total']);
        $this->assertEquals(2181.25, $filasMariaAntes[1]['total']);
        $this->assertEquals(3412.50, $filasMariaAntes[2]['total']);

        $filasLeoAntes = array_values(array_filter($filasAntes, fn($f) => $f['cliente'] === 'Leo'));
        $this->assertCount(2, $filasLeoAntes);
        $this->assertEquals(950.00, $filasLeoAntes[0]['total']);
        $this->assertEquals(2181.25, $filasLeoAntes[1]['total']);

        // 4. El cajero cobra exactamente $3,412.50 al vale de Maria Garcia
        $response = $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoMaria), [
            'monto_abonado' => 3412.50,
            'metodo_pago' => 'transferencia',
            'observaciones' => 'Pago total exigible según relación',
        ]);
        $response->assertSessionHasNoErrors();

        // 5. DESPUÉS DE PAGAR:
        // Maria fila 3 debe dar 0.00
        // Leo fila 2 debe conservar 2181.25
        // El total general debe ser 0.00 + 2181.25 = 2181.25 (o 2181.00 en pdf)
        $filasDespues = $service->generarFilasRelacionCobranza($distribuidora, $relacionActiva);

        $filasMariaDespues = array_values(array_filter($filasDespues, fn($f) => $f['cliente'] === 'Maria Garcia'));
        $this->assertCount(3, $filasMariaDespues);
        $this->assertEquals(950.00, $filasMariaDespues[0]['total']);
        $this->assertEquals(2181.25, $filasMariaDespues[1]['total']);
        $this->assertEquals(0.00, $filasMariaDespues[2]['total'], 'El corte 3 de Maria debe ser 0.00 tras pagar sus 3412.50');

        $filasLeoDespues = array_values(array_filter($filasDespues, fn($f) => $f['cliente'] === 'Leo'));
        $this->assertCount(2, $filasLeoDespues);
        $this->assertEquals(950.00, $filasLeoDespues[0]['total']);
        $this->assertEquals(2181.25, $filasLeoDespues[1]['total'], 'Leo debe mantener su total de 2181.25 sin verse afectado por el abono de Maria');

        // Suma de últimas filas
        $filasPorPrestamo = [];
        foreach ($filasDespues as $f) {
            $filasPorPrestamo[$f['prestamo_id']][] = $f;
        }
        $totalGeneralSum = 0;
        foreach ($filasPorPrestamo as $pId => $filasP) {
            $ultimaFila = end($filasP);
            $totalGeneralSum += max(0.0, floatval($ultimaFila['total']));
        }
        $this->assertEquals(2181.25, $totalGeneralSum);
        $this->assertEquals(2181.00, floor($totalGeneralSum));

        // 6. Se cobra también el vale de Leo en el corte 3 por $2,181.25
        $responseLeo = $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoLeo), [
            'monto_abonado' => 2181.25,
            'metodo_pago' => 'efectivo',
            'observaciones' => 'Pago total Leo corte 2',
        ]);
        $responseLeo->assertSessionHasNoErrors();

        // 7. SE SIMULA UN CORTE MÁS (Corte 4 - 2026-09-12)
        // Cierra la relación 3 y avanza al periodo 4
        $relacionActiva->update([
            'fecha_corte' => $t3,
            'estado_pago' => 'pago_a_tiempo',
            'monto_pagado' => 5593.75,
            'adeudo_pendiente' => 0.00,
        ]);

        $t4 = \Carbon\Carbon::parse('2026-09-12 12:00:00');
        \Carbon\Carbon::setTestNow($t4);
        $fechaCorteActiva4 = $t4->copy()->addDays(12);
        $fechaLimiteActiva4 = $t4->copy()->addDays(17);
        Configuracion::actual()->update([
            'fecha_corte' => $fechaCorteActiva4,
            'fecha_limite_pago' => $fechaLimiteActiva4,
        ]);

        $relacionActiva4 = \App\Models\RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => $fechaCorteActiva4,
            'fecha_limite_pago' => $fechaLimiteActiva4,
            'monto_total_periodo' => 1862.50,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 1862.50,
            'estado_pago' => 'pendiente',
        ]);

        // 8. Se cobran los abonos de la quincena regular en Corte 4: $931.25 cada uno
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoMaria), [
            'monto_abonado' => 931.25,
            'metodo_pago' => 'efectivo',
        ]);
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoLeo), [
            'monto_abonado' => 931.25,
            'metodo_pago' => 'efectivo',
        ]);

        // 9. VERIFICACIÓN EN CORTE 4 TRAS PAGOS REGULARES
        $filasCorte4 = $service->generarFilasRelacionCobranza($distribuidora, $relacionActiva4);

        // Maria Garcia: 4 filas (1: 950.00, 2: 2181.25, 3: 0.00, 4: 0.00 con recargos 0.00)
        $filasMariaCorte4 = array_values(array_filter($filasCorte4, fn($f) => $f['cliente'] === 'Maria Garcia'));
        $this->assertCount(4, $filasMariaCorte4);
        $this->assertEquals(950.00, $filasMariaCorte4[0]['total']);
        $this->assertEquals(2181.25, $filasMariaCorte4[1]['total']);
        $this->assertEquals(0.00, $filasMariaCorte4[2]['total']);
        $this->assertEquals('4/8', $filasMariaCorte4[3]['numero_pago']);
        $this->assertEquals(0.00, $filasMariaCorte4[3]['recargos']);
        $this->assertEquals(0.00, $filasMariaCorte4[3]['total'], 'El corte 4 de Maria debe ser 0.00 tras pagar sus 931.25');

        // Leo: 3 filas (1: 950.00, 2: 0.00, 3: 0.00 con recargos 0.00)
        $filasLeoCorte4 = array_values(array_filter($filasCorte4, fn($f) => $f['cliente'] === 'Leo'));
        $this->assertCount(3, $filasLeoCorte4);
        $this->assertEquals(950.00, $filasLeoCorte4[0]['total']);
        $this->assertEquals(0.00, $filasLeoCorte4[1]['total']);
        $this->assertEquals('3/8', $filasLeoCorte4[2]['numero_pago']);
        $this->assertEquals(0.00, $filasLeoCorte4[2]['recargos']);
        $this->assertEquals(0.00, $filasLeoCorte4[2]['total'], 'El corte 3 de Leo debe ser 0.00 tras pagar sus 931.25');

        // Total general en corte 4 debe ser 0.00
        $filasPorPrestamo4 = [];
        foreach ($filasCorte4 as $f) {
            $filasPorPrestamo4[$f['prestamo_id']][] = $f;
        }
        $totalGeneralSum4 = 0;
        foreach ($filasPorPrestamo4 as $pId => $filasP) {
            $ultimaFila = end($filasP);
            $totalGeneralSum4 += max(0.0, floatval($ultimaFila['total']));
        }
        $this->assertEquals(0.00, $totalGeneralSum4);

        \Carbon\Carbon::setTestNow(); // reset
    }

    public function test_usuario_liquida_primer_pago_hace_corte_muestra_segundo_pago_sin_recargos_y_total_931()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-08-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        Configuracion::actual()->update([
            'comision_oro' => 1.50,
            'comision_plata' => 1.50,
            'comision_cobre' => 1.50,
            'multa_mora_distribuidora' => 300.00,
            'fecha_corte' => $tiempoInicial->copy()->addDays(5),
            'fecha_limite_pago' => $tiempoInicial->copy()->addDays(10),
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $clienteLeo = $this->crearClienteTest('Leo', $distribuidora);
        $productoVale = ProductoVale::firstOrCreate(['clave' => 'VALE-8Q-5'], [
            'nombre' => '5/8',
            'monto_prestamo' => 10000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'comision_distribuidor' => 1.50,
            'activo' => true,
        ]);

        // 1. Vale de Leo creado en corte 1
        $prestamoLeo = Prestamo::create([
            'referencia' => 'VALE-LEO-1',
            'cliente_id' => $clienteLeo->id,
            'producto_vale_id' => $productoVale->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 10000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 0,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
            'created_at' => $tiempoInicial,
        ]);

        // Relación corte 1
        $relacion1 = RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => $tiempoInicial->copy()->addDays(5),
            'fecha_limite_pago' => $tiempoInicial->copy()->addDays(10),
            'monto_total_periodo' => 931.25,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 931.25,
            'estado_pago' => 'pendiente',
        ]);

        $service = app(CorteCobranzaService::class);

        // 2. Antes de pagar en corte 1: Fila muestra 931.25
        $filasAntes = $service->generarFilasRelacionCobranza($distribuidora, $relacion1);
        $this->assertCount(1, $filasAntes);
        $this->assertEquals(931.25, $filasAntes[0]['total']);

        // 3. Pagar el primer corte puntualmente ($931.25)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoLeo), [
            'monto_abonado' => 931.25,
            'metodo_pago' => 'efectivo',
        ]);

        // 4. Simular el corte (cerrar corte 1 y avanzar a corte 2)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(5));
        $service->simularSiguienteCorte();

        $relacion2 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
            ->where('estado_pago', 'pendiente')
            ->latest('fecha_corte')
            ->first();

        // 5. En corte 2:
        // Fila 1 (1/8): 931.25 (pago puntual)
        // Fila 2 (2/8): 931.25 (cuota neta del nuevo corte, 0 recargos)
        // Total relación: 931.25 (o 931.00 en pdf)
        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora, $relacion2);
        $this->assertCount(2, $filasCorte2);

        $this->assertEquals('1/8', $filasCorte2[0]['numero_pago']);
        $this->assertEquals(18.75, $filasCorte2[0]['comision']);
        $this->assertEquals(950.00, $filasCorte2[0]['pago']);
        $this->assertEquals(0.00, $filasCorte2[0]['recargos']);
        $this->assertEquals(931.25, $filasCorte2[0]['total']);

        $this->assertEquals('2/8', $filasCorte2[1]['numero_pago']);
        $this->assertEquals(18.75, $filasCorte2[1]['comision']);
        $this->assertEquals(950.00, $filasCorte2[1]['pago']);
        $this->assertEquals(0.00, $filasCorte2[1]['recargos']);
        $this->assertEquals(931.25, $filasCorte2[1]['total']);

        // Calcular total general de la relación
        $filasPorPrestamo = [];
        foreach ($filasCorte2 as $f) {
            $filasPorPrestamo[$f['prestamo_id']][] = $f;
        }
        $totalGeneralSum = 0;
        foreach ($filasPorPrestamo as $pId => $filasP) {
            $ultimaFila = end($filasP);
            $totalGeneralSum += max(0.0, floatval($ultimaFila['total']));
        }
        $this->assertEquals(931.25, $totalGeneralSum);
        $this->assertEquals(931.00, floor($totalGeneralSum));

        \Carbon\Carbon::setTestNow();
    }

    public function test_pago_vale_maria_no_leo_multa_solo_a_leo_totales_2181_y_931()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-08-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        Configuracion::actual()->update([
            'comision_oro' => 1.50,
            'comision_plata' => 1.50,
            'comision_cobre' => 1.50,
            'multa_mora_distribuidora' => 300.00,
            'fecha_corte' => $tiempoInicial->copy()->addDays(5),
            'fecha_limite_pago' => $tiempoInicial->copy()->addDays(10),
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $clienteLeo = $this->crearClienteTest('Leo', $distribuidora);
        $clienteMaria = $this->crearClienteTest('Maria Garcia', $distribuidora);

        $productoVale = ProductoVale::firstOrCreate(['clave' => 'VALE-8Q-5'], [
            'nombre' => '5/8',
            'monto_prestamo' => 10000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'comision_distribuidor' => 1.50,
            'activo' => true,
        ]);

        // Vale de Leo (Corte 1)
        $prestamoLeo = Prestamo::create([
            'referencia' => 'VALE-LEO-1',
            'cliente_id' => $clienteLeo->id,
            'producto_vale_id' => $productoVale->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 10000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 0,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
            'created_at' => $tiempoInicial,
        ]);

        // Vale de Maria (Corte 1)
        $prestamoMaria = Prestamo::create([
            'referencia' => 'VALE-MARIA-1',
            'cliente_id' => $clienteMaria->id,
            'producto_vale_id' => $productoVale->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 10000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 0,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
            'created_at' => $tiempoInicial,
        ]);

        $relacion1 = RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => $tiempoInicial->copy()->addDays(5),
            'fecha_limite_pago' => $tiempoInicial->copy()->addDays(10),
            'monto_total_periodo' => 1862.50,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 1862.50,
            'estado_pago' => 'pendiente',
        ]);

        // 1. Pagar el vale de Maria ($931.25) pero NO el de Leo en corte 1
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoMaria), [
            'monto_abonado' => 931.25,
            'metodo_pago' => 'efectivo',
        ]);

        // 2. Simular corte (Avanzar a Corte 2)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(5));
        $service = app(CorteCobranzaService::class);
        $service->simularSiguienteCorte();

        $prestamoLeo->refresh();
        $prestamoMaria->refresh();

        // Leo debe tener 1 multa de $300.00, Maria debe tener $0.00 de multa
        $this->assertEquals(300.00, floatval($prestamoLeo->multas), 'Leo debe tener 300 de recargo por no pagar');
        $this->assertEquals(0.00, floatval($prestamoMaria->multas), 'Maria debe tener 0 recargos por haber pagado');

        $relacion2 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
            ->where('estado_pago', 'pendiente')
            ->latest('fecha_corte')
            ->first();

        // 3. Generar filas de relación de cobranza
        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora, $relacion2);

        $filasLeo = array_values(array_filter($filasCorte2, fn($f) => $f['cliente'] === 'Leo'));
        $this->assertCount(2, $filasLeo);
        $this->assertEquals('1/8', $filasLeo[0]['numero_pago']);
        $this->assertEquals(950.00, $filasLeo[0]['total']);
        $this->assertEquals('2/8', $filasLeo[1]['numero_pago']);
        $this->assertEquals(300.00, $filasLeo[1]['recargos']);
        $this->assertEquals(2181.25, $filasLeo[1]['total'], 'El total del vale de Leo debe ser 2,181.25');

        $filasMaria = array_values(array_filter($filasCorte2, fn($f) => $f['cliente'] === 'Maria Garcia'));
        $this->assertCount(2, $filasMaria);
        $this->assertEquals('1/8', $filasMaria[0]['numero_pago']);
        $this->assertEquals(931.25, $filasMaria[0]['total']);
        $this->assertEquals('2/8', $filasMaria[1]['numero_pago']);
        $this->assertEquals(0.00, $filasMaria[1]['recargos']);
        $this->assertEquals(931.25, $filasMaria[1]['total'], 'El total del vale de Maria debe ser 931.25');

        \Carbon\Carbon::setTestNow();
    }

    public function test_pago_excedente_de_un_peso_muestra_menos_uno_y_descuenta_siguiente_corte()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-08-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        Configuracion::actual()->update([
            'comision_oro' => 1.50,
            'comision_plata' => 1.50,
            'comision_cobre' => 1.50,
            'multa_mora_distribuidora' => 300.00,
            'fecha_corte' => $tiempoInicial->copy()->addDays(5),
            'fecha_limite_pago' => $tiempoInicial->copy()->addDays(10),
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $clienteLeo = $this->crearClienteTest('Leo', $distribuidora);
        $productoVale = ProductoVale::firstOrCreate(['clave' => 'VALE-8Q-5'], [
            'nombre' => '5/8',
            'monto_prestamo' => 10000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'comision_distribuidor' => 1.50,
            'activo' => true,
        ]);

        $prestamoLeo = Prestamo::create([
            'referencia' => 'VALE-LEO-1',
            'cliente_id' => $clienteLeo->id,
            'producto_vale_id' => $productoVale->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 10000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 0,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
            'created_at' => $tiempoInicial,
        ]);

        $relacion1 = RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => $tiempoInicial->copy()->addDays(5),
            'fecha_limite_pago' => $tiempoInicial->copy()->addDays(10),
            'monto_total_periodo' => 931.25,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 931.25,
            'estado_pago' => 'pendiente',
        ]);

        // 1. Pagar $931.25 en corte 1
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoLeo), [
            'monto_abonado' => 931.25,
            'metodo_pago' => 'efectivo',
        ]);

        // 2. Avanzar a Corte 2
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(5));
        $service = app(CorteCobranzaService::class);
        $service->simularSiguienteCorte();

        $relacion2 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
            ->where('estado_pago', 'pendiente')
            ->latest('fecha_corte')
            ->first();

        // 3. En Corte 2: Pagar $932.25 (excedente de $1.00 sobre la cuota neta de $931.25)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoLeo), [
            'monto_abonado' => 932.25,
            'metodo_pago' => 'efectivo',
        ]);

        // 4. Ver filas en Corte 2:
        // Fila 1 (1/8): 931.25
        // Fila 2 (2/8): -1.00 (saldo a favor de $1.00)
        // Total general: 0.00
        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora, $relacion2);
        $this->assertCount(2, $filasCorte2);
        $this->assertEquals(931.25, $filasCorte2[0]['total']);
        $this->assertEquals(-1.00, $filasCorte2[1]['total'], 'La fila 2/8 debe mostrar -1.00');

        $totalGeneralSum2 = 0;
        foreach ($filasCorte2 as $f) {
            $totalGeneralSum2 = max(0.0, floatval($f['total']));
        }
        $this->assertEquals(0.00, $totalGeneralSum2, 'El total general debe dar 0.00');

        // 5. Avanzar a Corte 3
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(20));
        $service->simularSiguienteCorte();

        $relacion3 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
            ->where('estado_pago', 'pendiente')
            ->latest('fecha_corte')
            ->first();

        // 6. En Corte 3 (sin nuevo pago aún):
        // Fila 1 (1/8): 931.25
        // Fila 2 (2/8): -1.00
        // Fila 3 (3/8): 930.25 (931.25 - 1.00 de crédito a favor)
        $filasCorte3 = $service->generarFilasRelacionCobranza($distribuidora, $relacion3);
        $this->assertCount(3, $filasCorte3);
        $this->assertEquals(931.25, $filasCorte3[0]['total']);
        $this->assertEquals(-1.00, $filasCorte3[1]['total']);
        $this->assertEquals(930.25, $filasCorte3[2]['total'], 'La fila 3/8 debe tener el descuento del peso excedente (930.25)');

        \Carbon\Carbon::setTestNow();
    }
}
