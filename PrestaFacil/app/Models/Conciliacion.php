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
        'distribuidora_id',
        'referencia_original',
        'referencia_conciliacion',
        'fecha_pago',
        'metodo_pago',
        'monto_original',
        'monto_corregido',
        'motivo',
        'evidencia_path',
        'solicitante_id',
        'autorizador_id',
        'autorizador_rol',
        'conciliado_por_user_id',
        'estado',
        'observaciones_resolucion',
        'resolved_at',
        'conciliado_at',
    ];

    protected $casts = [
        'fecha_pago' => 'date',
        'monto_original' => 'decimal:2',
        'monto_corregido' => 'decimal:2',
        'resolved_at' => 'datetime',
        'conciliado_at' => 'datetime',
    ];

    public function prestamo(): BelongsTo
    {
        return $this->belongsTo(Prestamo::class, 'prestamo_id');
    }

    public function distribuidora(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distribuidora_id');
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

    public function conciliadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'conciliado_por_user_id');
    }

    public function esPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }

    public function estaAprobada(): bool
    {
        return in_array($this->estado, ['aprobada', 'conciliado']);
    }
}
