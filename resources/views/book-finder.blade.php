{{-- 注意：这是一个自定义路由页面，并非默认WordPress模板 --}}

@extends('layouts.app')

@section('content')
  @while(have_posts()) @php(the_post())
    @includeFirst(['sections.book-finder'])
  @endwhile
@endsection
