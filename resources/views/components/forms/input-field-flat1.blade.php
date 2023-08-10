<div class="tag.input-field flex flex-col gap-2 text-lg">
    <label class="text-sm text-quaternary" for="{{ $for }}">{{ $title }}</label>
    {{ $slot }}
    {{-- 不要改下面的format！！！使用了empty 必须顶格写…… --}}
    <ul class="tag.validate text-sm text-quaternary list-disc list-inside empty:hidden">@if(isset($messageList)){{ $messageList }}
@else
@foreach ($message as $item)<li>{{ $item }}</li>@endforeach
@endif</ul>
</div>
