<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RelacionCobranza extends Model
{
    use HasFactory;

    protected $table = 'relaciones_cobranza';

    protected $fillable = [
        'distribuidora_id',
        'fecha_corte',
        'fecha_limite_pago',
        'monto_total_periodo',
        'monto_pagado',
        'adeudo_pendiente',
        'multa_aplicada',
        'estado_pago',
        'puntos_ganados',
        'puntos_descontados',
        'corte_notificado_at',
        'multa_aplicada_at',
        'liquidado_at',
    ];

    protected $casts = [
        'fecha_corte' => 'datetime',
        'fecha_limite_pago' => 'datetime',
        'monto_total_periodo' => 'decimal:2',
        'monto_pagado' => 'decimal:2',
        'adeudo_pendiente' => 'decimal:2',
        'multa_aplicada' => 'decimal:2',
        'puntos_ganados' => 'integer',
        'puntos_descontados' => 'integer',
        'corte_notificado_at' => 'datetime',
        'multa_aplicada_at' => 'datetime',
        'liquidado_at' => 'datetime',
    ];

    public function distribuidora(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distribuidora_id');
    }

    public function esPagoAnticipado(): bool
    {
        return $this->estado_pago === 'pago_anticipado';
    }

    public function esPagoATiempo(): bool
    {
        return $this->estado_pago === 'pago_a_tiempo';
    }

    public function esPagoAtrasado(): bool
    {
        return $this->estado_pago === 'pago_atrasado';
    }

    public function estaLiquidada(): bool
    {
        return in_array($this->estado_pago, ['pago_anticipado', 'pago_a_tiempo', 'pago_atrasado']);
    }
}
