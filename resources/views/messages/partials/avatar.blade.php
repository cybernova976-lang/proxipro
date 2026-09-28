<span class="msg-avatar" aria-hidden="true">
    @if($person?->avatar)<img src="{{ storage_url($person->avatar) }}" alt="">@else{{ mb_strtoupper(mb_substr($person?->name ?? '?', 0, 1)) }}@endif
</span>
