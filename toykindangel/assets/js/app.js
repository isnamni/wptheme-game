/* =========================================================
   فرشته مهربون — تعاملات سمت کلاینت (نسخهٔ WordPress-first)
   ---------------------------------------------------------
   v0.22.5: این فایل قبلاً رندرر کامل دمو بود
   (renderHome / renderCategories / pcard / catTile از روی
   window.TKA). همهٔ رندررها حذف شدند چون هم‌اکنون PHP/SSR
   همان HTML را با دادهٔ واقعی WooCommerce می‌سازد.

   فقط سه تعامل واقعی که به JavaScript نیاز دارند باقی مانده‌اند:
     1) hero()        — wiring اسلایدر بنر (scrollTo + autoplay)
     2) gallery()     — wiring گالری صفحه محصول (thumbs از DOM)
     3) startTimers() — تایمر شمارش معکوس تا نیمه‌شب

   هیچ وابستگی به window.TKA (دیتاست دمو) باقی نمانده است.
   ========================================================= */
(function () {
  "use strict";

  /* ---------- ابزارهای محلی ---------- */
  function icon(id, cls) {
    return '<svg class="ic ' + (cls || "") + '" aria-hidden="true"><use href="#' + id + '"></use></svg>';
  }
  function img(src, cls) {
    return '<img src="' + src + '" alt="" loading="lazy" referrerpolicy="no-referrer" class="' + (cls || "") +
           '" onerror="this.remove()">';
  }

  /* ---------- تایمر شمارش معکوس تا نیمه‌شب ---------- */
  function startTimers() {
    var nodes = document.querySelectorAll("[data-timer]");
    if (!nodes.length) return;
    function pad(n) { return n < 10 ? "0" + n : "" + n; }
    function tick() {
      var now = new Date();
      var end = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 24, 0, 0, 0);
      var left = Math.max(0, Math.floor((end - now) / 1000));
      var h = Math.floor(left / 3600), m = Math.floor((left % 3600) / 60), s = left % 60;
      for (var i = 0; i < nodes.length; i++) {
        nodes[i].innerHTML = "<b>" + pad(h) + "</b><i>:</i><b>" + pad(m) + "</b><i>:</i><b>" + pad(s) + "</b>";
      }
    }
    tick();
    setInterval(tick, 1000);
  }

  /* ---------- اسلایدر بنر بالا (hero) ----------
   * v0.22.5: همیشه از scrollTo استفاده می‌کنیم (هم موبایل هم دسکتاپ).
   * transform در RTL جهت اشتباه داشت (slide‌ها در RTL از راست به چپ
   * چیده می‌شوند ولی transform:translateX منفی آن‌ها را به چپ می‌برد
   * که در RTL یعنی slide بعدی، نه قبلی). scrollTo با scroll-snap
   * در RTL درست کار می‌کند.
   *
   * CSS: track همیشه overflow-x:auto + scroll-snap-type:x mandatory.
   * .hero overflow:visible (برای فلش‌ها).
   */
  function hero() {
    var el = document.querySelector(".hero");
    if (!el) return;
    var track = el.querySelector(".hero__track");
    var dots = el.querySelector(".hero__dots");
    var prevBtn = el.querySelector(".hero__arrow--prev");
    var nextBtn = el.querySelector(".hero__arrow--next");
    if (!track || !track.children.length) return;

    var count = track.children.length;
    if (count <= 1) return;

    var items = dots ? dots.children : [];
    var current = 0;

    function goTo(i) {
      i = ((i % count) + count) % count;
      current = i;
      var slideWidth = track.clientWidth;
      if (slideWidth > 0) {
        track.scrollTo({ left: i * slideWidth, behavior: "smooth" });
      }
      for (var k = 0; k < items.length; k++) {
        items[k].classList.toggle("on", k === i);
      }
    }
    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    if (nextBtn) nextBtn.addEventListener("click", function(e){ e.preventDefault(); next(); });
    if (prevBtn) prevBtn.addEventListener("click", function(e){ e.preventDefault(); prev(); });

    /* dot را با اسکرول هماهنگ کن (هم موبایل هم دسکتاپ) */
    track.addEventListener("scroll", function() {
      if (!track.clientWidth) return;
      var i = Math.round(track.scrollLeft / track.clientWidth);
      if (i !== current) {
        current = i;
        for (var k = 0; k < items.length; k++) {
          items[k].classList.toggle("on", k === i);
        }
      }
    }, { passive: true });

    /* autoplay */
    var interval = parseInt(el.getAttribute("data-hero-interval"), 10);
    if (interval > 0) {
      var timer = setInterval(next, interval);
      el.addEventListener("mouseenter", function(){ clearInterval(timer); });
      el.addEventListener("mouseleave", function(){
        clearInterval(timer);
        timer = setInterval(next, interval);
      });
      el.addEventListener("touchstart", function(){ clearInterval(timer); }, { passive: true });
      el.addEventListener("touchend", function(){
        clearInterval(timer);
        if (interval > 0) timer = setInterval(next, interval);
      }, { passive: true });
    }
  }

  /* ---------- گالری صفحه محصول ----------
   * thumbs از تصاویر موجود در #galleryTrack ساخته می‌شود (DOM-based،
   * نه dataset). کلیک روی thumb به آن slide اسکرول می‌کند. */
  function gallery() {
    var track = document.getElementById("galleryTrack");
    var thumbs = document.getElementById("galleryThumbs");
    if (!track || !thumbs) return;
    var imgs = [].slice.call(track.querySelectorAll("img")).map(function (i) { return i.getAttribute("src"); });
    thumbs.innerHTML = imgs.map(function (src, i) {
      return '<button class="' + (i === 0 ? "on" : "") + '" data-i="' + i + '">' + img(src) + "</button>";
    }).join("");
    function mark(i) {
      [].forEach.call(thumbs.children, function (x, k) { x.classList.toggle("on", k === i); });
    }
    thumbs.addEventListener("click", function (e) {
      var b = e.target.closest("button");
      if (!b) return;
      var i = +b.dataset.i;
      track.scrollTo({ left: i * track.clientWidth, behavior: "smooth" });
      mark(i);
    });
    track.addEventListener("scroll", function () {
      if (!track.clientWidth) return;
      mark(Math.round(track.scrollLeft / track.clientWidth));
    });
  }

  /* ---------- راه‌اندازی ---------- */
  document.addEventListener("DOMContentLoaded", function () {
    hero();
    gallery();
    startTimers();
  });
})();
