import domReady from "@roots/sage/client/dom-ready";
import * as domUtils from "./dom-utils.mjs"

// =========Reader Settings=========
// =====Utility functions=====
/**
 * @param {Element[]} buttons 该选项的所有按钮
 * @param {Element} selected 选中的按钮
 */
function setButtonSelected(buttons, selected) {
    if (selected.classList.contains("selected"))
        return;
    buttons?.forEach((btn) => btn.classList.remove("selected"))
    selected.classList.add("selected")
}

// =====Get settings buttons=====
function getTextSizeSlider() {
    return document.querySelector(".r-text-slider")
}
function getThemeButtons() {
    return domUtils.collectElem(".r-theme-btn-")
}
function getDarkButton() {
    return document.querySelector(`.r-theme-btn-dark`)
}
function getFontButtons() {
    return domUtils.collectElem(".r-font-btn-")
}
function getWidthButtons() {
    return domUtils.collectElem(".r-max-w-btn-")
}

// =====Set selected style=====
function setThemeBtnStyle(btn) {
    let themes = getThemeButtons()
    themes.push(getDarkButton())
    setButtonSelected(themes, btn)
}
function setFontBtnStyle(btn) {
    setButtonSelected(getFontButtons(), btn)
}
function setMaxWidthBtnStyle(btn) {
    setButtonSelected(getWidthButtons(), btn)
}

// =====Settings function=====
function setTheme(btnClass) {
    const el = document.documentElement;

    if (el.classList.contains(btnClass))
        return;

    domUtils.removeClassWithPrefix(el, "r-theme-")
    el.classList.remove("dark")
    el.classList.add(btnClass)
}

/**
 * 改变一个元素某个按prefix+index模式组织的类
 * @param {string} selector 目标元素的选择器
 * @param {string} prefix 受影响类的前缀
 * @param {number} index 设置受影响类的索引
 * @returns 
 */
function indexdSettingsHelper(selector, prefix, index) {
    const el = document.querySelector(selector)
    const btnClass = prefix + index;

    if (el.classList.contains(btnClass))
        return;

    domUtils.removeClassWithPrefix(el, prefix)
    el.classList.add(btnClass)
}

function setFontFamily(index) {
    indexdSettingsHelper(".tag\\.book-reader", "r-font-", index)
}

function setMaxWidth(index) {
    indexdSettingsHelper(".tag\\.book-reader", "r-max-w-", index)
}

// =====Initialization=====


function readDefaultSettings() {
    //模拟点击（同时设置主题样式和按钮样式）
    document.querySelector(".r-theme-btn-0")?.click()

    const slider = getTextSizeSlider()
    if (slider) {
        slider.value = (parseInt(slider.min) + parseInt(slider.max)) / 2
        slider.dispatchEvent(new Event('input'))
    }

    document.querySelector(".r-font-btn-0")?.click()
    document.querySelector(".r-max-w-btn-1")?.click()
}

function initThemeButtons() {
    const registerBtn = (btn, themeClass) => btn?.addEventListener("click", () => {
        setTheme(themeClass)
        setThemeBtnStyle(btn)
    })

    //[r-theme-1, r-theme-2, ..., dark]
    getThemeButtons().forEach((btn, i) => registerBtn(btn, `r-theme-${i}`))
    registerBtn(getDarkButton(), "dark");
}



function readerSectionHighlight() {
    // let articles = document.querySelectorAll('article[class*="r-art-"]')
    let articles = document.querySelectorAll('article[data-art-id]')
    let inWindow = new Set()

    let callback = (entries) => {
        entries.forEach((entry) => {
            // 更新可见性记录
            if (entry.intersectionRatio > 0)
                inWindow.add(entry.target)
            else
                inWindow.delete(entry.target)
        })

        // 得到最顶可见元素
        let selected;
        for (let target of inWindow) {
            if (!selected || target.getBoundingClientRect().top < selected.getBoundingClientRect().top)
                selected = target;
        }

        // console.log(selected);

        // 修改导航标题为selected元素
        let title = selected?.querySelector(".chapter-title");

        document.querySelectorAll(".tag\\.pc-chpt").forEach(el => {
            el.textContent = title ? title.textContent : ""
        })

        //todo: 删除旧的
        document.querySelectorAll('.selected[data-cont-id]').forEach(el => el.classList.remove("selected"))
        document.querySelectorAll(`[data-cont-id="${selected.dataset.artId}"]`).forEach(el => el.classList.add("selected"))
        // console.log(title?.textContent)
    }
    let ob = new IntersectionObserver(callback, {})
    articles.forEach(x => ob.observe(x))
}



function initReaderSettings() {
    initThemeButtons()

    document.querySelector(".r-text-slider")?.addEventListener("input", function () {
        document.querySelector(".tag\\.book-reader").style.fontSize = `${1 + (this.value - 4) / 10}em`
    })

    getFontButtons().forEach((btn, i) => btn?.addEventListener("click", () => {
        setFontFamily(i)
        setFontBtnStyle(btn)
    }))

    getWidthButtons().forEach((btn, i) => btn?.addEventListener("click", () => {
        setMaxWidth(i)
        setMaxWidthBtnStyle(btn)
    }))

    readDefaultSettings()
}


domReady(async () => {
    initReaderSettings();
    readerSectionHighlight();

    // page reader
    document.querySelectorAll(".r-contents-toggle")?.forEach((res) => res.addEventListener("click", () => {
        document.querySelector(".tag\\.contents").classList.toggle('opened')
        document.documentElement.classList.toggle('overflow-hidden')
    }))
    document.querySelectorAll(".r-settings-toggle")?.forEach((res) => res.addEventListener("click", () =>
        document.querySelector(".tag\\.reader-settings").classList.toggle('opened')
    ))
});

