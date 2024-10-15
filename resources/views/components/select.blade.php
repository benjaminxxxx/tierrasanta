@props(['disabled' => false])

<select {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'w-full pr-8 rounded-lg border border-slate-400 bg-transparent py-2 pl-5 outline-none focus:border-primary focus-visible:shadow-none dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary focus:ring-0']) !!}>
{{$slot}}
</select>
