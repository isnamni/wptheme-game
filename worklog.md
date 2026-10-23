# Worklog

---
Task ID: 0
Agent: main (Z.ai Code)
Task: بررسی کامل قالب وردپرس toykindangel و تولید گزارش HTML ایرادات

Work Log:
- ریپازیتوری https://github.com/isnamni/wptheme-game با توکن کلون شد به /home/z/wptheme-game
- ساختار قالب در /home/z/wptheme-game/toykindangel شناسایی شد (woocommerce templates در woocommerce/، CSS در assets/css/، JS در assets/js/)
- پوشه اسکرین‌شات‌ها: /home/z/review/shots/

Stage Summary:
- نوبت v0.23.0 آخرین کامیت است (16 fixes)
- گزارش نهایی HTML باید در /home/z/review/ تولید شود

---
Task ID: 5
Agent: code-auditor
Task: Deep code audit of toykindangel theme — map 18 reported bugs to exact code (file:line + root cause + fix)

Work Log:
- Read all key templates (woocommerce/single-product.php, archive-product.php, cart.php, form-checkout.php, form-login.php), headers/footers, inc/* (wc-hooks, front-data, front-ssr, product-card, minicart, breadcrumbs, seo-meta, seo-schema, wc-pages-fix, performance, wishlist, ajax-search, demo-import), assets/js (app.js, main.js, wc.js), assets/css (demo/main/responsive/custom/pdp/wc-pages/commerce), functions.php, icons sprite; verified v0.23.0 git commit (6c90385) which attempted the same 16 fixes.
- Key discoveries: header-pdp.php/header-bare.php are dead code (single-product.php:37 calls get_header() → full header → nested duplicate <main id="content">); OOS "fix" in wc-hooks.php:63-76 excludes out-of-stock instead of ordering last and is clobbered by meta_key overrides; minicart close broken because wc.js openMinicart() bypasses main.js drawer state (openDrawer null → [data-tka-close]/backdrop handler returns early at main.js:573); brand colors = nth-child CSS cycling (main.css:1132-1136, custom.css:1095-1100); terms pre-check is JS-only + invalid CSS `checked:expression()` (custom.css:1738-1741) and is wiped by WC checkout fragment refresh; two competing checkout timelines (form-checkout.php:41-45 + wc.js injection); banner zoom = stacked custom.css banner blocks forcing fixed heights + object-fit:cover.

Stage Summary:
- Findings for bugs 1-19 delivered in final report (numbered, with file:line, snippet, root cause, concrete fix). No code modified.

---
Task ID: 4
Agent: browser-qa-reference-site
Task: Capture reference (good) product page of toykindangel.com on desktop + mobile; document gallery, thumbnails, typography, buy-box, header/footer behavior; identify theme/plugins.

Work Log:
- Direct access to toykindangel.com FAILS from this sandbox (DNS resolves to 85.10.251.210 but TCP connect times out; verified with curl — site blocks foreign/datacenter IPs). No Wayback Machine snapshot exists.
- Workaround: fetched origin HTML through Google Translate proxy (toykindangel-com.translate.goog), then built a local caching reverse-proxy (/home/z/mirror/proxy.py, port 8765) that tunnels ALL subresources (CSS/JS/images/fonts) from the origin through the same proxy. Page served at http://127.0.0.1:8765/ with real theme CSS/JS/images — pixel-faithful mirror, not a recreation. One mirror bug found+fixed during QA (my rewriter ate "#tab-description" fragments; tabs verified working after fix).
- NOTE: PNG files could not be visually displayed in this sub-agent context; all visual verification was done objectively via PIL pixel analysis of the saved screenshots + DOM/computed-style inspection. Screenshots are in /home/z/review/shots/ for human review.
- Captures: ref-01 desktop viewport, ref-02 desktop full (2440px), ref-03/04/05 after clicking thumbs #4/#6/#8 (main image src changes each time — verified via flex-active-slide + distinct crop hashes), ref-06 footer, ref-07 zoom-on-hover (.zoomImg 800x800 created), ref-08 PhotoSwipe lightbox open (800x800 large image, Esc closes), ref-09 mobile top (390x844), ref-10 mobile full (3107px), ref-11 mobile bottom, ref-12 description tab open.
- Checks: header top ✓ (desktop 175px <header class="header">; mobile = separate fixed 38px section.header-mobile.header-mobile-sticky, z=99). Footer bottom ✓ (desktop footer 1924–2440 incl. copyright "کلیه حقوق..."; mobile footer + purple floating contact button bottom-left #AE5399, 42px). Breadcrumbs ✓ both ("خانه / هشت تا دوازده سال / ... / product").
- Gallery: standard Woo gallery, 10 images. Main image 600x600 natural rendered 460x460 objectFit:fill, overflow visible → FULL photo visible, no crop/zoom. Thumbnails desktop = VERTICAL 51x51 column on the LEFT of main image (flex-direction:column, overflow:hidden, inactive opacity 0.5 / active 1.0). Thumbnails mobile = horizontal WRAPPING row below image (flex-wrap, no horizontal scroll; 3 rows at 63px), 15px gap under image. Clicking thumbs switches main image (FlexSlider init'd). Zoom-on-hover ✓ (jquery.zoom attaches .zoomImg 800x800 high-res). "بزرگنمایی تصویر" button opens PhotoSwipe lightbox ✓.
- BUY-BOX FINDING: product is OUT OF STOCK on the reference ("موجود نیست!" badge with purple ✗ under title) → NO price, NO quantity stepper, NO add-to-cart button anywhere on the page (confirmed absent in raw origin HTML too). Quantity-stepper check N/A on reference.
- Typography: font = "yekanbakh" (Yekan Bakh, self-hosted premium Persian font); body 14px; H1 18px/700; description p 15px with line-height 37.5px = 2.5 ratio (very airy), color rgb(48,48,48), RTL right-aligned; tab links 13px/600.
- Brand: NO brand logo/link on product page (no product_brand taxonomy, no pa-brand attr); only category "دسته: اسباب بازی". Header nav has "برند ها" menu item only.
- Mobile gaps: breadcrumb→gallery 35px, image→thumbnails 15px; NO sticky add-to-cart bar (none in DOM); NO bottom tab nav (only floating button). MINOR BUG found in reference theme: mobile breadcrumb (y=20, 12.9px gray) sits UNDER the fixed 38px sticky header → top ~18px of breadcrumb hidden behind header (no body padding-top in mobile CSS).
- Theme/plugin identification: Theme = **Pishro** (custom Iranian RTL Woo theme; body class wp-theme-pishro theme-pishro, /wp-content/themes/pishro/). Plugins: WooCommerce 10.9.4 (FlexSlider + jquery.zoom + PhotoSwipe), Elementor 4.1.4 (+Elementor Pro on homepage), Redux 4.4.10, Woo Variation Swatches (wvs-* classes), Rank Math SEO, WP Super Cache, Really Simple SSL; Goftino chat widget + ZarinPal/Enamad trust seals (external, unreachable from sandbox). Generator meta: "WordPress 7.1.3" (as reported by site).

Stage Summary:
- 12 reference screenshots saved to /home/z/review/shots/ (ref-01…ref-12). Mirror infra reusable: /home/z/mirror/proxy.py + rewrite.py (python3 rewrite.py && python3 proxy.py, then open http://127.0.0.1:8765/).
- Reference = theme "Pishro" + WooCommerce default gallery stack (FlexSlider/zoom/PhotoSwipe), vertical thumb rail on desktop, wrapping thumb row on mobile, Yekan Bakh typography with 2.5 line-height in descriptions.
- Reference page currently out-of-stock → its buy-box (price/qty/add-to-cart) is hidden, so the quantity-stepper comparison target must come from another in-stock reference product or from the client's expectation.
- Flagged: mobile breadcrumb/header overlap bug exists even in the "good" reference theme.

---
Task ID: 3 (main agent completed after subagent timeout)
Agent: main (Z.ai Code) — browser QA of broken site via local mirror
Task: بررسی مرورگری سایت toykindangel.com/newsite (دسکتاپ 1440 + موبایل 390)

Work Log:
- سرور سایت اتصال مستقیم دیتاسنتر را بلاک می‌کند؛ مسیر toykindangel-com.translate.goog با _x_tr_pto=wapp کار کرد
- proxy3.py روی پورت 8766: سرو استاتیک تم (سیم‌لینک از ریپازیتوری) + آپلود fallback به پروکسی قدیمی 8765 + کوکی‌آگاهی
- صفحات دریافتی: homepage، product (بازی فکری محله چینی‌ها)، cart. صفحات shop/checkout/cat به‌دلیل rate limit گوگل (429/408) دریافت نشدند (12 تلاش)
- بازتولید باگ 13: openMinicart از مسیر wc.js (jQuery trigger added_to_cart) → کلیک close button = باز ماند، کلیک backdrop = باز ماند، فقط Esc کار می‌کند
- اندازه‌گیری گالری: slide 742x520 با overflow:hidden، تصویر 740x740 → برش بالا/پایین (باگ زوم تأیید)
- بندانگشتی‌ها: justify-content:center + overflow → قیچی دوطرفه (تأیید بصری)
- برندها: دایره‌های رنگی pink/green/blue/orange تأیید بصری (nth-child CSS)
- کارت‌های صفحه اصلی: 30 کارت، صفر نشانگر ناموجود
- تایپوگرافی: 14.5px/26.8px در برابر مرجع 15px/37.5px
- موبایل PDP: هدر 5 ردیفه (~230px) قبل از عکس، bottomnav display:none، pdp__cta bottom:64px، body padding-bottom:150px
- سبد خالی: تایم‌لاین تزریقی JS خوب رندر می‌شود

Stage Summary:
- 16 اسکرین‌شات site-* در /home/z/review/shots/
- باگ‌های 3,4,9,13 بصری/تعاملی تأیید شدند؛ 1,7,8,14 از کد + نشانه‌های DOM

---
Task ID: 6
Agent: main (Z.ai Code)
Task: تولید گزارش نهایی HTML

Work Log:
- گزارش نهایی: /home/z/review/گزارش-ایرادات-قالب-ToyKindAngel.html (فارسی RTL، Vazirmatn، 25 کارت، 2 جدول، 8 اسکرین‌شات embed شده با مسیر نسبی shots/)
- ساختار: خلاصه مدیریتی، جدول وضعیت 16 مورد (✅/⚠️/❌)، 15 بخش تفصیلی با «به زبان ساده/شواهد/علت فنی file:line/راه‌حل کد»، 14 ایراد جدید، چک‌لیست ۳ گروه اولویت
- رندر گزارش در مرورگر تأیید شد (3 اسکرین‌شات preview)

Stage Summary:
- خروجی نهایی: گزارش HTML + پوشه shots/ (24 تصویر)

---
Task ID: 7
Agent: main (Z.ai Code)
Task: پیاده‌سازی کامل اصلاحات v0.24.0 روی قالب + ZIP نهایی + راهنمای به‌روزشده در روت

Work Log:
- هر ۱۶ مورد + بازخوردهای دور دوم در کد اعمال شد (کامیت 9c1dca0 روی شاخه dev): OOS-last واقعی (wc-hooks/front-data/product-card)، ریشه‌سازی برش گالری (absolute img + contain)، گالری RTL دو-مدلی در app.js، تک‌دکمه‌ای شدن خرید + تعداد در نوار چسبان، TKA_Drawers API برای بستن مینی‌سبد، تیک خودکار شرایط بعد از updated_checkout، فیلدهای ایمیل/کشور اختیاری+مخفی سمت PHP، تایم‌لاین استاتیک سبد/چک‌اوت، بنرهای طبیعی بدون برش، حذف رنگ‌های تصادفی برندها، نمایش bottomnav در PDP، تایپوگرافی 15px/1.9 و توضیحات 15px/2.2، رفع main تودرتو با id تکراری (tka-pdp-main → div)
- باگ کشف‌شده حین تست: تشخیص مدل RTL با نوشتن +max غلط بود (کلمپ به 0)؛ اصلاح به -max؛ و برش دسکتاپ گالری به‌خاطر grid+height:100% بود (تصویر 740px در اسلاید 520px)
- تست مرورگر روی هارنس با CSS/JS واقعی قالب (mobile 390/360 + desktop 1440): گالری، استپر، data-quantity sync، بستن دروار با دکمه/backdrop، bottomnav، بدون crop — همه پاس
- PHP 8.3 static binary برای php -l روی همهٔ فایل‌های تغییر یافته (بدون خطا)
- خروجی‌ها در روت: /home/z/my-project/toykindangel-v0.24.0.zip و /home/z/my-project/گزارش-و-راهنمای-قالب-v0.24.0.html (+ کپی در public/ برای دانلود از پنل پیش‌نمایش)

Stage Summary:
- نسخه 0.24.0 آماده نصب: آپلود zip در پیشخوان وردپرس + پاک‌کردن کش WP Super Cache
- راهنمای فارسی RTL هشت‌بخشی در روت پروژه قرار گرفت

---
Task ID: 8
Agent: main (Z.ai Code)
Task: رفع باگ‌های بازخورد دور سوم (v0.25.0) — ناموجودها، ستون‌بندی، هدر برگشت PDP، کامنت‌ها

Work Log:
- بازخورد مشتری پس از نصب v0.24.0: (۱) ناموجودها اول + موجودها حذف + تعداد کمتر؛ (۲) گرید ۵ ستونه به‌جای ۴؛ (۳) دو دکمهٔ سبد در PDP + نوار برگشت؛ (۴) UI کامنت‌ها
- ریشه‌یابی باگ ۱: کلوز meta_query با compare «=» روی _stock_status در wc-hooks.php v0.24.0 به‌عنوان WHERE فیلتر می‌شد → فقط outofstock ها برمی‌گشتند (تأیید HTML زنده: هر ۱۲ محصول صفحهٔ اول دسته، ناموجود بودند!)
- فیکس ۱: حذف کامل meta_query/meta_key؛ LEFT JOIN جدول wc_product_meta_lookup + posts_orderby (CASE stock_status='outofstock'، سپس total_sales/post_date/min_price) — هیچ محصولی حذف نمی‌شود
- فیکس ۲: rails خانه (amazing/newest/best) limit 40 → oos_last → slice 10؛ ریل best از SQL با LEFT JOIN (قبلاً meta_key=total_sales محصول‌های بدون متا را حذف می‌کرد)
- فیکس ۳: حذف کامل pdp__bar از single-product.php + همهٔ قواعد CSS (demo/main/responsive/pdp/custom) + هندلر tka-back در main.js؛ ردیف‌های گرید PDP بازشماری شد
- فیکس ۴: تک‌دکمه‌ای شدن خرید — remove_action هستهٔ add_to_cart + دیسپچر (فقط متغیر فرم دارد)؛ دکمهٔ خاکستری تکراری ناموجود در pricebox هم حذف شد
- فیکس ۵: گرید ≥1440 از ۵ به ۴ ستون + per-page ۱۲→۱۶ (مثل مرجع، گرید ۴×۴) + شمارندهٔ «نمایش ۱ تا ۱۶ از ۷۱۱ محصول» در sortbar
- فیکس ۶: بازطراحی کامل UI دیدگاه‌ها (pdp.css ~۱۵۰ خط): فرم گرید دوستونه، پیکر ستاره‌ای در wc.js، مخفی‌کردن عنوان تکراری هسته، دکمهٔ ثبت هم‌رنگ قالب، کارت‌های دیدگاه، اصلاح ترجمهٔ «رای دهید;» با gettext
- تست مرورگری روی هارنس (proxy3 + HTML شبیه‌سازی v0.25): تک‌دکمه، حذف bar، ۴ ستون ۱۴۴۰/۲ ستون موبایل، شمارنده، ستاره‌ها (کلیک → rating=2)، بدون برش گالری — همه پاس؛ php -l بدون خطا
- کامیت 6793e91 روی dev

Stage Summary:
- نسخه 0.25.0 آماده؛ باید ZIP + ممیزی UI کامل (دسکتاپ+موبایل) تولید شود

---
Task ID: 9
Agent: main (Z.ai Code)
Task: ممیزی کامل UI دسکتاپ+موبایل، فیکس‌های کشف‌شده حین ممیزی، گزارش HTML و ZIP نهایی

Work Log:
- ممیزی ۷ صفحه (خانه/دسته/محصول/سبد/تسویه/حساب/علاقه‌مندی) × ۲ نمایش (1440/390) با agent-browser روی پروکسی آینه (HTML زنده + CSS/JS نهایی قالب)
- اسکن DOM: سرریز افقی (صفر)، هدف‌های لمسی، فونت‌های ریز، alt تصاویر (۸۹ مورد alt خالی = تزئینی)
- کشف و رفع: pdp__cta در دسکتاپ display:none بود → با حذف فرم خرید داخل صفحه، دسکتاپ هیچ دکمهٔ خریدی نداشت (v0.25.1: نوار چسبان دسکتاپ فعال + استایل ظرف ۱۳۲۰ + body padding)
- کشف و رفع: صفحهٔ ورود OTP افزونهٔ UMS رنگ فیروزه‌ای inline !important داشت → بازنویسی با body-class scope در custom.css (بنفش قالب) — با مرورگر تأیید (rgb(174,83,153))
- ریزفیکس‌ها (v0.25.2): نشان ناموجود ۱۱px، ویجت‌های خالی سایدبار مخفی، هدف لمسی فوتر/«مشاهدهٔ همه»، dir=ltr شمارهٔ فوتر
- تأیید تطابق مرجع: سایت قدیمی ۱۶ محصول/صفحه و ۴ ستون → قالب جدید هم ۱۶/۴ + شمارندهٔ «نمایش ۱ تا ۱۶ از ۷۱۱»
- خروجی‌ها در روت: toykindangel-v0.25.2.zip (3MB) + ممیزی-UI-قالب-v0.25.html (612KB، ۱۲ اسکرین‌شات embed شده) + گزارش-و-راهنمای-قالب-v0.25.2.html (به‌روز شده؛ نسخهٔ 0.24 حذف شد) — کپی همه در public/
- گزارش ممیزی با مرورگر رندر و تأیید شد (۱۲ تصویر، ۷ سکشن)

Stage Summary:
- نسخهٔ نهایی 0.25.2 آماده نصب؛ کارهای باقی‌مانده سمت مشتری: تعویض بنرهای دمو، تصویر دسته‌ها، حذف ویجت خالی، خرید آزمایشی بعد از نصب

---
Task ID: 10
Agent: main (Z.ai Code)
Task: دور چهارم بازخورد (v0.26.0) — کارت‌های ارسال چک‌اوت، خطای چک‌اوت، ناموجودهای ریل‌های خانه، آیکون‌های اجتماعی فوتر

Work Log:
- HTML زندهٔ صفحهٔ اصلی با allorigins گرفته شد (translate.goog محدود شده بود): ۷ کارت ناموجود در ریل پیشنهاد ویژه + ۱ در پرفروش‌ها → تأیید باگ ریل
- ریشه‌یابی: overfetch 40 + oos_last کافی نبود چون >۳۰/۴۰ تخفیف‌دارها ناموجود بودند؛ فیکس: helper toykindangel_in_stock_only() + overfetch 60 → ناموجودها کلاً از ریل‌های خانه حذف
- باگ گرید چک‌اوت: NoticeGroup خطا که WC داخل فرم prepend می‌کند در گرید دوستونه جا می‌افتاد و ستون‌ها را می‌شکست؛ فیکس: جابه‌جایی JS به بالای فرم (tidyCheckoutNotices) + CSS grid-column:1/-1
- باگ دوم «فاصلهٔ خالی»: float هستهٔ WC روی form-row-first/last باعث فروریختن ارتفاع کارت فیلدها می‌شد؛ ردیف کدپستی/موبایل از کارت بیرون می‌زد و روی «سفارش شما» می‌افتاد (تأیید مرورگری با rect)؛ فیکس: display:flow-root روی کارت‌ها/رپرهای فیلد
- اسکرول خودکار به خطا + فوکوس اولین فیلد نامعتبر (checkout_error در wc.js) + قاب قرمز woocommerce-invalid (با !important قالب، قرمز هسته خنثی می‌شد)
- کارت‌های نحوهٔ ارسال: #shipping_method li کارت هم‌سبک درگاه پرداخت + هایلایت is-selected (JS) + :has() پشتیبان؛ ردیف ارسال بلاک شد تا لیبل «ارسال:» بالای کارت‌ها بیاید
- فوتر: ۴ آیکون برند جدید در اسپرایت (instagram با fill-rule=evenodd وگرنه مربع توپر می‌شد — با مرورگر پیدا شد)، مارک‌آپ آیکون‌محور با aria-label، استایل پایه main.css + حذف استایل pill موبایل
- هارنس: بازسازی چک‌اوت از شل HTML واقعی (mirror/site/qa/checkout-ok.html و checkout-err.html) — تست دسکتاپ 1440 + موبایل 390: کارت‌ها، اسکرول خطا (1912→119)، جابه‌جایی اعلان، بدون overlap — همه پاس
- php -l (php 8.3 static در /tmp/php) + node --check + کامیت 1f3e155 روی dev

Stage Summary:
- نسخه 0.26.0 کامیت شد؛ باقی‌مانده: ممیزی UI، گزارش HTML، به‌روزرسانی راهنما، ZIP در روت

---
Task ID: 10 (final)
Agent: main (Z.ai Code)
Task: بستهٔ نهایی v0.26.0 — ممیزی، گزارش، راهنما، ZIP

Work Log:
- ممیزی ۷ صفحه × ۲ نمایش با agent-browser: سرریز افقی صفر در همه؛ ۲ هدف لمسی ریز کشف و رفع شد (فلش ریل پیشنهاد ویژه ۱۶→۳۲px، لینک بردکرامب ۲۱→۳۹px) — کامیت 1b2ce60
- آیکون اینستاگرام بدون fill-rule مربع توپر می‌شد → fill-rule=evenodd + رندر تأیید شد
- صفحات زندهٔ خانه/دسته/چک‌اوت با allorigins دریافت شد: دسته = «نمایش ۱ تا ۱۶ از ۷۱۱ محصول» صفر ناموجود در صفحهٔ اول (قانون OOS-last سالم)؛ خانهٔ فعلی (v0.25.2 نصب‌شده) هنوز ۷ ناموجود در پیشنهاد ویژه — با نصب ۰.۲۶.۰ رفع می‌شود
- خروجی‌ها در روت my-project + کپی در public/: toykindangel-v0.26.0.zip (4MB, 158 فایل, Version: 0.26.0) + گزارش-و-راهنمای-قالب-v0.26.0.html + ممیزی-UI-قالب-v0.26.html (۱۴ اسکرین‌شات embed) — فایل‌های 0.25.2 از روت حذف شدند
- هر دو HTML با مرورگر رندر و تأیید شدند

Stage Summary:
- نسخهٔ نهایی 0.26.0 آماده نصب؛ کار سمت مشتری: نصب ZIP + پاک‌کردن کش + واردکردن آدرس واقعی شبکه‌های اجتماعی در سفارشی‌سازی

---
Task ID: 11
Agent: main (Z.ai Code)
Task: novak-ui-fixes.css v2.3.0 — مینی‌کارت، تب‌های عمودی دسته‌ها، ۳ باکس موبایل PDP، استایل بخش‌های PDP

Work Log:
- درخواست کاربر: به‌روزرسانی «همان فایل» v2.2.0 بدون حذف چیزی + ۴ آیتم جدید؛ خروجی برای Paste در Customizer
- مرورگر زنده در دسترس نبود (toykindangel.com از sandbox: timeout در curl/مرورگر/allorigins) → کلاس‌ها از سورس تم v0.26.0 محلی استخراج شد (همان نسخه نصب‌شده)
- کاوش سورس: inc/minicart.php (cta/ghost)، inc/cat-drawer.php + demo.css (crail__item 92px، آیکن 40px)، responsive.css (دراور دسکتاپ min-height:60px)، woocommerce/single-product.php (sellerbox/shipmethods/usps با margin-top:20px inline، pdp-sec/pdp-specs/tka-related)
- کشف باگ اضافی: content:"?" در جداکننده مراحل سبد = mojibake «←» — به «←» اصلاح و افشا شد
- قید دقت: فایل v2.2.0 خط‌به‌خط حفظ؛ diff خودکار فقط ۲ تغییر مجاز نشان داد (نسخه 2.2.0→2.3.0 + content fix) + بخش الحاقی
- FIX A: مخفی‌سازی تسویه حساب مینی‌کارت با اسکوپ .tka-minicart__actions (دکمه «رفتن به فروشگاه» حالت خالی سالم ماند) + دکمه «مشاهده سبد» بنفش اصلی با هاور
- FIX B: crail__item 92→64px، آیکن 40→24px (موبایل + صفحه دسته‌ها ≥1024: .cpage 64px/26px)، دراور دسکتاپ ردیف 60→44px/آیکن 24px
- FIX C: موبایل PDP — sellerbox/shipmethods فشرده (بدون سایه، بوردر نرم، فونت 11.5) + usps در body.single-product یک ردیف ۳ستونه باریک (خانه دست‌نخورده ۲ستونه می‌ماند؛ inline margin-top:20px با !important شکسته شد)
- FIX D: کارت یکدست بخش‌های PDP — pdp-sec__card بوردر/سایه نرم، هدر با خط نقطه‌چین + آیکن چیپ صورتی، جدول مشخصات ردیف‌های قرصی (th صورتی radius راست-RTL، td خاکستری radius چپ)، جداکننده فرم دیدگاه، tka-related کارت‌شده (موبایل inset با --pad، دسکتاپ max-width:1320 وسط‌چین) با پدینگ ترک تکراری اسلایدر
- اعتبارسنجی: postcss parse OK (180 rule، 18 media) + بالانس براکت 198/198 + diff ساختاری + خروجی در روت و public/

Stage Summary:
- novak-ui-fixes.css (v2.3.0، 1261 خط، 36KB) در روت و public/ — آماده Paste در Additional CSS؛ پس از تأیید کاربر باید به custom.css قالب منتقل شود
- نکته شبکه: راستی‌آزمایی مرورگری روی سایت زنده این جلسه ممکن نشد (بلاک بودن خروجی)؛ سلتورها مستقیماً از سورس تم نصب‌شده استخراج شده‌اند
