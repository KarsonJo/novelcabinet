{{-- CSS：当你写的代码是一坨holy shit但是能够跑起来：
说实话，但凡CSS支持插值函数，都不会需要Break Point的存在
如果你想要使用别的样式的轮播图，我建议不要修改这个文件，你重新写一个估计还更快一点 --}}
<section>
    <div class="mx-auto
    max-sm:max-h-72 max-sm:aspect-video
    sm:max-w-[min(90%,48rem)] sm:py-[5vh]
    lg:max-w-[unset]">
        <div class="tag.banner-carousel swiper [&_.swiper-slide>*]:opacity-50 [&_.swiper-slide>*]:transition-opacity [&_.swiper-slide>*]:duration-500 
            [&_.swiper-slide-prev>*]:opacity-50 [&_.swiper-slide-next>*]:opacity-50 [&_.swiper-slide-active>*]:opacity-100">
            <!-- Additional required wrapper -->
            <div class="swiper-wrapper">
                <!-- Slides -->

                @php
                    $test = ['https://th.bing.com/th/id/OIP.0qxWWiv5uAS-T2OK11jpawHaLZ?w=201&h=310&c=7&r=0&o=5&pid=1.7', 'https://th.bing.com/th/id/OIP.phO6zYlupAG15LsFLZwhuAHaL2?w=197&h=316&c=7&r=0&o=5&pid=1.7', 'https://th.bing.com/th/id/OIP.-KRIRXLmlauLveUi_0snUgHaLB?w=204&h=305&c=7&r=0&o=5&pid=1.7', 'https://th.bing.com/th/id/OIP.OhlI9JmNQmnIzJ00koH7ugHaKb?w=204&h=288&c=7&r=0&o=5&pid=1.7'];
                @endphp
                @foreach ($test as $item => $value)
                    {{-- Slide item --}}
                    <div class="swiper-slide aspect-video group/book
                    sm:aspect-[1.85/1]
                    md:aspect-[2/1]
                    lg:w-[min(48rem,75%)]">
                        {{-- Content wrapper --}}
                        <div class="h-[90%] w-full relative flex bg-zinc-700 rounded-lg shadow-md">
                            {{-- Background 1 --}}
                            <div class="absolute inset-0 overflow-hidden">
                                <img class="w-full h-full object-cover object-[50%_25%] opacity-50 blur-[clamp(1px,0.5vw,4px)] " src="{{ $value }}" alt="">
                            </div>
                            {{-- Book illustration --}}
                            <div class="basis-1/3 shrink-0 relative">
                                <div class="w-3/4 aspect-[3/4] absolute inset-0 m-auto 
                                sm:top-[unset] sm:translate-y-[10%]">
                                    {{-- <img class="w-full h-full" src="{{ $value }}" alt="book cover"> --}}
                                    {{-- @include('partials.elements.book') --}}
                                    <x-book-style1 :cover-src="$value"/>

                                </div>
                            </div>
                            {{-- Book info --}}
                            <div class="tag.content flex">
                                <div class="mb-2 px-[min(5vw,1.25rem)] py-[min(3vw,2rem)] flex flex-col justify-around" data-swiper-parallax="-30%" data-swiper-parallax-scale=".7">
                                    <div class="div">
                                        {{-- Title --}}
                                        <h1 class="text-xl md:text-2xl lg:text-3xl text-white">The Title {{ $item }}</h1>
                                        {{-- Author --}}
                                        <span class="text-sm lg:text-base text-slate-100 self-end">The Author</span>
                                    </div>
                                    {{-- Excerpt --}}
                                    <p class="text-[clamp(0.75rem,3vw,0.875rem)] line-clamp-3 text-slate-200
                                    lg:text-base lg:line-clamp-4">
                                        Lorem, ipsum dolor sit amet consectetur adipisicing elit. Veritatis adipisci harum itaque maxime perferendis voluptatem alias. Reiciendis porro quo eaque aut, corrupti sequi dolor alias, at corporis, inventore nostrum suscipit similique fugit ducimus deleniti. Delectus est id repudiandae saepe recusandae.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            <!-- If we need pagination -->
            <div class="swiper-pagination  !bottom-0"></div>

            <!-- If we need navigation buttons -->
            <div class="swiper-button-prev"></div>
            <div class="swiper-button-next"></div>
        </div>
    </div>

</section>
