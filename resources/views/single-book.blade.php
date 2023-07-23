@php
    global $post;
@endphp

@extends(empty(get_post_ancestors($post)) ? 'layouts.app' : 'layouts.reader')

@if (empty(get_post_ancestors($post)))
    @section('content')
        @includeFirst(['partials.content-book-intro', 'partials.content-single'])
    @endsection
@else
    @section('content')
        @includeFirst(['partials.content-book-chapter', 'partials.content-single'])
    @endsection
@endif
