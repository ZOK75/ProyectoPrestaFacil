<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoValeRequest;
use App\Models\ProductoVale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductoValeController extends Controller
{
    /**
     * Muestra la lista de productos de vales de préstamo.
     */
    public function index(Request $request)
    {
        $query = ProductoVale::with(['createdBy', 'updatedBy']);

        // Filtro por texto
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('clave', 'like', "%{$buscar}%");
            });
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            if ($request->input('estado') === 'activo') {
                $query->where('activo', true);
            } elseif ($request->input('estado') === 'inactivo') {
                $query->where('activo', false);
            }
        }

        $productos = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $stats = [
            'total' => ProductoVale::count(),
            'activos' => ProductoVale::where('activo', true)->count(),
            'inactivos' => ProductoVale::where('activo', false)->count(),
            'monto_promedio' => ProductoVale::avg('monto_prestamo') ?? 0,
            'costo_seguro_promedio' => ProductoVale::avg('costo_seguro') ?? 0,
        ];

        return view('producto-vales.index', compact('productos', 'stats'));
    }

    /**
     * Formulario de creación.
     */
    public function create()
    {
        return view('producto-vales.create');
    }

    /**
     * Guardar nuevo vale con registro del usuario creador.
     */
    public function store(StoreProductoValeRequest $request)
    {
        $data = $request->validated();
        $data['created_by_user_id'] = Auth::id() ?? null;
        $data['activo'] = true;
        $data['desactivado_at'] = null;

        $productoVale = ProductoVale::create($data);

        return redirect()->route('producto-vales.index')
            ->with('success', "El vale de préstamo '{$productoVale->nombre}' ({$productoVale->clave}) ha sido registrado exitosamente.");
    }

    /**
     * Ficha técnica y tabla de amortización quincenal.
     */
    public function show(ProductoVale $productoVale)
    {
        $productoVale->load(['createdBy', 'updatedBy']);

        $amortizacion = [];
        $saldoPendiente = $productoVale->monto_total_pagar;
        $cuota = $productoVale->cuota_quincenal;
        $montoOriginal = $productoVale->monto_prestamo;
        $seguroPorQuincena = $productoVale->costo_seguro / max(1, $productoVale->plazo_quincenas);
        $comisionTransferenciaPorQuincena = $productoVale->comision_transferencia / max(1, $productoVale->plazo_quincenas);
        $comisionAperturaMonto = $productoVale->monto_prestamo * ($productoVale->comision_apertura / 100);
        $comisionAperturaPorQuincena = $comisionAperturaMonto / max(1, $productoVale->plazo_quincenas);
        $interesPorQuincena = $productoVale->interes_total / max(1, $productoVale->plazo_quincenas);
        $capitalPorQuincena = $montoOriginal / max(1, $productoVale->plazo_quincenas);

        for ($i = 1; $i <= $productoVale->plazo_quincenas; $i++) {
            $saldoPendiente = max(0, $saldoPendiente - $cuota);
            $amortizacion[] = [
                'quincena' => $i,
                'cuota' => $cuota,
                'capital' => $capitalPorQuincena,
                'seguro' => $seguroPorQuincena,
                'comision_transferencia' => $comisionTransferenciaPorQuincena,
                'comision_apertura' => $comisionAperturaPorQuincena, // NUEVO
                'interes' => $interesPorQuincena,
                'saldo_restante' => $saldoPendiente,
            ];
        }

        return view('producto-vales.show', compact('productoVale', 'amortizacion'));
    }

    /**
     * La edición únicamente permite desactivar el producto.
     */
    public function edit(ProductoVale $productoVale)
    {
        $productoVale->load(['createdBy', 'updatedBy']);

        return view('producto-vales.edit', compact('productoVale'));
    }

    /**
     * Proceso de desactivación con timestamp y registro de usuario.
     */
    public function update(Request $request, ProductoVale $productoVale)
    {
        // Solo se permite desactivar el producto
        if (!$productoVale->activo) {
            return redirect()->route('producto-vales.index')
                ->with('info', "El vale '{$productoVale->nombre}' ya se encuentra desactivado.");
        }

        $productoVale->update([
            'activo' => false,
            'desactivado_at' => now(),
            'updated_by_user_id' => Auth::id() ?? null,
        ]);

        return redirect()->route('producto-vales.index')
            ->with('success', "El vale '{$productoVale->nombre}' ({$productoVale->clave}) fue desactivado correctamente el " . now()->format('d/m/Y H:i') . ".");
    }

    /**
     * Eliminación de un vale.
     */
    public function destroy(ProductoVale $productoVale)
    {
        $nombre = $productoVale->nombre;
        $productoVale->delete();

        return redirect()->route('producto-vales.index')
            ->with('success', "El vale '{$nombre}' fue eliminado exitosamente.");
    }
}
