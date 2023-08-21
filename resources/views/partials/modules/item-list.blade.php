@php
    $current_url = $_SERVER['REQUEST_URI'];
    preg_match('(/page/(\d+)/)', $current_url, $matches);
    $page_number = isset($matches[1]) ? $matches[1] : 1;
    $value = 'https://th.bing.com/th/id/OIP.0qxWWiv5uAS-T2OK11jpawHaLZ?w=201&h=310&c=7&r=0&o=5&pid=1.7';
@endphp


<div class="tag.catalog">
    <x-headline-style1 :title="'blah'" :link="'#'" :more="'more'" />
    {{-- Item --}}
    <x-book-list-main :book-posts="KarsonJo\BookPost\SqlQuery\BookFilterBuilder::create()->limit(6)->offset($page_number)->get_as_book()" />
</div>
