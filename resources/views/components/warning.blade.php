<div {{ $attributes->merge(['class' => 'flex items-center p-4 text-yellow-800 bg-yellow-100 border border-yellow-300 rounded-lg']) }}>
    <i class="fa fa-exclamation-triangle text-yellow-600 mr-3"></i>
    <div>
        {{ $slot }}
    </div>
</div>