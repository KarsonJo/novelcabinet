<div class="w-full aspect-square">
    <label class="relative flex cursor-pointer items-center rounded-full w-full h-full" for="{{ $id }}">
        <input id="{{ $id }}" value="{{ $value }}" name={{ $name }} {{ $checked ? 'checked' : '' }} type="checkbox" class="peer w-full h-full cursor-pointer appearance-none rounded-md border border-quaternary transition-all focus:ring-2 focus:ring-theme-bg1 checked:border-theme-bg1 checked:bg-theme-bg1" />
        <div class="absolute -inset-1/2 rounded-full bg-gray-300 opacity-0 transition-opacity peer-checked:bg-theme-bg1 peer-hover:opacity-25"></div>
        <div class="pointer-events-none absolute inset-[20%] text-white opacity-0 transition-opacity peer-checked:opacity-100">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-full h-full" viewBox="0 0 20 20" fill="currentColor" stroke="currentColor" stroke-width="1">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
            </svg>
        </div>
    </label>
</div>
