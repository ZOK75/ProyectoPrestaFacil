<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Conciliacion;
use App\Models\Prestamo;
use App\Models\ProductoVale;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductionFixesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function crearPrestamo(User $distribuidor): Prestamo
    {
        $cliente = Cliente::create([
            'distribuidor_id' => $distribuidor->id,
            'nombre' => 'Cliente Test',
            'apellido_paterno' => 'Paterno',
            'apellido_materno' => 'Materno',
            'curp' => 'TEST' . rand(100000, 999999) . 'HDFRRN00',
            'rfc' => 'TEST' . rand(100000, 999999) . 'XXX',
            'telefono' => '8110000000',
            'fecha_nacimiento' => '1990-01-01',
            'lugar_nacimiento' => 'Monterrey',
            'calle' => 'Calle 1',
            'colonia' => 'Colonia 1',
            'codigo_postal' => '64000',
            'ciudad' => 'Monterrey',
            'estado' => 'Nuevo León',
        ]);

        $producto = ProductoVale::first() ?? ProductoVale::create([
            'clave' => 'VALE1000',
            'nombre' => 'Vale 1000',
            'monto_prestamo' => 1000,
            'plazo_quincenas' => 10,
            'cuota_quincenal' => 100,
            'multa_quincenal' => 50,
            'activo' => true,
        ]);

        return Prestamo::create([
            'referencia' => 'VALE-TEST-' . rand(1000, 9999),
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $producto->id,
            'monto_prestamo' => 1000,
            'monto_total_pagar' => 1000,
            'cuota_quincenal' => 100,
            'plazo_quincenas' => 10,
            'pagos_totales' => 10,
            'adeudo_pendiente' => 1000,
            'pagos_recibidos' => 0,
            'pagos_realizados' => 0,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
            'created_by_user_id' => $distribuidor->id,
        ]);
    }

    /**
     * 1. Test Header Logo Redirects to Dashboard without permissions error
     */
    public function test_header_logo_redirects_to_dashboard(): void
    {
        $cajeroRol = Rol::whereIn('nombre', ['Cajero', 'cajero'])->first();
        $sucursal = Sucursal::first();

        $cajero = User::factory()->create([
            'rol_id' => $cajeroRol->id,
            'sucursal_id' => $sucursal->id,
        ]);

        $response = $this->actingAs($cajero)->get(route('dashboard'));
        $response->assertRedirect(route('cajero.dashboard'));
    }

    /**
     * 2. Test Abono limit error when amount >= 1,000,000
     */
    public function test_abono_limit_validation(): void
    {
        $cajeroRol = Rol::whereIn('nombre', ['Cajero', 'cajero'])->first();
        $distRol = Rol::whereIn('nombre', ['Distribuidor', 'distribuidora'])->first();
        $sucursal = Sucursal::first();

        $cajero = User::factory()->create(['rol_id' => $cajeroRol->id, 'sucursal_id' => $sucursal->id]);
        $distribuidor = User::factory()->create(['rol_id' => $distRol->id, 'sucursal_id' => $sucursal->id]);

        $prestamo = $this->crearPrestamo($distribuidor);

        $response = $this->actingAs($cajero)->post(route('cajero.abonos.store', $prestamo), [
            'monto_abonado' => 1500000,
            'metodo_pago' => 'transferencia',
            'observaciones' => 'Abono excedido',
        ]);

        $response->assertSessionHas('error', 'Límite de un solo abono debe ser menor a 1 millón.');
    }

    /**
     * 3. Test Duplicate Conciliation Prevention
     */
    public function test_duplicate_conciliation_prevention(): void
    {
        $cajeroRol = Rol::whereIn('nombre', ['Cajero', 'cajero'])->first();
        $distRol = Rol::whereIn('nombre', ['Distribuidor', 'distribuidora'])->first();
        $sucursal = Sucursal::first();

        $cajero = User::factory()->create(['rol_id' => $cajeroRol->id, 'sucursal_id' => $sucursal->id]);
        $distribuidor = User::factory()->create(['rol_id' => $distRol->id, 'sucursal_id' => $sucursal->id]);

        $prestamo = $this->crearPrestamo($distribuidor);

        // Crear una conciliación existente
        Conciliacion::create([
            'prestamo_id' => $prestamo->id,
            'referencia_conciliacion' => 'REF-CONC-001',
            'monto_original' => 500,
            'monto_corregido' => 600,
            'motivo' => 'Error de transferencia',
            'solicitante_id' => $cajero->id,
            'estado' => 'pendiente_coordinador',
        ]);

        // Intentar crear otra conciliación con el mismo préstamo
        $response = $this->actingAs($cajero)->post(route('cajero.conciliaciones.store'), [
            'prestamo_id' => $prestamo->id,
            'referencia_conciliacion' => 'REF-CONC-001',
            'monto_original' => 500,
            'monto_corregido' => 600,
            'motivo' => 'Intento duplicado',
        ]);

        $response->assertSessionHas('error', 'Ya existe una solicitud de conciliación manual pendiente para esta referencia o pago.');
    }

    /**
     * 4. Test 2-Step Conciliation Flow (Cajero -> Coordinador -> Gerente)
     */
    public function test_two_step_conciliation_workflow(): void
    {
        $cajeroRol = Rol::whereIn('nombre', ['Cajero', 'cajero'])->first();
        $coordRol = Rol::whereIn('nombre', ['Coordinador', 'coordinador'])->first();
        $gerenteRol = Rol::whereIn('nombre', ['Gerente de Sucursal', 'gerente de sucursal'])->first();
        $distRol = Rol::whereIn('nombre', ['Distribuidor', 'distribuidora'])->first();
        $sucursal = Sucursal::first();

        $cajero = User::factory()->create(['rol_id' => $cajeroRol->id, 'sucursal_id' => $sucursal->id]);
        $coordinador = User::factory()->create(['rol_id' => $coordRol->id, 'sucursal_id' => $sucursal->id]);
        $gerente = User::factory()->create(['rol_id' => $gerenteRol->id, 'sucursal_id' => $sucursal->id]);
        $distribuidor = User::factory()->create(['rol_id' => $distRol->id, 'sucursal_id' => $sucursal->id]);

        $prestamo = $this->crearPrestamo($distribuidor);

        // Paso 1: Cajero solicita conciliación
        $this->actingAs($cajero)->post(route('cajero.conciliaciones.store'), [
            'prestamo_id' => $prestamo->id,
            'referencia_conciliacion' => 'REF-TEST-2STEP',
            'monto_original' => 500,
            'monto_corregido' => 550,
            'motivo' => 'Prueba 2 pasos',
        ]);

        $conciliacion = Conciliacion::where('referencia_conciliacion', 'REF-TEST-2STEP')->first();
        $this->assertNotNull($conciliacion);
        $this->assertEquals('pendiente_coordinador', $conciliacion->estado);

        // Paso 2: Coordinador pre-aprueba
        $this->actingAs($coordinador)->post(route('coordinador.conciliaciones.decidir', $conciliacion), [
            'accion' => 'aceptar',
            'observaciones' => 'Pre-aprobado por coordinador',
        ]);

        $conciliacion->refresh();
        $this->assertEquals('pendiente_gerencia', $conciliacion->estado);

        // Paso 3: Gerente aprueba
        $resGerente = $this->actingAs($gerente)->post(route('gerente.conciliaciones.decidir', $conciliacion), [
            'accion' => 'aceptar',
            'observaciones' => 'Aprobado por gerente de sucursal',
        ]);
        $resGerente->assertSessionHas('success');

        $conciliacion->refresh();
        $this->assertEquals('conciliado', $conciliacion->estado);
        $this->assertEquals($gerente->id, $conciliacion->autorizador_id);
    }

    /**
     * 5. Test Gerente General cannot create Verificadores
     */
    public function test_gerente_general_cannot_create_verificador(): void
    {
        $ggRol = Rol::whereIn('nombre', ['Gerente General', 'gerente general'])->first();
        $verificadorRol = Rol::whereIn('nombre', ['Verificador', 'verificador'])->first();
        $sucursal = Sucursal::first();

        $gerenteGeneral = User::factory()->create(['rol_id' => $ggRol->id, 'sucursal_id' => $sucursal->id]);

        $response = $this->actingAs($gerenteGeneral)->post(route('usuarios.store'), [
            'name' => 'Prueba Verificador',
            'email' => 'verificador.test@prestafacil.com',
            'password' => 'Password123456!',
            'password_confirmation' => 'Password123456!',
            'rol_id' => $verificadorRol->id,
            'sucursal_id' => $sucursal->id,
        ]);

        $response->assertSessionHasErrors(['rol_id']);
    }
}
