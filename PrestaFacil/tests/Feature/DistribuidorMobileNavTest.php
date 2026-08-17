<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DistribuidorMobileNavTest extends TestCase
{
    public function test_distribuidor_navbar_contains_mobile_app_navigation_elements()
    {
        $distribuidorRol = Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        $sucursal = Sucursal::firstOrCreate(['nombre' => 'Sucursal Centro'], ['activo' => true]);

        $distribuidor = User::whereHas('rol', fn($q) => $q->where('nombre', 'Distribuidor'))->first();
        if (!$distribuidor) {
            $distribuidor = User::create([
                'name' => 'Distribuidor Test',
                'email' => 'dist.test.' . uniqid() . '@prestafacil.com',
                'password' => Hash::make('password'),
                'rol_id' => $distribuidorRol->id,
                'sucursal_id' => $sucursal->id,
                'activo' => true,
            ]);
        }

        $response = $this->actingAs($distribuidor)->get('/distribuidor/dashboard');
        $response->assertStatus(200);

        // Validar elementos de navegación móvil y enlaces de la app
        $response->assertSee('Mi Panel');
        $response->assertSee('Clientes');
        $response->assertSee('Préstamos');
        $response->assertSee('Avisos');
    }
}
