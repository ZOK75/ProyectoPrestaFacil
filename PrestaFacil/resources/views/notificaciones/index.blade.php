@extends('layouts.app')

@section('title', 'Notificaciones - PrestaFácil')

@section('content')
<div class="w-full max-w-full space-y-4" x-data="notificationsPage()" x-init="init()">

    <!-- Encabezado -->
    <div class="flex items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-white flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
                Centro de Notificaciones
            </h1>
            <p class="text-xs text-slate-400 mt-0.5">Avisos de cortes, recordatorios de cobranza, solicitudes y estado operativo en tiempo real.</p>
        </div>

        @if($notificaciones->where('leida', false)->count() > 0)
            <form novalidate action="{{ route('notificaciones.marcar-todas') }}" method="POST">
                @csrf
                <button type="submit" class="px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-semibold text-slate-200 transition flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    Marcar todas leídas
                </button>
            </form>
        @endif
    </div>

    <!-- Contenedor para Notificaciones Inyectadas en Vivo -->
    <template x-for="item in liveNotifications" :key="item.id">
        <div class="bg-slate-900 border border-indigo-500/50 bg-slate-900/90 shadow-xl shadow-indigo-500/10 rounded-2xl p-4 transition-all duration-300 transform translate-y-0">
            <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                <div class="flex items-start gap-3 flex-1 min-w-0">
                    <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5 bg-indigo-500/20 text-indigo-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>

                    <div class="space-y-1 min-w-0 flex-1">
                        <div class="flex items-center gap-2 flex-wrap">
                            <h3 class="text-sm font-extrabold text-white break-words" x-text="item.titulo"></h3>
                        </div>
                        <p class="text-xs text-slate-300 leading-relaxed break-words" x-text="item.mensaje"></p>
                        <span class="text-[10px] text-slate-500 font-mono block pt-0.5" x-text="item.created_at_human || 'Hace un momento'"></span>
                    </div>
                </div>

                <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto pt-2 sm:pt-0">
                    <template x-if="item.url">
                        <a :href="item.url" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-[11px] font-bold text-white transition flex items-center gap-1.5 shadow whitespace-nowrap">
                            <span>Ver Detalle</span>
                        </a>
                    </template>
                </div>
            </div>
        </div>
    </template>

    <!-- Lista de Notificaciones Estática / Paginada -->
    <div class="space-y-3">
        @forelse($notificaciones as $notif)
            <div class="bg-slate-900 border {{ $notif->leida ? 'border-slate-800/80 opacity-80' : 'border-indigo-500/30 bg-slate-900/90 shadow-lg shadow-indigo-500/5' }} rounded-2xl p-4 transition">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-3">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5
                            @if(str_contains($notif->tipo, 'corte')) bg-indigo-500/20 text-indigo-400
                            @elseif(str_contains($notif->tipo, 'multa')) bg-rose-500/20 text-rose-400
                            @elseif(str_contains($notif->tipo, 'anticipado')) bg-emerald-500/20 text-emerald-400
                            @elseif(str_contains($notif->tipo, 'cobrado') || str_contains($notif->tipo, 'prestamo_cobrado')) bg-emerald-500/20 text-emerald-400
                            @else bg-slate-800 text-slate-300 @endif">
                            @if(str_contains($notif->tipo, 'corte'))
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                            @elseif(str_contains($notif->tipo, 'multa'))
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            @elseif(str_contains($notif->tipo, 'cobrado') || str_contains($notif->tipo, 'prestamo_cobrado'))
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                                </svg>
                            @endif
                        </div>

                        <div class="space-y-1 min-w-0 flex-1">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h3 class="text-sm font-extrabold text-white break-words">{{ $notif->titulo }}</h3>
                                @if(!$notif->leida)
                                    <span class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-indigo-500/20 text-indigo-400 border border-indigo-500/30 shrink-0">NUEVA</span>
                                @endif
                            </div>
                            <p class="text-xs text-slate-300 leading-relaxed break-words">{{ $notif->mensaje }}</p>
                            <div class="flex items-center gap-2 pt-0.5 flex-wrap">
                                <span class="text-[10px] text-slate-500 font-mono">{{ $notif->created_at->diffForHumans() }} ({{ $notif->created_at->format('d/m/Y H:i') }})</span>
                                @if(isset($notif->data['sucursal_nombre']) || isset($notif->data['sucursal']))
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700">
                                        Sucursal: {{ $notif->data['sucursal_nombre'] ?? $notif->data['sucursal'] }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-auto pt-2 sm:pt-0">
                        @if($notif->tipo === 'corte_generado')
                            @php
                                $pdfUrl = route('prestamos.relacion-pdf');
                                if (isset($notif->data['url']) && !empty($notif->data['url'])) {
                                    $parsedPath = parse_url($notif->data['url'], PHP_URL_PATH);
                                    if ($parsedPath) {
                                        $pdfUrl = url($parsedPath);
                                    }
                                }
                            @endphp
                            <a href="{{ $pdfUrl }}" target="_blank" class="px-3 py-1.5 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-[11px] font-bold text-white transition flex items-center gap-1.5 shadow whitespace-nowrap">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Abrir PDF
                            </a>
                        @elseif(isset($notif->data['url']) && !empty($notif->data['url']) && !Auth::user()->esCajero())
                            @php
                                $actionUrl = $notif->data['url'];
                                $parsedPath = parse_url($actionUrl, PHP_URL_PATH);
                                if ($parsedPath) {
                                    $actionUrl = url($parsedPath);
                                }
                                $btnText = 'Ver Detalle';
                                if (str_contains($notif->tipo, 'transferencia') || str_contains($notif->tipo, 'traspaso')) {
                                    if (str_contains($notif->tipo, 'requiere_autorizacion') || str_contains($notif->tipo, 'distribuidora')) {
                                        $btnText = 'Revisar Traspaso';
                                    } else {
                                        $btnText = 'Ver Traspaso';
                                    }
                                }
                            @endphp
                            <a href="{{ $actionUrl }}" class="px-3 py-1.5 rounded-lg bg-purple-600 hover:bg-purple-500 text-[11px] font-bold text-white transition flex items-center gap-1.5 shadow whitespace-nowrap">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                {{ $btnText }}
                            </a>
                        @endif

                        @if(!$notif->leida)
                            <form novalidate action="{{ route('notificaciones.leer', $notif) }}" method="POST">
                                @csrf
                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-emerald-600/20 hover:bg-emerald-600/30 text-emerald-400 border border-emerald-500/30 text-[11px] font-bold transition whitespace-nowrap flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    Marcar como leída
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-slate-900 border border-slate-800 rounded-2xl p-12 text-center text-slate-500 space-y-3" x-show="liveNotifications.length === 0">
                <div class="w-12 h-12 rounded-full bg-slate-800 flex items-center justify-center mx-auto text-slate-400">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </div>
                <p class="text-sm font-semibold text-slate-400">No tienes notificaciones pendientes</p>
            </div>
        @endforelse
    </div>

    <!-- Paginación -->
    @if($notificaciones->hasPages())
        <div class="pt-2">
            {{ $notificaciones->links() }}
        </div>
    @endif

</div>

<script>
    function notificationsPage() {
        return {
            liveNotifications: [],
            init() {
                window.addEventListener('live-new-notification', (e) => {
                    if (e.detail) {
                        this.liveNotifications.unshift(e.detail);
                    }
                });
            }
        };
    }
</script>
@endsection
