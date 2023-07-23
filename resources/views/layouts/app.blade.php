{{-- Normal header-body-footer layout --}}
@extends('layouts.main')
@section('mainpage')
    @include('sections.header')

    <main id="main" class="main">
        @yield('content')
    </main>

    @hasSection('sidebar')
        <aside class="sidebar">
            @yield('sidebar')
        </aside>
    @endif

    @include('sections.footer')
@endsection
