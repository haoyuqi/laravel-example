<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>laravel example</title>

    <link href="{{ asset('css/app.css') }}" rel="stylesheet">

</head>
<body>
{{--@if (Route::has('login'))
    <div class="top-right links">
        @auth
            <a href="{{ url('/home') }}">Home</a>
        @else
            <a href="{{ route('login') }}">Login</a>

            @if (Route::has('register'))
                <a href="{{ route('register') }}">Register</a>
            @endif
        @endauth
    </div>
@endif--}}
<div id="app">
    <show-info info="{{ $info }}"></show-info>
</div>
<script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
