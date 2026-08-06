@extends('layouts.app')

@section('title', 'Asignar Vale / Prevale - PrestaFácil Móvil')

@section('content')
<div class="max-w-md mx-auto space-y-4">

    <!-- Encabezado Móvil -->
    <div class="flex items-center justify-between">
        <a href="{{ route('prestamos.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver a préstamos
        </a>
        <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-widest">Asignación Móvil</span>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <div class="mb-4 pb-3 border-b border-slate-800">
            <h1 class="text-lg font-black text-white">Asignar Vale a Cliente</h1>
            <p class="text-xs text-slate-400">Selecciona el cliente y el vale activo a otorgar.</p>
        </div>

        <form action="{{ route('prestamos.store') }}" method="POST" class="space-y-4">
            @csrf

            <!-- 1. Selección de Cliente -->
            <div>
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                    1. Seleccionar Cliente <span class="text-rose-400">*</span>
                </label>
                <select name="cliente_id" id="cliente_select" required onchange="window.location.href='{{ route('prestamos.create') }}?cliente_id=' + this.value"
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 @error('cliente_id') border-rose-500 @enderror">
                    <option value="">Selecciona un cliente activo...</option>
                    @foreach($clientes as $cli)
                        <option value="{{ $cli->id }}" {{ (old('cliente_id', $clienteSeleccionado?->id) == $cli->id) ? 'selected' : '' }}>
                            {{ $cli->nombre }} (CURP: {{ $cli->curp }})
                        </option>
                    @endforeach
                </select>
                @error('cliente_id')
                    <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Banner Informativo Tipo PREVALE / VALE -->
            @if($clienteSeleccionado)
                @if($tipoAsignacion === 'prevale')
                    <div class="p-3 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-400 text-xs space-y-0.5">
                        <div class="font-extrabold uppercase text-[10px] tracking-wider">¡Primera Asignación del Cliente!</div>
                        <p class="text-[11px] text-amber-200">
                            {{ $clienteSeleccionado->nombre }} no tiene historial previo. Este préstamo se registrará como <strong>PREVALE</strong> y generará una Referencia única.
                        </p>
                    </div>
                @else
                    <div class="p-3 rounded-xl bg-indigo-500/10 border border-indigo-500/30 text-indigo-400 text-xs space-y-0.5">
                        <div class="font-extrabold uppercase text-[10px] tracking-wider">Historial Existente</div>
                        <p class="text-[11px] text-indigo-200">
                            {{ $clienteSeleccionado->nombre }} ya cuenta con créditos previos. Esta asignación se registrará como <strong>VALE</strong>.
                        </p>
                    </div>
                @endif
            @endif

            <!-- 2. Selección de Vale (Solo Vales Activos) -->
            <div>
                <label class="block text-[11px] font-bold text-slate-300 uppercase tracking-wider mb-1.5">
                    2. Seleccionar Vale Activo <span class="text-rose-400">*</span>
                </label>
                <select name="producto_vale_id" required
                    class="w-full px-3.5 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-xs text-white focus:outline-none focus:border-indigo-500 @error('producto_vale_id') border-rose-500 @enderror">
                    <option value="">Selecciona un producto de vale activo...</option>
                    @foreach($valesActivos as $vale)
                        <option value="{{ $vale->id }}" {{ old('producto_vale_id') == $vale->id ? 'selected' : '' }}>
                            {{ $vale->clave }} — {{ $vale->nombre }} (${{ number_format($vale->monto_prestamo, 0) }} / ${{ number_format($vale->cuota_quincenal, 2) }} 15na)
                        </option>
                    @endforeach
                </select>
                @error('producto_vale_id')
                    <p class="text-[10px] text-rose-400 mt-1">{{ $message }}</p>
                @enderror
                <span class="text-[10px] text-slate-500 mt-1 block">Solo se muestran los vales actualmente vigentes y activos.</span>
            </div>

            <!-- Botones Móviles -->
            <div class="flex items-center gap-2 pt-3 border-t border-slate-800">
                <a href="{{ route('prestamos.index') }}" class="w-1/3 py-2.5 rounded-xl bg-slate-800 text-slate-300 text-xs font-semibold text-center transition">
                    Cancelar
                </a>
                <button type="submit" class="w-2/3 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold shadow-lg shadow-indigo-600/30 transition flex items-center justify-center gap-1">
                    Generar Referencia
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
