<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Prestamo;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol_id',
        'sucursal_id',
        'categoria_distribuidor',
        'limite_credito',
        'referencia_pago_distribuidor',
        'puntos',
        'activo',
        'desactivado_at',
        'desactivado_by_user_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'desactivado_at' => 'datetime',
            'limite_credito' => 'decimal:2',
            'puntos' => 'integer',
        ];
    }

    public function rol(): BelongsTo
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }

    public function hasRole(string $rolName): bool
    {
        return $this->rol && strtolower($this->rol->nombre) == strtolower($rolName);
    }

    public function sucursal(): BelongsTo
    {
        return $this->belongsTo(Sucursal::class);
    }

    /**
     * Usuario que desactivó esta cuenta.
     */
    public function desactivadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'desactivado_by_user_id');
    }

    // ──────────────────────────────────────────
    // Helpers de Rol
    // ──────────────────────────────────────────

    public function esGerenteGeneral(): bool
    {
        return strtolower($this->rol?->nombre ?? '') === 'gerente general';
    }

    public function esGerenteSucursal(): bool
    {
        return strtolower($this->rol?->nombre ?? '') === 'gerente de sucursal';
    }

    public function esDistribuidor(): bool
    {
        $nombre = strtolower($this->rol?->nombre ?? '');
        return in_array($nombre, ['distribuidor', 'distribuidora']);
    }

    /**
     * Obtiene el porcentaje de ganancia según su categoría de distribuidor.
     */
    public function obtenerPorcentajeGanancia(): float
    {
        if (!$this->esDistribuidor()) {
            return 0.0;
        }

        $config = Configuracion::actual();
        return $config->obtenerComision($this->categoria_distribuidor);
    }

    /**
     * Calcula el monto de crédito de vales en estado 'activo' que tiene ocupados el distribuidor.
     */
    public function creditoUtilizado(): float
    {
        if (!$this->esDistribuidor()) {
            return 0.0;
        }

        return floatval(Prestamo::where('created_by_user_id', $this->id)
            ->where('estado', 'activo')
            ->sum('monto_prestamo'));
    }

    /**
     * Calcula el crédito disponible actual del distribuidor.
     */
    public function creditoDisponible(): float
    {
        $limite = floatval($this->limite_credito ?? 20000.00);
        return max(0.0, $limite - $this->creditoUtilizado());
    }

    /**
     * Calcula el valor máximo que puede tener UN SOLO VALE otorgado por este distribuidor:
     * Regla: (50% del Límite de Crédito Total) + $500.00
     */
    public function montoMaximoPermitidoPorVale(): float
    {
        $limite = floatval($this->limite_credito ?? 20000.00);
        return ($limite * 0.50) + 500.00;
    }

    /**
     * Devuelve la referencia de pago bancaria única del distribuidor.
     */
    public function referenciaPago(): string
    {
        if (!empty($this->referencia_pago_distribuidor)) {
            return $this->referencia_pago_distribuidor;
        }

        return 'REF-DIST-' . str_pad((string)$this->id, 8, '0', STR_PAD_LEFT);
    }

    /**
     * Devuelve los roles que este usuario tiene permiso de asignar.
     */
    public function rolesPermitidos()
    {
        $query = Rol::query();

        if ($this->esGerenteGeneral()) {
            $query->where('nombre', '!=', 'Gerente General');
        } elseif ($this->esGerenteSucursal()) {
            $query->whereNotIn('nombre', ['Gerente General', 'Gerente de Sucursal']);
        } else {
            return Rol::where('id', 0)->get();
        }

        return $query->orderBy('nombre')->get();
    }

    /**
     * Devuelve las sucursales a las que este usuario puede asignar empleados.
     */
    public function sucursalesPermitidas()
    {
        if ($this->esGerenteGeneral()) {
            return Sucursal::where('activo', true)->orderBy('nombre')->get();
        }

        if ($this->esGerenteSucursal() && $this->sucursal_id) {
            return Sucursal::where('id', $this->sucursal_id)->get();
        }

        return Sucursal::where('id', 0)->get();
    }
}
