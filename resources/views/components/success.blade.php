<div {{ $attributes->merge(['class' => 'flex items-center p-4 text-lime-800 bg-lime-100 border border-lime-300 rounded-lg']) }}>
    <i class="fa fa-check-circle text-lime-600 mr-3"></i>
    <div>
        {{ $slot }}
    </div>
</div>