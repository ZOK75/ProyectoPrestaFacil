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
                <input type="text" name="name" value="{{ old('name', $usuario->name) }}" required maxlength="50"
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
                        Nueva Contraseña (Opcional)
                    </label>
                    <input type="password" name="password" minlength="12"
                        class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-sm text-white placeholder-slate-500 focus:outline-none focus:border-indigo-500 @error('password') border-rose-500 @enderror"
                        placeholder="Mínimo 12 caracteres (o dejar vacío)">
                    <span class="text-[10px] text-slate-500 mt-1 block">Dejar vacío si no deseas modificarla.</span>
                    @error('password')
                        <p class="text-xs text-rose-400 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-2">
                        Confirmar Nueva Contraseña
                    </label>
                    <input type="password" name="password_confirmation" minlength="12"
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

            <!-- Ajustes de Distribuidor: Categoría y Límite de Crédito -->
            @if($usuario->esDistribuidor())
            <div class="p-4 rounded-xl bg-slate-950 border border-slate-800 space-y-4">
                <h3 class="text-xs font-extrabold text-indigo-400 uppercase tracking-wider">
                    Parámetros de Distribución
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Categoría de Distribuidor
                        </label>
                        <select name="categoria_distribuidor"
                            class="w-full px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white focus:outline-none focus:border-indigo-500">
                            <option value="cobre" {{ old('categoria_distribuidor', $usuario->categoria_distribuidor) === 'cobre' ? 'selected' : '' }}>Cobre (Ganancia Config General)</option>
                            <option value="plata" {{ old('categoria_distribuidor', $usuario->categoria_distribuidor) === 'plata' ? 'selected' : '' }}>Plata (Ganancia Config General)</option>
                            <option value="oro" {{ old('categoria_distribuidor', $usuario->categoria_distribuidor) === 'oro' ? 'selected' : '' }}>Oro (Ganancia Config General)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                            Límite de Crédito ($)
                        </label>
                        <input type="number" step="0.01" min="0" name="limite_credito" value="{{ old('limite_credito', $usuario->limite_credito ?? 20000) }}"
                            class="w-full px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-emerald-400 font-bold focus:outline-none focus:border-emerald-500">
                        <span class="text-[10px] text-slate-500 mt-1 block">Línea de crédito asignada para colocar vales.</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-300 uppercase tracking-wider mb-1.5">
                        Referencia de Pago Bancaria Personalizada
                    </label>
                    <input type="text" name="referencia_pago_distribuidor" value="{{ old('referencia_pago_distribuidor', $usuario->referencia_pago_distribuidor) }}" placeholder="ej. REF-DIST-00000101"
                        class="w-full px-3.5 py-2 rounded-xl bg-slate-900 border border-slate-800 text-sm text-white font-mono focus:outline-none focus:border-indigo-500">
                    <span class="text-[10px] text-slate-500 mt-1 block">Dejar en blanco para generar automáticamente.</span>
                </div>
            </div>
            @endif

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
