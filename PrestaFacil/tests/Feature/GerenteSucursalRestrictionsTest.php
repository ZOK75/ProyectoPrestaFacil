<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Tests\TestCase;

class GerenteSucursalRestrictionsTest extends TestCase
{
    public function test_gerente_sucursal_cannot_access_prestamos_clientes_autorizaciones_nor_solicitudes()
    {
        $gerenteSucursalRol = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Test'], ['activo' => true]);

        $gerenteUser = User::factory()->create([
            'rol_id' => $gerenteSucursalRol->id,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);

        // 1. Debe poder ver su dashboard, usuarios y vales
        $response = $this->actingAs($gerenteUser)->get('/gerente-sucursal/dashboard');
        $response->assertStatus(200);

        $response = $this->actingAs($gerenteUser)->get('/usuarios');
        $response->assertStatus(200);

        $response = $this->actingAs($gerenteUser)->get('/producto-vales');
        $response->assertStatus(200);

        // 2. NO debe poder ver préstamos
        $response = $this->actingAs($gerenteUser)->get('/prestamos');
        $response->assertRedirect('/gerente-sucursal/dashboard');
        $response->assertSessionHas('error');

        // 3. NO debe poder ver clientes
        $response = $this->actingAs($gerenteUser)->get('/clientes');
        $response->assertRedirect('/gerente-sucursal/dashboard');
        $response->assertSessionHas('error');

        // 4. NO debe poder ver autorizaciones
        $response = $this->actingAs($gerenteUser)->get('/autorizaciones');
        $response->assertRedirect('/gerente-sucursal/dashboard');
        $response->assertSessionHas('error');

        // 5. NO debe poder ver solicitudes de clientes
        $response = $this->actingAs($gerenteUser)->get('/solicitudes-clientes');
        $response->assertRedirect('/gerente-sucursal/dashboard');
        $response->assertSessionHas('error');
    }
}
