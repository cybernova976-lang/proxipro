{{-- En-tête d'une colonne : titre, sous-titre et un seul chiffre, le nombre d'éléments. --}}
<header class="pipeline-stage-header">
    <div class="pipeline-stage-title">
        <h2 id="pipeline-{{ $stage }}-title">{{ $title }}</h2>
        <p>{{ $subtitle }}</p>
    </div>
    <span class="pipeline-count" aria-label="{{ $count }} élément{{ $count > 1 ? 's' : '' }}">{{ $count }}</span>
</header>
