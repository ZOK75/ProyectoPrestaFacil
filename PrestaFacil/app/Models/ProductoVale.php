<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductoVale extends Model
{
    use HasFactory;

    protected $table = 'producto_vales';

    protected $fillable = [
        'clave',
        'nombre',
        'monto_prestamo',
        'costo_seguro',
        'plazo_quincenas',
        'comision_apertura',
        'tasa_interes_quincenal',
        'activo',
        'desactivado_at',
        'created_by_user_id',
        'updated_by_user_id',
        'descripcion',
    ];

    protected $casts = [
        'monto_prestamo' => 'decimal:2',
        'costo_seguro' => 'decimal:2',
        'plazo_quincenas' => 'integer',
        'comision_apertura' => 'decimal:2',
        'tasa_interes_quincenal' => 'decimal:2',
        'activo' => 'boolean',
        'desactivado_at' => 'datetime',
    ];

    protected $appends = [
        'interes_total',
        'monto_total_pagar',
        'cuota_quincenal',
    ];

    /**
     * Relación con el usuario creador.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Relación con el usuario que modificó o desactivó.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * Interés acumulado a lo largo del plazo de quincenas.
     */
    protected function interesTotal(): Attribute
    {
        return Attribute::make(
            get: fn () => (float)$this->monto_prestamo * ((float)$this->tasa_interes_quincenal / 100) * (int)$this->plazo_quincenas
        );
    }

    /**
     * Monto total que el cliente pagará (Monto Préstamo + Costo Seguro + Intereses Totales).
     */
    protected function montoTotalPagar(): Attribute
    {
        return Attribute::make(
            get: fn () => (float)$this->monto_prestamo + (float)$this->costo_seguro + (float)$this->interes_total + ((float)$this->comision_apertura / 100) * (float)$this->monto_prestamo
        );
    }

    /**
     * Pago o cuota quincenal (Monto Total a Pagar / Plazo Quincenas).
     */
    protected function cuotaQuincenal(): Attribute
    {
        return Attribute::make(
            get: function () {
                $plazo = (int)$this->plazo_quincenas;
                return $plazo > 0 ? ((float)$this->monto_total_pagar) / $plazo : 0;
            }
        );
    }
}
