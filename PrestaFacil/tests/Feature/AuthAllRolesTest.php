<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAllRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_seeded_roles_can_login_without_419_error()
    {
        $this->seed();

        $sucursal = Sucursal::first();

        $accounts = [
            'admin.sistema@prestafacil.com' => '/gerente-general/dashboard',
        ];

        $rolesToTest = [
            'Gerente General' => ['email' => 'gerente.general@prestafacil.com', 'redirect' => '/gerente-general/dashboard', 'sucursal_id' => null],
            'Gerente de Sucursal' => ['email' => 'gerente.centro@prestafacil.com', 'redirect' => '/gerente-sucursal/dashboard', 'sucursal_id' => $sucursal?->id],
            'Distribuidor' => ['email' => 'distribuidor.centro@prestafacil.com', 'redirect' => '/distribuidor/dashboard', 'sucursal_id' => $sucursal?->id],
            'Cajero' => ['email' => 'cajero.norte@prestafacil.com', 'redirect' => '/cajero/dashboard', 'sucursal_id' => $sucursal?->id],
            'Coordinador' => ['email' => 'coordinador.centro@prestafacil.com', 'redirect' => '/coordinador/dashboard', 'sucursal_id' => $sucursal?->id],
        ];

        foreach ($rolesToTest as $rolNombre => $data) {
            $rol = Rol::where('nombre', $rolNombre)->first();
            User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => "Usuario $rolNombre",
                    'password' => bcrypt('password'),
                    'email_verified_at' => now(),
                    'rol_id' => $rol?->id,
                    'sucursal_id' => $data['sucursal_id'],
                    'activo' => true,
                ]
            );
            $accounts[$data['email']] = $data['redirect'];
        }

        foreach ($accounts as $email => $expectedRedirect) {
            $user = User::where('email', $email)->first();
            $this->assertNotNull($user, "El usuario $email debe existir en la base de datos.");

            $user->password = bcrypt('password');
            $user->save();

            $response = $this->post('/login', [
                'email' => $email,
                'password' => 'password',
            ]);

            $response->assertStatus(302);
            $response->assertRedirect($expectedRedirect);

            $this->assertAuthenticatedAs($user);

            // Cerrar sesión para la siguiente iteración
            $this->post('/logout');
            $this->assertGuest();
        }
    }
}
