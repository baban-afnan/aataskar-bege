@if ($paginator->hasPages())
    <nav class="d-flex justify-content-center my-3">
        <ul class="pagination pagination-sm flex-wrap shadow-sm mb-0">

            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true">
                    <span class="page-link rounded-start">&laquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link rounded-start" href="{{ $paginator->previousPageUrl() }}"
                        rel="prev">&laquo;</a>
                </li>
            @endif

            {{-- Always show first page --}}
            @if ($paginator->currentPage() > 3)
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->url(1) }}">1</a>
                </li>
                @if ($paginator->currentPage() > 4)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
            @endif

            {{-- Pages around current page --}}
            @for ($i = max(1, $paginator->currentPage() - 1); $i <= min($paginator->lastPage(), $paginator->currentPage() + 1); $i++)
                @if ($i == $paginator->currentPage())
                    <li class="page-item active" aria-current="page">
                        <span class="page-link bg-primary border-primary text-white">{{ $i }}</span>
                    </li>
                @else
                    <li class="page-item"><a class="page-link" href="{{ $paginator->url($i) }}">{{ $i }}</a>
                    </li>
                @endif
            @endfor

            {{-- Trailing ellipsis + last page --}}
            @if ($paginator->currentPage() < $paginator->lastPage() - 2)
                @if ($paginator->currentPage() < $paginator->lastPage() - 3)
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                @endif
                <li class="page-item">
                    <a class="page-link"
                        href="{{ $paginator->url($paginator->lastPage()) }}">{{ $paginator->lastPage() }}</a>
                </li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link rounded-end" href="{{ $paginator->nextPageUrl() }}" rel="next">&raquo;</a>
                </li>
            @else
                <li class="page-item disabled">
                    <span class="page-link rounded-end">&raquo;</span>
                </li>
            @endif

        </ul>
    </nav>
@endif
