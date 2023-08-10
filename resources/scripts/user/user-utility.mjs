import * as novel from "@scripts/requests/theme-novel.mjs"

function loginListener() {
    const form = document.querySelector(".tag\\.user-login-form")

    if (!form)
        return

    const submit = form.querySelector(".tag\\.user-login-submit")
    const passEye = form.querySelector(".tag\\.pass-eye")
    const passInput = document.querySelector(".tag\\.pass-input")


    // 显示密码
    passEye.addEventListener("click", () => passInput.type = passInput.type === "password" ? "text" : "password");

    // 登录
    submit.addEventListener("click", async () => {
        const formData = new FormData(form)

        try {
            submit.disabled = true
            
            const username = formData.get("username")
            const password = formData.get("password")
            const remember = formData.get("remember") ? true : false
            const redirectTo = formData.get("redirect-referer") ? document.referrer : ''

            const task = novel.login(username, password, remember, redirectTo)
            // const response = await doAndNotify(task)
            const response = await task

            const messageBox = form.querySelector(".tag\\.message-box")
            const data = await response.json()

            if (data.message)
                messageBox.innerHTML = data.message

            if (response.status === 302)
                window.location = data.location
        }
        catch (error) {
            console.log(error)
        }
        finally {
            submit.disabled = false
        }
    })
}


export function initUserUtilities() {
    loginListener()
}