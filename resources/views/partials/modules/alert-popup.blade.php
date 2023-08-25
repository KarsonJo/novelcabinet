<div class="tag.alert-list fixed inset-0 top-20 flex items-center flex-col gap-1 pointer-events-none z-50">
    {{-- @foreach (['normal', 'info', 'success', 'warning', 'error'] as $item) --}}
    <section class="tag.alert-popup relative border border-current backdrop-blur-md min-w-[min(80%,16rem)] max-w-[32rem] mx-16 px-8 py-2 rounded-lg shadow-lg transition-opacity duration-1000
    [&.style-normal]:text-theme-bg1 [&.style-info]:text-blue-300 [&.style-success]:text-green-300 [&.style-warning]:text-orange-200 [&.style-error]:text-red-300 style-normal group hidden not-show:opacity-0">
        <div class="absolute inset-0 bg-current opacity-50 -z-50 contrast-125 brightness-110"></div>
        <div class="text-primary group-[.style-normal]:text-theme-fg1">
            <h1 class="tag.alert-title font-semibold my-1">Lorem ipsum dolor sit amet.</h1>
            <p class="tag.alert-message opacity-80 text-sm">Lorem ipsum dolor sit amet consectetur adipisicing elit. Accusantium ab optio eaque voluptas quod officia nulla fugiat vitae dicta ea.</p>
        </div>
    </section>
    {{-- @endforeach --}}

</div>
