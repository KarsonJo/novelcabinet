@extends('layouts.main')
@section('mainpage')
    <div class="w-full h-full">
        <div class="">
            @includeFirst(['forms.user-login'])
        </div>
    </div>
@endsection
