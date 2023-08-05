/**
 * fetch请求返回非200
 */
export class ResponseError extends Error {
    /**
     * 
     * @param {string} message 
     */
    constructor(message, data) {
        super(message)
        this.name = "ResponseError"
        this.data = data
    }
}