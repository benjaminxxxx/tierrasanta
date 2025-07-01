@props(['active', 'currentSelected','text','logo'])

@php
    $classes =
        $active ?? false
        ? 'group relative flex items-center gap-2.5 rounded-sm px-4 py-2 font-medium text-bodydark1 duration-300 ease-in-out hover:bg-graydark dark:hover:bg-meta-4 bg-graydark dark:bg-meta-4'
        : 'group relative flex items-center gap-2.5 rounded-sm px-4 py-2 font-medium text-bodydark1 duration-300 ease-in-out hover:bg-graydark dark:hover:bg-meta-4';
    $itemName = $attributes->get('name');
@endphp

<li>
    <a {{ $attributes->merge(['class' => $classes, 'href' => '#']) }}
        @click.prevent="selected = (selected === '{{ $itemName }}' ? '' : '{{ $itemName }}')">

        <div class="w-6 text-center">
            @isset($logo)
                <i class="{{ $logo }}"></i>
            @endisset
        </div>


        <span class="menu-text">
            {{$text ?? ''}}
        </span>

        <svg class="absolute right-4 top-1/2 -translate-y-1/2 fill-current menu-text"
            :class="{ 'rotate-180': (selected === '{{ $itemName }}') }" width="20" height="20" viewBox="0 0 20 20"
            fill="none" xmlns="http://www.w3.org/2000/svg">
            <path fill-rule="evenodd" clip-rule="evenodd"
                d="M4.41107 6.9107C4.73651 6.58527 5.26414 6.58527 5.58958 6.9107L10.0003 11.3214L14.4111 6.91071C14.7365 6.58527 15.2641 6.58527 15.5896 6.91071C15.915 7.23614 15.915 7.76378 15.5896 8.08922L10.5896 13.0892C10.2641 13.4147 9.73651 13.4147 9.41107 13.0892L4.41107 8.08922C4.08563 7.76378 4.08563 7.23614 4.41107 6.9107Z"
                fill="" />
        </svg>
    </a>
    <div class="translate transform overflow-hidden menu-text"
        :class="(selected === '{{ $itemName }}') ? 'block' : 'hidden'">
        <ul class="mb-5.5 mt-4 flex flex-col gap-2.5 pl-6">
            {{ $children ?? $slot }}
        </ul>
    </div>
</li>