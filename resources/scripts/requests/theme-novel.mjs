import { basicHeader } from "./rest-utility.mjs"

const domain = "knc"
const version = "v1"
const namespace = `/wp-json/${domain}/${version}`

/**
 * @param {string} username 
 * @param {string} password 
 * @param {boolean} remember 
 * @returns 
 */
export async function login(username, password, remember, redirectTo) {
    const href = `${namespace}/login`
    const headers = basicHeader
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
    const headers = basicHeader
    // 请求
    return await fetch(href, {
        method: "POST",
        body: JSON.stringify(userdata),
        headers: headers
    })
}
