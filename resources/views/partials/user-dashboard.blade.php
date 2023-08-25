{{-- <div class="tag.user-dashboard-menu swiper">
    <div class="swiper-wrapper">
        <nav class="swiper-slide menu max-w-xs flex-1 h-screen tag.nav bg-theme-bg1 flex flex-col transition-all">
            <div class="py-6 flex justify-center">
                <div class="h-32 w-32 bg-yellow-100 rounded-full">
                </div>
            </div>
            <div dir="rtl" class="ml-1 scrollbar-thin scrollbar-thumb-transparent [&:hover]:scrollbar-thumb-theme-fg1 overflow-y-scroll scrollbar-thumb-rounded-full gutter-stable">
                <ul dir="ltr" class="pl-4 my-6">
                    @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 2, 3, 4, 5, 6, 7, 8, 9] as $item)
                        <li class="tag.nav-item inverse-rounded-right hover:bg-primary-bg rounded-l-full transition-colors relative 
                            before:opacity-0 hover:before:opacity-100 before:bg-primary-bg after:opacity-0 hover:after:opacity-100 after:bg-primary-bg">
                            <a class="block p-5" href="">
                                <span class=""><i class="fa-light fa-user"></i></span>
                                <span class="">菜单项{{ $item }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </nav>
        <div class="swiper-slide content tag.main bg-primary-bg flex flex-col">
            <div class="block relative h-full aspect-square menu-button">
                <div class="rounded-full absolute h-1/10 w-full bg-current top-1/4"></div>
                <div class="rounded-full absolute h-1/10 w-4/5 bg-current top-1/2"></div>
                <div class="rounded-full absolute h-1/10 w-full bg-current top-3/4"></div>
            </div>
        </div>
    </div>
</div> --}}

@php
    
    use NovelCabinet\Services\Route\Enums\UserEndpoints;
    use KarsonJo\Utilities\Route\Router;
    use NovelCabinet\Helpers\WebHelpers;
    global $wp;
    $endpoint = UserEndpoints::tryFrom(Router::$data['userEndpoint']) ?? UserEndpoints::Settings;
    $menu = [
        UserEndpoints::Settings->value => [
            'displayName' => __('edit-profile'),
            'iconClass' => 'fa-user',
        ],
        UserEndpoints::Books->value => [
            'displayName' => __('all-books'),
            'iconClass' => 'fa-books',
        ],
    ];
@endphp
<div class="">
    <div class="tag.user-dashboard h-screen flex group/dashboard">
        <div class="tag.col-left transition-all   max-sm:w-0 max-xl:w-20 xl:w-80 group-[.opened]/dashboard:w-[min(80%,20rem)] xl:group-[.opened]/dashboard:w-20 relative">
            {{-- navigation bar --}}
            <nav class="tag.nav h-screen bg-theme-bg1 flex flex-col">
                <div class="py-6 whitespace-nowrap flex items-center">
                    <i class="text-5xl font-bold p-5 fa-brands fa-apple"></i>
                    <span class="p-2">阿婆的故事</span>
                </div>
                <div dir="rtl" class="ml-1 scrollbar-thin scrollbar-thumb-transparent [&:hover]:scrollbar-thumb-theme-fg1 overflow-y-scroll scrollbar-thumb-rounded-full gutter-stable">
                    <ul dir="ltr" class="tag-dashboard-menu pl-1 my-8 whitespace-nowrap">
                        @foreach ($menu as $segment => $item)
                            <li class="tag.nav-item inverse-rounded-right rounded-l-full bg-primary-bg bg-opacity-0 transition-colors relative {{ $segment === $endpoint->value ? 'selected' : '' }}
                                before:opacity-0 before:bg-primary-bg after:opacity-0 after:bg-primary-bg 
                                hover:bg-opacity-90 hover:before:opacity-90 hover:after:opacity-90 hover:text-theme-bg1
                                selected:bg-opacity-100 selected:before:opacity-100 selected:after:opacity-100 selected:text-theme-bg1">
                                <a class="py-4 flex items-center" href="{{ WebHelpers::getUserHomeUrl("/$segment") }}">
                                    <span class="font-semibold text-2xl px-3"><i class="fa-light {{ $item['iconClass'] }}"></i></span>
                                    <span class="px-5">{{ $item['displayName'] }}</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </nav>
        </div>

        {{-- 为了花里胡哨视觉效果：js中会修改子容器的大小溢出窗口大小，hidden很重要 --}}
        <div class="tag.col-right flex-1   transition-all relative overflow-hidden">
            <div class="tag.main absolute inset-0 bg-primary-bg flex flex-col">
                {{-- top bar --}}
                <div class="tag.top-bar shrink-0 p-2 h-16 flex">
                    <div class="tag.menu-button h-10 w-10 p-1 cursor-pointer">
                        <div class="block relative h-full aspect-square">
                            <div class="rounded-full absolute h-1/10 w-full bg-current top-1/4"></div>
                            <div class="rounded-full absolute h-1/10 w-4/5 bg-current top-1/2"></div>
                            <div class="rounded-full absolute h-1/10 w-full bg-current top-3/4"></div>
                        </div>
                    </div>
                    <div class="ml-auto mr-8 h-12 max-sm:hidden">
                        {{-- @include('partials.header.user-menu') --}}
                        <x-header.user-menu />
                    </div>
                </div>
                {{-- main content --}}
                <div class="tag-dashboard-loading loader-fade sticky top-0 mx-auto hidden text-theme-bg1 w-40 h-40"></div>
                <div class="tag-dashboard-content grow overflow-y-auto">
                    @switch($endpoint)
                        @case(UserEndpoints::Settings)
                            @include('sections.user.profile-settings')
                        @break

                        @case(UserEndpoints::Books)
                            @include('sections.user.user-booklist')
                        @break

                        @default
                    @endswitch

                </div>

            </div>
        </div>
    </div>

</div>



{{-- <div class="tag.user-dashboard-menu swiper">
    <div class="tag.user-dashboard swiper-wrapper h-screen">
        <nav class="tag.nav swiper-slide menu w-full max-w-xs h-screen bg-theme-bg1 flex flex-col">
            <div class="py-6 flex justify-center">
                <div class="h-40 w-40 bg-yellow-100 rounded-full"></div>
            </div>
            <div dir="rtl" class="ml-1 scrollbar-thin scrollbar-thumb-transparent [&:hover]:scrollbar-thumb-theme-fg1 overflow-y-scroll scrollbar-thumb-rounded-full gutter-stable">
                <ul dir="ltr" class="pl-4 my-6">
                    @foreach ([1, 2, 3, 4, 5, 6, 7, 8, 9, 1, 2, 3, 4, 5, 6, 7, 8, 9] as $item)
                        <li class="tag.nav-item inverse-rounded-right hover:bg-primary-bg rounded-l-full transition-colors relative 
                            before:opacity-0 hover:before:opacity-100 before:bg-primary-bg after:opacity-0 hover:after:opacity-100 after:bg-primary-bg">
                            <a class="block p-5" href="">
                                <span class=""><i class="fa-light fa-user"></i></span>
                                <span class="">菜单项{{ $item }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </nav>
        <div class="swiper-slide content tag.main bg-primary-bg flex flex-col">
            <div class="tag.top-bar p-2 h-16">
                <div class="h-10 w-10 p-1 cursor-pointer">
                    <div class="menu-button block relative h-full aspect-square">
                        <div class="rounded-full absolute h-1/10 w-full bg-current top-1/4"></div>
                        <div class="rounded-full absolute h-1/10 w-4/5 bg-current top-1/2"></div>
                        <div class="rounded-full absolute h-1/10 w-full bg-current top-3/4"></div>
                    </div>
                </div>
            </div>
            <div class="grow overflow-y-auto gutter-stable">
                @include('sections.profile-settings')
    
            </div>
        </div>
    </div>
    
</div> --}}
