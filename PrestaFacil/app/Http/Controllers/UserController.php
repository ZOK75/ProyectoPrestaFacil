<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Obtiene el usuario autenticado actual o, mientras no haya sesión,
     * devuelve el primer Gerente General para que el CRUD funcione en dev.
     */
    private function operador(): User
    {
        if (Auth::check()) {
            return Auth::user()->load('rol');
        }

        // Fallback desarrollo: actuar como Gerente General
        return User::whereHas('rol', fn ($q) => $q->where('nombre', 'Gerente General'))
            ->first() ?? User::first();
    }

    /**
     * Listado de usuarios con filtros por sucursal, rol, estado y búsqueda.
     */
    public function index(Request $request)
    {
        $operador = $this->operador();
        $query = User::with(['rol', 'sucursal', 'desactivadoPor']);

        // Si es Distribuidor, solo puede ver usuarios ACTIVOS
        if ($operador->esDistribuidor()) {
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

        // Restricción por Sucursal: Usuarios que no son Gerente General ni Administrador sólo ven usuarios de su sucursal
        if (!$operador->esGerenteGeneral() && !$operador->esAdministrador()) {
            if ($operador->sucursal_id) {
                $query->where('sucursal_id', $operador->sucursal_id);
            }
        }

        // Un gerente de sucursal solo ve ciertos roles de su propia sucursal
        if ($operador->esGerenteSucursal()) {
            $query->whereHas('rol', fn($q) => $q->whereIn('nombre', ['Coordinador', 'Cajero', 'Distribuidor', 'Distribuidora', 'coordinador', 'cajero', 'distribuidor', 'distribuidora', 'Verificador', 'verificador']));
        }

        // Filtro por texto
        if ($request->filled('buscar')) {
            $buscar = $request->input('buscar');
            $query->where(function ($q) use ($buscar) {
                $q->where('name', 'like', "%{$buscar}%")
                  ->orWhere('email', 'like', "%{$buscar}%");
            });
        }

        // Filtro por rol
        if ($request->filled('rol_id')) {
            $query->where('rol_id', $request->input('rol_id'));
        }

        // Filtro por sucursal (gerente general y administrador)
        if ($request->filled('sucursal_id') && ($operador->esGerenteGeneral() || $operador->esAdministrador())) {
            $query->where('sucursal_id', $request->input('sucursal_id'));
        }

        $usuarios = $query->orderBy('name')->paginate(12)->withQueryString();

        $statsQuery = User::query();
        if (!$operador->esGerenteGeneral() && !$operador->esAdministrador() && $operador->sucursal_id) {
            $statsQuery->where('sucursal_id', $operador->sucursal_id);
        }

        $stats = [
            'total' => $operador->esDistribuidor() ? (clone $statsQuery)->where('activo', true)->count() : (clone $statsQuery)->count(),
            'activos' => (clone $statsQuery)->where('activo', true)->count(),
            'inactivos' => (clone $statsQuery)->where('activo', false)->count(),
            'con_rol' => (clone $statsQuery)->whereNotNull('rol_id')->count(),
            'sin_sucursal' => (clone $statsQuery)->whereNull('sucursal_id')->count(),
        ];

        $roles = Rol::orderBy('nombre')->get();
        $sucursales = Sucursal::where('activo', true)->orderBy('nombre')->get();

        return view('usuarios.index', compact('usuarios', 'stats', 'roles', 'sucursales', 'operador'));
    }

    /**
     * Formulario de alta de usuario. No permitido para Distribuidor ni Administrador (solo lectura).
     */
    public function create()
    {
        $operador = $this->operador();

        if ($operador->esDistribuidor()) {
            abort(403, 'Acceso denegado: Tu rol de Distribuidor solo tiene permisos de lectura para usuarios activos.');
        }

        $rolesPermitidos = $operador->rolesPermitidos();
        $sucursalesPermitidas = $operador->sucursalesPermitidas();

        return view('usuarios.create', compact('operador', 'rolesPermitidos', 'sucursalesPermitidas'));
    }

    /**
     * Registrar un nuevo usuario. Al registrarse, los distribuidores SIEMPRE inician en categoría 'cobre'.
     */
    public function store(StoreUserRequest $request)
    {
        $operador = $this->operador();

        if ($operador->esDistribuidor()) {
            abort(403, 'Acceso denegado: Tu rol de Distribuidor solo tiene permisos de lectura para usuarios activos.');
        }

        $data = $request->validated();

        // Si el operador es Gerente de Sucursal, forzar que la sucursal del nuevo usuario sea su sucursal
        if ($operador->esGerenteSucursal()) {
            $data['sucursal_id'] = $operador->sucursal_id;
        }

        // Validación de permisos de rol
        $rolesPermitidosIds = $operador->rolesPermitidos()->pluck('id')->toArray();
        if (!in_array($data['rol_id'], $rolesPermitidosIds)) {
            return back()->withErrors(['rol_id' => 'Acceso denegado: No tienes permisos para asignar este rol.'])
                         ->with('error', 'Acceso denegado: No tienes permisos para asignar este rol.')
                         ->withInput();
        }

        $rolSeleccionado = Rol::find($data['rol_id']);
        $nombreRol = strtolower($rolSeleccionado?->nombre ?? '');

        // Generar CURP ficticio único si no aplica
        $data['curp'] = 'CURP' . strtoupper(uniqid());
        $data['password'] = Hash::make($data['password']);
        $data['activo'] = true;

        if (in_array($nombreRol, ['distribuidor', 'distribuidora'])) {
            $data['categoria_distribuidor'] = 'cobre';
        }

        $usuario = User::create($data);

        AuditService::registrar(
            'REGISTRO_USUARIO',
            "Usuario '{$usuario->name}' ({$rolSeleccionado?->nombre}) creado por " . ($operador->name ?? 'Usuario'),
            [
                'entidad_tipo' => 'users',
                'entidad_id' => $usuario->id,
                'user_id' => Auth::id() ?? $operador->id,
                'user_rol' => $operador->rol?->nombre,
                'sucursal_id' => $usuario->sucursal_id,
            ]
        );

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario '{$usuario->name}' registrado exitosamente con rol '{$rolSeleccionado?->nombre}'.");
    }

    /**
     * Muestra el detalle de un usuario específico.
     */
    public function show(User $usuario)
    {
        $operador = $this->operador();
        
        // Cargar las relaciones necesarias para evitar N+1 en la vista
        $usuario->load(['rol', 'sucursal', 'desactivadoPor']);

        return view('usuarios.show', compact('usuario', 'operador'));
    }

    /**
     * Formulario de edición. No permitido para Distribuidor ni Administrador.
     */
    public function edit(User $usuario)
    {
        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            abort(403, 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede modificar usuarios.');
        }

        if ($operador->esDistribuidor()) {
            abort(403, 'Acceso denegado: Tu rol de Distribuidor solo tiene permisos de lectura para usuarios activos.');
        }

        if (!$usuario->activo) {
            return redirect()->route('usuarios.index')
                ->with('error', "No se puede editar al usuario '{$usuario->name}' porque se encuentra inactivo.");
        }

        // Si el operador es Gerente de Sucursal, solo puede editar personal y distribuidores de su propia sucursal (excluyendo a otros Gerentes de Sucursal)
        if ($operador->esGerenteSucursal()) {
            if ($usuario->sucursal_id !== $operador->sucursal_id || $usuario->esGerenteSucursal()) {
                abort(403, 'Acceso denegado: El Gerente de Sucursal no puede modificar los datos de un Gerente de Sucursal.');
            }
        }

        $rolesPermitidos = $operador->rolesPermitidos($usuario->esDistribuidor());
        $sucursalesPermitidas = $operador->sucursalesPermitidas();

        return view('usuarios.edit', compact('usuario', 'operador', 'rolesPermitidos', 'sucursalesPermitidas'));
    }

    /**
     * Actualizar datos del usuario.
     */
    public function update(UpdateUserRequest $request, User $usuario)
    {
        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            abort(403, 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede modificar usuarios.');
        }

        if ($operador->esDistribuidor()) {
            abort(403, 'Acceso denegado: Tu rol de Distribuidor solo tiene permisos de lectura para usuarios activos.');
        }

        if (!$usuario->activo) {
            return redirect()->route('usuarios.index')
                ->with('error', "No se puede modificar al usuario '{$usuario->name}' porque se encuentra inactivo.");
        }

        // Si el operador es Gerente de Sucursal, solo puede modificar usuarios de su propia sucursal (excluyendo a otros Gerentes de Sucursal)
        if ($operador->esGerenteSucursal()) {
            if ($usuario->sucursal_id !== $operador->sucursal_id || $usuario->esGerenteSucursal()) {
                abort(403, 'Acceso denegado: El Gerente de Sucursal no puede modificar los datos de un Gerente de Sucursal.');
            }
        }

        $data = $request->validated();

        if ($operador->esGerenteSucursal()) {
            $data['sucursal_id'] = $operador->sucursal_id;
        }

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $datosAntes = $usuario->makeHidden('password')->toArray();
        $usuario->update($data);
        $datosDespues = $usuario->fresh()->makeHidden('password')->toArray();

        $resumenCambios = AuditService::describirCambiosUsuario($datosAntes, $datosDespues, $request->validated());

        // Notificaciones automáticas si cambió límite de crédito o categoría de distribuidor
        if ($usuario->esDistribuidor()) {
            if (isset($data['limite_credito']) && floatval($data['limite_credito']) != floatval($datosAntes['limite_credito'] ?? 0)) {
                \App\Models\NotificacionCajero::enviar(
                    $usuario->id,
                    'solicitud_credito_aprobada',
                    'Línea de Crédito Actualizada',
                    "La Gerencia ({$operador->name}) ha actualizado tu límite de crédito a $" . number_format($usuario->limite_credito, 2) . "."
                );

                if ($usuario->coordinador_id) {
                    \App\Models\NotificacionCajero::enviar(
                        $usuario->coordinador_id,
                        'solicitud_credito_aprobada',
                        'Línea de Crédito de Distribuidora Actualizada',
                        "La Gerencia ({$operador->name}) ha actualizado el límite de crédito de {$usuario->name} a $" . number_format($usuario->limite_credito, 2) . "."
                    );
                }
            }

            if (isset($data['categoria_distribuidor']) && strtolower($data['categoria_distribuidor']) !== strtolower($datosAntes['categoria_distribuidor'] ?? '')) {
                \App\Models\NotificacionCajero::enviar(
                    $usuario->id,
                    'solicitud_categoria_aprobada',
                    'Categoría de Distribuidor Actualizada',
                    "La Gerencia ({$operador->name}) ha actualizado tu nivel a Categoría " . strtoupper($usuario->categoria_distribuidor) . "."
                );

                if ($usuario->coordinador_id) {
                    \App\Models\NotificacionCajero::enviar(
                        $usuario->coordinador_id,
                        'solicitud_categoria_aprobada',
                        'Categoría de Distribuidora Actualizada',
                        "La Gerencia ({$operador->name}) ha actualizado a {$usuario->name} a Categoría " . strtoupper($usuario->categoria_distribuidor) . "."
                    );
                }
            }
        }

        AuditService::registrar(
            'ACTUALIZACION_USUARIO',
            "Usuario '{$usuario->name}' actualizado por " . ($operador->name ?? 'Usuario') . $resumenCambios['texto'],
            [
                'entidad_tipo' => 'users',
                'entidad_id' => $usuario->id,
                'user_id' => Auth::id() ?? $operador->id,
                'user_rol' => $operador->rol?->nombre,
                'sucursal_id' => $usuario->sucursal_id,
                'antes' => $datosAntes,
                'despues' => $datosDespues,
                'detalle_cambios' => $resumenCambios['cambios'],
                'resumen_modificaciones' => $resumenCambios['detalles'],
            ]
        );

        return redirect()->route('usuarios.index')
            ->with('success', "Usuario '{$usuario->name}' actualizado correctamente.");
    }

    /**
     * Desactivar un usuario.
     */
    public function destroy(User $usuario)
    {
        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            abort(403, 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede desactivar usuarios.');
        }

        if ($operador->esDistribuidor()) {
            abort(403, 'Acceso denegado: Tu rol de Distribuidor solo tiene permisos de lectura para usuarios activos.');
        }

        if (!$usuario->activo) {
            return redirect()->route('usuarios.index')
                ->with('info', "El usuario '{$usuario->name}' ya se encuentra desactivado.");
        }

        if ($usuario->id === Auth::id()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'No puedes desactivar tu propia cuenta de usuario.');
        }

        $datosAntes = $usuario->makeHidden('password')->toArray();
        $usuario->update([
            'activo' => false,
            'desactivado_at' => now(),
            'desactivado_by_user_id' => Auth::id() ?? $operador->id,
        ]);

        AuditService::registrar(
            'DESACTIVACION_USUARIO',
            "Usuario '{$usuario->name}' desactivado por " . ($operador->name ?? 'Usuario'),
            [
                'entidad_tipo' => 'users',
                'entidad_id' => $usuario->id,
                'user_id' => Auth::id() ?? $operador->id,
                'user_rol' => $operador->rol?->nombre,
                'sucursal_id' => $operador->sucursal_id,
                'antes' => $datosAntes,
                'despues' => $usuario->fresh()->makeHidden('password')->toArray(),
            ]
        );

        return redirect()->route('usuarios.index')
            ->with('success', "El usuario '{$usuario->name}' fue desactivado correctamente el " . now()->format('d/m/Y H:i') . ".");
    }
}
