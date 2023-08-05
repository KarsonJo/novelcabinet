<div class="tag.book group w-full h-full select-none">
        {{-- Inner --}}
        <div class="tag.inside bg-amber-100 rounded border border-solid border-zinc-700 h-[96%] relative top-[2%] shadow-book1-inner"></div>
    {{-- Cover placeholder --}}
    <div class="tag.book-cover absolute inset-0 origin-left style-3d bg-zinc-700 rounded shadow-sm transition-transform duration-500
    group-hover:translate-book1-open overflow-hidden">
        {{-- Cover --}}
        @if ($coverImage)
        <img class="absolute w-full h-full -z-10 object-cover" src="{{ $coverImage }}" alt="book cover">
        @endif
        {{-- Cover light1 --}}
        <div class="tag.effect w-2/12 h-full m-auto ml-[5%] 
        border-l-2 border-solid border-black border-opacity-5 
        bg-gradient-to-r from-white/30 to-transparent transition-[width] duration-500
        group-hover:w-3/12"></div>
        {{-- Cover light2 --}}
        <div class="tag.light absolute top-0 right-0 bottom-0 w-11/12 rounded opacity-10
        bg-gradient-to-r from-transparent to-white/30 transition-[all] duration-500
        group-hover:opacity-100 group-hover:w-8/12"></div>
    </div>

</div>
