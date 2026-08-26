<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Tests\TestCase;

class AdminUserCreationTest extends TestCase
{
    public function test_administrador_can_only_create_gerente_general()
    {
        $adminRol = Rol::firstOrCreate(['nombre' => 'Administrador']);
        $ggRol = Rol::firstOrCreate(['nombre' => 'Gerente General']);
        $cajeroRol = Rol::firstOrCreate(['nombre' => 'Cajero']);

        $admin = User::factory()->create([
            'email' => 'admin.test.' . rand(10000, 99999) . '@prestafacil.com',
            'rol_id' => $adminRol->id,
            'activo' => true,
        ]);

        // 1. Acceder a formulario de creación como Administrador
        $response = $this->actingAs($admin)->get(route('usuarios.create'));
        $response->assertStatus(200);
        $response->assertSee('<option value="' . $ggRol->id . '"', false);
        $response->assertDontSee('<option value="' . $cajeroRol->id . '"', false);

        // 2. Intentar crear Gerente General (Permitido)
        $uniqueEmailGG = 'gg.creado.por.admin.' . rand(10000, 99999) . '@prestafacil.com';
        $resStoreGG = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Gerente General Creado por Admin',
            'email' => $uniqueEmailGG,
            'password' => 'PasswordSeguro123#',
            'password_confirmation' => 'PasswordSeguro123#',
            'rol_id' => $ggRol->id,
            'sucursal_id' => null,
        ]);
        $resStoreGG->assertRedirect(route('usuarios.index'));
        $this->assertDatabaseHas('users', ['email' => $uniqueEmailGG]);

        // 3. Intentar crear cualquier otro rol (ej. Cajero) (Rechazado)
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Test Admin'], ['activo' => true]);
        $uniqueEmailCajero = 'cajero.admin.fail.' . rand(10000, 99999) . '@prestafacil.com';
        $resStoreCajero = $this->actingAs($admin)->post(route('usuarios.store'), [
            'name' => 'Cajero Intento Admin',
            'email' => $uniqueEmailCajero,
            'password' => 'PasswordSeguro123#',
            'password_confirmation' => 'PasswordSeguro123#',
            'rol_id' => $cajeroRol->id,
            'sucursal_id' => $sucursal->id,
        ]);
        $resStoreCajero->assertSessionHasErrors('rol_id');
        $this->assertDatabaseMissing('users', ['email' => $uniqueEmailCajero]);
    }
}
