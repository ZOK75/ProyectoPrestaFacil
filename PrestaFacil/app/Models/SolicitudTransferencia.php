<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTransferencia extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'solicitudes_transferencias';

    protected $fillable = [
        'distribuidor_id',
        'coordinador_emisor_id',
        'coordinador_receptor_id',
        'sucursal_origen_id',
        'sucursal_destino_id',
        'motivo',
        'estado',
        'observaciones_coordinador_receptor',
        'observaciones_gerente',
        'gerente_id',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function distribuidor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distribuidor_id');
    }

    public function coordinadorEmisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_emisor_id');
    }

    public function coordinadorReceptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_receptor_id');
    }

    public function sucursalOrigen(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_origen_id');
    }

    public function sucursalDestino(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class, 'sucursal_destino_id');
    }

    public function gerente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'gerente_id');
    }

    public function esPendienteCoordinador(): bool
    {
        return $this->estado === 'pendiente_coordinador';
    }

    public function esPendienteGerente(): bool
    {
        return $this->estado === 'pendiente_gerente';
    }

    public function esAprobada(): bool
    {
        return $this->estado === 'aprobada';
    }

    public function esRechazada(): bool
    {
        return in_array($this->estado, ['rechazada_coordinador', 'rechazada_gerente']);
    }
}
