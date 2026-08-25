<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class LogViewerController extends Controller
{
    /**
     * Muestra el Centro de Logs de Auditoría y de la Aplicación.
     * Exclusivo para el rol de Administrador.
     */
    public function index(Request $request)
    {
        $operador = Auth::user();
        if (!$operador || !$operador->esAdministrador()) {
            $ruta = ($operador && $operador->esGerenteSucursal()) ? 'gerente-sucursal.dashboard' : 'gerente-general.dashboard';
            return redirect()->route($ruta)
                ->with('error', 'Acceso denegado: El Centro de Logs es exclusivo para el rol de Administrador.');
        }

        $tab = $request->input('tab', 'auditoria');

        // 1. Logs de Auditoría (Base de Datos)
        $auditQuery = AuditLog::orderBy('created_at', 'desc');

        if ($request->filled('tipo_operacion')) {
            $auditQuery->where('tipo_operacion', $request->input('tipo_operacion'));
        }

        if ($request->filled('buscar_auditoria')) {
            $buscar = $request->input('buscar_auditoria');
            $auditQuery->where(function ($q) use ($buscar) {
                $q->where('descripcion', 'like', "%{$buscar}%")
                  ->orWhere('tipo_operacion', 'like', "%{$buscar}%")
                  ->orWhere('user_rol', 'like', "%{$buscar}%")
                  ->orWhere('ip_address', 'like', "%{$buscar}%")
                  ->orWhere('entidad_tipo', 'like', "%{$buscar}%");
            });
        }

        $auditLogs = $auditQuery->paginate(20, ['*'], 'audit_page')->withQueryString();

        // Tipos de operaciones únicas para el selector de filtro
        $tiposOperacion = AuditLog::select('tipo_operacion')
            ->distinct()
            ->whereNotNull('tipo_operacion')
            ->pluck('tipo_operacion');

        // 2. Logs del Sistema (Archivo storage/logs/laravel.log)
        $nivelFiltro = $request->input('nivel_sistema');
        $buscarSistema = $request->input('buscar_sistema');
        $systemLogs = $this->parsearLogsSistema($nivelFiltro, $buscarSistema);

        return view('logs.index', compact(
            'tab',
            'auditLogs',
            'tiposOperacion',
            'systemLogs',
            'nivelFiltro',
            'buscarSistema'
        ));
    }

    /**
     * Parsea el archivo laravel.log y devuelve una colección estructurada de registros.
     */
    private function parsearLogsSistema(?string $nivelFiltro = null, ?string $buscar = null): array
    {
        $logPath = storage_path('logs/laravel.log');

        if (!File::exists($logPath)) {
            return [];
        }

        $content = File::get($logPath);
        if (empty(trim($content))) {
            return [];
        }

        // Patrón estándar de logs de Laravel: [YYYY-MM-DD HH:MM:SS] environment.LEVEL: message
        $pattern = '/^\[(?P<date>.*?)\]\s(?P<env>\w+)\.(?P<level>\w+):(?P<message>.*?)(?=(?:^\[|\z))/ms';
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        $parsedLogs = [];
        // Procesamos en orden inverso (los más recientes primero)
        $matches = array_reverse($matches);

        foreach ($matches as $index => $match) {
            $level = strtoupper(trim($match['level']));
            $messageBlock = trim($match['message']);

            // Separar la primera línea del mensaje de la traza (stack trace)
            $lines = explode("\n", $messageBlock);
            $mainMessage = $lines[0] ?? '';
            $stackTrace = count($lines) > 1 ? implode("\n", array_slice($lines, 1)) : '';

            // Filtrado por nivel
            if ($nivelFiltro && strtolower($level) !== strtolower($nivelFiltro)) {
                continue;
            }

            // Filtrado por búsqueda de texto
            if ($buscar && !str_contains(strtolower($messageBlock), strtolower($buscar))) {
                continue;
            }

            $parsedLogs[] = [
                'id' => $index + 1,
                'timestamp' => trim($match['date']),
                'env' => trim($match['env']),
                'level' => $level,
                'message' => $mainMessage,
                'stack_trace' => $stackTrace,
            ];

            // Límite de 100 registros para rendimiento óptimo
            if (count($parsedLogs) >= 100) {
                break;
            }
        }

        return $parsedLogs;
    }
}
