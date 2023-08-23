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
                <div class="mb-2 grow flex items-center justify-between flex-wrap">
                    <a class="hover:text-theme-bg1" href="{{ $book->permalink }}">{{ $book->title }}</a>
                    <div class="tag.tags m-1 shrink-0 grow flex flex-wrap gap-1 text-xs text-tertiary flex-row-reverse">
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
                            <div class="h-8 sm:h-10 sm:text-lg flex gap-2">
                                <a href="{!! add_query_arg(['post_type' => BookPost::KBP_BOOK, QueryData::NEW_CHAPTER_OF => $book->ID], admin_url('post-new.php')) !!}" class="cursor-pointer flex items-center justify-center h-full aspect-square btn-style1-amber">
                                    <i class="fa-light fa-file-circle-plus"></i>
                                </a>
                                <label for="filter-state" class="tag.contents-toggle flex items-center justify-center cursor-pointer aspect-square btn-style1-blue">
                                    <i class="fa-light fa-list-tree"></i>
                                </label>
                                <a href="{!! get_delete_post_link($book->ID) !!}" class="cursor-pointer flex items-center justify-center h-full aspect-square btn-style2-rose">
                                    <i class="fa-light fa-trash-xmark"></i>
                                </a>
                            </div>


                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>

<section class="tag.top-bar z-50 lg:w-80 max-lg:fixed">
    <input checked class="hidden peer" type="checkbox" value="checked" id="filter-state">
    <label class="fixed inset-0 hidden bg-black bg-opacity-50 animate-fade-in-f peer-checked:block" for="filter-state"></label>
    {{-- main menu --}}
    <section class="fixed inset-0 top-[10%] rounded-xl bg-primary-bg transition-transform border-t-4 border-theme-bg1 duration-200 shadow-lg min-h-[20rem]
    lg:hidden lg:inset-[10%] max-lg:translate-y-full max-lg:rounded-b-none peer-checked:flex flex-col peer-checked:translate-y-0">
        {{-- top bar --}}
        <div class="px-5 py-4 flex justify-between items-center">
            <label class="tag.filter-toggle basis-1/4 cursor-pointer" for="filter-state">
                <i class="btn-back arrow left w-4 block"></i>
            </label>
            <div class="grow text-center text-lg font-semibold">目录</div>
            <div class="tag.reset-btn basis-1/4 text-right text-red-500 text-sm cursor-pointer"></div>
        </div>



        <section class="tag.draggable-contents px-4 grow overflow-y-auto">
            @foreach ([1, 2, 3] as $volume)
                <div class="tag.draggable-volume">
                    {{-- Volume name and toggle --}}
                    <input class="hidden peer" type="checkbox" value="checked" id="vid-test">
                    <label class="flex py-2 my-3 rounded bg-theme-bg1 text-theme-fg1 shadow text-sm font-semibold text-opacity-80 bg-opacity-10 cursor-pointer
                    hover:bg-opacity-20 peer-checked:[&_.vtoggle]:rotate-180 sm:my-6" for="vid-test">
                        <button class="w-10 shrink-0 cursor-move flex items-center justify-center">
                            <i class="fa-light fa-grip-dots-vertical"></i>
                        </button>
                        <div class="flex grow items-center flex-wrap">
                            <h3 class="tag.volume-title py-2 grow">
                                the title
                            </h3>
                            <div class="flex items-center grow justify-end">
                                <div class="flex gap-2 h-10 text-lg">
                                    <a href="{!! get_delete_post_link($book->ID) !!}" class="flex items-center justify-center h-full aspect-square shrink-0 btn-style1-blue">
                                        <i class="fa-light fa-input-text"></i>
                                    </a>
                                    <a href="{!! get_delete_post_link($book->ID) !!}" class="flex items-center justify-center h-full aspect-square shrink-0 btn-style2-rose">
                                        <i class="fa-light fa-trash-xmark"></i>
                                    </a>
                                </div>
                                <div class="vtoggle px-8 w-10 aspect-square flex justify-center items-center origin-center transition-transform duration-200">
                                    <i class="arrow up border-theme-bg1"></i>
                                </div>
                            </div>
                        </div>
                    </label>


                    {{-- Chapters list --}}
                    <ul class="grid gap-2 text-secondary text-sm sm:grid-cols-list-item peer-checked:hidden">
                        @foreach ([1, 2, 3] as $chapter)
                            <li class="tag.draggable-chapter flex p-4 border border-quaternary-bg shadow hover:bg-theme-bg1 hover:bg-opacity-20 rounded-md mx-2 selected:font-bold selected:text-theme-bg1" data-cont-id="chapter-id">
                                {{-- @php
                                $_post = new WP_Post((object) ['ID' => $chapter->ID, 'post_type' => 'book', 'post_status' => 'publish', 'post_title' => '123', 'post_name' => '456', 'post_parent' => 1]);
                                // get_permalink($_post);
                                wp_cache_add( $_post->ID, $_post, 'posts' );
                                // $_post = get_post($_post->ID);
                                // print_r($_post->filter)
                            @endphp --}}
                                <button class="w-10 shrink-0 self-stretch cursor-move flex items-center justify-center">
                                    <i class="fa-light fa-grip-dots-vertical"></i>
                                </button>
                                <div class="flex grow items-center flex-wrap">
                                    <a href="#" class="p-1 sm:p-2 grow">
                                        <h3 class="tag.chapter-title">
                                            the chapter
                                        </h3>
                                    </a>
                                    <div class="grow flex gap-2 h-8 self-end justify-end">
                                        <a href="{!! get_delete_post_link($book->ID) !!}" class="flex items-center justify-center h-full aspect-square shrink-0 btn-style1-blue">
                                            <i class="fa-light fa-input-text"></i>
                                        </a>
                                        <a href="{!! get_delete_post_link($book->ID) !!}" class="flex items-center justify-center h-full aspect-square shrink-0 btn-style1-rose">
                                            <i class="fa-light fa-trash-xmark"></i>
                                        </a>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </section>






    </section>
</section>
