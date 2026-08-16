<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Registra un log inmutable de auditoría para operaciones sensibles del cajero.
     *
     * @param string $tipo Ej: 'ENTREGA_PREVALE', 'ABONO_RECIBIDO', 'CANJE_PUNTOS'
     * @param string $descripcion Descripción legible de la acción.
     * @param array $opciones Datos opcionales (antes, despues, entidad_tipo, etc)
     * @return AuditLog|null Retorna el log creado, o null en caso de error extremo.
     */
    public static function registrar(string $tipo, string $descripcion, array $opciones = []): ?AuditLog
    {
        try {
            return AuditLog::registrar($tipo, $descripcion, $opciones);
        } catch (\Exception $e) {
            // Fallback: Si la base de datos falla por alguna razón, al menos
            // queda en el log del sistema (storage/logs/laravel.log)
            Log::error("Fallo al registrar AuditLog [$tipo]: " . $e->getMessage(), [
                'descripcion' => $descripcion,
                'opciones' => $opciones,
                'user' => auth()->id(),
            ]);
            return null;
        }
    }
}
