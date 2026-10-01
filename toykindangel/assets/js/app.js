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
   * HTML از سمت سرور می‌آید (inc/front-ssr.php). JS فقط:
   *   - dots را می‌سازد (تعداد slideها از DOM خوانده می‌شود، نه از dataset)
   *   - اسکرول را با dot فعال هماهنگ می‌کند
   *   - autoplay هر ۴.۵ ثانیه
   * اگر بنری نباشد، کاری نمی‌کند. */
  function hero() {
    var el = document.querySelector(".hero");
    if (!el) return;
    var track = el.querySelector(".hero__track");
    var dots = el.querySelector(".hero__dots");
    if (!track || !track.children.length) return;

    /* dots را فقط اگر خالی است بساز (SSR ممکن است خودش ساخته باشد). */
    if (dots && !dots.children.length) {
      var n = track.children.length;
      var html = "";
      for (var i = 0; i < n; i++) {
        html += '<i class="' + (i === 0 ? "on" : "") + '"></i>';
      }
      dots.innerHTML = html;
    }
    var items = dots ? dots.children : [];
    track.addEventListener("scroll", function () {
      if (!track.clientWidth) return;
      var i = Math.round(track.scrollLeft / track.clientWidth);
      for (var k = 0; k < items.length; k++) items[k].classList.toggle("on", k === i);
    });
    var idx = 0;
    var count = track.children.length;
    if (count <= 1) return; /* autoplay فقط وقتی بیش از یک slide باشد. */
    setInterval(function () {
      if (!track.clientWidth) return;
      idx = (idx + 1) % count;
      track.scrollTo({ left: idx * track.clientWidth, behavior: "smooth" });
    }, 4500);
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
