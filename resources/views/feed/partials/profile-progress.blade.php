{{--
    Zone 3 · progression du profil — une seule action suivante.

    La ligne disparait entierement quand il n'y a plus rien a faire.
    Priorite : la verification d'identite d'abord (c'est elle qui produit
    le badge visible sur les cartes), puis la suggestion pro la plus utile.
--}}
@php
    $pkNext = null;

    // 1. Verification d'identite — le signal le plus rentable pour les deux roles.
    if ($pkVerificationStatus === 'returned') {
        $pkNext = [
            'icon'  => 'fas fa-exclamation-triangle',
            'tone'  => 'warn',
            'title' => 'Corrections demandées sur votre vérification',
            'desc'  => 'Un document doit être renvoyé pour obtenir le badge vérifié.',
            'cta'   => 'Corriger',
            'url'   => route('verification.index'),
        ];
    } elseif ($pkVerificationStatus === 'rejected') {
        $pkNext = [
            'icon'  => 'fas fa-times-circle',
            'tone'  => 'warn',
            'title' => 'Votre vérification a été refusée',
            'desc'  => 'Vous pouvez soumettre une nouvelle demande dès maintenant.',
            'cta'   => 'Recommencer',
            'url'   => route('verification.index'),
        ];
    } elseif ($pkVerificationStatus === 'pending') {
        $pkNext = [
            'icon'  => 'fas fa-hourglass-half',
            'tone'  => 'info',
            'title' => 'Vérification en cours d’examen',
            'desc'  => 'Vos documents sont entre nos mains, rien de plus à faire pour l’instant.',
            'cta'   => null,
            'url'   => route('verification.index'),
        ];
    } elseif (! $pkIsVerified) {
        $pkNext = [
            'icon'  => 'fas fa-shield-alt',
            'tone'  => 'ok',
            'title' => 'Vérifiez votre identité',
            'desc'  => 'Le badge « Vérifié » s’affiche sur vos annonces et sur votre profil : c’est ce que regardent les autres membres avant de répondre.',
            'cta'   => 'Vérifier mon profil',
            'url'   => route('verification.index'),
        ];
    } elseif (($pkRole ?? 'client') === 'provider' && ! empty($pkSuggestion)) {
        // 2. Sinon, pour un prestataire, la premiere suggestion de son profil pro.
        $pkNext = [
            'icon'  => $pkSuggestion['icon'] ?? 'fas fa-user-pen',
            'tone'  => 'ok',
            'title' => $pkSuggestion['title'] ?? 'Complétez votre profil professionnel',
            'desc'  => $pkSuggestion['description'] ?? 'Des informations à jour vous rendent plus visible auprès des clients.',
            'cta'   => $pkSuggestion['action_label'] ?? 'Continuer',
            'url'   => $pkSuggestionUrl,
        ];
    }
@endphp

@if($pkNext)
    <div class="pk-card pk-progress">
        <span class="pk-progress__ico"
              @if($pkNext['tone'] === 'warn') style="background: var(--pk-warn-bg); color: var(--pk-warn);"
              @elseif($pkNext['tone'] === 'info') style="background: var(--pk-50); color: var(--pk-700);"
              @endif>
            <i class="{{ $pkNext['icon'] }}"></i>
        </span>

        <div class="pk-progress__txt">
            <b>{{ $pkNext['title'] }}</b>
            <p>{{ $pkNext['desc'] }}</p>
            @if(($pkRole ?? 'client') === 'provider' && $pkCompletion > 0 && $pkCompletion < 100)
                <div class="pk-bar"><i style="width: {{ $pkCompletion }}%"></i></div>
            @endif
        </div>

        @if(($pkRole ?? 'client') === 'provider' && $pkCompletion > 0 && $pkCompletion < 100)
            <span class="pk-progress__pct">{{ $pkCompletion }} %</span>
        @endif

        @if($pkNext['cta'])
            <a href="{{ $pkNext['url'] }}" class="pk-btn-soft">
                {{ $pkNext['cta'] }} <i class="fas fa-arrow-right"></i>
            </a>
        @endif
    </div>
@endif
