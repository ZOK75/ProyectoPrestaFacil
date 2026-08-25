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

        // Manejador para fallos de conexión con Servidor de Base de Datos Remoto
        $exceptions->render(function (\PDOException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Error de conexión a la base de datos',
                    'message' => 'El servidor de la base de datos no responde o no está disponible temporalmente. Inténtalo de nuevo más tarde.',
                ], 503);
            }

            return response()->view('errors.database', [
                'exception' => $e,
            ], 503);
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            $prev = $e->getPrevious();
            $msg = strtolower($e->getMessage() . ($prev ? ' ' . $prev->getMessage() : ''));
            $isConnectionFailure = $prev instanceof \PDOException || (
                str_contains($msg, 'connection refused') ||
                str_contains($msg, 'timed out') ||
                str_contains($msg, 'timeout') ||
                str_contains($msg, 'no route to host') ||
                str_contains($msg, 'network is unreachable') ||
                str_contains($msg, 'server has gone away') ||
                str_contains($msg, 'could not connect') ||
                str_contains($msg, 'target machine actively refused') ||
                str_contains($msg, 'getaddrinfo failed') ||
                str_contains($msg, '[2002]') ||
                str_contains($msg, 'access denied') ||
                str_contains($msg, 'lost connection')
            );

            if ($isConnectionFailure) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'error' => 'Error de conexión a la base de datos',
                        'message' => 'El servidor de la base de datos no responde o no está disponible temporalmente. Inténtalo de nuevo más tarde.',
                    ], 503);
                }

                return response()->view('errors.database', [
                    'exception' => $e,
                ], 503);
            }
        });

        // Manejador global de excepciones (error 500 genérico seguro sin exponer datos)
        $exceptions->render(function (\Throwable $e, $request) {
            if ($request->expectsJson()) {
                $mensaje = ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && !empty(trim($e->getMessage())))
                    ? $e->getMessage()
                    : '¡Ops! Algo salió mal, por favor inténtalo más tarde.';

                return response()->json([
                    'error' => 'Error del servidor',
                    'message' => $mensaje,
                ], 500);
            }

            if (!app()->environment('testing') && !config('app.debug')) {
                return response()->view('errors.500', [
                    'exception' => $e,
                ], 500);
            }
        });
    })->create();
