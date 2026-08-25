<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\StorePrestamoRequest;
use App\Models\Cliente;
use App\Models\Configuracion;
use App\Models\PagoPrestamo;
use App\Models\Prestamo;
use App\Models\ProductoVale;
use App\Models\RelacionCobranza;
use App\Models\User;
use App\Services\AuditService;
use App\Services\CorteCobranzaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrestamoController extends Controller
{
    protected CorteCobranzaService $corteService;

    public function __construct(CorteCobranzaService $corteService)
    {
        $this->corteService = $corteService;
    }

    private function operador(): ?User
    {
        if (Auth::check()) {
            return Auth::user()->load('rol');
        }

        return User::first();
    }

    private function verificarBloqueoGerencial(?User $operador): ?\Illuminate\Http\RedirectResponse
    {
        if ($operador && ($operador->esGerenteGeneral() || $operador->esGerenteSucursal())) {
            $ruta = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            abort(403, 'Acceso denegado: El rol gerencial no tiene permisos para acceder al módulo de préstamos.');
        }

        return null;
    }

    /**
     * Catálogo móvil de préstamos y estado de cuenta de clientes.
     */
    public function index(Request $request)
    {
        $operador = $this->operador();

        if ($redirect = $this->verificarBloqueoGerencial($operador)) {
            return $redirect;
        }

        // Ejecución automática reactiva con hora del servidor
        $this->corteService->verificarYProcesarCortesYVencimientos();

        $query = Prestamo::with(['cliente', 'productoVale', 'pagos']);

        // Si es distribuidor, filtra solo sus préstamos colocados
        if ($operador && $operador->esDistribuidor()) {
            $query->where('created_by_user_id', $operador->id);
        }

        // Buscador por Referencia o Nombre / CURP de Cliente
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('referencia', 'like', "%{$buscar}%")
                  ->orWhereHas('cliente', function ($qc) use ($buscar) {
                      $qc->where('nombre', 'like', "%{$buscar}%")
                         ->orWhere('curp', 'like', "%{$buscar}%");
                  });
            });
        }

        // Filtro por tipo (prevale / vale)
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->input('tipo'));
        }

        // Filtro por estado (activo / finalizado)
        if ($request->filled('estado')) {
            $query->where('estado', $request->input('estado'));
        }

        $prestamos = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $stats = [
            'total' => Prestamo::count(),
            'activos' => Prestamo::where('estado', 'activo')->count(),
            'prevales' => Prestamo::where('tipo', 'prevale')->count(),
            'vales' => Prestamo::where('tipo', 'vale')->count(),
            'adeudo_total' => Prestamo::where('estado', 'activo')->sum('adeudo_pendiente'),
            'pagos_recibidos_total' => Prestamo::sum('pagos_recibidos'),
            'multas_total' => Prestamo::sum('multas'),
        ];

        // Obtener estado del periodo actual
        $configuracion = Configuracion::actual();
        $relacionActual = null;
        if ($operador && $operador->esDistribuidor()) {
            $relacionActual = RelacionCobranza::where('distribuidora_id', $operador->id)
                ->where('fecha_corte', $configuracion->fecha_corte)
                ->first();
        }

        return view('prestamos.index', compact('prestamos', 'stats', 'operador', 'configuracion', 'relacionActual'));
    }

    /**
     * Formulario móvil para asignar un vale/prevale a un cliente.
     */
    public function create(Request $request)
    {
        $operador = $this->operador();

        if ($redirect = $this->verificarBloqueoGerencial($operador)) {
            return $redirect;
        }

        if ($operador && $operador->esAdministrador()) {
            abort(403, 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
        }

        if ($operador && $operador->esDistribuidor() && $operador->esMorosa()) {
            abort(403, 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
        }

        if ($operador && $operador->esDistribuidor() && $operador->esMorosa()) {
            abort(403, 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
        }

        // Si es distribuidor, verificar que sea el creador del préstamo
        if ($operador && $operador->esDistribuidor() && $prestamo->created_by_user_id !== $operador->id) {
            abort(403, 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
        }

        if ($operador && $operador->esDistribuidor()) {
            abort(403, 'Acceso denegado: El cobro y registro de abonos debe ser realizado en ventanilla por el personal de Caja.');
        }

        if ($prestamo->estaPagado()) {
            abort(403, 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
        }

        if ($operador && $operador->esDistribuidor()) {
            abort(403, 'Acceso denegado: El cobro y registro de abonos debe ser realizado en ventanilla por el personal de Caja.');
        }

        if ($prestamo->estaPagado()) {
            return redirect()->route('prestamos.show', $prestamo)
                ->with('info', "La referencia {$prestamo->referencia} ya se encuentra liquidada.");
        }

        $data = $request->validated();

        $montoAbonado = floatval($data['monto_abonado']);
        $montoMulta = floatval($data['monto_multa'] ?? 0);

        // Folio único de abono
        $folioPago = "PAGO-" . date('YmdHis') . "-" . rand(10, 99);
        $quincenaNum = $prestamo->pagos_realizados + 1;

        // Registrar abono en la bitácora
        $pago = PagoPrestamo::create([
            'prestamo_id' => $prestamo->id,
            'folio_pago' => $folioPago,
            'numero_quincena' => $quincenaNum,
            'monto_abonado' => $montoAbonado,
            'monto_multa' => $montoMulta,
            'metodo_pago' => $data['metodo_pago'] ?? 'Efectivo',
            'observaciones' => $data['observaciones'] ?? null,
            'registrado_por_user_id' => Auth::id() ?? $operador?->id,
        ]);

        // Actualizar acumulados del préstamo
        $nuevosPagosRealizados = $prestamo->pagos_realizados + 1;
        $nuevosPagosRecibidos = $prestamo->pagos_recibidos + $montoAbonado;
        $nuevasMultas = $prestamo->multas + $montoMulta;
        $nuevoAdeudo = max(0, $prestamo->adeudo_pendiente - $montoAbonado + $montoMulta);
        $nuevoEstado = ($nuevoAdeudo <= 0) ? 'finalizado' : 'activo';

        $prestamo->update([
            'pagos_realizados' => $nuevosPagosRealizados,
            'pagos_recibidos' => $nuevosPagosRecibidos,
            'multas' => $nuevasMultas,
            'adeudo_pendiente' => $nuevoAdeudo,
            'estado' => $nuevoEstado,
        ]);

        AuditService::registrar(
            'REGISTRO_ABONO',
            "Abono de \${$montoAbonado} registrado a Vale {$prestamo->referencia} (Quincena #{$quincenaNum})" . ($montoMulta > 0 ? " [Multa: \${$montoMulta}]" : ""),
            [
                'entidad_tipo' => 'pago_prestamos',
                'entidad_id' => $pago->id,
                'user_id' => $pago->registrado_por_user_id,
                'user_rol' => $operador?->rol?->nombre,
                'sucursal_id' => $operador?->sucursal_id,
                'despues' => $pago->toArray(),
            ]
        );

        $mensaje = "Pago de $" . number_format($montoAbonado, 2) . " registrado con éxito para la Referencia {$prestamo->referencia}.";
        if ($montoMulta > 0) {
            $mensaje .= " (Se aplicó multa de $" . number_format($montoMulta, 2) . ")";
        }

        // Evaluar si la distribuidora liquidó por completo su relación
        if ($prestamo->createdBy) {
            $relacionLiquidada = $this->corteService->evaluarLiquidacionRelacion($prestamo->createdBy);
            if ($relacionLiquidada) {
                if ($relacionLiquidada->esPagoAnticipado()) {
                    $mensaje .= " 🌟 ¡Relación liquidada con Pago Anticipado! Se acumularon {$relacionLiquidada->puntos_ganados} puntos.";
                } elseif ($relacionLiquidada->esPagoATiempo()) {
                    $mensaje .= " ✅ Relación liquidada a tiempo dentro del periodo.";
                } elseif ($relacionLiquidada->esPagoAtrasado()) {
                    $mensaje .= " ⚠️ Relación liquidada con Pago Atrasado (-20% puntos aplicados).";
                }
            }
        }

        return redirect()->route('prestamos.show', $prestamo)
            ->with('success', $mensaje);
    }

    /**
     * Genera la Relación de Cobranza del Distribuidor en formato PDF.
     */
    public function relacionCobranza(Request $request, $corte_id = null)
    {
        $operador = $this->operador();
        $configuracion = Configuracion::actual();

        $relacion = null;
        $distribuidoraId = null;
        $distribuidora = null;
        
        $corte_id = $corte_id ?? $request->corte_id;

        if ($corte_id) {
            $relacion = RelacionCobranza::with('distribuidora')->find($corte_id);
            if ($relacion) {
                $distribuidoraId = $relacion->distribuidora_id;
                $distribuidora = $relacion->distribuidora;
            }
        } elseif ($request->filled('distribuidora_id')) {
            $distribuidoraId = $request->distribuidora_id;
            $distribuidora = User::find($distribuidoraId);
        } elseif ($operador && $operador->esDistribuidor()) {
            $distribuidoraId = $operador->id;
            $distribuidora = $operador;
        }

        if (!$distribuidora) {
            return back()->with('error', 'No se especificó un corte o distribuidora para visualizar.');
        }

        if (!$relacion) {
            $this->corteService->verificarYProcesarCortesYVencimientos();
            $relacion = RelacionCobranza::where('distribuidora_id', $distribuidoraId)
                ->where('fecha_corte', $configuracion->fecha_corte)
                ->first();
        }

        $fechaCorteRef = $relacion ? $relacion->fecha_corte : $configuracion->fecha_corte;

        // Cargar todos los préstamos activos en la fecha de corte
        $prestamosQuery = Prestamo::with(['cliente', 'productoVale', 'pagos'])
            ->where('created_by_user_id', $distribuidoraId)
            ->where('created_at', '<=', $fechaCorteRef)
            ->where(function($query) use ($fechaCorteRef) {
                $query->where('estado', 'activo')
                      ->orWhere('updated_at', '>', $fechaCorteRef);
            });

        $prestamos = $prestamosQuery->orderBy('created_at', 'desc')->get();

        return view('prestamos.relacion_pdf', compact('operador', 'distribuidora', 'configuracion', 'prestamos', 'relacion'));
    }
}
