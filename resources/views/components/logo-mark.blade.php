@props(['class' => 'h-9 w-9'])

<img
    src="{{ asset('images/logo.png') }}"
    alt="{{ config('app.name', 'SkoolMS') }}"
    {{ $attributes->merge(['class' => $class . ' object-contain shrink-0']) }}
>
