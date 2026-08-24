<section>
    <header>
        <h2 class="text-lg font-bold text-white flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
            </svg>
            Información de la Cuenta
        </h2>
        <p class="mt-1 text-xs text-slate-400">
            Actualiza el nombre y correo electrónico asociado a tu perfil.
        </p>
    </header>

    <form novalidate id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form novalidate method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <label for="name" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Nombre Completo</label>
            <input id="name" name="name" type="text" maxlength="50" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            @if($errors->has('name'))
                <span class="text-xs text-rose-400 font-semibold mt-1 block">{{ $errors->first('name') }}</span>
            @endif
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-400 uppercase tracking-wider mb-1">Correo Electrónico</label>
            <input id="email" name="email" type="email" class="w-full bg-slate-950 border border-slate-800 rounded-xl text-white px-4 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 focus:outline-none transition" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            @if($errors->has('email'))
                <span class="text-xs text-rose-400 font-semibold mt-1 block">{{ $errors->first('email') }}</span>
            @endif

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 p-3 bg-rose-500/10 border border-rose-500/20 rounded-xl">
                    <p class="text-xs text-rose-400 font-medium">
                        Tu correo no está verificado.
                        <button form="send-verification" class="underline font-bold hover:text-rose-300 transition focus:outline-none">
                            Haz clic aquí para reenviar el enlace.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-xs font-medium text-emerald-400">
                            Un nuevo enlace de verificación ha sido enviado a tu correo.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4 pt-2">
            <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white shadow-lg shadow-indigo-900/20 text-xs font-bold tracking-wide transition">
                Guardar Cambios
            </button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2500)" class="text-xs font-bold text-emerald-400 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Actualizado
                </p>
            @endif
        </div>
    </form>
</section>
