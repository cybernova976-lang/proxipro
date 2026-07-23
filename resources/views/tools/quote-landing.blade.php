<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">
    <title>Créer un Devis ou une Facture Gratuit en Ligne | {{ config('app.name', 'Lunamars') }}</title>
    <meta name="description" content="Créez vos devis et factures professionnels gratuitement en quelques clics. Outil en ligne simple, rapide et adapté aux auto-entrepreneurs, artisans, freelances et petites entreprises.">
    <meta name="keywords" content="créer devis gratuit, facture en ligne, générateur devis, outil facturation, devis auto-entrepreneur, facture freelance, devis artisan">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="{{ url('/creer-devis-facture-gratuit') }}">

    <!-- Open Graph -->
    <meta property="og:title" content="Créer un Devis ou une Facture Gratuit en Ligne | {{ config('app.name', 'Lunamars') }}">
    <meta property="og:description" content="Créez vos devis et factures professionnels gratuitement en quelques clics. Outil en ligne simple et adapté à tous les métiers.">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url('/creer-devis-facture-gratuit') }}">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="{{ config('app.name', 'Lunamars') }}">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Créer un Devis ou une Facture Gratuit en Ligne">
    <meta name="twitter:description" content="Créez vos devis et factures professionnels gratuitement. Outil simple et rapide.">

    <!-- JSON-LD Structured Data -->
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "SoftwareApplication",
        "name": "Lunamars - Générateur de Devis et Factures",
        "operatingSystem": "Web",
        "applicationCategory": "BusinessApplication",
        "description": "Outil en ligne pour créer des devis et factures professionnels en PDF.",
        "offers": {
            "@@type": "Offer",
            "price": "0",
            "priceCurrency": "EUR",
            "description": "1 devis gratuit offert, puis packs à partir de 4,99€"
        },
        "aggregateRating": {
            "@@type": "AggregateRating",
            "ratingValue": "4.8",
            "ratingCount": "250"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@@context": "https://schema.org",
        "@@type": "FAQPage",
        "mainEntity": [
            {
                "@@type": "Question",
                "name": "Comment créer un devis gratuit en ligne ?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Inscrivez-vous gratuitement sur Lunamars, remplissez les informations de votre entreprise et de votre client, ajoutez vos lignes de produits ou services, et téléchargez votre devis en PDF en un clic. Votre premier devis est entièrement gratuit."
                }
            },
            {
                "@@type": "Question",
                "name": "L'outil de devis est-il vraiment gratuit ?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Oui, votre premier devis est 100% gratuit, sans engagement. Pour créer davantage de documents (devis ou factures), vous pouvez acheter des packs de crédits à partir de 4,99€ pour 5 documents."
                }
            },
            {
                "@@type": "Question",
                "name": "Quels métiers peuvent utiliser cet outil ?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Notre outil est adapté à tous les métiers et statuts : auto-entrepreneurs, artisans, freelances, consultants, commerçants, professions libérales, petites entreprises... Quel que soit votre secteur d'activité."
                }
            },
            {
                "@@type": "Question",
                "name": "Les devis générés sont-ils conformes à la législation ?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Nos devis et factures incluent toutes les mentions obligatoires : numéro de document, coordonnées de l'émetteur et du client, détail des prestations, montants HT/TVA/TTC, conditions de paiement."
                }
            },
            {
                "@@type": "Question",
                "name": "Puis-je personnaliser mes devis et factures ?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Oui, vous pouvez personnaliser vos informations d'entreprise (nom, adresse, SIRET, logo à venir), les informations client, les lignes de détail, le taux de TVA, les conditions et les notes."
                }
            },
            {
                "@@type": "Question",
                "name": "Comment payer pour des crédits supplémentaires ?",
                "acceptedAnswer": {
                    "@@type": "Answer",
                    "text": "Vous pouvez acheter des packs de crédits par carte bancaire (paiement sécurisé via Stripe) ou utiliser vos points Lunamars. Les crédits n'expirent jamais."
                }
            }
        ]
    }
    </script>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81' }
                    }
                }
            }
        }
    </script>
    <style>
        .fade-in { animation: fadeIn 0.6s ease-out forwards; opacity: 0; }
        @keyframes fadeIn { to { opacity: 1; } }
        .fade-in-delay-1 { animation-delay: 0.1s; }
        .fade-in-delay-2 { animation-delay: 0.2s; }
        .fade-in-delay-3 { animation-delay: 0.3s; }
        .hover-lift { transition: transform 0.25s ease, box-shadow 0.25s ease; }
        .hover-lift:hover { transform: translateY(-4px); box-shadow: 0 12px 32px rgba(0,0,0,0.1); }
    </style>
</head>
<body class="font-sans bg-white text-gray-900 antialiased">

    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-100 sticky top-0 z-50">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center justify-between">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-xl font-bold text-primary-600 no-underline">
                <x-brand-mark :size="38" class="w-10 h-10 rounded-xl bg-white shadow-sm" :decorative="false" />
                {{ config('app.name', 'Lunamars') }}
            </a>
            <div class="flex items-center gap-3">
                @auth
                    <a href="{{ route('quote-tool.quote.create') }}" class="inline-flex items-center gap-2 bg-primary-600 text-white px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-primary-700 transition no-underline">
                        <i class="fas fa-plus"></i> Creer un devis
                    </a>
                @else
                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 text-sm font-medium no-underline">Connexion</a>
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-primary-600 text-white px-5 py-2.5 rounded-lg font-semibold text-sm hover:bg-primary-700 transition no-underline">
                        Commencer gratuitement
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative overflow-hidden bg-gradient-to-br from-primary-50 via-white to-primary-50">
        <div class="max-w-6xl mx-auto px-4 py-20 md:py-28">
            <div class="max-w-3xl mx-auto text-center">
                <div class="inline-flex items-center gap-2 bg-primary-100 text-primary-700 px-4 py-1.5 rounded-full text-sm font-semibold mb-6 fade-in">
                    <i class="fas fa-gift"></i> 1er devis gratuit - sans engagement
                </div>
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-5 leading-tight fade-in fade-in-delay-1">
                    Creez vos <span class="text-primary-600">Devis</span> et <span class="text-primary-600">Factures</span> Professionnels en Ligne
                </h1>
                <p class="text-lg text-gray-600 mb-8 max-w-2xl mx-auto leading-relaxed fade-in fade-in-delay-2">
                    Generez des devis et factures en PDF en quelques clics. Outil simple, rapide et adapte aux auto-entrepreneurs, artisans, freelances et PME.
                </p>
                <div class="flex flex-col sm:flex-row gap-3 justify-center fade-in fade-in-delay-3">
                    @auth
                        <a href="{{ route('quote-tool.quote.create') }}" class="inline-flex items-center justify-center gap-2 bg-primary-600 text-white px-8 py-3.5 rounded-xl font-bold text-base hover:bg-primary-700 transition shadow-lg shadow-primary-600/30 no-underline">
                            <i class="fas fa-file-alt"></i> Creer mon devis gratuit
                        </a>
                        <a href="{{ route('quote-tool.invoice.create') }}" class="inline-flex items-center justify-center gap-2 bg-white text-primary-600 px-8 py-3.5 rounded-xl font-bold text-base border-2 border-primary-200 hover:border-primary-400 transition no-underline">
                            <i class="fas fa-file-invoice"></i> Creer une facture
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex items-center justify-center gap-2 bg-primary-600 text-white px-8 py-3.5 rounded-xl font-bold text-base hover:bg-primary-700 transition shadow-lg shadow-primary-600/30 no-underline">
                            <i class="fas fa-file-alt"></i> Creer mon devis gratuit
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 bg-white text-primary-600 px-8 py-3.5 rounded-xl font-bold text-base border-2 border-primary-200 hover:border-primary-400 transition no-underline">
                            <i class="fas fa-sign-in-alt"></i> J'ai deja un compte
                        </a>
                    @endauth
                </div>
            </div>
        </div>
        <div class="absolute top-20 left-10 w-72 h-72 bg-primary-200 rounded-full opacity-20 blur-3xl"></div>
        <div class="absolute bottom-10 right-10 w-96 h-96 bg-primary-300 rounded-full opacity-15 blur-3xl"></div>
    </section>

    <!-- Features -->
    <section class="py-20 bg-white" id="fonctionnalites">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-bold text-gray-900 mb-3">Tout ce qu'il vous faut pour facturer</h2>
                <p class="text-gray-500 max-w-xl mx-auto">Un outil complet pour gerer vos devis et factures, quel que soit votre metier.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white border border-gray-100 rounded-2xl p-6 hover-lift">
                    <div class="w-12 h-12 bg-primary-100 text-primary-600 rounded-xl flex items-center justify-center text-lg mb-4"><i class="fas fa-file-alt"></i></div>
                    <h3 class="font-bold text-gray-900 mb-2">Devis professionnels</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Creez des devis detailles avec vos informations, celles du client, les lignes de prestation et les conditions.</p>
                </div>
                <div class="bg-white border border-gray-100 rounded-2xl p-6 hover-lift">
                    <div class="w-12 h-12 bg-green-100 text-green-600 rounded-xl flex items-center justify-center text-lg mb-4"><i class="fas fa-file-invoice"></i></div>
                    <h3 class="font-bold text-gray-900 mb-2">Factures conformes</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Generez des factures avec toutes les mentions obligatoires : numero, TVA, echeance, mode de paiement.</p>
                </div>
                <div class="bg-white border border-gray-100 rounded-2xl p-6 hover-lift">
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-xl flex items-center justify-center text-lg mb-4"><i class="fas fa-file-pdf"></i></div>
                    <h3 class="font-bold text-gray-900 mb-2">Export PDF instantane</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Telechargez vos documents au format PDF, prets a etre envoyes a vos clients par email ou imprimes.</p>
                </div>
                <div class="bg-white border border-gray-100 rounded-2xl p-6 hover-lift">
                    <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-xl flex items-center justify-center text-lg mb-4"><i class="fas fa-calculator"></i></div>
                    <h3 class="font-bold text-gray-900 mb-2">Calculs automatiques</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Totaux HT, TVA et TTC calcules automatiquement. Plus d'erreurs de calcul dans vos documents.</p>
                </div>
                <div class="bg-white border border-gray-100 rounded-2xl p-6 hover-lift">
                    <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center text-lg mb-4"><i class="fas fa-users"></i></div>
                    <h3 class="font-bold text-gray-900 mb-2">Adapte a tous les metiers</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Artisans, freelances, consultants, commercants, professions liberales... Notre outil s'adapte a votre activite.</p>
                </div>
                <div class="bg-white border border-gray-100 rounded-2xl p-6 hover-lift">
                    <div class="w-12 h-12 bg-teal-100 text-teal-600 rounded-xl flex items-center justify-center text-lg mb-4"><i class="fas fa-gift"></i></div>
                    <h3 class="font-bold text-gray-900 mb-2">1er devis gratuit</h3>
                    <p class="text-gray-500 text-sm leading-relaxed">Testez l'outil sans engagement. Votre premier devis est offert. Ensuite, des packs a partir de 4,99 EUR.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- How it works -->
    <section class="py-20 bg-gray-50" id="comment-ca-marche">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-bold text-gray-900 mb-3">Comment ca marche ?</h2>
                <p class="text-gray-500">3 etapes simples pour creer votre devis ou facture.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-5 shadow-lg shadow-primary-600/30">1</div>
                    <h3 class="font-bold text-gray-900 mb-2 text-lg">Remplissez vos informations</h3>
                    <p class="text-gray-500 text-sm">Entrez vos coordonnees et celles de votre client. Vos informations sont pre-remplies pour aller plus vite.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-5 shadow-lg shadow-primary-600/30">2</div>
                    <h3 class="font-bold text-gray-900 mb-2 text-lg">Ajoutez vos prestations</h3>
                    <p class="text-gray-500 text-sm">Detaillez vos produits ou services avec quantites et prix. Les totaux sont calcules automatiquement.</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 bg-primary-600 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mx-auto mb-5 shadow-lg shadow-primary-600/30">3</div>
                    <h3 class="font-bold text-gray-900 mb-2 text-lg">Telechargez votre PDF</h3>
                    <p class="text-gray-500 text-sm">Votre document professionnel est genere instantanement en PDF. Envoyez-le directement a vos clients.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Use cases -->
    <section class="py-20 bg-white" id="cas-usage">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-bold text-gray-900 mb-3">Adapte a votre activite</h2>
                <p class="text-gray-500 max-w-xl mx-auto">Quel que soit votre metier ou votre statut, notre outil s'adapte a vos besoins.</p>
            </div>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                    $useCases = [
                        ['icon' => 'fa-hammer', 'name' => 'Artisans', 'desc' => 'Plombiers, electriciens, peintres, menuisiers...', 'bg' => 'bg-orange-50', 'text' => 'text-orange-600'],
                        ['icon' => 'fa-laptop-code', 'name' => 'Freelances', 'desc' => 'Developpeurs, designers, redacteurs, traducteurs...', 'bg' => 'bg-blue-50', 'text' => 'text-blue-600'],
                        ['icon' => 'fa-store', 'name' => 'Auto-entrepreneurs', 'desc' => 'Micro-entreprises de tous secteurs d\'activite.', 'bg' => 'bg-green-50', 'text' => 'text-green-600'],
                        ['icon' => 'fa-building', 'name' => 'PME / TPE', 'desc' => 'Petites et moyennes entreprises, commercants.', 'bg' => 'bg-purple-50', 'text' => 'text-purple-600'],
                    ];
                @endphp
                @foreach($useCases as $uc)
                    <div class="border border-gray-100 rounded-xl p-5 hover-lift">
                        <div class="w-10 h-10 {{ $uc['bg'] }} {{ $uc['text'] }} rounded-lg flex items-center justify-center mb-3"><i class="fas {{ $uc['icon'] }}"></i></div>
                        <h3 class="font-bold text-gray-900 text-sm mb-1">{{ $uc['name'] }}</h3>
                        <p class="text-gray-500 text-xs leading-relaxed">{{ $uc['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="py-20 bg-gray-50" id="tarifs">
        <div class="max-w-5xl mx-auto px-4">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-bold text-gray-900 mb-3">Tarifs simples et transparents</h2>
                <p class="text-gray-500">Un premier devis gratuit, puis des packs adaptes a votre volume.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white border border-gray-200 rounded-2xl p-7 text-center hover-lift">
                    <div class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Gratuit</div>
                    <div class="text-4xl font-extrabold text-gray-900 mb-1">0&euro;</div>
                    <div class="text-gray-400 text-sm mb-5">1 devis offert</div>
                    <ul class="text-left text-sm text-gray-600 space-y-2 mb-6">
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> 1 devis gratuit</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Export PDF</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Calculs automatiques</li>
                    </ul>
                    @auth
                        <a href="{{ route('quote-tool.quote.create') }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2.5 rounded-lg font-semibold text-sm hover:bg-gray-200 transition no-underline">Commencer</a>
                    @else
                        <a href="{{ route('register') }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2.5 rounded-lg font-semibold text-sm hover:bg-gray-200 transition no-underline">Commencer</a>
                    @endauth
                </div>
                <div class="bg-white border-2 border-primary-500 rounded-2xl p-7 text-center relative hover-lift shadow-lg">
                    <div class="absolute -top-3 left-1/2 -translate-x-1/2 bg-primary-600 text-white text-xs font-bold px-4 py-1 rounded-full">Populaire</div>
                    <div class="text-sm font-semibold text-primary-600 uppercase tracking-wider mb-3">Professionnel</div>
                    <div class="text-4xl font-extrabold text-gray-900 mb-1">14,99&euro;</div>
                    <div class="text-gray-400 text-sm mb-5">20 documents</div>
                    <ul class="text-left text-sm text-gray-600 space-y-2 mb-6">
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> 20 devis ou factures</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Export PDF</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> 0,75&euro; par document</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Credits sans expiration</li>
                    </ul>
                    @auth
                        <a href="{{ route('quote-tool.credits') }}" class="block w-full text-center bg-primary-600 text-white py-2.5 rounded-lg font-semibold text-sm hover:bg-primary-700 transition no-underline">Acheter ce pack</a>
                    @else
                        <a href="{{ route('register') }}" class="block w-full text-center bg-primary-600 text-white py-2.5 rounded-lg font-semibold text-sm hover:bg-primary-700 transition no-underline">Acheter ce pack</a>
                    @endauth
                </div>
                <div class="bg-white border border-gray-200 rounded-2xl p-7 text-center hover-lift">
                    <div class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Entreprise</div>
                    <div class="text-4xl font-extrabold text-gray-900 mb-1">29,99&euro;</div>
                    <div class="text-gray-400 text-sm mb-5">50 documents</div>
                    <ul class="text-left text-sm text-gray-600 space-y-2 mb-6">
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> 50 devis ou factures</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Export PDF</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> 0,60&euro; par document</li>
                        <li class="flex items-center gap-2"><i class="fas fa-check text-green-500"></i> Credits sans expiration</li>
                    </ul>
                    @auth
                        <a href="{{ route('quote-tool.credits') }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2.5 rounded-lg font-semibold text-sm hover:bg-gray-200 transition no-underline">Acheter ce pack</a>
                    @else
                        <a href="{{ route('register') }}" class="block w-full text-center bg-gray-100 text-gray-700 py-2.5 rounded-lg font-semibold text-sm hover:bg-gray-200 transition no-underline">Acheter ce pack</a>
                    @endauth
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ -->
    <section class="py-20 bg-white" id="faq">
        <div class="max-w-3xl mx-auto px-4">
            <div class="text-center mb-14">
                <h2 class="text-3xl font-bold text-gray-900 mb-3">Questions frequentes</h2>
            </div>
            <div class="space-y-4">
                @php
                    $faqs = [
                        ['q' => 'Comment creer un devis gratuit en ligne ?', 'a' => 'Inscrivez-vous gratuitement sur Lunamars, remplissez les informations de votre entreprise et de votre client, ajoutez vos lignes de produits ou services, et telechargez votre devis en PDF en un clic. Votre premier devis est entierement gratuit.'],
                        ['q' => 'L\'outil de devis est-il vraiment gratuit ?', 'a' => 'Oui, votre premier devis est 100% gratuit, sans engagement. Pour creer davantage de documents (devis ou factures), vous pouvez acheter des packs de credits a partir de 4,99 EUR pour 5 documents.'],
                        ['q' => 'Quels metiers peuvent utiliser cet outil ?', 'a' => 'Notre outil est adapte a tous les metiers et statuts : auto-entrepreneurs, artisans, freelances, consultants, commercants, professions liberales, petites entreprises... Quel que soit votre secteur d\'activite.'],
                        ['q' => 'Les devis generes sont-ils conformes a la legislation ?', 'a' => 'Nos devis et factures incluent toutes les mentions obligatoires : numero de document, coordonnees de l\'emetteur et du client, detail des prestations, montants HT/TVA/TTC, conditions de paiement.'],
                        ['q' => 'Puis-je personnaliser mes devis et factures ?', 'a' => 'Oui, vous pouvez personnaliser vos informations d\'entreprise (nom, adresse, SIRET), les informations client, les lignes de detail, le taux de TVA, les conditions et les notes.'],
                        ['q' => 'Comment payer pour des credits supplementaires ?', 'a' => 'Vous pouvez acheter des packs de credits par carte bancaire (paiement securise via Stripe) ou utiliser vos points Lunamars. Les credits n\'expirent jamais.'],
                    ];
                @endphp
                @foreach($faqs as $i => $faq)
                    <div class="border border-gray-200 rounded-xl overflow-hidden" x-data="{ open: false }">
                        <button onclick="this.parentElement.classList.toggle('faq-open'); this.querySelector('.faq-chevron').classList.toggle('rotate-180')" class="w-full flex items-center justify-between p-5 text-left bg-white hover:bg-gray-50 transition">
                            <span class="font-semibold text-gray-900 text-sm pr-4">{{ $faq['q'] }}</span>
                            <i class="fas fa-chevron-down text-gray-400 text-sm faq-chevron transition-transform duration-200"></i>
                        </button>
                        <div class="faq-answer px-5 pb-5 text-gray-600 text-sm leading-relaxed" style="display: none;">
                            {{ $faq['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- CTA Final -->
    <section class="py-20 bg-gradient-to-br from-primary-600 to-primary-800">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h2 class="text-3xl font-bold text-white mb-4">Pret a creer votre premier devis ?</h2>
            <p class="text-primary-200 mb-8 text-lg">C'est gratuit, rapide et sans engagement. Lancez-vous maintenant.</p>
            @auth
                <a href="{{ route('quote-tool.quote.create') }}" class="inline-flex items-center gap-2 bg-white text-primary-700 px-8 py-4 rounded-xl font-bold text-base hover:bg-primary-50 transition shadow-xl no-underline">
                    <i class="fas fa-file-alt"></i> Creer mon devis gratuit
                </a>
            @else
                <a href="{{ route('register') }}" class="inline-flex items-center gap-2 bg-white text-primary-700 px-8 py-4 rounded-xl font-bold text-base hover:bg-primary-50 transition shadow-xl no-underline">
                    <i class="fas fa-rocket"></i> Commencer gratuitement
                </a>
            @endauth
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-gray-400 py-10">
        <div class="max-w-6xl mx-auto px-4 text-center">
            <p class="text-sm">&copy; {{ date('Y') }} {{ config('app.name', 'Lunamars') }}. Tous droits reserves.</p>
            <div class="flex justify-center gap-6 mt-3 text-sm">
                <a href="{{ url('/') }}" class="hover:text-white transition no-underline text-gray-400">Accueil</a>
                <a href="#fonctionnalites" class="hover:text-white transition no-underline text-gray-400">Fonctionnalites</a>
                <a href="#tarifs" class="hover:text-white transition no-underline text-gray-400">Tarifs</a>
                <a href="#faq" class="hover:text-white transition no-underline text-gray-400">FAQ</a>
            </div>
        </div>
    </footer>

    <script>
        // FAQ toggle
        document.querySelectorAll('.faq-open .faq-answer, .faq-answer').forEach(function(el) {
            // Initially hide answers
        });
        document.addEventListener('click', function(e) {
            const btn = e.target.closest('[onclick*="faq-open"]');
            if (!btn) return;
            const parent = btn.parentElement;
            const answer = parent.querySelector('.faq-answer');
            if (answer) {
                answer.style.display = answer.style.display === 'none' ? 'block' : 'none';
            }
        });
    </script>
</body>
</html>
