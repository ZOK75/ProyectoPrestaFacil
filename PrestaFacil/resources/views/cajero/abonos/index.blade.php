@extends('layouts.app')

@section('title', 'Recepción de Abonos - Cajero')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8" x-data="{ pagoModalOpen: false, prestamoSeleccionado: null }">

    <div class="flex items-center justify-between">
        <a href="{{ route('cajero.dashboard') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver al Panel
        </a>
    </div>

    <!-- Buscador -->
    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
        <form action="{{ route('cajero.abonos.index') }}" method="GET">
            <label class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 block">Buscar Préstamo Activo</label>
            <div class="relative">
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Referencia o Nombre del Cliente..."
                    class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 pl-4 pr-12 text-white font-mono text-sm focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 transition-all">
                <button type="submit" class="absolute right-2 top-1.5 bottom-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg px-3 flex items-center justify-center transition-colors">
                    Buscar
                </button>
            </div>
        </form>
    </div>

    <!-- Resultados -->
    @if(request()->filled('buscar') && $prestamos->isEmpty())
        <div class="p-4 bg-slate-900 border border-slate-800 rounded-2xl text-center text-slate-400 text-sm">
            No se encontraron préstamos activos con ese criterio.
        </div>
    @endif

    <div class="space-y-3">
        @foreach($prestamos as $prestamo)
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-4 shadow-xl">
                <div class="flex justify-between items-start border-b border-slate-800 pb-2 mb-2">
                    <div>
                        <div class="font-mono text-[10px] font-black text-emerald-400 mb-1">{{ $prestamo->referencia }}</div>
                        <h3 class="text-sm font-black text-white">{{ $prestamo->cliente->nombre }}</h3>
                    </div>
                    @if($prestamo->esPrevale())
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-amber-500/20 text-amber-300 uppercase border border-amber-500/30">Prevale</span>
                    @else
                        <span class="px-1.5 py-0.5 rounded text-[9px] font-black bg-cyan-500/20 text-cyan-300 uppercase border border-cyan-500/30">Vale Digital</span>
                    @endif
                </div>
                
                <div class="flex justify-between items-center text-xs">
                    <div>
                        <span class="text-slate-500 block">Cuota Qna:</span>
                        <span class="font-bold text-white">${{ number_format($prestamo->cuota_quincenal, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-slate-500 block text-right">Adeudo:</span>
                        <span class="font-bold text-rose-400">${{ number_format($prestamo->adeudo_pendiente, 2) }}</span>
                    </div>
                </div>

                <div class="mt-3 pt-3 border-t border-slate-800 flex justify-end gap-2">
                    <button type="button" 
                            @click="prestamoSeleccionado = { id: {{ $prestamo->id }}, ref: '{{ $prestamo->referencia }}', cliente: '{{ addslashes($prestamo->cliente->nombre) }}', cuota: {{ $prestamo->cuota_quincenal }}, adeudo: {{ $prestamo->adeudo_pendiente }} }; pagoModalOpen = true"
                            class="bg-emerald-600 hover:bg-emerald-500 text-white text-xs font-bold px-4 py-2 rounded-lg transition-colors shadow-lg shadow-emerald-500/20">
                        Registrar Abono
                    </button>
                </div>
            </div>
        @endforeach

        <div class="mt-4">
            {{ $prestamos->links() }}
        </div>
    </div>

    <!-- Modal Registro de Pago -->
    <div x-show="pagoModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-sm" style="display: none;">
        <div class="bg-slate-900 border border-emerald-900/50 rounded-2xl w-full max-w-sm shadow-2xl overflow-hidden" @click.away="pagoModalOpen = false">
            
            <div class="bg-emerald-600 p-4">
                <h3 class="text-white font-black text-lg">Registrar Abono</h3>
                <p class="text-emerald-100 text-[10px] font-mono mt-1" x-text="prestamoSeleccionado?.ref"></p>
            </div>

            <form :action="'{{ url('cajero/abonos') }}/' + prestamoSeleccionado?.id" method="POST" class="p-4 space-y-4">
                @csrf
                
                <div class="bg-slate-950 rounded-xl p-3 text-center border border-slate-800">
                    <div class="text-[10px] text-slate-400 uppercase font-bold mb-1" x-text="prestamoSeleccionado?.cliente"></div>
                    <div class="flex justify-between text-xs mt-2">
                        <div class="text-left">
                            <span class="text-slate-500 block">Sugerido:</span>
                            <span class="text-white font-bold" x-text="'$' + Number(prestamoSeleccionado?.cuota).toFixed(2)"></span>
                        </div>
                        <div class="text-right">
                            <span class="text-slate-500 block">Adeudo Total:</span>
                            <span class="text-rose-400 font-bold" x-text="'$' + Number(prestamoSeleccionado?.adeudo).toFixed(2)"></span>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Monto a Abonar</label>
                    <input type="number" name="monto_abonado" step="0.01" min="0.01" required
                        class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 px-3 text-emerald-400 font-mono text-xl font-black mt-1 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500 text-center">
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Método de Pago</label>
                    <select name="metodo_pago" required class="w-full bg-slate-950 border border-slate-700 rounded-xl py-3 px-3 text-white text-sm mt-1 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia / SPEI</option>
                        <option value="tarjeta">Tarjeta de Débito/Crédito</option>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase">Observaciones (Opcional)</label>
                    <textarea name="observaciones" rows="2" class="w-full bg-slate-950 border border-slate-700 rounded-xl py-2 px-3 text-white text-xs mt-1 focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" @click="pagoModalOpen = false" class="flex-1 py-3 bg-slate-800 hover:bg-slate-700 text-white font-bold text-sm rounded-xl transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-1 py-3 bg-emerald-600 hover:bg-emerald-500 text-white font-black text-sm rounded-xl transition-colors shadow-lg shadow-emerald-500/20">
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
