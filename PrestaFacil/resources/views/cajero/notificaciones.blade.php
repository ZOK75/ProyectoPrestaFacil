@extends('layouts.app')

@section('title', 'Notificaciones - Cajero')

@section('content')
<div class="max-w-md mx-auto space-y-4 pb-8">

    <div class="flex items-center justify-between">
        <a href="{{ Auth::user()->esCajero() ? route('cajero.dashboard') : route('autorizaciones.index') }}" class="text-xs font-bold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1">
            &larr; Volver
        </a>
    </div>

    <h1 class="text-xl font-black text-white flex items-center gap-2 mb-4">
        <svg class="w-6 h-6 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
        Notificaciones
    </h1>

    @if($notificaciones->isEmpty())
        <div class="p-6 bg-slate-900 border border-slate-800 rounded-2xl text-center shadow-xl">
            <p class="text-sm text-slate-400">No tienes notificaciones por el momento.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($notificaciones as $n)
                <div class="bg-slate-900 border {{ $n->leida ? 'border-slate-800' : 'border-indigo-500/50 shadow-lg shadow-indigo-500/10' }} rounded-2xl p-4 transition-all">
                    
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-2">
                            @if(!$n->leida)
                                <div class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></div>
                            @endif
                            <h3 class="text-sm font-black {{ $n->leida ? 'text-slate-300' : 'text-white' }}">{{ $n->titulo }}</h3>
                        </div>
                        <span class="text-[9px] font-mono text-slate-500">{{ $n->created_at->diffForHumans() }}</span>
                    </div>

                    <p class="text-xs {{ $n->leida ? 'text-slate-400' : 'text-slate-300' }} pl-4 mb-3">{{ $n->mensaje }}</p>

                    <div class="flex justify-between items-center pl-4">
                        @if($n->entidad_tipo === 'solicitudes_autorizacion')
                            <a href="{{ route('autorizaciones.show', $n->entidad_id) }}" class="text-[10px] font-bold text-indigo-400 hover:text-indigo-300">
                                Ver Solicitud &rarr;
                            </a>
                        @else
                            <div></div>
                        @endif

                        @if(!$n->leida)
                            <form novalidate action="{{ route('cajero.notificaciones.leer', $n->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="text-[10px] font-bold text-slate-500 hover:text-slate-300 bg-slate-800 px-2 py-1 rounded-md">
                                    Marcar leída
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            @endforeach

            <div class="mt-4">
                {{ $notificaciones->links() }}
            </div>
        </div>
    @endif

</div>
@endsection
