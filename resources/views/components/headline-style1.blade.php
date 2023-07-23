<div class="tag.header1 flex justify-between items-center flex-wrap py-2">
    <h1 class="p-2 border-l-4 border-theme-bg1 border-solid text-xl font-semibold">
        {{ $title ?? 'Column Title' }}
    </h1>
    @if (isset($link) && $link !== '')
        <a class="p-2 whitespace-nowrap transition-underline-center hover:trigger-underline" href="{{ $link ?? '#' }}">
            <span class="px-2 text-slate-700">{{ $more ?? 'More' }}</span><i class="arrow right border-theme-bg1"></i>
        </a>
    @endif
</div>
