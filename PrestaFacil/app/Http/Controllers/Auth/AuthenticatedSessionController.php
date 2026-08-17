<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

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
            return redirect()->route('autorizaciones.index');
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
