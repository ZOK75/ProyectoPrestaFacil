<section>
    <header>
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
            </svg>
            Actualizar Contraseña
        </h2>
        <p class="mt-1 text-xs text-slate-400">
            Asegúrate de usar una contraseña larga y segura para mantener tu cuenta protegida.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('put')

        <div>
            <label for="update_password_current_password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Contraseña Actual</label>
            <input id="update_password_current_password" name="current_password" type="password" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition" autocomplete="current-password" />
            @if($errors->updatePassword->has('current_password'))
                <span class="text-xs text-rose-400 font-semibold mt-1 block">{{ $errors->updatePassword->first('current_password') }}</span>
            @endif
        </div>

        <div>
            <label for="update_password_password" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Nueva Contraseña</label>
            <input id="update_password_password" name="password" type="password" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition" autocomplete="new-password" />
            @if($errors->updatePassword->has('password'))
                <span class="text-xs text-rose-400 font-semibold mt-1 block">{{ $errors->updatePassword->first('password') }}</span>
            @endif
        </div>

        <div>
            <label for="update_password_password_confirmation" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Confirmar Contraseña</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition" autocomplete="new-password" />
            @if($errors->updatePassword->has('password_confirmation'))
                <span class="text-xs text-rose-400 font-semibold mt-1 block">{{ $errors->updatePassword->first('password_confirmation') }}</span>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-600 hover:bg-amber-500 text-white shadow-lg shadow-amber-900/20 text-xs font-bold tracking-wide transition">
                Cambiar Contraseña
            </button>

            @if (session('status') === 'password-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Actualizada
                </p>
            @endif
        </div>
    </form>
</section>
