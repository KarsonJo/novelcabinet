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

/**
 * 设置示意消息重要性的css类，形如style-[type]
 * @param {Element} el 
 * @param {string} type success / warning / error
 */
export function setStyleLevelClass(el, type) {
    const prefix = 'style-'
    removeClassWithPrefix(el, prefix)
    el.classList.add(`${prefix}${type}`)
}

// =========Dom data processing=========
/**
 * 收集formData并转换成object，更改为驼峰命名
 * @param {FormData} formData
 * @param {boolean} keepEmpty 保留空字符串的数据
 */
export function formData2Obj(formData, keepEmpty = false) {
    let obj = {}
    for (const [originalKey, _] of formData) {
        // 转换为驼峰命名
        const key = hyphenToCamel(originalKey)

        if (key in obj)
            continue

        obj[key] = formData.getAll(originalKey)
        if (obj[key].length === 1)
            obj[key] = obj[key][0] // 拆解单值
    }

    // 清除空值
    if (!keepEmpty)
        for (const key in obj)
            if (!obj[key])
                delete obj[key]

    return obj
}

/**
 * 将连字符命名转换成驼峰命名
 * https://stackoverflow.com/questions/6660977/convert-hyphens-to-camel-case-camelcase
 * @param {将} str 
 * @returns 
 */
export function hyphenToCamel(str) {
    return str.replace(/-([a-z])/g, g => g[1].toUpperCase());
}

export function camelToHyphen(str) {
    return str.replace(/([a-z][A-Z])/g, g => g[0] + '-' + g[1].toLowerCase());
}

export function asIterable(el) {
    return el?.[Symbol.iterator] ? el : (el ? [el] : [])
}