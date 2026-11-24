{{--
    Bandeau de chiffres d'une liste (devis, factures) : une seule carte, une case par chiffre.
    Paramètres : $label (nom accessible), $items = [['label' => …, 'value' => …, 'tone' => info|warning|success|danger|neutral]].
--}}
<ul class="pro-kpis" aria-label="{{ $label }}" style="--pro-kpi-count: {{ count($items) }}">
    @foreach($items as $kpi)
        <li>
            <span class="pro-kpis__label"><span class="pro-kpis__dot pro-kpis__dot--{{ $kpi['tone'] ?? 'neutral' }}" aria-hidden="true"></span>{{ $kpi['label'] }}</span>
            <strong class="pro-kpis__value">{{ $kpi['value'] }}</strong>
        </li>
    @endforeach
</ul>
