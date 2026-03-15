<nav class="flex px-5 py-3 text-muted-foreground border border-border rounded-lg bg-muted"
    aria-label="Breadcrumb">

    <ol class="inline-flex items-center space-x-1 md:space-x-2 rtl:space-x-reverse">

        {{-- HOME --}}
        <li class="inline-flex items-center">
            <a href="{{ route('dashboard') }}"
                class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 dark:text-gray-400 dark:hover:text-white">

                <svg class="w-3 h-3 me-2.5" xmlns="http://www.w3.org/2000/svg"
                    fill="currentColor" viewBox="0 0 20 20">
                    <path
                        d="m19.707 9.293-2-2-7-7a1 1 0 0 0-1.414 0l-7 7-2 2a1 1 0 0 0 1.414 1.414L2 10.414V18a2 2 0 0 0 2 2h3a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h3a2 2 0 0 0 2-2v-7.586l.293.293a1 1 0 0 0 1.414-1.414Z"/>
                </svg>

                Inicio
            </a>
        </li>

        {{-- ITEMS DINAMICOS --}}
        @foreach ($items as $item)

            <li>
                <div class="flex items-center">

                    {{-- FLECHA --}}
                    <svg class="rtl:rotate-180 block w-3 h-3 mx-1 text-gray-400"
                        xmlns="http://www.w3.org/2000/svg"
                        fill="none"
                        viewBox="0 0 6 10">
                        <path stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="m1 9 4-4-4-4"/>
                    </svg>

                    {{-- LINK O TEXTO --}}
                    @if (isset($item['route']))
                        <a href="{{ route($item['route']) }}"
                            class="ms-1 text-sm font-medium text-gray-700 hover:text-blue-600 md:ms-2 dark:text-gray-400 dark:hover:text-white">
                            {{ $item['label'] }}
                        </a>
                    @else
                        <span class="ms-1 text-sm font-medium text-gray-500 md:ms-2 dark:text-gray-400">
                            {{ $item['label'] }}
                        </span>
                    @endif

                </div>
            </li>

        @endforeach

    </ol>

</nav>