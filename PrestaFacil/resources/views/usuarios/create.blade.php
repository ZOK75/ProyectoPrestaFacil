@extends('layouts.app')

@section('title', 'Nuevo Usuario - PrestaFácil')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <a href="{{ route('usuarios.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1 mb-2">
            &larr; Volver a usuarios
        </a>
        <h1 class="text-2xl font-extrabold text-white">Registrar Nuevo Usuario</h1>
        <p class="text-sm text-slate-400">
            Operando como <span class="text-indigo-400 font-semibold">{{ $operador->rol?->nombre ?? 'Sin rol' }}</span>.
            @if($operador->esGerenteSucursal())
                Solo puedes asignar roles operativos en tu sucursal.
            @else
                Puedes asignar cualquier rol (excepto Gerente General) en cualquier sucursal.
            @endif
        </p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <form action="{{ route('usuarios.store') }}" method="POST" class="space-y-5">
            @csrf

            <!-- Nombre -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Nombre Completo <span class="text-rose-400">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name') }}" placeholder="ej. Juan Pérez García" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('name') border-rose-500 @enderror">
                @error('name')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Correo Electrónico <span class="text-rose-400">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email') }}" placeholder="ej. usuario@prestafacil.com" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('email') border-rose-500 @enderror">
                @error('email')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contraseña -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Contraseña <span class="text-rose-400">*</span>
                    </label>
                    <input type="password" name="password" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('password') border-rose-500 @enderror"
                        placeholder="Mínimo 8 caracteres">
                    @error('password')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Confirmar Contraseña <span class="text-rose-400">*</span>
                    </label>
                    <input type="password" name="password_confirmation" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                        placeholder="Repite la contraseña">
                </div>
            </div>

            <!-- Rol y Sucursal -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Rol <span class="text-rose-400">*</span>
                    </label>
                    <select name="rol_id" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500 @error('rol_id') border-rose-500 @enderror">
                        <option value="">Seleccionar rol...</option>
                        @foreach($rolesPermitidos as $rol)
                            <option value="{{ $rol->id }}" {{ old('rol_id') == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
                        @endforeach
                    </select>
                    @error('rol_id')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Sucursal <span class="text-rose-400">*</span>
                    </label>
                    <select name="sucursal_id" required
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500 @error('sucursal_id') border-rose-500 @enderror">
                        <option value="">Seleccionar sucursal...</option>
                        @foreach($sucursalesPermitidas as $suc)
                            <option value="{{ $suc->id }}" {{ old('sucursal_id') == $suc->id ? 'selected' : '' }}>{{ $suc->nombre }}</option>
                        @endforeach
                    </select>
                    @error('sucursal_id')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Nota Informativa de Categoría Inicial Cobre -->
            <div class="p-3.5 rounded-xl bg-amber-500/10 border border-amber-500/20 text-amber-300 text-xs flex items-center gap-2.5">
                <div class="w-6 h-6 rounded-lg bg-amber-500/20 flex items-center justify-center font-bold text-amber-400 shrink-0">
                    🥉
                </div>
                <div>
                    <strong>Categoría Inicial Automática:</strong> Si el rol asignado es <strong>Distribuidor / Distribuidora</strong>, iniciará siempre en <strong>Categoría Cobre</strong> por regla del sistema. Podrá ascender de categoría en la edición de su perfil.
                </div>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="{{ route('usuarios.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition">
                    Registrar Usuario
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
