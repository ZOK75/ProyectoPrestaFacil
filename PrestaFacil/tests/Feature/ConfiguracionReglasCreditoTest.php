<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\ProductoVale;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ConfiguracionReglasCreditoTest extends TestCase
{
    public function test_gerente_general_can_update_reglas_credito_in_configuracion()
    {
        $rolGerente = Rol::firstOrCreate(['nombre' => 'Gerente General']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Matriz'], ['activo' => true]);

        $gerente = User::factory()->create([
            'rol_id' => $rolGerente->id,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);

        $config = Configuracion::actual();

        $response = $this->actingAs($gerente)->put(route('configuracion-general.update'), [
            'dia_corte' => 12,
            'hora_corte' => '20:00',
            'dia_limite_pago' => 17,
            'hora_limite_pago' => '22:00',
            'multa_adeudo' => 350.00,
            'comision_cobre' => 4.00,
            'comision_plata' => 7.00,
            'comision_oro' => 11.00,
            'porcentaje_regla_prevale' => 40.00,
            'tolerancia_regla_prevale' => 800.00,
            'monto_base_puntos' => 1500.00,
            'puntos_por_monto_base' => 4,
            'valor_punto' => 2.50,
            'motivo' => 'Ajuste de prueba de reglas de crédito',
        ]);

        $response->assertRedirect(route('configuracion-general.edit'));
        $response->assertSessionHas('success');

        $config->refresh();
        $this->assertEquals(40.00, floatval($config->porcentaje_regla_prevale));
        $this->assertEquals(800.00, floatval($config->tolerancia_regla_prevale));
    }

    public function test_distribuidor_maximo_por_vale_updates_dynamically_with_configuracion()
    {
        $rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Matriz'], ['activo' => true]);

        $distribuidor = User::factory()->create([
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $sucursal->id,
            'limite_credito' => 20000.00,
            'activo' => true,
        ]);

        $config = Configuracion::actual();
        
        // 1. Con regla estándar (50% + $500) -> 20,000 * 0.50 + 500 = $10,500
        $config->update([
            'porcentaje_regla_prevale' => 50.00,
            'tolerancia_regla_prevale' => 500.00,
        ]);

        $this->assertEquals(10500.00, $distribuidor->montoMaximoPermitidoPorVale());

        // 2. Modificando configuración a (30% + $1,000) -> 20,000 * 0.30 + 1,000 = $7,000
        $config->update([
            'porcentaje_regla_prevale' => 30.00,
            'tolerancia_regla_prevale' => 1000.00,
        ]);

        $this->assertEquals(7000.00, $distribuidor->montoMaximoPermitidoPorVale());
    }

    public function test_vale_assignment_respects_newly_configured_max_limit()
    {
        $rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Matriz'], ['activo' => true]);

        $distribuidor = User::factory()->create([
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $sucursal->id,
            'limite_credito' => 20000.00,
            'activo' => true,
        ]);

        $cliente = Cliente::create([
            'nombre' => 'Cliente Regla Credito',
            'curp' => substr('CURP' . strtoupper(bin2hex(random_bytes(7))), 0, 18),
            'fecha_nacimiento' => '1990-01-01',
            'lugar_nacimiento' => 'Mérida',
            'calle' => 'Calle 1 #100',
            'colonia' => 'Centro',
            'codigo_postal' => '97000',
            'ciudad' => 'Mérida',
            'estado' => 'Yucatán',
            'activo' => true,
            'created_by_user_id' => $distribuidor->id,
        ]);

        // Vale de $8,000
        $productoVale = ProductoVale::create([
            'clave' => 'VALE-8K-' . uniqid(),
            'nombre' => 'Vale $8,000',
            'monto_prestamo' => 8000.00,
            'costo_seguro' => 200.00,
            'comision_apertura' => 0.00,
            'tasa_interes_quincenal' => 2.50,
            'plazo_quincenas' => 14,
            'activo' => true,
        ]);

        // Configuración restrictiva: 30% + $500 -> Tope = (20,000 * 0.30) + 500 = $6,500
        Configuracion::actual()->update([
            'porcentaje_regla_prevale' => 30.00,
            'tolerancia_regla_prevale' => 500.00,
        ]);

        // Intento de emitir vale de $8,000 cuando el tope es $6,500 -> Debe fallar con validación
        $response = $this->actingAs($distribuidor)->post(route('prestamos.store'), [
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
        ]);

        $response->assertSessionHasErrors('producto_vale_id');

        // Ahora flexibilizamos la configuración: 40% + $1,000 -> Tope = (20,000 * 0.40) + 1,000 = $9,000
        Configuracion::actual()->update([
            'porcentaje_regla_prevale' => 40.00,
            'tolerancia_regla_prevale' => 1000.00,
        ]);

        // Intento de emitir el mismo vale de $8,000 -> Debe ser exitoso
        $responseOk = $this->actingAs($distribuidor)->post(route('prestamos.store'), [
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $productoVale->id,
        ]);

        $responseOk->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prestamos', [
            'cliente_id' => $cliente->id,
            'monto_prestamo' => 8000.00,
            'estado' => 'pendiente',
        ]);
    }
}
