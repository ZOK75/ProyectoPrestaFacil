<?php
 
namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
 
class Configuracion extends Model
{
    use HasFactory;
 
    protected $table = 'configuracion_generales';
 
    protected $fillable = [
        'fecha_corte',
        'fecha_limite_pago',
        'multa_adeudo',
        'comision_cobre',
        'comision_plata',
        'comision_oro',
        'created_by_user_id',
        'updated_by_user_id',
    ];
 
    protected $casts = [
        'fecha_corte' => 'datetime',
        'fecha_limite_pago' => 'datetime',
        'multa_adeudo' => 'decimal:2',
        'comision_cobre' => 'decimal:2',
        'comision_plata' => 'decimal:2',
        'comision_oro' => 'decimal:2',
    ];
 
    /**
     * Usuario que creó el registro de configuración.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
 
    /**
     * Usuario que realizó la última modificación.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by_user_id');
    }

    /**
     * Historial de cambios de esta configuración.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(ConfiguracionLog::class, 'configuracion_id')->orderByDesc('changed_at');
    }

    /**
     * Devuelve el porcentaje de comisión configurado para una categoría dada.
     */
    public function obtenerComision(?string $categoria): float
    {
        return match(strtolower($categoria ?? 'cobre')) {
            'oro' => floatval($this->comision_oro),
            'plata' => floatval($this->comision_plata),
            default => floatval($this->comision_cobre),
        };
    }
 
    /**
     * La configuración general es un singleton: siempre debe existir
     * una sola fila.
     */
    public static function actual(): self
    {
        return static::first() ?? static::create([
            'fecha_corte' => now()->startOfDay(),
            'fecha_limite_pago' => now()->addDays(15)->startOfDay(),
            'multa_adeudo' => 300,
            'comision_cobre' => 3.00,
            'comision_plata' => 6.00,
            'comision_oro' => 10.00,
        ]);
    }
}