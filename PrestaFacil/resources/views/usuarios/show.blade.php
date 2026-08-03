@extends('layouts.app')

@section('title', 'Detalle de ' . $usuario->name . ' - PrestaFácil')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <a href="{{ route('usuarios.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1 mb-2">
                &larr; Volver a usuarios
            </a>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-extrabold text-white">{{ $usuario->name }}</h1>
                @if($usuario->activo)
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                        Activo
                    </span>
                @else
                    <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                        Desactivado
                    </span>
                @endif
            </div>
        </div>

        @if($usuario->activo)
            <div class="flex items-center gap-2">
                <a href="{{ route('usuarios.edit', $usuario) }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold text-sm shadow-md transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    Editar
                </a>

                <form action="{{ route('usuarios.destroy', $usuario) }}" method="POST" class="inline-block" onsubmit="return confirm('¿Confirmas que deseas desactivar al usuario {{ $usuario->name }}? Esta acción no se puede deshacer y registrará la marca de tiempo actual.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-semibold text-sm shadow-md transition flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                        </svg>
                        Desactivar Usuario
                    </button>
                </form>
            </div>
        @endif
    </div>

    <!-- Banner si está desactivado -->
    @if(!$usuario->activo)
        <div class="p-4 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-500/20 flex items-center justify-center font-bold">!</div>
                <div>
                    <h4 class="font-bold text-sm">Este usuario ha sido desactivado permanentemente</h4>
                    <p class="text-xs text-rose-300/80 mt-0.5">
                        Desactivado el {{ $usuario->desactivado_at ? $usuario->desactivado_at->format('d/m/Y H:i:s') : 'N/A' }}
                        @if($usuario->desactivadoPor)
                            por <strong class="text-rose-200">{{ $usuario->desactivadoPor->name }}</strong>
                        @endif
                        — No es posible modificar ni reactivar esta cuenta.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Ficha del Usuario -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <div class="flex items-center gap-5 mb-6 pb-6 border-b border-slate-800">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-white text-2xl font-extrabold shadow-lg shadow-indigo-500/30">
                {{ strtoupper(substr($usuario->name, 0, 1)) }}
            </div>
            <div>
                <h2 class="text-xl font-bold text-white">{{ $usuario->name }}</h2>
                <p class="text-sm text-slate-400">{{ $usuario->email }}</p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
            <div class="space-y-4">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Rol Asignado</span>
                    @if($usuario->rol)
                        @php
                            $colorMap = [
                                'Gerente General' => 'bg-violet-500/10 text-violet-400 border-violet-500/20',
                                'Gerente de Sucursal' => 'bg-blue-500/10 text-blue-400 border-blue-500/20',
                                'Asesor de Crédito' => 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20',
                                'Cajero' => 'bg-amber-500/10 text-amber-400 border-amber-500/20',
                                'Cobrador' => 'bg-cyan-500/10 text-cyan-400 border-cyan-500/20',
                            ];
                            $cls = $colorMap[$usuario->rol->nombre] ?? 'bg-slate-800 text-slate-300 border-slate-700';
                        @endphp
                        <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-semibold border mt-1 {{ $cls }}">
                            {{ $usuario->rol->nombre }}
                        </span>
                    @else
                        <span class="text-sm text-slate-500 italic mt-1 block">Sin rol asignado</span>
                    @endif
                </div>

                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Sucursal</span>
                    <span class="text-sm text-white font-semibold mt-1 block">
                        {{ $usuario->sucursal?->nombre ?? 'Corporativo (sin sucursal)' }}
                    </span>
                    @if($usuario->sucursal?->direccion)
                        <span class="text-xs text-slate-500 block">{{ $usuario->sucursal->direccion }}</span>
                    @endif
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Correo Electrónico</span>
                    <span class="text-sm text-white font-mono mt-1 block">{{ $usuario->email }}</span>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider block">Fecha de Registro</span>
                    <span class="text-sm text-white font-mono mt-1 block">{{ $usuario->created_at?->format('d/m/Y H:i') ?? '—' }}</span>
                </div>
                @if(!$usuario->activo)
                    <div>
                        <span class="text-xs font-semibold text-rose-400 uppercase tracking-wider block">Fecha de Desactivación</span>
                        <span class="text-sm text-rose-300 font-mono mt-1 block">{{ $usuario->desactivado_at ? $usuario->desactivado_at->format('d/m/Y H:i:s') : '—' }}</span>
                        @if($usuario->desactivadoPor)
                            <span class="text-xs text-slate-400 block">Desactivado por: {{ $usuario->desactivadoPor->name }}</span>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
