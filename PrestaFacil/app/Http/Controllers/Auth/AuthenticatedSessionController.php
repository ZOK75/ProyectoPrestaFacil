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
            return redirect()->route('coordinador.dashboard');
        }

        if ($user->esVerificador()) {
            return redirect()->route('verificador.dashboard');
        }

        return redirect()->route('producto-vales.index');

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
