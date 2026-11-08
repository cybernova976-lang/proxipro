{{-- Moment : le jour puis le moment de la journée, sur un même écran ; « Peu importe » est déjà coché. --}}
@php
    $pkWhenChoices = ['today' => 'Aujourd’hui', 'this_week' => 'Cette semaine', 'this_month' => 'Ce mois-ci', 'no_rush' => 'Pas pressé', 'date' => 'Choisir une date'];
    $pkWindowChoices = ['flexible' => 'Peu importe', 'morning' => 'Matin', 'afternoon' => 'Après-midi', 'evening' => 'Soir'];
    $pkOldWindow = old('time_window', 'flexible');
@endphp
<fieldset class="pk-guided__step" data-pk-step="{{ $n }}" data-pk-when>
    <legend class="pk-guided__legend" tabindex="-1">Quand ?</legend>
    <button type="button" class="pk-listen" data-pk-listen hidden>Écouter</button>
    <div class="pk-answers pk-answers--text pk-answers--many">
        @foreach($pkWhenChoices as $pkValue => $pkLabel)
            <label class="pk-answer pk-answer--text">
                <input type="radio" name="when" value="{{ $pkValue }}" @checked(old('when') === $pkValue)>
                <span class="pk-answer__label">{{ $pkLabel }}</span>
            </label>
        @endforeach
    </div>
    <div class="pk-guided__field" data-pk-date>
        <label for="pkDesiredDate">Date (si vous avez choisi « Choisir une date »)</label>
        <input type="date" id="pkDesiredDate" name="desired_date_choice" min="{{ today()->toDateString() }}" value="{{ old('desired_date_choice') }}">
    </div>
    <p class="pk-guided__subtitle" id="pkWindowTitle">À quel moment ?</p>
    <div class="pk-answers pk-answers--text" role="radiogroup" aria-labelledby="pkWindowTitle">
        @foreach($pkWindowChoices as $pkValue => $pkLabel)
            <label class="pk-answer pk-answer--text">
                <input type="radio" name="time_window" value="{{ $pkValue }}" @checked($pkOldWindow === $pkValue)>
                <span class="pk-answer__label">{{ $pkLabel }}</span>
            </label>
        @endforeach
    </div>
</fieldset>
