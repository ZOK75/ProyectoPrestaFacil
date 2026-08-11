@extends('layouts.app')

@section('title', 'Panel de Gerente de Sucursal - PrestaFácil')

@section('content')
<div class="space-y-8">

    <!-- Header Gerente de Sucursal -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                        Gerente de Sucursal
                    </span>
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        {{ $operador->sucursal?->nombre ?? 'Sucursal sin asignar' }}
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Bienvenido, {{ $operador->name }}
                </h1>
                <p class="text-slate-400 text-sm mt-1">Supervisión y gestión del personal operativo y distribuidores de tu sucursal.</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('usuarios.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Registrar Usuario
                </a>
                <a href="{{ route('usuarios.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition">
                    <svg class="w-4 h-4 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Gestión de Usuarios
                </a>
                <a href="{{ route('producto-vales.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition">
                    <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                    </svg>
                    Catálogo de Vales
                </a>
            </div>
        </div>
    </div>

    <!-- KPIs del Personal de la Sucursal -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Personal Asignado</span>
            <div class="text-2xl font-black text-white mt-2">{{ number_format($statsEquipo['total_personal']) }}</div>
            <p class="text-xs text-indigo-400 mt-1">Colaboradores en sucursal</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Cuentas Activas</span>
            <div class="text-2xl font-black text-emerald-300 mt-2">{{ number_format($statsEquipo['activos']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Con acceso al sistema</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Distribuidores</span>
            <div class="text-2xl font-black text-amber-300 mt-2">{{ number_format($statsEquipo['distribuidores']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Colocación y red de vales</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-violet-400 uppercase tracking-wider">Cajeros y Operativos</span>
            <div class="text-2xl font-black text-violet-300 mt-2">{{ number_format($statsEquipo['cajeros'] + $statsEquipo['otros']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Ventanilla y verificación</p>
        </div>
    </div>

    <!-- Sección: Equipo y Personal de tu Sucursal -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Personal y Distribuidores de {{ $operador->sucursal?->nombre ?? 'tu Sucursal' }}
                </h2>
                <p class="text-slate-400 text-xs mt-0.5">Control de cuentas y perfiles de los usuarios que operan en esta sucursal.</p>
            </div>
            <a href="{{ route('usuarios.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 transition">
                Administrar usuarios &rarr;
            </a>
        </div>

        @if($personalSucursal->isEmpty())
            <div class="p-12 text-center text-slate-500 text-sm">
                No hay usuarios asignados a esta sucursal aún.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-300">
                    <thead class="bg-slate-950/60 text-xs uppercase tracking-wider text-slate-400 border-b border-slate-800">
                        <tr>
                            <th class="px-6 py-3.5">Usuario / Nombre</th>
                            <th class="px-6 py-3.5">Correo Electrónico</th>
                            <th class="px-6 py-3.5">Rol</th>
                            <th class="px-6 py-3.5">Estado</th>
                            <th class="px-6 py-3.5 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-800/60">
                        @foreach($personalSucursal as $usuario)
                            <tr class="hover:bg-slate-800/40 transition">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-9 h-9 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-bold text-xs">
                                            {{ strtoupper(substr($usuario->name, 0, 2)) }}
                                        </div>
                                        <div>
                                            <div class="font-semibold text-white">{{ $usuario->name }}</div>
                                            @if($usuario->esDistribuidor() && $usuario->categoria_distribuidor)
                                                <span class="text-[10px] text-amber-400 font-bold uppercase">Categoría {{ $usuario->categoria_distribuidor }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 font-mono text-xs text-slate-400">
                                    {{ $usuario->email }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold
                                        @if($usuario->esGerenteSucursal()) bg-indigo-500/10 text-indigo-400 border border-indigo-500/20
                                        @elseif($usuario->esDistribuidor()) bg-amber-500/10 text-amber-400 border border-amber-500/20
                                        @elseif($usuario->esCajero()) bg-emerald-500/10 text-emerald-400 border border-emerald-500/20
                                        @else bg-slate-800 text-slate-300 border border-slate-700 @endif">
                                        {{ $usuario->rol?->nombre ?? 'Sin Rol' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($usuario->activo)
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Activo
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Inactivo
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="{{ route('usuarios.show', $usuario) }}" class="px-3 py-1.5 rounded-lg bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-indigo-300 border border-slate-700 transition">
                                            Ver Perfil
                                        </a>
                                        <a href="{{ route('usuarios.edit', $usuario) }}" class="px-3 py-1.5 rounded-lg bg-indigo-600/20 hover:bg-indigo-600/30 text-indigo-300 border border-indigo-500/30 text-xs font-semibold transition">
                                            Editar
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

</div>
@endsection