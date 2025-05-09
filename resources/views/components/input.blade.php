@props(['disabled' => false,'id'])

@php
$id = $id ?? md5($attributes->wire('model'));
@endphp


<input id="{{ $id }}" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'w-full rounded-lg border border-slate-400 dark:border-0 dark:text-primaryTextDark bg-transparent py-2 px-4 outline-none focus:border-primary focus-visible:shadow-none dark:border-form-strokedark dark:bg-form-input dark:focus:border-primary focus:ring-0 dark:text-primaryTextDark']) !!}>
