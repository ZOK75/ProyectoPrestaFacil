@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}" class="flex items-center justify-between">
        <div class="flex justify-between flex-1 sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center px-4 py-2 text-xs font-semibold text-slate-500 bg-slate-900 border border-slate-800 cursor-default rounded-xl">
                    Anterior
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" class="relative inline-flex items-center px-4 py-2 text-xs font-semibold text-slate-300 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    Anterior
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" class="relative inline-flex items-center px-4 py-2 ml-3 text-xs font-semibold text-slate-300 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 hover:text-white transition">
                    Siguiente
                </a>
            @else
                <span class="relative inline-flex items-center px-4 py-2 ml-3 text-xs font-semibold text-slate-500 bg-slate-900 border border-slate-800 cursor-default rounded-xl">
                    Siguiente
                </span>
            @endif
        </div>

        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            <div>
                <p class="text-xs text-slate-400">
                    Mostrando
                    @if ($paginator->firstItem())
                        <span class="font-bold text-slate-200">{{ $paginator->firstItem() }}</span>
                        a
                        <span class="font-bold text-slate-200">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    de
                    <span class="font-bold text-slate-200">{{ $paginator->total() }}</span>
                    resultados
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex rounded-xl shadow-sm space-x-1">
                    {{-- Previous Page Link --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-600 bg-slate-900 border border-slate-800 cursor-default rounded-xl" aria-hidden="true">
                                &larr; Anterior
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-300 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 hover:text-white transition" aria-label="{{ __('pagination.previous') }}">
                            &larr; Anterior
                        </a>
                    @endif

                    {{-- Pagination Elements --}}
                    @foreach ($elements as $element)
                        {{-- "Three Dots" Separator --}}
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-500 bg-slate-900 border border-slate-800 cursor-default rounded-xl">{{ $element }}</span>
                            </span>
                        @endif

                        {{-- Array Of Links --}}
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex items-center px-3.5 py-2 text-xs font-bold text-white bg-indigo-600 border border-indigo-500 rounded-xl shadow-lg shadow-indigo-600/30">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" class="relative inline-flex items-center px-3.5 py-2 text-xs font-semibold text-slate-300 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 hover:text-white transition" aria-label="{{ __('Go to page :page', ['page' => $page]) }}">
                                        {{ $page }}
                                    </a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next Page Link --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative inline-flex items-center px-3 py-2 text-xs font-semibold text-slate-300 bg-slate-900 border border-slate-800 rounded-xl hover:bg-slate-800 hover:text-white transition" aria-label="{{ __('pagination.next') }}">
                            Siguiente &rarr;
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex items-center px-3 py-2 text-xs font-medium text-slate-600 bg-slate-900 border border-slate-800 cursor-default rounded-xl" aria-hidden="true">
                                Siguiente &rarr;
                            </span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
