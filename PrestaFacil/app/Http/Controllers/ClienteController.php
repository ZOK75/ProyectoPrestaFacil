<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreClienteRequest;
use App\Http\Requests\UpdateClienteRequest;
use App\Models\Cliente;
use App\Models\SolicitudCliente;
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
            return Auth::user()->load('rol', 'sucursal');
        }

        return User::whereHas('rol', fn ($q) => $q->whereIn('nombre', ['Distribuidor', 'Distribuidora']))
            ->first() ?? User::first();
    }

    /**
     * Verifica que el usuario tenga acceso a clientes (Distribuidores y Gerentes).
     */
    private function verificarAcceso(): ?\Illuminate\Http\RedirectResponse
    {
        $operador = $this->operador();
        if (!$operador) {
            return redirect()->route('login');
        }
        return null;
    }

    /**
     * Listado de clientes con filtros por búsqueda y estado.
     */
    public function index(Request $request)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $operador = $this->operador();
        $query = Cliente::with(['createdBy', 'desactivadoPor', 'solicitudPendiente']);

        // Si es distribuidor, filtra los clientes creados por él o de su sucursal
        if ($operador->esDistribuidor()) {
            $query->where('created_by_user_id', $operador->id);
        }

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

        $baseStats = $operador->esDistribuidor() 
            ? Cliente::where('created_by_user_id', $operador->id) 
            : Cliente::query();

        $stats = [
            'total' => (clone $baseStats)->count(),
            'activos' => (clone $baseStats)->where('activo', true)->count(),
            'inactivos' => (clone $baseStats)->where('activo', false)->count(),
        ];

        return view('clientes.index', compact('clientes', 'stats', 'operador'));
    }

    /**
     * Formulario de registro de cliente.
     */
    public function create()
    {
        if ($redirect = $this->verificarAcceso()) {
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
        if ($redirect = $this->verificarAcceso()) {
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
            ->with('success', "El cliente '{$cliente->nombre}' (CURP: {$cliente->curp}) fue registrado exitosamente.");
    }

    /**
     * Ficha técnica del cliente y visualización/descarga de expedientes PDF.
     */
    public function show(Cliente $cliente)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $cliente->load(['createdBy', 'desactivadoPor', 'solicitudes.aprobadoPor', 'solicitudes.distribuidor']);
        $operador = $this->operador();

        return view('clientes.show', compact('cliente', 'operador'));
    }

    /**
     * Formulario de edición de cliente.
     */
    public function edit(Cliente $cliente)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' está desactivado y no puede ser modificado.");
        }

        if ($cliente->tieneSolicitudPendiente()) {
            return redirect()->route('clientes.index')
                ->with('warning', "El cliente '{$cliente->nombre}' ya tiene una solicitud pendiente de autorización en Gerencia.");
        }

        $cliente->load(['createdBy', 'desactivadoPor']);
        $operador = $this->operador();

        return view('clientes.edit', compact('cliente', 'operador'));
    }

    /**
     * Actualizar datos del cliente:
     * - Si el operador es Distribuidor: Genera una solicitud de actualización a Gerencia.
     * - Si el operador es Gerente: Aplica la actualización directamente.
     */
    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' está desactivado y no puede ser modificado.");
        }

        if ($cliente->tieneSolicitudPendiente()) {
            return redirect()->route('clientes.index')
                ->with('warning', "El cliente '{$cliente->nombre}' ya tiene una solicitud pendiente de autorización en Gerencia.");
        }

        $operador = $this->operador();
        $data = $request->validated();

        // FLUJO DISTRIBUIDOR: Envía solicitud de actualización a Gerencia
        if ($operador->esDistribuidor()) {
            $pdfIneNuevo = null;
            $pdfComprobanteNuevo = null;

            if ($request->hasFile('pdf_ine')) {
                $pdfIneNuevo = $request->file('pdf_ine')->store('expedientes_clientes/solicitudes_temp', 'public');
            }

            if ($request->hasFile('pdf_comprobante')) {
                $pdfComprobanteNuevo = $request->file('pdf_comprobante')->store('expedientes_clientes/solicitudes_temp', 'public');
            }

            SolicitudCliente::create([
                'tipo' => 'actualizacion',
                'estado' => 'pendiente',
                'cliente_id' => $cliente->id,
                'distribuidor_id' => $operador->id,
                'sucursal_id' => $operador->sucursal_id,
                'datos_originales' => $cliente->only([
                    'nombre', 'curp', 'rfc', 'fecha_nacimiento', 'lugar_nacimiento',
                    'calle', 'colonia', 'codigo_postal', 'ciudad', 'estado',
                    'path_ine_pdf', 'path_comprobante_pdf'
                ]),
                'datos_solicitados' => $data,
                'motivo' => $request->input('motivo_solicitud') ?? 'Actualización de datos generales del cliente por Distribuidor.',
                'pdf_ine_nuevo' => $pdfIneNuevo,
                'pdf_comprobante_nuevo' => $pdfComprobanteNuevo,
            ]);

            return redirect()->route('clientes.index')
                ->with('success', "Se ha enviado la Solicitud de Actualización para '{$cliente->nombre}'. Tu Gerente de Sucursal y el Gerente General han sido notificados para autorizar los cambios.");
        }

        // FLUJO GERENTE: Aplica directamente los cambios
        if ($request->hasFile('pdf_ine')) {
            if ($cliente->path_ine_pdf && Storage::disk('public')->exists($cliente->path_ine_pdf)) {
                Storage::disk('public')->delete($cliente->path_ine_pdf);
            }
            $data['path_ine_pdf'] = $request->file('pdf_ine')->store('expedientes_clientes/ine', 'public');
        }

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
     * Desactivar cliente:
     * - Si el operador es Distribuidor: Genera una solicitud de desactivación a Gerencia.
     * - Si el operador es Gerente: Desactiva directamente al cliente.
     */
    public function destroy(Request $request, Cliente $cliente)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' ya se encuentra desactivado.");
        }

        if ($cliente->tieneSolicitudPendiente()) {
            return redirect()->route('clientes.index')
                ->with('warning', "El cliente '{$cliente->nombre}' ya tiene una solicitud pendiente de autorización en Gerencia.");
        }

        $operador = $this->operador();

        // FLUJO DISTRIBUIDOR: Envía solicitud de desactivación a Gerencia
        if ($operador->esDistribuidor()) {
            SolicitudCliente::create([
                'tipo' => 'desactivacion',
                'estado' => 'pendiente',
                'cliente_id' => $cliente->id,
                'distribuidor_id' => $operador->id,
                'sucursal_id' => $operador->sucursal_id,
                'datos_originales' => $cliente->toArray(),
                'motivo' => $request->input('motivo_desactivacion') ?? 'Solicitud de baja/desactivación iniciada por Distribuidor.',
            ]);

            return redirect()->route('clientes.index')
                ->with('success', "Se ha enviado la Solicitud de Desactivación para '{$cliente->nombre}'. Tu Gerente de Sucursal y el Gerente General han sido notificados para autorizar la baja.");
        }

        // FLUJO GERENTE: Desactivación inmediata
        $cliente->update([
            'activo' => false,
            'desactivado_at' => now(),
            'desactivado_by_user_id' => Auth::id() ?? $operador?->id,
        ]);

        return redirect()->route('clientes.index')
            ->with('success', "El cliente '{$cliente->nombre}' (CURP: {$cliente->curp}) fue desactivado correctamente.");
    }
}
