<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\NotificacionCajero;
use App\Models\Prestamo;
use App\Models\ProductoVale;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class NotificacionPrestamoCobradoTest extends TestCase
{
    use WithFaker;

    public function test_distribuidor_and_coordinador_receive_notification_when_client_cashes_loan_at_cashier()
    {
        $rolCoordinador = Rol::firstOrCreate(['nombre' => 'Coordinador']);
        $rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $rolCajero = Rol::firstOrCreate(['nombre' => 'Cajero']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Mérida Centro'], ['activo' => true]);

        $coordinador = User::factory()->create([
            'rol_id' => $rolCoordinador->id,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);

        $distribuidor = User::factory()->create([
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $sucursal->id,
            'coordinador_id' => $coordinador->id,
            'limite_credito' => 25000.00,
            'activo' => true,
        ]);

        $cajero = User::factory()->create([
            'rol_id' => $rolCajero->id,
            'sucursal_id' => $sucursal->id,
            'name' => 'Carlos Cajero',
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Roberto Gómez',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1992-06-15',
            'lugar_nacimiento' => 'Mérida',
            'calle' => 'Calle 50 #123',
            'colonia' => 'Centro',
            'codigo_postal' => '97000',
            'ciudad' => 'Mérida',
            'estado' => 'Yucatán',
            'activo' => true,
            'created_by_user_id' => $distribuidor->id,
        ]);

        $productoVale = ProductoVale::create([
            'clave' => 'VALE-6000-' . uniqid(),
            'nombre' => 'Vale $6,000',
            'monto_prestamo' => 6000.00,
            'costo_seguro' => 100.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 14,
            'activo' => true,
        ]);

        // Distribuidor asigna el vale (estado pendiente)
        $this->actingAs($distribuidor)->post(route('prestamos.store'), [
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
        ]);

        $prestamo = Prestamo::where('cliente_id', $cliente->id)->first();
        $this->assertNotNull($prestamo);
        $this->assertEquals('pendiente', $prestamo->estado);

        // Cajero entrega / cobra el prevale en ventanilla
        $response = $this->actingAs($cajero)->post(route('cajero.prevale.entregar', $prestamo), [
            'numero_transferencia' => 'TRF-COBRO-998877',
            'monto_depositado' => 6000.00,
        ]);

        $response->assertRedirect(route('cajero.dashboard'));

        $prestamo->refresh();
        $this->assertEquals('activo', $prestamo->estado);
        $this->assertEquals('entregado', $prestamo->estado_entrega);

        // 1. Validar notificación para la distribuidora
        $notifDistribuidor = NotificacionCajero::where('user_id', $distribuidor->id)
            ->where('tipo', 'prestamo_cobrado')
            ->latest()
            ->first();

        $this->assertNotNull($notifDistribuidor);
        $this->assertStringContainsString('Roberto Gómez', $notifDistribuidor->mensaje);
        $this->assertStringContainsString('6,000', $notifDistribuidor->mensaje);
        $this->assertStringContainsString('Carlos Cajero', $notifDistribuidor->mensaje);
        $this->assertStringContainsString('Sucursal Mérida Centro', $notifDistribuidor->mensaje);

        // 2. Validar notificación para el coordinador
        $notifCoordinador = NotificacionCajero::where('user_id', $coordinador->id)
            ->where('tipo', 'prestamo_cobrado')
            ->latest()
            ->first();

        $this->assertNotNull($notifCoordinador);
        $this->assertStringContainsString('Roberto Gómez', $notifCoordinador->mensaje);
        $this->assertStringContainsString($distribuidor->name, $notifCoordinador->mensaje);

        // 3. Validar que la distribuidora pueda ver la notificación en su centro de notificaciones
        $responseView = $this->actingAs($distribuidor)->get(route('notificaciones.index'));
        $responseView->assertStatus(200);
        $responseView->assertSee('¡Préstamo Cobrado en Ventanilla!');
        $responseView->assertSee('Roberto Gómez');
    }
}
