<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'ProxiPro') }} - Services entre particuliers et professionnels</title>
    <meta name="description" content="Trouvez des prestataires de confiance près de chez vous. Bricolage, jardinage, aide à domicile, cours particuliers...">

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        primary: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        },
                        secondary: {
                            500: '#8b5cf6',
                            600: '#7c3aed',
                        }
                    }
                }
            }
        }
    </script>

    <style>
        /* Animations */
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        @keyframes float-delayed {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-15px); }
        }
        @keyframes pulse-slow {
            0%, 100% { opacity: 0.4; }
            50% { opacity: 0.7; }
        }
        @keyframes slide-up {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-delayed { animation: float-delayed 5s ease-in-out infinite 1s; }
        .animate-pulse-slow { animation: pulse-slow 4s ease-in-out infinite; }
        .animate-slide-up { animation: slide-up 0.8s ease-out forwards; }
        
        /* Glassmorphism */
        .glass {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
        }
        
        /* Gradient backgrounds */
        .gradient-hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        /* Card hover effects */
        .category-card {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .category-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        }
        
        /* Search bar focus */
        .search-input:focus {
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.2);
        }
        
        /* Smooth scroll */
        html {
            scroll-behavior: smooth;
        }
        
        /* Step connector */
        .step-connector::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -50%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, #e0e7ff 0%, #c7d2fe 100%);
            transform: translateY(-50%);
            z-index: -1;
        }
        @media (max-width: 768px) {
            .step-connector::after {
                display: none;
            }
        }
        
        /* Image overlay */
        .image-overlay {
            background: linear-gradient(to top, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0) 50%);
        }
    </style>
</head>
<body class="font-sans antialiased text-gray-800">

    <!-- Navigation Modern -->
    <nav class="fixed w-full z-50 glass border-b border-white/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ url('/') }}" class="flex items-center space-x-2">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-secondary-600 flex items-center justify-center">
                            <i class="fas fa-hands-helping text-white text-lg"></i>
                        </div>
                        <span class="text-2xl font-bold gradient-text">ProxiPro</span>
                    </a>
                </div>

                <!-- Desktop Menu -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="#categories" class="text-gray-600 hover:text-primary-600 font-medium transition">Services</a>
                    <a href="#comment-ca-marche" class="text-gray-600 hover:text-primary-600 font-medium transition">Comment ça marche</a>
                </div>

                <!-- Auth Buttons -->
                <div class="hidden md:flex items-center space-x-4">
                    @if (Route::has('login'))
                        @auth
                            <a href="{{ url('/home') }}" class="text-gray-600 hover:text-primary-600 font-medium transition">Mon compte</a>
                            <a href="{{ url('/home') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-full font-medium transition shadow-lg shadow-primary-500/30">
                                Tableau de bord
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="text-gray-600 hover:text-primary-600 font-medium transition">Connexion</a>
                            @if (Route::has('register'))
                                <a href="{{ route('register') }}" class="bg-primary-600 hover:bg-primary-700 text-white px-5 py-2.5 rounded-full font-medium transition shadow-lg shadow-primary-500/30">
                                    S'inscrire
                                </a>
                            @endif
                        @endauth
                    @endif
                </div>

                <!-- Mobile menu button -->
                <button class="md:hidden p-2 rounded-lg hover:bg-gray-100" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
                    <i class="fas fa-bars text-gray-600 text-xl"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Menu -->
        <div id="mobile-menu" class="hidden md:hidden glass border-t border-white/20">
            <div class="px-4 py-3 space-y-2">
                <a href="#categories" class="block px-3 py-2 rounded-lg hover:bg-white/50 text-gray-700">Services</a>
                <a href="#comment-ca-marche" class="block px-3 py-2 rounded-lg hover:bg-white/50 text-gray-700">Comment ça marche</a>
                <hr class="border-gray-200">
                @if (Route::has('login'))
                    @auth
                        <a href="{{ url('/home') }}" class="block px-3 py-2 text-primary-600 font-medium">Mon compte</a>
                    @else
                        <a href="{{ route('login') }}" class="block px-3 py-2 text-gray-700">Connexion</a>
                        <a href="{{ route('register') }}" class="block px-3 py-2 text-primary-600 font-medium">S'inscrire</a>
                    @endauth
                @endif
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center overflow-hidden pt-16">
        <!-- Background Elements -->
        <div class="absolute inset-0 bg-gradient-to-br from-primary-50 via-white to-purple-50"></div>
        
        <!-- Decorative blobs -->
        <div class="absolute top-20 left-10 w-72 h-72 bg-primary-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float"></div>
        <div class="absolute top-40 right-10 w-96 h-96 bg-purple-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-float-delayed"></div>
        <div class="absolute bottom-20 left-1/3 w-80 h-80 bg-pink-300 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse-slow"></div>

        <div class="relative z-10 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center max-w-4xl mx-auto">
                <!-- Badge -->
                <div class="inline-flex items-center px-4 py-2 rounded-full bg-white/80 backdrop-blur-sm shadow-sm border border-primary-100 mb-8 animate-slide-up">
                    <span class="flex h-2 w-2 rounded-full bg-green-500 mr-2"></span>
                    <span class="text-sm font-medium text-gray-600">+10 000 services réalisés ce mois</span>
                </div>

                <!-- Title -->
                <h1 class="text-5xl md:text-6xl lg:text-7xl font-bold text-gray-900 mb-6 leading-tight animate-slide-up" style="animation-delay: 0.1s;">
                    Trouvez le <span class="gradient-text">prestataire</span><br>qu'il vous faut
                </h1>

                <!-- Subtitle -->
                <p class="text-xl md:text-2xl text-gray-600 mb-12 max-w-2xl mx-auto animate-slide-up" style="animation-delay: 0.2s;">
                    Bricolage, jardinage, aide à domicile, cours particuliers...<br class="hidden md:block">
                    Des milliers de professionnels et particuliers compétents près de chez vous.
                </p>

                <!-- Search Bar -->
                <div class="bg-white rounded-2xl shadow-2xl shadow-primary-500/10 p-2 max-w-3xl mx-auto animate-slide-up" style="animation-delay: 0.3s;">
                    <form action="{{ url('/ads') }}" method="GET" class="flex flex-col md:flex-row gap-2">
                        <div class="flex-1 relative">
                            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="q" 
                                placeholder="De quoi avez-vous besoin ?"
                                class="search-input w-full pl-12 pr-4 py-4 rounded-xl border-0 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-primary-500 transition text-gray-700 placeholder-gray-400"
                            >
                        </div>
                        <div class="flex-1 relative">
                            <div class="absolute inset-y-0 left-4 flex items-center pointer-events-none">
                                <i class="fas fa-map-marker-alt text-gray-400"></i>
                            </div>
                            <input 
                                type="text" 
                                name="location" 
                                placeholder="Ville ou code postal"
                                class="search-input w-full pl-12 pr-4 py-4 rounded-xl border-0 bg-gray-50 focus:bg-white focus:ring-2 focus:ring-primary-500 transition text-gray-700 placeholder-gray-400"
                            >
                        </div>
                        <button type="submit" class="bg-primary-600 hover:bg-primary-700 text-white px-8 py-4 rounded-xl font-semibold transition shadow-lg shadow-primary-500/30 flex items-center justify-center gap-2">
                            <span>Rechercher</span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>
                </div>

                <!-- Quick Tags -->
                <div class="mt-8 flex flex-wrap justify-center gap-3 animate-slide-up" style="animation-delay: 0.4s;">
                    <span class="text-sm text-gray-500">Populaire :</span>
                    <a href="{{ url('/ads?q=bricolage') }}" class="px-4 py-1.5 rounded-full bg-white/80 hover:bg-white border border-gray-200 text-sm text-gray-700 transition hover:border-primary-300 hover:text-primary-600">🔨 Bricolage</a>
                    <a href="{{ url('/ads?q=jardinage') }}" class="px-4 py-1.5 rounded-full bg-white/80 hover:bg-white border border-gray-200 text-sm text-gray-700 transition hover:border-primary-300 hover:text-primary-600">🌿 Jardinage</a>
                    <a href="{{ url('/ads?q=ménage') }}" class="px-4 py-1.5 rounded-full bg-white/80 hover:bg-white border border-gray-200 text-sm text-gray-700 transition hover:border-primary-300 hover:text-primary-600">✨ Ménage</a>
                    <a href="{{ url('/ads?q=cours') }}" class="px-4 py-1.5 rounded-full bg-white/80 hover:bg-white border border-gray-200 text-sm text-gray-700 transition hover:border-primary-300 hover:text-primary-600">📚 Cours</a>
                </div>
            </div>
        </div>

        <!-- Scroll indicator -->
        <div class="absolute bottom-8 left-1/2 transform -translate-x-1/2 animate-bounce">
            <a href="#categories" class="text-gray-400 hover:text-gray-600 transition">
                <i class="fas fa-chevron-down text-2xl"></i>
            </a>
        </div>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Explorez nos services</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">Des centaines de compétences disponibles pour tous vos besoins du quotidien</p>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                <!-- Category 1 -->
                <a href="{{ url('/ads?category=bricolage') }}" class="category-card group">
                    <div class="bg-gradient-to-br from-amber-400 to-orange-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-orange-500/30 group-hover:scale-110 transition">
                        <i class="fas fa-tools text-white text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1">Bricolage</h3>
                    <p class="text-sm text-gray-500">2 450 pros</p>
                </a>

                <!-- Category 2 -->
                <a href="{{ url('/ads?category=jardinage') }}" class="category-card group">
                    <div class="bg-gradient-to-br from-green-400 to-emerald-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-green-500/30 group-hover:scale-110 transition">
                        <i class="fas fa-leaf text-white text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1">Jardinage</h3>
                    <p class="text-sm text-gray-500">1 890 pros</p>
                </a>

                <!-- Category 3 -->
                <a href="{{ url('/ads?category=menage') }}" class="category-card group">
                    <div class="bg-gradient-to-br from-cyan-400 to-blue-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-cyan-500/30 group-hover:scale-110 transition">
                        <i class="fas fa-sparkles text-white text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1">Ménage</h3>
                    <p class="text-sm text-gray-500">3 120 pros</p>
                </a>

                <!-- Category 4 -->
                <a href="{{ url('/ads?category=aide') }}" class="category-card group">
                    <div class="bg-gradient-to-br from-rose-400 to-pink-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-rose-500/30 group-hover:scale-110 transition">
                        <i class="fas fa-hands-helping text-white text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1">Aide à domicile</h3>
                    <p class="text-sm text-gray-500">2 780 pros</p>
                </a>

                <!-- Category 5 -->
                <a href="{{ url('/ads?category=cours') }}" class="category-card group">
                    <div class="bg-gradient-to-br from-blue-400 to-indigo-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-500/30 group-hover:scale-110 transition">
                        <i class="fas fa-graduation-cap text-white text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1">Cours</h3>
                    <p class="text-sm text-gray-500">1 560 pros</p>
                </a>

                <!-- Category 6 -->
                <a href="{{ url('/ads?category=informatique') }}" class="category-card group">
                    <div class="bg-gradient-to-br from-violet-400 to-purple-500 w-16 h-16 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-violet-500/30 group-hover:scale-110 transition">
                        <i class="fas fa-laptop-code text-white text-2xl"></i>
                    </div>
                    <h3 class="font-semibold text-gray-900 mb-1">Informatique</h3>
                    <p class="text-sm text-gray-500">980 pros</p>
                </a>
            </div>

            <div class="text-center mt-12">
                <a href="{{ url('/ads') }}" class="inline-flex items-center text-primary-600 font-semibold hover:text-primary-700 transition">
                    Voir tous les services
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- How it Works Section -->
    <section id="comment-ca-marche" class="py-24 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <span class="text-primary-600 font-semibold text-sm uppercase tracking-wider">Simple et rapide</span>
                <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-4">Comment ça marche ?</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">Trouvez l'aide dont vous avez besoin en quelques clics</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="relative text-center">
                    <div class="step-connector relative">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-primary-500 to-primary-600 flex items-center justify-center mx-auto mb-6 shadow-xl shadow-primary-500/30">
                            <span class="text-3xl font-bold text-white">1</span>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition">
                        <div class="w-14 h-14 rounded-xl bg-primary-100 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-edit text-primary-600 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Décrivez votre besoin</h3>
                        <p class="text-gray-600">Publiez une annonce gratuite en décrivant ce dont vous avez besoin. Soyez précis pour recevoir les meilleures offres.</p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="relative text-center">
                    <div class="step-connector relative">
                        <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-secondary-500 to-purple-600 flex items-center justify-center mx-auto mb-6 shadow-xl shadow-purple-500/30">
                            <span class="text-3xl font-bold text-white">2</span>
                        </div>
                    </div>
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition">
                        <div class="w-14 h-14 rounded-xl bg-purple-100 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-users text-purple-600 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Recevez des offres</h3>
                        <p class="text-gray-600">Des prestataires qualifiés vous contactent avec leurs propositions. Comparez les profils, avis et tarifs.</p>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="relative text-center">
                    <div class="w-20 h-20 rounded-2xl bg-gradient-to-br from-pink-500 to-rose-600 flex items-center justify-center mx-auto mb-6 shadow-xl shadow-pink-500/30">
                        <span class="text-3xl font-bold text-white">3</span>
                    </div>
                    <div class="bg-white rounded-2xl p-8 shadow-lg hover:shadow-xl transition">
                        <div class="w-14 h-14 rounded-xl bg-pink-100 flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-handshake text-pink-600 text-xl"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">Choisissez et réalisez</h3>
                        <p class="text-gray-600">Sélectionnez le meilleur profil et réalisez votre projet en toute confiance. Paiement sécurisé disponible.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-20 bg-gradient-to-r from-primary-600 to-purple-600">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">50K+</div>
                    <div class="text-primary-200">Utilisateurs</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">15K+</div>
                    <div class="text-primary-200">Prestataires</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">100K+</div>
                    <div class="text-primary-200">Services réalisés</div>
                </div>
                <div>
                    <div class="text-4xl md:text-5xl font-bold text-white mb-2">4.8/5</div>
                    <div class="text-primary-200">Note moyenne</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-24 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <span class="text-primary-600 font-semibold text-sm uppercase tracking-wider">Pourquoi nous choisir</span>
                    <h2 class="text-4xl font-bold text-gray-900 mt-2 mb-6">La confiance avant tout</h2>
                    <p class="text-lg text-gray-600 mb-8">Nous vérifions chaque profil et mettons tout en œuvre pour que vos échanges se passent dans les meilleures conditions.</p>
                    
                    <div class="space-y-6">
                        <div class="flex items-start">
                            <div class="w-12 h-12 rounded-xl bg-green-100 flex items-center justify-center flex-shrink-0 mr-4">
                                <i class="fas fa-shield-alt text-green-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">Profils vérifiés</h3>
                                <p class="text-gray-600">Tous nos prestataires sont identifiés et leurs avis sont authentiques.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-12 h-12 rounded-xl bg-blue-100 flex items-center justify-center flex-shrink-0 mr-4">
                                <i class="fas fa-lock text-blue-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">Paiement sécurisé</h3>
                                <p class="text-gray-600">Option de paiement sécurisé pour tranquillité d'esprit.</p>
                            </div>
                        </div>
                        
                        <div class="flex items-start">
                            <div class="w-12 h-12 rounded-xl bg-purple-100 flex items-center justify-center flex-shrink-0 mr-4">
                                <i class="fas fa-headset text-purple-600 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-gray-900 mb-1">Support réactif</h3>
                                <p class="text-gray-600">Notre équipe vous accompagne 7j/7 en cas de besoin.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="absolute inset-0 bg-gradient-to-r from-primary-400 to-purple-400 rounded-3xl transform rotate-3 opacity-20"></div>
                    <img 
                        src="https://images.unsplash.com/photo-1600880292203-757bb62b4baf?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" 
                        alt="Professionnel au travail" 
                        class="relative rounded-2xl shadow-2xl w-full object-cover h-[500px]"
                    >
                    <!-- Floating card -->
                    <div class="absolute -bottom-6 -left-6 bg-white rounded-xl shadow-xl p-4 max-w-xs">
                        <div class="flex items-center gap-3">
                            <img src="https://images.unsplash.com/photo-1494790108377-be9c29b29330?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" alt="User" class="w-12 h-12 rounded-full object-cover">
                            <div>
                                <div class="font-semibold text-gray-900">Marie L.</div>
                                <div class="flex items-center text-yellow-400 text-sm">
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <i class="fas fa-star"></i>
                                    <span class="text-gray-500 ml-1">5.0</span>
                                </div>
                            </div>
                        </div>
                        <p class="text-gray-600 text-sm mt-2">"Service impeccable ! Je recommande vivement."</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Pro CTA Section -->
    <section id="pro" class="py-24 bg-gray-900 relative overflow-hidden">
        <!-- Background pattern -->
        <div class="absolute inset-0 opacity-10">
            <div class="absolute inset-0" style="background-image: radial-gradient(circle at 2px 2px, white 1px, transparent 0); background-size: 40px 40px;"></div>
        </div>
        
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-4xl md:text-5xl font-bold text-white mb-6">
                        Vous êtes <span class="gradient-text">professionnel</span> ou <span class="gradient-text">particulier compétent</span> ?
                    </h2>
                    <p class="text-xl text-gray-400 mb-8">
                        Rejoignez notre communauté de prestataires et développez votre activité. Des milliers de clients vous cherchent déjà.
                    </p>
                    
                    <div class="space-y-4 mb-8">
                        <div class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span>Inscription gratuite en 2 minutes</span>
                        </div>
                        <div class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span>Recevez des demandes qualifiées près de chez vous</span>
                        </div>
                        <div class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span>Aucune commission sur vos services</span>
                        </div>
                        <div class="flex items-center text-gray-300">
                            <i class="fas fa-check-circle text-green-500 mr-3"></i>
                            <span>Outils de gestion et de facturation inclus</span>
                        </div>
                    </div>
                    
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center bg-primary-600 hover:bg-primary-700 text-white px-8 py-4 rounded-xl font-semibold transition shadow-lg shadow-primary-500/30">
                            Devenir prestataire
                            <i class="fas fa-arrow-right ml-2"></i>
                        </a>
                        <a href="#" class="inline-flex items-center justify-center border border-gray-600 hover:border-gray-400 text-gray-300 hover:text-white px-8 py-4 rounded-xl font-semibold transition">
                            En savoir plus
                        </a>
                    </div>
                </div>
                
                <div class="relative">
                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-4 mt-8">
                            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                                        <i class="fas fa-euro-sign text-green-400"></i>
                                    </div>
                                    <div class="text-green-400 font-semibold">+€850</div>
                                </div>
                                <div class="text-gray-400 text-sm">Ce mois-ci</div>
                            </div>
                            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-full bg-primary-500/20 flex items-center justify-center">
                                        <i class="fas fa-calendar-check text-primary-400"></i>
                                    </div>
                                    <div class="text-primary-400 font-semibold">12 missions</div>
                                </div>
                                <div class="text-gray-400 text-sm">Réalisées</div>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-full bg-purple-500/20 flex items-center justify-center">
                                        <i class="fas fa-star text-purple-400"></i>
                                    </div>
                                    <div class="text-purple-400 font-semibold">4.9/5</div>
                                </div>
                                <div class="text-gray-400 text-sm">Note moyenne</div>
                            </div>
                            <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
                                <div class="flex items-center gap-3 mb-3">
                                    <div class="w-10 h-10 rounded-full bg-orange-500/20 flex items-center justify-center">
                                        <i class="fas fa-users text-orange-400"></i>
                                    </div>
                                    <div class="text-orange-400 font-semibold">48 clients</div>
                                </div>
                                <div class="text-gray-400 text-sm">Satisfaits</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-24 bg-primary-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Ils nous font confiance</h2>
                <p class="text-xl text-gray-600">Découvrez les expériences de nos utilisateurs</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <div class="flex items-center gap-1 text-yellow-400 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-600 mb-6">"J'ai trouvé un bricoleur en moins d'une heure pour réparer ma porte. Service rapide et professionnel, je recommande !"</p>
                    <div class="flex items-center gap-4">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" alt="User" class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <div class="font-semibold text-gray-900">Pierre D.</div>
                            <div class="text-gray-500 text-sm">Paris</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <div class="flex items-center gap-1 text-yellow-400 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                    <p class="text-gray-600 mb-6">"En tant que professionnel, ProxiPro m'a permis de développer ma clientèle locale. L'interface est intuitive et les clients sont sérieux."</p>
                    <div class="flex items-center gap-4">
                        <img src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" alt="User" class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <div class="font-semibold text-gray-900">Marc T.</div>
                            <div class="text-gray-500 text-sm">Jardinier pro - Lyon</div>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="bg-white rounded-2xl p-8 shadow-lg">
                    <div class="flex items-center gap-1 text-yellow-400 mb-4">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star-half-alt"></i>
                    </div>
                    <p class="text-gray-600 mb-6">"Super plateforme ! J'ai trouvé une nounou d'urgence pour mes enfants. Le système d'avis m'a rassurée dans mon choix."</p>
                    <div class="flex items-center gap-4">
                        <img src="https://images.unsplash.com/photo-1438761681033-6461ffad8d80?ixlib=rb-4.0.3&auto=format&fit=crop&w=100&q=80" alt="User" class="w-12 h-12 rounded-full object-cover">
                        <div>
                            <div class="font-semibold text-gray-900">Sophie M.</div>
                            <div class="text-gray-500 text-sm">Bordeaux</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-4xl font-bold text-gray-900 mb-6">Prêt à trouver l'aide qu'il vous faut ?</h2>
            <p class="text-xl text-gray-600 mb-8">Rejoignez plus de 50 000 utilisateurs satisfaits et trouvez votre prestataire dès maintenant.</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('register') }}" class="inline-flex items-center justify-center bg-primary-600 hover:bg-primary-700 text-white px-8 py-4 rounded-xl font-semibold transition shadow-lg shadow-primary-500/30">
                    Commencer gratuitement
                    <i class="fas fa-arrow-right ml-2"></i>
                </a>
                <a href="{{ url('/ads') }}" class="inline-flex items-center justify-center border-2 border-gray-300 hover:border-gray-400 text-gray-700 hover:text-gray-900 px-8 py-4 rounded-xl font-semibold transition">
                    Explorer les services
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-300 py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-8 mb-12">
                <!-- Brand -->
                <div class="col-span-2 lg:col-span-2">
                    <a href="{{ url('/') }}" class="flex items-center space-x-2 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-secondary-600 flex items-center justify-center">
                            <i class="fas fa-hands-helping text-white text-lg"></i>
                        </div>
                        <span class="text-2xl font-bold text-white">ProxiPro</span>
                    </a>
                    <p class="text-gray-400 mb-4 max-w-sm">La marketplace de services entre particuliers et professionnels. Trouvez l'aide dont vous avez besoin, près de chez vous.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary-600 transition">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary-600 transition">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary-600 transition">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" class="w-10 h-10 rounded-full bg-gray-800 flex items-center justify-center hover:bg-primary-600 transition">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>

                <!-- Services -->
                <div>
                    <h4 class="text-white font-semibold mb-4">Services</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition">Bricolage</a></li>
                        <li><a href="#" class="hover:text-white transition">Jardinage</a></li>
                        <li><a href="#" class="hover:text-white transition">Ménage</a></li>
                        <li><a href="#" class="hover:text-white transition">Aide à domicile</a></li>
                        <li><a href="#" class="hover:text-white transition">Cours particuliers</a></li>
                    </ul>
                </div>

                <!-- Company -->
                <div>
                    <h4 class="text-white font-semibold mb-4">Entreprise</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition">À propos</a></li>
                        <li><a href="#" class="hover:text-white transition">Carrières</a></li>
                        <li><a href="#" class="hover:text-white transition">Presse</a></li>
                        <li><a href="#" class="hover:text-white transition">Blog</a></li>
                        <li><a href="#" class="hover:text-white transition">Contact</a></li>
                    </ul>
                </div>

                <!-- Support -->
                <div>
                    <h4 class="text-white font-semibold mb-4">Aide</h4>
                    <ul class="space-y-2">
                        <li><a href="#" class="hover:text-white transition">Centre d'aide</a></li>
                        <li><a href="#" class="hover:text-white transition">Sécurité</a></li>
                        <li><a href="#" class="hover:text-white transition">Confidentialité</a></li>
                        <li><a href="#" class="hover:text-white transition">Conditions</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} ProxiPro. Tous droits réservés.</p>
                <div class="flex space-x-6 mt-4 md:mt-0 text-sm">
                    <a href="#" class="text-gray-500 hover:text-white transition">Politique de confidentialité</a>
                    <a href="#" class="text-gray-500 hover:text-white transition">Conditions d'utilisation</a>
                    <a href="#" class="text-gray-500 hover:text-white transition">Cookies</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.classList.add('shadow-lg');
            } else {
                nav.classList.remove('shadow-lg');
            }
        });
    </script>
</body>
</html>
