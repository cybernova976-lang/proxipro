{{-- Une question par écran : de grosses réponses à toucher, et toujours « Je ne sais pas » en dernier. --}}
@php
    $pkOld = old('answers.'.$qKey);
    $pkMany = count($question['options']) + 1 >= 5;
@endphp
<fieldset class="pk-guided__step" data-pk-step="{{ $n }}" data-pk-question="{{ $qKey }}">
    <legend class="pk-guided__legend" tabindex="-1">{{ $question['label'] }}</legend>
    <button type="button" class="pk-listen" data-pk-listen hidden>Écouter</button>
    <div class="pk-answers{{ $pkMany ? ' pk-answers--many' : '' }}">
        @foreach($question['options'] as $optKey => $option)
            <label class="pk-answer">
                <input type="radio" name="answers[{{ $qKey }}]" value="{{ $optKey }}" @checked($pkOld === (string) $optKey)>
                <img src="{{ $option['picto_url'] }}" alt="" width="40" height="40">
                <span class="pk-answer__label">{{ $option['label'] }}</span>
            </label>
        @endforeach
        <label class="pk-answer pk-answer--unknown">
            <input type="radio" name="answers[{{ $qKey }}]" value="unknown" @checked($pkOld === 'unknown')>
            <img src="{{ $question['unknown']['picto_url'] }}" alt="" width="40" height="40">
            <span class="pk-answer__label">{{ $question['unknown']['label'] }}</span>
        </label>
    </div>
</fieldset>
