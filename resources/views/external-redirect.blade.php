@php
    $notValidLinkMsg = __('not-valid-external-link', 'NovelCabinet');
@endphp
<div class="pt-40 px-4">
    {{-- <svg class="svg-inline--fa fa-triangle-exclamation" style="--fa-primary-color: #5f6f8c; --fa-secondary-color: #2f66c6;" aria-hidden="true" focusable="false" data-prefix="fad" data-icon="triangle-exclamation" role="img" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" data-fa-i2svg="">
        <g class="fa-duotone-group">
            <path class="fa-secondary" fill="currentColor" d="M256 32c14.2 0 27.3 7.5 34.5 19.8l216 368c7.3 12.4 7.3 27.7 .2 40.1S486.3 480 472 480H40c-14.3 0-27.6-7.7-34.7-20.1s-7-27.8 .2-40.1l216-368C228.7 39.5 241.8 32 256 32zm0 128c-13.3 0-24 10.7-24 24V296c0 13.3 10.7 24 24 24s24-10.7 24-24V184c0-13.3-10.7-24-24-24zm32 224a32 32 0 1 0 -64 0 32 32 0 1 0 64 0z"></path>
            <path class="fa-primary" fill="currentColor" d="M280 184c0-13.3-10.7-24-24-24s-24 10.7-24 24V296c0 13.3 10.7 24 24 24s24-10.7 24-24V184zM256 416a32 32 0 1 0 0-64 32 32 0 1 0 0 64z"></path>
        </g>
    </svg> --}}
    <div class="w-24 h-24 m-auto">
        <i class="fa-duotone text-8xl fa-triangle-exclamation" style="--fa-primary-color: rgb(var(--theme-primary-fg)); --fa-secondary-color: rgb(var(--theme-primary-bg));"></i>

    </div>
    <div class="flex flex-col gap-4 m-auto max-w-md py-8 px-6 bg-theme-bg1 bg-opacity-5 rounded-lg shadow-lg">
        <div id="external-warning-msg" class="text-tertiary">您即将离开本站，去往<span id="external-link" class="text-theme-bg1 underline"></span>，请注意账号安全。</div>
        <button id="external-jump" class="ml-auto py-2 px-4 bg-theme-bg1 rounded shadow">继续</button>
    </div>
</div>
{{-- 内联JavaScript，只跟随页面加载，以防用户在其它页面偷偷利用 --}}
{{-- 有点丑，将就着看吧 --}}
<script type="text/javascript">
    const searchParams = new URLSearchParams(window.location.search)
    const target = searchParams.get("target")

    // target.startsWith('http') || target.startsWith('https')
    try {
        new URL(target)
        document.getElementById("external-link").innerText = target
        document.getElementById("external-jump").addEventListener("click", () => location.href = target)
    } catch {
        document.getElementById("external-warning-msg").innerText = "{{ $notValidLinkMsg }}";
    }
</script>
