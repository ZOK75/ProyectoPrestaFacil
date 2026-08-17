@extends('layouts.app')

@section('title', 'Panel de Gerencia General - PrestaFácil')

@section('content')
<div class="space-y-8">

    <!-- Header Gerente General / Auditor -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    @if(Auth::user()->esAdministrador())
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-500/20 text-amber-300 border border-amber-500/30 uppercase tracking-wider">
                            🔒 Auditoría y Supervisión
                        </span>
                    @else
                        <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                            Gerencia General
                        </span>
                    @endif
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700">
                        Corporativo Nacional
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2">
                    {{ Auth::user()->esAdministrador() ? 'Panel de Auditoría del Sistema' : 'Bienvenido, ' . $operador->name }}
                </h1>
                <p class="text-slate-400 text-sm mt-1">Supervisión integral de la red de sucursales, usuarios, reglas financieras y políticas del sistema.</p>
            </div>

            <div class="flex items-center gap-3 flex-wrap">
                <a href="{{ route('configuracion-general.edit') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Reglas y Configuración
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

                @if(Auth::user()->esAdministrador())
                    <a href="{{ route('logs.index') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-semibold transition">
                        <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Visor de Logs
                    </a>
                @endif
            </div>
        </div>
    </div>

    <!-- KPIs Corporativos -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sucursales Activas</span>
            <div class="text-2xl font-black text-white mt-2">{{ number_format($statsCorporativas['total_sucursales']) }}</div>
            <p class="text-xs text-indigo-400 mt-1">Red en operación</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-emerald-400 uppercase tracking-wider">Total Personal</span>
            <div class="text-2xl font-black text-emerald-300 mt-2">{{ number_format($statsCorporativas['total_usuarios']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Usuarios corporativos activos</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-amber-400 uppercase tracking-wider">Red de Distribución</span>
            <div class="text-2xl font-black text-amber-300 mt-2">{{ number_format($statsCorporativas['distribuidores']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Distribuidores activos</p>
        </div>
        <div class="bg-slate-900 border border-slate-800 rounded-2xl p-5 shadow-xl">
            <span class="text-xs font-semibold text-violet-400 uppercase tracking-wider">Productos de Vales</span>
            <div class="text-2xl font-black text-violet-300 mt-2">{{ number_format($statsCorporativas['vales_catalogo']) }}</div>
            <p class="text-xs text-slate-400 mt-1">Tipos de vales en catálogo</p>
        </div>
    </div>

    <!-- Reglas de Corte y Configuración Activa -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 shadow-xl space-y-4">
        <div class="flex items-center justify-between border-b border-slate-800 pb-3">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-xl bg-indigo-600/20 text-indigo-400 flex items-center justify-center font-bold text-xs">
                    ⚙️
                </div>
                <div>
                    <h2 class="text-base font-bold text-white">Reglas del Sistema y Políticas Financieras Vigentes</h2>
                    <p class="text-xs text-slate-400">Parámetros de corte, vencimientos, multas y comisiones automáticas</p>
                </div>
            </div>
            @if(!Auth::user()->esAdministrador())
                <a href="{{ route('configuracion-general.edit') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition">
                    Modificar Configuración &rarr;
                </a>
            @endif
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Día y Hora de Corte</span>
                <span class="text-sm font-extrabold text-white">Día {{ $configuracion->dia_corte }} @ {{ $configuracion->hora_corte ?? '20:00' }}</span>
                <span class="text-[10px] text-slate-500 block">Cierre de quincena</span>
            </div>

            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Fecha Límite de Pago</span>
                <span class="text-sm font-extrabold text-amber-400">Día {{ $configuracion->dia_limite_pago }} @ {{ $configuracion->hora_limite_pago ?? '20:00' }}</span>
                <span class="text-[10px] text-slate-500 block">Límite para liquidar sin multa</span>
            </div>

            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Multa por Adeudo Vencido</span>
                <span class="text-sm font-extrabold text-rose-400">${{ number_format($configuracion->multa_adeudo, 2) }}</span>
                <span class="text-[10px] text-slate-500 block">Por distribuidora incumplida</span>
            </div>

            <div class="bg-slate-950/70 border border-slate-800/80 rounded-2xl p-4 space-y-1">
                <span class="text-slate-400 block font-medium">Programa de Puntos</span>
                <span class="text-sm font-extrabold text-emerald-400">${{ number_format($configuracion->monto_base_puntos ?? 1200, 0) }} &rarr; {{ $configuracion->puntos_por_monto_base ?? 3 }} pts</span>
                <span class="text-[10px] text-slate-500 block">Valor: ${{ number_format($configuracion->valor_punto ?? 2.00, 2) }}/punto</span>
            </div>
        </div>
    </div>

    <!-- Directorio de Sucursales -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Red de Sucursales y Desglose de Personal
            </h2>
            <a href="{{ route('usuarios.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 transition">
                Ver todos los usuarios &rarr;
            </a>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($sucursales as $sucursal)
                <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-md">
                    <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-black text-sm">
                                {{ strtoupper(substr($sucursal->nombre, 0, 2)) }}
                            </div>
                            <div>
                                <h3 class="font-extrabold text-white text-sm">{{ $sucursal->nombre }}</h3>
                                <span class="text-[11px] text-slate-400 block">{{ $sucursal->ciudad ?? 'Sede Regional' }}</span>
                            </div>
                        </div>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                            Activa
                        </span>
                    </div>

                    <div class="space-y-2 text-xs">
                        <div class="flex justify-between items-center text-slate-400">
                            <span>Gerente a Cargo:</span>
                            <span class="font-semibold text-slate-200">
                                {{ $sucursal->usuarios->firstWhere('rol.nombre', 'Gerente de Sucursal')?->name ?? 'Sin asignar' }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-slate-400">
                            <span>Distribuidores:</span>
                            <span class="font-bold text-amber-400">
                                {{ $sucursal->usuarios->filter(fn($u) => $u->esDistribuidor())->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-slate-400">
                            <span>Cajeros:</span>
                            <span class="font-bold text-emerald-400">
                                {{ $sucursal->usuarios->filter(fn($u) => $u->esCajero())->count() }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-slate-400 pt-2 border-t border-slate-800">
                            <span class="font-medium text-slate-300">Total Colaboradores:</span>
                            <span class="font-black text-white text-sm">{{ $sucursal->usuarios->count() }}</span>
                        </div>
                    </div>

                    <div class="pt-2">
                        <a href="{{ route('usuarios.index', ['sucursal_id' => $sucursal->id]) }}" class="w-full py-2 rounded-xl bg-slate-900 hover:bg-slate-800 border border-slate-700 text-xs font-bold text-indigo-300 text-center transition block">
                            Ver Personal de Sucursal
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

</div>
@endsection
