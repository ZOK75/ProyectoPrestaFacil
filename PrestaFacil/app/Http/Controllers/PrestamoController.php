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
            return redirect()->route($ruta)
                ->with('error', 'Acceso denegado: El rol gerencial no tiene permisos para acceder al módulo de préstamos.');
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
            return redirect()->route('prestamos.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
        }

        // Obtener únicamente vales activos
        $valesActivos = ProductoVale::where('activo', true)
            ->orderBy('monto_prestamo', 'asc')
            ->get();

        // Obtener clientes activos
        $clientes = Cliente::where('activo', true)
            ->orderBy('nombre', 'asc')
            ->get();

        $clienteSeleccionado = null;
        $tipoAsignacion = 'prevale';

        if ($request->filled('cliente_id')) {
            $clienteSeleccionado = Cliente::find($request->input('cliente_id'));
            if ($clienteSeleccionado) {
                $tieneHistorial = Prestamo::where('cliente_id', $clienteSeleccionado->id)->exists();
                $tipoAsignacion = $tieneHistorial ? 'vale' : 'prevale';
            }
        }

        return view('prestamos.create', compact('operador', 'valesActivos', 'clientes', 'clienteSeleccionado', 'tipoAsignacion'));
    }

    /**
     * Registrar la asignación de un vale/prevale con validaciones de negocio.
     */
    public function store(StorePrestamoRequest $request)
    {
        $operador = $this->operador();

        if ($redirect = $this->verificarBloqueoGerencial($operador)) {
            return $redirect;
        }

        if ($operador && $operador->esAdministrador()) {
            return redirect()->route('prestamos.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
        }

        $cliente = Cliente::findOrFail($request->cliente_id);

        if (!$cliente->activo) {
            return back()->withErrors(['cliente_id' => 'Este cliente está desactivado.'])->withInput();
        }

        // 1. Solo se permite 1 préstamo activo por cliente
        $prestamoActivo = Prestamo::where('cliente_id', $cliente->id)
            ->where('estado', 'activo')
            ->first();

        if ($prestamoActivo) {
            return back()->withErrors([
                'cliente_id' => "No es posible otorgar un nuevo vale. El cliente '{$cliente->nombre}' ya cuenta con un préstamo activo pendiente de liquidar (Referencia: {$prestamoActivo->referencia})."
            ])->withInput();
        }

        $vale = ProductoVale::where('id', $request->producto_vale_id)
            ->where('activo', true)
            ->firstOrFail();

        // 2. Límite máximo por vale (50% del límite de crédito + $500)
        if ($operador && $operador->esDistribuidor()) {
            $montoMaxVale = $operador->montoMaximoPermitidoPorVale();
            if (floatval($vale->monto_prestamo) > $montoMaxVale) {
                return back()->withErrors([
                    'producto_vale_id' => "El vale solicitado ($" . number_format($vale->monto_prestamo, 2) . ") excede el tope máximo permitido por vale para tu línea de crédito ($" . number_format($montoMaxVale, 2) . ")."
                ])->withInput();
            }

            // 3. Verificar crédito disponible del distribuidor
            $creditoDisponible = $operador->creditoDisponible();
            if (floatval($vale->monto_prestamo) > $creditoDisponible) {
                return back()->withErrors([
                    'producto_vale_id' => "Límite de crédito insuficiente. Tu crédito disponible actual es de $" . number_format($creditoDisponible, 2) . " y el vale solicitado requiere $" . number_format($vale->monto_prestamo, 2) . "."
                ])->withInput();
            }
        }

        // Determinar si es la primera asignación (prevale) o subsecuente (vale)
        $conteoPrevio = Prestamo::where('cliente_id', $cliente->id)->count();
        $tipo = ($conteoPrevio === 0) ? 'prevale' : 'vale';

        // Generar Referencia Única
        $prefijo = strtoupper($tipo);
        $referencia = "REF-{$prefijo}-" . date('Ymd') . "-" . str_pad(rand(100, 999), 3, '0', STR_PAD_LEFT);

        $prestamo = Prestamo::create([
            'referencia' => $referencia,
            'cliente_id' => $cliente->id,
            'producto_vale_id' => $vale->id,
            'tipo' => $tipo,
            'monto_prestamo' => $vale->monto_prestamo,
            'cuota_quincenal' => $vale->cuota_quincenal,
            'pagos_totales' => $vale->plazo_quincenas,
            'pagos_realizados' => 0,
            'monto_total_pagar' => $vale->monto_total_pagar,
            'adeudo_pendiente' => $vale->monto_total_pagar,
            'pagos_recibidos' => 0,
            'multas' => 0,
            'estado' => 'activo',
            'activo' => true,
            'created_by_user_id' => Auth::id() ?? $operador?->id,
        ]);

        $tipoTexto = strtoupper($tipo);
        return redirect()->route('prestamos.show', $prestamo)
            ->with('success', "¡Asignación exitosa! Se generó el {$tipoTexto} con Referencia {$referencia} para {$cliente->nombre}. Saldo de crédito actualizado.");
    }

    /**
     * Ficha técnica del estado de cuenta de la Referencia.
     */
    public function show(Prestamo $prestamo)
    {
        $operador = $this->operador();

        if ($redirect = $this->verificarBloqueoGerencial($operador)) {
            return $redirect;
        }

        $prestamo->load(['cliente', 'productoVale', 'pagos.registradoPor', 'createdBy']);

        return view('prestamos.show', compact('prestamo', 'operador'));
    }

    /**
     * Formulario móvil para registrar abono y aplicar multas.
     */
    public function pagoForm(Prestamo $prestamo)
    {
        $operador = $this->operador();

        if ($redirect = $this->verificarBloqueoGerencial($operador)) {
            return $redirect;
        }

        if ($operador && $operador->esAdministrador()) {
            return redirect()->route('prestamos.show', $prestamo)
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
        }

        if ($operador && $operador->esDistribuidor()) {
            return redirect()->route('prestamos.show', $prestamo)
                ->with('error', 'Acceso denegado: El cobro y registro de abonos debe ser realizado en ventanilla por el personal de Caja.');
        }

        if ($prestamo->estaPagado()) {
            return redirect()->route('prestamos.show', $prestamo)
                ->with('info', "La referencia {$prestamo->referencia} ya se encuentra liquidada.");
        }

        return view('prestamos.pago', compact('prestamo', 'operador'));
    }

    /**
     * Procesar el pago de quincena.
     */
    public function registrarPago(StorePagoRequest $request, Prestamo $prestamo)
    {
        $operador = $this->operador();

        if ($redirect = $this->verificarBloqueoGerencial($operador)) {
            return $redirect;
        }

        if ($operador && $operador->esAdministrador()) {
            return redirect()->route('prestamos.show', $prestamo)
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría).');
        }

        if ($operador && $operador->esDistribuidor()) {
            return redirect()->route('prestamos.show', $prestamo)
                ->with('error', 'Acceso denegado: El cobro y registro de abonos debe ser realizado en ventanilla por el personal de Caja.');
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
    public function relacionCobranza()
    {
        $operador = $this->operador();

        if ($redirect = $this->verificarBloqueoGerencial($operador)) {
            return $redirect;
        }

        $this->corteService->verificarYProcesarCortesYVencimientos();
        $configuracion = Configuracion::actual();

        // Cargar todos los préstamos activos de los clientes de este distribuidor
        $prestamosQuery = Prestamo::with(['cliente', 'productoVale', 'pagos'])
            ->where('estado', 'activo');

        if ($operador && $operador->esDistribuidor()) {
            $prestamosQuery->where('created_by_user_id', $operador->id);
        }

        $prestamos = $prestamosQuery->orderBy('created_at', 'desc')->get();

        $relacion = null;
        if ($operador && $operador->esDistribuidor()) {
            $relacion = RelacionCobranza::where('distribuidora_id', $operador->id)
                ->where('fecha_corte', $configuracion->fecha_corte)
                ->first();
        }

        return view('prestamos.relacion_pdf', compact('operador', 'configuracion', 'prestamos', 'relacion'));
    }
}
