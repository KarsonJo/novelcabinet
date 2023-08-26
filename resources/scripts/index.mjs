import Swiper, { EffectCoverflow, Navigation, Pagination, Autoplay, Parallax } from "swiper";

import 'swiper/css';
import 'swiper/css/effect-coverflow'
import 'swiper/css/pagination'
import 'swiper/css/navigation'
import domReady from "@roots/sage/client/dom-ready";

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


domReady(async () => {
    // index
    initCarousel()
});




