// import { ResponseError } from "@scripts/errors.mjs"
import { basicHeader, restDomain } from "./rest-utility.mjs"

const namespace = restDomain("kbp", "v1")

/**
 * 请求为当前用户创建收藏夹
 * @param {string} title 
 * @param {number} visibility 
 * @returns {Promise<any>} json
 */
export async function createFavoriteList(title, visibility) {
    const href = `${namespace}/fav/create`
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
 * @param {number} postId 
 * @param {Array} favLists 
 * @returns 
 */
export async function updatePostFavorite(postId, favLists) {
    const href = `${namespace}/post-fav/update/${postId}`
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

async function restFetchContents(postId, init = undefined) {
    const href = `${namespace}/contents/${postId}`
    return await fetch(href, init)
}

export async function getContents(postId) {
    return await restFetchContents(postId, {
        method: 'GET',
        headers: basicHeader()
    })
}

export async function updateContents(postId, bookHierarchy) {
    return await restFetchContents(postId, {
        method: 'PATCH',
        headers: basicHeader(),
        body: JSON.stringify({ hierarchy: bookHierarchy })
    })
}

/**
 * 
 * @param {number} postId 
 * @param {RequestInit} init 
 * @returns 
 */
async function restFetchPost(postId, init = undefined) {
    const href = `${namespace}/posts/${postId}`
    return await fetch(href, init)
}

export async function renamePost(postId, title) {
    return restFetchPost(postId, {
        method: 'PATCH',
        headers: basicHeader(),
        body: JSON.stringify({ title: title })
    })
}

async function updatePost(postId, body) {
    return restFetchPost(postId, {
        method: 'PATCH',
        headers: basicHeader(),
        body: JSON.stringify(body)
    })
}

export async function trashPost(postId) {
    return updatePost(postId, { trashed: true });
}

export async function untrashPost(postId) {
    return updatePost(postId, { trashed: false });
}

export async function deletePost(postId) {
    return restFetchPost(postId, {
        method: 'DELETE',
        headers: basicHeader(),
    })
}