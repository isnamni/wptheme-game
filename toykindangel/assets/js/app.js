/* =========================================================
   فرشته مهربون — تعاملات سمت کلاینت (نسخهٔ WordPress-first)
   ---------------------------------------------------------
   v0.21.0 refactor — این فایل قبلاً رندرر کامل دمو بود
   (renderHome / renderCategories / pcard / catTile از روی
   window.TKA). همهٔ رندررها حذف شدند چون هم‌اکنون PHP/SSR
   همان HTML را با دادهٔ واقعی WooCommerce می‌سازد.

   فقط سه تعامل واقعی که به JavaScript نیاز دارند باقی مانده‌اند:
     1) hero()        — wiring اسلایدر بنر (dots + autoplay بر اساس DOM)
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
   * v0.22.2: HTML از سمت سرور می‌آید. JS:
   *   - فلش‌های چپ/راست (دسکتاپ) با transform:translateX (قابل‌اعتمادتر از scrollTo)
   *   - dots هماهنگ با slide فعال
   *   - autoplay با interval قابل تنظیم از Customizer
   *   - touch/swipe در موبایل با scroll-snap + overflow:auto
   * اگر بنری نباشد یا فقط یک slide باشد، کاری نمی‌کند. */
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

    /* نشان دادن slide i با transform */
    function goTo(i) {
      i = ((i % count) + count) % count;
      current = i;
      var slideWidth = track.clientWidth;
      /* در دسکتاپ از transform استفاده کن (قابل‌اعتمادتر)، در موبایل از scrollTo */
      if (window.innerWidth >= 768) {
        track.style.transform = "translateX(" + (-i * slideWidth) + "px)";
      } else {
        track.scrollTo({ left: i * slideWidth, behavior: "smooth" });
      }
      /* dot فعال */
      for (var k = 0; k < items.length; k++) {
        items[k].classList.toggle("on", k === i);
      }
    }
    function next() { goTo(current + 1); }
    function prev() { goTo(current - 1); }

    if (nextBtn) nextBtn.addEventListener("click", function(e){ e.preventDefault(); next(); });
    if (prevBtn) prevBtn.addEventListener("click", function(e){ e.preventDefault(); prev(); });

    /* در موبایل: dot را با اسکرول هماهنگ کن */
    if (window.innerWidth < 768) {
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
    }

    /* resize: transform را دوباره محاسبه کن */
    window.addEventListener("resize", function() {
      goTo(current);
    });

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
