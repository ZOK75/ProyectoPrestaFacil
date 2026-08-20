@extends('layouts.app')

@section('title', 'Gestión de Sucursales - PrestaFácil')

@section('content')
<div class="space-y-8" x-data="{ showCreateModal: false, showEditModal: false, showMoveModal: false, editSucursal: {}, moveGerente: {} }">

    <!-- Header Sucursales -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-indigo-950/40 to-slate-900 border border-slate-800 p-6 sm:p-8 shadow-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-500/20 text-indigo-300 border border-indigo-500/30 uppercase tracking-wider">
                        🏢 Administración Corporativa
                    </span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight mt-2">
                    Catálogo y Gestión de Sucursales
                </h1>
                <p class="text-slate-400 text-sm mt-1">Crea nuevas sedes, edita sus datos, desactiva ubicaciones y reasigna Gerentes en cascada.</p>
            </div>

            @if($operador->esGerenteGeneral())
                <div class="flex items-center gap-3 flex-wrap">
                    <button @click="showCreateModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nueva Sucursal
                    </button>

                    <button @click="showMoveModal = true" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-amber-600/20 hover:bg-amber-600/30 text-amber-300 border border-amber-500/30 text-xs font-bold transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                        </svg>
                        Reasignar Gerente (En Cascada)
                    </button>
                </div>
            @endif
        </div>
    </div>

    <!-- Directorio de Sucursales -->
    <div class="bg-slate-900 border border-slate-800 rounded-3xl shadow-xl overflow-hidden">
        <div class="p-6 border-b border-slate-800 flex items-center justify-between">
            <h2 class="text-lg font-bold text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                Sucursales Registradas ({{ $sucursales->count() }})
            </h2>
        </div>

        <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($sucursales as $sucursal)
                <div class="bg-slate-950/70 border border-slate-800 rounded-2xl p-5 space-y-4 shadow-md flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between border-b border-slate-800/80 pb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-indigo-600/30 border border-indigo-500/40 flex items-center justify-center text-indigo-300 font-black text-sm">
                                    {{ strtoupper(substr($sucursal->nombre, 0, 2)) }}
                                </div>
                                <div>
                                    <h3 class="font-extrabold text-white text-sm">{{ $sucursal->nombre }}</h3>
                                    <span class="text-[11px] text-slate-400 block">{{ $sucursal->direccion ?? 'Sin dirección registrada' }}</span>
                                </div>
                            </div>
                            @if($sucursal->activo)
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                                    Activa
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-rose-500/10 text-rose-400 border border-rose-500/20">
                                    Inactiva
                                </span>
                            @endif
                        </div>

                        <div class="space-y-2 text-xs mt-3">
                            <div class="flex justify-between items-center text-slate-400">
                                <span>Teléfono:</span>
                                <span class="font-mono text-slate-300">{{ $sucursal->telefono ?? 'N/A' }}</span>
                            </div>
                            <div class="flex justify-between items-center text-slate-400">
                                <span>Personal Activo:</span>
                                <span class="font-bold text-indigo-300">{{ $sucursal->usuarios_count }} usuarios</span>
                            </div>
                        </div>
                    </div>

                    @if($operador->esGerenteGeneral())
                        <div class="pt-3 border-t border-slate-800/80 flex items-center gap-2">
                            <button @click="editSucursal = { id: '{{ $sucursal->id }}', nombre: '{{ addslashes($sucursal->nombre) }}', direccion: '{{ addslashes($sucursal->direccion) }}', telefono: '{{ addslashes($sucursal->telefono) }}' }; showEditModal = true"
                                    class="flex-1 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-slate-200 text-center transition border border-slate-700">
                                Editar
                            </button>

                            <form action="{{ route('sucursales.toggle-status', $sucursal->id) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" 
                                        onclick="return confirm('¿Estás seguro de cambiar el estado de esta sucursal? No se eliminarán datos del sistema.')"
                                        class="px-3 py-2 rounded-xl text-xs font-bold transition {{ $sucursal->activo ? 'bg-rose-500/10 text-rose-400 border border-rose-500/20 hover:bg-rose-500/20' : 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 hover:bg-emerald-500/20' }}">
                                    {{ $sucursal->activo ? 'Desactivar' : 'Activar' }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Modal Crear Sucursal -->
    <div x-show="showCreateModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4" style="display: none;" x-transition>
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showCreateModal = false"></div>
        <div class="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 z-50 text-left space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white">Crear Nueva Sucursal</h3>
                <button @click="showCreateModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>
            <form action="{{ route('sucursales.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Nombre de la Sucursal *</label>
                    <input type="text" name="nombre" required placeholder="Ej: Sucursal Centro" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Dirección</label>
                    <input type="text" name="direccion" placeholder="Calle, Número, Colonia, Ciudad" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Teléfono</label>
                    <input type="text" name="telefono" placeholder="10 dígitos" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="showCreateModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">Guardar Sucursal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Editar Sucursal -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4" style="display: none;" x-transition>
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showEditModal = false"></div>
        <div class="relative w-full max-w-md bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 z-50 text-left space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white">Editar Sucursal</h3>
                <button @click="showEditModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>
            <form :action="`/sucursales/${editSucursal.id}`" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Nombre *</label>
                    <input type="text" name="nombre" x-model="editSucursal.nombre" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Dirección</label>
                    <input type="text" name="direccion" x-model="editSucursal.direccion" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Teléfono</label>
                    <input type="text" name="telefono" x-model="editSucursal.telefono" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                </div>
                <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold">Actualizar</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Reasignar Gerente de Sucursal en Cascada -->
    <div x-show="showMoveModal" class="fixed inset-0 z-50 flex items-center justify-center overflow-y-auto p-4" style="display: none;" x-transition>
        <div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm" @click="showMoveModal = false"></div>
        <div class="relative w-full max-w-lg bg-slate-900 border border-slate-800 rounded-2xl shadow-2xl p-6 z-50 text-left space-y-4">
            <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                <h3 class="text-base font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                    </svg>
                    Reasignar Gerente de Sucursal (Propagación en Cascada)
                </h3>
                <button @click="showMoveModal = false" class="text-slate-400 hover:text-white">&times;</button>
            </div>

            <div class="p-3 bg-amber-500/10 border border-amber-500/20 rounded-xl text-xs text-amber-300 space-y-1">
                <strong class="block font-bold">⚠️ Atención: Propagación en Cascada</strong>
                <span>Al mover un Gerente a otra sucursal, <strong>todos sus Coordinadores</strong> y <strong>sus Distribuidoras asociadas</strong> se transferirán automáticamente a la nueva sucursal.</span>
            </div>

            <form action="{{ route('gerente-general.reasignar-gerente') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Seleccionar Gerente de Sucursal *</label>
                    <select name="gerente_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        <option value="">-- Selecciona un Gerente --</option>
                        @foreach($gerentesSucursal as $g)
                            <option value="{{ $g->id }}">{{ $g->name }} (Sucursal Actual: {{ $g->sucursal?->nombre ?? 'Sin Asignar' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Nueva Sucursal Destino *</label>
                    <select name="nueva_sucursal_id" required class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none">
                        <option value="">-- Selecciona la nueva sucursal --</option>
                        @foreach($sucursales->where('activo', true) as $suc)
                            <option value="{{ $suc->id }}">{{ $suc->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="flex justify-end gap-2 pt-3 border-t border-slate-800">
                    <button type="button" @click="showMoveModal = false" class="px-4 py-2 rounded-xl bg-slate-800 text-slate-300 text-xs font-bold">Cancelar</button>
                    <button type="submit" onclick="return confirm('¿Confirmas el traslado corporativo de este Gerente y toda su estructura?')" class="px-4 py-2 rounded-xl bg-amber-600 hover:bg-amber-500 text-white text-xs font-bold">Ejecutar Reasignación en Cascada</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
