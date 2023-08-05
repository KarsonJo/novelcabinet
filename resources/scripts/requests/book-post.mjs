import { ResponseError } from "@scripts/errors.mjs"

function basicHeader() {
    return new Headers({
        "Content-Type": "application/json",
        "Accept": "application/json",
        "X-WP-Nonce": wpApiSettings.nonce
    })
}


/**
 * 请求为当前用户创建收藏夹
 * @param {string} title 
 * @param {number} visibility 
 * @returns {Promise<any>} json
 */
export async function createFavoriteList(title, visibility) {
    const href = "/wp-json/kbp/v1/fav/create"
    const headers = basicHeader()
    // 请求
    return await fetch(href, {
        method: 'POST',
        body: JSON.stringify({
            title: title,
            visibility: visibility,
        }),
        headers: headers
    })
}

/**
 * 
 * @param {number} postId 
 * @param {Array} favLists 
 * @returns 
 */
export async function updatePostFavorite(postId, favLists) {
    const href = "/wp-json/kbp/v1/post-fav/update/" + postId
    const headers = basicHeader()
    // 请求
    return await fetch(href, {
        method: 'POST',
        body: JSON.stringify({
            favLists: favLists,
        }),
        headers: headers
    })
}