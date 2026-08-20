<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\SolicitudDistribuidor;
use App\Models\SolicitudTransferencia;
use App\Models\SolicitudCredito;
use App\Models\SolicitudAutorizacion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VpnAuthorizationSuiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.vpn_domain' => 'vpn.prestafacil.uk']);
        config(['app.vpn_required' => true]);
    }

    private function crearOperador($rolNombre = 'Gerente General')
    {
        $rol = Rol::firstOrCreate(['nombre' => $rolNombre]);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Test', 'activo' => true]);

        return User::create([
            'name' => 'Usuario Test ' . $rolNombre,
            'email' => strtolower(str_replace(' ', '_', $rolNombre)) . '_' . uniqid() . '@prestafacil.test',
            'password' => bcrypt('password123'),
            'rol_id' => $rol->id,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);
    }

    // ────────────────────────────────────────────────────────
    // PROCESO 1: Aceptar una distribuidora (crear nuevo usuario)
    // ────────────────────────────────────────────────────────
    public function test_proceso_1_aceptar_distribuidora_bloqueado_sin_vpn()
    {
        $gerente = $this->crearOperador('Gerente General');
        $solicitud = SolicitudDistribuidor::create([
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'telefono' => '1234567890',
            'fecha_nacimiento' => '1990-01-01',
            'curp' => 'PEPA900101HDFRRN01',
            'rfc' => 'PEPA900101XXX',
            'calle' => 'Calle 1',
            'colonia' => 'Colonia',
            'codigo_postal' => '12345',
            'ciudad' => 'Ciudad',
            'estado_republica' => 'Estado',
            'datos_casa' => 'Propia',
            'coordinador_id' => $gerente->id,
            'sucursal_id' => $gerente->sucursal_id,
            'estado' => 'en espera',
        ]);

        $response = $this->actingAs($gerente)
            ->from('http://prestafacil.uk/gerente-general/dashboard')
            ->post("http://prestafacil.uk/gerente-general/solicitudes-distribuidoras/{$solicitud->id}/decidir", [
                'accion' => 'aprobar'
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'no tienes autorizacion para completar el proceso');
    }

    public function test_proceso_1_aceptar_distribuidora_permitido_con_vpn()
    {
        $gerente = $this->crearOperador('Gerente General');
        $solicitud = SolicitudDistribuidor::create([
            'nombres' => 'Ana',
            'apellidos' => 'Pérez',
            'telefono' => '1234567890',
            'fecha_nacimiento' => '1990-01-01',
            'curp' => 'PEPA900101HDFRRN01',
            'rfc' => 'PEPA900101XXX',
            'calle' => 'Calle 1',
            'colonia' => 'Colonia',
            'codigo_postal' => '12345',
            'ciudad' => 'Ciudad',
            'estado_republica' => 'Estado',
            'datos_casa' => 'Propia',
            'coordinador_id' => $gerente->id,
            'sucursal_id' => $gerente->sucursal_id,
            'estado' => 'en espera',
        ]);

        $response = $this->actingAs($gerente)
            ->from('http://vpn.prestafacil.uk/gerente-general/dashboard')
            ->post("http://vpn.prestafacil.uk/gerente-general/solicitudes-distribuidoras/{$solicitud->id}/decidir", [
                'accion' => 'aprobar'
            ]);

        $this->assertNotEquals('no tienes autorizacion para completar el proceso', session('error'));
    }

    // ────────────────────────────────────────────────────────
    // PROCESO 2 & 3: Aceptar traspasos (Gerente)
    // ────────────────────────────────────────────────────────
    public function test_proceso_2_y_3_traspaso_distribuidor_y_coordinador_bloqueado_sin_vpn()
    {
        $gerente = $this->crearOperador('Gerente de Sucursal');
        $distribuidor = $this->crearOperador('Distribuidor');

        $transferencia = SolicitudTransferencia::create([
            'distribuidor_id' => $distribuidor->id,
            'coordinador_emisor_id' => $gerente->id,
            'coordinador_receptor_id' => $gerente->id,
            'sucursal_origen_id' => $gerente->sucursal_id,
            'sucursal_destino_id' => $gerente->sucursal_id,
            'motivo' => 'Motivo de prueba',
            'estado' => 'pendiente_gerente',
        ]);

        $response = $this->actingAs($gerente)
            ->from('http://prestafacil.uk/gerente-sucursal/dashboard')
            ->post("http://prestafacil.uk/gerente-sucursal/transferencias/{$transferencia->id}/decidir", [
                'accion' => 'aprobar'
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'no tienes autorizacion para completar el proceso');
    }

    // ────────────────────────────────────────────────────────
    // PROCESO 4, 6, 7: Autorizaciones (Coordinador / Gerente)
    // ────────────────────────────────────────────────────────
    public function test_proceso_4_6_7_autorizaciones_bloqueado_sin_vpn()
    {
        $coordinador = $this->crearOperador('Coordinador');
        $solicitud = SolicitudAutorizacion::create([
            'tipo' => 'modificacion_datos',
            'solicitante_id' => $coordinador->id,
            'sucursal_id' => $coordinador->sucursal_id,
            'entidad_tipo' => 'clientes',
            'entidad_id' => 1,
            'motivo' => 'Prueba',
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($coordinador)
            ->from('http://prestafacil.uk/autorizaciones')
            ->post("http://prestafacil.uk/autorizaciones/{$solicitud->id}/aprobar", [
                'observaciones' => 'Aprobado'
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'no tienes autorizacion para completar el proceso');
    }

    // ────────────────────────────────────────────────────────
    // PROCESO 5: Aumento de crédito (Gerente)
    // ────────────────────────────────────────────────────────
    public function test_proceso_5_aumento_credito_bloqueado_sin_vpn()
    {
        $gerente = $this->crearOperador('Gerente de Sucursal');
        $distribuidor = $this->crearOperador('Distribuidor');

        $solicitudCredito = SolicitudCredito::create([
            'distribuidor_id' => $distribuidor->id,
            'coordinador_id' => $gerente->id,
            'limite_actual' => 10000,
            'limite_nuevo' => 20000,
            'motivo' => 'Solicitud incremento prueba',
            'estado' => 'pendiente',
        ]);

        $response = $this->actingAs($gerente)
            ->from('http://prestafacil.uk/gerente-sucursal/dashboard')
            ->post("http://prestafacil.uk/solicitudes-credito/{$solicitudCredito->id}/procesar", [
                'accion' => 'aprobar'
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'no tienes autorizacion para completar el proceso');
    }

    // ────────────────────────────────────────────────────────
    // PROCESO 8: Modificación datos de distribuidor (Gerente)
    // ────────────────────────────────────────────────────────
    public function test_proceso_8_modificacion_datos_distribuidor_bloqueado_sin_vpn()
    {
        $gerente = $this->crearOperador('Gerente General');
        $usuario = $this->crearOperador('Distribuidor');

        $response = $this->actingAs($gerente)
            ->from('http://prestafacil.uk/usuarios')
            ->put("http://prestafacil.uk/usuarios/{$usuario->id}", [
                'name' => 'Nombre Editado',
                'email' => $usuario->email,
                'rol_id' => $usuario->rol_id,
                'sucursal_id' => $usuario->sucursal_id,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'no tienes autorizacion para completar el proceso');
    }

    public function test_proceso_8_modificacion_datos_distribuidor_permitido_con_vpn()
    {
        $gerente = $this->crearOperador('Gerente General');
        $usuario = $this->crearOperador('Distribuidor');

        $response = $this->actingAs($gerente)
            ->from('http://vpn.prestafacil.uk/usuarios')
            ->put("http://vpn.prestafacil.uk/usuarios/{$usuario->id}", [
                'name' => 'Nombre Editado',
                'email' => $usuario->email,
                'rol_id' => $usuario->rol_id,
                'sucursal_id' => $usuario->sucursal_id,
            ]);

        $this->assertNotEquals('no tienes autorizacion para completar el proceso', session('error'));
    }
}
