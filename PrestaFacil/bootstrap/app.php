<?php

use App\Http\Middleware\CheckRole;
use App\Http\Middleware\EnsureVpnAccess;
use App\Http\Middleware\VerificarCorteMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            VerificarCorteMiddleware::class,
        ]);

        $middleware->alias([
            'role' => CheckRole::class,
            'require.vpn' => EnsureVpnAccess::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // Manejador para error 419 (TokenMismatchException)
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'La sesión ha expirado. Por favor recarga la página e intenta de nuevo.',
                ], 419);
            }

            return redirect()->route('login')
                ->withInput($request->except('password', '_token'))
                ->withErrors(['email' => 'La sesión o el token de seguridad expiraron. Por favor ingresa tus datos nuevamente.']);
        });

        // Manejador para HTTP 419 genérico
        $exceptions->render(function (HttpException $e, $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'La sesión ha expirado. Por favor recarga la página e intenta de nuevo.',
                    ], 419);
                }

                return redirect()->route('login')
                    ->withInput($request->except('password', '_token'))
                    ->withErrors(['email' => 'La sesión o el token de seguridad expiraron. Por favor ingresa tus datos nuevamente.']);
            }
        });

        // Manejador global de excepciones (error 500 genérico seguro sin exponer datos)
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Error del servidor',
                    'message' => '¡Ops! Algo salió mal, por favor inténtalo más tarde.',
                ], 500);
            }

            if (!app()->environment('testing') && !config('app.debug')) {
                return response()->view('errors.500', [], 500);
            }
        });
    })->create();
