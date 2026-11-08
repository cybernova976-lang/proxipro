{{-- En-tête du parcours guidé : retour, étape courante, besoin choisi et erreurs en phrases simples. --}}
@php
    $pkErrorMessages = [];
    if ($errors->any()) {
        $pkKnown = [
            'situation_key' => null,
            'answers' => null,
            'when' => null,
            'desired_date_choice' => null,
            'location' => 'Choisissez votre commune.',
            'city' => 'Choisissez votre commune.',
            'country' => 'Choisissez votre pays ou territoire.',
            'price' => 'Indiquez un prix en euros, ou choisissez « À discuter ».',
            'price_type' => 'Choisissez « À discuter » ou proposez un prix.',
            'publication_confirmed' => 'Cochez la case « Ma demande sera visible pendant 30 jours » avant de publier.',
            'title' => 'Une demande identique a déjà été publiée aujourd’hui.',
        ];
        foreach ($errors->getMessages() as $pkField => $pkMessages) {
            $pkRoot = explode('.', $pkField)[0];
            if (str_starts_with($pkField, 'photos')) {
                $pkErrorMessages[] = $pkMessages[0];
            } elseif (array_key_exists($pkRoot, $pkKnown)) {
                $pkErrorMessages[] = $pkKnown[$pkRoot] ?? $pkMessages[0];
            } else {
                $pkErrorMessages[] = 'Une information manque. Vérifiez vos réponses, puis publiez de nouveau.';
            }
        }
        $pkErrorMessages = array_values(array_unique($pkErrorMessages));
    }
@endphp
<header class="pk-guided__header">
    <div class="pk-guided__topline">
        <a class="pk-guided__back" href="{{ route('feed') }}" data-pk-back>← Retour</a>
        <p class="pk-guided__count" aria-live="polite">Étape <span data-pk-step-current>1</span> sur <span data-pk-step-total>{{ $pkStepTotal }}</span></p>
    </div>
    <progress class="pk-guided__progress" max="{{ $pkStepTotal }}" value="1" aria-hidden="true"></progress>
    <p class="pk-guided__need">
        Votre besoin : <strong>{{ $pkSituation['label'] }}</strong> · <a href="{{ route('feed') }}#pkProblemPicker">changer</a>
    </p>
    <div class="pk-guided__alert" role="alert" @if($pkErrorMessages === []) hidden @endif>
        @foreach($pkErrorMessages as $pkMessage)
            <p>{{ $pkMessage }}</p>
        @endforeach
    </div>
    <p class="pk-guided__resume" role="status" data-pk-resume-msg hidden></p>
</header>
