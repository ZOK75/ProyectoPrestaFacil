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
        $diaCorteInicial = now()->day;
        $config->update([
            'dia_corte' => $diaCorteInicial,
            'hora_corte' => now()->subMinutes(30)->format('H:i:s'),
            'dia_limite_pago' => $diaCorteInicial,
            'hora_limite_pago' => now()->subMinute()->format('H:i:s'),
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
        $fechaEsperadaCorte = now()->setDay($diaCorteInicial)->addDays(15);
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
}
