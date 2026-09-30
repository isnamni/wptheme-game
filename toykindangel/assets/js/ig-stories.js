/*
 * TKA 0.18.1 — Instagram-style story viewer (vanilla JS, no dependencies).
 * نوار .tka-igbar را می‌خواند، نمایشگر تمام‌صفحه می‌سازد و با پروگرس‌بار
 * ۵ ثانیه‌ای بین استوری‌ها جابه‌جا می‌شود. بدون نوار، هیچ کاری نمی‌کند.
 */
(function () {
	'use strict';

	var DURATION = 5000; /* هر استوری ۵ ثانیه */

	function ready(fn) {
		if (document.readyState !== 'loading') {
			fn();
		} else {
			document.addEventListener('DOMContentLoaded', fn);
		}
	}

	ready(function () {
		var bar = document.querySelector('[data-tka-igstories]');
		if (!bar) {
			return;
		}

		var buttons = Array.prototype.slice.call(bar.querySelectorAll('.tka-igbar__item'));
		var items = buttons.map(function (btn) {
			return {
				full: btn.getAttribute('data-ig-full') || '',
				thumb: btn.getAttribute('data-ig-thumb') || '',
				name: btn.getAttribute('data-ig-name') || '',
			};
		}).filter(function (it) { return it.full; });
		if (!items.length) {
			return;
		}

		/* ---------- ساخت نمایشگر (یک‌بار) ---------- */
		var view = document.createElement('div');
		view.className = 'tka-igview';
		view.hidden = true;
		view.innerHTML =
			'<div class="tka-igview__backdrop" data-ig-close></div>' +
			'<div class="tka-igview__card" role="dialog" aria-modal="true" aria-label="نمایش استوری">' +
				'<div class="tka-igview__img"><img alt="" draggable="false"></div>' +
				'<div class="tka-igview__progress"></div>' +
				'<div class="tka-igview__head">' +
					'<img class="tka-igview__ava" alt="">' +
					'<span class="tka-igview__name"></span>' +
					'<span class="tka-igview__time">الان</span>' +
					'<button type="button" class="tka-igview__close" aria-label="بستن" data-ig-close>&#10005;</button>' +
				'</div>' +
				'<button type="button" class="tka-igview__zone tka-igview__zone--prev" aria-label="استوری قبلی"></button>' +
				'<button type="button" class="tka-igview__zone tka-igview__zone--next" aria-label="استوری بعدی"></button>' +
			'</div>';
		document.body.appendChild(view);

		var card = view.querySelector('.tka-igview__card');
		var imgEl = view.querySelector('.tka-igview__img img');
		var avaEl = view.querySelector('.tka-igview__ava');
		var nameEl = view.querySelector('.tka-igview__name');
		var progEl = view.querySelector('.tka-igview__progress');
		var fills = [];

		var idx = 0;
		var elapsed = 0;
		var last = 0;
		var raf = 0;
		var paused = false;

		/* ---------- رندر اسلاید جاری + سگمنت‌ها ---------- */
		function renderSlide() {
			progEl.innerHTML = '';
			fills = [];
			items.forEach(function (_, i) {
				var seg = document.createElement('span');
				seg.className = 'tka-igview__seg' + (i < idx ? ' done' : '');
				var fill = document.createElement('i');
				seg.appendChild(fill);
				progEl.appendChild(seg);
				fills.push(fill);
			});
			var it = items[idx];
			avaEl.src = it.thumb || it.full;
			nameEl.textContent = it.name;
			imgEl.src = it.full;
			imgEl.alt = it.name;

			/* پیش‌بارگذاری استوری بعدی */
			if (items[idx + 1]) {
				var im = new Image();
				im.src = items[idx + 1].full;
			}
		}

		/* ---------- حلقهٔ پروگرس ---------- */
		function tick(now) {
			if (!paused) {
				elapsed += now - last;
				var p = Math.min(elapsed / DURATION, 1);
				if (fills[idx]) {
					fills[idx].style.transform = 'scaleX(' + p + ')';
				}
				if (p >= 1) {
					goNext();
					return;
				}
			}
			last = now;
			raf = requestAnimationFrame(tick);
		}

		function startTimer() {
			cancelAnimationFrame(raf);
			last = performance.now();
			raf = requestAnimationFrame(tick);
		}

		function markSeen(i) {
			if (buttons[i]) {
				buttons[i].classList.add('seen');
			}
		}

		/* ---------- کنترل‌ها ---------- */
		function open(startIdx) {
			idx = Math.min(Math.max(startIdx, 0), items.length - 1);
			elapsed = 0;
			view.hidden = false;
			document.documentElement.classList.add('tka-ig-lock');
			renderSlide();
			startTimer();
		}

		function close() {
			markSeen(idx);
			cancelAnimationFrame(raf);
			view.hidden = true;
			document.documentElement.classList.remove('tka-ig-lock');
		}

		function goNext() {
			markSeen(idx);
			if (idx + 1 < items.length) {
				idx += 1;
				elapsed = 0;
				renderSlide();
				startTimer();
			} else {
				close();
			}
		}

		function goPrev() {
			markSeen(idx);
			if (idx > 0) {
				idx -= 1;
				elapsed = 0;
				renderSlide();
				startTimer();
			} else {
				elapsed = 0;
				if (fills[idx]) {
					fills[idx].style.transform = 'scaleX(0)';
				}
			}
		}

		/* ---------- رویدادها ---------- */
		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var i = parseInt(btn.getAttribute('data-ig-index'), 10);
				if (isNaN(i)) {
					i = 0;
				}
				open(i);
			});
		});

		view.querySelectorAll('[data-ig-close]').forEach(function (el) {
			el.addEventListener('click', close);
		});
		view.querySelector('.tka-igview__zone--next').addEventListener('click', goNext);
		view.querySelector('.tka-igview__zone--prev').addEventListener('click', goPrev);

		/* نگه‌داشتن = توقف تایمر (مثل اینستاگرام) */
		card.addEventListener('pointerdown', function () {
			paused = true;
		});
		['pointerup', 'pointercancel', 'pointerleave'].forEach(function (ev) {
			card.addEventListener(ev, function () {
				paused = false;
			});
		});

		/* تصویر خطا خورد → رد شو */
		imgEl.addEventListener('error', function () {
			if (idx + 1 < items.length) {
				goNext();
			} else {
				close();
			}
		});

		/* کیبورد */
		document.addEventListener('keydown', function (e) {
			if (view.hidden) {
				return;
			}
			if (e.key === 'Escape') {
				close();
			} else if (e.key === 'ArrowLeft') {
				goNext(); /* RTL: چپ = بعدی */
			} else if (e.key === 'ArrowRight') {
				goPrev();
			}
		});

		/* کلیک روی کارت هم مثل اینستا = بعدی (جز دکمه‌ها) — با zone ها پوشش داده شده */
	});
})();
