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
import Swiper, { EffectCoverflow, Navigation, Pagination, Autoplay, Parallax } from "swiper";

import 'swiper/css';
import 'swiper/css/effect-coverflow'
import 'swiper/css/pagination'
import 'swiper/css/navigation'
function initCarousel() {
    const swiper = new Swiper('.swiper', {
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

import { initReaderSettings } from "./reader-settings.mjs";
import { initBookFinder } from "./book-finder.mjs";


export function siteInitialize() {
    // all
    initListeners()
    setDefaultValues()
    // index
    initCarousel()

    // reader
    initReaderSettings()
    readerSectionHighlight()

    // book finder
    initBookFinder()

}