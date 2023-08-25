import { removeClassWithPrefix } from "./dom-utils.mjs"

/**
 * 
 * @param {string} type 
 * @param {string} title 
 * @param {string} message 
 * @param {number} time 
 */
export function showAlert(type, title, message, time = 5000) {
    const classPrefix = "style-";
    // const container = document.querySelector(".tag\\.alert-list")
    const template = document.querySelector(".tag\\.alert-popup")

    if (!template) return

    const alert = template.cloneNode(true)
    alert.style.display = 'block'
    template.parentElement.appendChild(alert)

    // style
    removeClassWithPrefix(alert, classPrefix)
    switch (type) {
        case "info":
            alert.classList.add(classPrefix + "info")
            break;
        case "success":
            alert.classList.add(classPrefix + "success")
            break;
        case "warning":
            alert.classList.add(classPrefix + "warning")
            break;
        case "error":
            alert.classList.add(classPrefix + "error")
            break;

        default:
            alert.classList.add(classPrefix + "normal")
            break;
    }

    setTimeout(() => alert.classList.add("show"), 50) //延迟触发克隆元素的过渡动画，不知道为什么需要这样做
    alert.querySelector(".tag\\.alert-title").innerText = title ?? ''
    alert.querySelector(".tag\\.alert-message").innerText = message ?? ''

    setTimeout(() => {
        alert.classList.remove("show")
        alert.addEventListener("transitionend", () => alert.remove())
    }, time)
}