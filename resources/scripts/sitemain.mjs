
import Swiper, { EffectCoverflow, Navigation, Pagination, Autoplay, Parallax } from "swiper";

import 'swiper/css';
import 'swiper/css/effect-coverflow'
import 'swiper/css/pagination'
import 'swiper/css/navigation'

import { initUserDashboard } from "./user/user-dashboard.mjs";
import { initUserUtilities } from "./user/user-utility.mjs";
import { initReaderSettings } from "./reader-settings.mjs";
import { initBookFinder } from "./book-finder.mjs";
import { showAlert } from "./alert.mjs"
import { ResponseError } from "./errors.mjs"

import * as bookPost from "@scripts/requests/book-post.mjs"
import * as theme from "@scripts/requests/theme-novel.mjs"
import { initBookManager } from "./user/book-manager.mjs";


// =========External Link Warning=========
function initExternalLinkWarning() {
    document.addEventListener("click", (event) => {
        if (event.target.tagName.toLowerCase() === 'a') {
            const url = event.target.href

            console.log(url)
            if (url && new URL(url).origin !== location.origin) {
                event.preventDefault()
                theme.toExternalWarn(url)
            }
        }
    })

}
// =========BookIntro=========
function initRating() {
    const ratingStars = document.querySelector(".tag\\.rating")
    const ratingNum = document.querySelector(".tag\\.rating-num")
    const userRating = document.querySelector(".tag\\.rating-user")
    const userRatingNum = document.querySelector(".tag\\.rating-num-user")

    if (!ratingStars)
        return

    const originalRating = getComputedStyle(ratingStars).getPropertyValue("--rating")
    // let currRating = 0

    function voted() { return userRating.classList.contains("voted") }

    function setVoted(voted) { voted ? userRating.classList.add("voted") : userRating.classList.remove("voted") }

    /**
     * 获取鼠标当前位置代表的评分
     * @returns number [0,10]
     */
    function getScoreOnMousePos(pageX) {
        const containerRect = ratingStars.getBoundingClientRect()
        const mouseX = pageX - containerRect.left
        const percentage = mouseX / containerRect.width
        return Math.max(0, Math.min(10, Math.round(percentage * 100 / 10)))
    }

    function setRatingBar(rating) { ratingStars.style.setProperty("--rating", rating) }

    function setRatingNum(rating) { ratingNum.innerHTML = rating }

    function setUserRatingNum(rating) { userRatingNum.innerHTML = rating }

    function setUserRating(rating) {
        setRatingBar(rating)
        setUserRatingNum(rating)
    }

    function setRating(rating) {
        setRatingBar(rating)
        setRatingNum(rating)
    }

    function resetUserRating() { setUserRating(originalRating) }

    ratingStars.addEventListener("mouseout", () => voted() || resetUserRating())
    ratingStars.addEventListener("mousemove", (event) => voted() || setUserRating(getScoreOnMousePos(event.pageX)))
    ratingStars.addEventListener("click", () => voted() || sendRatingRequest(userRatingNum.innerHTML))


    async function sendRatingRequest(rating) {
        if (voted()) return false
        // console.log(rating)
        setVoted(true)

        const href = "/wp-json/kbp/v1/rate/" + getCurrentPostID()
        const headers = new Headers({
            "Content-Type": "application/json",
            "Accept": "application/json",
            "X-WP-Nonce": wpApiSettings.nonce
        })
        try {
            // 请求
            const response = await fetch(href, {
                method: 'POST',
                body: JSON.stringify({ rating: rating }),
                headers: headers
            })

            if (response.status != 200)
                throw new ResponseError("error response", response)
            // throw new Error("error response", { cause: response })

            const data = await response.json()

            console.log(data.avgRating)
            showAlert("success", data.title, data.message)
            setRating(data.avgRating)

        }
        catch (error) {
            console.log(`Error ${typeof (error)}`)
            console.log(error)
            setVoted(false)
            resetUserRating()

            if (error instanceof ResponseError) {
                const data = await error.response.json()
                showAlert("error", data.title, data.message)
            }
        }
    }
}


/**
 * utility: 请求一个数据，并把返回的title, message输出出来
 * @param {Promise<any>} task 
 */
async function doAndNotify(task) {
    try {
        const response = await task
        const cloned = response.clone()
        // console.log(response)
        const data = await cloned.json()
        if (cloned.status == 200) {
            showAlert("success", data.title, data.message)
        }
        else {
            showAlert("error", data.title, data.message)
        }
        return response
    }
    catch (error) {
        showAlert("error", "request error", error.message)
        throw error
    }
}

function initFavorite() {
    const addFavSubmit = document.querySelector(".tag\\.fav-submit-btn")
    const addFavToggle = document.querySelector("#fav-toggle")
    const createSubmit = document.querySelector(".tag\\.fav-create-submit")
    const createToggle = document.querySelector("#fav-create-toggle")

    if (!addFavSubmit)
        return;

    // 创建收藏夹
    createSubmit.addEventListener("click", async () => {
        const form = document.querySelector(".tag\\.fav-create-form")
        const formData = new FormData(form)


        try {
            createSubmit.disabled = true

            const title = formData.get("title")
            const visibility = formData.get("visibility") ? 1 : 0

            const task = bookPost.createFavoriteList(title, visibility)
            const response = await doAndNotify(task)

            if (response.status === 200) {
                const data = await response.json()
                createToggle.checked = false //关闭
                addFavlistItem(data.listId, data.listTitle)
            }
        }
        catch (error) {
            console.log(error)
        }
        finally {
            createSubmit.disabled = false
        }
    })

    function addFavlistItem(id, title) {
        const template = document.querySelector(".tag\\.fav-item")

        const cloned = template.cloneNode(true)
        template.parentElement.appendChild(cloned)

        const input = cloned.querySelector('input[name="fav-list-id"]')
        const titleElem = cloned.querySelector(".tag\\.fav-item-title")

        input.value = id
        titleElem.innerText = title
    }

    // 收藏文章
    addFavSubmit.addEventListener("click", async () => {
        const form = document.querySelector(".tag\\.add-fav-form")
        const formData = new FormData(form)
        try {
            addFavSubmit.disabled = true

            const postId = getCurrentPostID()
            const favLists = formData.getAll("fav-list-id").filter(x => x > 0) //去掉模板或无效

            const task = bookPost.updatePostFavorite(postId, favLists)
            const response = await doAndNotify(task)

            if (response.status === 200)
                addFavToggle.checked = false
        }
        catch (error) {
            console.log(error)
        }
        finally {
            addFavSubmit.disabled = false
        }

        // console.log(formData.getAll("fav-list-id"))
    })

    // 搜索（筛选）自己的收藏夹
    const searchInput = document.querySelector("#fav-search")
    const typeInterval = 500
    let typingTimer

    searchInput.addEventListener('keyup', () => {
        clearTimeout(typingTimer)
        typingTimer = setTimeout(liveSearch, typeInterval)
    })

    function liveSearch() {
        const items = document.querySelectorAll(".tag\\.fav-panel .tag\\.fav-item")
        const keyword = searchInput.value.toLowerCase()
        for (let item of items) {
            if (item.innerText.toLowerCase().includes(keyword))
                item.classList.remove("hidden")
            else
                item.classList.add("hidden")
        }
    }

}

function getCurrentPostID() {
    let postID = 0;
    const classPrefix = "postid-";
    for (let cls of document.body.classList)
        if (cls.startsWith(classPrefix))
            postID = parseInt(cls.slice(classPrefix.length))
    return postID;
}

function initExcerptShowMore() {
    const showMoreBtn = document.querySelector(".tag\\.excerpt-more")
    const excerpt = document.querySelector(".tag\\.book-excerpt")
    if (!excerpt)
        return
    const height = excerpt.clientHeight
    const maxHeight = parseInt(window.getComputedStyle(excerpt).maxHeight)
    if (!maxHeight || height < maxHeight) //没有最大高度，或未超过最大高度
        showMoreBtn.checked = true;
}


// =========Reader=========
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

// =========Initilization=========

function initListeners() {
    document.querySelector("#nav-toggle")?.addEventListener("click", function () {
        this.classList.toggle('opened')
    })

    // page reader
    document.querySelectorAll(".r-contents-toggle")?.forEach((res) => res.addEventListener("click", () => {
        document.querySelector(".tag\\.contents").classList.toggle('opened')
        document.documentElement.classList.toggle('overflow-hidden')
    }))
    document.querySelectorAll(".r-settings-toggle")?.forEach((res) => res.addEventListener("click", () =>
        document.querySelector(".tag\\.reader-settings").classList.toggle('opened')
    ))
}

function setDefaultValues() {
}

// =========Carousel=========

function initCarousel() {
    const swiper = new Swiper('.tag\\.banner-carousel.swiper', {
        modules: [EffectCoverflow, Navigation, Pagination, Autoplay, Parallax],
        init: true,
        // Optional parameters
        parallax: true,
        loop: true,
        centeredSlides: true,
        slidesPerView: "auto",
        // grabCursor: true,
        freeMode: false,
        mousewheel: false,
        speed: 800,
        // If we need pagination
        pagination: {
            el: '.swiper-pagination',
            type: 'bullets',
            clickable: true,
        },

        // Navigation arrows
        navigation: {
            enabled: true,
            nextEl: '.swiper-button-next',
            prevEl: '.swiper-button-prev',
        },
        // autoplay: {
        //     delay: 3000,
        //     disableOnInteraction: false,
        //     pauseOnMouseEnter: true,
        // },
        effect: 'coverflow',
        coverflowEffect: {
            // rotate: 30,
            // stretch: 100,
            depth: 200,

            // modifier:2.5,
            slideShadows: false,
            // scale: 0.5,
        },
        // breakpoints: {
        //     640: {
        //         slidesPerView: 1.25,
        //         spaceBetween: 20
        //     },
        //     1024: {
        //         slidesPerView: 1.5,
        //         spaceBetween: 20
        //     },
        //     1440: {
        //         slidesPerView: "auto",
        //         spaceBetween: 20
        //     }
        // }
    });
}



export function siteInitialize() {
    // all
    initListeners()
    setDefaultValues()
    initExternalLinkWarning()
    // index
    initCarousel()

    // book intro
    initRating()
    initExcerptShowMore()
    initFavorite()

    // reader
    initReaderSettings()
    readerSectionHighlight()

    // book finder
    initBookFinder()

    // user dashboard
    initUserDashboard()

    //user 
    initUserUtilities()
    initBookManager()
}