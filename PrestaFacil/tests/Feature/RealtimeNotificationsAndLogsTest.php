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
        $this->assertEquals('TEST_OPERACION_LIVE', $response->json('audit_logs.0.tipo_operacion'));
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
}
