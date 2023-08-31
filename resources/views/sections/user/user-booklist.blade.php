@php
    use NovelCabinet\Utilities\Formatter;
    use KarsonJo\BookPost\SqlQuery\BookQuery;
    use KarsonJo\BookPost\BookPost;
    use NovelCabinet\Helpers\WebHelpers;
    use NovelCabinet\Services\Route\Enums\UserEndpoints;
    use KarsonJo\BookPost\Route\QueryData;
    
    $currentFilter = get_query_var(QueryData::KBP_BOOK_STATUS);
    
    // use NovelCabinet\Services\Route\Enums\UserBookEndpoints;
    $bookQuery = KarsonJo\BookPost\SqlQuery\BookFilterBuilder::create(null, false)->of_author(get_current_user_id());
    switch ($currentFilter) {
        case 'publish':
            $books = $bookQuery->of_status('publish');
            break;
        case 'future':
            $books = $bookQuery->of_status('future');
            break;
        case 'trash':
            $books = $bookQuery->of_status('trash');
            break;
        default:
            break;
    }
    
    $books = $bookQuery->get_as_book();
    
    $book = \KarsonJo\BookPost\Book::initBookFromPost(14);
    
    // $bookEndpoints = NovelCabinet\Services\Route\Enums\UserBookEndpoints::sigments();
    $filters = [
        'all' => [
            'displayName' => __('all', 'NovelCabinet'),
            'args' => [],
            'active' => !$currentFilter,
        ],
        'publish' => [
            'displayName' => __('publish', 'NovelCabinet'),
            'args' => ['status' => 'publish'],
            'active' => $currentFilter === 'publish',
        ],
        'future' => [
            'displayName' => __('future', 'NovelCabinet'),
            'args' => ['status' => 'future'],
            'active' => $currentFilter === 'future',
        ],
        'trash' => [
            'displayName' => __('trash', 'NovelCabinet'),
            'args' => ['status' => 'trash'],
            'active' => $currentFilter === 'trash',
        ],
    ];
@endphp

<div class="mx-auto min-h-[32rem] max-w-7xl sm:p-8">
    <div class="p-5">
        <div class="tag-filter-tabs flex gap-2 py-2 text-sm justify-center sm:text-base sm:justify-start">
            @foreach ($filters as $filter)
                <a href="{{ add_query_arg($filter['args'], WebHelpers::currentUrl()) }}" class="{{ $filter['active'] ? 'selected' : '' }} border-theme-bg1 selected:border-b-2 selected:text-theme-bg1 selected:pointer-events-none">
                    <div class="flex items-center justify-center px-4 py-2">{{ $filter['displayName'] }}</div>
                </a>
            @endforeach
        </div>
        <div class="tag-book-list grid sm:grid-cols-[repeat(auto-fill,minmax(360px,1fr))] gap-2">
            {{-- @foreach ($books as $book)
                @php
                    $this_book = \KarsonJo\BookPost\Book::initBookFromPost(17);
                    $contents = $this_book->contents;
                @endphp
                <x-book-contents-list1 :contents="$contents" />
            @endforeach --}}

            @foreach ($books as $book)
                <div class="tag-book-item relative p-4 flex flex-col bg-theme-bg1 bg-opacity-5 rounded-lg shadow-lg text-sm" data-post-id="{{ $book->ID }}">
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
                                    @if ($book->status === 'trash')
                                        <a href="{!! wp_nonce_url(add_query_arg(['action' => 'untrash', 'post' => $book->ID], admin_url('post.php')),"untrash-post_$book->ID") !!}" class="tag-untrash-btn cursor-pointer flex items-center justify-center h-full aspect-square btn-style1-green">
                                            <i class="fa-light fa-trash-undo"></i>
                                        </a>
                                        <a href="{!! get_delete_post_link($book->ID, '', true) !!}" class="tag-delete-btn cursor-pointer flex items-center justify-center h-full aspect-square btn-style2-rose">
                                            <i class="fa-light fa-trash-slash"></i>
                                        </a>
                                    @else
                                        <a href="{!! add_query_arg(['post_type' => BookPost::KBP_BOOK, QueryData::NEW_CHAPTER_OF => $book->ID], admin_url('post-new.php')) !!}" class="cursor-pointer flex items-center justify-center h-full aspect-square btn-style1-green">
                                            <i class="fa-light fa-file-circle-plus"></i>
                                        </a>
                                        <a href="{!! add_query_arg(['action' => 'edit', 'post' => $book->ID], admin_url('post.php')) !!}" class="tag-edit-btn cursor-pointer flex items-center justify-center h-full aspect-square btn-style1-amber">
                                            {{-- <i class="fa-light fa-file-circle-plus"></i> --}}
                                            <i class="fa-light fa-pen-to-square"></i>
                                        </a>
                                        <label for="filter-state" class="tag-contents-btn flex items-center justify-center cursor-pointer aspect-square btn-style1-blue">
                                            <i class="fa-light fa-list-tree"></i>
                                        </label>
                                        <a href="{!! get_delete_post_link($book->ID) !!}" class="tag-trash-btn cursor-pointer flex items-center justify-center h-full aspect-square btn-style2-rose">
                                            <i class="fa-light fa-trash-xmark"></i>
                                        </a>
                                    @endif
                                </div>


                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        <div class="tag-book-loading loader-fade sticky top-0 mx-auto text-theme-bg1 hidden w-40 h-40"></div>
    </div>
</div>

<section class="tag.top-bar z-50 lg:w-80 max-lg:fixed">
    <input class="hidden peer" type="checkbox" value="checked" id="filter-state">
    <label class="fixed inset-0 hidden bg-black bg-opacity-50 animate-fade-in-f peer-checked:block" for="filter-state"></label>
    {{-- main menu --}}
    <section class="fixed inset-0 top-[10%] rounded-xl bg-primary-bg transition-transform border-t-4 border-theme-bg1 duration-200 shadow-lg min-h-[20rem]
    lg:hidden lg:inset-[10%] max-lg:translate-y-full max-lg:rounded-b-none peer-checked:flex flex-col peer-checked:translate-y-0">
        {{-- top bar --}}
        <div class="px-5 py-4 flex justify-between items-center">
            <label class="tag.filter-toggle basis-1/4 cursor-pointer" for="filter-state">
                <i class="btn-back arrow left w-4 block"></i>
            </label>
            <div class="grow text-center text-lg font-semibold">{{ __('contents', 'NovelCabinet') }}</div>
            <div class="tag-contents-submit-btn basis-1/4 text-right text-green-500 text-sm cursor-pointer">确认顺序更变</div>
        </div>


        <section class="tag.draggable-contents px-4 group/contents grow overflow-y-auto">
            <div class="tag-draggable-volume-grid relative">
                <!-- template: draggable-volume-item -->
            </div>
            <div class="tag-contents-loading hidden loader-fade inset-0 mx-auto text-theme-bg1 w-40 h-40"></div>

            <template id="draggable-volume-item-tpl">

                <div class="absolute w-full muuri-item">
                    <!-- Volume header -->
                    <input class="tag-volume-toggle hidden peer" type="checkbox" value="checked">
                    <label class="tag.volume-label flex py-2 my-3 rounded bg-theme-bg1 text-theme-fg1 shadow text-sm font-semibold text-opacity-80 bg-opacity-10 cursor-pointer
                        hover:bg-opacity-20 peer-checked:[&_.vtoggle]:rotate-180 sm:my-6">
                        <button class="tag.grip-volume w-10 shrink-0 cursor-move flex items-center justify-center">
                            <i class="fa-light fa-grip-dots-vertical"></i>
                        </button>
                        <div class="flex grow items-center flex-wrap">
                            <a href="#" class="tag-volume-url p-1 sm:p-2 grow">
                                <h3 class="tag-volume-title py-2 grow">
                                    the title
                                </h3>
                            </a>
                            <div class="flex items-center grow justify-end">
                                <div class="flex gap-2 h-10 text-lg">
                                    {{-- <button class="tag.rename-btn flex items-center justify-center h-full aspect-square shrink-0 btn-style1-blue">
                                        <i class="tag-trash-btn fa-light fa-input-text"></i>
                                    </button> --}}
                                    <a href="{!! add_query_arg(['action' => 'edit'], admin_url('post.php')) !!}" class="tag-edit-btn cursor-pointer flex items-center justify-center h-full aspect-square btn-style1-amber">
                                        {{-- <i class="fa-light fa-file-circle-plus"></i> --}}
                                        <i class="fa-light fa-pen-to-square"></i>
                                    </a>
                                    <button class="flex items-center justify-center h-full aspect-square shrink-0 btn-style2-rose">
                                        <i class="fa-light fa-trash-xmark"></i>
                                    </button>
                                </div>
                                <div class="vtoggle px-8 w-10 aspect-square flex justify-center items-center origin-center transition-transform duration-200">
                                    <i class="arrow up border-theme-bg1"></i>
                                </div>
                            </div>
                        </div>
                    </label>


                    <!-- Chapter list -->
                    <div class="tag-draggable-chapter-grid relative peer-checked:hidden group-[.dragging]/contents:hidden py-10">
                        <!-- template: draggable-chapter-item -->
                    </div>
                </div>

            </template>

            <template id="draggable-chapter-item-tpl">

                <div class="muuri-item absolute flex p-4 w-full border border-quaternary-bg shadow text-secondary text-sm rounded-md sm:m-2 sm:w-auto [&.muuri-item-dragging]:bg-theme-bg1 [&.muuri-item-dragging]:bg-opacity-20">
                    <button class="tag.grip-chapter w-10 shrink-0 self-stretch cursor-move flex items-center justify-center">
                        <i class="fa-light fa-grip-dots-vertical"></i>
                    </button>
                    <div class="flex grow items-center flex-wrap">
                        <a href="#" class="tag-chapter-url p-1 sm:p-2 grow">
                            <h3 class="tag-chapter-title">
                                the chapter
                            </h3>
                        </a>
                        <div class="grow flex gap-2 h-8 self-end justify-end">
                            {{-- <button class="tag.rename-btn flex items-center justify-center h-full aspect-square shrink-0 btn-style1-blue">
                                <i class="fa-light fa-input-text"></i>
                            </button> --}}
                            <a href="{!! add_query_arg(['action' => 'edit'], admin_url('post.php')) !!}" class="tag-edit-btn cursor-pointer flex items-center justify-center h-full aspect-square btn-style1-amber">
                                {{-- <i class="fa-light fa-file-circle-plus"></i> --}}
                                <i class="fa-light fa-pen-to-square"></i>
                            </a>
                            <button class="tag-trash-btn flex items-center justify-center h-full aspect-square shrink-0 btn-style1-rose">
                                <i class="fa-light fa-trash-xmark"></i>
                            </button>
                        </div>
                    </div>
                </div>

            </template>
        </section>
    </section>
</section>
