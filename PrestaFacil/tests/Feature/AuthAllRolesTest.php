<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthAllRolesTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_seeded_roles_can_login_without_419_error()
    {
        $this->seed();

        $accounts = [
            'admin.sistema@prestafacil.com' => '/gerente-general/dashboard',
            'gerente.general@prestafacil.com' => '/gerente-general/dashboard',
            'gerente.centro@prestafacil.com' => '/gerente-sucursal/dashboard',
            'distribuidor.centro@prestafacil.com' => '/distribuidor/dashboard',
            'cajero.norte@prestafacil.com' => '/cajero/dashboard',
            'coordinador.centro@prestafacil.com' => '/coordinador/dashboard',
        ];

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

            $this->assertAuthenticatedAs($user);

            // Cerrar sesión para la siguiente iteración
            $this->post('/logout');
            $this->assertGuest();
        }
    }
}
