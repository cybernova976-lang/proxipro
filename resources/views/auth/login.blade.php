@extends('layouts.auth')

@section('title', 'Connexion')

@section('content')
<div class="flex-1 flex flex-col items-center justify-center px-4 py-10">
    <div class="w-full max-w-[400px] fade-in">
        <!-- Logo -->
        <a href="{{ url('/') }}" class="flex items-center justify-center gap-2 mb-10">
            <span class="text-2xl">🔨</span>
            <span class="text-xl font-extrabold tracking-tight text-gray-900">ProxiPro</span>
        </a>

        <!-- Carte -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-8">
            <h1 class="text-xl font-bold text-center mb-1">Connectez-vous</h1>
            <p class="text-sm text-gray-500 text-center mb-7">Accédez à votre espace personnel</p>

            <!-- Boutons sociaux -->
            <div class="space-y-2.5 mb-6">
                <a href="{{ route('social.redirect', 'google') }}" class="flex items-center justify-center gap-3 w-full py-2.5 px-4 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Continuer avec Google
                </a>
            </div>

            <!-- Séparateur -->
            <div class="flex items-center gap-3 mb-6">
                <div class="flex-1 h-px bg-gray-200"></div>
                <span class="text-xs text-gray-400 font-medium">ou</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            <!-- Messages -->
            @if (session('error'))
            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg flex items-start gap-2">
                <svg class="w-5 h-5 text-red-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/>
                </svg>
                <p class="text-sm text-red-700 font-medium">{{ session('error') }}</p>
            </div>
            @endif

            @if (session('success'))
            <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg flex items-start gap-2">
                <svg class="w-5 h-5 text-green-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-sm text-green-700 font-medium">{{ session('success') }}</p>
            </div>
            @endif

            @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-100 rounded-lg">
                <p class="text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        {{ $error }}
                    @endforeach
                </p>
            </div>
            @endif

            @if (session('status'))
            <div class="mb-4 p-3 bg-green-50 border border-green-100 rounded-lg">
                <p class="text-sm text-green-700">{{ session('status') }}</p>
            </div>
            @endif

            <!-- Formulaire -->
            <form method="POST" action="{{ route('login') }}" class="space-y-4" id="loginForm">
                @csrf

                <!-- Anti-bot: Honeypot -->
                <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true" tabindex="-1">
                    <input type="text" name="website_url" value="" autocomplete="off" tabindex="-1">
                </div>


                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">E-mail</label>
                    <input 
                        id="email" name="email" type="email" required value="{{ old('email') }}" 
                        placeholder="nom@exemple.com" autofocus
                        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400 @error('email') border-red-400 @enderror"
                    >
                    @error('email')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label for="password" class="text-sm font-medium text-gray-700">Mot de passe</label>
                        @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="text-xs text-blue-600 hover:text-blue-700 font-medium">Oublié ?</a>
                        @endif
                    </div>
                    <div class="relative">
                        <input 
                            id="password" name="password" type="password" required 
                            placeholder="••••••••"
                            class="w-full px-3 py-2.5 pr-10 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400 @error('password') border-red-400 @enderror"
                        >
                        <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg id="eye-icon" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-sm text-gray-600">Se souvenir de moi</span>
                </label>

                <button type="submit" id="loginSubmitBtn" class="w-full py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Se connecter
                </button>


            </form>
        </div>

        <!-- Lien inscription -->
        <p class="text-center text-sm text-gray-500 mt-6">
            Pas encore de compte ? <a href="{{ route('register') }}" class="font-semibold text-blue-600 hover:text-blue-700">Créer un compte</a>
        </p>
        
        <p class="text-center text-xs text-gray-400 mt-4">
            <a href="{{ url('/legal/terms') }}" class="hover:text-gray-600 underline">Conditions</a> · <a href="{{ url('/legal/privacy') }}" class="hover:text-gray-600 underline">Confidentialité</a>
        </p>
    </div>
</div>

<script>
    function togglePassword() {
        const f = document.getElementById('password');
        f.type = f.type === 'password' ? 'text' : 'password';
    }


</script>
@endsection
