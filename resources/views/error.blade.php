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
        <div id="app">
            @if($errors->any())
                <show-info info="{{ $errors->first() }}"></show-info>
            @else
                <show-info info="{{ $info }}"></show-info>
            @endif
        </div>
    <script src="{{ asset('js/app.js') }}"></script>
    </body>
</html>