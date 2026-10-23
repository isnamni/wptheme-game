/**
 * ToyKind Angel — جستجوی زندهٔ AJAX (هدر / جعبه‌های جستجو).
 *
 * پیکربندی از window.TKA_LIVE (functions.php):
 *   restUrl — مسیر REST جستجو (toykindangel/v1/search)؛ اگر باشد اولویت دارد
 *   url     — آدرس admin-ajax با action=tka_live_search (fallback وقتی restUrl نباشد)
 *   min     — حداقل نویسه برای شروع جستجو (پیش‌فرض ۲)
 *   loading — متن حالت بارگذاری
 *   error   — متن خطا
 *
 * قابلیت‌ها: debounce، لغو درخواست قبلی (AbortController)،
 * ناوبری کیبورد (↑ ↓ Enter Escape)، ARIA combobox/listbox،
 * بستن با کلیک بیرون، بازِ دوباره با فوکوس.
 */
(function () {
        'use strict';

        var CFG = window.TKA_LIVE || {};
        var ENDPOINT = CFG.url || '';
        var REST = CFG.restUrl || '';
        var MIN = CFG.min || 2;

        if (!ENDPOINT && !REST) {
                return;
        }

        var forms = document.querySelectorAll('form.searchbox[data-tka-live]');
        Array.prototype.forEach.call(forms, init);

        function init(form) {
                var input = form.querySelector('input[type="search"]');
                var panel = form.querySelector('.tka-ls');
                if (!input || !panel) {
                        return;
                }

                var timer = null;
                var ctrl = null;
                var lastTerm = '';
                var items = [];
                var active = -1;

                function isOpen() {
                        return !panel.hidden;
                }

                function open() {
                        if (panel.hidden) {
                                panel.hidden = false;
                                input.setAttribute('aria-expanded', 'true');
                        }
                }

                function close() {
                        panel.hidden = true;
                        input.setAttribute('aria-expanded', 'false');
                        setActive(-1);
                }

                function esc(s) {
                        var d = document.createElement('div');
                        d.textContent = s == null ? '' : String(s);
                        return d.innerHTML;
                }

                function showLoading() {
                        panel.innerHTML =
                                '<div class="tka-ls__loading" aria-live="polite"><span class="tka-ls__spinner" aria-hidden="true"></span>' +
                                esc(CFG.loading || 'در حال جستجو…') +
                                '</div>';
                        open();
                }

                function showError() {
                        panel.innerHTML = '<div class="tka-ls__empty">' + esc(CFG.error || 'خطا در دریافت نتایج؛ دوباره تلاش کنید.') + '</div>';
                        open();
                }

                function showEmpty(term) {
                        panel.innerHTML =
                                '<div class="tka-ls__empty">' +
                                'نتیجه‌ای برای «' + esc(term) + '» پیدا نشد.' +
                                '</div>';
                        open();
                }

                function render(data, term) {
                        if (!data || !data.html) {
                                showEmpty(term);
                                return;
                        }

                        panel.innerHTML = data.html;

                        if (data.all_url) {
                                var foot = document.createElement('div');
                                foot.className = 'tka-ls__foot';
                                var a = document.createElement('a');
                                a.href = data.all_url;
                                a.textContent = 'مشاهدهٔ همهٔ نتایج برای «' + (data.term || term) + '»';
                                foot.appendChild(a);
                                panel.appendChild(foot);
                        }

                        items = Array.prototype.slice.call(panel.querySelectorAll('.tka-ls__item'));
                        setActive(-1);
                        open();
                }

                function setActive(i) {
                        active = i;
                        items.forEach(function (el, n) {
                                if (n === i) {
                                        el.classList.add('on');
                                        el.setAttribute('aria-selected', 'true');
                                } else {
                                        el.classList.remove('on');
                                        el.removeAttribute('aria-selected');
                                }
                        });

                        if (i >= 0 && items[i]) {
                                input.setAttribute('aria-activedescendant', items[i].id || '');
                                if (items[i].scrollIntoView) {
                                        items[i].scrollIntoView({ block: 'nearest' });
                                }
                        } else {
                                input.removeAttribute('aria-activedescendant');
                        }
                }

                function search(term) {
                        if (ctrl) {
                                ctrl.abort();
                        }
                        ctrl = 'AbortController' in window ? new AbortController() : null;

                        var opts = ctrl ? { signal: ctrl.signal } : {};
                        showLoading();

                        // مسیر REST وقتی باشد اولویت دارد؛ وگرنه admin-ajax قدیمی.
                        // آدرس admin-ajax خودش ?action=… دارد، REST ندارد → جداکننده متفاوت.
                        var base = REST || ENDPOINT;
                        var url = base + (base.indexOf('?') === -1 ? '?' : '&') + 'q=' + encodeURIComponent(term);

                        fetch(url, opts)
                                .then(function (r) {
                                        if (!r.ok) {
                                                throw new Error('http_' + r.status);
                                        }
                                        return r.json();
                                })
                                .then(function (res) {
                                        // پاسخ قدیمی را نپذیر (کاربر ممکن است واژه را عوض کرده باشد).
                                        if (input.value.trim() !== term) {
                                                return;
                                        }
                                        // admin-ajax: { success, data } — REST: payload مستقیم است.
                                        render(res && (res.data || res), term);
                                })
                                .catch(function (err) {
                                        if (err && err.name === 'AbortError') {
                                                return;
                                        }
                                        showError();
                                });
                }

                input.addEventListener('input', function () {
                        var term = input.value.trim();

                        if (timer) {
                                clearTimeout(timer);
                        }

                        if (term.length < MIN) {
                                lastTerm = '';
                                if (ctrl) {
                                        ctrl.abort();
                                }
                                close();
                                return;
                        }

                        timer = setTimeout(function () {
                                lastTerm = term;
                                search(term);
                        }, 300);
                });

                input.addEventListener('focus', function () {
                        // با فوکوس دوباره، آخرین نتیجهٔ موجود باز می‌شود.
                        if (input.value.trim().length >= MIN && panel.innerHTML) {
                                open();
                        }
                });

                input.addEventListener('keydown', function (e) {
                        if (!isOpen()) {
                                return;
                        }

                        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
                                e.preventDefault();
                                if (!items.length) {
                                        return;
                                }
                                var next = e.key === 'ArrowDown' ? Math.min(active + 1, items.length - 1) : Math.max(active - 1, -1);
                                setActive(next);
                        } else if (e.key === 'Enter') {
                                if (active >= 0 && items[active]) {
                                        e.preventDefault();
                                        window.location.href = items[active].getAttribute('href');
                                }
                                // بدون انتخاب: سابمیت عادی فرم انجام می‌شود.
                        } else if (e.key === 'Escape') {
                                close();
                                input.blur();
                        }
                });

                document.addEventListener('click', function (e) {
                        if (isOpen() && !form.contains(e.target)) {
                                close();
                        }
                });

                document.addEventListener('keydown', function (e) {
                        if (e.key === 'Escape' && isOpen() && document.activeElement !== input) {
                                close();
                        }
                });
        }
})();
