<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;
use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Writer;
use App\Notifications\SendEmail2FACode;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        session()->forget(['2fa:user_id', '2fa:setup', '2fa:remember', 'email_2fa_user_id', 'email_2fa_code', 'email_2fa_expires_at']);

        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {

        if (! App::environment('testing')) {
            $client = Http::asForm();

            if (App::environment('local')) {
                $client->withoutVerifying();
            }

            $response = $client->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret'   => config('services.recaptcha.secret'),
                'response' => $request->input('g-recaptcha-response'),
                'remoteip' => $request->ip(),
            ]);

            if (! $response->json('success')) {
                return back()->withErrors([
                    'g-recaptcha-response' => 'Por favor, confirma que no eres un robot completando el reCAPTCHA.',
                ])->withInput();
            }
        }


        $request->authenticate();

        $request->session()->regenerate();

        // Limpiamos la URL "intended" para obligar a Laravel a usar nuestro match
        $request->session()->forget('url.intended');

        $user = Auth::user()->load('rol');

        // El rol Verificador solo requiere correo y contraseña (sin Google 2FA ni Mailtrap)
        if ($user->esVerificador()) {
            return $this->proceedAfterGoogle2FA($user, $request);
        }

        $shouldEnforce2FA = !App::environment('testing') || session('testing_2fa_flow', false);

        // CASO 1: Primer inicio de sesión (Usuario no ha vinculado ni activado Google 2FA aún)
        if ($shouldEnforce2FA && !$user->google2fa_enabled && class_exists(\PragmaRX\Google2FA\Google2FA::class)) {
            $google2fa = new \PragmaRX\Google2FA\Google2FA();

            if (!$user->google2fa_secret) {
                $user->google2fa_secret = $google2fa->generateSecretKey();
                $user->save();
            }

            Auth::guard('web')->logout();

            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:setup', true);

            return redirect()->route('2fa.setup');
        }

        // CASO 2: Inicios de sesión subsecuentes (Google 2FA ya está activo)
        if ($shouldEnforce2FA && $user->google2fa_enabled && $user->google2fa_secret) {
            Auth::guard('web')->logout();

            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:remember', $request->boolean('remember'));

            return redirect()->route('2fa.challenge');
        }

        return $this->proceedAfterGoogle2FA($user, $request);
    }

    /**
     * Muestra la pantalla de configuración del código QR para usuarios nuevos.
     */
    public function show2faSetup(Request $request)
    {
        $userId = session('2fa:user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user || !$user->google2fa_secret) {
            return redirect()->route('login');
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA();

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            'PrestaFacil',
            $user->email,
            $user->google2fa_secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(180),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrImage = $writer->writeString($qrCodeUrl);

        return view('auth.2fa-setup', compact('qrImage'));
    }

    /**
     * Confirma el primer código del QR y activa el 2FA.
     */
    public function confirm2faSetup(Request $request): RedirectResponse
    {
        $request->validate([
            'one_time_password' => 'required|digits:6',
        ], [
            'one_time_password.required' => 'Ingresa el código de 6 dígitos.',
            'one_time_password.digits' => 'El código debe contener exactamente 6 dígitos.',
        ]);

        $userId = session('2fa:user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (!$user || !$user->google2fa_secret) {
            return redirect()->route('login');
        }

        $google2fa = new \PragmaRX\Google2FA\Google2FA();
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            $user->google2fa_enabled = true;
            $user->save();

            session()->forget(['2fa:user_id', '2fa:setup']);

            $user = User::with('rol')->find($user->id);

            return $this->proceedAfterGoogle2FA($user, $request);
        }

        return back()->withErrors(['one_time_password' => 'El código de verificación es incorrecto. Escanea nuevamente el código QR en tu app e inténtalo de nuevo.']);
    }

    public function show2faChallenge(Request $request)
    {
        if (!session()->has('2fa:user_id')) {
            return redirect()->route('login');
        }

        return view('auth.2fa-challenge');
    }


    public function verify2fa(Request $request): RedirectResponse
    {
        $userId = session('2fa:user_id');

        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::with('rol')->find($userId);
        $google2fa = new \PragmaRX\Google2FA\Google2FA();

        if (!$user || !$user->google2fa_secret) {
            return redirect()->route('login');
        }


        // Validar el código de 6 dígitos ingresado
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            session()->forget('2fa:user_id');

            $user = User::with('rol')->find($user->id);

            return $this->proceedAfterGoogle2FA($user, $request);
        }

        return back()->withErrors(['one_time_password' => 'El código de verificación es incorrecto.']);
    }

    /**
     * Procesa la lógica posterior a Google 2FA (envío de correo Mailtrap si es rol privilegiado o login directo).
     */
    private function proceedAfterGoogle2FA(User $user, Request $request): RedirectResponse
    {
        $user->load('rol');

        $shouldEnforce2FA = !App::environment('testing') || session('testing_2fa_flow', false);

        // Solamente roles de alta gerencia (Gerente General, Administrador, Gerente Sucursal) requieren verificación por correo
        if ($shouldEnforce2FA && ($user->esGerenteGeneral() || $user->esAdministrador() || $user->esGerenteSucursal())) {
            Auth::guard('web')->logout();

            $code = rand(100000, 999999);

            session([
                'email_2fa_user_id'    => $user->id,
                'email_2fa_code'       => $code,
                'email_2fa_expires_at' => now()->addMinutes(10),
            ]);

            try {
                $user->notify(new SendEmail2FACode($code));
                Log::info('Correo 2FA de Mailtrap enviado correctamente a: ' . $user->email);
            } catch (\Throwable $e) {
                Log::error('Falla al enviar correo 2FA Mailtrap a ' . $user->email . ': ' . $e->getMessage());
            }

            return redirect()->route('auth.email-2fa.challenge');
        }

        if (!Auth::check()) {
            Auth::login($user);
            $request->session()->regenerate();
        }

        if ($user->esGerenteGeneral() || $user->esAdministrador()) {
            return redirect()->route('gerente-general.dashboard');
        }

        if ($user->esGerenteSucursal()) {
            return redirect()->route('gerente-sucursal.dashboard');
        }

        if ($user->esCoordinador()) {
            return redirect()->route('coordinador.dashboard');
        }

        if ($user->esDistribuidor()) {
            return redirect()->route('distribuidor.dashboard');
        }

        if ($user->esCajero()) {
            return redirect()->route('cajero.dashboard');
        }

        if ($user->esVerificador()) {
            return redirect()->route('verificador.dashboard');
        }

        return redirect()->route('producto-vales.index');
    }

    public function showEmail2FAChallenge()
    {
        $userId = session('email_2fa_user_id');
        $expiresAt = session('email_2fa_expires_at');

        if (!$userId || !$expiresAt || now()->greaterThan($expiresAt)) {
            session()->forget(['email_2fa_user_id', 'email_2fa_code', 'email_2fa_expires_at']);

            return redirect()->route('login')->withErrors([
                'email' => 'La sesión de verificación por correo ha expirado o no es válida. Por favor ingresa tus datos nuevamente.',
            ]);
        }

        return view('auth.email-2fa-challenge');
    }

    public function verifyEmail2FA(Request $request)
    {
        $request->validate([
            'code' => 'required|numeric',
        ]);

        $sessionCode = session('email_2fa_code');
        $expiresAt = session('email_2fa_expires_at');
        $userId = session('email_2fa_user_id');

        if (!$userId || !$sessionCode || now()->greaterThan($expiresAt)) {
            return back()->withErrors(['code' => 'El código ha expirado o es inválido. Inténtalo de nuevo.']);
        }

        if ($request->code == $sessionCode) {
            // Limpiar variables de sesión del 2FA
            session()->forget(['email_2fa_code', 'email_2fa_expires_at', 'email_2fa_user_id']);

            // Loguear formalmente al usuario
            Auth::loginUsingId($userId);
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors(['code' => 'El código ingresado es incorrecto.']);
    }

    public function resendEmail2FA()
    {
        $userId = session('email_2fa_user_id');
        
        if (!$userId) {
            return redirect()->route('login');
        }

        $user = User::findOrFail($userId);
        $code = rand(100000, 999999);

        session([
            'email_2fa_code' => $code,
            'email_2fa_expires_at' => now()->addMinutes(10),
        ]);

        try {
            $user->notify(new SendEmail2FACode($code));
            Log::info('Reenvío de correo 2FA Mailtrap exitoso para: ' . $user->email);
        } catch (\Throwable $e) {
            Log::error('Error al reenviar correo 2FA Mailtrap a ' . $user->email . ': ' . $e->getMessage());
        }

        return back()->with('status', 'Hemos reenviado un nuevo código a tu correo.');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
