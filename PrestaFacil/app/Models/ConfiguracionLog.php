<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConfiguracionLog extends Model
{
    use HasFactory;

    protected $table = 'configuracion_logs';

    public $timestamps = false;

    protected $fillable = [
        'configuracion_id',
        'fecha_corte',
        'fecha_limite_pago',
        'multa_adeudo',
        'changed_by_user_id',
        'motivo',
        'changed_at',
    ];

    protected $casts = [
        'fecha_corte' => 'datetime',
        'fecha_limite_pago' => 'datetime',
        'multa_adeudo' => 'decimal:2',
        'changed_at' => 'datetime',
    ];

    /**
     * Usuario que realizó el cambio.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }

    /**
     * Configuración a la que pertenece este log.
     */
    public function configuracion(): BelongsTo
    {
        return $this->belongsTo(Configuracion::class, 'configuracion_id');
    }
}
