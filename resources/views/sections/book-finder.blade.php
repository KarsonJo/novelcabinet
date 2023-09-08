<div class="tag.book-filter flex min-h-screen mx-xfit-lg gap-5 my-5 sm:my-8">
    {{-- the filter --}}
    {{-- background --}}
    <section class="tag.top-bar z-50 lg:w-80 max-lg:fixed shrink-0">
        <input class="hidden peer" type="checkbox" value="checked" id="filter-state">
        {{-- filte toggle btn --}}
        <label class="tag.filter-toggle fixed bottom-4 right-4 w-12 h-12 rounded-full bg-theme-bg1 cursor-pointer lg:hidden" for="filter-state">
            <svg t="1690133678643" class="absolute inset-1/4 text-theme-fg1" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="2271" fill="currentColor">
                <path d="M874.0352 299.8784C926.72 299.8784 972.8 253.7984 972.8 201.216 972.8 148.48 926.72 102.4 874.0352 102.4c-46.08 0-78.9504 26.3168-92.16 65.8432H51.2V233.984h730.6752c13.2096 39.5264 46.08 65.8432 92.16 65.8432z m0-131.6352c19.7632 0 32.9216 13.1584 32.9216 32.9216 0 19.712-13.1584 32.8704-32.9216 32.8704-19.712 0-32.8704-13.1584-32.8704-32.8704 0-19.7632 13.1584-32.9216 32.8704-32.9216zM150.016 760.6784c46.08 0 78.9504 26.3168 92.16 65.8432H972.8v65.8432H242.1248c-13.2096 39.4752-46.08 65.792-92.16 65.792-52.6848 0-98.7648-46.08-98.7648-98.7136 0-52.6848 46.08-98.7648 98.7648-98.7648zM512 431.616c46.08 0 79.0016 26.3168 92.16 65.792H972.8V563.2h-368.64c-13.1584 39.4752-52.6848 65.8432-92.16 65.8432-39.4752 0-79.0016-26.368-92.16-65.8432H51.2V497.3568h368.64c13.1584-39.4752 46.08-65.792 92.16-65.792zM512 563.2c19.7632 0 32.9216-13.1584 32.9216-32.9216s-13.1584-32.9216-32.9216-32.9216-32.9216 13.1584-32.9216 32.9216S492.2368 563.2 512 563.2z" p-id="2272"></path>
            </svg>
        </label>
        <label class="max-lg:fixed inset-0 hidden bg-black bg-opacity-50 animate-fade-in-f peer-checked:block" for="filter-state"></label>
        {{-- main menu --}}
        <section class="rounded-xl bg-primary-bg transition-transform border-t-4 border-theme-bg1 duration-200 peer-checked:translate-y-0 shadow-lg inset-0 top-5 sticky min-h-[20rem]
        max-lg:fixed max-lg:top-[10%] max-lg:translate-y-full max-lg:rounded-b-none">
            {{-- top bar --}}
            <div class="px-5 py-4 flex justify-between items-center">
                <label class="tag.filter-toggle basis-1/4 cursor-pointer lg:hidden" for="filter-state">
                    <i class="btn-back arrow left w-4 block"></i>
                </label>
                <div class="grow text-center text-lg font-semibold">筛选器</div>
                <div class="tag.reset-btn basis-1/4 text-right text-red-500 text-sm cursor-pointer">重置</div>
            </div>


            {{-- filter block --}}
            <section class="tag.filter-block">
                @foreach ($filters as $filter)
                    {{-- header --}}
                    <div class="">
                        <input class="hidden peer" type="checkbox" value="checked" id="filter-{{ $filter['key'] }}">
                        <label class="p-3 flex items-center justify-between bg-theme-bg1 text-theme-fg1 font-semibold bg-opacity-5 cursor-pointer hover:bg-opacity-20
                        peer-checked:[&_.vtoggle]:rotate-180" for="filter-{{ $filter['key'] }}">
                            <div class="tag.filter-title line-clamp-2">
                                {{ $filter['title'] }}
                            </div>
                            <div class="vtoggle h-full aspect-square flex justify-center items-center origin-center transition-transform duration-200">
                                <i class="arrow up border-theme-bg1"></i>
                            </div>
                        </label>

                        {{-- option list --}}
                        <div class="tag.filter-list-{{ $filter['key'] }} p-3 peer-checked:hidden text-tertiary text-sm flex gap-2 flex-wrap">
                            @foreach ($filter['items'] as $item)
                                {{-- <div class="tag.filter-item flex gap-2 items-center">
                            <div class="w-4 h-4">
                                <x-checkbox1 :dom-id="$id" />
                            </div>
                            <label class="mt-px cursor-pointer select-none" for="{{ $id }}">内容</label>
                        </div> --}}
                                <div class="">
                                    <input class="hidden peer" type="checkbox" name="{{ $item['queryKey'] }}" value="{{ $item['value'] }}" id="{{ $item['queryKey'] }}-{{ $item['value'] }}">
                                    <label class="px-4 py-2 rounded-full border border-quaternary border-opacity-25 bg-theme-bg1 bg-opacity-0 flex items-center gap-2 cursor-pointer
                            hover:backdrop-contrast-90 active:bg-opacity-100 peer-checked:text-theme-fg1 peer-checked:bg-opacity-75 peer-checked:[&_.close-btn]:block" for="{{ $item['queryKey'] }}-{{ $item['value'] }}">
                                        <span class="">
                                            {{ $item['content'] }}
                                        </span>
                                        <div class="close-btn rounded-full w-3 h-3 hidden">
                                            <x-close-button />
                                        </div>
                                    </label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach



            </section>


        </section>
    </section>
    <section class="grow">
        <x-headline-style1 :title="'筛选'" />
        <div class="flex flex-wrap gap-x-3 gap-y-2 my-4">
            <div class="tag.active-btn-tpl hidden">
                <input class="hidden peer" type="checkbox" value="checked" name="eee" id="active-template">
                <label class="group px-3 py-1 rounded-full bg-quaternary-bg bg-opacity-25 border-quaternary-bg border text-sm text-secondary flex items-center gap-2 cursor-pointer hover:backdrop-contrast-90 hover:text-theme-bg1" for="active-template">
                    <span class="">TPL</span>
                    <div class="close-btn rounded-full w-2 h-2 text-quinary group-hover:text-theme-bg1">
                        <x-close-button />
                    </div>
                </label>
            </div>
        </div>

        <div class="tag.filter-result relative">
            {{-- @php($books = KarsonJo\BookPost\SqlQuery\BookFilterBuilder::create()->limit(6)->paged(1)->get_as_book()) --}}
            {{-- @php($books = TenQuality\WP\Database\QueryBuilder::create()->select('post_title')->from('posts')->get()) --}}
            <x-book-list-main :book-posts="$books" />
            <div class="absolute inset-0 pointer-events-none">

                <i class="tag.filter-loader loader-fade sticky top-0 mx-auto hidden text-theme-bg1 w-40 h-40"></i>
            </div>
            <div class="text-secondary text-center text-sm">
                <div class="tag.filter-error hidden">( ˘•ω•˘ )出错啦！请尝试刷新页面</div>
                <div class="tag.filter-empty text-sm hidden">( ˘•ω•˘ )没有这种东西啦！</div>
            </div>
        </div>
        <div class="tag.filter-pagination flex gap-2 justify-center text-sm my-3">
            @foreach ($pagination as $p)
                <a class="border-2 border-theme-bg1 rounded {{ $page == $p['page'] ? 'pointer-events-none bg-theme-bg1' : '' }}" href="{{ $p['url'] }}">
                    <div class="min-h-[2rem] min-w-[2rem] flex items-center justify-center p-2">{{ $p['page'] }}</div>
                </a>
            @endforeach
        </div>
    </section>
</div>
