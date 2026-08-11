<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Tests\TestCase;

class GerentesRestrictionsTest extends TestCase
{
    public function test_gerente_general_cannot_access_prestamos_clientes_autorizaciones_solicitudes_nor_logs()
    {
        $gerenteGeneralRol = Rol::firstOrCreate(['nombre' => 'Gerente General']);

        $gerenteGeneralUser = User::factory()->create([
            'rol_id' => $gerenteGeneralRol->id,
            'activo' => true,
        ]);

        // 1. Debe poder ver su dashboard, usuarios, vales y configuración
        $response = $this->actingAs($gerenteGeneralUser)->get('/gerente-general/dashboard');
        $response->assertStatus(200);

        $response = $this->actingAs($gerenteGeneralUser)->get('/usuarios');
        $response->assertStatus(200);

        $response = $this->actingAs($gerenteGeneralUser)->get('/producto-vales');
        $response->assertStatus(200);

        $response = $this->actingAs($gerenteGeneralUser)->get('/configuracion-general');
        $response->assertStatus(200);

        // 2. NO debe poder ver logs
        $response = $this->actingAs($gerenteGeneralUser)->get('/logs');
        $response->assertRedirect('/gerente-general/dashboard');
        $response->assertSessionHas('error');

        // 3. NO debe poder ver préstamos
        $response = $this->actingAs($gerenteGeneralUser)->get('/prestamos');
        $response->assertRedirect('/gerente-general/dashboard');
        $response->assertSessionHas('error');

        // 4. NO debe poder ver clientes
        $response = $this->actingAs($gerenteGeneralUser)->get('/clientes');
        $response->assertRedirect('/gerente-general/dashboard');
        $response->assertSessionHas('error');

        // 5. NO debe poder ver autorizaciones
        $response = $this->actingAs($gerenteGeneralUser)->get('/autorizaciones');
        $response->assertRedirect('/gerente-general/dashboard');
        $response->assertSessionHas('error');

        // 6. NO debe poder ver solicitudes de clientes
        $response = $this->actingAs($gerenteGeneralUser)->get('/solicitudes-clientes');
        $response->assertRedirect('/gerente-general/dashboard');
        $response->assertSessionHas('error');
    }

    public function test_gerente_sucursal_cannot_access_prestamos_clientes_autorizaciones_solicitudes_nor_logs()
    {
        $gerenteSucursalRol = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Test 2'], ['activo' => true]);

        $gerenteSucursalUser = User::factory()->create([
            'rol_id' => $gerenteSucursalRol->id,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);

        // 1. Debe poder ver su dashboard, usuarios y vales
        $response = $this->actingAs($gerenteSucursalUser)->get('/gerente-sucursal/dashboard');
        $response->assertStatus(200);

        $response = $this->actingAs($gerenteSucursalUser)->get('/usuarios');
        $response->assertStatus(200);

        $response = $this->actingAs($gerenteSucursalUser)->get('/producto-vales');
        $response->assertStatus(200);

        // 2. NO debe poder ver logs
        $response = $this->actingAs($gerenteSucursalUser)->get('/logs');
        $response->assertRedirect('/gerente-sucursal/dashboard');
        $response->assertSessionHas('error');

        // 3. NO debe poder ver préstamos
        $response = $this->actingAs($gerenteSucursalUser)->get('/prestamos');
        $response->assertRedirect('/gerente-sucursal/dashboard');
        $response->assertSessionHas('error');

        // 4. NO debe poder ver clientes
        $response = $this->actingAs($gerenteSucursalUser)->get('/clientes');
        $response->assertRedirect('/gerente-sucursal/dashboard');
        $response->assertSessionHas('error');

        // 5. NO debe poder ver autorizaciones
        $response = $this->actingAs($gerenteSucursalUser)->get('/autorizaciones');
        $response->assertRedirect('/gerente-sucursal/dashboard');
        $response->assertSessionHas('error');

        // 6. NO debe poder ver solicitudes de clientes
        $response = $this->actingAs($gerenteSucursalUser)->get('/solicitudes-clientes');
        $response->assertRedirect('/gerente-sucursal/dashboard');
        $response->assertSessionHas('error');
    }
}
