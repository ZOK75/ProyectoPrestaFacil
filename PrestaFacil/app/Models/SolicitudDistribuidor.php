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
        'datos_verificacion',
        'observaciones_resolucion',
        'resolved_at',
    ];

    protected $casts = [
        'datos_familiares' => 'array',
        'datos_verificacion' => 'array',
        'fecha_nacimiento' => 'date',
        'resolved_at' => 'datetime',
    ];

    /**
     * Obtener el valor verificado o el valor original si no fue modificado
     */
    public function getDatoVerificado(string $campo, mixed $default = null): mixed
    {
        if (!empty($this->datos_verificacion) && is_array($this->datos_verificacion) && array_key_exists($campo, $this->datos_verificacion)) {
            return $this->datos_verificacion[$campo];
        }

        return $this->{$campo} ?? $default;
    }

    /**
     * Saber si un campo fue modificado por el verificador
     */
    public function isCampoModificado(string $campo): bool
    {
        if (empty($this->datos_verificacion) || !is_array($this->datos_verificacion)) {
            return false;
        }

        if (!array_key_exists($campo, $this->datos_verificacion)) {
            return false;
        }

        $orig = $this->{$campo};
        $verif = $this->datos_verificacion[$campo];

        if ($orig instanceof \Carbon\Carbon || $orig instanceof \DateTimeInterface) {
            $orig = $orig->format('Y-m-d');
        }

        if (is_array($orig) || is_array($verif)) {
            return json_encode($orig) !== json_encode($verif);
        }

        return trim((string)$orig) !== trim((string)$verif);
    }

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
