@props(['disabled' => false])

<input {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'w-full mt-2 rounded-lg border border-slate-400 bg-transparent py-3 px-5 outline-none focus:border-primary focus-visible:shadow-none dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary focus:ring-0 dark:text-primaryTextDark']) !!}>
