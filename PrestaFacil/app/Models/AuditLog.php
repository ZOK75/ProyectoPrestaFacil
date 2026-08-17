<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;


use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuids;

    protected $table = 'audit_logs';

    public $timestamps = false; // Only uses created_at explicitly in the schema

    protected $fillable = [
        'tipo_operacion',
        'descripcion',
        'datos_antes',
        'datos_despues',
        'user_id',
        'user_rol',
        'autorizador_id',
        'autorizador_rol',
        'sucursal_id',
        'entidad_tipo',
        'entidad_id',
        'evidencia_path',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'datos_antes' => 'array',
        'datos_despues' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Helper para registrar un log fácilmente y asegurar la inmutabilidad
     * de la operación (el modelo AuditLog no debería ser actualizado).
     */
    public static function registrar(
        string $tipo, 
        string $descripcion, 
        array $opciones = []
    ): self {
        return self::create([
            'tipo_operacion' => $tipo,
            'descripcion' => $descripcion,
            'datos_antes' => $opciones['antes'] ?? null,
            'datos_despues' => $opciones['despues'] ?? null,
            'user_id' => $opciones['user_id'] ?? auth()->id(),
            'user_rol' => $opciones['user_rol'] ?? auth()->user()?->rol?->nombre ?? 'Sistema',
            'autorizador_id' => $opciones['autorizador_id'] ?? null,
            'autorizador_rol' => $opciones['autorizador_rol'] ?? null,
            'sucursal_id' => $opciones['sucursal_id'] ?? auth()->user()?->sucursal_id,
            'entidad_tipo' => $opciones['entidad_tipo'] ?? null,
            'entidad_id' => $opciones['entidad_id'] ?? null,
            'evidencia_path' => $opciones['evidencia_path'] ?? null,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
}
