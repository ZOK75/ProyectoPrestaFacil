<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\SolicitudCategoria;
use App\Models\SolicitudCredito;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SolicitudesCreditoYCategoriaTest extends TestCase
{
    use DatabaseTransactions;

    protected $rolCoordinador;
    protected $rolGerenteSucursal;
    protected $rolGerenteGeneral;
    protected $rolDistribuidor;
    protected $sucursal;
    protected $sucursal2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rolCoordinador = Rol::firstOrCreate(['nombre' => 'Coordinador']);
        $this->rolGerenteSucursal = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);
        $this->rolGerenteGeneral = Rol::firstOrCreate(['nombre' => 'Gerente General']);
        $this->rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor']);

        $this->sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Test Norte', 'activo' => true]);
        $this->sucursal2 = Sucursal::firstOrCreate(['nombre' => 'Sucursal Test Sur', 'activo' => true]);

        config(['app.vpn_required' => false]);
    }

    public function test_coordinador_solicita_aumento_de_credito_y_notifica_a_gerentes()
    {
        $coordinador = User::factory()->create([
            'rol_id' => $this->rolCoordinador->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $distribuidor = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'coordinador_id' => $coordinador->id,
            'limite_credito' => 20000,
        ]);

        $gerenteSucursal = User::factory()->create([
            'rol_id' => $this->rolGerenteSucursal->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $gerenteGeneral = User::factory()->create([
            'rol_id' => $this->rolGerenteGeneral->id,
        ]);

        $response = $this->actingAs($coordinador)->post(route('coordinador.distribuidores.solicitar-credito', $distribuidor), [
            'limite_nuevo' => 35000,
            'motivo' => 'Excelente historial de pagos y alta demanda de vales.',
        ]);

        $response->assertRedirect(route('coordinador.dashboard'));
        $this->assertDatabaseHas('solicitudes_credito', [
            'distribuidor_id' => $distribuidor->id,
            'coordinador_id' => $coordinador->id,
            'limite_actual' => 20000,
            'limite_nuevo' => 35000,
            'estado' => 'pendiente',
        ]);

        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $gerenteSucursal->id,
            'tipo' => 'solicitud_credito',
        ]);

        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $gerenteGeneral->id,
            'tipo' => 'solicitud_credito',
        ]);
    }

    public function test_gerente_de_sucursal_aprueba_aumento_de_credito_y_se_actualiza_limite()
    {
        $coordinador = User::factory()->create([
            'rol_id' => $this->rolCoordinador->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $distribuidor = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'coordinador_id' => $coordinador->id,
            'limite_credito' => 20000,
        ]);

        $gerenteSucursal = User::factory()->create([
            'rol_id' => $this->rolGerenteSucursal->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $solicitud = SolicitudCredito::create([
            'distribuidor_id' => $distribuidor->id,
            'coordinador_id' => $coordinador->id,
            'limite_actual' => 20000,
            'limite_nuevo' => 45000,
            'motivo' => 'Ampliación de cartera solicitada.',
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($gerenteSucursal)->post(route('solicitudes-credito.procesar', $solicitud), [
            'accion' => 'aprobar',
            'observaciones' => 'Aprobado por excelente colocación.',
        ]);

        $response->assertRedirect(route('gerente-sucursal.dashboard'));
        $this->assertEquals(45000, $distribuidor->fresh()->limite_credito);
        $this->assertEquals('aprobado', $solicitud->fresh()->estado);
        $this->assertEquals($gerenteSucursal->id, $solicitud->fresh()->gerente_id);

        // Notificación al coordinador y a la distribuidora
        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $coordinador->id,
            'tipo' => 'solicitud_credito_aprobada',
        ]);
        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $distribuidor->id,
            'tipo' => 'solicitud_credito_aprobada',
        ]);
    }

    public function test_gerente_general_o_de_sucursal_puede_modificar_datos_de_distribuidor()
    {
        $gerenteSucursal = User::factory()->create([
            'rol_id' => $this->rolGerenteSucursal->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $distribuidor = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'name' => 'Maria Perez',
            'categoria_distribuidor' => 'cobre',
            'limite_credito' => 20000,
        ]);

        $response = $this->actingAs($gerenteSucursal)->put(route('usuarios.update', $distribuidor), [
            'name' => 'Maria Perez Modificada',
            'email' => $distribuidor->email,
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'categoria_distribuidor' => 'plata',
            'limite_credito' => 30000,
            'referencia_pago_distribuidor' => 'REF-MOD-12345',
        ]);

        $response->assertRedirect(route('usuarios.index'));
        $distActualizado = $distribuidor->fresh();
        $this->assertEquals('Maria Perez Modificada', $distActualizado->name);
        $this->assertEquals('plata', $distActualizado->categoria_distribuidor);
        $this->assertEquals(30000, $distActualizado->limite_credito);
        $this->assertEquals('REF-MOD-12345', $distActualizado->referencia_pago_distribuidor);

        $log = \App\Models\AuditLog::where('tipo_operacion', 'ACTUALIZACION_USUARIO')
            ->where('entidad_id', $distribuidor->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Maria Perez Modificada', $log->descripcion);
        $this->assertStringContainsString("Nombre: [Antes: 'Maria Perez' -> Ahora: 'Maria Perez Modificada']", $log->descripcion);
        $this->assertStringContainsString("Categoría: [Antes: 'cobre' -> Ahora: 'plata']", $log->descripcion);
        $this->assertStringContainsString("Límite de Crédito: [Antes: '$20,000.00' -> Ahora: '$30,000.00']", $log->descripcion);
        $this->assertStringContainsString("Referencia de Pago:", $log->descripcion);
    }

    public function test_coordinador_solicita_aumento_de_categoria_y_gerente_la_aprueba()
    {
        $coordinador = User::factory()->create([
            'rol_id' => $this->rolCoordinador->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        $distribuidor = User::factory()->create([
            'rol_id' => $this->rolDistribuidor->id,
            'sucursal_id' => $this->sucursal->id,
            'coordinador_id' => $coordinador->id,
            'categoria_distribuidor' => 'cobre',
        ]);

        $gerenteSucursal = User::factory()->create([
            'rol_id' => $this->rolGerenteSucursal->id,
            'sucursal_id' => $this->sucursal->id,
        ]);

        // Coordinador solicita ascenso a 'oro'
        $response = $this->actingAs($coordinador)->post(route('coordinador.distribuidores.solicitar-categoria', $distribuidor), [
            'categoria_nueva' => 'oro',
            'motivo' => 'La distribuidora superó las metas de colocación quincenales.',
        ]);

        $response->assertRedirect(route('coordinador.dashboard'));

        $solicitud = SolicitudCategoria::where('distribuidor_id', $distribuidor->id)->first();
        $this->assertNotNull($solicitud);
        $this->assertEquals('cobre', $solicitud->categoria_actual);
        $this->assertEquals('oro', $solicitud->categoria_nueva);
        $this->assertEquals('pendiente', $solicitud->estado);

        // Gerente de sucursal aprueba la solicitud
        $responseAprob = $this->actingAs($gerenteSucursal)->post(route('solicitudes-categoria.procesar', $solicitud), [
            'accion' => 'aprobar',
            'observaciones' => 'Ascenso autorizado por mérito.',
        ]);

        $responseAprob->assertRedirect(route('gerente-sucursal.dashboard'));
        $this->assertEquals('oro', $distribuidor->fresh()->categoria_distribuidor);
        $this->assertEquals('aprobado', $solicitud->fresh()->estado);
        $this->assertEquals($gerenteSucursal->id, $solicitud->fresh()->gerente_id);

        // Notificaciones enviadas
        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $coordinador->id,
            'tipo' => 'solicitud_categoria_aprobada',
        ]);
        $this->assertDatabaseHas('notificaciones_cajero', [
            'user_id' => $distribuidor->id,
            'tipo' => 'solicitud_categoria_aprobada',
        ]);
    }
}
