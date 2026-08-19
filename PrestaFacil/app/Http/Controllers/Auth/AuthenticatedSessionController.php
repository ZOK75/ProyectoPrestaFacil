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


class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {

        $client = Http::asForm();

        if(App::environment('local')) {
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


        $request->authenticate();

        $request->session()->regenerate();

        // Limpiamos la URL "intended" para obligar a Laravel a usar nuestro match
        $request->session()->forget('url.intended');

        $user = Auth::user()->load('rol');

        if ($user->google2fa_enabled && $user->google2fa_secret) {
            // Deslogueamos del guard web para evitar acceso directo al dashboard
            Auth::guard('web')->logout();

            // Guardamos temporalmente el ID en la sesión DESPUÉS del logout
            $request->session()->put('2fa:user_id', $user->id);
            $request->session()->put('2fa:remember', $request->boolean('remember'));

            return redirect()->route('2fa.challenge');
        }
        

        if ($user->esGerenteGeneral() || $user->esAdministrador()) {
            return redirect()->route('gerente-general.dashboard');
        }

        if ($user->esGerenteSucursal()) {
            return redirect()->route('gerente-sucursal.dashboard');
        }

        if ($user->esDistribuidor()) {
            return redirect()->route('distribuidor.dashboard');
        }

        if ($user->esCajero()) {
            return redirect()->route('cajero.dashboard');
        }

        if ($user->esCoordinador()) {
            return redirect()->route('autorizaciones.index');
        }

        if ($user->esVerificador()) {
            return redirect()->route('verificador.dashboard');
        }

        return redirect()->route('producto-vales.index');

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

        $user = User::find($userId);
        $google2fa = new Google2FA();

        if (!$user || !$user->google2fa_secret) {
            return redirect()->route('login');
        }


        // Validar el código de 6 dígitos ingresado
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            session()->forget('2fa:user_id');
            Auth::login($user);
            $request->session()->regenerate(); 

            // Una vez validado el 2FA, ejecutamos tu redirección por roles
            $user->load('rol');

            if ($user->esGerenteGeneral() || $user->esAdministrador()) {
                return redirect()->route('gerente-general.dashboard');
            }
            
            if ($user->esGerenteSucursal()) {
                return redirect()->route('gerente-sucursal.dashboard');
            }

            if ($user->esDistribuidor()) {
                return redirect()->route('distribuidor.dashboard');
            }

            if ($user->esCajero()) {
                return redirect()->route('cajero.dashboard');
            }

            if ($user->esCoordinador()) {
                return redirect()->route('autorizaciones.index');
            }

            if ($user->esVerificador()) {
                return redirect()->route('verificador.dashboard');
            }

            return redirect()->route('producto-vales.index');
        }

        return back()->withErrors(['one_time_password' => 'El código de verificación es incorrecto.']);
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
