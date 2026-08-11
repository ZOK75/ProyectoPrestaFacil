<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\Prestamo;
use App\Models\Configuracion;
use App\Models\SolicitudCliente;
use App\Models\RelacionCobranza;
use App\Models\NotificacionCajero;

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
        'limite_credito_anterior',
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
            'limite_credito_anterior' => 'decimal:2',
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

    /**
     * Préstamos otorgados / colocados por este usuario.
     */
    public function prestamos(): HasMany
    {
        return $this->hasMany(Prestamo::class, 'created_by_user_id');
    }

    /**
     * Solicitudes de clientes registradas por este usuario.
     */
    public function solicitudesClientes(): HasMany
    {
        return $this->hasMany(SolicitudCliente::class, 'distribuidor_id');
    }

    /**
     * Relaciones de cobranza de este distribuidor.
     */
    public function relacionesCobranza(): HasMany
    {
        return $this->hasMany(RelacionCobranza::class, 'distribuidora_id');
    }

    /**
     * Notificaciones recibidas por este usuario.
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(NotificacionCajero::class, 'user_id');
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

    public function esCajero(): bool
    {
        $nombre = strtolower($this->rol?->nombre ?? '');
        return in_array($nombre, ['cajero', 'cajera', 'caja']);
    }

    public function esCoordinador(): bool
    {
        $nombre = strtolower($this->rol?->nombre ?? '');
        return in_array($nombre, ['coordinador', 'coordinadora']);
    }

    /**
     * Conteo de solicitudes pendientes para vista de gerentes.
     */
    public function conteoSolicitudesPendientes(): int
    {
        $query = SolicitudCliente::where('estado', 'pendiente');
        if ($this->esGerenteSucursal() && $this->sucursal_id) {
            $query->where('sucursal_id', $this->sucursal_id);
        }
        return $query->count();
    }

    /**
     * Conteo de notificaciones sin leer de este usuario.
     */
    public function conteoNotificacionesSinLeer(): int
    {
        return NotificacionCajero::where('user_id', $this->id)
            ->where('leida', false)
            ->count();
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
     * Calcula los puntos acumulados por el distribuidor según el total de productos otorgados.
     * Fórmula: floor(Total en productos / Monto base) * Puntos base
     */
    public function puntosAcumulados(): int
    {
        if (!$this->esDistribuidor()) {
            return 0;
        }

        $config = Configuracion::actual();
        $totalProductos = floatval(Prestamo::where('created_by_user_id', $this->id)->sum('monto_prestamo'));

        return $config->calcularPuntosPorMonto($totalProductos);
    }

    /**
     * Devuelve el equivalente en dinero de los puntos acumulados.
     */
    public function valorPuntosEnDinero(): float
    {
        $config = Configuracion::actual();
        $valorPorPunto = floatval($config->valor_punto ?? 2.00);

        return $this->puntosAcumulados() * $valorPorPunto;
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
