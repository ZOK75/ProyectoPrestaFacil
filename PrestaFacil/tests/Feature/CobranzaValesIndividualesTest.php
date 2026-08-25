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
}
