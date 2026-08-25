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
        $this->assertEquals(250.00, $relacion->monto_total_periodo);
        $this->assertEquals(250.00, $relacion->adeudo_pendiente);
        $this->assertEquals(0.00, $relacion->monto_pagado);

        // 2. Abono en ventanilla de $100: se actualiza la relación
        $this->actingAs($cajero)->post(route('cajero.abonos.distribuidora.store', $distribuidora), [
            'referencia_pago' => 'REF-DIST-00000077',
            'monto_abonado' => 100.00,
            'metodo_pago' => 'efectivo',
        ]);

        $relacion->refresh();
        $this->assertEquals(100.00, $relacion->monto_pagado);
        $this->assertEquals(150.00, $relacion->adeudo_pendiente);

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
        $this->assertEquals(450.00, $relacion->adeudo_pendiente); // 150 + 300
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
        $this->assertEquals(300.00, $relacionB->adeudo_pendiente);
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
}
