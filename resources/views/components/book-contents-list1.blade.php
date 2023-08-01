@if ($hasContent)
    @foreach ($volumes as $volume)
        {{-- Single Volume --}}
        <div class="tag.volume">
            {{-- Volume name and toggle --}}
            <input class="hidden peer" type="checkbox" value="checked" id="{{ 'vid-' . $volume->ID }}">
            <label class="p-3 my-3 flex items-center justify-between rounded-sm bg-theme-bg1 text-theme-fg1 text-sm font-semibold text-opacity-80 bg-opacity-10 cursor-pointer hover:bg-opacity-20
            sm:p-4 sm:my-4 peer-checked:[&_.vtoggle]:rotate-180" for="{{ 'vid-' . $volume->ID }}">
                <div class="line-clamp-2">
                    {{ $volume->post_title }}
                </div>
                <div class="vtoggle h-full aspect-square flex justify-center items-center origin-center transition-transform duration-200">
                    <i class="arrow up border-theme-bg1"></i>
                </div>
            </label>

            {{-- Chapters list --}}
            <ul class="grid gap-2 text-secondary text-sm sm:grid-cols-contents-item peer-checked:hidden">
                @foreach ($contents[$volume->ID] as $chapter)
                    <li class="hover:bg-theme-bg1 hover:bg-opacity-20 rounded-md mx-2 border-b flex items-center selected:font-bold selected:text-theme-bg1" data-cont-id="{{ $chapter->ID }}">
                        {{-- @php
                            $_post = new WP_Post((object) ['ID' => $chapter->ID, 'post_type' => 'book', 'post_status' => 'publish', 'post_title' => '123', 'post_name' => '456', 'post_parent' => 1]);
                            // get_permalink($_post);
                            wp_cache_add( $_post->ID, $_post, 'posts' );
                            // $_post = get_post($_post->ID);
                            // print_r($_post->filter)
                        @endphp --}}
                        <a href="{{ get_permalink($chapter->ID) }}" class="p-1 w-full sm:p-2">
                            <div class="line-clamp-2">
                                {{ $chapter->post_title }}
                            </div>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
@endif
