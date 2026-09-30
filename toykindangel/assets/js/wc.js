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
			b.innerHTML = '<svg class="ic" aria-hidden="true" style="transform:' + (delta < 0 ? 'rotate(45deg)' : 'none') + '"><use href="#i-plus"></use></svg>';
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
		var inputs = scope.querySelectorAll('form.cart .quantity input.qty, .tka-cart__qty input.qty, .woocommerce-cart-form input.qty');
		[].forEach.call(inputs, function (input) { stepperBox(input); });
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
	function openMinicart() {
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

	/* وقتی AJAX افزودن به سبد ووکامرس تمام شد → دراور باز شود (رویدادهای jQuery WC) */
	function hookJqEvents() {
		if (!window.jQuery) { return; }
		window.jQuery(document.body)
			.on('added_to_cart', function () {
				openMinicart();
			})
			.on('removed_from_cart wc_fragments_refreshed wc_fragments_loaded', function () {
				/* فرگمنت مینی‌سبد خودش با added_to_cart/removed_from_cart به‌روز می‌شود؛
				   اگر سبد خالی شد دراور را نبندیم — فقط محتوا عوض می‌شود. */
			});
	}

	function boot() {
		enhanceQty(document);
		initLoginTabs();
		hookJqEvents();
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
