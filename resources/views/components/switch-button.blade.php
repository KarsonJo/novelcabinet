<div class="w-full h-full">
    <input class="hidden peer" type="checkbox" name="{{ $name }}" id="{{ $id }}">
    <label class="block relative w-full h-full rounded-full bg-quaternary-bg transition cursor-pointer peer-checked:[&_label]:left-full peer-checked:[&_label]:-translate-x-full peer-checked:bg-green-400" for="{{ $id }}">
        <label class="h-full aspect-square absolute left-0 transition-all duration-500 cursor-pointer" for="{{ $id }}">
            <div class="absolute inset-0.5 bg-primary-bg rounded-full cursor-pointer"></div>
        </label>
    </label>
</div>
