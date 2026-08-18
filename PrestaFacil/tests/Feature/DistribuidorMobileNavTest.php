<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DistribuidorMobileNavTest extends TestCase
{
    public function test_distribuidor_navbar_contains_mobile_app_navigation_elements()
    {
        $distribuidorRol = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Centro'], ['activo' => true]);

        $distribuidor = User::whereHas('rol', fn($q) => $q->where('nombre', 'Distribuidor'))->first();
        if (!$distribuidor) {
            $distribuidor = User::create([
                'name' => 'Distribuidor Test',
                'email' => 'dist.test.' . uniqid() . '@prestafacil.com',
                'password' => Hash::make('password'),
                'rol_id' => $distribuidorRol->id,
                'sucursal_id' => $sucursal->id,
                'activo' => true,
            ]);
        }

        $response = $this->actingAs($distribuidor)->get('/distribuidor/dashboard');
        $response->assertStatus(200);

        // Validar elementos de navegación móvil y enlaces de la app
        $response->assertSee('Mi Panel');
        $response->assertSee('Clientes');
        $response->assertSee('Préstamos');
        $response->assertSee('Avisos');
    }

    public function test_distribuidor_assigns_vale_as_pendiente_and_reserves_credit()
    {
        $rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Norte'], ['activo' => true]);

        $distribuidor = User::factory()->create([
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $sucursal->id,
            'limite_credito' => 30000.00,
            'activo' => true,
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Cliente Prueba Uno',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1995-01-01',
            'lugar_nacimiento' => 'Mérida',
            'calle' => 'Calle 10 #100',
            'colonia' => 'Centro',
            'codigo_postal' => '97000',
            'ciudad' => 'Mérida',
            'estado' => 'Yucatán',
            'activo' => true,
            'created_by_user_id' => $distribuidor->id,
        ]);

        $productoVale = \App\Models\ProductoVale::create([
            'clave' => 'VALE-5000-' . uniqid(),
            'nombre' => 'Vale $5,000',
            'monto_prestamo' => 5000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 14,
            'activo' => true,
        ]);

        // Crédito inicial
        $this->assertEquals(30000.00, $distribuidor->creditoDisponible());
        $this->assertEquals(0.0, $distribuidor->creditoUtilizado());

        // Distribuidor asigna el vale
        $response = $this->actingAs($distribuidor)->post(route('prestamos.store'), [
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
        ]);

        $prestamo = \App\Models\Prestamo::where('cliente_id', $cliente->id)->first();
        $this->assertNotNull($prestamo);
        $response->assertRedirect(route('prestamos.show', $prestamo));

        // El préstamo DEBE estar en estado 'pendiente'
        $this->assertEquals('pendiente', $prestamo->estado);
        $this->assertEquals('pendiente', $prestamo->estado_entrega);
        $this->assertTrue($prestamo->esPendiente());
        $this->assertFalse($prestamo->esActivo());

        // REGLA: Mientras el vale está pendiente, el límite de crédito NO cambia (creditoUtilizado = 0, creditoDisponible = 30000)
        $distribuidor->refresh();
        $this->assertEquals(0.00, $distribuidor->creditoUtilizado());
        $this->assertEquals(30000.00, $distribuidor->creditoDisponible());

        // No permite crear otro vale al mismo cliente mientras tenga uno pendiente
        $responseSegundo = $this->actingAs($distribuidor)->post(route('prestamos.store'), [
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
        ]);
        $responseSegundo->assertSessionHasErrors('cliente_id');
    }

    public function test_distribuidor_can_deactivate_pending_vale_and_credit_is_released()
    {
        $rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Norte'], ['activo' => true]);

        $distribuidor = User::factory()->create([
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $sucursal->id,
            'limite_credito' => 20000.00,
            'activo' => true,
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Cliente Cancelacion',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1995-02-02',
            'lugar_nacimiento' => 'Mérida',
            'calle' => 'Calle 20 #200',
            'colonia' => 'Centro',
            'codigo_postal' => '97000',
            'ciudad' => 'Mérida',
            'estado' => 'Yucatán',
            'activo' => true,
            'created_by_user_id' => $distribuidor->id,
        ]);

        $productoVale = \App\Models\ProductoVale::create([
            'clave' => 'VALE-4000-' . uniqid(),
            'nombre' => 'Vale $4,000',
            'monto_prestamo' => 4000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 14,
            'activo' => true,
        ]);

        $this->actingAs($distribuidor)->post(route('prestamos.store'), [
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
        ]);

        $prestamo = \App\Models\Prestamo::where('cliente_id', $cliente->id)->first();
        $this->assertEquals('pendiente', $prestamo->estado);

        // Desactivar vale pendiente
        $responseDelete = $this->actingAs($distribuidor)->delete(route('prestamos.destroy', $prestamo));
        $responseDelete->assertRedirect(route('prestamos.index'));
        $responseDelete->assertSessionHas('success');

        $prestamo->refresh();
        $this->assertEquals('desactivado', $prestamo->estado);
        $this->assertEquals('cancelado', $prestamo->estado_entrega);
        $this->assertFalse($prestamo->activo);

        // El crédito se mantiene limpio y disponible
        $distribuidor->refresh();
        $this->assertEquals(0.0, $distribuidor->creditoUtilizado());
        $this->assertEquals(20000.00, $distribuidor->creditoDisponible());
    }

    public function test_cajero_delivers_vale_and_becomes_activo_cannot_be_deactivated_by_distribuidor()
    {
        $rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $rolCajero = Rol::firstOrCreate(['nombre' => 'Cajero']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Norte'], ['activo' => true]);

        $distribuidor = User::factory()->create([
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $sucursal->id,
            'limite_credito' => 20000.00,
            'activo' => true,
        ]);

        $cajero = User::factory()->create([
            'rol_id' => $rolCajero->id,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);

        $cliente = \App\Models\Cliente::create([
            'nombre' => 'Cliente Entrega',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1995-03-03',
            'lugar_nacimiento' => 'Mérida',
            'calle' => 'Calle 30 #300',
            'colonia' => 'Centro',
            'codigo_postal' => '97000',
            'ciudad' => 'Mérida',
            'estado' => 'Yucatán',
            'activo' => true,
            'created_by_user_id' => $distribuidor->id,
        ]);

        $productoVale = \App\Models\ProductoVale::create([
            'clave' => 'VALE-3000-' . uniqid(),
            'nombre' => 'Vale $3,000',
            'monto_prestamo' => 3000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 14,
            'activo' => true,
        ]);

        // Asignar vale
        $this->actingAs($distribuidor)->post(route('prestamos.store'), [
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
        ]);

        $prestamo = \App\Models\Prestamo::where('cliente_id', $cliente->id)->first();
        $this->assertEquals('pendiente', $prestamo->estado);

        // Antes de la entrega, el crédito aún no ha cambiado
        $distribuidor->refresh();
        $this->assertEquals(0.00, $distribuidor->creditoUtilizado());
        $this->assertEquals(20000.00, $distribuidor->creditoDisponible());

        // Cajero entrega el prevale
        $responseEntrega = $this->actingAs($cajero)->post(route('cajero.prevale.entregar', $prestamo), [
            'numero_transferencia' => 'TRF-123456',
            'monto_depositado' => 3000.00,
        ]);
        $responseEntrega->assertRedirect(route('cajero.dashboard'));

        $prestamo->refresh();
        $this->assertEquals('activo', $prestamo->estado);
        $this->assertEquals('entregado', $prestamo->estado_entrega);
        $this->assertTrue($prestamo->esActivo());
        $this->assertFalse($prestamo->esPendiente());

        // REGLA: Ahora que está activo, el límite de crédito SÍ cambia
        $distribuidor->refresh();
        $this->assertEquals(3000.00, $distribuidor->creditoUtilizado());
        $this->assertEquals(17000.00, $distribuidor->creditoDisponible());

        // Distribuidor intenta desactivarlo -> Debe ser rechazado
        $responseDelete = $this->actingAs($distribuidor)->delete(route('prestamos.destroy', $prestamo));
        $responseDelete->assertSessionHas('error');

        $prestamo->refresh();
        $this->assertEquals('activo', $prestamo->estado);
    }

    public function test_distribuidor_only_sees_and_assigns_vales_to_own_registered_clients()
    {
        $rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Norte'], ['activo' => true]);

        $distribuidor1 = User::factory()->create([
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $sucursal->id,
            'limite_credito' => 20000.00,
            'activo' => true,
        ]);

        $distribuidor2 = User::factory()->create([
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $sucursal->id,
            'limite_credito' => 20000.00,
            'activo' => true,
        ]);

        // Cliente registrado por distribuidor 1
        $clientePropio = \App\Models\Cliente::create([
            'nombre' => 'Cliente Propio D1',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1995-04-04',
            'lugar_nacimiento' => 'Mérida',
            'calle' => 'Calle 40 #400',
            'colonia' => 'Centro',
            'codigo_postal' => '97000',
            'ciudad' => 'Mérida',
            'estado' => 'Yucatán',
            'activo' => true,
            'created_by_user_id' => $distribuidor1->id,
        ]);

        // Cliente registrado por distribuidor 2
        $clienteAjeno = \App\Models\Cliente::create([
            'nombre' => 'Cliente Ajeno D2',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1995-05-05',
            'lugar_nacimiento' => 'Mérida',
            'calle' => 'Calle 50 #500',
            'colonia' => 'Centro',
            'codigo_postal' => '97000',
            'ciudad' => 'Mérida',
            'estado' => 'Yucatán',
            'activo' => true,
            'created_by_user_id' => $distribuidor2->id,
        ]);

        $productoVale = \App\Models\ProductoVale::create([
            'clave' => 'VALE-2000-' . uniqid(),
            'nombre' => 'Vale $2,000',
            'monto_prestamo' => 2000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 14,
            'activo' => true,
        ]);

        // Distribuidor 1 entra a prestamos.create: solo debe ver a $clientePropio en el select
        $responseCreate = $this->actingAs($distribuidor1)->get(route('prestamos.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Cliente Propio D1');
        $responseCreate->assertDontSee('Cliente Ajeno D2');

        // Distribuidor 1 intenta asignar un vale al cliente ajeno -> debe ser rechazado
        $responseStoreAjeno = $this->actingAs($distribuidor1)->post(route('prestamos.store'), [
            'cliente_id' => $clienteAjeno->id,
            'producto_vale_id' => $productoVale->id,
        ]);
        $responseStoreAjeno->assertSessionHasErrors('cliente_id');

        // Distribuidor 1 asigna vale a su propio cliente -> éxito
        $responseStorePropio = $this->actingAs($distribuidor1)->post(route('prestamos.store'), [
            'cliente_id' => $clientePropio->id,
            'producto_vale_id' => $productoVale->id,
        ]);
        $responseStorePropio->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prestamos', [
            'cliente_id' => $clientePropio->id,
            'created_by_user_id' => $distribuidor1->id,
            'estado' => 'pendiente',
        ]);
    }
}
