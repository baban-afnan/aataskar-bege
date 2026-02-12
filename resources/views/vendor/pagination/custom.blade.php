@if ($paginator->hasPages())
    <nav aria-label="Page navigation" class="mt-4">
        <ul class="pagination pagination-md justify-content-center m-0">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                    <span class="page-link border-0 rounded-start-pill text-secondary bg-light" aria-hidden="true">
                        <i class="bi bi-chevron-left"></i>
                    </span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link border-0 rounded-start-pill text-primary bg-white shadow-sm hover-shadow" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="@lang('pagination.previous')">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if (is_string($element))
                    <li class="page-item disabled" aria-disabled="true"><span class="page-link border-0 text-muted bg-white">{{ $element }}</span></li>
                @endif

                {{-- Array Of Links --}}
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="page-item active" aria-current="page">
                                <span class="page-link border-0 shadow-sm fw-bold" style="background-color: #0db4bd; color: white;">{{ $page }}</span>
                            </li>
                        @else
                            <li class="page-item">
                                <a class="page-link border-0 text-dark bg-white shadow-sm hover-shadow hover-primary" href="{{ $url }}">{{ $page }}</a>
                            </li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link border-0 rounded-end-pill text-primary bg-white shadow-sm hover-shadow" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="@lang('pagination.next')">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                    <span class="page-link border-0 rounded-end-pill text-secondary bg-light" aria-hidden="true">
                        <i class="bi bi-chevron-right"></i>
                    </span>
                </li>
            @endif
        </ul>
    </nav>
    
    <style>
        .page-link {
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 3px;
            border-radius: 50% !important;
            transition: all 0.2s ease;
        }
        .page-link:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 180, 189, 0.25);
        }
        .hover-shadow:hover {
            transform: translateY(-2px);
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.1) !important;
        }
        .hover-primary:hover {
            color: #0db4bd !important;
            background-color: #f8f9fa !important;
        }
    </style>
@endif
