@extends('layout.index')

@section('title', 'Error')

@section('content')
    <div id="app">
        @if($errors->any())
            <show-info info="{{ $errors->first() }}"></show-info>
        @else
            <show-info info="{{ $info }}"></show-info>
        @endif
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
@endsection
