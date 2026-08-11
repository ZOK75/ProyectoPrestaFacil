<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    use HasUuids, HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'nombre',
        'curp',
        'rfc',
        'fecha_nacimiento',
        'lugar_nacimiento',
        'calle',
        'colonia',
        'codigo_postal',
        'ciudad',
        'estado',
        'path_ine_pdf',
        'path_comprobante_pdf',
        'activo',
        'desactivado_at',
        'created_by_user_id',
        'desactivado_by_user_id',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'activo' => 'boolean',
        'desactivado_at' => 'datetime',
    ];

    /**
     * Usuario que registró al cliente.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Usuario que desactivó al cliente.
     */
    public function desactivadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'desactivado_by_user_id');
    }

    /**
     * Solicitudes de cambio o desactivación sobre este cliente.
     */
    public function solicitudes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(SolicitudCliente::class, 'cliente_id')->orderBy('created_at', 'desc');
    }

    /**
     * Solicitud pendiente actual si existe.
     */
    public function solicitudPendiente(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(SolicitudCliente::class, 'cliente_id')->where('estado', 'pendiente')->latestOfMany();
    }

    /**
     * Indica si el cliente tiene alguna solicitud pendiente de aprobación.
     */
    public function tieneSolicitudPendiente(): bool
    {
        return $this->solicitudes()->where('estado', 'pendiente')->exists();
    }

    /**
     * Préstamos/Vales del cliente.
     */
    public function prestamos(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Prestamo::class, 'cliente_id')->orderBy('created_at', 'desc');
    }
}
