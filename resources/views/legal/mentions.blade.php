@extends('layouts.app')

@section('title', 'Mentions légales - ' . config('app.name', 'Lunamars'))
@section('meta_description', 'Informations légales relatives à l’éditeur et à l’hébergeur de ' . config('app.name', 'Lunamars') . '.')

@section('content')
@php
    $legal = [
        'entity_name' => \App\Support\PlatformFeatures::legalValue('legal_entity_name', 'entity_name'),
        'entity_form' => \App\Support\PlatformFeatures::legalValue('legal_entity_form', 'entity_form'),
        'registration_number' => \App\Support\PlatformFeatures::legalValue('legal_registration_number', 'registration_number'),
        'vat_number' => \App\Support\PlatformFeatures::legalValue('legal_vat_number', 'vat_number'),
        'address' => \App\Support\PlatformFeatures::legalValue('legal_address', 'address'),
        'publication_director' => \App\Support\PlatformFeatures::legalValue('legal_publication_director', 'publication_director'),
    ];
    $requiredLegalFields = collect([
        'Raison sociale' => $legal['entity_name'],
        'Adresse' => $legal['address'],
        'Directeur de publication' => $legal['publication_director'],
        'Hébergeur' => config('legal.host_name'),
        'Adresse de l’hébergeur' => config('legal.host_address'),
    ]);
@endphp
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <nav aria-label="breadcrumb" class="mb-3"><ol class="breadcrumb small"><li class="breadcrumb-item"><a href="{{ url('/') }}">Accueil</a></li><li class="breadcrumb-item active">Mentions légales</li></ol></nav>
            <div class="card border-0 shadow-sm"><div class="card-body p-4 p-md-5">
                <h1 class="mb-4">Mentions légales</h1>

                @if($requiredLegalFields->contains(fn ($value) => blank($value)))
                    <div class="alert alert-warning"><strong>Document à finaliser avant la mise en production.</strong> Certaines informations obligatoires sur l’éditeur ou l’hébergeur ne sont pas encore configurées.</div>
                @endif

                <h4 class="mt-4">1. Éditeur du site</h4>
                <dl class="row">
                    <dt class="col-sm-4">Raison sociale</dt><dd class="col-sm-8">{{ $legal['entity_name'] ?: 'À renseigner' }}</dd>
                    @if($legal['entity_form'])<dt class="col-sm-4">Forme juridique</dt><dd class="col-sm-8">{{ $legal['entity_form'] }}</dd>@endif
                    @if($legal['registration_number'])<dt class="col-sm-4">Immatriculation</dt><dd class="col-sm-8">{{ $legal['registration_number'] }}</dd>@endif
                    @if($legal['vat_number'])<dt class="col-sm-4">TVA</dt><dd class="col-sm-8">{{ $legal['vat_number'] }}</dd>@endif
                    <dt class="col-sm-4">Adresse</dt><dd class="col-sm-8">{{ $legal['address'] ?: 'À renseigner' }}</dd>
                    <dt class="col-sm-4">Contact</dt><dd class="col-sm-8">@if(config('site.support_email'))<a href="mailto:{{ config('site.support_email') }}">{{ config('site.support_email') }}</a>@else À renseigner @endif</dd>
                    <dt class="col-sm-4">Publication</dt><dd class="col-sm-8">{{ $legal['publication_director'] ?: 'À renseigner' }}</dd>
                </dl>

                <h4 class="mt-4">2. Hébergement</h4>
                <p><strong>{{ config('legal.host_name') ?: 'Hébergeur à renseigner' }}</strong><br>{{ config('legal.host_address') ?: 'Adresse à renseigner' }}</p>

                <h4 class="mt-4">3. Rôle de la plateforme</h4>
                <p>{{ config('app.name', 'Lunamars') }} fournit un service de mise en relation. Les utilisateurs restent responsables de leurs annonces, propositions, échanges, obligations professionnelles et de la bonne exécution des prestations conclues entre eux.</p>

                <h4 class="mt-4">4. Propriété intellectuelle</h4>
                <p>Les éléments propres à la plateforme sont protégés par les règles applicables à la propriété intellectuelle. Les contenus publiés par les utilisateurs restent sous leur responsabilité et ne doivent pas porter atteinte aux droits de tiers.</p>

                <h4 class="mt-4">5. Données personnelles et signalement</h4>
                <p>Consultez la <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>. Pour signaler un contenu ou contacter l’éditeur, utilisez le <a href="{{ route('contact.index') }}">formulaire de contact</a>.</p>

                <p class="mt-5 text-muted small">Dernière mise à jour : {{ config('legal.last_updated') ?: date('d/m/Y') }}</p>
            </div></div>
        </div>
    </div>
</div>
@endsection
