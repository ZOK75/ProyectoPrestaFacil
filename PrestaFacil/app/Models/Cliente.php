<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cliente extends Model
{
    use HasFactory;

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
}
