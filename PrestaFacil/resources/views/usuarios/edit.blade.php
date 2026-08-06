@extends('layouts.app')

@section('title', 'Editar Usuario ' . $usuario->name . ' - PrestaFácil')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">

    <div>
        <a href="{{ route('usuarios.index') }}" class="text-xs font-semibold text-indigo-400 hover:text-indigo-300 inline-flex items-center gap-1 mb-2">
            &larr; Volver a usuarios
        </a>
        <h1 class="text-2xl font-extrabold text-white">Editar Usuario: <span class="text-indigo-400">{{ $usuario->name }}</span></h1>
        <p class="text-sm text-slate-400">Modifica los datos, rol o sucursal del usuario. Deja la contraseña vacía para no cambiarla.</p>
    </div>

    <div class="bg-slate-900 border border-slate-800 rounded-2xl p-6 shadow-xl">
        <form action="{{ route('usuarios.update', $usuario) }}" method="POST" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Nombre -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Nombre Completo <span class="text-rose-400">*</span>
                </label>
                <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500 @error('name') border-rose-500 @enderror">
                @error('name')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Correo Electrónico <span class="text-rose-400">*</span>
                </label>
                <input type="email" name="email" value="{{ old('email', $usuario->email) }}" required
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500 @error('email') border-rose-500 @enderror">
                @error('email')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Contraseña (Opcional) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Nueva Contraseña
                    </label>
                    <input type="password" name="password"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('password') border-rose-500 @enderror"
                        placeholder="Dejar vacío para no cambiar">
                    @error('password')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Confirmar Nueva Contraseña
                    </label>
                    <input type="password" name="password_confirmation"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500"
                        placeholder="Repite la nueva contraseña">
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
                            <option value="{{ $rol->id }}" {{ old('rol_id', $usuario->rol_id) == $rol->id ? 'selected' : '' }}>{{ $rol->nombre }}</option>
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
                            <option value="{{ $suc->id }}" {{ old('sucursal_id', $usuario->sucursal_id) == $suc->id ? 'selected' : '' }}>{{ $suc->nombre }}</option>
                        @endforeach
                    </select>
                    @error('sucursal_id')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Categoría de Distribuidor -->
            <div>
                <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                    Categoría de Distribuidor (Si aplica)
                </label>
                <select name="categoria_distribuidor"
                    class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500 @error('categoria_distribuidor') border-rose-500 @enderror">
                    <option value="cobre" {{ old('categoria_distribuidor', $usuario->categoria_distribuidor) === 'cobre' ? 'selected' : '' }}>Cobre (Ganancia por Configuración General)</option>
                    <option value="plata" {{ old('categoria_distribuidor', $usuario->categoria_distribuidor) === 'plata' ? 'selected' : '' }}>Plata (Ganancia por Configuración General)</option>
                    <option value="oro" {{ old('categoria_distribuidor', $usuario->categoria_distribuidor) === 'oro' ? 'selected' : '' }}>Oro (Ganancia por Configuración General)</option>
                </select>
                <span class="text-[10px] text-slate-500 mt-1 block">Aplica únicamente si el usuario es Distribuidor o Distribuidora.</span>
                @error('categoria_distribuidor')
                    <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
                <a href="{{ route('usuarios.index') }}" class="px-5 py-2.5 rounded-xl bg-slate-800 text-slate-300 hover:text-white text-sm font-semibold transition">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-semibold shadow-lg shadow-indigo-600/30 transition">
                    Actualizar Usuario
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
