
import { showAlert } from "./alert.mjs"
import * as theme from "@scripts/requests/theme-novel.mjs"


// =========External Link Warning=========
function initExternalLinkWarning() {
    document.addEventListener("click", (event) => {
        if (event.target.tagName.toLowerCase() === 'a') {
            const url = event.target.href

            console.log(url)
            if (url && new URL(url).origin !== location.origin) {
                event.preventDefault()
                theme.toExternalWarn(url)
            }
        }
    })

}

/**
 * utility: 请求一个数据，并把返回的title, message输出出来
 * @param {Promise<any>} task 
 */
export async function doAndNotify(task) {
    try {
        const response = await task
        const cloned = response.clone()
        // console.log(response)
        const data = await cloned.json()
        if (cloned.status == 200) {
            showAlert("success", data.title, data.message)
        }
        else {
            showAlert("error", data.title, data.message)
        }
        return response
    }
    catch (error) {
        showAlert("error", "request error", error.message)
        throw error
    }
}

// =========Initilization=========

function initListeners() {
    document.querySelector("#nav-toggle")?.addEventListener("click", function () {
        this.classList.toggle('opened')
    })
}




export function siteInitialize() {
    // all
    initListeners()
    initExternalLinkWarning()
}