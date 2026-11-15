@if ($paginator->hasPages())
    <nav class="admin-pagination d-flex flex-wrap align-items-center justify-content-between gap-2" aria-label="Pagination">
        <p class="small text-muted mb-0">
            Affichage de <span class="fw-semibold">{{ $paginator->firstItem() }}</span>
            à <span class="fw-semibold">{{ $paginator->lastItem() }}</span>
            sur <span class="fw-semibold">{{ $paginator->total() }}</span>
        </p>

        <ul class="pagination pagination-sm flex-wrap mb-0">
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">Précédent</span></li>
            @else
                <li class="page-item"><a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev">Précédent</a></li>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <li class="page-item disabled d-none d-sm-block" aria-disabled="true"><span class="page-link">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page"><span class="page-link">{{ $page }}</span></li>
                        @else
                            <li class="page-item d-none d-sm-block"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <li class="page-item"><a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next">Suivant</a></li>
            @else
                <li class="page-item disabled" aria-disabled="true"><span class="page-link">Suivant</span></li>
            @endif
        </ul>
    </nav>
@endif
