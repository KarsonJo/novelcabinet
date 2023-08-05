// ==================== utility ====================
const clickEvent = new Event('click', { bubbles: true })
let abortSignal
let requestCount = 0

function asIterable(el) {
    return el?.[Symbol.iterator] ? el : (el ? [el] : [])
}

// ==================== query selectors ====================

function getFilterResultPanel(el) {
    return (el ?? document).querySelector(".tag\\.catalog")
}

function getPaginationPanel(el) {
    return (el ?? document).querySelector(".tag\\.filter-pagination")
}

function getFilterResultItems(el) {
    return (el ?? document).querySelectorAll(".tag\\.catalog>*")
}

function getFilterBtns(el) {
    return (el ?? document).querySelectorAll(".tag\\.filter-block input[name]")
}

function getResetBtn() {
    return document.querySelector(".tag\\.book-filter .tag\\.reset-btn")
}

function getPageBtns(el) {
    return (el ?? document).querySelectorAll(".tag\\.filter-pagination a")
}

function getFilterLatestBtns(el) {
    return (el ?? document).querySelectorAll('.tag\\.filter-list-latest input[name]')
}


// ==================== event handlers ====================

function addPageBtnClickHandler(els) {
    for (let el of asIterable(els))
        el.addEventListener("click", event => {
            requestForNewContent(el.href)
            event.preventDefault()
        })
}

function addFilterBtnClickHandler(els) {
    for (let el of asIterable(els))
        el.addEventListener("click", () => toggleBtnStyleActive(el))
}

function addFilterBtnChangeHandler(els) {
    for (let el of asIterable(els))
        el.addEventListener("change", () => requestForNewContent(generateContentUrl().href))
}

function addResetBtnClickHandler(els, filterBtns) {
    for (let el of asIterable(els))
        el.addEventListener("click", () => {
            resetBtns(filterBtns)
            requestForNewContent(generateContentUrl().href)
        })
}

function addFilterLatestBtnInputHandler(els) {
    for (let el of asIterable(els))
        el.addEventListener("input", () => {
            if (!el.checked)
                return;
            resetBtns(els, el)
        })
}

// ==================== dom operations ====================

/**
 * 复制活跃标签的模板物体
 * @param {object} obj 对接数据
 * @returns {Node} 克隆的物体
 */
function duplicateActiveItem(obj) {
    let el = document.querySelector(".tag\\.active-btn-tpl")

    if (!el) return el;


    let cloned = el.cloneNode(true)
    el.parentElement.appendChild(cloned)

    cloned.style.display = "block";
    cloned.id = obj.elemID //id
    cloned.dataset.index = obj.index; //index in origin list
    cloned.querySelector("span").innerText = obj.content //text content
    cloned.querySelector("label").setAttribute("for", obj.inputID) //for-

    sortActiveItems()
    return cloned

    /**
     * 重新排列物体的顺序
     */
    function sortActiveItems() {
        let el = document.querySelectorAll(".tag\\.active-btn-tpl[data-index]")

        let sorted = Array.from(el).sort((a, b) => a.dataset.index - b.dataset.index)
        sorted.forEach(e => e.parentElement.appendChild(e))
    }
}

function setFilterLoaderStyle(visible) {
    const el = document.querySelector(".tag\\.filter-result .tag\\.filter-loader")
    el.style.display = visible ? "block" : ""
}

function setFilterErrorStyle(visible) {
    const el = document.querySelector(".tag\\.filter-result .tag\\.filter-error")
    el.style.display = visible ? "block" : ""
}

function setFilterEmptyStyle(visible) {
    const el = document.querySelector(".tag\\.filter-result .tag\\.filter-empty")
    el.style.display = visible ? "block" : ""
}

function updateButtonStyleFromUrl() {
    const filterBtns = getFilterBtns()
    const queryKeys = Array.from(filterBtns, el => el.getAttribute("name"))
    const filters = getDataFromQueryString(queryKeys)

    for (let el of filterBtns) {
        const key = el.getAttribute("name")
        if (key in filters && filters[key].has(el.value)) {
            el.checked = true;
            el.dispatchEvent(clickEvent); // 只fire点击事件
        }
    }
}

function updateContentIndicator() {
    // console.log(getFilterResultPanel(document).innerText.trim().length == 0);
    const empty = getFilterResultPanel(document).innerText.trim().length == 0
    setFilterEmptyStyle(empty)
}

// ==================== data processing ====================


/**
 * 从地址栏中找出给定键值的查询字符串
 * 若存在代表集合的元素，将分割
 * 分割符"-"与后端处理逻辑相对应
 * @param {string[]} keys 需要查找的键值
 * @returns {object} 字典，键：查询字符串键值，值：键对应值的集合
 */
function getDataFromQueryString(keys) {
    const searchParams = new URLSearchParams(window.location.search)
    let obj = {}
    keys.forEach(key => {
        if (searchParams.has(key) && !(key in obj))
            obj[key] = new Set(searchParams.get(key).replace(/^-+|-+$/g, '').split('-'))
    })
    // console.log(obj)
    return obj
}

function generateContentUrl() {
    const data = collectDataFromBtns()
    const query = parseDataToQueryPairs(data)

    const url = new URL(window.location.href)
    url.search = new URLSearchParams(query).toString()
    // console.log(url.href)
    return url;
    // requestForNewContent(url.href)


    function collectDataFromBtns() {
        let obj = {}
        for (let el of getFilterBtns()) {
            if (!el.checked)
                continue;
            const name = el.getAttribute("name")
            obj[name] = obj[name] || []
            obj[name].push(el.value)
        }
        // console.log(obj)
        return obj
    }

    function parseDataToQueryPairs(obj) {
        Object.keys(obj).forEach((key) => obj[key] = obj[key].join('-'))
        return obj
    }
}

async function requestForNewContent(href) {
    const header = new Headers({ "Accept": "text/html" })
    try {
        // 计数
        ++requestCount;
        // 终止现有的
        abortSignal?.abort()
        abortSignal = new AbortController()
        // 改变视觉
        const container = getFilterResultPanel(document)
        const pagination = getPaginationPanel(document)
        container.innerText = ""
        pagination.innerText = ""
        setFilterLoaderStyle(true)
        setFilterErrorStyle(false)
        setFilterEmptyStyle(false)

        // 请求
        const response = await fetch(href, {
            headers: header,
            signal: abortSignal.signal,
        })

        if (response.status != 200)
            throw new Error("error response", { cause: response })

        const html = await response.text()
        const doc = new DOMParser().parseFromString(html, 'text/html')

        // 改变url
        history.pushState('', '', href)

        // 填充内容
        const newContent = getFilterResultItems(doc)
        if (newContent && newContent.length > 0)
            newContent.forEach(el => container.appendChild(el))
        updateContentIndicator()

        // 分页
        pagination.replaceWith(getPaginationPanel(doc))
        addPageBtnClickHandler(getPageBtns())
    }
    catch (error) {
        if (error.name !== 'AbortError') {
            console.log(`Error ${typeof (error)}`, error)
            setFilterErrorStyle(true)
        }
    }
    finally {
        if (--requestCount == 0) {
            setFilterLoaderStyle(false)
        }
    }
}


/**
 * 将按钮的状态“样式”设置为活跃/非活跃
 * @param {Element} el 
 */
function toggleBtnStyleActive(el) {
    // console.log(el.getAttribute("name") + "=" + el.value + ", id=" + el.id, ", content=" + el.parentElement.querySelector('span').innerText + ", checked=" + el.checked)
    if (el.checked)
        duplicateActiveItem({
            value: el.value,
            inputID: el.id,
            index: el.dataset.index,
            elemID: "active-" + el.id,
            content: el.parentElement.querySelector("span").innerText
        })
    else
        document.querySelector("#active-" + el.id)?.remove()
}

function resetBtns(groupedBtns, self) {
    // 取消其它按钮的选择
    groupedBtns.forEach(btn => {
        if (btn != self && btn.checked == true) {
            btn.checked = false
            btn.dispatchEvent(clickEvent) // 只fire点击事件 // 模拟点击将它关闭
        }
    })
}

/**
 * 返回所有filter <input>
 */
export function initBookFinder() {
    if (document.querySelector(".tag\\.book-filter") === null)
        return;

    const filterBtns = getFilterBtns()
    const filterLatestBtns = getFilterLatestBtns()
    const resetBtn = getResetBtn()
    const pageBtns = getPageBtns()


    // 编号
    filterBtns.forEach((el, i) => el.dataset.index = i)

    // 分类点击事件
    addFilterBtnClickHandler(filterBtns)
    // 分类按钮改变事件
    addFilterBtnChangeHandler(filterBtns)

    addFilterLatestBtnInputHandler(filterLatestBtns)

    addResetBtnClickHandler(resetBtn, filterBtns)
    addPageBtnClickHandler(pageBtns)


    // 状态初始化
    updateButtonStyleFromUrl()
    updateContentIndicator()
}