@extends('layouts.app')

@section('content')
    {{-- @include('partials.page-header') --}}

    {{-- Carousel --}}
    @include('partials.elements.carousel')

    <hr class="w-4/5 mx-auto my-5">

    {{-- Main --}}
    <div class="my-5 mx-xfit-md flex gap-10">
        <div class="flex-grow">
            @include('partials.modules.item-list')
            <hr class="w-4/5 mx-auto my-5">
            @include('partials.modules.item-list')
        </div>

        @if (is_active_sidebar('index-body1'))
            <div class="hidden shrink-0 bg-theme-bg1 bg-opacity-5
            xl:w-[min(20%,16rem)] xl:block">
                {{-- @include('partials.elements.headline1') --}}
                <x-headline-style1 :title="'Column X'" />
                <ul class="wp-widget my-5 px-3 text-slate-500">
                    @php(dynamic_sidebar('index-body1'))
                </ul>
            </div>
        @endif

    </div>


    {{-- 
    <div class="flex justify-center">
        <div class="tag.catalog my-5 mx-[calc(20vw-4rem)] flex">
            <div class="">
                @include('partials.modules.item-list')
                @include('partials.modules.item-list')
            </div>
            <div class="lg:w-[min(25%,16rem)] shrink-0"></div>
        </div>
    </div> --}}








    {{-- @if (!have_posts())
    <x-alert type="warning">
      {!! __('Sorry, no results were found.', 'sage') !!}
    </x-alert>

    {!! get_search_form(false) !!}
  @endif

  @while (have_posts()) @php(the_post())
    @includeFirst(['partials.content-' . get_post_type(), 'partials.content'])
  @endwhile

  {!! get_the_posts_navigation() !!} --}}
@endsection

{{-- @section('sidebar')
  @include('sections.sidebar')
@endsection --}}
