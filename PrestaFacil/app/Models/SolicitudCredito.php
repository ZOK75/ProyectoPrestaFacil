<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudCredito extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_credito';

    protected $fillable = [
        'distribuidor_id',
        'coordinador_id',
        'gerente_id',
        'limite_actual',
        'limite_nuevo',
        'motivo',
        'estado',
        'observaciones',
    ];

    public function distribuidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distribuidor_id');
    }

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }

    public function gerente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerente_id');
    }

    public function esPendiente(): bool
    {
        return $this->estado === 'pendiente';
    }
}
