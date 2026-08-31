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

    public function test_proceso_verificador_edicion_solicitud_bloqueado_sin_vpn()
    {
        $verificador = $this->crearOperador('Verificador');
        $solicitud = SolicitudDistribuidor::create([
            'nombres' => 'Juan',
            'apellidos' => 'Gómez',
            'telefono' => '1234567890',
            'fecha_nacimiento' => '1990-01-01',
            'curp' => 'GOMJ900101HDFRRN01',
            'rfc' => 'GOMJ900101XXX',
            'calle' => 'Calle 2',
            'colonia' => 'Colonia',
            'codigo_postal' => '12345',
            'ciudad' => 'Ciudad',
            'estado_republica' => 'Estado',
            'datos_casa' => 'Propia',
            'coordinador_id' => $verificador->id,
            'sucursal_id' => $verificador->sucursal_id,
            'estado' => 'en espera de verificacion',
        ]);

        $response = $this->actingAs($verificador)
            ->from('http://prestafacil.uk/verificador/dashboard')
            ->post("http://prestafacil.uk/verificador/solicitudes/{$solicitud->id}/procesar", [
                'dictamen' => 'aprobado',
                'comentarios_verificador' => 'Todo correcto',
                'nombres' => 'Juan Modificado',
                'apellidos' => 'Gómez',
                'telefono' => '1234567890',
                'calle' => 'Calle 2',
                'colonia' => 'Colonia',
                'codigo_postal' => '12345',
                'ciudad' => 'Ciudad',
                'estado_republica' => 'Estado',
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

    public function test_proceso_morosidad_bloqueado_sin_vpn()
    {
        $gerente = $this->crearOperador('Gerente de Sucursal');
        $distribuidor = $this->crearOperador('Distribuidor');

        $response = $this->actingAs($gerente)
            ->from('http://prestafacil.uk/gerente-sucursal/dashboard')
            ->post("http://prestafacil.uk/distribuidores/{$distribuidor->id}/decidir-morosidad", [
                'accion' => 'quitar',
                'motivo' => 'Regularización autorizada',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'no tienes autorizacion para completar el proceso');
    }

    public function test_proceso_traspaso_coordinador_bloqueado_sin_vpn()
    {
        $gerenteA = $this->crearOperador('Gerente de Sucursal');
        $gerenteB = $this->crearOperador('Gerente de Sucursal');
        $coordinador = $this->crearOperador('Coordinador');
        $coordinador->update(['sucursal_id' => $gerenteA->sucursal_id]);

        $response = $this->actingAs($gerenteA)
            ->from('http://prestafacil.uk/gerente-sucursal/dashboard')
            ->post("http://prestafacil.uk/gerente-sucursal/coordinadores/traspasar", [
                'coordinador_id' => $coordinador->id,
                'gerente_receptor_id' => $gerenteB->id,
                'motivo' => 'Transferencia por cobertura',
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'no tienes autorizacion para completar el proceso');
    }

    public function test_proceso_creacion_usuario_bloqueado_sin_vpn()
    {
        $gerente = $this->crearOperador('Gerente General');
        $rolDist = Rol::firstOrCreate(['nombre' => 'Distribuidor']);

        $response = $this->actingAs($gerente)
            ->from('http://prestafacil.uk/usuarios/create')
            ->post("http://prestafacil.uk/usuarios", [
                'name' => 'Nuevo Usuario Prueba',
                'email' => 'nuevo_usuario_' . uniqid() . '@test.com',
                'password' => 'Password12345!',
                'password_confirmation' => 'Password12345!',
                'rol_id' => $rolDist->id,
                'sucursal_id' => $gerente->sucursal_id,
            ]);

        $response->assertStatus(302);
        $response->assertSessionHas('error', 'no tienes autorizacion para completar el proceso');
    }
}
