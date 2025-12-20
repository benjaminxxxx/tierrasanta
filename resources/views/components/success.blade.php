
<div {{ $attributes->merge(['class' => 'flex items-center p-4 text-green-800 bg-green-100 border border-green-300 rounded-lg']) }}>
    <i class="fa fa-check-circle text-white-600 mr-3"></i>
    <div>
        {{ $slot }}
    </div>
</div>