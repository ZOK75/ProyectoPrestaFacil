<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificacionCajero extends Model
{
    use HasUuids;

    protected $table = 'notificaciones_cajero';

    protected $fillable = [
        'user_id',
        'tipo',
        'titulo',
        'mensaje',
        'data',
        'leida',
        'leida_at',
        'entidad_tipo',
        'entidad_id',
    ];

    protected $casts = [
        'data' => 'array',
        'leida' => 'boolean',
        'leida_at' => 'datetime',
    ];

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeSinLeer($query)
    {
        return $query->where('leida', false);
    }

    public function marcarLeida(): void
    {
        if (!$this->leida) {
            $this->update([
                'leida' => true,
                'leida_at' => now(),
            ]);
        }
    }

    public static function enviar(
        string $userId, 
        string $tipo, 
        string $titulo, 
        string $mensaje, 
        array $data = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'data' => $data,
            'entidad_tipo' => $data['entidad_tipo'] ?? null,
            'entidad_id' => $data['entidad_id'] ?? null,
        ]);
    }
}
