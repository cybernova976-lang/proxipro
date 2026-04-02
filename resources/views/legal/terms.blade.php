@extends('layouts.app')

@section('title', 'Conditions d\'utilisation - ProxiPro')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb small">
                    <li class="breadcrumb-item"><a href="{{ url('/') }}" class="text-decoration-none"><i class="fas fa-home me-1"></i>Accueil</a></li>
                    <li class="breadcrumb-item active">Conditions d'utilisation</li>
                </ol>
            </nav>
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <h1 class="mb-4">Conditions d'utilisation</h1>
                    
                    <h4 class="mt-4">1. Objet</h4>
                    <p>Les présentes conditions générales d'utilisation (CGU) ont pour objet de définir les règles d'utilisation de la plateforme ProxiPro.</p>
                    
                    <h4 class="mt-4">2. Acceptation des conditions</h4>
                    <p>L'utilisation de la plateforme ProxiPro implique l'acceptation pleine et entière des présentes CGU. Si vous n'acceptez pas ces conditions, veuillez ne pas utiliser notre service.</p>
                    
                    <h4 class="mt-4">3. Inscription</h4>
                    <p>Pour utiliser certaines fonctionnalités de la plateforme, vous devez créer un compte. Vous vous engagez à fournir des informations exactes et à les maintenir à jour.</p>
                    
                    <h4 class="mt-4">4. Services proposés</h4>
                    <p>ProxiPro est une plateforme de mise en relation entre particuliers et professionnels. Elle permet :</p>
                    <ul>
                        <li>La publication d'annonces de services</li>
                        <li>La recherche de prestataires</li>
                        <li>La mise en relation entre utilisateurs</li>
                    </ul>
                    
                    <h4 class="mt-4">5. Responsabilité des utilisateurs</h4>
                    <p>Chaque utilisateur est responsable du contenu qu'il publie sur la plateforme. Tout contenu illégal, diffamatoire ou portant atteinte aux droits d'autrui est interdit.</p>
                    
                    <h4 class="mt-4">6. Propriété intellectuelle</h4>
                    <p>Le contenu publié par les utilisateurs reste leur propriété. Toutefois, en publiant sur ProxiPro, vous accordez une licence d'utilisation à la plateforme.</p>
                    
                    <h4 class="mt-4">7. Limitation de responsabilité</h4>
                    <p>ProxiPro agit en tant qu'intermédiaire et ne peut être tenu responsable des transactions effectuées entre utilisateurs.</p>
                    
                    <h4 class="mt-4">8. Modification des CGU</h4>
                    <p>ProxiPro se réserve le droit de modifier les présentes CGU à tout moment. Les utilisateurs seront informés de toute modification substantielle.</p>
                    
                    <div class="mt-5 text-muted">
                        <p><small>Dernière mise à jour : {{ date('d/m/Y') }}</small></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
