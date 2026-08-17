<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudAutorizacion extends Model
{
    use HasUuids;

    protected $table = 'solicitudes_autorizacion';

    protected $fillable = [
        'tipo',
        'estado',
        'solicitante_id',
        'sucursal_id',
        'entidad_tipo',
        'entidad_id',
        'datos_originales',
        'datos_propuestos',
        'motivo',
        'evidencia_path',
        'autorizador_id',
        'autorizador_rol',
        'observaciones_resolucion',
        'resolved_at',
    ];

    protected $casts = [
        'datos_originales' => 'array',
        'datos_propuestos' => 'array',
        'resolved_at' => 'datetime',
    ];

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizador_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function esPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function aprobar(User $autorizador, ?string $observaciones = null): void
    {
        $this->update([
            'estado' => 'aprobada',
            'autorizador_id' => $autorizador->id,
            'autorizador_rol' => $autorizador->rol->nombre,
            'observaciones_resolucion' => $observaciones,
            'resolved_at' => now(),
        ]);
    }

    public function rechazar(User $autorizador, string $motivo, ?string $observaciones = null): void
    {
        $this->update([
            'estado' => 'rechazada',
            'autorizador_id' => $autorizador->id,
            'autorizador_rol' => $autorizador->rol->nombre,
            'observaciones_resolucion' => $observaciones ? $motivo . ' - ' . $observaciones : $motivo,
            'resolved_at' => now(),
        ]);
    }
}
