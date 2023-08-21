import { basicHeader } from "./rest-utility.mjs"

const domain = "knc"
const version = "v1"
const namespace = `/wp-json/${domain}/${version}`

// ========== REST API ==========

/**
 * @param {string} username 
 * @param {string} password 
 * @param {boolean} remember 
 * @returns 
 */
export async function login(username, password, remember, redirectTo) {
    const href = `${namespace}/login`
    const headers = basicHeader()
    // 请求
    return await fetch(href, {
        method: "POST",
        body: JSON.stringify({
            username: username,
            password: password,
            remember: remember,
            redirectTo: redirectTo
        }),
        headers: headers
    })
}

/**
 * @param {object} userdata 
 * @returns 
 */
export async function updateUserdata(userdata) {

    const href = `${namespace}/userdata/update`
    const headers = basicHeader()
    // 请求
    return await fetch(href, {
        method: "POST",
        body: JSON.stringify(userdata),
        headers: headers
    })
}

// ========== Redirect method ==========
/**
 * 按照主题设置为链接添加末尾斜杠
 * 一般用于js生成的url
 * @param {string} value
 */
function userTrailingSlashIt(value) {
    return themeConfig ? value.trimEnd() + themeConfig.trailingSlash : value;
}

/**
 * 跳转到外链访问警告的页面
 * @param {string} target 
 */
export function toExternalWarn(target) {
    const url = new URL(userTrailingSlashIt("/external-redirect"), location.origin)
    url.searchParams.set('target', target)
    window.location.href = url.href
}