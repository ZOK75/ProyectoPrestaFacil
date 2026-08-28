<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\NotificacionCajero;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RealtimeNotificationsAndLogsTest extends TestCase
{
    use RefreshDatabase;

    private function crearRol(string $nombre): Rol
    {
        return Rol::firstOrCreate(['nombre' => $nombre], ['descripcion' => $nombre, 'activo' => true]);
    }

    private function crearSucursal(string $nombre = 'Sucursal Norte'): Sucursal
    {
        return Sucursal::firstOrCreate(['nombre' => $nombre], ['activo' => true]);
    }

    public function test_live_poll_notificaciones_retorna_conteo_y_lista_en_tiempo_real(): void
    {
        $rolDist = $this->crearRol('Distribuidor');
        $distribuidor = User::factory()->create([
            'rol_id' => $rolDist->id,
            'activo' => true,
        ]);

        // Crear 2 notificaciones para el distribuidor
        NotificacionCajero::enviar(
            $distribuidor->id,
            'prestamo_cobrado',
            'Préstamo cobrado en ventanilla',
            'Tu cliente ha cobrado su vale #123456.',
            ['url' => '/prestamos/1']
        );

        NotificacionCajero::enviar(
            $distribuidor->id,
            'corte_generado',
            'Corte quincenal generado',
            'Tu relación de cobranza ya se encuentra disponible.'
        );

        $response = $this->actingAs($distribuidor)->getJson(route('notificaciones.live-poll'));

        $response->assertOk()
            ->assertJsonStructure([
                'unread_count',
                'notifications' => [
                    '*' => [
                        'id',
                        'tipo',
                        'titulo',
                        'mensaje',
                        'leida',
                        'url',
                        'created_at_human',
                        'created_at_full',
                        'timestamp',
                    ]
                ],
                'timestamp',
            ]);

        $this->assertEquals(2, $response->json('unread_count'));
        $this->assertCount(2, $response->json('notifications'));
    }

    public function test_live_logs_endpoint_retorna_auditoria_y_logs_para_administrador(): void
    {
        $rolAdmin = $this->crearRol('Administrador');
        $admin = User::factory()->create([
            'rol_id' => $rolAdmin->id,
            'activo' => true,
        ]);

        AuditLog::registrar(
            'TEST_OPERACION_LIVE',
            'Prueba de registro de auditoría en tiempo real',
            [
                'user_id' => $admin->id,
                'user_rol' => 'Administrador',
                'antes' => ['key' => 'old_value'],
                'despues' => ['key' => 'new_value'],
                'entidad_tipo' => 'test',
                'entidad_id' => '1',
            ]
        );

        $response = $this->actingAs($admin)->getJson(route('logs.live'));

        $response->assertOk()
            ->assertJsonStructure([
                'audit_logs' => [
                    '*' => [
                        'id',
                        'fecha_hora',
                        'fecha_human',
                        'tipo_operacion',
                        'user_name',
                        'user_rol',
                        'descripcion',
                        'ip_address',
                    ]
                ],
                'system_logs',
                'timestamp',
                'total_audit',
            ]);

        $this->assertGreaterThanOrEqual(1, count($response->json('audit_logs')));
        $this->assertTrue(collect($response->json('audit_logs'))->pluck('tipo_operacion')->contains('TEST_OPERACION_LIVE'));
    }

    public function test_live_logs_endpoint_bloqueado_para_roles_no_administradores(): void
    {
        $rolCoordinador = $this->crearRol('Coordinador');
        $coordinador = User::factory()->create([
            'rol_id' => $rolCoordinador->id,
            'activo' => true,
        ]);

        $response = $this->actingAs($coordinador)->getJson(route('logs.live'));

        $response->assertForbidden();
    }

    public function test_verificador_procesa_solicitud_y_genera_log_con_datos_modificados(): void
    {
        config(['app.vpn_required' => false]);

        $rolVerif = $this->crearRol('Verificador');
        $rolCoord = $this->crearRol('Coordinador');
        $sucursal = $this->crearSucursal('Sucursal Centro');

        $verificador = User::factory()->create([
            'rol_id' => $rolVerif->id,
            'sucursal_id' => $sucursal->id,
            'name' => 'Carlos Verificador',
            'activo' => true,
        ]);

        $coordinador = User::factory()->create([
            'rol_id' => $rolCoord->id,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);

        $solicitud = \App\Models\SolicitudDistribuidor::create([
            'nombres' => 'Laura',
            'apellidos' => 'Gomez Lopez',
            'telefono' => '5511223344',
            'fecha_nacimiento' => '1990-05-15',
            'curp' => 'GOLL900515HDFRRN01',
            'rfc' => 'GOLL9005151A2',
            'calle' => 'Av Hidalgo 123',
            'colonia' => 'Centro',
            'codigo_postal' => '06000',
            'ciudad' => 'Cuauhtémoc',
            'estado_republica' => 'CDMX',
            'datos_casa' => 'Casa propia de 2 niveles',
            'coordinador_id' => $coordinador->id,
            'sucursal_id' => $sucursal->id,
            'estado' => 'en espera de verificacion',
        ]);

        // Verificador modifica la calle, el teléfono y el RFC durante su visita presencial
        $response = $this->actingAs($verificador)->post(route('verificador.solicitudes.procesar', $solicitud), [
            'dictamen_verificador' => 'aceptado',
            'comentarios_verificador' => 'Domicilio verificado físicamente, se corrigieron calle y teléfono.',
            'nombres' => 'Laura',
            'apellidos' => 'Gomez Lopez',
            'telefono' => '5599887766', // Modificado
            'fecha_nacimiento' => '1990-05-15',
            'lugar_nacimiento' => 'CDMX',
            'curp' => 'GOLL900515HDFRRN01',
            'rfc' => 'GOLL9005159Z9', // Modificado
            'calle' => 'Av Hidalgo 456 Int 2', // Modificado
            'colonia' => 'Centro',
            'codigo_postal' => '06000',
            'ciudad' => 'Cuauhtémoc',
            'estado_republica' => 'CDMX',
            'datos_casa' => 'Casa propia de 2 niveles con fachada verde', // Modificado
        ]);

        $response->assertRedirect(route('verificador.dashboard'));

        $this->assertDatabaseHas('audit_logs', [
            'tipo_operacion' => 'VERIFICACION_SOLICITUD_DISTRIBUIDOR',
            'entidad_tipo' => 'solicitudes_distribuidores',
            'entidad_id' => $solicitud->id,
            'user_id' => $verificador->id,
        ]);

        $log = AuditLog::where('tipo_operacion', 'VERIFICACION_SOLICITUD_DISTRIBUIDOR')
            ->where('entidad_id', $solicitud->id)
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Carlos Verificador', $log->descripcion);
        $this->assertStringContainsString('ACEPTADO', $log->descripcion);
        $this->assertStringContainsString('Teléfono', $log->descripcion);
        $this->assertStringContainsString('Calle y Número', $log->descripcion);
        $this->assertStringContainsString('RFC', $log->descripcion);

        $this->assertEquals('5511223344', $log->datos_antes['campos']['telefono']);
        $this->assertEquals('5599887766', $log->datos_despues['campos']['telefono']);
        $this->assertEquals('Av Hidalgo 123', $log->datos_antes['campos']['calle']);
        $this->assertEquals('Av Hidalgo 456 Int 2', $log->datos_despues['campos']['calle']);
    }
}
