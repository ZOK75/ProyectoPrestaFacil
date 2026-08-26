@extends('layouts.app')

@section('title', 'Gestión de Usuarios - PrestaFácil')

@section('content')
<div class="space-y-6">

    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white tracking-tight flex items-center gap-2">
                Gestión de Usuarios
                @if($operador->esAdministrador())
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-black uppercase bg-slate-800 text-indigo-400 border border-indigo-500/30">
                        <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        Modo Auditoría (Solo Lectura)
                    </span>
                @endif
            </h1>
            <p class="text-slate-400 text-sm mt-1">
                Consulta y supervisión de empleados y distribuidores.
                <span class="text-indigo-400 font-semibold">Operando como: {{ $operador->name }} ({{ $operador->rol?->nombre ?? 'Sin rol' }})</span>
            </p>
        </div>
        @if(!$operador->esDistribuidor())
            <div>
                <a href="{{ route('usuarios.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-lg shadow-indigo-600/30 transition-all hover:scale-[1.02]">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                    </svg>
                    Nuevo Usuario
                </a>
            </div>
        @endif
    </div>

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-between shadow-lg shadow-rose-950/20">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center">
                    <svg class="w-5 h-5 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <span class="text-sm font-medium">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-rose-400/60 hover:text-rose-400 text-lg leading-none">&times;</button>
        </div>
    @endif

    <!-- Estadísticas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Usuarios</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-500/10 flex items-center justify-center text-indigo-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-white mt-2">{{ $stats['total'] }}</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Usuarios Activos</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-500/10 flex items-center justify-center text-emerald-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-emerald-400 mt-2">{{ $stats['activos'] }}</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Desactivados</span>
                <div class="w-9 h-9 rounded-xl bg-rose-500/10 flex items-center justify-center text-rose-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-rose-400 mt-2">{{ $stats['inactivos'] }}</div>
        </div>

        <div class="bg-slate-900/60 border border-slate-800 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Con Rol Asignado</span>
                <div class="w-9 h-9 rounded-xl bg-blue-500/10 flex items-center justify-center text-blue-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </div>
            </div>
            <div class="text-2xl font-bold text-blue-400 mt-2">{{ $stats['con_rol'] }}</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-sm">
        <form novalidate action="{{ route('usuarios.index') }}" method="GET" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Buscar por nombre o email..."
                    class="w-full pl-10 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">
            </div>

            <select name="rol_id" class="w-full md:w-44 px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500">
                <option value="">Todos los roles</option>
                @foreach($roles as $rol)
                    <option value="{{ $rol->id }}" {{ request('rol_id') == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                @endforeach
            </select>

            @if($operador->esGerenteGeneral() || $operador->esAdministrador())
                <select name="sucursal_id" class="w-full md:w-44 px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Todas las sucursales</option>
                    @foreach($sucursales as $suc)
                        <option value="{{ $suc->id }}" {{ request('sucursal_id') == $suc->id ? 'selected' : '' }}>{{ $suc->nombre }}</option>
                    @endforeach
                </select>
            @endif

            @if(!$operador->esDistribuidor())
                <select name="estado" class="w-full md:w-44 px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500">
                    <option value="">Todos los estados</option>
                    <option value="activo" {{ request('estado') === 'activo' ? 'selected' : '' }}>Solo Activos</option>
                    <option value="inactivo" {{ request('estado') === 'inactivo' ? 'selected' : '' }}>Solo Desactivados</option>
                </select>
            @endif

            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-white text-sm font-semibold transition">Filtrar</button>
                @if(request()->hasAny(['buscar', 'rol_id', 'sucursal_id', 'estado']))
                    <a href="{{ route('usuarios.index') }}" class="px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-400 hover:text-white text-sm font-medium flex items-center justify-center">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl overflow-hidden shadow-xl">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-950/80 text-xs font-semibold text-slate-400 uppercase tracking-wider border-b border-slate-800">
                    <tr>
                        <th class="px-6 py-4">Usuario</th>
                        <th class="px-6 py-4">Rol / Categoría</th>
                        <th class="px-6 py-4">Sucursal</th>
                        <th class="px-6 py-4">Estado / Desactivación</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    @forelse($usuarios as $usuario)
                        <tr class="hover:bg-slate-800/40 transition">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-white text-sm font-bold shadow">
                                        {{ strtoupper(substr($usuario->name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-bold text-white">{{ $usuario->name }}</div>
                                        <div class="text-xs text-slate-400">{{ $usuario->email }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if($usuario->rol)
                                    @php
                                        $colorMap = [
                                            'Gerente General' => 'bg-violet-500/10 text-violet-400 border-violet-500/20',
                                            'Gerente de Sucursal' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                            'Administrador' => 'bg-slate-500/10 text-slate-300 border-slate-500/20',
                                            'Asesor de Crédito' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                            'Cajero' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                            'Cobrador' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
                                            'Distribuidor' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                                            'Distribuidora' => 'bg-indigo-500/10 text-indigo-400 border-indigo-500/20',
                                        ];
                                        $cls = $colorMap[$usuario->rol->nombre] ?? 'bg-slate-800 text-slate-300 border-slate-700';
                                    @endphp
                                    <div class="space-y-1">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border {{ $cls }}">
                                            {{ $usuario->rol->nombre }}
                                        </span>
                                        @if($usuario->esDistribuidor())
                                            <div class="text-[11px] text-amber-400 font-bold uppercase tracking-wider">
                                                • {{ $usuario->categoria_distribuidor ?? 'cobre' }} ({{ number_format($usuario->obtenerPorcentajeGanancia(), 0) }}%)
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-slate-500 italic">Sin rol</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($usuario->sucursal)
                                    <span class="text-sm text-slate-200">{{ $usuario->sucursal->nombre }}</span>
                                @else
                                    <span class="text-xs text-slate-500 italic">Corporativo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if($usuario->activo)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span> Activo
                                    </span>
                                @else
                                    <div class="space-y-0.5">
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Desactivado
                                        </span>
                                        @if($usuario->desactivado_at)
                                            <span class="block text-[10px] text-slate-400 font-mono">
                                                {{ $usuario->desactivado_at->format('d/m/Y H:i') }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('usuarios.show', $usuario) }}" class="inline-flex p-2 rounded-lg bg-slate-800 text-slate-300 hover:text-white hover:bg-slate-700 transition" title="Ver Detalle">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>

                                @if($usuario->activo && !$operador->esDistribuidor() && !$operador->esAdministrador())
                                    <a href="{{ route('usuarios.edit', $usuario) }}" class="inline-flex p-2 rounded-lg bg-indigo-600/20 text-indigo-400 hover:bg-indigo-600 hover:text-white transition" title="Editar Usuario">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>

                                    <form novalidate action="{{ route('usuarios.destroy', $usuario) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Confirmas que deseas desactivar al usuario {{ $usuario->name }}? Esta acción no se puede deshacer y registrará la marca de tiempo actual.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg bg-rose-500/10 text-rose-400 hover:bg-rose-600 hover:text-white transition" title="Desactivar Usuario">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                <p class="font-medium text-slate-400">No se encontraron usuarios</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($usuarios->hasPages())
            <div class="px-6 py-4 bg-slate-950 border-t border-slate-800">
                {{ $usuarios->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
