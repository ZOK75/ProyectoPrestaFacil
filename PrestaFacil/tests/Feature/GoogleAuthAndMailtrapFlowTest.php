<?php

namespace Tests\Feature;

use App\Models\Rol;
use App\Models\User;
use App\Notifications\SendEmail2FACode;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Notification;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class GoogleAuthAndMailtrapFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        if (!class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
            $this->markTestSkipped('El paquete pragmarx/google2fa no está instalado en vendor.');
        }

        Rol::firstOrCreate(['nombre' => 'Gerente General']);
        Rol::firstOrCreate(['nombre' => 'Gerente de Sucursal']);
        Rol::firstOrCreate(['nombre' => 'Coordinador']);
        Rol::firstOrCreate(['nombre' => 'Administrador']);
        Rol::firstOrCreate(['nombre' => 'Distribuidor']);
        Rol::firstOrCreate(['nombre' => 'Cajero']);
        Rol::firstOrCreate(['nombre' => 'Verificador']);

        config(['app.vpn_required' => false]);
    }

    public function test_usuario_nuevo_es_redirigido_a_setup_qr_en_primer_login()
    {
        $rolDist = Rol::where('nombre', 'Distribuidor')->first();
        $user = User::factory()->create([
            'rol_id' => $rolDist->id,
            'google2fa_enabled' => false,
            'google2fa_secret' => null,
        ]);

        $response = $this->withSession(['testing_2fa_flow' => true])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('2fa.setup'));
        $this->assertNotNull($user->fresh()->google2fa_secret);
        $this->assertFalse($user->fresh()->google2fa_enabled);
    }

    public function test_confirmacion_de_qr_activa_2fa_y_loguea_distribuidor_sin_correo()
    {
        Notification::fake();
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secret = $google2fa->generateSecretKey();

        $rolDist = Rol::where('nombre', 'Distribuidor')->first();
        $user = User::factory()->create([
            'rol_id' => $rolDist->id,
            'google2fa_enabled' => false,
            'google2fa_secret' => $secret,
        ]);

        $validOtp = $google2fa->getCurrentOtp($secret);

        $response = $this->withSession(['2fa:user_id' => $user->id, 'testing_2fa_flow' => true])
            ->post(route('2fa.setup.confirm'), [
                'one_time_password' => $validOtp,
            ]);

        $this->assertTrue($user->fresh()->google2fa_enabled);
        $this->assertAuthenticatedAs($user);
        Notification::assertNothingSent();
    }

    public function test_confirmacion_de_qr_para_gerente_general_envia_correo_mailtrap()
    {
        Notification::fake();
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secret = $google2fa->generateSecretKey();

        $rolGG = Rol::where('nombre', 'Gerente General')->first();
        $user = User::factory()->create([
            'rol_id' => $rolGG->id,
            'google2fa_enabled' => false,
            'google2fa_secret' => $secret,
        ]);

        $validOtp = $google2fa->getCurrentOtp($secret);

        $response = $this->withSession(['2fa:user_id' => $user->id, 'testing_2fa_flow' => true])
            ->post(route('2fa.setup.confirm'), [
                'one_time_password' => $validOtp,
            ]);

        $response->assertRedirect(route('auth.email-2fa.challenge'));
        $this->assertTrue($user->fresh()->google2fa_enabled);
        Notification::assertSentTo($user, SendEmail2FACode::class);
    }

    public function test_login_subsecuente_pide_2fa_challenge()
    {
        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $secret = $google2fa->generateSecretKey();

        $rolDist = Rol::where('nombre', 'Distribuidor')->first();
        $user = User::factory()->create([
            'rol_id' => $rolDist->id,
            'google2fa_enabled' => true,
            'google2fa_secret' => $secret,
        ]);

        $response = $this->withSession(['testing_2fa_flow' => true])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('2fa.challenge'));
    }

    public function test_verificador_omite_google_auth_y_mailtrap_ingresando_directo()
    {
        $rolVer = Rol::where('nombre', 'Verificador')->first();
        $user = User::factory()->create([
            'rol_id' => $rolVer->id,
            'google2fa_enabled' => false,
            'google2fa_secret' => null,
        ]);

        $response = $this->withSession(['testing_2fa_flow' => true])->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $response->assertRedirect(route('verificador.dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
