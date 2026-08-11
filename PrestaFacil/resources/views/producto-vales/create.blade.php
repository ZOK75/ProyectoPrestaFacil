@extends('layouts.app')

@section('title', 'Nuevo Vale de Préstamo - PrestaFácil')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">

    <!-- Encabezado -->
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('producto-vales.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1 mb-2">
                &larr; Volver al catálogo de vales
            </a>
            <h1 class="text-2xl font-extrabold text-white">Registrar Nuevo Vale de Préstamo</h1>
            <p class="text-sm text-slate-400">Define las condiciones y límites financieros del vale.</p>
        </div>
    </div>

    <!-- Formulario + Precalculadora -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Formulario -->
        <div class="lg:col-span-2 bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
            <form action="{{ route('producto-vales.store') }}" method="POST" class="space-y-5" id="formProductoVale" novalidate>
                @csrf

                <!-- Clave y Nombre -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Clave del Producto <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" 
                               name="clave" 
                               id="clave" 
                               value="{{ old('clave') }}" 
                               placeholder="Ej. VLT-5K-12Q" 
                               required 
                               maxlength="50"
                               autocomplete="off"
                               oninput="this.value = this.value.toUpperCase().replace(/[^A-Z0-9\-_]/g, '')"
                               class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 font-mono focus:outline-none focus:border-indigo-500 @error('clave') border-rose-500 @enderror">
                        <span class="text-[10px] text-slate-500 mt-1 block">Solo letras mayúsculas, números y guiones.</span>
                        @error('clave')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Nombre Comercial <span class="text-rose-400">*</span>
                        </label>
                        <input type="text" 
                               name="nombre" 
                               id="nombre" 
                               value="{{ old('nombre') }}" 
                               placeholder="Ej. Vale Nómina Estándar 12Q" 
                               required 
                               minlength="3"
                               maxlength="255"
                               class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('nombre') border-rose-500 @enderror">
                        @error('nombre')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Monto Préstamo, Seguro y Comisión de Apertura -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Monto Préstamo ($) <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 text-slate-500 text-sm font-semibold">$</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="100" 
                                   max="1000000" 
                                   id="monto_prestamo" 
                                   name="monto_prestamo" 
                                   value="{{ old('monto_prestamo', 5000) }}" 
                                   required
                                   onkeydown="if(['e','E','+','-'].includes(event.key)) event.preventDefault();"
                                   oninput="if(parseFloat(this.value) > 1000000) this.value = 1000000; if(parseFloat(this.value) < 0) this.value = 0;"
                                   class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white font-semibold focus:outline-none focus:border-indigo-500 @error('monto_prestamo') border-rose-500 @enderror">
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Mínimo: $100.00 MXN</span>
                        @error('monto_prestamo')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Costo Seguro ($) <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-2.5 text-slate-500 text-sm font-semibold">$</span>
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   max="100000" 
                                   id="costo_seguro" 
                                   name="costo_seguro" 
                                   value="{{ old('costo_seguro', 200) }}" 
                                   required
                                   onkeydown="if(['e','E','+','-'].includes(event.key)) event.preventDefault();"
                                   oninput="if(parseFloat(this.value) > 100000) this.value = 100000; if(parseFloat(this.value) < 0) this.value = 0;"
                                   class="w-full pl-8 pr-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-amber-400 font-semibold focus:outline-none focus:border-indigo-500 @error('costo_seguro') border-rose-500 @enderror">
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Ingresa 0 si no incluye seguro.</span>
                        @error('costo_seguro')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Comisión Apertura (%) <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   max="100" 
                                   id="comision_apertura" 
                                   name="comision_apertura" 
                                   value="{{ old('comision_apertura', 0) }}" 
                                   required
                                   onkeydown="if(['e','E','+','-'].includes(event.key)) event.preventDefault();"
                                   oninput="if(parseFloat(this.value) > 100) this.value = 100; if(parseFloat(this.value) < 0) this.value = 0;"
                                   class="w-full pl-4 pr-8 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-cyan-400 font-semibold focus:outline-none focus:border-indigo-500 @error('comision_apertura') border-rose-500 @enderror">
                            <span class="absolute right-3.5 top-2.5 text-slate-500 text-sm font-semibold">%</span>
                        </div>
                        <span class="text-[10px] text-slate-500 mt-1 block">Porcentaje (0 - 100%).</span>
                        @error('comision_apertura')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Plazo Quincenas y Tasa de Interés -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Plazo en Quincenas <span class="text-rose-400">*</span>
                        </label>
                        <input type="number" 
                               id="plazo_quincenas" 
                               name="plazo_quincenas" 
                               value="{{ old('plazo_quincenas', 12) }}" 
                               min="1" 
                               max="120" 
                               step="1"
                               required
                               onkeydown="if(['e','E','+','-','.'].includes(event.key)) event.preventDefault();"
                               oninput="this.value = this.value.replace(/[^0-9]/g, ''); if(parseInt(this.value) > 120) this.value = 120;"
                               class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white font-semibold focus:outline-none focus:border-indigo-500 @error('plazo_quincenas') border-rose-500 @enderror">
                        <span class="text-[11px] text-slate-500 mt-1 block">Solo números enteros (1 a 120 quincenas).</span>
                        @error('plazo_quincenas')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                            Tasa Interés Quincenal (%) <span class="text-rose-400">*</span>
                        </label>
                        <div class="relative">
                            <input type="number" 
                                   step="0.01" 
                                   min="0" 
                                   max="100" 
                                   id="tasa_interes_quincenal" 
                                   name="tasa_interes_quincenal" 
                                   value="{{ old('tasa_interes_quincenal', 2.20) }}" 
                                   required
                                   onkeydown="if(['e','E','+','-'].includes(event.key)) event.preventDefault();"
                                   oninput="if(parseFloat(this.value) > 100) this.value = 100; if(parseFloat(this.value) < 0) this.value = 0;"
                                   class="w-full pl-4 pr-8 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white font-semibold focus:outline-none focus:border-indigo-500 @error('tasa_interes_quincenal') border-rose-500 @enderror">
                            <span class="absolute right-3.5 top-2.5 text-slate-500 text-sm font-semibold">%</span>
                        </div>
                        <span class="text-[11px] text-slate-500 mt-1 block">Tasa aplicada cada 15 días (0 - 100%).</span>
                        @error('tasa_interes_quincenal')
                            <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Descripción -->
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Descripción u Observaciones (Opcional)
                    </label>
                    <textarea name="descripcion" 
                              id="descripcion" 
                              rows="3" 
                              maxlength="1000" 
                              placeholder="Notas o condiciones adicionales del vale..."
                              class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500">{{ old('descripcion') }}</textarea>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                    <a href="{{ route('producto-vales.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold transition">
                        Cancelar
                    </a>
                    <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Guardar Producto Vale
                    </button>
                </div>
            </form>
        </div>

        <!-- Panel Lateral: Precalculadora en Tiempo Real -->
        <div class="bg-gradient-to-b from-indigo-950/40 to-slate-900 border border-indigo-500/20 rounded-2xl p-6 shadow-xl flex flex-col justify-between space-y-6">
            <div>
                <div class="flex items-center gap-2 mb-4">
                    <div class="w-8 h-8 rounded-lg bg-indigo-500/20 flex items-center justify-center text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h2 class="text-base font-bold text-white">Simulación de Pagos</h2>
                </div>

                <div class="space-y-4 text-sm">
                    <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800">
                        <span class="text-xs text-slate-400 block font-medium">Monto Total a Pagar</span>
                        <span id="calc_total" class="text-xl font-extrabold text-indigo-300">$0.00</span>
                        <span class="text-[10px] text-slate-500 block mt-0.5">(Préstamo + Seguro + Comisión + Intereses)</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800">
                        <span class="text-xs text-slate-400 block font-medium">Cuota Quincenal (Total / Plazo)</span>
                        <span id="calc_cuota" class="text-xl font-extrabold text-emerald-400">$0.00</span>
                        <span class="text-[10px] text-slate-500 block mt-0.5">Monto a liquidar cada 15 días</span>
                    </div>

                    <div class="p-3.5 rounded-xl bg-slate-950/60 border border-slate-800">
                        <span class="text-xs text-slate-400 block font-medium">Interés Total Acumulado</span>
                        <span id="calc_interes" class="text-lg font-bold text-slate-300">$0.00</span>
                    </div>
                </div>
            </div>

            <div class="text-[11px] text-slate-400 leading-relaxed border-t border-slate-800/80 pt-4 space-y-1">
                <p>💡 <strong>Validaciones activas:</strong></p>
                <p class="text-slate-500">&bull; Monto entre $100 y $1,000,000</p>
                <p class="text-slate-500">&bull; Plazos enteros de 1 a 120 quincenas</p>
                <p class="text-slate-500">&bull; Tasas y comisiones entre 0% y 100%</p>
            </div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const inputMonto = document.getElementById('monto_prestamo');
        const inputSeguro = document.getElementById('costo_seguro');
        const inputComisionApertura = document.getElementById('comision_apertura');
        const inputPlazo = document.getElementById('plazo_quincenas');
        const inputTasa = document.getElementById('tasa_interes_quincenal');

        const displayTotal = document.getElementById('calc_total');
        const displayCuota = document.getElementById('calc_cuota');
        const displayInteres = document.getElementById('calc_interes');

        function calcularValores() {
            const monto = Math.max(0, parseFloat(inputMonto.value) || 0);
            const seguro = Math.max(0, parseFloat(inputSeguro.value) || 0);
            const comisionAperturaPct = Math.min(100, Math.max(0, parseFloat(inputComisionApertura.value) || 0));
            const comisionApertura = monto * (comisionAperturaPct / 100);
            const plazo = Math.min(120, Math.max(0, parseInt(inputPlazo.value) || 0));
            const tasa = Math.min(100, Math.max(0, parseFloat(inputTasa.value) || 0));

            const interesTotal = monto * (tasa / 100) * plazo;
            const totalPagar = monto + seguro + comisionApertura + interesTotal;
            const cuotaQuincenal = plazo > 0 ? (totalPagar / plazo) : 0;

            displayTotal.textContent = '$' + totalPagar.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            displayCuota.textContent = '$' + cuotaQuincenal.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            displayInteres.textContent = '$' + interesTotal.toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        [inputMonto, inputSeguro, inputComisionApertura, inputPlazo, inputTasa].forEach(el => {
            if(el) {
                el.addEventListener('input', calcularValores);
                el.addEventListener('change', calcularValores);
            }
        });

        calcularValores();
    });
</script>
@endpush
