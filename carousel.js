/* ==========================================================
   carousel.js — Simple Gallery Carousel
   ========================================================== */

class Carousel {
    constructor(selector, interval = 3500) {
        this.container = document.querySelector(selector);
        if (!this.container) return;

        this.slides = this.container.querySelectorAll(".slide");
        this.current = 0;
        this.interval = interval;

        this.init();
    }

    init() {
        this.showSlide(0);
        this.startAutoPlay();
    }

    showSlide(index) {
        this.slides.forEach(s => s.classList.remove("active"));
        this.slides[index].classList.add("active");
        this.current = index;
    }

    next() {
        let nextIndex = this.current + 1;
        if (nextIndex >= this.slides.length) nextIndex = 0;
        this.showSlide(nextIndex);
    }

    startAutoPlay() {
        setInterval(() => this.next(), this.interval);
    }
}

// Initialize when loaded
window.addEventListener("load", () => {
    new Carousel("#gallery-carousel");
});
