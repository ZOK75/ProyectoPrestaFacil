<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Cliente;
use App\Models\ProductoVale;
use App\Models\Rol;
use App\Models\User;
use Tests\TestCase;

class AdministradorRoleTest extends TestCase
{
    public function test_administrador_can_view_all_gerente_general_modules_and_logs()
    {
        $adminRol = Rol::firstOrCreate(['nombre' => 'Administrador']);
        $adminUser = User::factory()->create([
            'rol_id' => $adminRol->id,
            'activo' => true,
        ]);

        // 1. Dashboard
        $response = $this->actingAs($adminUser)->get('/gerente-general/dashboard');
        $response->assertStatus(200);

        // 2. Usuarios
        $response = $this->actingAs($adminUser)->get('/usuarios');
        $response->assertStatus(200);

        // 3. Clientes
        $response = $this->actingAs($adminUser)->get('/clientes');
        $response->assertStatus(200);

        // 4. Préstamos
        $response = $this->actingAs($adminUser)->get('/prestamos');
        $response->assertStatus(200);

        // 5. Vales
        $response = $this->actingAs($adminUser)->get('/producto-vales');
        $response->assertStatus(200);

        // 6. Solicitudes
        $response = $this->actingAs($adminUser)->get('/solicitudes-clientes');
        $response->assertStatus(200);

        // 7. Autorizaciones
        $response = $this->actingAs($adminUser)->get('/autorizaciones');
        $response->assertStatus(200);

        // 8. Configuración
        $response = $this->actingAs($adminUser)->get('/configuracion-general');
        $response->assertStatus(200);

        // 9. Visor de Logs (Tab Auditoría y Tab Sistema)
        $response = $this->actingAs($adminUser)->get('/logs?tab=auditoria');
        $response->assertStatus(200);

        $response = $this->actingAs($adminUser)->get('/logs?tab=sistema');
        $response->assertStatus(200);
    }

    public function test_administrador_cannot_mutate_create_or_update()
    {
        $adminRol = Rol::firstOrCreate(['nombre' => 'Administrador']);
        $adminUser = User::factory()->create([
            'rol_id' => $adminRol->id,
            'activo' => true,
        ]);

        // Intentar registrar usuario
        $response = $this->actingAs($adminUser)->post('/usuarios', [
            'name' => 'Intento Hack',
            'email' => 'hacker@test.com',
            'password' => 'Password123#Secure',
            'password_confirmation' => 'Password123#Secure',
            'rol_id' => $adminRol->id,
        ]);
        $response->assertSessionHas('error');

        // Intentar registrar cliente
        $response = $this->actingAs($adminUser)->post('/clientes', [
            'nombre' => 'Cliente Fake',
            'curp' => 'FAKE123456HDFRRN01',
        ]);
        $response->assertSessionHas('error');

        // Intentar modificar configuración
        $response = $this->actingAs($adminUser)->put('/configuracion-general', [
            'dia_corte' => 12,
            'hora_corte' => '20:00',
            'dia_limite_pago' => 16,
            'hora_limite_pago' => '20:00',
            'multa_adeudo' => 500,
            'comision_cobre' => 4,
            'comision_plata' => 7,
            'comision_oro' => 11,
            'monto_base_puntos' => 1000,
            'puntos_por_monto_base' => 2,
            'valor_punto' => 2,
        ]);
        $response->assertSessionHas('error');
    }
}
