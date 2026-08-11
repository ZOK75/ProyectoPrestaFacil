<x-guest-layout>
    <div class="bg-slate-900 border border-slate-800 rounded-3xl p-8 shadow-2xl shadow-indigo-950/20 space-y-6">
        
        <!-- Encabezado / Logo -->
        <div class="text-center space-y-2">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-gradient-to-tr from-indigo-600 to-violet-500 text-white mb-2 shadow-lg shadow-indigo-500/30">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
            </div>
            <h1 class="text-2xl font-black text-white tracking-tight">PrestaFácil</h1>
            <p class="text-xs text-slate-400">Ingresa tus credenciales para acceder al sistema</p>
        </div>

        <!-- Alertas de Sesión / Error -->
        @if(session('status'))
            <div class="p-3.5 rounded-xl bg-indigo-500/10 border border-indigo-500/20 text-indigo-400 text-xs font-semibold">
                {{ session('status') }}
            </div>
        @endif

        @if($errors->any())
            <div class="p-3.5 rounded-xl bg-rose-500/10 border border-rose-500/20 text-rose-400 text-xs space-y-1">
                @foreach($errors->all() as $error)
                    <p class="flex items-center gap-1.5">
                        <span>•</span> {{ $error }}
                    </p>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4" autocomplete="off">
            @csrf

            <!-- Correo Electrónico -->
            <div class="space-y-1.5">
                <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Correo electrónico
                </label>
                <div class="relative">
                    <input id="email" 
                           type="email" 
                           name="email" 
                           value="{{ old('email') }}" 
                           required 
                           autofocus 
                           placeholder="usuario@prestafacil.com"
                           class="w-full px-4 py-3 bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-white placeholder-slate-500 text-sm transition outline-none">
                </div>
            </div>

            <!-- Contraseña -->
            <div class="space-y-1.5">
                <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-300">
                    Contraseña
                </label>
                <div class="relative">
                    <input id="password" 
                           type="password" 
                           name="password" 
                           required 
                           placeholder="••••••••"
                           class="w-full px-4 py-3 bg-slate-950 border border-slate-800 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 rounded-xl text-white placeholder-slate-500 text-sm transition outline-none">
                </div>
            </div>

            <!-- Botón de Envío -->
            <div class="pt-2">
                <button type="submit" 
                        class="w-full py-3.5 px-4 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-xl text-sm transition duration-150 shadow-lg shadow-indigo-600/30 flex items-center justify-center gap-2">
                    <span>Iniciar Sesión</span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                    </svg>
                </button>
            </div>
        </form>

        <div class="pt-2 border-t border-slate-800/80 text-center">
            <p class="text-[11px] text-slate-500">
                PrestaFácil &copy; {{ date('Y') }} &bull; Gestión Financiera de Vales
            </p>
        </div>

    </div>
</x-guest-layout>