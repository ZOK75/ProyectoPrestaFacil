<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePagoRequest;
use App\Http\Requests\StorePrestamoRequest;
use App\Models\Cliente;
use App\Models\PagoPrestamo;
use App\Models\Prestamo;
use App\Models\ProductoVale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PrestamoController extends Controller
{
    private function operador(): ?User
    {
        if (Auth::check()) {
            return Auth::user()->load('rol');
        }

        return User::first();
    }

    /**
     * Catálogo móvil de préstamos y estado de cuenta de clientes.
     */
    public function index(Request $request)
    {
        $operador = $this->operador();
        $query = Prestamo::with(['cliente', 'productoVale', 'pagos']);

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

        return view('prestamos.index', compact('prestamos', 'stats', 'operador'));
    }

    /**
     * Formulario móvil para asignar un vale/prevale a un cliente.
     */
    public function create(Request $request)
    {
        $operador = $this->operador();

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
     * Registrar la asignación de un vale/prevale con generación de Referencia única.
     */
    public function store(StorePrestamoRequest $request)
    {
        $operador = $this->operador();
        $cliente = Cliente::findOrFail($request->cliente_id);

        if (!$cliente->activo) {
            return back()->withErrors(['cliente_id' => 'Este cliente está desactivado.'])->withInput();
        }

        $vale = ProductoVale::where('id', $request->producto_vale_id)
            ->where('activo', true)
            ->firstOrFail();

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
            ->with('success', "¡Asignación exitosa! Se generó el {$tipoTexto} con la Referencia {$referencia} para {$cliente->nombre}.");
    }

    /**
     * Ficha técnica del estado de cuenta de la Referencia.
     */
    public function show(Prestamo $prestamo)
    {
        $prestamo->load(['cliente', 'productoVale', 'pagos.registradoPor', 'createdBy']);
        $operador = $this->operador();

        return view('prestamos.show', compact('prestamo', 'operador'));
    }

    /**
     * Formulario móvil para registrar abono y aplicar multas.
     */
    public function pagoForm(Prestamo $prestamo)
    {
        if ($prestamo->estaPagado()) {
            return redirect()->route('prestamos.show', $prestamo)
                ->with('info', "La referencia {$prestamo->referencia} ya se encuentra liquidada.");
        }

        $operador = $this->operador();

        return view('prestamos.pago', compact('prestamo', 'operador'));
    }

    /**
     * Procesar el pago de quincena y aplicar multas acumuladas.
     */
    public function registrarPago(StorePagoRequest $request, Prestamo $prestamo)
    {
        if ($prestamo->estaPagado()) {
            return redirect()->route('prestamos.show', $prestamo)
                ->with('info', "La referencia {$prestamo->referencia} ya se encuentra liquidada.");
        }

        $data = $request->validated();
        $operador = $this->operador();

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

        return redirect()->route('prestamos.show', $prestamo)
            ->with('success', $mensaje);
    }
}
