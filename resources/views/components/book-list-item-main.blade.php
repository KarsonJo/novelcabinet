<div class="tag.catalog-item group min-h-[8rem] flex gap-4 p-2 hover:backdrop-contrast-75 transition-[backdrop-filter]">
    {{-- Left cover --}}
    <div class="tag.cover m-auto flex-shrink-0 w-[min(33%,8rem)]">
        <a href="{{ $permalink }}">
            <div class="w-full aspect-[3/4] relative">
                <x-book-style1 :cover-src="$coverSrc" />
            </div>
        </a>
    </div>
    {{-- Right info --}}
    <div class=" flex-grow flex flex-col justify-around">
        {{-- Info upper --}}
        <div>
            <a class="hover:bg-themb" href="{{ $permalink }}">
                <h1 class="mb-1 leading-tight line-clamp-2">{{ $title }}</h1>
            </a>
            <span class="my-1 text-xs leading-tight text-slate-700 line-clamp-1">{{ $author }}</span>
        </div>

        {{-- Info lower --}}
        <div class="text-xs text-slate-500">
            <p class="my-1 line-clamp-2">{{ $excerpt }}</p>
            @if ($tags)
                <div class="tag.tags m-1 flex flex-wrap gap-1 flex-row-reverse">
                    @foreach ($tags as $tag)
                        {{-- <div class="bg-slate-500 bg-opacity-10 px-1 py-0.5 rounded-md ">{{ $tag->name }}</div> --}}
                        <x-tag :tag="$tag->name" />
                    @endforeach
                    {{-- <div class="bg-slate-500 bg-opacity-10 px-1 py-0.5 rounded-md ">tag1</div>
                <div class="bg-slate-500 bg-opacity-10 px-1 py-0.5 rounded-md ">tag2</div>
                <div class="bg-slate-500 bg-opacity-10 px-1 py-0.5 rounded-md ">tag3</div> --}}
                </div>

            @endif
        </div>
    </div>
</div>
