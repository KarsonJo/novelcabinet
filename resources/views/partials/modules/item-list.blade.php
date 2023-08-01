@php
    $current_url = $_SERVER['REQUEST_URI'];
    preg_match('(/page/(\d+)/)', $current_url, $matches);
    $page_number = isset($matches[1]) ? $matches[1] : 1;
    $value = 'https://th.bing.com/th/id/OIP.0qxWWiv5uAS-T2OK11jpawHaLZ?w=201&h=310&c=7&r=0&o=5&pid=1.7';
@endphp

{{-- @foreach (KarsonJo\BookPost\get_books(9, $page_number) as $item)
    @php
        $image = wp_get_attachment_image_src(get_post_thumbnail_id($item->ID), "full");
        $image = is_array($image) ? $image[0] :"1234";
    @endphp
    <div class="">
        {{ $item->post_title }}
    </div>
    <div class="">
        {{ $item->post_type }}
    </div>
    <div class="">
        {{ $image }}
    </div>
@endforeach --}}


<div class="tag.catalog">
    {{-- <div class="tag.catalog my-5 mx-[calc(20vw-4rem)]"> --}}
    {{-- Title --}}
    {{-- <div class="">
    <h1 class=" my-4 p-2 border-l-4 border-theme-bg1 border-solid text-xl font-semibold">Column Title</h1>
</div> --}}
    {{-- @include('partials.elements.headline1', ['title' => 'blah', 'link' => '#', 'more' => '233']) --}}
    <x-headline-style1 :title="'blah'" :link="'#'" :more="'more'" />
    {{-- Item --}}
    <x-book-list-main :book-posts="KarsonJo\BookPost\SqlQuery\BookFilterBuilder::create()->limit(6)->offset($page_number)->get_as_book()" />
    {{-- <div class="grid grid-cols-list-item gap-5">
        @foreach (KarsonJo\BookPost\get_books(6, $page_number) as $post)
            <x-book-list-item-main :book-post="$post" />
        @endforeach
    </div> --}}
    {{-- </div> --}}
</div>
