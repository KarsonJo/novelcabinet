{{-- 注意：这是一个自定义路由页面，并非默认WordPress模板 --}}

@extends('layouts.main')

@section('mainpage')
    @includeFirst(['partials.user-dashboard'])
@endsection