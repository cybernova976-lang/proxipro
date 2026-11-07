@php
    $pkNeedChoices = [
        ['label' => 'Réparer une fuite', 'category' => 'Bricolage & Travaux', 'subcategory' => 'Plombier', 'icon' => 'fa-tint', 'tone' => 'blue'],
        ['label' => 'Faire le ménage', 'category' => 'Nettoyage & Entretien', 'subcategory' => 'Femme/Homme de ménage', 'icon' => 'fa-broom', 'tone' => 'mint'],
        ['label' => 'Entretenir mon jardin', 'category' => 'Jardinage & Extérieur', 'subcategory' => 'Jardinier', 'icon' => 'fa-leaf', 'tone' => 'green'],
        ['label' => 'Réparer mon ordinateur', 'category' => 'Informatique & Tech', 'subcategory' => 'Technicien informatique', 'icon' => 'fa-laptop', 'tone' => 'violet'],
    ];
    $pkEnabledServices = \App\Support\MarketplaceCategoryRegistry::enabledServices();
    $pkNeedChoices = array_filter($pkNeedChoices, fn ($need) => isset($pkEnabledServices[$need['category']]) && in_array($need['subcategory'], $pkEnabledServices[$need['category']]['subcategories'] ?? [], true));
@endphp
<section class="pk-needs" id="besoin" aria-labelledby="pkNeedsTitle">
    <div class="pk-needs__heading">
        <div><h2 id="pkNeedsTitle">Un besoin ? On vous aide à le décrire.</h2><p>Choisissez un service. Des exemples vous aideront à écrire votre demande.</p></div>
        <a href="{{ route('demand.create') }}">Autre besoin <i class="fas fa-arrow-right" aria-hidden="true"></i></a>
    </div>
    <div class="pk-needs__choices">
        @foreach($pkNeedChoices as $need)
            <a class="pk-need pk-need--{{ $need['tone'] }}" href="{{ route('demand.create', ['category' => $need['category'], 'subcategory' => $need['subcategory']]) }}">
                <span><i class="fas {{ $need['icon'] }}" aria-hidden="true"></i></span>
                <strong>{{ $need['label'] }}</strong>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </a>
        @endforeach
    </div>
</section>
