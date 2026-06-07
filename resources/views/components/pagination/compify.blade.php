@if ($paginator->hasPages())
    <nav class="compify-pagination" role="navigation" aria-label="Pagination Navigation">
        <div class="compify-pagination__info">
            Showing {{ $paginator->firstItem() }} to {{ $paginator->lastItem() }} of {{ $paginator->total() }} results
        </div>

        <div class="compify-pagination__links">
            {{-- Previous Page --}}
            @if ($paginator->onFirstPage())
                <span class="compify-pagination__btn is-disabled">‹</span>
            @else
                <button
                    type="button"
                    class="compify-pagination__btn"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    rel="prev"
                    aria-label="Previous"
                >
                    ‹
                </button>
            @endif

            {{-- Page Numbers --}}
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="compify-pagination__dots">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="compify-pagination__page is-active">{{ $page }}</span>
                        @else
                            <button
                                type="button"
                                class="compify-pagination__page"
                                wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')"
                                wire:loading.attr="disabled"
                            >
                                {{ $page }}
                            </button>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page --}}
            @if ($paginator->hasMorePages())
                <button
                    type="button"
                    class="compify-pagination__btn"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled"
                    rel="next"
                    aria-label="Next"
                >
                    ›
                </button>
            @else
                <span class="compify-pagination__btn is-disabled">›</span>
            @endif
        </div>
    </nav>
@endif