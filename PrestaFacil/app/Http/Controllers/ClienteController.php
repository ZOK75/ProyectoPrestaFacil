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
     * Verifica que el usuario tenga acceso a clientes.
     */
    private function verificarAcceso(): ?\Illuminate\Http\RedirectResponse
    {
        $operador = $this->operador();
        if (!$operador) {
            return redirect()->route('login');
        }

        if ($operador->esGerenteGeneral() || $operador->esGerenteSucursal()) {
            $ruta = $operador->esGerenteGeneral() ? 'gerente-general.dashboard' : 'gerente-sucursal.dashboard';
            return redirect()->route($ruta)
                ->with('error', 'Acceso denegado: El rol gerencial no tiene permisos para acceder al módulo de clientes.');
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

        // Si es distribuidor, filtra los clientes creados por él
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

        if ($operador->esAdministrador()) {
            return redirect()->route('clientes.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede registrar clientes.');
        }

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

        if ($operador->esAdministrador()) {
            return redirect()->route('clientes.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede registrar clientes.');
        }

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

        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('clientes.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede editar clientes.');
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' está desactivado y no puede ser modificado.");
        }

        if ($cliente->tieneSolicitudPendiente()) {
            return redirect()->route('clientes.index')
                ->with('warning', "El cliente '{$cliente->nombre}' ya tiene una solicitud pendiente de autorización.");
        }

        $cliente->load(['createdBy', 'desactivadoPor']);

        return view('clientes.edit', compact('cliente', 'operador'));
    }

    /**
     * Actualizar datos del cliente.
     */
    public function update(UpdateClienteRequest $request, Cliente $cliente)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('clientes.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede modificar clientes.');
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' está desactivado y no puede ser modificado.");
        }

        if ($cliente->tieneSolicitudPendiente()) {
            return redirect()->route('clientes.index')
                ->with('warning', "El cliente '{$cliente->nombre}' ya tiene una solicitud pendiente de autorización.");
        }

        $data = $request->validated();

        // FLUJO DISTRIBUIDOR: Envía solicitud de actualización
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
                ->with('success', "Se ha enviado la Solicitud de Actualización para '{$cliente->nombre}'.");
        }

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
     * Desactivar cliente.
     */
    public function destroy(Request $request, Cliente $cliente)
    {
        if ($redirect = $this->verificarAcceso()) {
            return $redirect;
        }

        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('clientes.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede desactivar clientes.');
        }

        if (!$cliente->activo) {
            return redirect()->route('clientes.index')
                ->with('info', "El cliente '{$cliente->nombre}' ya se encuentra desactivado.");
        }

        if ($cliente->tieneSolicitudPendiente()) {
            return redirect()->route('clientes.index')
                ->with('warning', "El cliente '{$cliente->nombre}' ya tiene una solicitud pendiente de autorización.");
        }

        // FLUJO DISTRIBUIDOR: Envía solicitud de desactivación
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
                ->with('success', "Se ha enviado la Solicitud de Desactivación para '{$cliente->nombre}'.");
        }

        // Desactivación inmediata
        $cliente->update([
            'activo' => false,
            'desactivado_at' => now(),
            'desactivado_by_user_id' => Auth::id() ?? $operador?->id,
        ]);

        return redirect()->route('clientes.index')
            ->with('success', "El cliente '{$cliente->nombre}' (CURP: {$cliente->curp}) fue desactivado correctamente.");
    }
}
