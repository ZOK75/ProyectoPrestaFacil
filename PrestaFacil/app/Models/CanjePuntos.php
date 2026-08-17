<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CanjePuntos extends Model
{
    use HasUuids;

    protected $table = 'canjes_puntos';

    public $timestamps = false;

    protected $fillable = [
        'distribuidora_id',
        'puntos_canjeados',
        'valor_punto',
        'equivalente_dinero',
        'sobrante_devuelto',
        'cajera_id',
        'sucursal_id',
        'created_at',
    ];

    protected $casts = [
        'puntos_canjeados' => 'integer',
        'valor_punto' => 'decimal:2',
        'equivalente_dinero' => 'decimal:2',
        'sobrante_devuelto' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    public function distribuidora(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distribuidora_id');
    }

    public function cajera(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cajera_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }
}
