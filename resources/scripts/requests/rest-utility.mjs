export const basicHeader = () => new Headers({
    "Content-Type": "application/json",
    "Accept": "application/json",
    "X-WP-Nonce": wpApiSettings.nonce
})

/**
 * API的前缀：/wp-json/namspace/version
 */
export function restDomain(domain, version) {
    return `/wp-json/${domain}/${version}`
}