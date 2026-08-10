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
        'porcentaje_regla_prevale',
        'tolerancia_regla_prevale',
        'valor_punto',
        'puntos_por_relacion',
        'penalizacion_morosidad_puntos',
        'multiplo_canje_puntos',
        'multiplo_producto',
        'strikes_morosidad',
        'fecha_pago_2',
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
        'porcentaje_regla_prevale' => 'decimal:2',
        'tolerancia_regla_prevale' => 'decimal:2',
        'valor_punto' => 'decimal:2',
        'puntos_por_relacion' => 'integer',
        'penalizacion_morosidad_puntos' => 'decimal:2',
        'multiplo_canje_puntos' => 'integer',
        'multiplo_producto' => 'integer',
        'strikes_morosidad' => 'integer',
        'fecha_pago_2' => 'datetime',
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
     * Helpers de acceso rápido para configuración del cajero
     */
    public function obtenerPorcentajeRegla(): float
    {
        return floatval($this->porcentaje_regla_prevale ?? 50.00);
    }

    public function obtenerTolerancia(): float
    {
        return floatval($this->tolerancia_regla_prevale ?? 500.00);
    }

    public function obtenerValorPunto(): float
    {
        return floatval($this->valor_punto ?? 10.00);
    }

    public function obtenerMultiploCanje(): int
    {
        return intval($this->multiplo_canje_puntos ?? 20);
    }

    public function obtenerStrikesMorosidad(): int
    {
        return intval($this->strikes_morosidad ?? 3);
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
            'porcentaje_regla_prevale' => 50.00,
            'tolerancia_regla_prevale' => 500.00,
            'valor_punto' => 10.00,
            'puntos_por_relacion' => 5,
            'penalizacion_morosidad_puntos' => 20.00,
            'multiplo_canje_puntos' => 20,
            'multiplo_producto' => 100,
            'strikes_morosidad' => 3,
            'fecha_pago_2' => now()->addDays(30)->startOfDay(),
        ]);
    }
}