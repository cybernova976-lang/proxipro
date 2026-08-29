@php
    $currentPage = $paginator->currentPage();
    $lastPage = $paginator->lastPage();
    $windowStart = max(1, $currentPage - 2);
    $windowEnd = min($lastPage, $currentPage + 2);
@endphp

<nav class="ads-pagination-shell" aria-label="Pagination des annonces">
    <p class="ads-pagination-summary">
        Affichage de {{ $paginator->firstItem() }} à {{ $paginator->lastItem() }} sur {{ $paginator->total() }} annonces
    </p>

    <div class="ads-pagination">
        @if($paginator->onFirstPage())
            <span class="ads-pagination__disabled" aria-disabled="true">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                <span class="ads-pagination__label">Précédent</span>
            </span>
        @else
            <a class="ads-pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                <span class="ads-pagination__label">Précédent</span>
            </a>
        @endif

        <span class="ads-pagination__mobile-page">Page {{ $currentPage }} sur {{ $lastPage }}</span>

        <span class="ads-pagination__pages" aria-label="Pages disponibles">
            @if($windowStart > 1)
                <a class="ads-pagination__link" href="{{ $paginator->url(1) }}" aria-label="Page 1">1</a>
                @if($windowStart > 2)
                    <span class="ads-pagination__ellipsis" aria-hidden="true">…</span>
                @endif
            @endif

            @foreach(range($windowStart, $windowEnd) as $page)
                @if($page === $currentPage)
                    <span class="ads-pagination__current" aria-current="page">{{ $page }}</span>
                @else
                    <a class="ads-pagination__link" href="{{ $paginator->url($page) }}" aria-label="Page {{ $page }}">{{ $page }}</a>
                @endif
            @endforeach

            @if($windowEnd < $lastPage)
                @if($windowEnd < $lastPage - 1)
                    <span class="ads-pagination__ellipsis" aria-hidden="true">…</span>
                @endif
                <a class="ads-pagination__link" href="{{ $paginator->url($lastPage) }}" aria-label="Page {{ $lastPage }}">{{ $lastPage }}</a>
            @endif
        </span>

        @if($paginator->hasMorePages())
            <a class="ads-pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next">
                <span class="ads-pagination__label">Suivant</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </a>
        @else
            <span class="ads-pagination__disabled" aria-disabled="true">
                <span class="ads-pagination__label">Suivant</span>
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </span>
        @endif
    </div>
</nav>
