@props(['items'])

<ul class="menu">
    @foreach ($items as $item)
        <li class="menu-item">
            @if (($item['action'] ?? '') === 'dispatch')
                <button
                    class="menu-button"
                    @click="$wire.dispatch('{{ $item['event'] }}')"
                >
                    {{ $item['label'] }}
                    {{-- Changed arrow symbol from ▶ to → and positioned it properly --}}
                    @if (!empty($item['children']))
                        <span class="submenu-arrow">→</span>
                    @endif
                </button>
            @elseif (isset($item['route']))
                <a href="{{ route($item['route']) }}" class="menu-button">
                    {{ $item['label'] }}
                    @if (!empty($item['children']))
                        <span class="submenu-arrow">→</span>
                    @endif
                </a>
            @else
                <span class="menu-button">
                    {{ $item['label'] }}
                    @if (!empty($item['children']))
                        <span class="submenu-arrow">→</span>
                    @endif
                </span>
            @endif

            {{-- Removed the separate arrow span outside the button --}}
            @if (!empty($item['children']))
                <x-menubar.menu :items="$item['children']" />
            @endif
        </li>
    @endforeach
</ul>
