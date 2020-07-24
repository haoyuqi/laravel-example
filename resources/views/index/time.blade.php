@extends('layout.index')

@section('title', 'Time')

@section('content')
    <div id="app">
        <show-info info="{{ $info }}"></show-info>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
