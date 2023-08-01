<div class="tag.catalog grid grid-cols-list-item gap-5">
    @foreach ($bookPosts as $post)
        <x-book-list-item-main :book="$post" />
    @endforeach
</div>
