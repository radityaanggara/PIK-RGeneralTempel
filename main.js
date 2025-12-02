/* ==========================================================
   main.js — Core UI Scripts
   PIK-R General Tempel Website
   ========================================================== */

// ========== Sticky Header ==========
window.addEventListener("scroll", () => {
    const header = document.querySelector("header");
    if (window.scrollY > 20) {
        header.classList.add("scrolled");
    } else {
        header.classList.remove("scrolled");
    }
});

// ========== Mobile Menu Toggle ==========
const menuBtn = document.querySelector("#menu-btn");
const navLinks = document.querySelector(".nav-links");

if (menuBtn) {
    menuBtn.addEventListener("click", () => {
        navLinks.classList.toggle("open");
    });
}

// Close nav when clicking a link (mobile only)
document.querySelectorAll(".nav-links a").forEach(link => {
    link.addEventListener("click", () => {
        navLinks.classList.remove("open");
    });
});

// ========== AOS-like On Scroll Animation ==========
const aosElements = document.querySelectorAll("[data-aos]");

function aosTrigger() {
    const triggerPoint = window.innerHeight * 0.85;

    aosElements.forEach(el => {
        const rect = el.getBoundingClientRect().top;

        if (rect < triggerPoint) {
            el.classList.add("aos-animate");

            // Delay system
            const delay = el.getAttribute("data-aos-delay");
            if (delay) {
                el.style.transitionDelay = delay + "ms";
            }
        }
    });
}

window.addEventListener("scroll", aosTrigger);
window.addEventListener("load", aosTrigger);

// ========== Smooth Scroll for internal links ==========
document.querySelectorAll('a[href^="#"]').forEach(link => {
    link.addEventListener("click", function (e) {
        const target = document.querySelector(this.getAttribute("href"));
        if (target) {
            e.preventDefault();
            window.scrollTo({
                top: target.offsetTop - 80,
                behavior: "smooth"
            });
        }
    });
});

document.addEventListener("keydown", e => {
    if (e.shiftKey && e.key === "A") {
        document.querySelector(".nav-admin").style.display = "inline-block";
    }
});

