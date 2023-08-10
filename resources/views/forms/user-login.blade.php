<form class="tag.user-login-form max-w-xs flex flex-col gap-4 p-8 m-auto rounded-lg shadow-lg bg-theme-bg1 bg-opacity-5" action="javascript:void(0);" method="post">
    <x-forms.input-field-flat1 title="用户名：" for="username">
        <input class="outline-none border-b bg-transparent" type="text" name="username" autocomplete="username">
    </x-forms.input-field-flat1>

    <x-forms.input-field-flat1 title="密码：" for="password">
        <div class="flex border-b">
            <input class="tag.pass-input grow outline-none bg-transparent" type="password" name="password" autocomplete="current-password">
            <i class="tag.pass-eye h-4/5 aspect-square fa-light fa-eye"></i>
        </div>
    </x-forms.input-field-flat1>

    <div class="flex justify-between items-center">
        <div class="flex items-center gap-4">
            <div class="h-6 w-12">
                <x-switch-button />
            </div>
            <div class="text-sm text-secondary">保持登录</div>
        </div>
        <a href="{{ wp_lostpassword_url() }}" class="justify-self-end text-quaternary underline text-sm">
            忘记密码?
        </a>
        <input class="hidden peer" type="checkbox" name="redirect-referer" checked aria-hidden>
    </div>

    <div class="tag.message-box my-2 text-rose-400 text-sm"></div>
    <button class="tag.user-login-submit text-theme-fg1 bg-theme-bg1 rounded shadow px-4 py-2" type="submit">登录</button>
</form>
