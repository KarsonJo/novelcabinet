{{-- Absolute gradient background --}}
<div class="absolute inset-0 -z-50">
    <div class="flex flex-col  opacity-50">
        <div class="absolute inset-0 bg-blue-50 -z-50"></div>
        <div class="bg-gradient-to-b from-red-50 to-blue-50 h-[48rem] grow"></div>
    </div>
</div>


<article class="mx-auto mt-16 px-[5vw] max-w-md sm:max-w-6xl flex flex-col gap-8">
    <section class="tag.book-info">
        <div class="tag.basic card-bd1 relative flex flex-col
            sm:flex-row">
            {{-- Upper: Main info --}}
            <div class="sm:w-[min(33%,96rem)] sm:max-w-xs shrink-0 sm:self-center">
                <div class="tag.cover m-auto flex-shrink-0 w-1/2
                    max-sm:-translate-y-10 sm:w-2/3 sm:py-8">
                    <div class="w-full aspect-[3/4] relative">
                        <x-book-style1 :cover-src="$coverSrc" />
                    </div>
                </div>

            </div>

            {{-- Lower: Details --}}

            <div class="px-4 sm:py-8">
                <div class="tag.info flex flex-col items-center gap-2 sm:items-start">
                    <div class="flex flex-col text-center gap-2 sm:flex-row items-center flex-wrap">
                        <h1 class="text-lg font-semibold text-primary"> {{ $bookName }} </h1>
                        {{-- <div class="my-4 mx-2 max-sm:hidden">
                                <x-headline-style1 :title="'Excerpt'" :link="'#'" :more="'more'" />
                            </div> --}}
                        <h2 class="text-secondary"> {{ $author }} 著 </h2>
                    </div>
                    <h2 class="rating-stars text-yellow-400 text" style="--rating:8.75"></h2>
                    {{-- <div class="bg-slate-500 bg-opacity-10 px-1 py-0.5 rounded-md ">tag1</div> --}}
                    <div class="tag.tags m-1 flex flex-wrap gap-1 justify-center text-sm">
                        @foreach ($tags as $tag)
                            <x-tag :tag="$tag->name" />
                        @endforeach
                    </div>

                    {{-- <div class="tag.reserved py-5"></div> --}}
                </div>
                {{-- Details header --}}
                {{-- Details content --}}
                <div class="tag.details">
                    <div class="relative overflow-hidden my-4
                         sm:max-h-[unset]">
                        <input class="hidden peer" type="checkbox" id="menu-toggle">
                        {{-- Mobile read more toggle --}}
                        <label class="absolute bottom-0 left-0 w-full text-center p-2 cursor-pointer rounded-b-lg
                            bg-gradient-to-t from-slate-50 to-transparent
                            peer-checked:hidden sm:hidden" for="menu-toggle">
                            展开全部
                        </label>
                        <p class="text-tertiary sm:min-h-[6rem] max-h-12 peer-checked:max-h-[unset]">
                            {{ $excerpt }}
                        </p>
                    </div>
                    <div class="my-4">
                        <a class="inline-block rounded px-6 pb-2 pt-2.5 w-full shadow-md transition duration-150 saturate-100 {{ $hasContent ? ' bg-theme-bg1 text-theme-fg1 cursor-pointer' : 'bg-gray-400 text-white pointer-events-none' }}
                            hover:saturate-150 focus:outline-none focus:ring-0 active:saturate-200 sm:w-auto" type="button" data-te-ripple-init data-te-ripple-color="light" href="{{ $readingLink }}">
                            {{ $hasContent ? '开始阅读' : '敬请期待' }}
                        </a>
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
            <x-book-contents-list1 :contents="$contents"/>
        </div>
    </section>
</article>
