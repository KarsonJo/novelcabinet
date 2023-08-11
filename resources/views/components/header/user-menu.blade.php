<div class="relative group/user-menu max-sm:w-[min(6rem,33%)] max-sm:aspect-square flex">
    <a class="tag-avatar flex-grow flex justify-center items-center text-6xl sm:text-4xl" href="{!! $profileUrl !!}">
        @if ($logged)
            <img class="h-full aspect-square rounded-full
            sm:h-12" src="{{ $avatar }}">
        @else
            <i class="fa-light fa-circle-user hover:text-theme-bg1 cursor-pointer"></i>
        @endif
    </a>
    <div class="tag-menu hidden animate-fade-in-f
                absolute left-1/2 -translate-x-1/2 top-full w-max min-w-[4rem] max-w-[8rem]
                pt-4 shadow-drop
                sm:group-hover/user-menu:block">
        {{-- Menu body --}}
        <div class="p-2 text-xs text-center rounded-md bg-theme-bg1 text-theme-fg1">
            @if ($logged)
                <div class="px-3 my-3 break-all">
                    Signed in as<div class="text-sm font-bold">{{ $name }}</div>
                </div>
                <div>
                    @foreach ($menu as $item)
                        <a class="px-3 py-1.5 block hover:bg-theme-fg1 hover:text-theme-bg1" href="{!! $item['url'] !!}" {{ $item['blank'] ? 'target="_blank"' : '' }}>
                            {{ $item['title'] }}
                        </a>
                    @endforeach
                </div>
            @else
                <div class="px-3 text-sm">
                    <a href="{!! $profileUrl !!}" class="text-theme-primary"> sign in </a>
                </div>
            @endif
        </div>
    </div>
</div>
