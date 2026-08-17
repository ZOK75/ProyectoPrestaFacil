<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudDistribuidor extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'solicitudes_distribuidores';

    protected $fillable = [
        'nombres',
        'apellidos',
        'telefono',
        'fecha_nacimiento',
        'curp',
        'rfc',
        'lugar_nacimiento',
        'calle',
        'colonia',
        'codigo_postal',
        'estado_republica',
        'ciudad',
        'datos_familiares',
        'datos_vehiculos',
        'datos_casa',
        'referencias_laborales',
        'coordinador_id',
        'sucursal_id',
        'verificador_id',
        'user_id',
        'estado',
        'dictamen_verificador',
        'comentarios_verificador',
        'observaciones_resolucion',
        'resolved_at',
    ];

    protected $casts = [
        'datos_familiares' => 'array',
        'fecha_nacimiento' => 'date',
        'resolved_at' => 'datetime',
    ];

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_id');
    }

    public function verificador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verificador_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return "{$this->nombres} {$this->apellidos}";
    }
}
