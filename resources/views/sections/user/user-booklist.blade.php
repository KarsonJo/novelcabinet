@php
    // $books = KarsonJo\BookPost\SqlQuery\BookFilterBuilder::create()
    //     ->of_author(1)
    //     ->get_as_book();
    $book = \KarsonJo\BookPost\Book::initBookFromPost(14);
@endphp

<div class="px-8 mx-auto my-8 min-h-[32rem] max-w-7xl">
    <div class="p-6">
        {{-- @foreach ($books as $book)
            @php
                $this_book = \KarsonJo\BookPost\Book::initBookFromPost(17);
                $contents = $this_book->contents;
            @endphp
            <x-book-contents-list1 :contents="$contents" />
        @endforeach --}}
        <div class="p-4 my-4 bg-theme-bg1 bg-opacity-5 rounded-lg shadow-lg">
            <div class="tag.book-item flex gap-4">
                <div class="tag.book-cover aspect-[3/4] h-24">
                    <x-book-style1 :cover-src="$book->cover" />
                </div>
                <div class="">
                    <div class="">Lorem ipsum dolor sit amet.</div>
                    <div class="tag.tags m-1 flex flex-wrap gap-1 flex-row-reverse">
                        @foreach ($book->genres as $tag)
                            <x-tag :tag="$tag->name" />
                        @endforeach
                    </div>
                </div>


            </div>
        </div>
    </div>
</div>
