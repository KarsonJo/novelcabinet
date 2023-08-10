@php
    $user = wp_get_current_user();
    $newEmail = get_user_meta($user->ID, '_new_email', true)['newemail'] ?? false;
    $dismissAction = "{$user->ID}_new_email";
    $cancelNewEmailLink = add_query_arg(
        [
            'dismiss' => $dismissAction,
            '_wpnonce' => wp_create_nonce("dismiss-$dismissAction"),
        ],
        self_admin_url('profile.php'),
    );
@endphp
<div class="w-4/5 mx-auto my-8 bg-theme-bg1 bg-opacity-5 min-h-[32rem] max-w-5xl rounded-lg shadow-lg">
    <form class="tag.update-profile-form p-8 [&_input]:border-b [&_input]:border-quaternary [&_input]:bg-transparent [&_input]:outline-none focus:[&_input]:border-theme-bg1" action="javascript:void(0);" method="post">
        <h1 class="font-semibold text-2xl">基本信息</h1>
        <hr class="my-5 bg-quaternary">

        <section class="tag.basic-fields grid grid-cols-1 gap-5 md:grid-cols-2">
            <x-forms.input-field-flat1 title="用户名：" :message="['用户名不能更改']" for="username">
                <input class="disabled:text-quaternary h-10" disabled type="text" name="username" id="username" value="{{ $user->user_login }}">
            </x-forms.input-field-flat1>

            <x-forms.input-field-flat1 title="显示名称：" for="display-name">
                <input class="h-10" type="text" name="display-name" id="display-name" value="{{ $user->display_name }}" autocomplete="nickname">
            </x-forms.input-field-flat1>

            <x-forms.input-field-flat1 title="姓氏：" for="last-name">
                <input class="h-10" type="text" name="last-name" id="last-name" value="{{ $user->last_name }}" autocomplete="family-name">
            </x-forms.input-field-flat1>

            <x-forms.input-field-flat1 title="名字：" for="first-name">
                <input class="h-10" type="text" name="first-name" id="first-name" value="{{ $user->first_name }}" autocomplete="given-name">
            </x-forms.input-field-flat1>

            <x-forms.input-field-flat1 title="出生日期：" for="birthdate" autocomplete="bday">
                <input class="h-10" type="date" name="birthdate" id="birthdate" value="{{ $user->get('birthdate') }}">
            </x-forms.input-field-flat1>

            <x-forms.input-field-flat1 title="性别：" for="birthdate">
                <div class="h-10 flex items-center gap-2">
                    @foreach (NovelCabinet\User\GENDERS as $gender)
                        <input class="h-1/2 aspect-square accent-theme-bg1" type="radio" name="gender" id="gender" {{ $gender == $user->get('gender') ? 'checked' : '' }} value="{{ $gender }}">
                        <label for="male">{{ $gender }}</label>
                    @endforeach
                    {{-- <input class="h-1/2 aspect-square accent-theme-bg1" type="radio" name="gender" value="male">
                    <label for="male">男</label>
                    <input class="h-1/2 aspect-square accent-theme-bg1" type="radio" name="gender" value="female">
                    <label for="female">女</label> --}}
                </div>
            </x-forms.input-field-flat1>

            <x-forms.input-field-flat1 title="个人说明：" for="user-description">
                <textarea class="h-24 border-b border-quaternary bg-transparent outline-none focus:border-theme-bg1" type="text" name="user-description" id="user-description">{{ $user->user_description }}</textarea>
            </x-forms.input-field-flat1>

            <x-forms.input-field-flat1 title="头像：">
                <div class="h-24 flex flex-col gap-2">
                    <div class="grow relative">
                        <img class="absolute h-full aspect-square rounded-full border" src="{{ get_avatar_url($user->ID) }}">

                    </div>
                    <div class="text-sm text-tertiary">
                        在<a class="px-1 text-sky-500 underline" href="https://cravatar.cn">支持平台 <i class="fa-light fa-arrow-up-right-from-square"></i></a>修改头像
                    </div>
                </div>
            </x-forms.input-field-flat1>
        </section>

        <div class="h-12"></div>

        <h1 class="font-semibold text-2xl">账号资料</h1>
        <hr class="my-5 bg-quaternary">

        <section class="tag.account-fields grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-forms.input-field-flat1 title="原始密码：" for="curr-password" :message="['修改本部分信息须先验证身份']">
                <input class="h-10" type="password" name="curr-password" id="curr-password">
            </x-forms.input-field-flat1>

            <x-forms.input-field-flat1 title="电子邮箱：" for="email">
                <div class="h-10 flex gap-2">
                    <input class="h-full w-0 grow peer disabled:text-quaternary" type="text" name="email" id="email" required {{ $newEmail ? 'disabled' : '' }} value="{{ $user->user_email }}" autocomplete="email">
                    <a class="px-4 shrink-0 bg-theme-bg1 text-sm rounded shadow items-center hidden peer-disabled:flex" href="{{ $cancelNewEmailLink }}">撤销</a>
                    @if ($newEmail)
                        <x-slot:message-list>
                            <li class=" [&>span]:underline">{!! sprintf(__('user-msg-email-pending', 'NovelCabinet'), " <span>$newEmail</span> ") !!}</li>
                        </x-slot:message-list>
                    @endif
                </div>
            </x-forms.input-field-flat1>

            <x-forms.input-field-flat1 title="新密码：" for="new-password">
                <input class="h-10" type="password" name="new-password" id="new-password" autocomplete="new-password">
            </x-forms.input-field-flat1>

            <x-forms.input-field-flat1 title="确认密码：" for="repeat-password">
                <input class="h-10" type="password" name="repeat-password" id="repeat-password">
            </x-forms.input-field-flat1>
        </section>

        <div class="flex justify-between items-center mt-8">
            <div class="tag.message-box text-sm success:text-green-500 warning:text-yellow-500 error:text-rose-400"></div>
            <button class="cursor-pointer px-4 py-2 !bg-theme-bg1 shadow-md rounded disabled:saturate-0" type="submit">提交</button>
        </div>
    </form>
</div>
