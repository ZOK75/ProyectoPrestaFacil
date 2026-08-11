<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conciliacion extends Model
{
    use HasUuids;

    protected $table = 'conciliaciones';

    protected $fillable = [
        'prestamo_id',
        'pago_prestamo_id',
        'monto_original',
        'monto_corregido',
        'motivo',
        'evidencia_path',
        'solicitante_id',
        'autorizador_id',
        'autorizador_rol',
        'estado',
        'observaciones_resolucion',
        'resolved_at',
    ];

    protected $casts = [
        'monto_original' => 'decimal:2',
        'monto_corregido' => 'decimal:2',
        'resolved_at' => 'datetime',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_id');
    }

    public function pago(): BelongsTo
    {
        return $this->belongsTo(PagoPrestamo::class, 'pago_prestamo_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitante_id');
    }

    public function autorizador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'autorizador_id');
    }

    public function esPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function estaAprobada(): bool
    {
        return $this->estado === 'aprobada';
    }
}
