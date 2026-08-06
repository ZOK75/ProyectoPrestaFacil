<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ClienteController extends Controller
{
    /**
     * Obtiene el usuario operador actual.
     */
    private function operador(): ?User
    {
        if (Auth::check()) {
            return Auth::user()->load('rol');
        }

        return User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']))
            ->first() ?? User::first();
    }

    /**
     * Verifica que el usuario sea Distribuidor / Distribuidora.
     */
    private function verificarAccesoDistribuidor(): ?\Illuminate\Http\RedirectResponse
    {
        $operador = $this->operador();
        if ($operador && !$operador->esDistribuidor()) {
            return redirect()->route('producto-vales.index')
                ->with('error', 'Acceso denegado: El módulo de clientes es exclusivo para el rol de Distribuidor/Distribuidora.');
        }
        return null;
    }

    /**
     * Listado de clientes con filtros por búsqueda y estado.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->verificarAccesoDistribuidor()) {
            return $redirect;
        }

        $operador = $this->operador();
        $query = Cliente::with(['createdBy', 'desactivadoPor']);

        // Filtro por texto (Nombre, CURP, RFC)
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('nombre', 'like', "%{$buscar}%")
                  ->orWhere('curp', 'like', "%{$buscar}%")
                  ->orWhere('rfc', 'like', "%{$buscar}%");
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

        $clientes = $query->orderBy('created_at', 'desc')->paginate(10)->withQueryString();

        $stats = [
            'total' => Cliente::count(),
            'activos' => Cliente::where('activo', true)->count(),
            'inactivos' => Cliente::where('activo', false)->count(),
        ];

        return view('clientes.index', compact('clientes', 'stats', 'operador'));
    }

    /**
     * Formulario de registro de cliente.
     */
    public function create()
    {
        if ($redirect = $this->verificarAccesoDistribuidor()) {
            return $redirect;
        }

        $operador = $this->operador();

        return view('clientes.create', compact('operador'));
    }

    /**
     * Registrar un nuevo cliente con la carga de 2 archivos PDF independientes.
     */
    public function store(StoreClienteRequest $request)
    {
        if ($redirect = $this->verificarAccesoDistribuidor()) {
            return $redirect;
        }

        $operador = $this->operador();
        $data = $request->validated();

        // Almacenar el archivo INE PDF
        if ($request->hasFile('pdf_ine')) {
            $pathIne = $request->file('pdf_ine')->store('expedientes_clientes/ine', 'public');
            $data['path_ine_pdf'] = $pathIne;
        }

        // Almacenar el archivo Comprobante de Domicilio PDF
        if ($request->hasFile('pdf_comprobante')) {
            $pathComprobante = $request->file('pdf_comprobante')->store('expedientes_clientes/comprobantes', 'public');
            $data['path_comprobante_pdf'] = $pathComprobante;
        }

        $data['created_by_user_id'] = Auth::id() ?? $operador?->id;
        $data['activo'] = true;
        $data['desactivado_at'] = null;

        $cliente = Cliente::create($data);

        return redirect()->route('clientes.index')
            ->with('success', "El cliente '{$cliente->nombre}' (CURP: {$cliente->curp}) fue registrado exitosamente con sus expedientes PDF.");
    }

    /**
     * Ficha técnica del cliente y visualización/descarga de expedientes PDF.
     */
    public function show(Cliente $cliente)
    {
        if ($redirect = $this->verificarAccesoDistribuidor()) {
            return $redirect;
        }

        $cliente->load(['createdBy', 'desactivadoPor']);
        $operador = $this->operador();

        return view('clientes.show', compact('cliente', 'operador'));
    }

    /**
     * Formulario de edición de cliente.
     */
    public function edit(Cliente $cliente)
    {
        if ($redirect = $this->verificarAccesoDistribuidor()) {
            return $redirect;
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' está desactivado y no puede ser modificado.");
        }

        $cliente->load(['createdBy', 'desactivadoPor']);
        $operador = $this->operador();

        return view('clientes.edit', compact('cliente', 'operador'));
    }

    /**
     * Actualizar datos del cliente o reemplazar expedientes PDF.
     */
    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        if ($redirect = $this->verificarAccesoDistribuidor()) {
            return $redirect;
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' está desactivado y no puede ser modificado.");
        }

        $data = $request->validated();

        // Actualizar INE PDF si se subió uno nuevo
        if ($request->hasFile('pdf_ine')) {
            if ($cliente->path_ine_pdf && Storage::disk('public')->exists($cliente->path_ine_pdf)) {
                Storage::disk('public')->delete($cliente->path_ine_pdf);
            }
            $data['path_ine_pdf'] = $request->file('pdf_ine')->store('expedientes_clientes/ine', 'public');
        }

        // Actualizar Comprobante PDF si se subió uno nuevo
        if ($request->hasFile('pdf_comprobante')) {
            if ($cliente->path_comprobante_pdf && Storage::disk('public')->exists($cliente->path_comprobante_pdf)) {
                Storage::disk('public')->delete($cliente->path_comprobante_pdf);
            }
            $data['path_comprobante_pdf'] = $request->file('pdf_comprobante')->store('expedientes_clientes/comprobantes', 'public');
        }

        $cliente->update($data);

        return redirect()->route('clientes.index')
            ->with('success', "Los datos del cliente '{$cliente->nombre}' fueron actualizados correctamente.");
    }

    /**
     * Desactivar cliente (sin eliminar registros ni archivos de la BD).
     */
    public function destroy(Cliente $cliente)
    {
        if ($redirect = $this->verificarAccesoDistribuidor()) {
            return $redirect;
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' ya se encuentra desactivado.");
        }

        $operador = $this->operador();

        $cliente->update([
            'activo' => false,
            'desactivado_at' => now(),
            'desactivado_by_user_id' => Auth::id() ?? $operador?->id,
        ]);

        return redirect()->route('clientes.index')
            ->with('success', "El cliente '{$cliente->nombre}' (CURP: {$cliente->curp}) fue desactivado correctamente el " . now()->format('d/m/Y H:i') . ".");
    }
}
