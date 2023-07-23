// =========Dom Utilities=========
export function removeClassWithPrefix(el, prefix) {
    for (let i = el.classList.length - 1; i >= 0; i--) {
        const className = el.classList[i]
        if (className.startsWith(prefix))
            el.classList.remove(className)
    }
}

/**
 * 找前缀一致，编号升序的元素
 * @param {string} prefix 前缀
 * @param {number} start 编号
 */
export function collectElem(prefix, start = 0) {
    const arr = []
    for (let i = start, res; (res = document.querySelector(`${prefix}${i}`)) != null; i++)
        arr.push(res)
    return arr
}
