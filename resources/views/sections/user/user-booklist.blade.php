@php
    use NovelCabinet\Utilities\Formatter;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use KarsonJo\BookPost\BookPost;
    use KarsonJo\BookPost\Route\QueryData;
    $books = KarsonJo\BookPost\SqlQuery\BookFilterBuilder::create(null, false)
        ->of_author(1)
        ->get_as_book();
    $book = \KarsonJo\BookPost\Book::initBookFromPost(14);
    
@endphp

<div class="px-8 mx-auto my-8 min-h-[32rem] max-w-7xl">
    <div class="grid sm:grid-cols-[repeat(auto-fill,minmax(360px,1fr))] gap-2">
        {{-- @foreach ($books as $book)
            @php
                $this_book = \KarsonJo\BookPost\Book::initBookFromPost(17);
                $contents = $this_book->contents;
            @endphp
            <x-book-contents-list1 :contents="$contents" />
        @endforeach --}}

        @foreach ($books as $book)
            <div class="tag.book-item p-4 flex flex-col bg-theme-bg1 bg-opacity-5 rounded-lg shadow-lg text-sm">
                {{-- title --}}
                <div class="mb-2 flex items-center justify-between flex-wrap">
                    <a class="hover:text-theme-bg1" href="{{ $book->permalink }}">{{ $book->title }}</a>
                    <div class="tag.tags m-1 shrink-0 flex flex-wrap gap-1 text-xs text-tertiary flex-row-reverse">
                        @foreach ($book->genres as $tag)
                            <x-tag :tag="$tag->name" />
                        @endforeach
                    </div>
                </div>

                <div class="grow flex gap-5">
                    <div class="tag.book-cover aspect-[3/4] h-24 self-center">
                        <a href="{{ $book->permalink }}">
                            <x-book-style1 :cover-src="$book->cover" />
                        </a>
                    </div>


                    <div class="grow flex flex-col gap-2 flex-wrap sm:flex-row">
                        <div class="tag.info text-secondary grow">
                            <div class="my-1">
                                <span>{{ __($book->status, 'NovelCabinet') }}</span>
                                <span> · </span>
                                <span>{{ Formatter::humanLookNumber($book->wordCount) . '字' }}</span>
                                <span> · </span>
                                <span>{{ BookQuery::getFavoriteCount($book->ID) . '收藏' }}</span>
                                <div class="my-1 text-tertiary">
                                    <span>{{ __('last update', 'NovelCabinet') }}</span>
                                    <span class="text-xs">{{ $book->updateTime->format('Y-m-d H:i:s') }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-2 flex-wrap grow items-end justify-end">
                            <div class="h-8 sm:h-10 sm:text-lg">
                                <a href="{!! add_query_arg(['post_type' => BookPost::KBP_BOOK, QueryData::NEW_CHAPTER_OF => $book->ID], admin_url('post-new.php')) !!}">
                                    <button class="hover:bg-amber-500 h-full aspect-square hover:text-white rounded-lg shadow-lg transition-colors border border-transparent text-amber-500 bg-white border-amber-500">
                                        <i class="fa-light fa-file-circle-plus"></i>
                                    </button></a>
                                <button class="hover:bg-blue-400 h-full aspect-square hover:text-white rounded-lg shadow-lg transition-colors border border-transparent text-blue-400 bg-white border-blue-400">
                                    <i class="fa-light fa-list-tree"></i>
                                </button>
                                <a href="{!! get_delete_post_link($book->ID) !!}">
                                    <button class="bg-rose-500 h-full aspect-square text-white rounded-lg shadow-lg transition-colors border border-transparent hover:text-rose-500 hover:bg-white hover:border-rose-500">
                                        <i class="fa-light fa-trash-xmark"></i>
                                    </button>
                                </a>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        @endforeach


    </div>
</div>
