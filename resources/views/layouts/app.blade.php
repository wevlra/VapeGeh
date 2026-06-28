<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">

        <title>{{ $title ?? config('app.name') }}</title>

        {{-- PWA --}}
        <link rel="manifest" href="/manifest.json">
        <link rel="apple-touch-icon" href="/icon-180.png">
        <meta name="apple-mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
        <meta name="theme-color" content="#F59E0B">

        @vite(['resources/css/app.css'])

        @livewireStyles
    </head>
    <body>
        {{ $slot }}

        @livewireScripts
    </body>
</html>
