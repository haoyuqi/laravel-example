@extends('layout.index')

@section('title', 'Time')

@section('content')
    <div id="app">
        <time-component init_data="{{ json_encode($init_data) }}"></time-component>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
