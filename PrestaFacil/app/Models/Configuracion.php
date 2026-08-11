<?php
 
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
 
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
 
class Configuracion extends Model
{
    use HasUuids, HasFactory;
 
    protected $table = 'configuracion_generales';
 
    protected $fillable = [
        'dia_corte',
        'hora_corte',
        'dia_limite_pago',
        'hora_limite_pago',
        'fecha_corte',
        'fecha_limite_pago',
        'multa_adeudo',
        'comision_cobre',
        'comision_plata',
        'comision_oro',
        'monto_base_puntos',
        'puntos_por_monto_base',
        'valor_punto',
        'porcentaje_regla_prevale',
        'tolerancia_regla_prevale',
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
        'dia_corte' => 'integer',
        'dia_limite_pago' => 'integer',
        'fecha_corte' => 'datetime',
        'fecha_limite_pago' => 'datetime',
        'fecha_pago_2' => 'datetime',
        'multa_adeudo' => 'decimal:2',
        'comision_cobre' => 'decimal:2',
        'comision_plata' => 'decimal:2',
        'comision_oro' => 'decimal:2',
        'monto_base_puntos' => 'decimal:2',
        'puntos_por_monto_base' => 'integer',
        'valor_punto' => 'decimal:2',
        'porcentaje_regla_prevale' => 'decimal:2',
        'tolerancia_regla_prevale' => 'decimal:2',
        'puntos_por_relacion' => 'integer',
        'penalizacion_morosidad_puntos' => 'decimal:2',
        'multiplo_canje_puntos' => 'integer',
        'multiplo_producto' => 'integer',
        'strikes_morosidad' => 'integer',
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
     * Calcula la fecha y hora de corte exacta para el mes de referencia.
     */
    public function fechaCorteCalculada(?Carbon $ref = null): Carbon
    {
        $fecha = ($ref ? $ref->copy() : now());
        $dia = intval($this->dia_corte ?? 10);
        $hora = $this->hora_corte ?? '22:20:00';
        
        $maxDias = $fecha->daysInMonth;
        $diaAjustado = min($dia, $maxDias);
        
        $horaPartes = explode(':', substr($hora, 0, 5));
        $h = intval($horaPartes[0] ?? 22);
        $m = intval($horaPartes[1] ?? 20);
        
        return $fecha->copy()->setDay($diaAjustado)->setTime($h, $m, 0);
    }

    /**
     * Calcula la fecha y hora límite de pago exacta para el periodo correspondiente.
     * Regla: Se evalúa si en el mismo mes la fecha/hora límite es posterior al corte.
     * Si el día y hora límite se anteponen (<= corte), se calcula para el siguiente mes.
     */
    public function fechaLimitePagoCalculada(?Carbon $ref = null): Carbon
    {
        $fechaCorte = $this->fechaCorteCalculada($ref);
        $diaLimite = intval($this->dia_limite_pago ?? 15);
        $horaLimite = $this->hora_limite_pago ?? '23:59:00';
        
        $horaPartes = explode(':', substr($horaLimite, 0, 5));
        $h = intval($horaPartes[0] ?? 23);
        $m = intval($horaPartes[1] ?? 59);
        
        // 1. Crear fecha límite tentativa en el mismo mes que el corte
        $maxDiasMismoMes = $fechaCorte->daysInMonth;
        $diaAjustadoMismoMes = min($diaLimite, $maxDiasMismoMes);
        $limiteMismoMes = $fechaCorte->copy()->setDay($diaAjustadoMismoMes)->setTime($h, $m, 0);
        
        // Si en el mismo mes la fecha/hora límite es estrictamente posterior al corte -> MISMO MES
        if ($limiteMismoMes->greaterThan($fechaCorte)) {
            return $limiteMismoMes;
        }
        
        // Si el día y hora límite se antepone (es menor o igual al corte) -> SIGUIENTE MES
        $fechaSiguienteMes = $fechaCorte->copy()->addMonthNoOverflow();
        $maxDiasSig = $fechaSiguienteMes->daysInMonth;
        $diaAjustadoSig = min($diaLimite, $maxDiasSig);
        
        return $fechaSiguienteMes->setDay($diaAjustadoSig)->setTime($h, $m, 0);
    }

    /**
     * Determina si la fecha límite cae en el siguiente mes respecto al corte.
     */
    public function esLimiteSiguienteMes(?Carbon $ref = null): bool
    {
        $corte = $this->fechaCorteCalculada($ref);
        $limite = $this->fechaLimitePagoCalculada($ref);

        return $limite->month !== $corte->month;
    }

    /**
     * Accessor dinámico para fecha_corte
     */
    public function getFechaCorteAttribute($value): Carbon
    {
        if (!empty($this->attributes['dia_corte'])) {
            return $this->fechaCorteCalculada();
        }
        return $value ? Carbon::parse($value) : now()->startOfDay();
    }

    /**
     * Accessor dinámico para fecha_limite_pago
     */
    public function getFechaLimitePagoAttribute($value): Carbon
    {
        if (!empty($this->attributes['dia_limite_pago'])) {
            return $this->fechaLimitePagoCalculada();
        }
        return $value ? Carbon::parse($value) : now()->addDays(15)->startOfDay();
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
     * Devuelve el múltiplo requerido para canjear puntos (por defecto 20).
     */
    public function obtenerMultiploCanje(): int
    {
        return intval($this->multiplo_canje_puntos ?? 20);
    }

    /**
     * Devuelve el valor monetario por cada punto canjeado (por defecto $2.00).
     */
    public function obtenerValorPunto(): float
    {
        return floatval($this->valor_punto ?? 2.00);
    }

    /**
     * Calcula los puntos generados según el total de productos colocados.
     * Fórmula: floor(montoTotal / monto_base_puntos) * puntos_por_monto_base
     */
    public function calcularPuntosPorMonto(float $montoTotal): int
    {
        $base = floatval($this->monto_base_puntos ?? 1200.00);
        if ($base <= 0) {
            return 0;
        }

        $puntosMultiplicador = intval($this->puntos_por_monto_base ?? 3);
        $bloques = intval(floor($montoTotal / $base));

        return $bloques * $puntosMultiplicador;
    }

    /**
     * Devuelve el porcentaje de la regla para prevales
     */
    public function obtenerPorcentajeRegla(): float
    {
        return floatval($this->porcentaje_regla_prevale ?? 15.0);
    }

    /**
     * Devuelve la tolerancia permitida en monto
     */
    public function obtenerTolerancia(): float
    {
        return floatval($this->tolerancia_regla_prevale ?? 500.0);
    }
 
    /**
     * La configuración general es un singleton: siempre debe existir
     * una sola fila.
     */
    public static function actual(): self
    {
        return static::first() ?? static::create([
            'dia_corte' => 10,
            'hora_corte' => '22:20:00',
            'dia_limite_pago' => 15,
            'hora_limite_pago' => '23:59:00',
            'multa_adeudo' => 300.00,
            'comision_cobre' => 3.00,
            'comision_plata' => 6.00,
            'comision_oro' => 10.00,
            'monto_base_puntos' => 1200.00,
            'puntos_por_monto_base' => 3,
            'valor_punto' => 2.00,
            'multiplo_canje_puntos' => 20,
        ]);
    }
}