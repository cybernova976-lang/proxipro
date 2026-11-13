@extends('pro.layout')
@section('title', 'Rapport d’activité - Espace Pro')
@section('topbar_title', 'Rapport d’activité')

@push('head')
<link rel="stylesheet" href="{{ asset('css/pro-premium.css') }}?v={{ @filemtime(public_path('css/pro-premium.css')) ?: 1 }}">
@endpush

@section('content')
<div class="pk-report">
    <header class="pk-report__head">
        <div>
            <p class="pk-report__kicker">Avantage Premium</p>
            <h1>Rapport d’activité{{ $locked ? '' : ' — '.$report['label'] }}</h1>
            <p>Ce que la plateforme a réellement mesuré pour vous, mois par mois. Aucun chiffre n’est estimé.</p>
        </div>
        @unless($locked)
            <form class="pk-report__months" method="GET" action="{{ route('pro.report') }}" data-report-months>
                <label for="pkReportMonth">Mois</label>
                <select id="pkReportMonth" name="mois">
                    @foreach($months as $month)
                        <option value="{{ $month['key'] }}" @selected($month['current'])>{{ ucfirst($month['label']) }}</option>
                    @endforeach
                </select>
                <button type="submit">Afficher</button>
            </form>
        @endunless
    </header>

    @if($locked)
        <section class="pk-report__locked" aria-labelledby="pkReportLocked">
            <h2 id="pkReportLocked">Un bilan mensuel de votre activité, compris dans Premium</h2>
            <ul>
                <li>le nombre de personnes qui ont consulté votre profil, comptées une fois par jour ;</li>
                <li>les ajouts en favoris de vos annonces et les nouveaux contacts reçus ;</li>
                <li>vos propositions, vos commandes, vos missions terminées et les montants versés ;</li>
                <li>les avis reçus, et pour les professionnels les devis et factures du mois ;</li>
                <li>la comparaison avec le mois précédent, et un récapitulatif par e-mail chaque début de mois.</li>
            </ul>
            @if($subscriptionsOpen)
                <a class="pk-report__cta" href="{{ route('pro.subscription') }}">
                    Découvrir Premium @if($monthlyPlan)— {{ $monthlyPlan['price'] }}{{ $monthlyPlan['period'] }}@endif
                    <i class="fas fa-arrow-right" aria-hidden="true"></i>
                </a>
                <p class="mt-3 mb-0">Sans engagement : l’abonnement se résilie à tout moment depuis « Mon abonnement ».</p>
            @else
                <p class="mb-0">{{ \App\Support\PlatformFeatures::proSubscriptionUnavailableMessage() }}</p>
            @endif
        </section>
    @else
        @if($report['in_progress'])
            <p class="pk-report__notice" role="status"><i class="fas fa-hourglass-half" aria-hidden="true"></i><span>Mois en cours : ces chiffres sont provisoires et évoluent chaque jour.</span></p>
        @elseif(! $report['has_activity'])
            <p class="pk-report__notice" role="status"><i class="fas fa-circle-info" aria-hidden="true"></i><span>Aucune activité n’a été mesurée sur ce mois. Un profil complet et une annonce publiée sont les deux premiers leviers pour être trouvé.</span></p>
        @endif

        @foreach($report['sections'] as $section)
            <section class="pk-report__section" aria-label="{{ $section['title'] }}">
                <h2>{{ $section['title'] }}</h2>
                <ul class="pk-report__grid">
                    @foreach($section['metrics'] as $metric)
                        @include('pro.partials.report-metric', ['metric' => $metric, 'previousLabel' => $report['previous_label']])
                    @endforeach
                </ul>
            </section>
        @endforeach

        <p class="pk-report__foot">
            Les comparaisons portent sur {{ $report['previous_label'] }}. « Non mesuré » signifie que la plateforme ne comptait pas encore cette donnée sur la période : elle n’est pas remplacée par une estimation.
            Les vues des annonces ne sont pas comptées et n’apparaissent donc pas ici.
            Le récapitulatif par e-mail se règle dans <a href="{{ route('settings.index') }}#notifications">Paramètres › Notifications</a>.
        </p>
    @endif
</div>
@endsection
