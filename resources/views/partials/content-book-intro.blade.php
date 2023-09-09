@php
use NovelCabinet\Utilities\Formatter;
@endphp

{{-- Absolute gradient background --}}
<div class="absolute inset-0 -z-50">
    <div class="flex flex-col  opacity-50">
        <div class="absolute inset-0 bg-blue-50 -z-50"></div>
        <div class="bg-gradient-to-b from-red-50 to-blue-50 h-[48rem] grow"></div>
    </div>
</div>


<article class="mx-auto mt-16 px-[5vw] max-w-md sm:max-w-6xl flex flex-col gap-6">
    <section class="tag.book-info">
        <div class="tag.basic card-bd1 bg-slate-50 relative flex flex-col
            sm:flex-row sm:px-8 sm:gap-8">
            {{-- Upper: Main info --}}
            <div class="sm:w-[min(33%,14rem)] sm:max-w-xs shrink-0 sm:self-center">
                <div class="tag.cover m-auto flex-shrink-0 w-1/2
                    max-sm:-translate-y-10 sm:w-full sm:py-8">
                    <div class="w-full aspect-[3/4] relative">
                        <x-book-style1 :cover-src="$book->cover" />
                    </div>
                </div>

            </div>

            {{-- Lower: Details --}}

            <div class="p-4 sm:px-0 sm:py-8 flex flex-col">
                <div class="tag.info flex flex-col items-center gap-2 sm:items-start">
                    <div class="flex flex-col text-center gap-2 items-center flex-wrap sm:flex-row sm:text-left">
                        <h1 class="text-lg font-semibold text-primary"> {{ $book->title }} </h1>
                        {{-- <div class="my-4 mx-2 max-sm:hidden">
                                <x-headline-style1 :title="'Excerpt'" :link="'#'" :more="'more'" />
                            </div> --}}
                        <h2 class="text-secondary"> {{ $book->author }} 著 </h2>
                    </div>

                    {{-- <div class="bg-slate-500 bg-opacity-10 px-1 py-0.5 rounded-md ">tag1</div> --}}
                    <hr class="w-4/5 self-center">
                    <div class="flex flex-col text-center gap-x-6 gap-y-2 items-center flex-wrap sm:flex-row">
                        <div class="{{ $ratingValid ? 'text-yellow-400' : 'text-quaternary' }} text-lg font-semibold">
                            <h2 class="tag.rating rating-stars cursor-pointer peer" style="--rating:{{ $ratingValid ? $book->rating : '0' }}"></h2>
                            <span class="tag.rating-num">{{ $ratingValid ? number_format($book->rating, 1) : '待定' }}</span>
                            <span class="tag.rating-user {{ $userRating ? 'voted' : '' }} text-sm hidden peer-hover:inline-block [&.voted]:inline-block [&:not(.voted)]:text-quaternary">
                                <span>/</span>
                                <span class="tag.rating-num-user">{{ $userRating }}</span>
                            </span>
                        </div>

                        @if ($book->updateTime)
                            <div class="text-xs text-quaternary">{{ $book->updateTime->format('Y-m-d G:i') . ' 更新' }}</div>
                        @endif
                    </div>

                    <div class="tag.tags flex flex-wrap gap-1 justify-center text-sm">
                        @foreach ($book->genres as $tag)
                            <x-tag :tag="$tag->name" />
                        @endforeach
                        @if ($book->wordCount >= 1000)
                            <x-tag :tag="Formatter::humanLookNumber($book->wordCount) . '字'" />
                        @endif
                        @if (($fav = KarsonJo\BookPost\SqlQuery\BookQuery::getFavoriteCount($book->ID)) > 0)
                            <x-tag :tag="Formatter::humanLookNumber($fav) . '收藏'" />
                        @endif
                    </div>


                    {{-- <div class="tag.reserved py-5"></div> --}}
                </div>
                {{-- Details header --}}
                {{-- Details content --}}
                <div class="tag.details grow flex flex-col justify-between">
                    <div class="relative overflow-hidden">
                        <input class="tag.excerpt-more hidden peer" type="checkbox" id="menu-toggle">
                        {{-- Mobile read more toggle --}}
                        <label class="absolute bottom-0 left-0 w-full text-center p-2 cursor-pointer rounded-b-lg
                            bg-gradient-to-t from-slate-50 to-transparent
                            peer-checked:hidden sm:hidden" for="menu-toggle">
                            展开全部
                        </label>
                        {{-- <p class="tag.book-excerpt text-tertiary my-4 max-h-12 sm:max-h-[none] peer-checked:max-h-[none]"> --}}
                            <div class="tag.book-excerpt prose text-tertiary my-4 max-w-none max-h-12 sm:max-h-[none] peer-checked:max-h-[none]">
                                {!! wpautop(esc_html($book->excerpt)) !!}

                            </div>
                        {{-- </p> --}}
                    </div>
                    <div class="flex flex-wrap gap-2 select-none">
                        <div class="max-sm:grow">
                            <a class="block rounded px-6 pb-2 pt-2.5 shadow-md transition duration-150 text-center {{ $hasContent ? ' bg-theme-bg1 text-theme-fg1' : 'bg-gray-400 text-white pointer-events-none' }} hover:saturate-150 active:saturate-200" href="{{ $readingLink }}">
                                {{ $hasContent ? '开始阅读' : '敬请期待' }}
                            </a>
                        </div>
                        {{-- favorite --}}
                        <div class="max-sm:grow relative">
                            <div>
                                <input class="tag.fav-btn hidden peer" type="checkbox" id="fav-toggle">
                                <label class="block rounded px-6 pb-2 pt-2.5 shadow-md transition duration-150 text-center bg-theme-bg1 text-theme-fg1 cursor-pointer hover:saturate-150 active:saturate-200" for="fav-toggle">
                                    加入收藏
                                </label>
                                {{-- <label class="tag.fav-mask fixed inset-0 hidden peer-checked:block" for="fav-toggle">

                            </label> --}}
                                {{-- favorite panel --}}
                                <div class="tag.fav-panel z-10 w-[min(80vw,20rem)] min-h-[16rem] max-h-[24rem] mt-4 absolute top-full right-0 card-bd1 bg-primary-bg hidden animate-peek-in-t-f peer-checked:flex flex-col">
                                    <div class="tag.fav-title font-semibold text-center py-2">
                                        收藏
                                    </div>
                                    <section class="tag.fav-search mx-4 my-2 h-12 flex shrink-0 items-center rounded-full border border-theme-bg1 outline-theme-bg1 outline-2 focus-within:outline">
                                        <div class="mx-4 grow">
                                            <input class="w-full outline-none" type="text" name="" id="fav-search" placeholder="查找收藏夹">
                                        </div>
                                        <div class="h-full aspect-square relative">
                                            <button class="rounded-full absolute inset-1 bg-theme-bg1 flex justify-center items-center">
                                                <i class="arrow right border-theme-fg1 mr-0.5"></i>
                                            </button>
                                        </div>
                                    </section>
                                    <form action="javascript:void(0);" class="tag.add-fav-form grow flex flex-col overflow-y-auto">
                                        @foreach ($favoriteLists as $list)
                                            <label class="tag.fav-item px-4 py-2 hover:backdrop-contrast-90 cursor-pointer text-secondary flex items-center gap-2">
                                                <div class="w-4 m-1.5"><x-checkbox1 :value="$list->ID ?? ''" name="fav-list-id" :checked="$list->in_fav ?? false" /></div>
                                                <div class="tag.fav-item-title line-clamp-2">{{ $list?->list_title ?? '' }}</div>
                                            </label>
                                        @endforeach
                                    </form>
                                    <button class="tag.fav-submit-btn p-3 hover:bg-theme-bg1 hover:bg-opacity-5 text-center disabled:bg-quaternary-bg">确认</button>
                                    <hr class="w-4/5 mx-auto">
                                    <label class="tag.fav-create-btn p-3 cursor-pointer hover:bg-theme-bg1 hover:bg-opacity-5 text-center" for="fav-create-toggle">创建收藏夹</label>
                                </div>
                            </div>

                            <div>
                                {{-- create favorite list panel --}}
                                <input class="hidden peer" type="checkbox" id="fav-create-toggle">
                                <div class="tag.fav-create-panel z-20 fixed top-1/2 left-1/2 -right-1/2 -translate-x-1/2 -translate-y-1/2 hidden peer-checked:flex justify-center">
                                    <div class="flex flex-col gap-4 p-4 grow m-4 max-w-sm rounded-lg shadow-lg bg-primary-bg animate-peek-in-t-f">
                                        <div class="font-semibold flex justify-between">
                                            <h1 class="">新建收藏夹</h1>
                                            <label class="w-8 cursor-pointer" for="fav-create-toggle"><x-close-button /></label>
                                        </div>

                                        <form action="javascript:void(0);" class="tag.fav-create-form text-secondary">
                                            <div class="mb-4"><input class="tag.fav-create-input p-2 w-full rounded border-b border-theme-bg1 focus:outline-theme-bg1" type="text" placeholder="输入名称" name="title"></div>
                                            <div class="flex justify-between items-center">
                                                <div class="flex gap-2">
                                                    <span>公开</span>
                                                    <div class=" w-16 h-6">
                                                        <x-switch-button name="visibility" id="fav-create-visibility" />
                                                    </div>
                                                </div>
                                                <button class="tag.fav-create-submit shrink-0 rounded px-6 pb-2 pt-2.5 shadow-md transition duration-150 text-center bg-theme-bg1 text-theme-fg1 hover:saturate-150 active:saturate-200 disabled:saturate-0">确认</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </section>
    {{-- Detail info --}}
    <section class="tag.detail-info flex gap-6 flex-col">
        <div class="tag.contents grow py-8 px-6 card-bd2">
            <x-headline-style1 :title="'目录'" />
            {{-- Contents --}}
            <x-book-contents-list1 :contents="$contents" />
        </div>
    </section>
</article>
