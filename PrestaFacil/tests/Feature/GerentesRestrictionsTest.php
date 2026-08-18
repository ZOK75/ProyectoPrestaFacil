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

    public function test_gerentes_cannot_create_distribuidora_and_category_notice_is_removed()
    {
        $gerenteGeneralRol = Rol::firstOrCreate(['nombre' => 'Gerente General']);
        $gerenteSucursalRol = Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);
        $rolDistribuidor = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $rolCajero = Rol::firstOrCreate(['nombre' => 'Cajero']);

        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Central'], ['activo' => true]);

        $gerenteGeneral = User::factory()->create([
            'rol_id' => $gerenteGeneralRol->id,
            'activo' => true,
        ]);

        $gerenteSucursal = User::factory()->create([
            'rol_id' => $gerenteSucursalRol->id,
            'sucursal_id' => $sucursal->id,
            'activo' => true,
        ]);

        // 1. Gerente General visita /usuarios/create: no debe ver el rol Distribuidor ni el mensaje de categoría
        $responseGG = $this->actingAs($gerenteGeneral)->get(route('usuarios.create'));
        $responseGG->assertStatus(200);
        $responseGG->assertDontSee('<option value="' . $rolDistribuidor->id . '">Distribuidor</option>', false);
        $responseGG->assertDontSee('Categoría Inicial Automática:');
        $responseGG->assertDontSee('Categoría Cobre');

        // 2. Gerente de Sucursal visita /usuarios/create: no debe ver el rol Distribuidor ni el mensaje de categoría
        $responseGS = $this->actingAs($gerenteSucursal)->get(route('usuarios.create'));
        $responseGS->assertStatus(200);
        $responseGS->assertDontSee('<option value="' . $rolDistribuidor->id . '">Distribuidor</option>', false);
        $responseGS->assertDontSee('Categoría Inicial Automática:');
        $responseGS->assertDontSee('Categoría Cobre');

        // 3. Intento de POST con rol Distribuidor por Gerente General -> Debe ser rechazado
        $responsePostGG = $this->actingAs($gerenteGeneral)->post(route('usuarios.store'), [
            'name' => 'Intento Distribuidora GG',
            'email' => 'intento.dist.gg@prestafacil.com',
            'password' => 'PasswordSeguro123#',
            'password_confirmation' => 'PasswordSeguro123#',
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $sucursal->id,
        ]);
        $responsePostGG->assertSessionHasErrors('rol_id');

        // 4. Intento de POST con rol Distribuidor por Gerente de Sucursal -> Debe ser rechazado
        $responsePostGS = $this->actingAs($gerenteSucursal)->post(route('usuarios.store'), [
            'name' => 'Intento Distribuidora GS',
            'email' => 'intento.dist.gs@prestafacil.com',
            'password' => 'PasswordSeguro123#',
            'password_confirmation' => 'PasswordSeguro123#',
            'rol_id' => $rolDistribuidor->id,
            'sucursal_id' => $sucursal->id,
        ]);
        $responsePostGS->assertSessionHasErrors('rol_id');

        // 5. Creación de un rol permitido (ej. Cajero) debe funcionar normalmente
        $responseValid = $this->actingAs($gerenteGeneral)->post(route('usuarios.store'), [
            'name' => 'Cajero Valido',
            'email' => 'cajero.valido@prestafacil.com',
            'password' => 'PasswordSeguro123#',
            'password_confirmation' => 'PasswordSeguro123#',
            'rol_id' => $rolCajero->id,
            'sucursal_id' => $sucursal->id,
        ]);
        $responseValid->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseHas('users', ['email' => 'cajero.valido@prestafacil.com']);
    }
}
