<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\PagoPrestamo;
use App\Models\Prestamo;
use App\Models\ProductoVale;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\CorteCobranzaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CobranzaValesIndividualesTest extends TestCase
{
    use RefreshDatabase;

    protected User $gerenteGeneral;
    protected User $cajero;
    protected User $distribuidor;
    protected Sucursal $sucursal;
    protected Cliente $cliente;

    protected function setUp(): void
    {
        parent::setUp();
        \Carbon\Carbon::setTestNow(\Carbon\Carbon::parse('2026-08-25 12:00:00'));

        $rolGG = Rol::firstOrCreate(['nombre' => 'Gerente General']);
        $rolCajero = Rol::firstOrCreate(['nombre' => 'Cajero']);
        $rolDist = Rol::firstOrCreate(['nombre' => 'Distribuidor']);

        $this->sucursal = Sucursal::create([
            'nombre' => 'Sucursal Centro',
            'ciudad' => 'Torreón',
            'estado' => 'Coahuila',
        ]);

        $this->gerenteGeneral = User::factory()->create([
            'rol_id' => $rolGG->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $this->cajero = User::factory()->create([
            'rol_id' => $rolCajero->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $this->distribuidor = User::factory()->create([
            'rol_id' => $rolDist->id,
            'sucursal_id' => $this->sucursal->id,
            'referencia_pago_distribuidor' => 'REF-DIST-00000088',
            'activo' => true,
            'puntos' => 0,
            'multas' => 0.00,
        ]);

        $this->cliente = Cliente::create([
            'nombre' => 'María',
            'apellido_paterno' => 'González',
            'curp' => 'GONM900101MDFRRN01',
            'fecha_nacimiento' => '1990-01-01',
            'lugar_nacimiento' => 'Torreón',
            'calle' => 'Av Hidalgo 123',
            'colonia' => 'Centro',
            'codigo_postal' => '27000',
            'ciudad' => 'Torreón',
            'estado' => 'Coahuila',
            'activo' => true,
            'created_by_user_id' => $this->distribuidor->id,
        ]);
    }

    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_gerente_general_can_create_producto_vale_with_multa(): void
    {
        $response = $this->actingAs($this->gerenteGeneral)->post(route('producto-vales.store'), [
            'clave' => 'VLT-5K-MULTA',
            'nombre' => 'Vale $5,000 con Multa',
            'monto_prestamo' => 5000.00,
            'costo_seguro' => 150.00,
            'comision_apertura' => 2.00,
            'plazo_quincenas' => 12,
            'tasa_interes_quincenal' => 2.20,
            'multa' => 180.00,
            'descripcion' => 'Vale de prueba con multa individual de $180.00',
        ]);

        $response->assertRedirect(route('producto-vales.index'));
        $this->assertDatabaseHas('producto_vales', [
            'clave' => 'VLT-5K-MULTA',
            'monto_prestamo' => 5000.00,
            'multa' => 180.00,
            'activo' => true,
        ]);
    }

    public function test_overdue_cut_applies_individual_multa_per_loan_and_advances_quincenal_cycle_15_days(): void
    {
        $productoVale = ProductoVale::create([
            'clave' => 'VALE-IND-150',
            'nombre' => 'Vale Individual',
            'monto_prestamo' => 3000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'plazo_quincenas' => 10,
            'tasa_interes_quincenal' => 2.50,
            'multa' => 150.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-IND-001',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
            'tipo' => 'vale',
            'monto_prestamo' => 3000.00,
            'cuota_quincenal' => 350.00,
            'monto_total_pagar' => 3500.00,
            'pagos_totales' => 10,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 3000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        $config = Configuracion::actual();
        $cortePasado = now()->subHours(2);
        $limitePasado = now()->subHour();
        $config->update([
            'dia_corte' => $cortePasado->day,
            'hora_corte' => $cortePasado->format('H:i:s'),
            'dia_limite_pago' => $limitePasado->day,
            'hora_limite_pago' => $limitePasado->format('H:i:s'),
        ]);

        $corteService = app(CorteCobranzaService::class);
        $corteService->verificarYProcesarCortesYVencimientos();

        // 1. Verificar que la multa del vale fue aplicada al préstamo individual ($150)
        $prestamo->refresh();
        $this->assertEquals(150.00, $prestamo->multas);
        $this->assertEquals(3150.00, $prestamo->totalAdeudoConMultas());

        // 2. Verificar que la distribuidora acumula la multa de sus vales
        $this->distribuidor->refresh();
        $this->assertEquals(150.00, $this->distribuidor->multas);

        // 3. Verificar que el ciclo se avanzó 15 días automáticamente
        $config->refresh();
        $fechaEsperadaCorte = $cortePasado->copy()->addDays(15);
        $this->assertEquals($fechaEsperadaCorte->day, $config->dia_corte);
    }

    public function test_cashier_can_pay_individual_vale_amortizing_multas_then_capital(): void
    {
        $productoVale = ProductoVale::create([
            'clave' => 'VALE-IND-TEST',
            'nombre' => 'Vale con Multa',
            'monto_prestamo' => 2000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'plazo_quincenas' => 10,
            'tasa_interes_quincenal' => 2.00,
            'multa' => 150.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-IND-PAGO',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
            'tipo' => 'vale',
            'monto_prestamo' => 2000.00,
            'cuota_quincenal' => 240.00,
            'monto_total_pagar' => 2400.00,
            'pagos_totales' => 10,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 1000.00,
            'multas' => 150.00, // Multa activa en el vale
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        $this->distribuidor->update(['multas' => 150.00]);

        // Cajero abona $400 directamente al vale: $150 liquidan la multa + $250 amortizan capital (adeudo pasa de 1000 a 750)
        $response = $this->actingAs($this->cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 400.00,
            'metodo_pago' => 'efectivo',
            'observaciones' => 'Abono directo a vale individual en caja',
        ]);

        $response->assertSessionHasNoErrors();

        $prestamo->refresh();
        $this->assertEquals(0.00, $prestamo->multas, 'La multa del vale debe quedar liquidada.');
        $this->assertEquals(750.00, $prestamo->adeudo_pendiente, 'El saldo deudor debe reducirse en $250.');
        $this->assertEquals(250.00, $prestamo->pagos_recibidos);
        $this->assertEquals(1, $prestamo->pagos_realizados);

        // La distribuidora debe ver sus multas reducidas a 0
        $this->distribuidor->refresh();
        $this->assertEquals(0.00, $this->distribuidor->multas);
    }

    public function test_gerente_general_can_simulate_corte_and_advance_cycle(): void
    {
        $productoVale = ProductoVale::create([
            'clave' => 'VALE-SIM-001',
            'nombre' => 'Vale Simulado',
            'monto_prestamo' => 4000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'plazo_quincenas' => 10,
            'tasa_interes_quincenal' => 2.00,
            'multa' => 200.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-SIM-001',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
            'tipo' => 'vale',
            'monto_prestamo' => 4000.00,
            'cuota_quincenal' => 480.00,
            'monto_total_pagar' => 4800.00,
            'pagos_totales' => 10,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 4000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        $config = Configuracion::actual();
        $corteOriginal = $config->fecha_corte->copy();

        // 1er Clic: Gerente General presiona el botón "Simular Siguiente Corte"
        $response1 = $this->actingAs($this->gerenteGeneral)->post(route('configuracion-general.simular-corte'));
        $response1->assertSessionHas('success');

        // El préstamo con adeudo vencido debe haber recibido su multa individual de $200
        $prestamo->refresh();
        $this->assertEquals(200.00, $prestamo->multas);

        // La distribuidora debe registrar la multa acumulada de sus vales ($200)
        $this->distribuidor->refresh();
        $this->assertEquals(200.00, $this->distribuidor->multas);

        // 2do Clic: Se vuelve a presionar el botón simulando otro corte consecutivo
        $response2 = $this->actingAs($this->gerenteGeneral)->post(route('configuracion-general.simular-corte'));
        $response2->assertSessionHas('success');

        // Las multas se acumulan: $200 + $200 = $400 en el préstamo y en la distribuidora
        $prestamo->refresh();
        $this->assertEquals(400.00, $prestamo->multas, 'La multa del vale debe acumularse a $400.');
        $this->assertEquals(4400.00, $prestamo->totalAdeudoConMultas());

        $this->distribuidor->refresh();
        $this->assertEquals(400.00, $this->distribuidor->multas, 'Las multas de la distribuidora deben acumularse a $400.');
        $this->assertEquals(4400.00, $this->distribuidor->totalAdeudoGlobal());
    }

    public function test_third_delay_notifies_gerente_general_and_gerente_sucursal(): void
    {
        $rolGS = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);
        $gerenteSucursal = User::factory()->create([
            'rol_id' => $rolGS->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VAL-DEL-100',
            'nombre' => 'Vale de Retrasos',
            'monto_prestamo' => 2000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'plazo_quincenas' => 8,
            'tasa_interes_quincenal' => 2.00,
            'multa' => 150.00,
            'activo' => true,
        ]);

        Prestamo::create([
            'referencia' => 'VAL-DEL-001',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
            'tipo' => 'vale',
            'monto_prestamo' => 2000.00,
            'cuota_quincenal' => 250.00,
            'monto_total_pagar' => 2500.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 2000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        // Simular 3 cortes consecutivos
        $this->actingAs($this->gerenteGeneral)->post(route('configuracion-general.simular-corte'));
        $this->distribuidor->refresh();
        $this->assertEquals(1, $this->distribuidor->conteo_retrasos);

        $this->actingAs($this->gerenteGeneral)->post(route('configuracion-general.simular-corte'));
        $this->distribuidor->refresh();
        $this->assertEquals(2, $this->distribuidor->conteo_retrasos);

        // 3er corte simulado: debe disparar la alerta del 3er retraso
        $this->actingAs($this->gerenteGeneral)->post(route('configuracion-general.simular-corte'));
        $this->distribuidor->refresh();
        $this->assertEquals(3, $this->distribuidor->conteo_retrasos);

        // Verificar que se emitieron las notificaciones para Gerente General y Gerente de Sucursal
        $notifGG = \App\Models\NotificacionCajero::where('user_id', $this->gerenteGeneral->id)
            ->where('tipo', 'alerta_morosidad_3er_retraso')
            ->first();
        $this->assertNotNull($notifGG, 'El Gerente General debe recibir notificación de 3er retraso.');

        $notifGS = \App\Models\NotificacionCajero::where('user_id', $gerenteSucursal->id)
            ->where('tipo', 'alerta_morosidad_3er_retraso')
            ->first();
        $this->assertNotNull($notifGS, 'El Gerente de Sucursal debe recibir notificación de 3er retraso.');
    }

    public function test_gerente_can_mark_distribuidora_as_morosa_and_cancels_pending_vales(): void
    {
        $rolGS = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);
        $gerenteSucursal = User::factory()->create([
            'rol_id' => $rolGS->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VAL-PEND-100',
            'nombre' => 'Vale Pendiente',
            'monto_prestamo' => 2000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'plazo_quincenas' => 8,
            'tasa_interes_quincenal' => 2.00,
            'multa' => 150.00,
            'activo' => true,
        ]);

        // Vale 1: Pendiente de entrega en ventanilla
        $valePendiente = Prestamo::create([
            'referencia' => 'VAL-PEND-001',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
            'tipo' => 'prevale',
            'monto_prestamo' => 2000.00,
            'cuota_quincenal' => 250.00,
            'monto_total_pagar' => 2500.00,
            'pagos_totales' => 8,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 2000.00,
            'multas' => 0.00,
            'estado' => 'pendiente',
            'estado_entrega' => 'pendiente',
            'activo' => true,
        ]);

        // Gerente de Sucursal decide marcar como morosa
        $response = $this->actingAs($gerenteSucursal)
            ->from(route('gerente-sucursal.dashboard'))
            ->post(route('gerente.distribuidores.decidir-morosidad', $this->distribuidor), [
                'accion' => 'marcar',
                'motivo' => 'Acumuló 3 retrasos de corte sin abono',
            ]);

        $response->assertSessionHas('warning');

        $this->distribuidor->refresh();
        $this->assertTrue($this->distribuidor->esMorosa());
        $this->assertNotNull($this->distribuidor->morosa_at);
        $this->assertEquals($gerenteSucursal->id, $this->distribuidor->morosa_by_user_id);

        // El vale pendiente debe haber sido desactivado y cancelado
        $valePendiente->refresh();
        $this->assertEquals('desactivado', $valePendiente->estado);
        $this->assertEquals('cancelado', $valePendiente->estado_entrega);
        $this->assertFalse($valePendiente->activo);
        $this->assertNotNull($valePendiente->desactivado_at);
    }

    public function test_morosa_distribuidora_is_blocked_from_assigning_vales(): void
    {
        $productoVale = ProductoVale::create([
            'clave' => 'VAL-BLOQ-100',
            'nombre' => 'Vale Bloqueado',
            'monto_prestamo' => 2000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'plazo_quincenas' => 8,
            'tasa_interes_quincenal' => 2.00,
            'multa' => 150.00,
            'activo' => true,
        ]);

        $this->distribuidor->update(['es_morosa' => true]);

        // 1. Intento de acceder al formulario de asignación
        $responseCreate = $this->actingAs($this->distribuidor)->get(route('prestamos.create'));
        $responseCreate->assertRedirect(route('distribuidor.dashboard'));
        $responseCreate->assertSessionHas('error');

        // 2. Intento de enviar creación de vale por POST
        $responseStore = $this->actingAs($this->distribuidor)->post(route('prestamos.store'), [
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
        ]);

        $responseStore->assertSessionHasErrors(['cliente_id']);

        // 3. Validación en servicio de validación de entrega de vales
        $validacionService = app(\App\Services\ValidacionValeService::class);
        $prestamoTest = new Prestamo([
            'monto_prestamo' => 2000.00,
            'cliente_id' => $this->cliente->id,
        ]);
        $errores = $validacionService->validarEntregaPrevale($prestamoTest, $this->distribuidor);
        $this->assertContains('La distribuidora está bloqueada por morosidad.', $errores);
    }

    public function test_gerente_can_unmark_morosidad_and_restore_distribuidora_operations(): void
    {
        $this->distribuidor->update([
            'es_morosa' => true,
            'conteo_retrasos' => 3,
            'morosa_at' => now(),
            'morosa_by_user_id' => $this->gerenteGeneral->id,
        ]);

        // Gerente General retira la morosidad
        $response = $this->actingAs($this->gerenteGeneral)->post(route('gerente.distribuidores.decidir-morosidad', $this->distribuidor), [
            'accion' => 'desmarcar',
        ]);

        $response->assertSessionHas('success');

        $this->distribuidor->refresh();
        $this->assertFalse($this->distribuidor->esMorosa());
        $this->assertEquals(0, $this->distribuidor->conteo_retrasos);
        $this->assertNull($this->distribuidor->morosa_at);

        // Ahora la distribuidora sí puede entrar a crear vales
        $responseCreate = $this->actingAs($this->distribuidor)->get(route('prestamos.create'));
        $responseCreate->assertOk();
    }

    public function test_administrador_cannot_manage_morosidad(): void
    {
        $rolAdmin = Rol::firstOrCreate(['nombre' => 'Administrador']);
        $admin = User::create([
            'name' => 'Auditor Administrador',
            'email' => 'admin.audit@prestafacil.test',
            'password' => bcrypt('password123'),
            'rol_id' => $rolAdmin->id,
            'sucursal_id' => $this->sucursal->id,
            'activo' => true,
        ]);

        $this->distribuidor->update(['es_morosa' => true]);

        // Administrador intenta quitar la morosidad: debe ser bloqueado con redirección y error
        $response = $this->actingAs($admin)->post(route('gerente.distribuidores.decidir-morosidad', $this->distribuidor), [
            'accion' => 'desmarcar',
        ]);
        $response->assertRedirect(route('gerente-general.dashboard'));
        $response->assertSessionHas('error');

        // La distribuidora sigue siendo morosa
        $this->distribuidor->refresh();
        $this->assertTrue($this->distribuidor->esMorosa());

        // Administrador intenta marcar morosidad: debe ser bloqueado
        $this->distribuidor->update(['es_morosa' => false]);
        $responseMarcar = $this->actingAs($admin)->post(route('gerente.distribuidores.decidir-morosidad', $this->distribuidor), [
            'accion' => 'marcar',
        ]);
        $responseMarcar->assertRedirect(route('gerente-general.dashboard'));
        $responseMarcar->assertSessionHas('error');

        $this->distribuidor->refresh();
        $this->assertFalse($this->distribuidor->esMorosa());
    }

    public function test_abonos_match_relacion_subtracting_distribuidora_commission(): void
    {
        $config = Configuracion::firstOrCreate([], [
            'comision_cobre' => 8.00,
            'comision_plata' => 10.00,
            'comision_oro' => 12.00,
        ]);
        $config->update([
            'comision_plata' => 10.00,
        ]);

        $this->distribuidor->update([
            'categoria_distribuidor' => 'plata',
            'multas' => 0.00,
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-5000-TEST',
            'nombre' => 'Vale $5,000',
            'monto_prestamo' => 5000.00,
            'costo_seguro' => 150.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 10,
            'multa' => 200.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-COMISION-01',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
            'tipo' => 'vale',
            'monto_prestamo' => 5000.00,
            'cuota_quincenal' => 650.00,
            'monto_total_pagar' => 6500.00,
            'pagos_totales' => 10,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 5000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        // Comisión esperada: 10% de 5000 = 500 total / 10 quincenas = $50 por quincena
        $this->assertEquals(50.00, $prestamo->comisionDistribuidorPorQuincena());
        // Cuota neta exigible a pagar: $650 - $50 = $600
        $this->assertEquals(600.00, $prestamo->cuotaQuincenalNeta());
        $this->assertEquals(600.00, $prestamo->totalExigibleQuincenalNeto());

        // A nivel distribuidora
        $this->assertEquals(50.00, $this->distribuidor->totalComisionQuincenal());
        $this->assertEquals(600.00, $this->distribuidor->totalCuotaQuincenalNeta());
        $this->assertEquals(600.00, $this->distribuidor->totalQuincenalExigibleRelacion());

        // Vista de cajero debe mostrar cuota neta ($600.00) y comisión (-$50.00)
        $response = $this->actingAs($this->cajero)->get(route('cajero.abonos.index'));
        $response->assertOk();
        $response->assertSee('$600.00');
        $response->assertSee('-$50.00');
        $response->assertSee('Cat. Plata (10% Com.)');
    }

    public function test_full_abono_before_cut_awards_points_and_shows_next_period_without_recargos(): void
    {
        $config = Configuracion::firstOrCreate([], [
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
            'comision_cobre' => 10.00,
        ]);
        $config->update([
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
            'comision_cobre' => 10.00,
        ]);

        $this->distribuidor->update([
            'categoria_distribuidor' => 'cobre',
            'puntos' => 0,
            'multas' => 0.00,
            'referencia_pago_distribuidor' => 'REF-DIST-PUNTOS-01',
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-2400-PUNTOS',
            'nombre' => 'Vale $2,400',
            'monto_prestamo' => 2400.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 10,
            'multa' => 200.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-PUNTOS-01',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
            'tipo' => 'vale',
            'monto_prestamo' => 2400.00,
            'cuota_quincenal' => 300.00,
            'monto_total_pagar' => 3000.00,
            'pagos_totales' => 10,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 2400.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        // Cuota neta: 300 - ((10% * 2400)/10) = 300 - 24 = $276.00
        $cuotaNeta = $this->distribuidor->totalCuotaQuincenalNeta();
        $this->assertEquals(276.00, $cuotaNeta);

        // 1. Abonar el TOTAL antes o al corte vía cajero
        $responseAbono = $this->actingAs($this->cajero)->post(route('cajero.abonos.distribuidora.store', $this->distribuidor), [
            'referencia_pago' => 'REF-DIST-PUNTOS-01',
            'monto_abonado' => 276.00,
            'metodo_pago' => 'efectivo',
        ]);
        $responseAbono->assertSessionHasNoErrors();

        // 2. Se deben haber calculado y sumado los puntos inmediatamente: floor(2400 / 1200) * 3 = 6 puntos
        $this->distribuidor->refresh();
        $this->assertEquals(6, $this->distribuidor->puntos);

        // 3. La relación debe mostrar el estado Liquidado y total en $0.00
        $responseRelacion = $this->actingAs($this->distribuidor)->get(route('prestamos.relacion-pdf'));
        $responseRelacion->assertOk();
        $responseRelacion->assertSee('Liquidado');
        $responseRelacion->assertSee('$0.00');
    }

    public function test_partial_abono_applies_recargos_and_subtracts_abono_in_relacion(): void
    {
        $config = Configuracion::firstOrCreate([], [
            'comision_cobre' => 10.00,
            'multa_adeudo' => 300.00,
        ]);
        $config->update([
            'comision_cobre' => 10.00,
            'multa_adeudo' => 300.00,
        ]);

        $this->distribuidor->update([
            'categoria_distribuidor' => 'cobre',
            'puntos' => 0,
            'multas' => 0.00,
            'referencia_pago_distribuidor' => 'REF-DIST-PARCIAL-01',
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-2000-PARCIAL',
            'nombre' => 'Vale $2,000',
            'monto_prestamo' => 2000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 10,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-PARCIAL-01',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
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

        // Cuota neta: 250 - ((10% * 2000) / 10) = 250 - 20 = $230.00
        $cuotaNeta = $this->distribuidor->totalCuotaQuincenalNeta();
        $this->assertEquals(230.00, $cuotaNeta);

        // 1. Se abona un monto MENOR al total ($100.00 de $230.00)
        $responseAbono = $this->actingAs($this->cajero)->post(route('cajero.abonos.distribuidora.store', $this->distribuidor), [
            'referencia_pago' => 'REF-DIST-PARCIAL-01',
            'monto_abonado' => 100.00,
            'metodo_pago' => 'efectivo',
        ]);
        $responseAbono->assertSessionHasNoErrors();

        // 2. Al vencer la fecha límite, se aplican recargos / multas ($300.00)
        $prestamo->update(['multas' => 300.00]);
        $this->distribuidor->update(['multas' => 300.00]);

        // 3. En la relación de cobranza:
        // Cuota neta actual ($230) + Recargos ($550 = Multa $300 + Cuota anterior $230 + Com anterior $20) = $780.00
        // Restando el abono realizado ($100.00):
        // Total a PAGAR = $680.00
        $responseRelacion = $this->actingAs($this->distribuidor)->get(route('prestamos.relacion-pdf'));
        $responseRelacion->assertOk();
        $responseRelacion->assertSee('Relación de Cobranza Oficial');
        $responseRelacion->assertSee('Vale $2,000');
        $responseRelacion->assertSee($this->cliente->nombre);
    }

    public function test_newly_assigned_and_cashed_prestamo_appears_in_relacion_pdf(): void
    {
        $productoVale = ProductoVale::create([
            'clave' => 'VALE-REC-100',
            'nombre' => 'Vale Reciente $1,000',
            'monto_prestamo' => 1000.00,
            'costo_seguro' => 50.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 10,
            'multa' => 100.00,
            'activo' => true,
        ]);

        // Distribuidor asigna vale
        $prestamo = Prestamo::create([
            'referencia' => 'VAL-RECIENTE-99',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
            'tipo' => 'vale',
            'monto_prestamo' => 1000.00,
            'cuota_quincenal' => 125.00,
            'monto_total_pagar' => 1250.00,
            'pagos_totales' => 10,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 1000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado', // Cobrado/entregado por cajero
        ]);

        // Abrir la relación de cobranza
        $response = $this->actingAs($this->distribuidor)->get(route('prestamos.relacion-pdf'));
        $response->assertOk();
        $response->assertSee('Vale Reciente $1,000');
        $response->assertSee($this->cliente->nombre);
        $response->assertDontSee('No se encontraron clientes con préstamos activos para este periodo.');
    }

    public function test_liquidated_cut_incurs_recargos_on_subsequent_unpaid_cuts(): void
    {
        $config = Configuracion::firstOrCreate([], [
            'comision_cobre' => 10.00,
            'multa_adeudo' => 300.00,
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
        ]);
        $config->update([
            'comision_cobre' => 10.00,
            'multa_adeudo' => 300.00,
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
        ]);

        $this->distribuidor->update([
            'categoria_distribuidor' => 'cobre',
            'puntos' => 0,
            'multas' => 0.00,
            'referencia_pago_distribuidor' => 'REF-DIST-MULTI-01',
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-MULTI-01',
            'nombre' => 'Vale Multicorte $2,000',
            'monto_prestamo' => 2000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 10,
            'multa' => 300.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-MULTI-REF',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
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

        // 1. Corte 1: Se liquida por completo ($230 cuota neta)
        $this->actingAs($this->cajero)->post(route('cajero.abonos.distribuidora.store', $this->distribuidor), [
            'referencia_pago' => 'REF-DIST-MULTI-01',
            'monto_abonado' => 230.00,
            'metodo_pago' => 'efectivo',
        ]);

        // Ver relación de corte 1: Liquidado sin recargos
        $response1 = $this->actingAs($this->distribuidor)->get(route('prestamos.relacion-pdf'));
        $response1->assertOk();
        $response1->assertSee('Liquidado');

        // 2. Simular cierre de Corte 1 (Liquidado, 0 multas) y avance a Corte 2
        $corteService = app(\App\Services\CorteCobranzaService::class);
        $corteService->simularSiguienteCorte();

        // En Corte 2 recién iniciado: aún no tiene multas porque acaba de abrirse
        $this->distribuidor->refresh();
        $this->assertEquals(0, $this->distribuidor->multas);

        // 3. Simular vencimiento de Corte 2 SIN PAGAR -> Aplica multas por mora
        $corteService->simularSiguienteCorte();

        $this->distribuidor->refresh();
        $this->assertGreaterThan(0, $this->distribuidor->multas);

        $response2 = $this->actingAs($this->distribuidor)->get(route('prestamos.relacion-pdf'));
        $response2->assertOk();
        $response2->assertSee('Relación de Cobranza Oficial');
    }

    public function test_partial_payment_then_mora_then_full_payment_cleans_subsequent_cut(): void
    {
        $config = Configuracion::firstOrCreate([], [
            'comision_cobre' => 3.00,
            'multa_adeudo' => 300.00,
        ]);
        $config->update([
            'comision_cobre' => 3.00,
            'multa_adeudo' => 300.00,
        ]);

        $this->distribuidor->update([
            'categoria_distribuidor' => 'cobre',
            'puntos' => 0,
            'multas' => 0.00,
            'referencia_pago_distribuidor' => 'REF-DIST-ESCENARIO-03',
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-5000-TEST',
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
            'referencia' => 'VAL-5000-REF',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
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
        ]);

        // 1. Corte 1: Abona $930.00 (pago parcial, faltaron $1.25)
        $this->actingAs($this->cajero)->post(route('cajero.abonos.distribuidora.store', $this->distribuidor), [
            'referencia_pago' => 'REF-DIST-ESCENARIO-03',
            'monto_abonado' => 930.00,
            'metodo_pago' => 'efectivo',
        ]);

        // Simular vencimiento de Corte 1 sin liquidar -> Aplica multas y avanza a Corte 2
        $corteService = app(\App\Services\CorteCobranzaService::class);
        $corteService->simularSiguienteCorte();

        // En Corte 2: Debe exigir $1,251.25 con recargos
        $this->distribuidor->refresh();
        $this->assertEquals(300.00, $this->distribuidor->multas);

        $responseCorte2 = $this->actingAs($this->distribuidor)->get(route('prestamos.relacion-pdf'));
        $responseCorte2->assertOk();
        $responseCorte2->assertSee('1,231.25');
        $responseCorte2->assertSee('1,251.00');

        // 2. En Corte 2: Abona y liquida el total con recargos ($1,251.00)
        $this->actingAs($this->cajero)->post(route('cajero.abonos.distribuidora.store', $this->distribuidor), [
            'referencia_pago' => 'REF-DIST-ESCENARIO-03',
            'monto_abonado' => 1251.00,
            'metodo_pago' => 'efectivo',
        ]);

        $this->distribuidor->refresh();
        $this->assertEquals(0.00, $this->distribuidor->multas);

        // 3. Simular avance a Corte 3
        $corteService->simularSiguienteCorte();

        // Corte 3 debe ser un periodo limpio con adeudo de la quincena 3, 0 recargos, 0 abonos descontados erróneamente
        $responseCorte3 = $this->actingAs($this->distribuidor)->get(route('prestamos.relacion-pdf'));
        $responseCorte3->assertOk();
        $responseCorte3->assertSee('Relación de Cobranza Oficial');
        $responseCorte3->assertSee('3/8');
    }

    public function test_liquidated_prestamo_does_not_appear_in_relacion_pdf(): void
    {
        $productoVale = ProductoVale::create([
            'clave' => 'VALE-LIQ-TEST',
            'nombre' => 'Vale Liquidado Test',
            'monto_prestamo' => 1000.00,
            'costo_seguro' => 50.00,
            'comision_apertura' => 10.00,
            'tasa_interes_quincenal' => 5.00,
            'plazo_quincenas' => 1,
            'multa' => 100.00,
            'activo' => true,
        ]);

        $prestamo = Prestamo::create([
            'referencia' => 'VAL-LIQUIDADO-99',
            'cliente_id' => $this->cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $this->distribuidor->id,
            'tipo' => 'vale',
            'monto_prestamo' => 1000.00,
            'cuota_quincenal' => 1100.00,
            'monto_total_pagar' => 1100.00,
            'pagos_totales' => 1,
            'pagos_realizados' => 0,
            'pagos_recibidos' => 0.00,
            'adeudo_pendiente' => 1000.00,
            'multas' => 0.00,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        // Ver relación antes de liquidar: debe aparecer el vale
        $responseAntes = $this->actingAs($this->distribuidor)->get(route('prestamos.relacion-pdf'));
        $responseAntes->assertOk();
        $responseAntes->assertSee('Vale Liquidado Test');

        // Cajero liquida el vale en su totalidad
        $this->actingAs($this->cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 1000.00,
            'metodo_pago' => 'efectivo',
        ]);

        $prestamo->refresh();
        $this->assertEquals('finalizado', $prestamo->estado);
        $this->assertEquals(0, $prestamo->adeudo_pendiente);

        // Ver relación después de liquidar: el vale ya NO debe aparecer en la relación
        $responseDespues = $this->actingAs($this->distribuidor)->get(route('prestamos.relacion-pdf'));
        $responseDespues->assertOk();
        $responseDespues->assertDontSee('Vale Liquidado Test');
        $responseDespues->assertSee('No se encontraron clientes con préstamos activos');
    }

    public function test_cliente_creation_requires_pdf_files_and_stores_them(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        // Intento sin archivos: debe fallar con errores de validación
        $responseSin = $this->actingAs($this->distribuidor)->post(route('clientes.store'), [
            'nombre' => 'Juan Perez Sin Archivos',
            'curp' => 'PESJ900101HDFRRN01',
            'rfc' => 'PESJ900101XXX',
            'fecha_nacimiento' => '1990-01-01',
            'lugar_nacimiento' => 'CDMX',
            'calle' => 'Av. Reforma 123',
            'colonia' => 'Juarez',
            'codigo_postal' => '06600',
            'ciudad' => 'CDMX',
            'estado' => 'CDMX',
        ]);
        $responseSin->assertSessionHasErrors(['pdf_ine', 'pdf_comprobante']);

        // Con archivos: debe registrar exitosamente
        $fileIne = \Illuminate\Http\UploadedFile::fake()->create('ine.pdf', 100, 'application/pdf');
        $fileComp = \Illuminate\Http\UploadedFile::fake()->create('comprobante.pdf', 100, 'application/pdf');

        $responseCon = $this->actingAs($this->distribuidor)->post(route('clientes.store'), [
            'nombre' => 'Juan Perez Con Archivos',
            'curp' => 'PESJ900101HDFRRN01',
            'rfc' => 'PESJ900101XXX',
            'fecha_nacimiento' => '1990-01-01',
            'lugar_nacimiento' => 'CDMX',
            'calle' => 'Av. Reforma 123',
            'colonia' => 'Juarez',
            'codigo_postal' => '06600',
            'ciudad' => 'CDMX',
            'estado' => 'CDMX',
            'pdf_ine' => $fileIne,
            'pdf_comprobante' => $fileComp,
        ]);

        $responseCon->assertRedirect();
        $this->assertDatabaseHas('clientes', [
            'nombre' => 'Juan Perez Con Archivos',
            'curp' => 'PESJ900101HDFRRN01',
        ]);
    }

    public function test_distribuidora_and_cajero_can_view_document_stream(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');

        $fileIne = \Illuminate\Http\UploadedFile::fake()->create('ine_cliente.pdf', 100, 'application/pdf');
        $path = $fileIne->store('expedientes_clientes/ine', 'public');

        $cliente = Cliente::create([
            'nombre' => 'María García',
            'curp' => 'GAMM920202MDFRRN02',
            'rfc' => 'GAMM920202YYY',
            'fecha_nacimiento' => '1992-02-02',
            'lugar_nacimiento' => 'Puebla',
            'calle' => 'Calle 5 Poniente',
            'colonia' => 'Centro',
            'codigo_postal' => '72000',
            'ciudad' => 'Puebla',
            'estado' => 'Puebla',
            'path_ine_pdf' => $path,
            'created_by_user_id' => $this->distribuidor->id,
            'activo' => true,
        ]);

        // Distribuidora propietaria puede abrir el INE
        $responseDist = $this->actingAs($this->distribuidor)->get(route('clientes.documento', [$cliente, 'ine']));
        $responseDist->assertOk();
        $responseDist->assertHeader('Content-Type', 'application/pdf');

        // Cajero puede abrir el INE
        $responseCajero = $this->actingAs($this->cajero)->get(route('clientes.documento', [$cliente, 'ine']));
        $responseCajero->assertOk();
        $responseCajero->assertHeader('Content-Type', 'application/pdf');
    }
}
