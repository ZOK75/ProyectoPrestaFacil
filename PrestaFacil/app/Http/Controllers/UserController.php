<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Rol;
use App\Models\Sucursal;
use App\Models\User;
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

        // Un gerente de sucursal solo ve los usuarios de su propia sucursal
        if ($operador->esGerenteSucursal()) {
            $query->where('sucursal_id', $operador->sucursal_id);
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

        $stats = [
            'total' => $operador->esDistribuidor() ? User::where('activo', true)->count() : User::count(),
            'activos' => User::where('activo', true)->count(),
            'inactivos' => User::where('activo', false)->count(),
            'con_rol' => User::whereNotNull('rol_id')->count(),
            'sin_sucursal' => User::whereNull('sucursal_id')->count(),
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

        if ($operador->esAdministrador()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede registrar usuarios.');
        }

        if ($operador->esDistribuidor()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: Tu rol de Distribuidor solo tiene permisos de lectura para usuarios activos.');
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

        if ($operador->esAdministrador()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede registrar usuarios.');
        }

        if ($operador->esDistribuidor()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: Tu rol de Distribuidor solo tiene permisos de lectura para usuarios activos.');
        }

        $data = $request->validated();

        // Validación de permisos de rol
        $rolesPermitidosIds = $operador->rolesPermitidos()->pluck('id')->toArray();
        if (!in_array($data['rol_id'], $rolesPermitidosIds)) {
            return back()->withErrors(['rol_id' => 'No tienes permiso para asignar este rol.'])->withInput();
        }

        // Validación de permisos de sucursal
        $sucursalesPermitidasIds = $operador->sucursalesPermitidas()->pluck('id')->toArray();
        if (!in_array($data['sucursal_id'], $sucursalesPermitidasIds)) {
            return back()->withErrors(['sucursal_id' => 'No tienes permiso para asignar usuarios a esta sucursal.'])->withInput();
        }

        // Validación estricta: Ningún rol puede crear usuarios con el rol Distribuidor
        $rolSeleccionado = Rol::find($data['rol_id']);
        if ($rolSeleccionado && in_array(strtolower($rolSeleccionado->nombre), ['distribuidor', 'distribuidora'])) {
            return back()->withErrors(['rol_id' => 'Ningún rol tiene permitido registrar usuarios con el rol de Distribuidor.'])->withInput();
        }

        $data['categoria_distribuidor'] = null;
        $data['password'] = Hash::make($data['password']);
        $data['activo'] = true;
        $data['desactivado_at'] = null;
        $data['desactivado_by_user_id'] = null;

        $user = User::create($data);

        return redirect()->route('usuarios.index')
            ->with('success', "El usuario '{$user->name}' fue registrado exitosamente.");
    }

    /**
     * Detalle de un usuario.
     */
    public function show(User $usuario)
    {
        $usuario->load(['rol', 'sucursal', 'desactivadoPor']);
        $operador = $this->operador();

        if ($operador->esDistribuidor() && !$usuario->activo) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: Tu rol de Distribuidor solo puede visualizar usuarios activos.');
        }

        return view('usuarios.show', compact('usuario', 'operador'));
    }

    /**
     * Formulario de edición. No permitido para Distribuidor ni Administrador.
     */
    public function edit(User $usuario)
    {
        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede modificar usuarios.');
        }

        if ($operador->esDistribuidor()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: Tu rol de Distribuidor solo tiene permisos de lectura para usuarios activos.');
        }

        if (!$usuario->activo) {
            return redirect()->route('usuarios.index')
                ->with('info', "El usuario '{$usuario->name}' está desactivado y no puede ser modificado ni reactivado.");
        }

        $usuario->load(['rol', 'sucursal']);
        $rolesPermitidos = $operador->rolesPermitidos();
        $sucursalesPermitidas = $operador->sucursalesPermitidas();

        return view('usuarios.edit', compact('usuario', 'operador', 'rolesPermitidos', 'sucursalesPermitidas'));
    }

    /**
     * Actualizar datos de un usuario (aquí sí se puede cambiar la categoría del distribuidor).
     */
    public function update(UpdateUserRequest $request, User $usuario)
    {
        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede modificar usuarios.');
        }

        if ($operador->esDistribuidor()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: Tu rol de Distribuidor solo tiene permisos de lectura para usuarios activos.');
        }

        if (!$usuario->activo) {
            return redirect()->route('usuarios.index')
                ->with('info', "El usuario '{$usuario->name}' está desactivado y no puede ser modificado ni reactivado.");
        }

        $data = $request->validated();

        // Validación de permisos de rol
        $rolesPermitidosIds = $operador->rolesPermitidos()->pluck('id')->toArray();
        if (!in_array($data['rol_id'], $rolesPermitidosIds)) {
            return back()->withErrors(['rol_id' => 'No tienes permiso para asignar este rol.'])->withInput();
        }

        // Validación de permisos de sucursal
        $sucursalesPermitidasIds = $operador->sucursalesPermitidas()->pluck('id')->toArray();
        if (!in_array($data['sucursal_id'], $sucursalesPermitidasIds)) {
            return back()->withErrors(['sucursal_id' => 'No tienes permiso para asignar usuarios a esta sucursal.'])->withInput();
        }

        // Ajuste de categoría en la edición
        $rolSeleccionado = Rol::find($data['rol_id']);
        if ($rolSeleccionado && in_array(strtolower($rolSeleccionado->nombre), ['distribuidor', 'distribuidora'])) {
            $data['categoria_distribuidor'] = $data['categoria_distribuidor'] ?? $usuario->categoria_distribuidor ?? 'cobre';
        } else {
            $data['categoria_distribuidor'] = null;
        }

        // Si no se envía contraseña, no se modifica
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $usuario->update($data);

        return redirect()->route('usuarios.index')
            ->with('success', "El usuario '{$usuario->name}' fue actualizado correctamente.");
    }

    /**
     * Desactivar un usuario (sin eliminar registros de la BD). No permitido para Distribuidor ni Administrador.
     */
    public function destroy(User $usuario)
    {
        $operador = $this->operador();

        if ($operador->esAdministrador()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: El rol de Administrador cuenta con permisos de solo lectura (auditoría) y no puede desactivar usuarios.');
        }

        if ($operador->esDistribuidor()) {
            return redirect()->route('usuarios.index')
                ->with('error', 'Acceso denegado: Tu rol de Distribuidor solo tiene permisos de lectura para usuarios activos.');
        }

        if (!$usuario->activo) {
            return redirect()->route('usuarios.index')
                ->with('info', "El usuario '{$usuario->name}' ya se encuentra desactivado.");
        }

        $usuario->update([
            'activo' => false,
            'desactivado_at' => now(),
            'desactivado_by_user_id' => Auth::id() ?? $operador->id,
        ]);

        return redirect()->route('usuarios.index')
            ->with('success', "El usuario '{$usuario->name}' fue desactivado correctamente el " . now()->format('d/m/Y H:i') . ".");
    }
}
