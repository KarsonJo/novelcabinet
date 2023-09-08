<div class="tag.bg absolute bg-reader-bg inset-0 -z-50"></div>
<div class="flex text-sm sm:pb-12 sm:text-base">
    <section class="select-none grow mx-auto max-w-4xl max-sm:p-1 sm:px-4"> {{-- <<- fit content for padding --}}
        {{-- Nav bar --}}
        <nav class="tag.menu bg-reader-paper shadow-sm fixed bottom-0 left-0 right-0 border-opacity-10 border-y border-y-black
        sm:sticky sm:top-0">
            <div class="tag.mb-nav-item flex grow h-14 justify-center items-center gap-6 sm:hidden">
                <div class="bg-gray-300 flex items-center justify-center px-8 py-2 rounded-md cursor-pointer">
                    <a href="{{ $preChapterUrl }}">上一章</a>
                </div>
                <div class="bg-gray-300 flex items-center justify-center px-8 py-2 rounded-md cursor-pointer">
                    <a href="{{ $nextChapterUrl }}">下一章</a>
                </div>
            </div>
            <div class="min-h-[3.5rem] flex grow items-center sm:justify-end sm:px-4 sm:gap-2 relative bg-inherit">
                <div class="tag.reader-settings bg-reader-paper border-reader-bg w-full absolute right-0 text-xs text-tertiary hidden
                max-sm:border-t-4 max-sm:bottom-full sm:w-3/4 sm:max-w-xl sm:top-full sm:text-sm sm:shadow-md animate-fade-in-f opened:block">
                    <div class="mx-5 my-6 grid grid-cols-4 items-center gap-4">
                        <div class="text-center">字体大小</div>
                        <input class="r-text-slider slider-round col-span-3 border border-primary border-opacity-25 bg-primary bg-opacity-10 accent-red-500 hover:bg-opacity-20" type="range" min="1" max="7" value="4" id="myRange">
                        <div class="text-center">阅读主题</div>
                        <div class="flex gap-2 col-span-3 justify-evenly items-center">
                            @foreach ([0, 1, 2, 3, 4, 'dark'] as $item)
                                <button class="rounded-full max-w-[2.5rem] max-h-10 grow aspect-square shadow-inner r-theme-btn-{{ $item }} border-2 border-primary border-opacity-25 selected:border-red-500"></button>
                            @endforeach
                        </div>
                        <div class="text-center">正文字体</div>
                        <div class="col-span-3 min-h-8 text-primary flex gap-3 justify-evenly items-center">
                            @foreach (['黑体', '宋体', '楷体'] as $index => $item)
                                <button class="r-font-btn-{{ $index }} rounded-md h-full reader-theme-4 bg-primary !bg-opacity-10 border !border-opacity-20 border-primary p-2 grow
                                hover:bg-red-300 hover:border-red-300 hover:text-red-500 selected:border-red-500 selected:text-red-500 selected:bg-red-500 selected:!border-opacity-100">{{ $item }}</button>
                            @endforeach
                        </div>
                        <div class="text-center max-sm:hidden">页面宽度</div>
                        <div class="col-span-3 min-h-8 text-primary flex gap-3 justify-evenly items-center max-sm:hidden">
                            @foreach (['小', '中', '大', '适应'] as $index => $item)
                                <button class="r-max-w-btn-{{ $index }} rounded-md h-full reader-theme-4 bg-primary !bg-opacity-10 border !border-opacity-20 border-primary p-2 grow
                                hover:bg-red-300 hover:border-red-300 hover:text-red-500 selected:border-red-500 selected:text-red-500 selected:bg-red-500 selected:!border-opacity-100">{{ $item }}</button>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="tag.pc-chpt p-2 grow text-sm text-quaternary px-4 max-sm:fixed inset-0 bottom-auto max-sm:bg-inherit sm:block">{{ the_title() }}</div>
                <div class="cursor-pointer flex items-center justify-center max-sm:grow">
                    <a href="{{ get_site_url() }}" class="max-w-[4rem] font-bold w-8 h-8">主页</a>
                </div>
                <div class="r-settings-toggle cursor-pointer flex items-center justify-center max-sm:grow">
                    <div class="max-w-[4rem] font-bold w-8 h-8">设置</div>
                </div>
                <div class="r-contents-toggle cursor-pointer flex items-center justify-center max-sm:grow">
                    <div class="max-w-[4rem] font-bold w-8 h-8">目录</div>
                </div>
            </div>
        </nav>
        {{-- <article class="h-[20vh] r-art-1 bg-red-500" data-art-id="1">
            <h1 class="chapter-title">red</h1>
        </article>
        <article class="h-[20vh] r-art-2 bg-yellow-500" data-art-id="2">
            <h1 class="chapter-title">yellow</h1>
        </article>
        <article class="h-[20vh] r-art-3 bg-green-500" data-art-id="3">
            <h1 class="chapter-title">green</h1>
        </article>
        <article class="h-[20vh] r-art-4 bg-blue-500" data-art-id="4">
            <h1 class="chapter-title">blue</h1>
        </article>
        <article class="h-[20vh] r-art-5 bg-purple-500" data-art-id="5">
            <h1 class="chapter-title">purple</h1>
        </article>
        <article class="h-[20vh] r-art-6 bg-slate-500" data-art-id="6">
            <h1 class="chapter-title">slate</h1>
        </article>
        <div class="h-screen"></div> --}}

        <article class="text-[1em] tag.book-reader prose p-8 bg-reader-paper shadow-md sm:p-[clamp(4rem,10%,7rem)]" data-art-id={{ get_the_ID() }}>
            <div class="tag.chapter-meta pb-2 mb-4 border-b border-b-quaternary border-opacity-25">
                <h1 class="chapter-title">{{ the_title() }}</h1>
                <div class="not-prose text-quaternary text-[0.875em] leading-[1.25em] flex gap-3 flex-wrap">
                    <span><a href="{{ $bookUrl }}" class="hover:text-primary hover:underline">{{ $bookName }}</a></span>
                    <span>·</span>
                    <span><a href="{{ $authorUrl }}" class="hover:text-primary hover:underline">{{ $author }}</a></span>
                    <span>·</span>
                    <span>{{ $postDateTime }}</span>
                </div>
            </div>
            <div class="chapter-content indent-8">
                @php(the_content())
            </div>
            <div class="tag.pc-nav not-prose pt-8 flex gap-0.5 text-center cursor-pointer max-sm:hidden">
                <a href="{{ $preChapterUrl }}" class="backdrop-contrast-75 hover:backdrop-contrast-50 active:backdrop-contrast-25 py-3 rounded-l-full grow">上一章</a>
                <button class="r-contents-toggle backdrop-contrast-75 hover:backdrop-contrast-50 active:backdrop-contrast-25 py-3 grow">目录</button>
                <a href="{{ $nextChapterUrl }}" class="backdrop-contrast-75 hover:backdrop-contrast-50 active:backdrop-contrast-25 py-3 rounded-r-full grow">下一章</a>
            </div>
        </article>
        {{-- <div class="h-screen"></div> --}}
    </section>
    {{-- 
    <footer>
        {!! wp_link_pages(['echo' => 0, 'before' => '<nav class="page-nav"><p>' . __('Pages:', 'sage'), 'after' => '</p></nav>']) !!}
    </footer> --}}

</div>

<section class="tag.contents grow fixed inset-0 -left-full w-full backdrop-blur-xl opened:translate-x-full transition-transform duration-300 z-10">
    <div class="absolute bg-white bg-opacity-80 inset-0 -z-50"></div>
    <label class="r-contents-toggle z-50 w-8 aspect-square cursor-pointer fixed bottom-3 right-6 sm:top-3 max-w-">
        <x-close-button />
    </label>
    <div class=" py-8 px-6 overflow-y-auto h-full w-full">
        <x-headline-style1 :title="'目录'" />
        {{-- Contents --}}
        <x-book-contents-list1 :contents="$contents" />
    </div>
</section>

@php(comments_template())



<div class="h-28 sm:hidden"></div>
