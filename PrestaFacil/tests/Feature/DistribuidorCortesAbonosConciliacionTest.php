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
        $service->simularSiguienteCorte();
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
        $this->assertEquals(4120.00, $filas[4]['total']);

        $this->assertEquals('4/4', $filas[5]['numero_pago']);
        $this->assertEquals(0.00, $filas[5]['comision']);
        $this->assertEquals(0.00, $filas[5]['pago']);
        $this->assertEquals(20.00, $filas[5]['recargos']);
        $this->assertEquals(4140.00, $filas[5]['total']);
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

        // Fila 8: 8/8 con 9681.25
        $fila8 = $filas[7];
        $this->assertEquals(8, $fila8['numero']);
        $this->assertEquals('8/8', $fila8['numero_pago']);
        $this->assertEquals(9681.25, $fila8['total']);

        // Fila 9: 9, vale 1, Juan, 8/8, 00.00, 00.00, 300.00, 10000.00 (9700.00 + 300.00)
        $fila9 = $filas[8];
        $this->assertEquals(9, $fila9['numero']);
        $this->assertEquals('vale 1', $fila9['producto']);
        $this->assertEquals('Juan', $fila9['cliente']);
        $this->assertEquals('8/8', $fila9['numero_pago']);
        $this->assertEquals(0.00, $fila9['comision']);
        $this->assertEquals(0.00, $fila9['pago']);
        $this->assertEquals(300.00, $fila9['recargos']);
        $this->assertEquals(10000.00, $fila9['total']);
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
        $filasAntes = $service->generarFilasRelacionCobranza($distribuidora);

        // Al cobrar el vale antes del corte debe verse vacío (0 filas para este nuevo vale)
        $this->assertCount(0, $filasAntes);

        // Al procesar el primer corte (simulación)
        $service->simularSiguienteCorte();
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        // Debe tener exactamente 1 fila (Corte 1 / 1/8) sin multas
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

        // 2. Se presiona el botón "Simular Corte" a las 12:05:00 (Primer corte para Leo)
        $t2 = \Carbon\Carbon::parse('2026-08-28 12:05:00');
        \Carbon\Carbon::setTestNow($t2);
        $service->simularSiguienteCorte();

        // En su primer corte, Leo se refleja sin multas ($931.25)
        $prestamoLeo->refresh();
        $this->assertEquals(0.00, floatval($prestamoLeo->multas));

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

        // 4. Se ejecuta el segundo corte a las 12:15:00
        $t4 = \Carbon\Carbon::parse('2026-08-28 12:15:00');
        \Carbon\Carbon::setTestNow($t4);
        $service->simularSiguienteCorte();

        // Ahora Leo sí recibe su multa de $300 por haber vencido el corte 1 impago
        $prestamoLeo->refresh();
        $this->assertEquals(300.00, floatval($prestamoLeo->multas));

        // Maria en su primer corte tiene $0.00 de multa
        $prestamoMaria->refresh();
        $this->assertEquals(0.00, floatval($prestamoMaria->multas));

        // 5. Se genera la relación de cobranza en el ciclo activo
        $filas = $service->generarFilasRelacionCobranza($distribuidora);

        // Leo tiene 2 filas (1/8 vencido con arrastre + 2/8 con recargos de $300)
        $filasLeo = array_values(array_filter($filas, fn($f) => $f['cliente'] === 'Leo'));
        $this->assertCount(2, $filasLeo);
        $this->assertEquals(1, $filasLeo[0]['numero']);
        $this->assertEquals('1/8', $filasLeo[0]['numero_pago']);
        $this->assertEquals(950.00, $filasLeo[0]['total']);
        $this->assertEquals(2, $filasLeo[1]['numero']);
        $this->assertEquals('2/8', $filasLeo[1]['numero_pago']);
        $this->assertEquals(300.00, $filasLeo[1]['recargos']);
        $this->assertEquals(2181.25, $filasLeo[1]['total']);

        // Maria Garcia tiene ÚNICAMENTE 1 fila limpia (primer corte sin multas)
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
        $this->assertEquals(3431.25, $filasMariaAntes[2]['total']);

        $filasLeoAntes = array_values(array_filter($filasAntes, fn($f) => $f['cliente'] === 'Leo'));
        $this->assertCount(2, $filasLeoAntes);
        $this->assertEquals(950.00, $filasLeoAntes[0]['total']);
        $this->assertEquals(2181.25, $filasLeoAntes[1]['total']);

        // 4. El cajero cobra exactamente $3,431.25 al vale de Maria Garcia
        $response = $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoMaria), [
            'monto_abonado' => 3431.25,
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
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(20));
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
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(20));
        $service = app(CorteCobranzaService::class);
        $service->simularSiguienteCorte();

        $prestamoLeo->refresh();
        $prestamoMaria->refresh();

        // Leo debe tener 1 multa de $300.00, Maria debe tener $0.00 de multa
        $this->assertEquals(300.00, floatval($prestamoLeo->multas), 'Leo debe tener 300 de recargo por no pagar');
        $this->assertEquals(0.00, floatval($prestamoMaria->multas), 'Maria debe tener 0 recargos por haber pagado');

        $relacion2 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)
            ->where('fecha_corte', '<=', now())
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
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(20));
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
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(35));
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

    public function test_cliente_con_atrasos_sucesivos_suma_comisiones_perdidas_3431_y_siguientes()
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
            'monto_total_periodo' => 931.25,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 931.25,
            'estado_pago' => 'pendiente',
        ]);

        $service = app(CorteCobranzaService::class);

        // Corte 1 activo sin pagar -> $931.25 (cuota neta a tiempo)
        $filas1 = $service->generarFilasRelacionCobranza($distribuidora, $relacion1);
        $this->assertEquals(931.25, $filas1[0]['total']);

        // Simular Corte 1 -> Avanzar a Corte 2
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(20));
        $service->simularSiguienteCorte();
        $relacion2 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)->where('fecha_corte', '<=', now())->latest('fecha_corte')->first();

        // Corte 2 sin pagar -> Fila 1: 950.00, Fila 2: 2,181.25 (950 + 931.25 + 300)
        $filas2 = $service->generarFilasRelacionCobranza($distribuidora, $relacion2);
        $this->assertCount(2, $filas2);
        $this->assertEquals(950.00, $filas2[0]['total']);
        $this->assertEquals(2181.25, $filas2[1]['total']);

        // Simular Corte 2 -> Avanzar a Corte 3
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(35));
        $service->simularSiguienteCorte();
        $relacion3 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)->where('fecha_corte', '<=', now())->latest('fecha_corte')->first();

        // Corte 3 sin pagar -> Fila 1: 950.00, Fila 2: 2,181.25, Fila 3: 3,431.25 (2200 + 931.25 + 300)
        $filas3 = $service->generarFilasRelacionCobranza($distribuidora, $relacion3);
        $this->assertCount(3, $filas3);
        $this->assertEquals(950.00, $filas3[0]['total']);
        $this->assertEquals(2181.25, $filas3[1]['total']);
        $this->assertEquals(3431.25, $filas3[2]['total'], 'El corte 3 debe ser 3,431.25 al sumar la comision perdida');

        // Simular Corte 3 -> Avanzar a Corte 4
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(50));
        $service->simularSiguienteCorte();
        $relacion4 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)->where('fecha_corte', '<=', now())->latest('fecha_corte')->first();

        // Corte 4 sin pagar -> Fila 4: 4,681.25 (3450 + 931.25 + 300)
        $filas4 = $service->generarFilasRelacionCobranza($distribuidora, $relacion4);
        $this->assertCount(4, $filas4);
        $this->assertEquals(4681.25, $filas4[3]['total'], 'El corte 4 debe ser 4,681.25');

        // Simular Corte 4 -> Avanzar a Corte 5
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(65));
        $service->simularSiguienteCorte();
        $relacion5 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)->where('fecha_corte', '<=', now())->latest('fecha_corte')->first();

        // Corte 5 sin pagar -> Fila 5: 5,931.25 (4700 + 931.25 + 300)
        $filas5 = $service->generarFilasRelacionCobranza($distribuidora, $relacion5);
        $this->assertCount(5, $filas5);
        $this->assertEquals(5931.25, $filas5[4]['total'], 'El corte 5 debe ser 5,931.25');

        \Carbon\Carbon::setTestNow();
    }

    public function test_cajero_puede_pagar_saldo_total_pendiente_con_multas_y_comisiones_perdidas()
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

        $cliente = $this->crearClienteTest('Probino Macaquino', $distribuidora);
        $productoVale = ProductoVale::firstOrCreate(['clave' => 'VALE-8Q-5'], [
            'nombre' => '5/8',
            'monto_prestamo' => 10000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'comision_distribuidor' => 1.50,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VALE-PROB-1',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 10000.00,
            'cuota_quincenal' => 950.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 7600.00,
            'adeudo_pendiente' => 5750.00,
            'multas' => 0,
            'estado' => 'activo',
            'created_by_user_id' => $distribuidora->id,
            'created_at' => $tiempoInicial,
        ]);

        RelacionCobranza::create([
            'distribuidora_id' => $distribuidora->id,
            'fecha_corte' => $tiempoInicial->copy()->addDays(5),
            'fecha_limite_pago' => $tiempoInicial->copy()->addDays(10),
            'monto_total_periodo' => 931.25,
            'monto_pagado' => 0.00,
            'adeudo_pendiente' => 931.25,
            'estado_pago' => 'pendiente',
        ]);

        // Simular 2 cortes con atraso (acumula 2 multas de $300 = $600 y comisiones perdidas)
        $service = app(CorteCobranzaService::class);
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(5));
        $service->simularSiguienteCorte();

        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(20));
        $service->simularSiguienteCorte();

        $prestamo->refresh();
        $this->assertEquals(600.00, floatval($prestamo->multas));

        // Total pendiente incluye capital + multas + comisiones perdidas (ej. 6350 o 6537.50)
        $filas = $service->generarFilasRelacionCobranza($distribuidora);
        $filasP = array_values(array_filter($filas, fn($f) => $f['prestamo_id'] == $prestamo->id));
        $comisionVale = 0;
        $multaPrestamo = 0;
        foreach ($filasP as $fp) {
            $comisionVale += floatval($fp['comision']);
            $multaPrestamo += floatval($fp['recargos']);
        }
        $numCortesAtrasados = max(0, count($filasP) - 1);
        $comisionPorQuincena = count($filasP) > 0 ? ($comisionVale / count($filasP)) : 0;
        $comisionesPerdidas = ($numCortesAtrasados > 0 && $multaPrestamo > 0) ? ($numCortesAtrasados * $comisionPorQuincena) : 0.0;
        $totalSaldoPendiente = floatval($prestamo->adeudo_pendiente) + max(floatval($prestamo->multas ?? 0), $multaPrestamo) + $comisionesPerdidas;

        // El cajero abona el totalSaldoPendiente completo
        $response = $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => $totalSaldoPendiente,
            'metodo_pago' => 'transferencia',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $prestamo->refresh();
        $this->assertEquals(0.00, floatval($prestamo->multas));
        $this->assertEquals(0.00, floatval($prestamo->adeudo_pendiente));
        $this->assertEquals('finalizado', $prestamo->estado);

        \Carbon\Carbon::setTestNow();
    }

    public function test_pago_con_centavos_faltantes_o_sobrantes_se_iguala_y_no_genera_excedente_ni_mora()
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

        $cliente = $this->crearClienteTest('Leo Test Centavos', $distribuidora);
        $productoVale = ProductoVale::firstOrCreate(['clave' => 'VALE-8Q-CENT'], [
            'nombre' => '5/8',
            'monto_prestamo' => 10000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'comision_distribuidor' => 1.50,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VALE-LEO-CENT',
            'cliente_id' => $cliente->id,
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

        $service = app(CorteCobranzaService::class);
        $service->simularSiguienteCorte();
        $filas = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertEquals(931.25, $filas[0]['total']);

        // Caso 1: Debe 931.25 y abona 931.00 (faltan 0.25 centavos) -> se iguala y queda en 0.00
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 931.00,
            'metodo_pago' => 'transferencia',
        ]);

        $filasDespues = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertEquals(0.00, $filasDespues[0]['total'], 'El pago de 931.00 sobre 931.25 debe igualarse a 0.00');

        // Simular corte -> Al avanzar al segundo corte, NO debe generar multa ni adeudo
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(5));
        $service->simularSiguienteCorte();

        $prestamo->refresh();
        $this->assertEquals(0.00, floatval($prestamo->multas), 'No debe generar recargos');

        $relacion2 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)->where('fecha_corte', '<=', now())->latest('fecha_corte')->first();
        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora, $relacion2);
        $this->assertCount(2, $filasCorte2);
        $this->assertEquals(931.25, $filasCorte2[0]['total'], 'Corte 1 histórico queda en 931.25 (saldado)');
        $this->assertEquals(931.25, $filasCorte2[1]['total'], 'Corte 2 activo es cuota normal 931.25 sin recargos');

        // Caso 2: Paga 931.50 (sobran 0.25 centavos) -> los centavos no cuentan como excedente
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 931.50,
            'metodo_pago' => 'transferencia',
        ]);

        $filasCorte2Pagado = $service->generarFilasRelacionCobranza($distribuidora, $relacion2);
        $this->assertEquals(0.00, $filasCorte2Pagado[1]['total'], 'El pago de 931.50 sobre 931.25 no debe dejar saldo negativo ni excedente de centavos');

        \Carbon\Carbon::setTestNow();
    }

    public function test_conciliacion_multiple_vales_por_folio_con_efecto_retroactivo_remueve_multas_conserva_comision_y_otorga_puntos()
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
            'meta_colocacion_mensual' => 1000.00,
            'puntos_por_meta' => 100,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'puntos' => 0,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $coordinador = User::factory()->create([
            'rol_id' => $this->rolCoordinador->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $cliente1 = $this->crearClienteTest('Leo Conciliado', $distribuidora);
        $cliente2 = $this->crearClienteTest('Maria Conciliada', $distribuidora);

        $productoVale = ProductoVale::firstOrCreate(['clave' => 'VALE-8Q-CONC'], [
            'nombre' => '5/8',
            'monto_prestamo' => 10000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'comision_distribuidor' => 1.50,
            'activo' => true,
        ]);

        $prestamo1 = Prestamo::create([
            'referencia' => 'VALE-LEO-001',
            'cliente_id' => $cliente1->id,
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

        $prestamo2 = Prestamo::create([
            'referencia' => 'VALE-MARIA-002',
            'cliente_id' => $cliente2->id,
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

        $service = app(CorteCobranzaService::class);

        // Avanzar el tiempo más allá del corte sin registrar pago -> Genera atraso y multas al segundo corte
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(5));
        $service->simularSiguienteCorte();

        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(20));
        $service->simularSiguienteCorte();

        $prestamo1->refresh();
        $prestamo2->refresh();
        $distribuidora->refresh();

        $this->assertEquals(300.00, floatval($prestamo1->multas), 'Leo debe tener 300 de multa por corte vencido');
        $this->assertEquals(300.00, floatval($prestamo2->multas), 'Maria debe tener 300 de multa por corte vencido');
        $this->assertEquals(600.00, floatval($distribuidora->multas), 'Distribuidora acumula 600 de multas');

        // Ahora el cajero registra una conciliación de pago bancario que ocurrió el día 3 (antes del corte del día 5)
        // cubriendo ambos vales ($931.25 a Leo y $931.25 a Maria = $1,862.50)
        $responseSolicitud = $this->actingAs($cajero)->post(route('cajero.conciliaciones.store'), [
            'distribuidora_id' => $distribuidora->id,
            'referencia_original' => 'REF-ERRONEA-BANCO',
            'referencia_conciliacion' => 'REF-CORRECTA-BANCO',
            'fecha_pago' => $tiempoInicial->copy()->addDays(3)->toDateString(),
            'monto_original' => 1862.50,
            'monto_corregido' => 1862.50,
            'motivo' => 'Ficha bancaria con referencia errónea pagada a tiempo el día 3.',
            'prestamos_asignados' => [
                ['prestamo_id' => $prestamo1->id, 'folio' => $prestamo1->referencia, 'monto' => 931.25],
                ['prestamo_id' => $prestamo2->id, 'folio' => $prestamo2->referencia, 'monto' => 931.25],
            ],
        ]);

        $responseSolicitud->assertSessionHasNoErrors();
        $responseSolicitud->assertSessionHas('success');

        $conciliacion = \App\Models\Conciliacion::where('referencia_conciliacion', 'REF-CORRECTA-BANCO')->first();
        $this->assertNotNull($conciliacion);
        $this->assertCount(2, $conciliacion->prestamos_asignados);

        $solicitudAut = SolicitudAutorizacion::where('entidad_id', $conciliacion->id)->first();
        $this->assertNotNull($solicitudAut);

        // El Coordinador aprueba la conciliación
        $responseAprobar = $this->actingAs($coordinador)->post(route('autorizaciones.aprobar', $solicitudAut), [
            'observaciones' => 'Comprobante cotejado con estado de cuenta bancario.',
        ]);
        $responseAprobar->assertRedirect(route('autorizaciones.index'));

        // Verificaciones del efecto retroactivo
        $prestamo1->refresh();
        $prestamo2->refresh();
        $distribuidora->refresh();

        // 1. Multas revertidas a 0.00
        $this->assertEquals(0.00, floatval($prestamo1->multas), 'Multas de Leo deben ser 0 tras conciliación a tiempo');
        $this->assertEquals(0.00, floatval($prestamo2->multas), 'Multas de Maria deben ser 0 tras conciliación a tiempo');
        $this->assertEquals(0.00, floatval($distribuidora->multas), 'Multas de Distribuidora deben ser 0');

        // 2. Pagos creados
        $this->assertEquals(1, $prestamo1->pagos_realizados);
        $this->assertEquals(931.25, floatval($prestamo1->pagos_recibidos));
        $this->assertEquals(1, $prestamo2->pagos_realizados);
        $this->assertEquals(931.25, floatval($prestamo2->pagos_recibidos));

        // 3. Puntos acreditados
        $this->assertGreaterThan(0, $distribuidora->puntos, 'Distribuidora debe recibir sus puntos por haber pagado a tiempo');

        // 4. Relación histórica liquidada
        $relacionCorte1 = RelacionCobranza::where('distribuidora_id', $distribuidora->id)->oldest('fecha_corte')->first();
        $this->assertEquals('pago_a_tiempo', $relacionCorte1->estado_pago);
        $this->assertEquals(0.00, floatval($relacionCorte1->adeudo_pendiente));

        \Carbon\Carbon::setTestNow();
    }

    public function test_conciliacion_notifica_a_gerentes_crea_logs_y_permite_decision_gerencial()
    {
        $rolGG = Rol::firstOrCreate(['nombre' => 'Gerente General']);
        $rolGS = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $gerenteGeneral = User::factory()->create([
            'rol_id' => $rolGG->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $gerenteSucursal = User::factory()->create([
            'rol_id' => $rolGS->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $cliente = $this->crearClienteTest('Cliente Conciliacion Gerente', $distribuidora);
        $productoVale = ProductoVale::firstOrCreate(['clave' => 'VALE-8Q-GER'], [
            'nombre' => '5/8',
            'monto_prestamo' => 10000.00,
            'plazo_quincenas' => 8,
            'cuota_quincenal' => 950.00,
            'multa' => 300.00,
            'comision_distribuidor' => 1.50,
            'activo' => true,
        ]);
        $prestamo = Prestamo::create([
            'referencia' => 'VALE-GER-001',
            'cliente_id' => $cliente->id,
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
        ]);

        // 1. El Cajero solicita la conciliación
        $response = $this->actingAs($cajero)->post(route('cajero.conciliaciones.store'), [
            'distribuidora_id' => $distribuidora->id,
            'referencia_original' => 'REF-BANCO-ERR-1',
            'referencia_conciliacion' => 'REF-BANCO-CORR-1',
            'fecha_pago' => now()->toDateString(),
            'monto_original' => 931.25,
            'monto_corregido' => 931.25,
            'motivo' => 'Depósito bancario no acreditado a tiempo.',
            'prestamos_asignados' => [
                ['prestamo_id' => $prestamo->id, 'folio' => $prestamo->referencia, 'monto' => 931.25],
            ],
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $conciliacion = \App\Models\Conciliacion::where('referencia_conciliacion', 'REF-BANCO-CORR-1')->first();
        $this->assertNotNull($conciliacion);

        // 2. Verificar Notificaciones a Gerente General y Gerente de Sucursal
        $notifGG = NotificacionCajero::where('user_id', $gerenteGeneral->id)
            ->where('tipo', 'conciliacion_solicitada_gerente')
            ->first();
        $this->assertNotNull($notifGG, 'El Gerente General debe recibir notificación de la solicitud');

        $notifGS = NotificacionCajero::where('user_id', $gerenteSucursal->id)
            ->where('tipo', 'conciliacion_solicitada_gerente')
            ->first();
        $this->assertNotNull($notifGS, 'El Gerente de Sucursal debe recibir notificación de la solicitud');

        // 3. Verificar Log 1: Solicitud de Conciliación
        $logSolicitud = \App\Models\AuditLog::where('tipo_operacion', 'CONCILIACION_SOLICITADA')
            ->where('entidad_id', $conciliacion->id)
            ->first();
        $this->assertNotNull($logSolicitud, 'Debe crearse un log de auditoría al solicitar la conciliación');
        $this->assertEquals($cajero->id, $logSolicitud->user_id);

        // 4. El Gerente de Sucursal aprueba la conciliación
        $responseAprobar = $this->actingAs($gerenteSucursal)
            ->post(route('gerente.conciliaciones.decidir', $conciliacion), [
                'accion' => 'aprobar',
                'observaciones' => 'Aprobado tras cotejo con estado de cuenta de sucursal',
            ]);

        $responseAprobar->assertSessionHas('success');

        $conciliacion->refresh();
        $this->assertEquals('conciliado', $conciliacion->estado);
        $this->assertEquals($gerenteSucursal->id, $conciliacion->autorizador_id);

        // 5. Verificar Log 2: Validación de la Conciliación
        $logValidacion = \App\Models\AuditLog::where('tipo_operacion', 'CONCILIACION_VALIDADA')
            ->where('entidad_id', $conciliacion->id)
            ->first();
        $this->assertNotNull($logValidacion, 'Debe crearse un log de auditoría al validar/aprobar la conciliación');
        $this->assertEquals($gerenteSucursal->id, $logValidacion->autorizador_id);

        // 6. Verificar notificación al Cajero
        $notifCajero = NotificacionCajero::where('user_id', $cajero->id)
            ->where('tipo', 'conciliacion_aprobada')
            ->first();
        $this->assertNotNull($notifCajero, 'El cajero solicitante debe recibir notificación de la aprobación');
    }

    public function test_modulo_apartado_conciliaciones_gerencia_vistas_filtros_y_detalle()
    {
        $rolGG = Rol::firstOrCreate(['nombre' => 'Gerente General']);
        $rolGS = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);

        $gerenteGeneral = User::factory()->create([
            'rol_id' => $rolGG->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $gerenteSucursal = User::factory()->create([
            'rol_id' => $rolGS->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
        ]);
        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $conciliacion = \App\Models\Conciliacion::create([
            'solicitante_id' => $cajero->id,
            'distribuidora_id' => $distribuidora->id,
            'referencia_original' => 'REF-ORIG-TEST',
            'referencia_conciliacion' => 'REF-CONC-TEST',
            'fecha_pago' => now(),
            'monto_original' => 1500.00,
            'monto_corregido' => 1500.00,
            'motivo' => 'Revisión gerencial de comprobante',
            'estado' => 'pendiente_gerencia',
        ]);

        // 1. Acceso permitido para Gerente General
        $resGG = $this->actingAs($gerenteGeneral)->get(route('gerente.conciliaciones.index'));
        $resGG->assertStatus(200);
        $resGG->assertSee('Conciliaciones de Pago');
        $resGG->assertSee('REF-CONC-TEST');

        // 2. Acceso permitido para Gerente de Sucursal
        $resGS = $this->actingAs($gerenteSucursal)->get(route('gerente.conciliaciones.index', ['estado' => 'pendientes']));
        $resGS->assertStatus(200);
        $resGS->assertSee('REF-CONC-TEST');

        // 3. Vista de detalle de conciliación
        $resShow = $this->actingAs($gerenteSucursal)->get(route('gerente.conciliaciones.show', $conciliacion));
        $resShow->assertStatus(200);
        $resShow->assertSee('Revisión gerencial de comprobante');
        $resShow->assertSee('Trazabilidad y Logs de Auditoría');

        // 4. Bloqueo para roles no gerenciales (Distribuidor)
        $resDist = $this->actingAs($distribuidora)->get(route('gerente.conciliaciones.index'));
        $resDist->assertRedirect(route('distribuidor.dashboard'));
        $resDist->assertSessionHas('error');

        // 5. Visualización del archivo adjunto
        \Illuminate\Support\Facades\Storage::fake('public');
        $fakeFile = \Illuminate\Http\UploadedFile::fake()->create('comprobante_banco.pdf', 100, 'application/pdf');
        $pathGuardado = $fakeFile->store('evidencias', 'public');
        $conciliacion->update(['evidencia_path' => $pathGuardado]);

        $resArchivo = $this->actingAs($gerenteGeneral)->get(route('conciliaciones.archivo', $conciliacion));
        $resArchivo->assertStatus(200);

        $resArchivoGS = $this->actingAs($gerenteSucursal)->get(route('conciliaciones.archivo', $conciliacion));
        $resArchivoGS->assertStatus(200);
    }

    public function test_log_de_corte_registra_fechas_de_corte_y_limite(): void
    {
        $rolGG = Rol::firstOrCreate(['nombre' => 'Gerente General']);
        $gerenteGeneral = User::factory()->create([
            'rol_id' => $rolGG->id,
            'sucursal_id' => $this->sucursal->id,
            'name' => 'Roberto Gerente',
            'activo' => true,
        ]);

        $config = \App\Models\Configuracion::firstOrCreate([], [
            'dia_corte' => 15,
            'dia_limite_pago' => 20,
            'hora_corte' => '22:00:00',
            'fecha_corte' => now()->addDays(2),
            'fecha_limite_pago' => now()->addDays(7),
        ]);

        $response = $this->actingAs($gerenteGeneral)->post(route('configuracion-general.simular-corte'));
        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('audit_logs', [
            'tipo_operacion' => 'SIMULACION_CORTE',
            'user_id' => $gerenteGeneral->id,
        ]);

        $log = \App\Models\AuditLog::where('tipo_operacion', 'SIMULACION_CORTE')
            ->where('user_id', $gerenteGeneral->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Roberto Gerente', $log->descripcion);
        $this->assertStringContainsString('Fecha de Corte:', $log->descripcion);
        $this->assertStringContainsString('Fecha Límite de Pago:', $log->descripcion);
        $this->assertStringContainsString('Próximo Ciclo:', $log->descripcion);

        $this->assertNotNull($log->datos_despues['fecha_corte']);
        $this->assertNotNull($log->datos_despues['fecha_limite_pago']);
        $this->assertNotNull($log->datos_despues['proxima_fecha_corte']);
        $this->assertNotNull($log->datos_despues['proxima_fecha_limite']);
    }

    public function test_flujo_cobro_vale_vacio_y_primer_corte_sin_multas_y_segundo_corte_con_multas()
    {
        // Caso exacto: Vale $15,000, apertura 10%, interes quincenal 5%, seguro 100, distribuidora Cobre 3%, multa 300, 8 quincenas
        Configuracion::actual()->update(['comision_cobre' => 3.00]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
        ]);

        $cliente = $this->crearClienteTest('Carlos Sanchez', $distribuidora);

        $producto = ProductoVale::firstOrCreate(['clave' => 'VALE-15K-8Q'], [
            'nombre' => 'Vale $15,000 / 8Q',
            'monto_prestamo' => 15000.00,
            'costo_seguro' => 100.00,
            'plazo_quincenas' => 8,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $tiempoInicial = \Carbon\Carbon::parse('2026-08-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        // 1. Cobro/Entrega del vale en caja (Momento 0)
        $prestamo = Prestamo::create([
            'referencia' => 'VALE-15K-001',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $producto->id,
            'tipo' => 'vale_digital',
            'monto_prestamo' => 15000.00,
            'cuota_quincenal' => 2825.00, // ($15000 + $6000 interes + $1500 apertura + $100 seguro) / 8 = $22600 / 8 = $2825.00
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'monto_total_pagar' => 22600.00,
            'adeudo_pendiente' => 22600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'entregado_at' => $tiempoInicial,
            'created_by_user_id' => $distribuidora->id,
            'created_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // MOMENTO 0: Al cobrar el vale en caja, la relación de cobranza para este vale está VACÍA (0 filas)
        $filasMomento0 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(0, $filasMomento0, 'Al cobrar el vale debe verse vacío en la relación');

        // MOMENTO 1: Se ejecuta el 1er corte (Simulación 1 a los 15 días)
        $tiempoCorte1 = $tiempoInicial->copy()->addDays(15);
        \Carbon\Carbon::setTestNow($tiempoCorte1);
        $resCorte1 = $service->simularSiguienteCorte();

        // En el primer corte NO debe aplicar multas
        $prestamo->refresh();
        $this->assertEquals(0, $resCorte1['multas_aplicadas']);
        $this->assertEquals(0.00, floatval($prestamo->multas), 'En el primer corte no hay multas');

        // La relación de cobranza muestra 1 fila con la cuota neta ($2,768.75) sin multas
        $filasCorte1 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(1, $filasCorte1);
        $this->assertEquals(1, $filasCorte1[0]['numero']);
        $this->assertEquals('1/8', $filasCorte1[0]['numero_pago']);
        $this->assertEquals(2825.00, $filasCorte1[0]['pago']);
        $this->assertEquals(56.25, $filasCorte1[0]['comision']); // ($15000 * 3%) / 8 = $56.25
        $this->assertEquals(2768.75, $filasCorte1[0]['cuota_neta']);
        $this->assertEquals(0.00, $filasCorte1[0]['recargos']);
        $this->assertEquals(2768.75, $filasCorte1[0]['total']);

        // MOMENTO 2: Se ejecuta el 2do corte (Simulación 2 a los 30 días) SIN HABER PAGADO EL 1ER CORTE
        $tiempoCorte2 = $tiempoInicial->copy()->addDays(30);
        \Carbon\Carbon::setTestNow($tiempoCorte2);
        $resCorte2 = $service->simularSiguienteCorte();

        // Ahora sí se aplica la multa del primer corte ($300.00)
        $prestamo->refresh();
        $this->assertEquals(1, $resCorte2['multas_aplicadas']);
        $this->assertEquals(300.00, floatval($prestamo->multas), 'Al 2do corte se aplica la multa del 1ro');

        // La relación de cobranza muestra 2 filas (1/8 vencido de $2,825.00 + 2/8 vigente con recargos de $300.00)
        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(2, $filasCorte2);

        // Fila 1 (Corte 1/8 vencido)
        $this->assertEquals('1/8', $filasCorte2[0]['numero_pago']);
        $this->assertEquals(2825.00, $filasCorte2[0]['total']);

        // Fila 2 (Corte 2/8 vigente con multa acumulada: $2825 arrastre + $2768.75 cuota neta + $300 multa = $5893.75)
        $this->assertEquals('2/8', $filasCorte2[1]['numero_pago']);
        $this->assertEquals(300.00, $filasCorte2[1]['recargos']);
        $this->assertEquals(5893.75, $filasCorte2[1]['total']);

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA 1: Pago quincenal completo.
     * Al pagar un vale en su totalidad quincena a quincena, los cortes continúan creándose
     * sucesivamente (1/8 -> 2/8 -> 3/8 -> 4/8) con $0 de multas y total neto exacto.
     */
    public function test_pago_quincenal_completo_continua_haciendo_cortes_consecutivos_sin_multas()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'activo' => true,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);
        $cliente = $this->crearClienteTest('Ana Gomez', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-5000-FULL',
            'nombre' => 'Vale $5,000',
            'monto_prestamo' => 5000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-5000-FULL-01',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'monto_total_pagar' => 7600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // 1. Simular Corte 1
        $service->simularSiguienteCorte();
        $filasCorte1 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(1, $filasCorte1);
        $this->assertEquals('1/8', $filasCorte1[0]['numero_pago']);
        $this->assertEquals(931.25, $filasCorte1[0]['total']);

        // Pagar quincena 1 completa ($931.25)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 931.25,
            'metodo_pago' => 'efectivo',
        ]);

        // 2. Avanzar a Corte 2 (+15 días)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(15));
        $resCorte2 = $service->simularSiguienteCorte();
        $this->assertEquals(0, $resCorte2['multas_aplicadas'], 'No debe haber multas porque se pagó a tiempo el corte 1');

        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(2, $filasCorte2, 'El corte 2 debe mostrar 2 filas (1/8 histórico y 2/8 vigente)');
        $this->assertEquals('1/8', $filasCorte2[0]['numero_pago']);
        $this->assertEquals('2/8', $filasCorte2[1]['numero_pago']);
        $this->assertEquals(0.00, $filasCorte2[1]['recargos']);
        $this->assertEquals(931.25, $filasCorte2[1]['total'], 'La fila 2/8 debe exigir su cuota neta regular de 931.25');

        // Pagar quincena 2 completa ($931.25)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 931.25,
            'metodo_pago' => 'efectivo',
        ]);

        // 3. Avanzar a Corte 3 (+30 días)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(30));
        $resCorte3 = $service->simularSiguienteCorte();
        $this->assertEquals(0, $resCorte3['multas_aplicadas']);

        $filasCorte3 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(3, $filasCorte3, 'El corte 3 debe mostrar 3 filas');
        $this->assertEquals('3/8', $filasCorte3[2]['numero_pago']);
        $this->assertEquals(0.00, $filasCorte3[2]['recargos']);
        $this->assertEquals(931.25, $filasCorte3[2]['total']);

        // Pagar quincena 3 completa ($931.25)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 931.25,
            'metodo_pago' => 'efectivo',
        ]);

        // 4. Avanzar a Corte 4 (+45 días)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(45));
        $resCorte4 = $service->simularSiguienteCorte();
        $this->assertEquals(0, $resCorte4['multas_aplicadas']);

        $filasCorte4 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(4, $filasCorte4, 'El corte 4 debe mostrar 4 filas');
        $this->assertEquals('4/8', $filasCorte4[3]['numero_pago']);
        $this->assertEquals(0.00, $filasCorte4[3]['recargos']);
        $this->assertEquals(931.25, $filasCorte4[3]['total']);

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA 2: Adeudo quincenal total (0 pago).
     * Si no se realizan abonos, los cortes continúan avanzando, acumulando las multas
     * por mora ($300 por corte atrasado) y arrastrando el saldo con pérdida de comisiones.
     */
    public function test_adeudo_quincenal_total_genera_cortes_con_multas_acumuladas_y_comisiones_perdidas()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'activo' => true,
        ]);
        $cliente = $this->crearClienteTest('Roberto Mora', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-5000-ADEUDO',
            'nombre' => 'Vale $5,000',
            'monto_prestamo' => 5000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-5000-ADEUDO-01',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'monto_total_pagar' => 7600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // Corte 1: 0 multas
        $service->simularSiguienteCorte();
        $filasCorte1 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertEquals(931.25, $filasCorte1[0]['total']);

        // Corte 2 (+15 días) SIN PAGAR: 1er retraso -> Multa de $300 aplicada
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(15));
        $service->simularSiguienteCorte();
        $prestamo->refresh();
        $this->assertEquals(300.00, floatval($prestamo->multas));

        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(2, $filasCorte2);
        $this->assertEquals(950.00, $filasCorte2[0]['total']);
        $this->assertEquals(2181.25, $filasCorte2[1]['total']);

        // Corte 3 (+30 días) SIN PAGAR: 2do retraso -> Multa acumulada de $600
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(30));
        $service->simularSiguienteCorte();
        $prestamo->refresh();
        $this->assertEquals(600.00, floatval($prestamo->multas));

        $filasCorte3 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(3, $filasCorte3);
        $this->assertEquals(3431.25, $filasCorte3[2]['total'], 'El corte 3 debe ser $3,431.25');

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA 3: Pago parcial.
     * Al abonar un monto parcial ($500 de $931.25), se registra el abono, queda saldo
     * pendiente ($431.25), y en el siguiente corte se arrastra el saldo restante con multa.
     */
    public function test_pago_parcial_registra_abono_y_arrastra_saldo_restante_a_siguientes_cortes()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'activo' => true,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);
        $cliente = $this->crearClienteTest('Luisa Parcial', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-5000-PARC',
            'nombre' => 'Vale $5,000',
            'monto_prestamo' => 5000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-5000-PARC-01',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'monto_total_pagar' => 7600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // Corte 1
        $service->simularSiguienteCorte();

        // Se abonan $500.00 (faltan $431.25)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 500.00,
            'metodo_pago' => 'efectivo',
        ]);

        $filasCorte1 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertEquals(500.00, $filasCorte1[0]['abono']);
        $this->assertEquals(431.25, $filasCorte1[0]['total']);

        // Corte 2 (+15 días): Al no liquidar el faltante, entra a mora con multa de $300
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(15));
        $service->simularSiguienteCorte();
        $prestamo->refresh();
        $this->assertEquals(300.00, floatval($prestamo->multas));

        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(2, $filasCorte2);
        $this->assertEquals(500.00, $filasCorte2[0]['abono']);
        $this->assertEquals(1681.25, $filasCorte2[1]['total'], 'Fila 2/8 debe exigir $1,681.25 ($450 restante + $300 multa + $931.25 cuota neta)');

        // Ahora en Corte 2 se abona el saldo restante ($1,681.25)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 1681.25,
            'metodo_pago' => 'efectivo',
        ]);

        // Corte 3 (+30 días): El vale queda al corriente, 0 multas nuevas y cuota limpia de $931.25
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(30));
        $service->simularSiguienteCorte();

        $filasCorte3 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(3, $filasCorte3);
        $this->assertEquals('3/8', $filasCorte3[2]['numero_pago']);
        $this->assertEquals(0.00, $filasCorte3[2]['recargos']);
        $this->assertEquals(931.25, $filasCorte3[2]['total']);

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA 4: Pago excedente.
     * Al pagar de más ($1,000 sobre una cuota neta de $931.25), se genera saldo a favor (-$69.00)
     * y en el siguiente corte se descuenta automáticamente ($931.25 - $69.00 = $862.25).
     */
    public function test_pago_excedente_genera_saldo_a_favor_y_se_descuenta_en_siguiente_corte()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'activo' => true,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);
        $cliente = $this->crearClienteTest('Victor Excedente', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-5000-EXC',
            'nombre' => 'Vale $5,000',
            'monto_prestamo' => 5000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-5000-EXC-01',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'monto_total_pagar' => 7600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // Corte 1
        $service->simularSiguienteCorte();

        // Se abonan $1,000.00 (excedente de $69.00)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 1000.00,
            'metodo_pago' => 'efectivo',
        ]);

        $filasCorte1 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertEquals(-69.00, $filasCorte1[0]['total'], 'La fila 1/8 debe mostrar -$69.00 de saldo a favor');

        // Corte 2 (+15 días): La fila 2/8 descuenta los $69.00 -> $862.25
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(15));
        $resCorte2 = $service->simularSiguienteCorte();
        $this->assertEquals(0, $resCorte2['multas_aplicadas']);

        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(2, $filasCorte2);
        $this->assertEquals(-69.00, $filasCorte2[0]['total']);
        $this->assertEquals(862.25, $filasCorte2[1]['total'], 'La fila 2/8 debe reflejar el descuento: $931.25 - $69.00 = $862.25');

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA 5: Combinando múltiples clientes de vales a la vez.
     * Con 4 clientes distintos bajo la misma distribuidora:
     * - Cliente A (Juan): Pago COMPLETO ($931.25)
     * - Cliente B (Pedro): SIN PAGO ($0.00) -> Entra en mora
     * - Cliente C (Luis): Pago PARCIAL ($500.00) -> Saldo pendiente con mora
     * - Cliente D (Carlos): Pago EXCEDENTE ($1,000.00) -> Saldo a favor
     * Se procesan cortes sucesivos y cada préstamo mantiene su estado, cálculo y avance independiente.
     */
    public function test_combinando_pagos_con_multiples_clientes_de_vales_simultaneamente()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'activo' => true,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $clienteJuan = $this->crearClienteTest('Juan Completo', $distribuidora);
        $clientePedro = $this->crearClienteTest('Pedro Adeudo', $distribuidora);
        $clienteLuis = $this->crearClienteTest('Luis Parcial', $distribuidora);
        $clienteCarlos = $this->crearClienteTest('Carlos Excedente', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-5000-MULTI',
            'nombre' => 'Vale $5,000',
            'monto_prestamo' => 5000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamoJuan = Prestamo::create([
            'referencia' => 'VAL-JUAN-01',
            'cliente_id' => $clienteJuan->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'monto_total_pagar' => 7600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $prestamoPedro = Prestamo::create([
            'referencia' => 'VAL-PEDRO-01',
            'cliente_id' => $clientePedro->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'monto_total_pagar' => 7600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $prestamoLuis = Prestamo::create([
            'referencia' => 'VAL-LUIS-01',
            'cliente_id' => $clienteLuis->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'monto_total_pagar' => 7600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $prestamoCarlos = Prestamo::create([
            'referencia' => 'VAL-CARLOS-01',
            'cliente_id' => $clienteCarlos->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'monto_total_pagar' => 7600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // 1. Simular Corte 1
        $service->simularSiguienteCorte();

        // Juan paga COMPLETO ($931.25)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoJuan), [
            'monto_abonado' => 931.25,
            'metodo_pago' => 'efectivo',
        ]);

        // Pedro NO paga ($0.00)

        // Luis paga PARCIAL ($500.00)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoLuis), [
            'monto_abonado' => 500.00,
            'metodo_pago' => 'efectivo',
        ]);

        // Carlos paga EXCEDENTE ($1,000.00)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamoCarlos), [
            'monto_abonado' => 1000.00,
            'metodo_pago' => 'efectivo',
        ]);

        // 2. Avanzar a Corte 2 (+15 días)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(15));
        $resCorte2 = $service->simularSiguienteCorte();

        // Se deben aplicar multas únicamente a los 2 vales con adeudo (Pedro y Luis), NO a Juan ni Carlos
        $this->assertEquals(2, $resCorte2['multas_aplicadas'], 'Solo Pedro y Luis deben recibir multa');

        $prestamoJuan->refresh();
        $prestamoPedro->refresh();
        $prestamoLuis->refresh();
        $prestamoCarlos->refresh();

        $this->assertEquals(0.00, floatval($prestamoJuan->multas), 'Juan no tiene multas');
        $this->assertEquals(300.00, floatval($prestamoPedro->multas), 'Pedro tiene multa de $300');
        $this->assertEquals(300.00, floatval($prestamoLuis->multas), 'Luis tiene multa de $300');
        $this->assertEquals(0.00, floatval($prestamoCarlos->multas), 'Carlos no tiene multas');

        // Verificar reporte global de la relación de cobranza
        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(8, $filasCorte2, 'Debe haber 8 filas (2 por cada uno de los 4 préstamos)');

        // Agrupar filas por préstamo
        $filasPorPrestamo = [];
        foreach ($filasCorte2 as $f) {
            $filasPorPrestamo[$f['prestamo_id']][] = $f;
        }

        // Juan: Fila 2/8 limpia ($931.25)
        $this->assertEquals(931.25, $filasPorPrestamo[$prestamoJuan->id][1]['total']);
        $this->assertEquals(0.00, $filasPorPrestamo[$prestamoJuan->id][1]['recargos']);

        // Pedro: Fila 2/8 con multa y arrastre ($2,181.25)
        $this->assertEquals(2181.25, $filasPorPrestamo[$prestamoPedro->id][1]['total']);
        $this->assertEquals(300.00, $filasPorPrestamo[$prestamoPedro->id][1]['recargos']);

        // Luis: Fila 2/8 con multa y faltante ($1,681.25)
        $this->assertEquals(1681.25, $filasPorPrestamo[$prestamoLuis->id][1]['total']);
        $this->assertEquals(300.00, $filasPorPrestamo[$prestamoLuis->id][1]['recargos']);

        // Carlos: Fila 2/8 con descuento del saldo a favor ($862.25)
        $this->assertEquals(862.25, $filasPorPrestamo[$prestamoCarlos->id][1]['total']);
        $this->assertEquals(0.00, $filasPorPrestamo[$prestamoCarlos->id][1]['recargos']);

        // PDF de la relación se genera correctamente
        $responsePDF = $this->actingAs($distribuidora)->get(route('prestamos.relacion-pdf'));
        $responsePDF->assertOk();
        $responsePDF->assertSee('Relación de Cobranza Oficial');
        $responsePDF->assertSee('Juan Completo');
        $responsePDF->assertSee('Pedro Adeudo');
        $responsePDF->assertSee('Luis Parcial');
        $responsePDF->assertSee('Carlos Excedente');

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA: Al liquidar el total de un vale (8/8 quincenas pagadas), el corte actual muestra
     * la liquidación y en el siguiente corte (corte 9 o posteriores) desaparece completamente de la relación.
     */
    public function test_si_se_paga_el_adeudo_total_el_siguiente_corte_desaparece_de_la_relacion()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'activo' => true,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);
        $cliente = $this->crearClienteTest('Maria Garcia', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-15000-FULL',
            'nombre' => 'jgcfjck',
            'monto_prestamo' => 15000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-15000-MG-01',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 15000.00,
            'cuota_quincenal' => 2825.00,
            'monto_total_pagar' => 22600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 22600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // Pagar las 8 quincenas consecutivas ($2,768.75 cada una)
        for ($c = 1; $c <= 8; $c++) {
            \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(($c - 1) * 15));
            $service->simularSiguienteCorte();

            $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
                'monto_abonado' => 2768.75,
                'metodo_pago' => 'efectivo',
            ]);
        }

        // Al corte 8: Debe mostrar las 8 filas y la fila 8/8 queda con $0 o liquidada
        $filasCorte8 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(8, $filasCorte8, 'En el corte 8 deben verse las 8 quincenas');
        $this->assertEquals('8/8', $filasCorte8[7]['numero_pago']);

        // MOMENTO CLAVE: Avanzar a Corte 9 (+15 días del corte 8)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(8 * 15));
        $service->simularSiguienteCorte();

        // En el corte 9 (el siguiente corte después de liquidar todo el vale), el vale DEBE DESAPARECER POR COMPLETO
        $filasCorte9 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(0, $filasCorte9, 'En el corte 9 el vale liquidado debe desaparecer completamente de la relación');

        // Avanzar a Corte 10: Sigue sin aparecer
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(9 * 15));
        $service->simularSiguienteCorte();

        $filasCorte10 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(0, $filasCorte10, 'En el corte 10 no deben existir filas sobrantes');

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA: Liquidación anticipada total en corte 2 (pagando el adeudo total restante).
     * En el corte 2 se muestra liquidado, y en el corte 3 desaparece completamente de la relación.
     */
    public function test_liquidacion_anticipada_total_en_corte_dos_desaparece_en_corte_tres()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'activo' => true,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);
        $cliente = $this->crearClienteTest('Carlos Anticipado', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-5000-ANT',
            'nombre' => 'Vale $5,000',
            'monto_prestamo' => 5000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-5000-ANT-01',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 950.00,
            'monto_total_pagar' => 7600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 7600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // Corte 1: Paga quincena 1 ($931.25)
        $service->simularSiguienteCorte();
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 931.25,
            'metodo_pago' => 'efectivo',
        ]);

        // Corte 2 (+15 días): Paga todo el saldo restante del vale (7 quincenas * $931.25 = $6,518.75)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(15));
        $service->simularSiguienteCorte();

        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 6518.75,
            'metodo_pago' => 'efectivo',
        ]);

        // En corte 2 se ven 2 filas y la fila 2/8 queda liquidada (saldo <= 0)
        $filasCorte2 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(2, $filasCorte2);
        $this->assertLessThanOrEqual(0.00, $filasCorte2[1]['total']);

        // Corte 3 (+30 días): El vale fue liquidado totalmente en el corte 2 -> DESAPARECE DE LA RELACIÓN
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(30));
        $service->simularSiguienteCorte();

        $filasCorte3 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(0, $filasCorte3, 'En el corte 3 el vale pagado anticipadamente desaparece por completo');

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA: Los puntos NO se asignan al momento de hacer el abono en caja.
     * Se asignan EXCLUSIVAMENTE hasta que se ejecuta/procesa el corte quincenal
     * bajo la condición de que la cuota haya sido liquidada antes del corte y sin multas.
     */
    public function test_puntos_no_se_otorgan_al_hacer_abono_sino_hasta_procesar_el_corte()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'activo' => true,
            'puntos' => 0,
            'multas' => 0.00,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);
        $cliente = $this->crearClienteTest('Distribuidora Puntos Test', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-15000-PTS',
            'nombre' => 'Vale $15,000',
            'monto_prestamo' => 15000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-15000-PTS-01',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 15000.00,
            'cuota_quincenal' => 2825.00,
            'monto_total_pagar' => 22600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 22600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // 1. Simular Corte 1 (se abre el periodo 1/8)
        $service->simularSiguienteCorte();
        $this->assertEquals(0, $distribuidora->puntos, 'Al abrir el corte 1 tiene 0 puntos');

        // 2. La distribuidora paga su cuota 1 ($2,768.75) antes del siguiente corte
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 2768.75,
            'metodo_pago' => 'efectivo',
        ]);

        // MOMENTO CLAVE 1: Inmediatamente tras hacer el abono, los puntos NO deben asignarse todavía
        $distribuidora->refresh();
        $this->assertEquals(0, $distribuidora->puntos, 'El abono NO otorga puntos de inmediato antes de que se ejecute el corte');

        // 3. Llega la fecha del Corte 2 (+15 días) y se procesa el corte
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(15));
        $resCorte2 = $service->simularSiguienteCorte();

        // MOMENTO CLAVE 2: AL EJECUTARSE EL CORTE, se evalúa que pagó a tiempo y se le otorgan sus 36 puntos
        $distribuidora->refresh();
        $this->assertEquals(36, $distribuidora->puntos, 'Al procesarse el corte, se otorgan los 36 puntos de bonificación por pago anticipado');

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA CASO USUARIO IMAGEN:
     * 1. Se cobra el vale (entregado en ventanilla).
     * 2. Se paga la cuota quincenal ($2,768.75) ANTES del primer corte.
     * 3. Se ejecuta el Corte #1.
     * RESULTADO ESPERADO:
     * - Muestra ÚNICAMENTE 1 fila (1/8), NO dos filas (1/8 y 2/8).
     * - Otorga los 36 puntos de bonificación por pago anticipado.
     * - La fila 1/8 muestra saldo cubierto y el total a pagar queda en $0.00.
     */
    public function test_cobro_vale_pago_anticipado_y_despues_corte_muestra_solo_un_pago_y_otorga_puntos()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'activo' => true,
            'puntos' => 0,
            'multas' => 0.00,
        ]);
        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);
        $cliente = $this->crearClienteTest('Maria Garcia', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-15000-MG',
            'nombre' => 'Vale $15,000',
            'monto_prestamo' => 15000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        // 1. Cobro del vale (entregado en ventanilla)
        $prestamo = Prestamo::create([
            'referencia' => 'VAL-15000-MG-01',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 15000.00,
            'cuota_quincenal' => 2825.00,
            'monto_total_pagar' => 22600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 22600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // 2. La distribuidora paga la cuota ($2,768.75) ANTES de que se haga el primer corte
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 2768.75,
            'metodo_pago' => 'efectivo',
        ]);

        // 3. Se ejecuta el Corte #1
        $service->simularSiguienteCorte();

        // 4. Verificación de puntos otorgados al corte
        $distribuidora->refresh();
        $this->assertEquals(36, $distribuidora->puntos, 'Debe otorgar 36 puntos al momento de hacer el corte');

        // 5. Verificación de que en el Corte #1 se genera ÚNICAMENTE 1 fila (1/8) y NO dos filas
        $filasCorte1 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(1, $filasCorte1, 'Debe mostrar solo 1 fila (1/8) en el Corte #1');
        $this->assertEquals('1/8', $filasCorte1[0]['numero_pago']);
        $this->assertEquals(0.00, $filasCorte1[0]['total'], 'La fila 1/8 debe estar liquidada con total $0.00');

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA CASO USUARIO IMAGEN 2:
     * Maria Garcia tiene 3 cortes transcurridos (1/8, 2/8, 3/8).
     * Se asigna un nuevo vale a Probiño Macaquiño.
     * En el siguiente corte (Corte 4):
     * - Maria debe mostrar su corte correlativo (4/8).
     * - Probiño debe mostrar ÚNICAMENTE su primer corte (1/8), NO dos cortes (1/8 y 2/8).
     */
    public function test_maria_tres_cortes_y_nuevo_vale_probino_muestra_solo_un_corte_en_el_siguiente()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'activo' => true,
            'puntos' => 0,
            'multas' => 0.00,
        ]);

        $clienteMaria = $this->crearClienteTest('Maria Garcia', $distribuidora);
        $clienteProbino = $this->crearClienteTest('Probiño Macaquiño', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-15000-MP',
            'nombre' => 'Vale $15,000',
            'monto_prestamo' => 15000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        // 1. Vale de Maria entregado en t = 0
        $prestamoMaria = Prestamo::create([
            'referencia' => 'VAL-MARIA-01',
            'cliente_id' => $clienteMaria->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 15000.00,
            'cuota_quincenal' => 2825.00,
            'monto_total_pagar' => 22600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 22600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // Simular Corte 1 (+0d)
        $service->simularSiguienteCorte();

        // Simular Corte 2 (+15d)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(15));
        $service->simularSiguienteCorte();

        // Simular Corte 3 (+30d)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(30));
        $service->simularSiguienteCorte();

        // En Corte 3: Maria tiene 3 cortes
        $filasCorte3 = $service->generarFilasRelacionCobranza($distribuidora);
        $filasMariaCorte3 = array_values(array_filter($filasCorte3, fn($f) => $f['cliente'] === 'Maria Garcia'));
        $this->assertCount(3, $filasMariaCorte3);

        // 2. Ahora, durante/después de Corte 3, se asigna y cobra un vale a Probiño Macaquiño
        $tiempoProbino = $tiempoInicial->copy()->addDays(30)->addHours(2);
        \Carbon\Carbon::setTestNow($tiempoProbino);

        $prestamoProbino = Prestamo::create([
            'referencia' => 'VAL-PROBINO-01',
            'cliente_id' => $clienteProbino->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 15000.00,
            'cuota_quincenal' => 2825.00,
            'monto_total_pagar' => 22600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 22600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoProbino,
            'entregado_at' => $tiempoProbino,
        ]);

        // Antes de que corra el Corte 4, Probiño no debe tener cortes pasados
        $filasAntesCorte4 = $service->generarFilasRelacionCobranza($distribuidora);
        $filasProbinoAntes = array_values(array_filter($filasAntesCorte4, fn($f) => $f['cliente'] === 'Probiño Macaquiño'));
        $this->assertCount(0, $filasProbinoAntes, 'Probiño recién cobrado no tiene cortes pasados');

        // 3. Simular Corte 4 (+45d)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(45));
        $service->simularSiguienteCorte();

        // En Corte 4: Maria tiene 4 cortes y Probiño tiene EXACTAMENTE 1 corte (1/8)
        $filasCorte4 = $service->generarFilasRelacionCobranza($distribuidora);
        $filasMariaCorte4 = array_values(array_filter($filasCorte4, fn($f) => $f['cliente'] === 'Maria Garcia'));
        $filasProbinoCorte4 = array_values(array_filter($filasCorte4, fn($f) => $f['cliente'] === 'Probiño Macaquiño'));

        $this->assertCount(4, $filasMariaCorte4, 'Maria debe tener 4 cortes en el Corte 4');
        $this->assertCount(1, $filasProbinoCorte4, 'Probiño debe tener ÚNICAMENTE 1 corte (1/8) en el Corte 4');
        $this->assertEquals('1/8', $filasProbinoCorte4[0]['numero_pago']);

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA: Recuperación progresiva del crédito disponible conforme se pagan las quincenas del vale.
     * Fórmula: monto_prestamo / total_quincenas se reintegra al crédito disponible con cada pago quincenal cubierto.
     * Si el pago se atrasa y se liquida posteriormente, también recupera su porción correspondiente de crédito.
     */
    public function test_recuperacion_proporcional_de_credito_por_quincena_pagada()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'limite_credito' => 200000.00,
            'activo' => true,
            'puntos' => 0,
            'multas' => 0.00,
        ]);

        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $cliente = $this->crearClienteTest('Cliente Credito Progresivo', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-15000-CRED',
            'nombre' => 'Vale $15,000',
            'monto_prestamo' => 15000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        // 1. Asignar y entregar el vale de $15,000 (8 quincenas)
        $prestamo = Prestamo::create([
            'referencia' => 'VAL-CRED-01',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 15000.00,
            'cuota_quincenal' => 2825.00,
            'monto_total_pagar' => 22600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 22600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        // Al inicio (sin pagos): Crédito Ocupado = $15,000, Crédito Disponible = $185,000 ($200,000 - $15,000)
        $this->assertEquals(15000.00, $distribuidora->creditoUtilizado());
        $this->assertEquals(185000.00, $distribuidora->creditoDisponible());

        // 2. Pago de Quincena 1 ($2,768.75)
        // Capital a recuperar: 15,000 / 8 = $1,875.00
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 2768.75,
            'metodo_pago' => 'efectivo',
        ]);

        $this->assertEquals(13125.00, $distribuidora->creditoUtilizado());
        $this->assertEquals(186875.00, $distribuidora->creditoDisponible(), 'Debe recuperar $1,875 de crédito tras pagar quincena 1');

        // 3. Pago de Quincena 2 ($2,768.75)
        // Total acumulado pagado: $5,537.50 (2/8) -> Capital recuperado acumulado: $3,750.00
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 2768.75,
            'metodo_pago' => 'efectivo',
        ]);

        $this->assertEquals(11250.00, $distribuidora->creditoUtilizado());
        $this->assertEquals(188750.00, $distribuidora->creditoDisponible(), 'Debe recuperar $3,750 de crédito acumulado tras pagar quincena 2');

        // 4. Pago con retraso en quincena 3: se paga la cuota quincenal correspondiente
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 2768.75,
            'metodo_pago' => 'efectivo',
        ]);

        // 3 quincenas cubiertas: capital recuperado $5,625.00 -> disponible $190,625.00
        $this->assertEquals(9375.00, $distribuidora->creditoUtilizado());
        $this->assertEquals(190625.00, $distribuidora->creditoDisponible(), 'Al liquidarse con retraso también recupera su capital equivalente');

        // 5. Liquidación restante (5 quincenas restantes: 5 * 2768.75 = $13,843.75)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 13843.75,
            'metodo_pago' => 'efectivo',
        ]);

        // Totalmente pagado: Crédito Ocupado = $0.00, Crédito Disponible = $200,000.00
        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA CASO USUARIO: Pago con retraso en Corte 2 ($3,127.50 cubriendo $3,125/$3,126.75 con excedente).
     * En Corte 3:
     * - NO debe aplicar nueva multa moratoria en Corte 3 (recargos = $0.00).
     * - Debe descontar el excedente pagado en Corte 2 de la cuota exigible en Corte 3.
     */
    public function test_pago_con_multa_en_corte_dos_liquida_fila_y_en_corte_tres_no_aplica_multa_y_descuenta_excedente()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'limite_credito' => 200000.00,
            'activo' => true,
            'puntos' => 0,
            'multas' => 0.00,
        ]);

        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $cliente = $this->crearClienteTest('Maria Garcia', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-15000-MP',
            'nombre' => 'Vale $15,000',
            'monto_prestamo' => 15000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-MARIA-EXC',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 15000.00,
            'cuota_quincenal' => 2825.00,
            'monto_total_pagar' => 22600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 22600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // 1. Simular Corte 1 (sin pagar)
        $service->simularSiguienteCorte();

        // 2. Simular Corte 2 (+15d): Al no pagar Corte 1, se aplica multa de $300
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(15));
        $service->simularSiguienteCorte();

        $prestamo->refresh();
        $this->assertEquals(300.00, $prestamo->multas, 'Debe tener $300 de multa aplicada en Corte 2');

        // 3. En Corte 2, la distribuidora paga $3,127.50
        // Monto exigible de Fila 2: Cuota Bruta $2,825 + Multa $300 = $3,125.00 (Excedente = $2.50)
        $response = $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 3127.50,
            'metodo_pago' => 'efectivo',
        ]);
        $response->assertSessionHasNoErrors();

        $prestamo->refresh();
        $this->assertEquals(0.00, $prestamo->multas, 'La multa del vale se liquida a $0 tras el pago');

        // 4. Simular Corte 3 (+30d)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(30));
        $resCorte3 = $service->simularSiguienteCorte();

        // En Corte 3: NO debe aplicarse nueva multa porque el ciclo anterior fue liquidado
        $this->assertEquals(0, $resCorte3['multas_aplicadas'], 'No deben aplicarse multas en Corte 3');

        $filasCorte3 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(3, $filasCorte3);

        // Fila 3 (Corte 3 actual):
        $fila3 = $filasCorte3[2];
        $this->assertEquals('3/8', $fila3['numero_pago']);
        $this->assertEquals(0.00, $fila3['recargos'], 'La Fila 3 no debe tener recargos ($0.00)');
        // Cuota neta ($2,768.75) - Excedente ($2.50) = $2,766.25
        $this->assertEquals(2766.25, $fila3['total'], 'La Fila 3 debe restar el excedente de $2.50 de la cuota neta ($2,768.75 - $2.50 = $2,766.25)');

        \Carbon\Carbon::setTestNow();
    }

    /**
     * PRUEBA CASO USUARIO IMAGEN 3:
     * En Corte 2 hay un excedente de $1.00 (Fila 2/8 muestra -$1.00).
     * En Corte 3, la Fila 3/8 descuenta el $1.00 ($2,768.75 - $1.00 = $2,767.75).
     * Tras pagar los $2,767.75 en Corte 3 y avanzar a Corte 4:
     * - Fila 3/8 queda liquidada.
     * - Fila 4/8 entra al corriente ($2,768.75 con $0 recargos).
     */
    public function test_abono_excedente_en_corte_dos_se_descuenta_en_corte_tres_y_no_se_pierde_en_corte_cuatro()
    {
        $tiempoInicial = \Carbon\Carbon::parse('2026-09-01 10:00:00');
        \Carbon\Carbon::setTestNow($tiempoInicial);

        $config = Configuracion::actual();
        $config->update([
            'comision_cobre' => 3.00,
        ]);

        $distribuidora = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'Cobre',
            'limite_credito' => 200000.00,
            'activo' => true,
            'puntos' => 0,
            'multas' => 0.00,
        ]);

        $cajero = User::factory()->create([
            'rol_id' => $this->rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $cliente = $this->crearClienteTest('Probiño Macaquiño', $distribuidora);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-15000-PROB',
            'nombre' => 'Vale $15,000',
            'monto_prestamo' => 15000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 8,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-PROB-EXC',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidora->id,
            'tipo' => 'vale',
            'monto_prestamo' => 15000.00,
            'cuota_quincenal' => 2825.00,
            'monto_total_pagar' => 22600.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 22600.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_at' => $tiempoInicial,
            'entregado_at' => $tiempoInicial,
        ]);

        $service = app(CorteCobranzaService::class);

        // 1. Corte 1 (+0d): Pago regular de $2,768.75
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 2768.75,
            'metodo_pago' => 'efectivo',
        ]);
        $service->simularSiguienteCorte();

        // 2. Corte 2 (+15d): Pago excedente de $1.00 ($2,769.75)
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(15));
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 2769.75,
            'metodo_pago' => 'efectivo',
        ]);
        $service->simularSiguienteCorte();

        // 3. Corte 3 (+30d): Se simula Corte 3
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(30));
        $service->simularSiguienteCorte();

        $filasCorte3 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(3, $filasCorte3);

        // Fila 2/8 muestra saldo a favor -$1.00
        $this->assertEquals(-1.00, $filasCorte3[1]['total']);
        // Fila 3/8 descuenta el $1.00 de la cuota neta ($2,768.75 - $1.00 = $2,767.75)
        $this->assertEquals(2767.75, $filasCorte3[2]['total'], 'Corte 3 debe descontar el $1.00 de saldo a favor');
        $this->assertEquals(0.00, $filasCorte3[2]['recargos']);

        // Se paga la cuota de Corte 3 con el descuento aplicado ($2,767.75)
        $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 2767.75,
            'metodo_pago' => 'efectivo',
        ]);

        // 4. Corte 4 (+45d): Se simula Corte 4
        \Carbon\Carbon::setTestNow($tiempoInicial->copy()->addDays(45));
        $service->simularSiguienteCorte();

        $filasCorte4 = $service->generarFilasRelacionCobranza($distribuidora);
        $this->assertCount(4, $filasCorte4);

        $this->assertEquals(2767.75, $filasCorte4[2]['total'], 'Fila 3/8 pagada con descuento muestra su cuota neta descontada ($2,767.75)');
        $this->assertEquals('4/8', $filasCorte4[3]['numero_pago']);
        $this->assertEquals(0.00, $filasCorte4[3]['recargos'], 'Fila 4/8 entra sin multas ($0.00)');
        $this->assertEquals(2768.75, $filasCorte4[3]['total'], 'Fila 4/8 entra al corriente con su cuota neta');

        \Carbon\Carbon::setTestNow();
    }
}

