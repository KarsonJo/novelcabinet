{{-- Navigation Bar --}}
<header class="banner flex justify-between min-h-[4rem] bg-theme-bg1 bg-opacity-10
sm:min-h-[5rem]">
    {{-- Brand --}}
    <div class="brand flex justify-start m-4 bg-slate-300">
        <a href="{{ home_url('/') }}">
            {!! $siteName !!}
        </a>
    </div>

    {{-- Mobile panel toggle --}}
    <label id="nav-toggle" class="group peer z-50 w-8 aspect-square fixed top-3 right-4 rounded text-theme-fg1 bg-theme-bg1 transition-colors duration-200
    sm:hidden [&:not(.opened)]:shadow-md [&.opened]:bg-transparent">

        <div class="block w-5 absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2">
            <span class="block absolute h-0.5 w-full bg-current transform transition duration-500 ease-in-out -translate-y-1.5 group-[.opened]:rotate-45 group-[.opened]:translate-y-0"></span>
            <span class="block absolute h-0.5 w-4/5 bg-current transform transition duration-500 ease-in-out group-[.opened]:opacity-0"></span>
            <span class="block absolute h-0.5 w-full bg-current transform transition duration-500 ease-in-out translate-y-1.5 group-[.opened]:-rotate-45 group-[.opened]:translate-y-0"></span>
        </div>
    </label>


    {{-- Interactable Area / Panel --}}
    <ul class="flex bg-theme-bg1 text-theme-fg1 font-normal z-40 gap-6 shadow-md
    supports-[backdrop-filter]:bg-opacity-20 supports-[backdrop-filter]:backdrop-blur-xl
    max-sm:fixed max-sm:inset-0 max-sm:left-full max-sm:w-[75vw] max-sm:flex-col max-sm:p-8 max-sm:pt-28
    max-sm:peer-[.opened]:-translate-x-full max-sm:duration-300 max-sm:transition-transform
    sm:pr-5 sm:rounded-l-xl">
        @if (has_nav_menu('primary_navigation'))
            <li class="sm:self-center">
                <nav id="nav-primary" class="nav-primary" aria-label="{{ wp_get_nav_menu_name('primary_navigation') }}">
                    <ul class="flex text gap-[clamp(1rem,3vw,3rem)]
                    sm:justify-center sm:px-[clamp(3rem,5vw,5rem)]
                    max-sm:flex-col">
                        @foreach ($navBarItems as $navBarItem)
                            <li class="transition-underline-center hover:trigger-underline
                            sm:h-20 sm:flex sm:items-center sm:justify-center
                            lg:min-w-[6rem]">
                                <a class="py-2 block" href="{{ $navBarItem->url }}">{{ $navBarItem->title }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>
            </li>
        @endif

        {{-- user info  --}}
        <li class="max-sm:-order-1 flex items-center gap-5">
            {{-- <div class="w-full h-full bg-slate-600 cursor-pointer p-4 mr-auto">
                <a href=""></a>
            </div> --}}
            @include('partials.header.user-menu')
        </li>
        {{-- search button --}}
        <li class="flex items-center">
            <div class="searchbox text-4xl hover:text-theme-bg1 cursor-pointer">
                <i class="fa-light fa-magnifying-glass"></i>
            </div>
        </li>
    </ul>
</header>
