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

    /**
     * Compara los datos anteriores y posteriores de un usuario y genera un desglose
     * legible de los campos modificados con sus valores previos y nuevos.
     */
    public static function describirCambiosUsuario(array $antes, array $despues, array $inputData = []): array
    {
        $etiquetas = [
            'name' => 'Nombre',
            'email' => 'Correo Electrónico',
            'telefono' => 'Teléfono',
            'rol_id' => 'Rol',
            'sucursal_id' => 'Sucursal',
            'coordinador_id' => 'Coordinador',
            'limite_credito' => 'Límite de Crédito',
            'categoria_distribuidor' => 'Categoría',
            'referencia_pago_distribuidor' => 'Referencia de Pago',
            'activo' => 'Estado',
            'es_morosa' => 'Morosidad',
            'multas' => 'Multas',
            'puntos' => 'Puntos',
        ];

        $cambios = [];
        $detalles = [];

        foreach ($despues as $campo => $nuevoValor) {
            if (in_array($campo, ['password', 'remember_token', 'updated_at', 'created_at', 'email_verified_at', 'google2fa_secret', 'google2fa_enabled'])) {
                continue;
            }

            $valorOriginal = $antes[$campo] ?? null;

            // Ignorar diferencias numéricas triviales (ej. 20000 vs 20000.00)
            if (is_numeric($valorOriginal) && is_numeric($nuevoValor) && floatval($valorOriginal) == floatval($nuevoValor)) {
                continue;
            }

            $origStr = is_bool($valorOriginal) ? ($valorOriginal ? 'true' : 'false') : (is_array($valorOriginal) ? json_encode($valorOriginal, JSON_UNESCAPED_UNICODE) : trim((string)$valorOriginal));
            $nuevoStr = is_bool($nuevoValor) ? ($nuevoValor ? 'true' : 'false') : (is_array($nuevoValor) ? json_encode($nuevoValor, JSON_UNESCAPED_UNICODE) : trim((string)$nuevoValor));

            if ($origStr !== $nuevoStr) {
                $label = $etiquetas[$campo] ?? ucfirst(str_replace('_', ' ', $campo));

                if ($campo === 'rol_id') {
                    $rolAnt = \App\Models\Rol::find($valorOriginal)?->nombre ?? ($valorOriginal ?: '(sin rol)');
                    $rolNue = \App\Models\Rol::find($nuevoValor)?->nombre ?? ($nuevoValor ?: '(sin rol)');
                    $detalles[] = "{$label}: [Antes: '{$rolAnt}' -> Ahora: '{$rolNue}']";
                } elseif ($campo === 'sucursal_id') {
                    $sucAnt = \App\Models\Sucursal::find($valorOriginal)?->nombre ?? ($valorOriginal ?: '(sin sucursal)');
                    $sucNue = \App\Models\Sucursal::find($nuevoValor)?->nombre ?? ($nuevoValor ?: '(sin sucursal)');
                    $detalles[] = "{$label}: [Antes: '{$sucAnt}' -> Ahora: '{$sucNue}']";
                } elseif ($campo === 'coordinador_id') {
                    $coordAnt = \App\Models\User::find($valorOriginal)?->name ?? ($valorOriginal ?: '(sin coordinador)');
                    $coordNue = \App\Models\User::find($nuevoValor)?->name ?? ($nuevoValor ?: '(sin coordinador)');
                    $detalles[] = "{$label}: [Antes: '{$coordAnt}' -> Ahora: '{$coordNue}']";
                } elseif ($campo === 'activo') {
                    $actAnt = $valorOriginal ? 'Activo' : 'Inactivo';
                    $actNue = $nuevoValor ? 'Activo' : 'Inactivo';
                    $detalles[] = "{$label}: [Antes: '{$actAnt}' -> Ahora: '{$actNue}']";
                } elseif ($campo === 'es_morosa') {
                    $morAnt = $valorOriginal ? 'Morosa' : 'Al corriente';
                    $morNue = $nuevoValor ? 'Morosa' : 'Al corriente';
                    $detalles[] = "{$label}: [Antes: '{$morAnt}' -> Ahora: '{$morNue}']";
                } elseif ($campo === 'limite_credito') {
                    $limAnt = '$' . number_format(floatval($valorOriginal), 2);
                    $limNue = '$' . number_format(floatval($nuevoValor), 2);
                    $detalles[] = "{$label}: [Antes: '{$limAnt}' -> Ahora: '{$limNue}']";
                } else {
                    $valAntFmt = ($valorOriginal !== null && $valorOriginal !== '') ? $valorOriginal : '(vacío)';
                    $valNueFmt = ($nuevoValor !== null && $nuevoValor !== '') ? $nuevoValor : '(vacío)';
                    $detalles[] = "{$label}: [Antes: '{$valAntFmt}' -> Ahora: '{$valNueFmt}']";
                }

                $cambios[$campo] = [
                    'campo' => $label,
                    'antes' => $valorOriginal,
                    'despues' => $nuevoValor,
                ];
            }
        }

        if (!empty($inputData['password'])) {
            $detalles[] = "Contraseña: [Actualizada]";
            $cambios['password'] = [
                'campo' => 'Contraseña',
                'antes' => '***',
                'despues' => '***',
            ];
        }

        return [
            'cambios' => $cambios,
            'detalles' => $detalles,
            'texto' => count($detalles) > 0 
                ? " (Campos modificados: " . implode(', ', $detalles) . ")"
                : " (Sin modificaciones a los campos)",
        ];
    }
}
