<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Prestamo;
use App\Models\Rol;
use App\Models\SolicitudTransferenciaCoordinador;
use App\Models\SolicitudTraspasoCliente;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TraspasosYReasignacionesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear o buscar roles
        Rol::firstOrCreate(['nombre' => 'Gerente General']);
        Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);
        Rol::firstOrCreate(['nombre' => 'Coordinador']);
        Rol::firstOrCreate(['nombre' => 'Distribuidor']);

        config(['app.vpn_required' => false]);
    }

    public function test_gerente_general_puede_mover_gerente_sucursal_en_cascada()
    {
        $rolGG = Rol::where('nombre', 'Gerente General')->first();
        $rolGS = Rol::where('nombre', 'Gerente de Sucursal')->first();
        $rolCoord = Rol::where('nombre', 'Coordinador')->first();
        $rolDist = Rol::where('nombre', 'Distribuidor')->first();

        $sucursalA = Sucursal::create(['nombre' => 'Sucursal Norte', 'activo' => true]);
        $sucursalB = Sucursal::create(['nombre' => 'Sucursal Sur', 'activo' => true]);

        $gg = User::factory()->create(['rol_id' => $rolGG->id]);
        $gerente = User::factory()->create(['rol_id' => $rolGS->id, 'sucursal_id' => $sucursalA->id]);
        $coordinador = User::factory()->create(['rol_id' => $rolCoord->id, 'sucursal_id' => $sucursalA->id]);
        $distribuidor = User::factory()->create(['rol_id' => $rolDist->id, 'sucursal_id' => $sucursalA->id, 'coordinador_id' => $coordinador->id]);

        $response = $this->actingAs($gg)->post(route('gerente-general.reasignar-gerente'), [
            'gerente_id' => $gerente->id,
            'nueva_sucursal_id' => $sucursalB->id,
        ]);

        $response->assertRedirect();
        $this->assertEquals($sucursalB->id, $gerente->fresh()->sucursal_id);
        $this->assertEquals($sucursalB->id, $coordinador->fresh()->sucursal_id);
        $this->assertEquals($sucursalB->id, $distribuidor->fresh()->sucursal_id);
    }

    public function test_no_se_puede_traspasar_cliente_con_prestamo_activo()
    {
        $rolDist = Rol::where('nombre', 'Distribuidor')->first();
        $distribuidorA = User::factory()->create(['rol_id' => $rolDist->id]);
        $distribuidorB = User::factory()->create(['rol_id' => $rolDist->id]);

        $cliente = Cliente::create([
            'nombre' => 'Juan Perez',
            'curp' => 'PEPJ900101HDFRRR01',
            'fecha_nacimiento' => '1990-01-01',
            'lugar_nacimiento' => 'CDMX',
            'calle' => 'Calle Falsa 123',
            'colonia' => 'Centro',
            'codigo_postal' => '06000',
            'ciudad' => 'Ciudad de Mexico',
            'estado' => 'CDMX',
            'created_by_user_id' => $distribuidorA->id,
            'activo' => true,
        ]);

        $productoVale = \App\Models\ProductoVale::create([
            'clave' => 'VALE-5000',
            'nombre' => 'Vale Test',
            'monto_prestamo' => 5000,
            'cuota_quincenal' => 500,
            'pagos_totales' => 10,
            'plazo_quincenas' => 10,
            'activo' => true,
        ]);

        // Crear préstamo activo
        Prestamo::create([
            'referencia' => 'VALE-TEST-1234',
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
            'created_by_user_id' => $distribuidorA->id,
            'monto_prestamo' => 5000,
            'cuota_quincenal' => 500,
            'pagos_totales' => 10,
            'pagos_realizados' => 5,
            'monto_total_pagar' => 5000,
            'adeudo_pendiente' => 2500,
            'estado' => 'activo',
            'estado_entrega' => 'entregado',
        ]);

        $this->assertTrue($cliente->tieneProductosActivos());

        $response = $this->actingAs($distribuidorA)->post(route('clientes.traspasar', $cliente), [
            'distribuidor_receptor_id' => $distribuidorB->id,
            'motivo' => 'Cambio de zona',
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('solicitudes_traspaso_clientes', [
            'cliente_id' => $cliente->id,
        ]);
    }

    public function test_traspaso_distribuidora_notifica_a_coordinador_receptor_y_gerentes()
    {
        $rolGG = Rol::where('nombre', 'Gerente General')->first();
        $rolGS = Rol::where('nombre', 'Gerente de Sucursal')->first();
        $rolCoord = Rol::where('nombre', 'Coordinador')->first();
        $rolDist = Rol::where('nombre', 'Distribuidor')->first();

        $sucursalA = Sucursal::create(['nombre' => 'Sucursal Norte A', 'activo' => true]);
        $sucursalB = Sucursal::create(['nombre' => 'Sucursal Sur B', 'activo' => true]);

        $gg = User::factory()->create(['rol_id' => $rolGG->id]);
        $gerenteA = User::factory()->create(['rol_id' => $rolGS->id, 'sucursal_id' => $sucursalA->id]);
        $gerenteB = User::factory()->create(['rol_id' => $rolGS->id, 'sucursal_id' => $sucursalB->id]);
        $coordA = User::factory()->create(['rol_id' => $rolCoord->id, 'sucursal_id' => $sucursalA->id]);
        $coordB = User::factory()->create(['rol_id' => $rolCoord->id, 'sucursal_id' => $sucursalB->id]);
        $dist = User::factory()->create(['rol_id' => $rolDist->id, 'sucursal_id' => $sucursalA->id, 'coordinador_id' => $coordA->id]);

        $response = $this->actingAs($coordA)->post(route('coordinador.distribuidores.solicitar-transferencia', $dist), [
            'coordinador_receptor_id' => $coordB->id,
            'motivo' => 'Cambio de domicilio a la zona sur',
        ]);

        $response->assertRedirect(route('coordinador.dashboard'));
        $this->assertDatabaseHas('solicitudes_transferencias', [
            'distribuidor_id' => $dist->id,
            'coordinador_emisor_id' => $coordA->id,
            'coordinador_receptor_id' => $coordB->id,
            'estado' => 'pendiente_coordinador',
        ]);

        // Notificación al coordinador receptor
        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $coordB->id,
            'tipo' => 'transferencia_distribuidora',
        ]);

        // Notificación a Gerentes de Sucursal (Origen y Destino)
        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $gerenteA->id,
            'tipo' => 'solicitud_transferencia',
        ]);
        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $gerenteB->id,
            'tipo' => 'solicitud_transferencia',
        ]);

        // Notificación al Gerente General
        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $gg->id,
            'tipo' => 'solicitud_transferencia',
        ]);
    }

    public function test_traspaso_coordinador_notifica_a_gerente_receptor_y_gerente_general()
    {
        $rolGG = Rol::where('nombre', 'Gerente General')->first();
        $rolGS = Rol::where('nombre', 'Gerente de Sucursal')->first();
        $rolCoord = Rol::where('nombre', 'Coordinador')->first();

        $sucursalA = Sucursal::create(['nombre' => 'Sucursal Norte A2', 'activo' => true]);
        $sucursalB = Sucursal::create(['nombre' => 'Sucursal Sur B2', 'activo' => true]);

        $gg = User::factory()->create(['rol_id' => $rolGG->id]);
        $gerenteA = User::factory()->create(['rol_id' => $rolGS->id, 'sucursal_id' => $sucursalA->id]);
        $gerenteB = User::factory()->create(['rol_id' => $rolGS->id, 'sucursal_id' => $sucursalB->id]);
        $coord = User::factory()->create(['rol_id' => $rolCoord->id, 'sucursal_id' => $sucursalA->id]);

        $response = $this->actingAs($gerenteA)->post(route('gerente-sucursal.coordinadores.traspasar'), [
            'coordinador_id' => $coord->id,
            'gerente_receptor_id' => $gerenteB->id,
            'motivo' => 'Transferencia por apertura de nueva sucursal',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('solicitudes_transferencia_coordinadores', [
            'coordinador_id' => $coord->id,
            'gerente_emisor_id' => $gerenteA->id,
            'gerente_receptor_id' => $gerenteB->id,
            'estado' => 'pendiente_gerente_receptor',
        ]);

        // Notificación al Gerente Receptor
        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $gerenteB->id,
            'tipo' => 'solicitud_traspaso_coordinador',
        ]);

        // Notificación al Gerente General
        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $gg->id,
            'tipo' => 'solicitud_traspaso_coordinador',
        ]);
    }
}
