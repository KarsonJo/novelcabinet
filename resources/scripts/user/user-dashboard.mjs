import * as utils from "@scripts/dom-utils.mjs"
import * as novel from "@scripts/requests/theme-novel.mjs"

/**
 * 提交修改用户信息表单
 * @returns 
 */
function initProfileSettings() {
    const form = document.querySelector(".tag\\.update-profile-form")
    if (!form) return
    const submit = form.querySelector('button[type="submit"]')

    // 提交修改
    submit.addEventListener("click", async () => {
        const messageBox = form.querySelector(".tag\\.message-box")
        if (messageBox)
            messageBox.innerHTML = ""
        submit.disabled = true

        const formData = new FormData(form)
        console.log(utils.formData2Obj(formData))
        try {
            const task = novel.updateUserdata(utils.formData2Obj(formData))
            // const response = await doAndNotify(task)
            const response = await task

            const data = await response.json()

            if (data.message && messageBox) {
                const msgType = response.status !== 200 ? 'error' : data.type
                // 输出总的返回信息
                utils.setStyleLevelClass(messageBox, msgType)
                messageBox.innerHTML = data.message

                // 输出返回的fields更改
                for (const [name, prop] of Object.entries(data.fields)) {
                    // console.log(name)
                    updateInputField(name, prop.locked, prop.messages)
                }
            }
        }
        catch (error) {
            console.log(error)
        }
        finally {
            submit.disabled = false
        }
    })

    /**
     * 更新输入和它的validate信息
     */
    function updateInputField(inputName, disabled, messages) {
        const input = document.querySelector(`input[name="${utils.camelToHyphen(inputName)}"]`)
        // console.log(input)
        // const input = document.querySelector(inputName)
        const validateLilst = input.closest('.tag\\.input-field').querySelector("ul.tag\\.validate")
        // console.log(validateLilst)

        if (disabled)
            input.disabled = true

        validateLilst.innerHTML = "";
        for (const message of utils.asIterable(messages)) {
            const li = document.createElement("li")
            li.innerHTML = message
            validateLilst.appendChild(li)
        }
    }
}

function initDashboardMenu() {
    const dashboard = document.querySelector(".tag\\.user-dashboard")
    if (!dashboard) return

    const dashboardToggle = dashboard.querySelector(".tag\\.menu-button")
    const main = dashboard.querySelector(".tag\\.main")



    const mediaQueryLarge = window.matchMedia('(max-width: 1024px)')

    dashboardToggle.addEventListener("click", () => {
        dashboard.classList.toggle("opened")
        if (!mediaQueryLarge.matches) return;

        // 给最小宽度，实现推挤效果……花里胡哨
        if (dashboard.classList.contains("opened"))
            main.style.minWidth = `${main.offsetWidth}px`
        else
            main.style.minWidth = ''
    })
}

export function initUserDashboard() {
    // initUserMenuSwiper()
    initDashboardMenu()
    initProfileSettings()
}