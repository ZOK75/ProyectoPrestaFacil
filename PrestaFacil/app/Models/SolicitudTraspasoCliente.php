<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolicitudTraspasoCliente extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'solicitudes_traspaso_clientes';

    protected $fillable = [
        'cliente_id',
        'distribuidor_emisor_id',
        'distribuidor_receptor_id',
        'coordinador_id',
        'motivo',
        'estado',
        'observaciones_distribuidor_receptor',
        'observaciones_coordinador',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function distribuidorEmisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distribuidor_emisor_id');
    }

    public function distribuidorReceptor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'distribuidor_receptor_id');
    }

    public function coordinador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }

    public function esPendienteDistribuidorReceptor(): bool
    {
        return $this->estado === 'pendiente_distribuidor_receptor';
    }

    public function esPendienteCoordinador(): bool
    {
        return $this->estado === 'pendiente_coordinador';
    }

    public function esAprobada(): bool
    {
        return $this->estado === 'aprobada';
    }

    public function esRechazada(): bool
    {
        return in_array($this->estado, ['rechazada_distribuidor_receptor', 'rechazada_coordinador']);
    }
}
