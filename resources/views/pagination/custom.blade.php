@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi Halaman" class="pagination-wrapper">
        <div class="pagination-info">
            Menampilkan <span>{{ $paginator->firstItem() }}</span> - <span>{{ $paginator->lastItem() }}</span> dari <span>{{ $paginator->total() }}</span> data
        </div>

        <div class="pagination-controls">
            {{-- Tombol Sebelumnya --}}
            @if ($paginator->onFirstPage())
                <span class="pagination-btn disabled" aria-disabled="true" title="Halaman pertama">
                    <svg class="pagination-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span>Sebelumnya</span>
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="pagination-btn" title="Halaman sebelumnya">
                    <svg class="pagination-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                    </svg>
                    <span>Sebelumnya</span>
                </a>
            @endif

            {{-- Nomor Halaman --}}
            <div class="pagination-numbers">
                @foreach ($elements as $element)
                    {{-- Separator titik tiga --}}
                    @if (is_string($element))
                        <span class="pagination-ellipsis">{{ $element }}</span>
                    @endif

                    {{-- Link Nomor --}}
                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span class="pagination-number active" aria-current="page">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="pagination-number">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach
            </div>

            {{-- Tombol Selanjutnya --}}
            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="pagination-btn" title="Halaman berikutnya">
                    <span>Selanjutnya</span>
                    <svg class="pagination-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </a>
            @else
                <span class="pagination-btn disabled" aria-disabled="true" title="Halaman terakhir">
                    <span>Selanjutnya</span>
                    <svg class="pagination-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                    </svg>
                </span>
            @endif
        </div>
    </nav>
@endif
