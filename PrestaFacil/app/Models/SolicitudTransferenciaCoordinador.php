<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTransferenciaCoordinador extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'solicitudes_transferencia_coordinadores';

    protected $fillable = [
        'coordinador_id',
        'gerente_emisor_id',
        'gerente_receptor_id',
        'sucursal_origen_id',
        'sucursal_destino_id',
        'motivo',
        'estado',
        'observaciones_gerente_receptor',
        'observaciones_gerente_general',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }

    public function gerenteEmisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerente_emisor_id');
    }

    public function gerenteReceptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerente_receptor_id');
    }

    public function sucursalOrigen(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_origen_id');
    }

    public function sucursalDestino(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_destino_id');
    }

    public function esPendienteGerenteReceptor(): bool
    {
        return $this->estado === 'pendiente_gerente_receptor';
    }

    public function esPendienteGerenteGeneral(): bool
    {
        return $this->estado === 'pendiente_gerente_general';
    }

    public function esAprobada(): bool
    {
        return $this->estado === 'aprobada';
    }

    public function esRechazada(): bool
    {
        return in_array($this->estado, ['rechazada_gerente_receptor', 'rechazada_gerente_general']);
    }
}
