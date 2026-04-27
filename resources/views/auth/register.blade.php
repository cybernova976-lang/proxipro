@extends('layouts.auth')

@section('title', 'Inscription')

@section('content')
<!-- Panneau droit - Formulaire -->
<div class="flex-1 flex items-start justify-center px-4 py-8 lg:px-12 overflow-y-auto">
    <div class="w-full max-w-lg fade-in">
        <!-- Logo mobile -->
        <a href="{{ url('/') }}" class="flex items-center gap-2 mb-8 lg:hidden">
            <span class="w-9 h-9 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-xl flex items-center justify-content-center text-white font-extrabold text-base shadow-md" style="display:inline-flex;align-items:center;justify-content:center;">P</span>
            <span class="text-xl font-extrabold tracking-tight text-gray-900">ProxiPro</span>
        </a>

        <!-- Carte du formulaire -->
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-8">
            <!-- Header -->
            <div class="mb-6">
                <h1 class="text-xl font-bold text-gray-900">Créer un compte</h1>
                <p class="text-sm text-gray-500 mt-1">Remplissez les informations ci-dessous</p>
            </div>

            <!-- Social buttons -->
            <div class="space-y-2.5 mb-5">
                <a href="{{ route('social.redirect', 'google') }}" class="flex items-center justify-center gap-3 w-full py-2.5 px-4 border border-gray-200 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                    <svg class="w-[18px] h-[18px]" viewBox="0 0 24 24">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    S'inscrire avec Google
                </a>
            </div>

            <!-- Séparateur -->
            <div class="flex items-center gap-3 mb-5">
                <div class="flex-1 h-px bg-gray-200"></div>
                <span class="text-xs text-gray-400 font-medium">ou par e-mail</span>
                <div class="flex-1 h-px bg-gray-200"></div>
            </div>

            <!-- Sélecteur de type d'utilisateur -->
            <div class="flex gap-3 mb-6" id="account-type-selector">
                <button
                    type="button"
                    onclick="setAccountType('particulier')"
                    id="btn-particulier"
                    class="flex-1 py-3 px-3 rounded-lg border-2 text-center transition-all group border-blue-600 bg-blue-50"
                >
                    <div class="text-lg mb-1">👤</div>
                    <div class="text-sm font-semibold text-blue-600">Particulier</div>
                </button>
                <button
                    type="button"
                    onclick="setAccountType('professionnel')"
                    id="btn-professionnel"
                    class="flex-1 py-3 px-3 rounded-lg border-2 text-center transition-all group border-gray-200 hover:border-gray-300 hover:bg-gray-50"
                >
                    <div class="text-lg mb-1">💼</div>
                    <div class="text-sm font-semibold text-gray-600">Professionnel</div>
                </button>
            </div>

            <!-- Message d'erreur social auth -->
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

            <!-- Messages de validation -->
            @if ($errors->any())
            <div class="mb-4 p-3 bg-red-50 border border-red-100 rounded-lg">
                <div class="text-sm text-red-600">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Formulaire -->
            <form method="POST" action="{{ route('register') }}" class="space-y-5" id="registerForm">
                @csrf
                <input type="hidden" name="account_type" id="account_type" value="{{ old('account_type', 'particulier') }}">
                
                <!-- Anti-bot: Token de timing (chiffré) -->
                <input type="hidden" name="_form_token" value="{{ encrypt(time()) }}">
                
                <!-- Anti-bot: Honeypot (invisible pour les vrais utilisateurs) -->
                <div style="position:absolute;left:-9999px;top:-9999px;" aria-hidden="true" tabindex="-1">
                    <label for="website_url">Ne pas remplir ce champ</label>
                    <input type="text" name="website_url" id="website_url" value="" autocomplete="off" tabindex="-1">
                </div>



                <!-- Champs Particulier -->
                <div id="fields-particulier">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Prénom *</label>
                            <input 
                                name="firstname" 
                                type="text" 
                                placeholder="Jean"
                                value="{{ old('firstname') }}"
                                class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Nom *</label>
                            <input 
                                name="lastname" 
                                type="text" 
                                placeholder="Dupont"
                                value="{{ old('lastname') }}"
                                class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400"
                            >
                        </div>
                    </div>
                </div>

                <!-- Champs Professionnel -->
                <div id="fields-professionnel" class="hidden space-y-5">
                    <!-- Sélecteur Type de Professionnel -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-3">Type d'activité *</label>
                        <div class="grid grid-cols-2 gap-3" id="business-type-selector">
                            <button
                                type="button"
                                onclick="setBusinessType('entreprise')"
                                id="btn-entreprise"
                                class="py-3 px-4 rounded-lg border-2 text-center transition-all border-gray-200 hover:border-gray-300 hover:bg-gray-50"
                            >
                                <div class="text-lg mb-1">🏢</div>
                                <div class="font-semibold text-gray-700 text-sm">Entreprise</div>
                                <div class="text-xs text-gray-400 mt-0.5">SARL, SAS, EURL...</div>
                            </button>
                            <button
                                type="button"
                                onclick="setBusinessType('auto_entrepreneur')"
                                id="btn-auto-entrepreneur"
                                class="py-3 px-4 rounded-lg border-2 text-center transition-all border-gray-200 hover:border-gray-300 hover:bg-gray-50"
                            >
                                <div class="text-lg mb-1">👨‍💼</div>
                                <div class="font-semibold text-gray-700 text-sm">Auto-entrepreneur</div>
                                <div class="text-xs text-gray-400 mt-0.5">Micro-entreprise</div>
                            </button>
                        </div>
                        <input type="hidden" name="business_type" id="business_type" value="{{ old('business_type') }}">
                        @error('business_type')
                            <p class="text-red-500 text-xs mt-2">{{ $message }}</p>
                        @enderror
                    </div>



                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            <span id="company-label">Nom de l'entreprise</span> *
                        </label>
                        <input 
                            name="company_name" 
                            type="text" 
                            id="company_name_input"
                            placeholder="Mon entreprise"
                            value="{{ old('company_name') }}"
                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400"
                        >
                        <span class="hidden" id="company-icon">🏢</span>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">SIRET</label>
                            <input 
                                name="siret" 
                                type="text" 
                                placeholder="12345678901234"
                                maxlength="14"
                                value="{{ old('siret') }}"
                                class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400"
                            >
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Téléphone</label>
                            <input 
                                name="phone" 
                                type="tel" 
                                placeholder="01 23 45 67 89"
                                value="{{ old('phone') }}"
                                class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400"
                            >
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Secteur d'activité</label>
                        <select 
                            name="sector" 
                            class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                        >
                            <option value="">Choisir...</option>
                            <option value="BTP / Construction" {{ old('sector') == 'BTP / Construction' ? 'selected' : '' }}>BTP / Construction</option>
                            <option value="Services à la personne" {{ old('sector') == 'Services à la personne' ? 'selected' : '' }}>Services à la personne</option>
                            <option value="Informatique / Tech" {{ old('sector') == 'Informatique / Tech' ? 'selected' : '' }}>Informatique / Tech</option>
                            <option value="Transport / Logistique" {{ old('sector') == 'Transport / Logistique' ? 'selected' : '' }}>Transport / Logistique</option>
                            <option value="Commerce / Vente" {{ old('sector') == 'Commerce / Vente' ? 'selected' : '' }}>Commerce / Vente</option>
                            <option value="Restauration / Hôtellerie" {{ old('sector') == 'Restauration / Hôtellerie' ? 'selected' : '' }}>Restauration / Hôtellerie</option>
                            <option value="Santé / Bien-être" {{ old('sector') == 'Santé / Bien-être' ? 'selected' : '' }}>Santé / Bien-être</option>
                            <option value="Éducation / Formation" {{ old('sector') == 'Éducation / Formation' ? 'selected' : '' }}>Éducation / Formation</option>
                            <option value="Agriculture / Jardinage" {{ old('sector') == 'Agriculture / Jardinage' ? 'selected' : '' }}>Agriculture / Jardinage</option>
                            <option value="Autre" {{ old('sector') == 'Autre' ? 'selected' : '' }}>Autre</option>
                        </select>
                    </div>


                </div>

                <!-- Champs communs -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-1.5">E-mail *</label>
                    <input 
                        id="email" name="email" type="email" required 
                        placeholder="nom@exemple.com" value="{{ old('email') }}"
                        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400 @error('email') border-red-400 @enderror"
                    >
                    @error('email')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="referral_code" class="block text-sm font-medium text-gray-700 mb-1.5">Code de parrainage</label>
                    <input
                        id="referral_code" name="referral_code" type="text"
                        placeholder="Ex. PROX123ABC"
                        value="{{ old('referral_code', request('ref')) }}"
                        class="w-full px-3 py-2.5 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400 @error('referral_code') border-red-400 @enderror"
                    >
                    <p class="mt-1 text-xs text-gray-500">Optionnel. Votre parrain gagne 50 points et vous 20 points après votre premier achat validé.</p>
                    @error('referral_code')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">Mot de passe *</label>
                    <div class="relative">
                        <input 
                            id="password" name="password" type="password" required 
                            placeholder="Min. 8 caractères"
                            maxlength="40"
                            autocomplete="new-password"
                            autocapitalize="off"
                            spellcheck="false"
                            class="w-full px-3 py-2.5 pr-10 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400 @error('password') border-red-400 @enderror"
                        >
                        <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg id="password-toggle" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                    <p class="mt-2 text-xs text-gray-500">8 à 40 caractères. Les phrases de passe sont autorisées (espaces inclus).</p>
                    <div class="mt-1.5 h-1 bg-gray-100 rounded-full overflow-hidden">
                        <div id="password-strength-bar" class="h-full rounded-full transition-all duration-500 bg-gray-300" style="width: 0%"></div>
                    </div>
                    <p id="password-strength-text" class="text-xs mt-1 text-gray-400">Sécurité du mot de passe</p>
                </div>

                <div>
                    <label for="password-confirm" class="block text-sm font-medium text-gray-700 mb-1.5">Confirmer le mot de passe *</label>
                    <div class="relative">
                        <input 
                            id="password-confirm" name="password_confirmation" type="password" required 
                            placeholder="Répétez le mot de passe"
                            maxlength="40"
                            autocomplete="new-password"
                            autocapitalize="off"
                            spellcheck="false"
                            class="w-full px-3 py-2.5 pr-10 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 placeholder:text-gray-400"
                        >
                        <button type="button" onclick="togglePassword('password-confirm')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <svg id="password-confirm-toggle" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </button>
                    </div>
                    <p id="password-match" class="text-xs mt-1 font-medium hidden"></p>
                </div>

                <!-- Conditions -->
                <div class="space-y-2 pt-1">
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="terms" required class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs text-gray-600 leading-relaxed">
                            J'accepte les <a href="{{ route('legal.terms') }}" target="_blank" class="text-blue-600 hover:underline">Conditions d'utilisation</a> et la <a href="{{ route('legal.privacy') }}" target="_blank" class="text-blue-600 hover:underline">Politique de confidentialité</a> *
                        </span>
                    </label>
                    <label class="flex items-start gap-2.5 cursor-pointer">
                        <input type="checkbox" name="newsletter" class="mt-0.5 w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs text-gray-600">Recevoir des offres et actualités par e-mail</span>
                    </label>
                </div>

                <!-- Bouton de soumission -->
                <button type="submit" id="submitBtn" class="w-full py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition disabled:opacity-50 disabled:cursor-not-allowed">
                    Créer mon compte
                </button>


            </form>

            <!-- Lien connexion -->
            <p class="text-center text-sm text-gray-500 mt-6">
                Déjà inscrit ? <a href="{{ route('login') }}" class="font-semibold text-blue-600 hover:text-blue-700">Se connecter</a>
            </p>
        </div>

        <!-- Footer -->
        <p class="text-center text-xs text-gray-400 mt-4">
            <a href="{{ route('legal.terms') }}" target="_blank" class="hover:text-gray-600 underline">Conditions</a> · <a href="{{ route('legal.privacy') }}" target="_blank" class="hover:text-gray-600 underline">Confidentialité</a>
        </p>
    </div>
</div>

<script>
    let currentAccountType = 'particulier';

    function setAccountType(type) {
        currentAccountType = type;
        document.getElementById('account_type').value = type;

        const btnParticulier = document.getElementById('btn-particulier');
        const btnProfessionnel = document.getElementById('btn-professionnel');
        const fieldsParticulier = document.getElementById('fields-particulier');
        const fieldsProfessionnel = document.getElementById('fields-professionnel');

        if (type === 'particulier') {
            btnParticulier.className = 'flex-1 py-3 px-3 rounded-lg border-2 text-center transition-all group border-blue-600 bg-blue-50';
            btnParticulier.querySelector('div:first-child').className = 'text-lg mb-1';
            btnParticulier.querySelector('div:last-child').className = 'text-sm font-semibold text-blue-600';
            
            btnProfessionnel.className = 'flex-1 py-3 px-3 rounded-lg border-2 text-center transition-all group border-gray-200 hover:border-gray-300 hover:bg-gray-50';
            btnProfessionnel.querySelector('div:first-child').className = 'text-lg mb-1';
            btnProfessionnel.querySelector('div:last-child').className = 'text-sm font-semibold text-gray-600';

            fieldsParticulier.classList.remove('hidden');
            fieldsProfessionnel.classList.add('hidden');
        } else {
            btnProfessionnel.className = 'flex-1 py-3 px-3 rounded-lg border-2 text-center transition-all group border-blue-600 bg-blue-50';
            btnProfessionnel.querySelector('div:first-child').className = 'text-lg mb-1';
            btnProfessionnel.querySelector('div:last-child').className = 'text-sm font-semibold text-blue-600';
            
            btnParticulier.className = 'flex-1 py-3 px-3 rounded-lg border-2 text-center transition-all group border-gray-200 hover:border-gray-300 hover:bg-gray-50';
            btnParticulier.querySelector('div:first-child').className = 'text-lg mb-1';
            btnParticulier.querySelector('div:last-child').className = 'text-sm font-semibold text-gray-600';

            fieldsParticulier.classList.add('hidden');
            fieldsProfessionnel.classList.remove('hidden');
            
            // Réinitialiser le sélecteur de type de business
            resetBusinessTypeSelector();
        }
    }

    let currentBusinessType = '';

    function setBusinessType(type) {
        currentBusinessType = type;
        document.getElementById('business_type').value = type;

        const btnEntreprise = document.getElementById('btn-entreprise');
        const btnAutoEntrepreneur = document.getElementById('btn-auto-entrepreneur');
        const companyLabel = document.getElementById('company-label');
        const companyInput = document.getElementById('company_name_input');
        const companyIcon = document.getElementById('company-icon');

        if (type === 'entreprise') {
            btnEntreprise.className = 'py-3 px-4 rounded-lg border-2 text-center transition-all border-blue-600 bg-blue-50';
            btnAutoEntrepreneur.className = 'py-3 px-4 rounded-lg border-2 text-center transition-all border-gray-200 hover:border-gray-300 hover:bg-gray-50';
            
            companyLabel.textContent = "Nom de l'entreprise";
            companyInput.placeholder = "Ma société SARL";
            companyIcon.textContent = '🏢';
        } else {
            btnAutoEntrepreneur.className = 'py-3 px-4 rounded-lg border-2 text-center transition-all border-blue-600 bg-blue-50';
            btnEntreprise.className = 'py-3 px-4 rounded-lg border-2 text-center transition-all border-gray-200 hover:border-gray-300 hover:bg-gray-50';
            
            companyLabel.textContent = "Nom commercial";
            companyInput.placeholder = "Jean Dupont Services";
            companyIcon.textContent = '👨‍💼';
        }
    }

    function resetBusinessTypeSelector() {
        currentBusinessType = '';
        document.getElementById('business_type').value = '';
        
        const btnEntreprise = document.getElementById('btn-entreprise');
        const btnAutoEntrepreneur = document.getElementById('btn-auto-entrepreneur');
        
        btnEntreprise.className = 'py-3 px-4 rounded-lg border-2 text-center transition-all border-gray-200 hover:border-gray-300 hover:bg-gray-50';
        btnAutoEntrepreneur.className = 'py-3 px-4 rounded-lg border-2 text-center transition-all border-gray-200 hover:border-gray-300 hover:bg-gray-50';
    }

    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        field.type = field.type === 'password' ? 'text' : 'password';
    }

    const commonPasswords = new Set([
        '123456', '12345678', '123456789', '1234567890', 'password', 'password123',
        'qwerty', 'azerty', 'admin', 'admin123', 'welcome', 'letmein', 'iloveyou',
        '000000', '111111', 'abc123', 'motdepasse', 'motdepasse123', 'proxipro'
    ]);

    function evaluatePasswordStrength(password) {
        const normalized = password.trim().toLowerCase();
        const email = (document.getElementById('email').value || '').trim().toLowerCase();
        const emailLocalPart = email.includes('@') ? email.split('@')[0] : email;

        const firstName = (document.querySelector('input[name="firstname"]')?.value || '').trim().toLowerCase();
        const lastName = (document.querySelector('input[name="lastname"]')?.value || '').trim().toLowerCase();
        const companyName = (document.querySelector('input[name="company_name"]')?.value || '').trim().toLowerCase();
        const fullName = `${firstName} ${lastName}`.trim();

        if (!password) {
            return { width: '0%', color: '#9ca3af', text: 'Sécurité du mot de passe' };
        }

        if (password.length < 8) {
            return { width: '20%', color: '#ef4444', text: 'Insuffisant (minimum 8 caractères)' };
        }

        if (password.length > 40) {
            return { width: '20%', color: '#ef4444', text: 'Insuffisant (maximum 40 caractères)' };
        }

        if (commonPasswords.has(normalized)) {
            return { width: '20%', color: '#ef4444', text: 'Mot de passe trop courant' };
        }

        if (normalized === email || normalized === emailLocalPart || normalized === firstName || normalized === lastName || normalized === companyName || normalized === fullName) {
            return { width: '20%', color: '#ef4444', text: 'Insuffisant (trop proche de vos informations)' };
        }

        let score = 0;
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (password.length >= 16) score++;
        if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9\s]/.test(password)) score++;
        if (password.includes(' ') && password.trim().split(/\s+/).length >= 3) score++;

        if (score <= 2) {
            return { width: '35%', color: '#f97316', text: 'Moyen' };
        }

        if (score <= 5) {
            return { width: '70%', color: '#22c55e', text: 'Fort' };
        }

        return { width: '100%', color: '#16a34a', text: 'Très fort' };
    }

    function updatePasswordStrength(password) {
        const bar = document.getElementById('password-strength-bar');
        const text = document.getElementById('password-strength-text');
        const level = evaluatePasswordStrength(password);
        bar.style.width = level.width;
        bar.style.backgroundColor = level.color;
        text.style.color = level.color;
        text.textContent = level.text;
    }

    document.getElementById('password').addEventListener('input', function(e) {
        updatePasswordStrength(e.target.value);

        const confirmPassword = document.getElementById('password-confirm').value;
        if (confirmPassword) {
            const matchText = document.getElementById('password-match');
            matchText.classList.remove('hidden');
            if (e.target.value === confirmPassword) {
                matchText.className = 'text-xs mt-1.5 font-medium text-green-500';
                matchText.textContent = '✓ Les mots de passe correspondent';
            } else {
                matchText.className = 'text-xs mt-1.5 font-medium text-red-500';
                matchText.textContent = '✗ Les mots de passe ne correspondent pas';
            }
        }
    });

    // Password match checker
    document.getElementById('password-confirm').addEventListener('input', function(e) {
        const password = document.getElementById('password').value;
        const confirmPassword = e.target.value;
        const matchText = document.getElementById('password-match');

        if (confirmPassword) {
            matchText.classList.remove('hidden');
            if (password === confirmPassword) {
                matchText.className = 'text-xs mt-1.5 font-medium text-green-500';
                matchText.textContent = '✓ Les mots de passe correspondent';
            } else {
                matchText.className = 'text-xs mt-1.5 font-medium text-red-500';
                matchText.textContent = '✗ Les mots de passe ne correspondent pas';
            }
        } else {
            matchText.classList.add('hidden');
        }
    });

    // ==============================
    // Restore form state after validation failure
    // ==============================
    document.addEventListener('DOMContentLoaded', function() {
        const oldAccountType = document.getElementById('account_type').value;
        const oldBusinessType = document.getElementById('business_type').value;

        // Restore account type
        if (oldAccountType === 'professionnel') {
            setAccountType('professionnel');
        }

        // Restore business type
        if (oldBusinessType) {
            setBusinessType(oldBusinessType);
        }
    });


</script>
@endsection
