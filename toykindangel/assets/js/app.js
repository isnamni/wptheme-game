/* =========================================================
   منطق نمونه — فرشته مهربون (نسخه موبایل)
   ========================================================= */
(function () {
  "use strict";

  var T = window.TKA;

  /* ---------- ابزارها ---------- */
  function fmt(n) { return String(n).replace(/\B(?=(\d{3})+(?!\d))/g, ","); }
  function icon(id, cls) {
    return '<svg class="ic ' + (cls || "") + '" aria-hidden="true"><use href="#' + id + '"></use></svg>';
  }
  /* اگر تصویری لود نشد، خودِ تصویر حذف می‌شود و Placeholder نمایش داده می‌شود */
  function img(src, cls) {
    return '<img src="' + src + '" alt="" loading="lazy" referrerpolicy="no-referrer" class="' + (cls || "") +
           '" onerror="this.remove()">';
  }
  function esc(s) { return String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;"); }

  /* ---------- کارت محصول ---------- */
  function pcard(p) {
    var off = p.off ? '<span class="pcard__off num">' + p.off + '%</span>' : "";
    var bnpl = p.bnpl ? '<span class="pcard__bnpl">' + icon("i-tag") + 'خرید قسطی</span>' : "";
    var old = p.old ? '<span class="pcard__old num">' + fmt(p.old) + '</span>' : '<span class="pcard__old"></span>';
    return '' +
      '<a class="pcard" href="' + (p.url || "#") + '">' +
        '<div class="pcard__media">' + img(p.img) +
          '<span class="ph">' + icon("i-cat-camera") + '</span>' + off +
        '</div>' + bnpl +
        '<div class="pcard__name">' + esc(p.name) + '</div>' +
        '<div class="pcard__prices">' + old +
          '<div class="pcard__price num"><span>' + fmt(p.price) + '</span><small>تومان</small></div>' +
        '</div>' +
      '</a>';
  }
  function moreCard(href) {
    return '<a class="morecard" href="' + (href || "#") + '"><i>' + icon("i-chev-left") + '</i>مشاهده همه</a>';
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

  /* ---------- اسلایدر بنر بالا ---------- */
  function hero() {
    var el = document.querySelector(".hero");
    if (!el || !T) return;
    var track = el.querySelector(".hero__track");
    var dots = el.querySelector(".hero__dots");
    /* SSR guard: when the server already rendered slides (WP data),
       only wire the dots/autoplay to the existing markup. */
    if (track.children.length) {
      if (dots && !dots.children.length) {
        dots.innerHTML = track.children.length && T.banners.hero
          ? T.banners.hero.map(function (_, i) { return '<i class="' + (i === 0 ? "on" : "") + '"></i>'; }).join("")
          : "";
      }
    } else {
    track.innerHTML = T.banners.hero.map(function (b) {
      return '<a class="hero__slide" href="' + b.href + '">' + img(b.img) + '</a>';
    }).join("");
    dots.innerHTML = T.banners.hero.map(function (_, i) {
      return '<i class="' + (i === 0 ? "on" : "") + '"></i>';
    }).join("");
    }
    var items = dots.children;
    track.addEventListener("scroll", function () {
      if (!track.clientWidth) return;
      var i = Math.round(track.scrollLeft / track.clientWidth);
      for (var k = 0; k < items.length; k++) items[k].classList.toggle("on", k === i);
    });
    var idx = 0;
    setInterval(function () {
      if (!track.clientWidth) return;
      idx = (idx + 1) % T.banners.hero.length;
      track.scrollTo({ left: idx * track.clientWidth, behavior: "smooth" });
    }, 4500);
  }

  /* ---------- صفحه اصلی ---------- */
  function renderHome() {
    if (!document.getElementById("home")) return;

    /* بنر تمام‌عرض بالای صفحه */
    var ts = document.getElementById("topStrip");
    if (ts && T.banners.strip) ts.innerHTML = img(T.banners.strip.img);

    /* ردیف دسته‌بندی‌ها (استوری‌مانند) — SSR guard: فقط وقتی خالی */
    var row = document.getElementById("storyRow");
    if (row && !row.children.length) {
      var html = T.stories.map(function (s) {
        return '<a class="story" href="categories.html">' +
          '<span class="story__img">' + img(s.img) + '</span>' +
          '<span class="story__label">' + esc(s.name) + "</span></a>";
      }).join("");
      html += '<a class="story" href="categories.html">' +
        '<span class="story__img" style="display:grid;place-items:center;background:var(--primary-light);color:var(--primary)">' +
        '<svg class="ic" style="font-size:34px"><use href="#i-grid"></use></svg></span>' +
        '<span class="story__label">همه دسته‌ها</span></a>';
      row.innerHTML = html;
    }

    /* اسلایدرهای محصول — SSR guard: فقط وقتی خالی */
    function fill(id, list, withMore) {
      var el = document.getElementById(id);
      if (el && !el.children.length) el.innerHTML = list.map(pcard).join("") + (withMore ? moreCard("#") : "");
    }
    fill("amazingTrack", T.products.amazing, true);
    fill("newestTrack", T.products.newest, true);
    fill("bestTrack", T.products.best, true);

    /* گرید بنرها — SSR guard */
    var g = document.getElementById("bannerGrid");
    if (g && !g.children.length) {
      g.innerHTML = T.banners.grid.map(function (b) {
        return '<a class="banner" href="' + b.href + '">' + img(b.img) + '</a>';
      }).join("");
    }

    /* برندها — SSR guard */
    var br = document.getElementById("brandRow");
    if (br && !br.children.length) {
      br.innerHTML = T.brands.map(function (b) {
        return '<a class="brand" href="#"><span class="brand__img">' + img(b.img) +
               '</span><span>' + b.name + "</span></a>";
      }).join("");
    }
  }

  /* ---------- صفحه دسته‌بندی‌ها (AllCategories) ---------- */
  /* ---------- صفحه دسته‌بندی‌ها ----------
     ساختار دقیقاً مطابق /categories موبایل اسنپ‌شاپ:
       ستون راست   = دسته‌های اصلی (آیکن ۴۴px + برچسب ۱۲px، سطر ۹۲px،
                     نوار ۵px رنگ اصلی برای موردِ فعال)
       پنل راست‌به‌چپ = «مشاهده همه محصولات» + سرگروه‌ها +
                     شبکهٔ ۳ ستونهٔ کاشی‌های زیردسته (تصویر ۶۴px + برچسب ۲ خط)   */
  /* تصویرِ کاشی: اگر تصویر محصول لود نشد، یک تصویر محلی جایگزین می‌شود */
  function catTile(name, src, fb) {
    var tag = fb
      ? '<img src="' + src + '" alt="" loading="lazy" referrerpolicy="no-referrer" ' +
        'onerror="this.onerror=null;this.src=\'' + fb + '\'">'
      : img(src);
    return '<a class="ctile" href="#">' +
             '<span class="ctile__img">' + tag + '</span>' +
             '<span class="ctile__lb">' + esc(name) + '</span>' +
           '</a>';
  }

  /* اگر true باشد همهٔ گروه‌ها باز می‌شوند؛
     false (پیش‌فرض = رفتار اسنپ‌شاپ) فقط یک گروه باز است */
  var CAT_OPEN_ALL = false;

  function renderCategories() {
    var rail = document.getElementById("catRail");
    var panel = document.getElementById("catPanel");
    if (!rail || !panel) return;

    var pool = (T.productImgs && T.productImgs.length) ? T.productImgs : [""];
    var fbPool = (T.catIcons && T.catIcons.length) ? T.catIcons : [""];
    var n = 0;
    function nextImg() {
      var i = n++;
      return { src: pool[i % pool.length], fb: fbPool[i % fbPool.length] };
    }

    rail.innerHTML = T.categories.map(function (c, i) {
      return '<button class="crail__item' + (i === 0 ? " on" : "") + '" data-i="' + i + '">' +
               '<img class="crail__ico" src="' + c.img + '" alt="" loading="lazy">' +
               "<span>" + esc(c.name) + "</span>" +
             "</button>";
    }).join("");

    function paint(i) {
      var c = T.categories[i];
      var html = '<a class="call" href="#"><span>مشاهده همه محصولات</span>' + icon("i-chev-left") + "</a>";
      c.groups.forEach(function (g, gi) {
        html +=
          '<div class="cgroup' + (CAT_OPEN_ALL || gi === 0 ? " cgroup--open" : "") + '">' +
            '<button class="cgroup__head" type="button"><b>' + esc(g.title) + "</b>" +
              icon("i-chev-left") + "</button>" +
            '<div class="cgrid">' +
              g.items.map(function (it) { var p = nextImg(); return catTile(it, p.src, p.fb); }).join("") +
              (function () { var p = nextImg(); return catTile("همه کالاها", p.src, p.fb); })() +
            "</div>" +
          "</div>";
      });
      panel.innerHTML = html;
      panel.scrollTop = 0;
    }
    paint(0);

    /* باز/بسته شدن گروه‌ها (آکاردئون — فقط یک گروه در هر لحظه باز است) */
    panel.addEventListener("click", function (e) {
      var head = e.target.closest(".cgroup__head");
      if (!head) return;
      var grp = head.parentNode;
      var open = grp.classList.contains("cgroup--open");
      [].forEach.call(panel.querySelectorAll(".cgroup"), function (g) { g.classList.remove("cgroup--open"); });
      if (!open) grp.classList.add("cgroup--open");
    });

    rail.addEventListener("click", function (e) {
      var btn = e.target.closest(".crail__item");
      if (!btn) return;
      var prev = rail.querySelector(".crail__item.on");
      if (prev) prev.classList.remove("on");
      btn.classList.add("on");
      try { btn.scrollIntoView({ block: "nearest" }); } catch (err) {}
      paint(+btn.dataset.i);
    });

    /* جستجو در کل درخت دسته‌بندی */
    var s = document.getElementById("catSearch");
    if (s) {
      s.addEventListener("input", function () {
        var q = s.value.trim();
        if (!q) {
          var act = rail.querySelector(".crail__item.on");
          paint(act ? +act.dataset.i : 0);
          return;
        }
        var res = [];
        T.categories.forEach(function (c) {
          c.groups.forEach(function (g) {
            g.items.forEach(function (it) {
              if (it.indexOf(q) > -1 || g.title.indexOf(q) > -1 || c.name.indexOf(q) > -1) {
                if (res.indexOf(it) === -1) res.push(it);
              }
            });
          });
        });
        panel.innerHTML = res.length
          ? '<a class="call" href="#"><span>نتایج جستجو</span>' +
            '<small class="num" style="color:var(--gray-500);font-weight:400">' + res.length + " مورد</small></a>" +
            '<div class="cgrid">' +
              res.slice(0, 60).map(function (r) { var p = nextImg(); return catTile(r, p.src, p.fb); }).join("") +
            "</div>"
          : '<div class="cempty">' + icon("i-search") +
            'نتیجه‌ای برای «' + esc(q) + "» یافت نشد</div>";
      });
    }
  }

  /* ---------- گالری صفحه محصول ---------- */
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

  /* ---------- سبد خرید (نمایشی) ---------- */
  var cartCount = 0;
  document.addEventListener("click", function (e) {
    var b = e.target.closest("[data-add-to-cart]");
    if (!b) return;
    e.preventDefault();
    cartCount++;
    [].forEach.call(document.querySelectorAll(".badge-count"), function (n) { n.textContent = cartCount; });
    var old = b.innerHTML;
    b.innerHTML = icon("i-check") + "به سبد خرید اضافه شد";
    b.style.filter = "saturate(.55)";
    setTimeout(function () { b.innerHTML = old; b.style.filter = ""; }, 1600);
  });

  /* ---------- راه‌اندازی ---------- */
  document.addEventListener("DOMContentLoaded", function () {
    hero();
    renderHome();
    renderCategories();
    gallery();
    startTimers();

    var path = location.pathname.split("/").pop() || "index.html";
    [].forEach.call(document.querySelectorAll(".bottomnav a"), function (a) {
      if (a.getAttribute("href") === path) a.classList.add("on");
    });
  });
})();
