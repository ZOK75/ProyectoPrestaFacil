<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductoValeRequest;
use App\Models\ProductoVale;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProductoValeController extends Controller
{
    /**
     * Obtiene el usuario operador actual.
     */
    private function operador(): ?User
    {
        if (Auth::check()) {
            return Auth::user()->load('rol');
        }

        return User::whereHas('rol', fn ($q) => $q->where('nombre', 'Gerente General'))
            ->first() ?? User::first();
    }

    /**
     * Muestra la lista de productos de vales de préstamo.
     */
    public function index(Request $request)
    {
        $operador = $this->operador();
        
        // Bloquear acceso al Verificador
        if ($operador && $operador->esVerificador()) {
            return redirect()->route('verificador.dashboard')
                ->with('error', 'Acceso denegado: Los Verificadores no tienen acceso al catálogo de vales.');
        }

        $esGerenteGeneral = $operador ? $operador->esGerenteGeneral() : false;
        $esDistribuidor = $operador ? $operador->esDistribuidor() : false;

        $query = ProductoVale::with(['createdBy', 'updatedBy']);

        // Si el usuario es Distribuidor, solo ve vales ACTIVOS
        if ($esDistribuidor) {
            $query->where('activo', true);
        } else {
            // Filtro por estado para otros roles
            if ($request->filled('estado')) {
                if ($request->input('estado') === 'activo') {
                    $query->where('activo', true);
                } elseif ($request->input('estado') === 'inactivo') {
                    $query->where('activo', false);
                }
            }
        }

        // Filtro por texto
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('clave', 'like', "%{$buscar}%");
            });
        }

        $productos = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $stats = [
            'total' => $esDistribuidor ? ProductoVale::where('activo', true)->count() : ProductoVale::count(),
            'activos' => ProductoVale::where('activo', true)->count(),
            'inactivos' => ProductoVale::where('activo', false)->count(),
            'monto_promedio' => ProductoVale::avg('monto_prestamo') ?? 0,
            'costo_seguro_promedio' => ProductoVale::avg('costo_seguro') ?? 0,
        ];

        // Renderizar vista móvil para el rol de Distribuidor / Distribuidora
        if ($esDistribuidor) {
            return view('producto-vales.index_mobile', compact('productos', 'stats', 'esGerenteGeneral', 'esDistribuidor'));
        }

        return view('producto-vales.index', compact('productos', 'stats', 'esGerenteGeneral', 'esDistribuidor'));
    }

    /**
     * Formulario de creación. Solo permitido para Gerente General.
     */
    public function create()
    {
        $operador = $this->operador();
        if (!$operador || !$operador->esGerenteGeneral()) {
            return redirect()->route('producto-vales.index')
                ->with('error', 'Acceso denegado: Únicamente el Gerente General tiene autorización para agregar nuevos vales.');
        }

        return view('producto-vales.create');
    }

    /**
     * Guardar nuevo vale con registro del usuario creador. Solo permitido para Gerente General.
     */
    public function store(StoreProductoValeRequest $request)
    {
        $operador = $this->operador();
        if (!$operador || !$operador->esGerenteGeneral()) {
            return redirect()->route('producto-vales.index')
                ->with('error', 'Acceso denegado: Únicamente el Gerente General tiene autorización para agregar nuevos vales.');
        }

        $data = $request->validated();
        $data['created_by_user_id'] = Auth::id() ?? $operador->id;
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
        $operador = $this->operador();

        // Bloquear acceso al Verificador
        if ($operador && $operador->esVerificador()) {
            return redirect()->route('verificador.dashboard')
                ->with('error', 'Acceso denegado: Los Verificadores no tienen acceso al catálogo de vales.');
        }

        $esGerenteGeneral = $operador ? $operador->esGerenteGeneral() : false;
        $esDistribuidor = $operador ? $operador->esDistribuidor() : false;

        // Si es distribuidor y el vale está desactivado, denegar acceso
        if ($esDistribuidor && !$productoVale->activo) {
            return redirect()->route('producto-vales.index')
                ->with('error', 'Acceso denegado: Como Distribuidor solo puedes visualizar vales activos.');
        }

        $amortizacion = [];
        $saldoPendiente = $productoVale->monto_total_pagar;
        $cuota = $productoVale->cuota_quincenal;
        $montoOriginal = $productoVale->monto_prestamo;
        $seguroPorQuincena = $productoVale->costo_seguro / max(1, $productoVale->plazo_quincenas);
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
                'comision_apertura' => $comisionAperturaPorQuincena,
                'interes' => $interesPorQuincena,
                'saldo_restante' => $saldoPendiente,
            ];
        }

        // Renderizar vista móvil para el rol de Distribuidor / Distribuidora
        if ($esDistribuidor) {
            return view('producto-vales.show_mobile', compact('productoVale', 'amortizacion', 'esGerenteGeneral', 'esDistribuidor'));
        }

        return view('producto-vales.show', compact('productoVale', 'amortizacion', 'esGerenteGeneral', 'esDistribuidor'));
    }

    /**
     * La edición únicamente permite desactivar el producto. Solo permitido para Gerente General.
     */
    public function edit(ProductoVale $productoVale)
    {
        $operador = $this->operador();
        if (!$operador || !$operador->esGerenteGeneral()) {
            return redirect()->route('producto-vales.index')
                ->with('error', 'Acceso denegado: Únicamente el Gerente General tiene autorización para desactivar vales.');
        }

        $productoVale->load(['createdBy', 'updatedBy']);

        return view('producto-vales.edit', compact('productoVale'));
    }

    /**
     * Proceso de desactivación con timestamp y registro de usuario. Solo permitido para Gerente General.
     */
    public function update(Request $request, ProductoVale $productoVale)
    {
        $operador = $this->operador();
        if (!$operador || !$operador->esGerenteGeneral()) {
            return redirect()->route('producto-vales.index')
                ->with('error', 'Acceso denegado: Únicamente el Gerente General tiene autorización para desactivar vales.');
        }

        if (!$productoVale->activo) {
            return redirect()->route('producto-vales.index')
                ->with('info', "El vale '{$productoVale->nombre}' ya se encuentra desactivado.");
        }

        $productoVale->update([
            'activo' => false,
            'desactivado_at' => now(),
            'updated_by_user_id' => Auth::id() ?? $operador->id,
        ]);

        return redirect()->route('producto-vales.index')
            ->with('success', "El vale '{$productoVale->nombre}' ({$productoVale->clave}) fue desactivado correctamente el " . now()->format('d/m/Y H:i') . ".");
    }

    /**
     * Desactivación de un vale (sin borrar de BD). Solo permitido para Gerente General.
     */
    public function destroy(ProductoVale $productoVale)
    {
        $operador = $this->operador();
        if (!$operador || !$operador->esGerenteGeneral()) {
            return redirect()->route('producto-vales.index')
                ->with('error', 'Acceso denegado: Únicamente el Gerente General tiene autorización para desactivar vales.');
        }

        if (!$productoVale->activo) {
            return redirect()->route('producto-vales.index')
                ->with('info', "El vale '{$productoVale->nombre}' ya se encuentra desactivado.");
        }

        $productoVale->update([
            'activo' => false,
            'desactivado_at' => now(),
            'updated_by_user_id' => Auth::id() ?? $operador->id,
        ]);

        return redirect()->route('producto-vales.index')
            ->with('success', "El vale '{$productoVale->nombre}' ({$productoVale->clave}) fue desactivado correctamente el " . now()->format('d/m/Y H:i') . ".");
    }
}
