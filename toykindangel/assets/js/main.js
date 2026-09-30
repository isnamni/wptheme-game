/**
 * ToyKind Angel — WordPress glue layer.
 * (demo logic itself lives in app.js — untouched)
 *
 *  1) Remaps the demo's relative asset paths to the real theme URI.
 *  2) Applies the server-provided dataset (window.TKA_WP — built by
 *     inc/front-data.php from Customizer + WooCommerce) onto the demo
 *     dataset BEFORE app.js renders at DOMContentLoaded.
 *  3) After render, fixes links the demo hardcodes (categories.html, "#").
 *  4) Back-to-top button.
 */
(function () {
        'use strict';

        var T = window.TKA;
        var B = window.TKA_BASE;
        var WP = window.TKA_WP;

        /* ---------- 1) Demo path rewrite ("assets/img/…" → TKA_BASE + path) ---------- */
        if (T && B) {
                var fix = function (p) {
                        return (typeof p === 'string' && p.indexOf('assets/') === 0) ? B + '/' + p : p;
                };
                ['hero', 'grid'].forEach(function (k) {
                        (T.banners[k] || []).forEach(function (b) { b.img = fix(b.img); });
                });
                if (T.banners.strip) { T.banners.strip.img = fix(T.banners.strip.img); }
                T.stories.forEach(function (s) { s.img = fix(s.img); });
                T.catIcons.forEach(function (s, i, a) { a[i] = fix(s); });
                T.categories.forEach(function (c) { c.img = fix(c.img); });
        }

        /* ---------- 2) Apply the WordPress dataset (Step 3) ----------
         * Every section is replaced ONLY when real WP data exists; empty
         * sections keep showing the demo sample while "fallback" is on.  */
        if (T && WP) {
                var fallback = WP.fallback !== false;

                if (WP.strip) { T.banners.strip = WP.strip; }
                if (WP.hero && WP.hero.length) { T.banners.hero = WP.hero; }
                if (WP.grid && WP.grid.length) { T.banners.grid = WP.grid; }
                if (WP.stories && WP.stories.length) { T.stories = WP.stories; }
                if (WP.brands && WP.brands.length) { T.brands = WP.brands; }

                /* Categories browser (Step 4): real WP terms replace the
                 * demo tree only when at least one top-level term exists
                 * (an empty browser would break app.js paint(0)). */
                if (WP.categories && WP.categories.length) {
                        T.categories = WP.categories.map(function (c, i) {
                                return {
                                        name: c.name,
                                        groups: c.groups || [],
                                        /* Rail icon: term thumbnail, falling back
                                         * to the local demo icon set. */
                                        img: c.img || T.catIcons[i % T.catIcons.length]
                                };
                        });
                }
                if (WP.productImgs && WP.productImgs.length) {
                        T.productImgs = WP.productImgs;
                }

                if (WP.products) {
                        ['amazing', 'newest', 'best'].forEach(function (k) {
                                if (WP.products[k] && WP.products[k].length) {
                                        T.products[k] = WP.products[k];
                                } else if (!fallback) {
                                        T.products[k] = [];
                                }
                        });
                }

                if (!fallback) {
                        if (!WP.stories) { T.stories = []; }
                        if (!WP.brands) { T.brands = []; }
                        if (!WP.hero) { T.banners.hero = []; }
                        if (!WP.grid) { T.banners.grid = []; }
                        if (!WP.products || !WP.products.amazing) { T.products.amazing = []; }
                        if (!WP.products || !WP.products.newest) { T.products.newest = []; }
                        if (!WP.products || !WP.products.best) { T.products.best = []; }
                }
        }

        document.addEventListener('DOMContentLoaded', function () {
                /* ---------- 3) Fix hardcoded demo links after render ---------- */
                var catUrl = WP && WP.catUrl ? WP.catUrl : '';
                if (catUrl) {
                        // Stories: app.js hardcodes href="categories.html".
                        var storyLinks = (WP && WP.stories) || [];
                        var storyAnchors = document.querySelectorAll('#storyRow a.story');
                        [].forEach.call(storyAnchors, function (a, i) {
                                a.href = (storyLinks[i] && storyLinks[i].href) ? storyLinks[i].href : catUrl;
                        });

                        // "مشاهده همه" cards at the end of each rail.
                        [].forEach.call(document.querySelectorAll('.morecard[href="#"]'), function (a) {
                                a.href = catUrl;
                        });

                        // Brand tiles (demo hardcodes "#").
                        var brandLinks = (WP && WP.brands) || [];
                        [].forEach.call(document.querySelectorAll('#brandRow a.brand'), function (a, i) {
                                if (brandLinks[i] && brandLinks[i].href) { a.href = brandLinks[i].href; }
                        });
                }

                /* ---------- 4) Categories browser glue (Step 4) ----------
                 * app.js paints the two-column browser but leaves every
                 * tile/link href as "#". Real URLs are attached here:
                 *   - positional mapping while a category is painted
                 *     (rail item i → catLinks[i], group j → groups[j])
                 *   - label matching for live-search results
                 * A MutationObserver re-applies it on every repaint
                 * (rail switch / accordion / search) — no demo edits. */
                var rail = document.getElementById('catRail');
                var catPanel = document.getElementById('catPanel');
                if (rail && catPanel && WP && WP.catLinks && WP.catLinks.length) {

                        var fixPanelLinks = function () {
                                var onBtn = rail.querySelector('.crail__item.on');
                                var idx = onBtn ? +onBtn.dataset.i : 0;
                                var data = WP.catLinks[idx] || null;
                                var call = catPanel.querySelector('a.call');
                                var groups = catPanel.querySelectorAll('.cgroup');

                                if (groups.length && data) {
                                        /* Normal mode: call → top-term archive,
                                         * tiles → child terms (last tile of each
                                         * group is the demo's "همه کالاها" card). */
                                        if (call && call.textContent.indexOf('مشاهده همه') > -1) {
                                                call.href = data.url || WP.catUrl || '#';
                                        }
                                        var gi = 0;
                                        [].forEach.call(groups, function (g) {
                                                var gd = data.groups && data.groups[gi++];
                                                if (!gd) { return; }
                                                [].forEach.call(g.querySelectorAll('a.ctile'), function (a, k) {
                                                        a.href = (k < gd.urls.length && gd.urls[k]) ? gd.urls[k] : (gd.url || '#');
                                                });
                                        });
                                } else if (call && call.querySelector('small')) {
                                        /* Search mode: header shows a <small> count —
                                         * match tiles by their visible label. */
                                        [].forEach.call(catPanel.querySelectorAll('a.ctile'), function (a) {
                                                var lb = a.querySelector('.ctile__lb');
                                                var name = lb ? lb.textContent : '';
                                                if (WP.catByName && WP.catByName[name]) {
                                                        a.href = WP.catByName[name];
                                                }
                                        });
                                }
                        };

                        if ('MutationObserver' in window) {
                                var panelMo = new MutationObserver(fixPanelLinks);
                                panelMo.observe(catPanel, { childList: true });
                        }
                        fixPanelLinks();

                        /* Deep link: /categories/#cat-N opens that rail item
                         * (app.js painted index 0 — clicking re-runs its own
                         * paint handler, keeping behaviour 1:1). */
                        var hashM = /^#cat-(\d+)$/.exec(location.hash || '');
                        if (hashM && rail.children[hashM[1]]) {
                                rail.children[hashM[1]].click();
                        }

                        /* Keep the hash in sync when the user switches category. */
                        rail.addEventListener('click', function (e) {
                                var btn = e.target.closest('.crail__item');
                                if (!btn || !btn.dataset.i) { return; }
                                try {
                                        history.replaceState(null, '', '#cat-' + btn.dataset.i);
                                } catch (err) { /* ignore */ }
                        });
                }

                /* Enter in the browser search jumps to the WP search page
                 * (the demo input only filters the tree). */
                var catSearch = document.getElementById('catSearch');
                if (catSearch && WP && WP.searchUrl) {
                        catSearch.addEventListener('keydown', function (e) {
                                if (e.key === 'Enter' && catSearch.value.trim()) {
                                        window.location.href = WP.searchUrl + '?s=' +
                                                encodeURIComponent(catSearch.value.trim());
                                }
                        });
                }

                /* ---------- 5) Back to top ---------- */
                var btn = document.getElementById('tka-backtop');
                if (btn) {
                        var toggleVisibility = function () {
                                var show = window.scrollY > 400;
                                /* hidden attribute + display none via CSS */
                                if (show) {
                                        btn.removeAttribute('hidden');
                                } else {
                                        btn.setAttribute('hidden', '');
                                }
                        };

                        window.addEventListener('scroll', toggleVisibility, { passive: true });
                        toggleVisibility();

                        btn.addEventListener('click', function () {
                                window.scrollTo({ top: 0, behavior: 'smooth' });
                        });
                }

                /* ---------- 6) PDP glue (Step 5) ----------
                 * The demo pdp__bar buttons are wired here instead of
                 * inline handlers (cleaner + CSP friendly):
                 *   - back  → history.back() when possible
                 *   - share → Web Share API with clipboard fallback
                 *   - fav   → localStorage wishlist + toast feedback */
                var toast = function (msg) {
                        var el = document.createElement('div');
                        el.className = 'tka-toast';
                        el.setAttribute('role', 'status');
                        el.textContent = msg;
                        document.body.appendChild(el);
                        setTimeout(function () { el.classList.add('show'); }, 10);
                        setTimeout(function () {
                                el.classList.remove('show');
                                setTimeout(function () { el.remove(); }, 350);
                        }, 2200);
                };

                /* PDP back button (demo used inline onclick="history.back()"). */
                [].forEach.call(document.querySelectorAll('.tka-back'), function (b) {
                        b.addEventListener('click', function () {
                                if (window.history.length > 1) {
                                        window.history.back();
                                } else {
                                        window.location.href = '/';
                                }
                        });
                });

                /* Share: native sheet → clipboard → legacy copy fallback. */
                [].forEach.call(document.querySelectorAll('.tka-share'), function (b) {
                        b.addEventListener('click', function () {
                                var url = b.dataset.shareUrl || location.href;
                                var title = b.dataset.shareTitle || document.title;
                                var legacyCopy = function () {
                                        var ta = document.createElement('textarea');
                                        ta.value = url;
                                        ta.setAttribute('readonly', '');
                                        ta.style.cssText = 'position:fixed;left:-9999px';
                                        document.body.appendChild(ta);
                                        ta.select();
                                        var ok = false;
                                        try { ok = document.execCommand('copy'); } catch (err) { ok = false; }
                                        ta.remove();
                                        toast(ok ? 'لینک محصول کپی شد' : url);
                                };
                                if (navigator.share) {
                                        navigator.share({ title: title, url: url }).catch(function () { /* cancelled */ });
                                } else if (navigator.clipboard && navigator.clipboard.writeText) {
                                        navigator.clipboard.writeText(url).then(function () {
                                                toast('لینک محصول کپی شد');
                                        }).catch(legacyCopy);
                                } else {
                                        legacyCopy();
                                }
                        });
                });

                /* Wishlist: localStorage list of product IDs + heart state. */
                var FAV_KEY = 'tka_favs';
                var readFavs = function () {
                        try {
                                var raw = window.localStorage.getItem(FAV_KEY);
                                var list = raw ? JSON.parse(raw) : [];
                                return Object.prototype.toString.call(list) === '[object Array]' ? list : [];
                        } catch (err) { return []; }
                };
                var writeFavs = function (list) {
                        try { window.localStorage.setItem(FAV_KEY, JSON.stringify(list)); } catch (err) { /* private mode */ }
                };
                /* Header heart badge (all pages). */
                var syncFavBadge = function () {
                        var n = readFavs().length;
                        [].forEach.call(document.querySelectorAll('.tka-fav-badge'), function (el) {
                                el.textContent = n;
                                el.hidden = !n;
                        });
                };
                syncFavBadge();
                [].forEach.call(document.querySelectorAll('.tka-fav'), function (b) {
                        var id = String(b.dataset.favId || '');
                        if (!id) { return; }
                        var sync = function () {
                                var on = readFavs().indexOf(id) > -1;
                                b.classList.toggle('on', on);
                                b.setAttribute('aria-pressed', on ? 'true' : 'false');
                        };
                        sync();
                        b.addEventListener('click', function () {
                                var list = readFavs();
                                var i = list.indexOf(id);
                                if (i > -1) {
                                        list.splice(i, 1);
                                        toast('از علاقه‌مندی‌ها حذف شد');
                                } else {
                                        list.push(id);
                                        toast('به علاقه‌مندی‌ها اضافه شد');
                                }
                                writeFavs(list);
                                sync();
                                syncFavBadge();
                        });
                });

                /* ---------- Wishlist page ----------
                 * Fetches real WC cards for the localStorage IDs through
                 * the tka_wishlist_render endpoint (inc/wishlist.php). */
                var wishGrid = document.getElementById('tkaWishGrid');
                if (wishGrid) {
                        var wishIds = readFavs();
                        var wishCount = document.getElementById('tka-wish-count');
                        var wishEmpty = document.getElementById('tkaWishEmpty');
                        if (!wishIds.length) {
                                wishGrid.setAttribute('aria-busy', 'false');
                                wishGrid.style.display = 'none';
                                if (wishEmpty) { wishEmpty.hidden = false; }
                                if (wishCount) { wishCount.textContent = '۰'; }
                        } else {
                                if (wishCount) { wishCount.textContent = String(wishIds.length); }
                                var body = new URLSearchParams();
                                body.append('action', 'tka_wishlist_render');
                                wishIds.forEach(function (id) { body.append('ids[]', id); });
                                fetch(window.TKA_AJAX || '/wp-admin/admin-ajax.php', {
                                        method: 'POST',
                                        credentials: 'same-origin',
                                        headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
                                        body: body.toString()
                                }).then(function (r) { return r.json(); }).then(function (res) {
                                        wishGrid.setAttribute('aria-busy', 'false');
                                        if (res && res.success && res.data && res.data.html) {
                                                wishGrid.innerHTML = res.data.html;
                                                [].forEach.call(wishGrid.querySelectorAll('.tka-wish-remove'), function (btn) {
                                                        btn.addEventListener('click', function () {
                                                                var id = String(btn.dataset.wishId || '');
                                                                var list = readFavs().filter(function (v) { return v !== id; });
                                                                writeFavs(list);
                                                                syncFavBadge();
                                                                var item = btn.closest('.tka-wish-item');
                                                                if (item) { item.remove(); }
                                                                if (!readFavs().length) {
                                                                        wishGrid.style.display = 'none';
                                                                        if (wishEmpty) { wishEmpty.hidden = false; }
                                                                        if (wishCount) { wishCount.textContent = '۰'; }
                                                                } else if (wishCount) {
                                                                        wishCount.textContent = String(readFavs().length);
                                                                }
                                                        });
                                                });
                                        } else {
                                                wishGrid.style.display = 'none';
                                                if (wishEmpty) { wishEmpty.hidden = false; }
                                        }
                                }).catch(function () {
                                        wishGrid.setAttribute('aria-busy', 'false');
                                        wishGrid.innerHTML = '<div class="tka-wishlist__loading">خطا در بارگذاری — دوباره تلاش کنید</div>';
                                });
                        }
                }

                /* Smooth scroll for the reviews anchor on the PDP. */
                [].forEach.call(document.querySelectorAll('a.tka-reviews-link[href="#reviews"]'), function (a) {
                        a.addEventListener('click', function (e) {
                                var target = document.getElementById('reviews');
                                if (target) {
                                        e.preventDefault();
                                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                }
                        });
                });

                /* Sticky CTA «مشاهده و خرید» jumps to the price box. */
                [].forEach.call(document.querySelectorAll('.tka-cta-alt[href="#pricebox-top"]'), function (a) {
                        a.addEventListener('click', function (e) {
                                var box = document.querySelector('.pricebox');
                                if (box) {
                                        e.preventDefault();
                                        box.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                }
                        });
                });

                /* ---------- Variable products: demo-style chips ----------
                 * The standard WC variation <select>s stay in the DOM (form
                 * integrity, gateway plugins, stock logic) but are visually
                 * replaced by chips. Chip click → select value + native
                 * 'change' (bubbles to WC's jQuery delegated handlers);
                 * WC-driven updates (reset link, disabled combos) sync back
                 * via the jQuery 'change'/'reset_data'/'check_variations'
                 * events, which native listeners can't see. */
                [].forEach.call(document.querySelectorAll('.variations_form.cart .variations select'), function (sel) {
                 try {
                        var td = sel.closest('td.value') || sel.parentElement;
                        if (!td) { return; }
                        var wrap = document.createElement('div');
                        wrap.className = 'tka-chips';
                        wrap.setAttribute('role', 'listbox');
                        wrap.setAttribute('aria-label', sel.closest('tr') ? (sel.closest('tr').querySelector('th.label') || {}).textContent || '' : '');
                        [].forEach.call(sel.options, function (opt) {
                                if (!opt.value) { return; }
                                var chip = document.createElement('button');
                                chip.type = 'button';
                                chip.className = 'tka-chip';
                                chip.setAttribute('role', 'option');
                                chip.dataset.value = opt.value;
                                chip.textContent = opt.textContent;
                                chip.addEventListener('click', function () {
                                        sel.value = (sel.value === opt.value) ? '' : opt.value;
                                        sel.dispatchEvent(new Event('change', { bubbles: true }));
                                        syncChips();
                                });
                                wrap.appendChild(chip);
                        });
                        if (!wrap.children.length) { return; }
                        sel.classList.add('tka-chips__source');
                        td.insertBefore(wrap, sel);
                        function syncChips() {
                                [].forEach.call(wrap.children, function (chip) {
                                        var opt = sel.querySelector('option[value="' + CSS.escape(chip.dataset.value) + '"]');
                                        chip.classList.toggle('on', sel.value === chip.dataset.value);
                                        chip.classList.toggle('disabled', !!(opt && opt.disabled));
                                        chip.setAttribute('aria-selected', sel.value === chip.dataset.value ? 'true' : 'false');
                                });
                        }
                        syncChips();
                        window.tkaSyncChips = window.tkaSyncChips || [];
                        window.tkaSyncChips.push(syncChips);
                 } catch (e) { /* chips are cosmetic — form stays functional */ }
                });
                if (window.jQuery && window.tkaSyncChips) {
                        window.jQuery(document).on('change reset_data check_variations woocommerce_update_variation_values', function () {
                                setTimeout(function () { window.tkaSyncChips.forEach(function (fn) { fn(); }); }, 60);
                        });
                }

                /* ---------- 7) Image delivery glue (Step 6) ----------
                 * The demo img() helper lazy-loads EVERY image — including
                 * the LCP hero slider. Without touching demo files we fix
                 * that here: the visible hero slide becomes eager +
                 * fetchpriority=high, every other image gets decoding=async
                 * (loading stays lazy from the demo itself). */
                [].forEach.call(document.querySelectorAll('.hero__track .hero__slide img'), function (img, i) {
                        img.decoding = 'async';
                        if (0 === i) {
                                img.loading = 'eager';
                                img.setAttribute('fetchpriority', 'high');
                        }
                });

                [].forEach.call(document.querySelectorAll('.banners img, .brands img, .stories img, .plist__track img, .offer__track img'), function (img) {
                        img.decoding = 'async';
                });

                /* ---------- 8) Cart quantity steppers (Step 8 UX glue) ----------
                 * The classic WC cart only ships a bare number input; demo-like
                 * Iranian shops use +/− steppers. Buttons are injected around
                 * the input and drive it through WC's own qty field so the
                 * «بروزرسانی سبد خرید» flow stays intact. */
                [].forEach.call(document.querySelectorAll('.woocommerce-cart table.shop_table .quantity'), function (q) {
                        var input = q.querySelector('input.qty');
                        if (!input || q.querySelector('.tka-step')) { return; }

                        var mk = function (label, cls) {
                                var b = document.createElement('button');
                                b.type = 'button';
                                b.className = 'tka-step ' + cls;
                                b.textContent = label;
                                b.setAttribute('aria-label', cls === 'minus' ? 'کاهش تعداد' : 'افزایش تعداد');
                                return b;
                        };

                        var step = function (delta) {
                                var val = parseInt(input.value, 10) || 0;
                                var min = parseFloat(input.getAttribute('min')) || 1;
                                var max = parseFloat(input.getAttribute('max'));
                                var next = val + delta;
                                if (next < min) { next = min; }
                                if (!isNaN(max) && max > 0 && next > max) { next = max; }
                                if (next === val) { return; }
                                input.value = next;
                                /* Same event WC's own handlers listen to. */
                                input.dispatchEvent(new Event('change', { bubbles: true }));
                                /* Classic cart needs the update button; do it silently. */
                                var form = input.closest('form.woocommerce-cart-form');
                                var upd = form ? form.querySelector('button[name="update_cart"]') : null;
                                if (upd) {
                                        upd.disabled = false;
                                        if (window.jQuery) {
                                                window.jQuery(upd).trigger('click');
                                        } else {
                                                upd.click();
                                        }
                                }
                        };

                        var minus = mk('−', 'minus');
                        var plus = mk('+', 'plus');
                        q.insertBefore(minus, input);
                        q.appendChild(plus);
                });

                /* ---------- 9) Desktop rail arrows (v0.7.0) ----------
                 * On desktop (≥1024px) the demo rails (amazing / newest /
                 * best / brands / related) become wide scrollers; injected
                 * prev/next buttons page them like a real desktop shop.
                 * Pure glue: demo markup untouched, buttons are additive. */
                [].forEach.call(document.querySelectorAll('.offer, .section, .brands-section'), function (sec) {
                        var track = sec.querySelector('.offer__track, .plist__track, .brands');
                        if (!track || sec.querySelector('.tka-rail')) { return; }
                        /* Only for horizontally scrollable rails. */
                        if (track.scrollWidth <= track.clientWidth + 48) { return; }

                        var mk = function (dir) {
                                var b = document.createElement('button');
                                b.type = 'button';
                                b.className = 'tka-rail tka-rail--' + dir;
                                b.setAttribute('aria-label', dir === 'next' ? 'مشاهده موارد بعدی' : 'مشاهده موارد قبلی');
                                b.innerHTML = '<svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg>';
                                b.addEventListener('click', function () {
                                        var amount = Math.max(280, track.clientWidth * 0.75);
                                        /* RTL: scrolling forward = negative scrollLeft. */
                                        var sign = dir === 'next' ? -1 : 1;
                                        track.scrollBy({ left: sign * amount, behavior: 'smooth' });
                                });
                                return b;
                        };

                        var sync = function () {
                                var max = track.scrollWidth - track.clientWidth;
                                /* RTL scrollLeft runs 0 → -max in modern browsers. */
                                var sl = Math.abs(track.scrollLeft);
                                prevBtn.disabled = sl <= 4;
                                nextBtn.disabled = sl >= max - 4;
                        };

                        sec.classList.add('tka-railsec');
                        var nextBtn = mk('next');
                        var prevBtn = mk('prev');
                        sec.appendChild(nextBtn);
                        sec.appendChild(prevBtn);
                        track.addEventListener('scroll', sync, { passive: true });
                        window.addEventListener('resize', sync);
                        sync();
                });

                /* ---------- 10) Drawers — primary menu + categories browser (v0.9) ----------
                 * Everything is scoped to .tka-drawer containers (#tka-menu-drawer /
                 * #tka-cats-drawer, printed by inc/cat-drawer.php in footer.php),
                 * so the demo app.js categories page (#catRail/#catPanel) is
                 * never touched. */
                (function () {
                        var esc = function (s) {
                                return String(s).replace(/[&<>"']/g, function (c) {
                                        return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
                                });
                        };
                        var RM = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                        var backdrop = document.getElementById('tka-drawer-backdrop');
                        var openDrawer = null;
                        var lastTrigger = null;

                        var focusables = function (box) {
                                return [].slice.call(box.querySelectorAll(
                                        'a[href], button:not([disabled]), input:not([disabled]), select, textarea, [tabindex]:not([tabindex="-1"])'
                                )).filter(function (el) { return el.offsetParent !== null; });
                        };

                        var syncTriggers = function (name, expanded) {
                                [].forEach.call(document.querySelectorAll('[data-tka-drawer="' + name + '"]'), function (t) {
                                        t.setAttribute('aria-expanded', expanded ? 'true' : 'false');
                                });
                        };

                        var open = function (name, trigger) {
                                var d = document.getElementById('tka-' + name + '-drawer');
                                if (!d) { return; }
                                if (openDrawer === d) { return; }
                                if (openDrawer) { hide(openDrawer); }
                                openDrawer = d;
                                d.dataset.tkaName = name;
                                lastTrigger = trigger || null;
                                d.removeAttribute('hidden');
                                void d.offsetWidth; /* reflow → transition runs */
                                d.classList.add('is-open');
                                if (backdrop) {
                                        backdrop.removeAttribute('hidden');
                                        requestAnimationFrame(function () { backdrop.classList.add('is-open'); });
                                }
                                document.documentElement.classList.add('tka-drawer-lock');
                                syncTriggers(name, true);
                                var f = focusables(d);
                                if (f.length) { f[0].focus(); }
                        };

                        var hide = function (d) {
                                d.classList.remove('is-open');
                                if (backdrop) { backdrop.classList.remove('is-open'); }
                                document.documentElement.classList.remove('tka-drawer-lock');
                                if (d.dataset.tkaName) { syncTriggers(d.dataset.tkaName, false); }
                                /* Per-drawer timer: switching drawers must not cancel
                                 * the previous one's hide, and the shared backdrop only
                                 * hides when no drawer remains open. */
                                clearTimeout(d._tkaTimer || 0);
                                d._tkaTimer = setTimeout(function () {
                                        if (openDrawer !== d) { d.setAttribute('hidden', ''); }
                                        if (!openDrawer && backdrop) { backdrop.setAttribute('hidden', ''); }
                                }, RM ? 0 : 320);
                        };

                        var close = function () {
                                if (!openDrawer) { return; }
                                var d = openDrawer;
                                openDrawer = null;
                                hide(d);
                                if (lastTrigger && document.contains(lastTrigger)) { lastTrigger.focus(); }
                                lastTrigger = null;
                        };

                        /* Delegated open/close triggers. */
                        document.addEventListener('click', function (e) {
                                var t = e.target.closest('[data-tka-drawer]');
                                if (t) { open(t.getAttribute('data-tka-drawer'), t); return; }
                                if (!openDrawer) { return; }
                                if (e.target.closest('[data-tka-close]')) { close(); return; }
                                if (backdrop && e.target === backdrop) { close(); }
                        });

                        /* Escape closes; Tab is trapped inside the open drawer. */
                        document.addEventListener('keydown', function (e) {
                                if (!openDrawer) { return; }
                                if (e.key === 'Escape') { e.preventDefault(); close(); return; }
                                if (e.key !== 'Tab') { return; }
                                var f = focusables(openDrawer);
                                if (!f.length) { return; }
                                var first = f[0], last = f[f.length - 1];
                                if (e.shiftKey && document.activeElement === first) {
                                        e.preventDefault(); last.focus();
                                } else if (!e.shiftKey && document.activeElement === last) {
                                        e.preventDefault(); first.focus();
                                }
                        });

                        /* Categories drawer: rail switch + accordions + search. */
                        var catsDrawer = document.getElementById('tka-cats-drawer');
                        if (!catsDrawer) { return; }

                        var panel = catsDrawer.querySelector('.tka-drawer__panel');
                        var search = catsDrawer.querySelector('[data-tka-cats-search]');
                        var resultsPage = null;

                        var showPage = function (idx) {
                                if (!panel) { return; }
                                [].forEach.call(panel.querySelectorAll('.tka-drawer__page'), function (p) {
                                        if (p.getAttribute('data-page') === String(idx)) {
                                                p.removeAttribute('hidden');
                                        } else {
                                                p.setAttribute('hidden', '');
                                        }
                                });
                                panel.scrollTop = 0;
                        };

                        var buildResults = function (q) {
                                if (!panel) { return; }
                                if (!resultsPage || !panel.contains(resultsPage)) {
                                        resultsPage = document.createElement('div');
                                        resultsPage.className = 'tka-drawer__page tka-drawer__page--search';
                                        resultsPage.setAttribute('data-page', 'search');
                                        panel.appendChild(resultsPage);
                                }
                                var seen = {};
                                var count = 0;
                                var grid = document.createElement('div');
                                grid.className = 'cgrid';
                                [].forEach.call(panel.querySelectorAll('.tka-drawer__page[data-page]:not([data-page="search"]) .ctile'), function (tile) {
                                        var name = tile.getAttribute('data-name') || '';
                                        if (!name || name.indexOf(q) === -1 || seen[name]) { return; }
                                        seen[name] = 1;
                                        count++;
                                        if (count <= 60) { grid.appendChild(tile.cloneNode(true)); }
                                });
                                if (count) {
                                        resultsPage.innerHTML = '<a class="call" href="' +
                                                ((WP && WP.searchUrl) ? esc(WP.searchUrl) + '?s=' + encodeURIComponent(q) : '#') +
                                                '"><span>نتایج جستجو</span><small class="num" style="color:var(--gray-500);font-weight:400">' +
                                                count + ' مورد</small></a>';
                                        resultsPage.appendChild(grid);
                                } else {
                                        resultsPage.innerHTML = '<div class="cempty">' +
                                                '<svg class="ic" aria-hidden="true"><use href="#i-search"></use></svg>' +
                                                'نتیجه‌ای برای «' + esc(q) + '» یافت نشد</div>';
                                }
                                [].forEach.call(panel.querySelectorAll('.tka-drawer__page'), function (p) { p.setAttribute('hidden', ''); });
                                resultsPage.removeAttribute('hidden');
                                panel.scrollTop = 0;
                        };

                        catsDrawer.addEventListener('click', function (e) {
                                var item = e.target.closest('.crail__item');
                                if (item) {
                                        [].forEach.call(catsDrawer.querySelectorAll('.crail__item'), function (b) {
                                                b.classList.toggle('on', b === item);
                                        });
                                        if (search) { search.value = ''; }
                                        showPage(item.getAttribute('data-panel'));
                                        return;
                                }
                                var head = e.target.closest('.cgroup__head');
                                if (head) {
                                        var grp = head.parentNode;
                                        var page = head.closest('.tka-drawer__page');
                                        var wasOpen = grp.classList.contains('cgroup--open');
                                        if (page) {
                                                [].forEach.call(page.querySelectorAll('.cgroup'), function (g) {
                                                        g.classList.remove('cgroup--open');
                                                });
                                        }
                                        if (!wasOpen) { grp.classList.add('cgroup--open'); }
                                }
                        });

                        if (search) {
                                search.addEventListener('input', function () {
                                        var q = search.value.trim();
                                        if (!q) {
                                                if (resultsPage) { resultsPage.setAttribute('hidden', ''); }
                                                var on = catsDrawer.querySelector('.crail__item.on');
                                                showPage(on ? on.getAttribute('data-panel') : '0');
                                                return;
                                        }
                                        buildResults(q);
                                });
                                search.addEventListener('keydown', function (e) {
                                        if (e.key === 'Enter' && search.value.trim() && WP && WP.searchUrl) {
                                                window.location.href = WP.searchUrl + '?s=' + encodeURIComponent(search.value.trim());
                                        }
                                });
                        }
                })();

                /* Header cart badge stays in sync after silent cart updates
                 * (wc-cart-fragments only listens to wc_fragment_refresh). */
                if (window.jQuery) {
                        ['updated_cart_totals', 'updated_wc_div'].forEach(function (ev) {
                                window.jQuery(document.body).on(ev, function () {
                                        window.jQuery(document.body).trigger('wc_fragment_refresh');
                                });
                        });
                }
        });


        /* ---------- TKA 0.16.0 (task 18-b): هدر چسبان فشرده ----------
           یک سنتینل ۱px دقیقاً قبل از .header می‌گذاریم؛ وقتی از دید
           خارج شد یعنی هدر به لبهٔ بالا چسبیده → body.tka-stuck و
           CSS (main.css) هدر را فشرده + سایه می‌دهد. برگشت به بالا =
           حذف کلاس. rAF/IO پسند؛ بدون تغییر قالب. */
        function tkaInitStickyHeader() {
                var header = document.querySelector('.header');
                if (!header || !('IntersectionObserver' in window)) return;
                if (header.dataset.tkaSticky) return;
                header.dataset.tkaSticky = '1';

                var sentinel = document.createElement('div');
                sentinel.setAttribute('aria-hidden', 'true');
                sentinel.style.cssText =
                        'position:relative;width:1px;height:1px;margin:0;padding:0;pointer-events:none';
                header.parentNode.insertBefore(sentinel, header);

                var io = new IntersectionObserver(function (entries) {
                        for (var i = 0; i < entries.length; i++) {
                                document.body.classList.toggle('tka-stuck', !entries[i].isIntersecting);
                        }
                }, { threshold: 0 });
                io.observe(sentinel);

                /* TKA 0.17.0 (ممیزی P2-6): توکن پویای ارتفاع هدر.
                   --header-h در demo.css مقدار ثابت کهنهٔ 104px دارد؛
                   اینجا ارتفاع «واقعی» هدر (و تغییرش در حالت فشردهٔ
                   .tka-stuck یا ریسپانسیو) با ResizeObserver روی
                   <html> نوشته می‌شود تا هر قانون CSS که به
                   var(--header-h) تکیه می‌کند همیشه درست باشد. */
                if ('ResizeObserver' in window) {
                        var setH = function () {
                                var h = header.offsetHeight;
                                if (h > 0) document.documentElement.style.setProperty('--header-h', h + 'px');
                        };
                        setH();
                        new ResizeObserver(setH).observe(header);
                }
        }
        if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', tkaInitStickyHeader);
        } else {
                tkaInitStickyHeader();
        }
})();
