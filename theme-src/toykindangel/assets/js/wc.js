/* فرشته مهربون — لایهٔ چسب ووکامرس (v0.10)
 * ۱) استپر + / − برای ورودی‌های تعداد (سبد، PDP)
 * ۲) تب‌های ورود / ثبت‌نام
 * ۳) باز شدن دراور مینی‌سبد پس از افزودن AJAX به سبد + همگام‌سازی فرگمنت‌ها
 * بدون وابستگی به jQuery برای منطق UI؛ فقط شنوندهٔ رویداد WC از jQuery استفاده می‌کند.
 */
(function () {
        'use strict';

        /* ---------- ۱) استپر تعداد ---------- */
        function stepperBox(input) {
                if (!input || input.dataset.tkaStepper === '1') { return null; }
                var min = parseFloat(input.getAttribute('min')) || 0;
                var step = parseFloat(input.getAttribute('step')) || 1;
                var box = document.createElement('span');
                box.className = 'tka-qty-box';
                box.style.cssText = 'display:inline-flex;align-items:center;vertical-align:middle';

                function btn(delta, label) {
                        var b = document.createElement('button');
                        b.type = 'button';
                        b.className = 'tka-qty-btn';
                        b.setAttribute('aria-label', label);
                        /* v0.23.0: برای منفی از علامت minus استفاده کن (نه plus چرخیده) */
                        if (delta < 0) {
                                b.innerHTML = '<svg class="ic" aria-hidden="true"><use href="#i-minus"></use></svg>';
                        } else {
                                b.innerHTML = '<svg class="ic" aria-hidden="true"><use href="#i-plus"></use></svg>';
                        }
                        b.addEventListener('click', function () {
                                var v = parseFloat(input.value) || 0;
                                var next = v + delta * step;
                                var maxAttr = parseFloat(input.getAttribute('max'));
                                if (!isNaN(maxAttr) && maxAttr > 0) { next = Math.min(next, maxAttr); }
                                next = Math.max(next, min);
                                input.value = next;
                                input.dispatchEvent(new Event('change', { bubbles: true }));
                        });
                        return b;
                }

                var minus = btn(-step, 'کاهش');
                var plus = btn(step, 'افزایش');
                input.parentNode.insertBefore(box, input);
                box.appendChild(minus);
                box.appendChild(input);
                box.appendChild(plus);
                input.dataset.tkaStepper = '1';
                return box;
        }

        function enhanceQty(root) {
                var scope = root || document;
                var inputs = scope.querySelectorAll('form.cart .quantity input.qty, .tka-cart__qty input.qty, .woocommerce-cart-form input.qty, .pdp__cta__qty input.qty');
                [].forEach.call(inputs, function (input) { stepperBox(input); });
        }

        /* ---------- v0.24.0 (نیاز #۸): همگام‌سازی تعداد نوار چسبان ---------- */
        function bindStickyQty() {
                var box = document.getElementById('tka-cta-qty');
                if (!box) { return; }
                var input = box.querySelector('input.qty');
                var atc = document.querySelector('.pdp__cta .add_to_cart_button[data-quantity]');
                if (!input || !atc) { return; }
                var sync = function () {
                        var v = parseInt(input.value, 10);
                        if (isNaN(v) || v < 1) { v = 1; }
                        atc.setAttribute('data-quantity', String(v));
                };
                input.addEventListener('change', sync);
                input.addEventListener('input', sync);
                sync();
        }

        /* ---------- ۲) تب ورود/ثبت‌نام ---------- */
        function initLoginTabs() {
                var tabs = document.querySelectorAll('[data-tka-login-tab]');
                if (!tabs.length) { return; }
                [].forEach.call(tabs, function (tab) {
                        tab.addEventListener('click', function () {
                                var name = tab.getAttribute('data-tka-login-tab');
                                [].forEach.call(tabs, function (t) {
                                        var on = t === tab;
                                        t.classList.toggle('is-active', on);
                                        t.setAttribute('aria-selected', on ? 'true' : 'false');
                                });
                                [].forEach.call(document.querySelectorAll('[data-tka-login-pane]'), function (p) {
                                        p.classList.toggle('is-active', p.getAttribute('data-tka-login-pane') === name);
                                });
                        });
                });
        }

        /* ---------- ۳) دراور مینی‌سبد ---------- */
        /* v0.24.0 (نیاز #۱۳): باز/بستن مینی‌سبد فقط از سیستم رسمی دراورها
         * (main.js → window.TKA_Drawers). مسیر قبلی، DOM را مستقیم دستکاری
         * می‌کرد و حالت openDrawer در main.js null می‌ماند؛ نتیجه: کلیک روی
         * دکمهٔ بستن و backdrop بی‌اثر بود (باگ تأییدشده در QA مرورگر).
         * مسیر مستقیم فقط به‌عنوان fallback نگه داشته شده است. */
        function openMinicart() {
                if (window.TKA_Drawers && typeof window.TKA_Drawers.open === 'function') {
                        window.TKA_Drawers.open('minicart', null);
                        return;
                }
                var d = document.getElementById('tka-minicart-drawer');
                var backdrop = document.getElementById('tka-drawer-backdrop');
                if (!d) { return; }
                d.removeAttribute('hidden');
                void d.offsetWidth;
                d.classList.add('is-open');
                if (backdrop) {
                        backdrop.removeAttribute('hidden');
                        requestAnimationFrame(function () { backdrop.classList.add('is-open'); });
                }
                document.documentElement.classList.add('tka-drawer-lock');
        }

        function closeMinicart() {
                if (window.TKA_Drawers && typeof window.TKA_Drawers.close === 'function') {
                        window.TKA_Drawers.close();
                        return;
                }
                var d = document.getElementById('tka-minicart-drawer');
                var backdrop = document.getElementById('tka-drawer-backdrop');
                if (!d) { return; }
                d.classList.remove('is-open');
                if (backdrop) { backdrop.classList.remove('is-open'); }
                document.documentElement.classList.remove('tka-drawer-lock');
                setTimeout(function () { d.setAttribute('hidden', ''); }, 320);
        }

        /* دراور از سیستم data-tka-drawer مدیریت می‌شود؛ فقط بستن با کلید فرار هم اینجا امن است */
        document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') { closeMinicart(); }
        });

        /* v0.26.0 — کلیک روی رادیوی نحوهٔ ارسال → هایلایت کارت انتخابی */
        document.addEventListener('change', function (e) {
                if (e.target && e.target.type === 'radio' && e.target.name && e.target.name.indexOf('shipping_method') === 0) {
                        markShipping();
                }
        });

        /* v0.26.0 — هایلایت کارت نحوهٔ ارسال انتخاب‌شده (کلاس is-selected)
         * :has() پشتیبان CSS است؛ برای مرورگرهای قدیمی هم اینجا تضمین می‌شود */
        function markShipping() {
                [].forEach.call(document.querySelectorAll('#shipping_method li, .woocommerce-shipping-methods li'), function (li) {
                        var input = li.querySelector('input[type=radio]');
                        if (input) { li.classList.toggle('is-selected', !!input.checked); }
                });
        }

        /* v0.26.0 — باگ «فاصلهٔ خالی» چک‌اوت: ووکامرس هنگام خطا گروه اعلان را
         * داخل فرم prepend می‌کند و چون فرم در دسکتاپ گرید دوستونه است، گرید
         * می‌شکند (ستون فرم به ردیف بعد می‌رود). گروه اعلان به بالای فرم
         * (بعد از سربرگ مراحل) منتقل می‌شود. */
        function tidyCheckoutNotices() {
                var form = document.querySelector('form.tka-co__form');
                if (!form) { return; }
                var groups = form.querySelectorAll(':scope > .woocommerce-NoticeGroup');
                if (!groups.length) { return; }
                var co = form.closest('.tka-co');
                var anchor = co ? co.querySelector('.tka-co__head') : null;
                [].forEach.call(groups, function (ng) {
                        if (anchor && anchor.parentNode === co) {
                                co.insertBefore(ng, anchor.nextSibling);
                        } else if (co) {
                                co.insertBefore(ng, form);
                        }
                });
        }

        /* v0.26.0 — خطای «ثبت سفارش»: بدون اسکرول دستی، کاربر خطا را ببیند؛
         * اول گروه اعلان (بالای فرم)، وگرنه اولین فیلد نامعتبر (قاب قرمز). */
        function scrollToCheckoutError() {
                tidyCheckoutNotices();
                var el = document.querySelector('.woocommerce-NoticeGroup-checkout') ||
                                 document.querySelector('.tka-co__form .woocommerce-error') ||
                                 document.querySelector('.woocommerce-checkout .woocommerce-invalid input, .woocommerce-checkout .woocommerce-invalid select');
                if (!el) { return; }
                var y = el.getBoundingClientRect().top + (window.pageYOffset || document.documentElement.scrollTop) - 120;
                window.scrollTo({ top: Math.max(y, 0), behavior: 'smooth' });
                var inv = document.querySelector('.woocommerce-checkout .woocommerce-invalid input, .woocommerce-checkout .woocommerce-invalid select');
                if (inv && inv.focus) { try { inv.focus({ preventScroll: true }); } catch (err) { /* noop */ } }
        }

        /* وقتی AJAX افزودن به سبد ووکامرس تمام شد → دراور باز شود (رویدادهای jQuery WC) */
        function hookJqEvents() {
                if (!window.jQuery) { return; }
                window.jQuery(document.body)
                        .on('added_to_cart', function () {
                                openMinicart();
                        })
                        /* v0.24.0 (نیاز #۱۴): ووکامرس بعد از هر آپدیت AJAX چک‌اوت
                         * (فرگمنت‌ها) فرم را بازسازی می‌کند و تیک «شرایط و مقررات»
                         * ریست می‌شود — چک‌باکس باید بعد از هر refresh دوباره تیک
                         * بخورد، نه فقط در بار اول (باگ v0.23.0). */
                        .on('updated_checkout updated_shipping_methods init_checkout', function () {
                                [].forEach.call(document.querySelectorAll('input[name="terms"]'), function (cb) {
                                        cb.checked = true;
                                });
                                /* v0.26.0: فرگمنت تازهٔ ارسال دوباره هایلایت + گروه اعلان مرتب */
                                markShipping();
                                tidyCheckoutNotices();
                        })
                        /* v0.26.0 (بازخورد مشتری): بعد از خطای ثبت سفارش، خودکار به خطا اسکرول شود */
                        .on('checkout_error', function () {
                                scrollToCheckoutError();
                        });
        }

        function boot() {
                enhanceQty(document);
                initLoginTabs();
                hookJqEvents();
                bindStickyQty();
                markShipping();
        }

        if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
        } else {
                boot();
        }

        /* PDP: بعد از کامل شدن فرم هسته هم استپر را تضمین کن (برخی قالب‌ها دیر رندر می‌کنند) */
        window.addEventListener('load', function () { enhanceQty(document); });

        /* اکسپورت برای سازگاری آینده */
        window.TKA_WC = { openMinicart: openMinicart, closeMinicart: closeMinicart, enhanceQty: enhanceQty };
})();

/* ---------- v0.24.0: تیک خودکار شرایط + بدون تایم‌لاین تزریقی ---------- */
document.addEventListener('DOMContentLoaded', function() {
  /* تیک خودکار «شرایط و مقررات» (بار اول؛ refreshهای بعدی از رویدادهای
   * updated_checkout در hookJqEvents مدیریت می‌شوند) */
  [].forEach.call(document.querySelectorAll('#terms, input[name="terms"]'), function (cb) {
    cb.checked = true;
  });

  /* v0.24.0 (نیاز #۶): تایم‌لاین ۳ مرحله‌ای دیگر از JS تزریق نمی‌شود.
   * v0.23.0 دو تایم‌لاین رقیب می‌ساخت (تزریقی JS + چیپ‌های داخل قالب
   * form-checkout.php). حالا هر دو صفحهٔ سبد و چک‌اوت مارک‌آپ استاتیک
   * یکسانی (tka-co__steps) در قالب PHP خودشان دارند — بدون پرش/دوبارگی. */
});

/* ---------- v0.25.0: پیکر ستاره‌ای امتیاز فرم دیدگاه محصول ----------
 * WooCommerce یک <select name="rating"> ساده چاپ می‌کند؛ این ماژول روی
 * آن یک پیکر ستاره‌ای ۵ پرچم سوار می‌کند (select سر جایش می‌ماند و
 * مقدارش ست می‌شود — بدون JS هم فرم کار می‌کند). */
(function () {
        'use strict';

        function paint(btns, value) {
                [].forEach.call(btns, function (b) {
                        var v = parseInt(b.getAttribute('data-v'), 10);
                        b.classList.toggle('is-on', v <= value);
                        b.setAttribute('aria-checked', v === value ? 'true' : 'false');
                });
        }

        function enhance(select) {
                if (!select || select.dataset.tkaStars === '1') { return; }
                select.dataset.tkaStars = '1';

                var wrap = document.createElement('div');
                wrap.className = 'tka-stars';
                wrap.setAttribute('role', 'radiogroup');
                wrap.setAttribute('aria-label', 'امتیاز شما');

                var btns = [];
                /* direction:ltr روی کانتینر — ستارهٔ ۵ در چپ مثل الگوی مرجع */
                for (var v = 5; v >= 1; v--) {
                        (function (val) {
                                var b = document.createElement('button');
                                b.type = 'button';
                                b.className = 'tka-stars__btn';
                                b.setAttribute('data-v', String(val));
                                b.setAttribute('role', 'radio');
                                b.setAttribute('aria-label', val + ' ستاره');
                                b.innerHTML = '<svg class="ic" aria-hidden="true"><use href="#i-star"></use></svg>';
                                b.addEventListener('click', function () {
                                        select.value = String(val);
                                        select.required = false; /* مقدار انتخاب شد */
                                        paint(btns, val);
                                });
                                btns.push(b);
                                wrap.appendChild(b);
                        })(v);
                }

                var hint = document.createElement('span');
                hint.className = 'tka-stars__hint';
                hint.textContent = 'برای امتیاز دادن روی ستاره‌ها بزنید';

                select.insertAdjacentElement('afterend', wrap);
                wrap.insertAdjacentElement('afterend', hint);

                /* مقدار اولیه (بازبینی پس از خطا) */
                var init = parseInt(select.value, 10);
                if (init >= 1 && init <= 5) { paint(btns, init); }
        }

        function boot() {
                [].forEach.call(document.querySelectorAll('#reviews select#rating, #review_form select#rating'), enhance);
        }

        if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', boot);
        } else {
                boot();
        }
})();
