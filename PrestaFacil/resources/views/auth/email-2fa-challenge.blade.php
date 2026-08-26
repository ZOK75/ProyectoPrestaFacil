<x-guest-layout>
    <div class="w-full sm:max-w-md mx-auto my-6 px-6 sm:px-8 py-8 sm:py-10 bg-white shadow-xl shadow-slate-200/60 overflow-hidden rounded-2xl border border-slate-100 text-center">
        
        <!-- Icono Mail -->
        <div class="inline-flex items-center justify-center w-16 h-16 bg-emerald-50 text-emerald-600 rounded-full mb-4 border border-emerald-100/80 shadow-sm">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
            </svg>
        </div>

        <h1 class="text-2xl font-bold text-slate-900 mb-1">Verificación por Correo</h1>
        <p class="text-sm text-slate-500 mb-6">Hemos enviado un código de seguridad de 6 dígitos a tu correo electrónico registrado.</p>

        @if (session('status'))
            <div class="mb-4 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 p-3 rounded-xl">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 text-xs text-rose-600 bg-rose-50 border border-rose-200 p-3 rounded-xl text-left">
                @foreach ($errors->all() as $error)
                    <p class="font-medium">• {{ $error }}</p>
                @endforeach
            </div>
        @endif

        <form novalidate method="POST" action="{{ route('auth.email-2fa.verify') }}" class="space-y-5">
            @csrf

            <div class="text-left">
                <label for="code" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Código de Verificación</label>
                <input id="code" type="text" name="code" required autofocus placeholder="123456" maxlength="6" autocomplete="one-time-code"
                    class="w-full text-center tracking-[0.35em] text-2xl font-bold px-4 py-3 border border-slate-200 bg-slate-50 text-slate-800 rounded-xl focus:bg-white focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10 transition">
            </div>

            <button type="submit"
                class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-3.5 px-4 rounded-xl text-sm shadow-lg shadow-emerald-600/25 hover:shadow-emerald-600/35 transition duration-200 cursor-pointer">
                Verificar Código
            </button>
        </form>

        <form novalidate method="POST" action="{{ route('auth.email-2fa.resend') }}" class="mt-6 pt-4 border-t border-slate-100">
            @csrf
            <p class="text-xs text-slate-500 mb-2">¿No recibiste el correo o ya expiró?</p>
            <button type="submit" class="text-xs font-bold text-emerald-600 hover:text-emerald-700 hover:underline bg-transparent border-0 cursor-pointer transition">
                Reenviar nuevo código
            </button>
        </form>

    </div>
</x-guest-layout>