<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudCategoria extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'solicitudes_categoria';

    protected $fillable = [
        'distribuidor_id',
        'coordinador_id',
        'gerente_id',
        'categoria_actual',
        'categoria_nueva',
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
