# گزارش Audit و Root Cause Analysis — قالب ToyKind Angel (v0.18.0)

**تاریخ:** ۲۰۲۶-۰۹-۳۰
**دامنه بررسی:** کل کد PHP قالب (`toykindangel`)، قالب‌های WooCommerce، فایل‌های JS و زنجیره hookها
**نوع بررسی:** استاتیک (بدون اجرای Runtime / Query Profiling)
**هیچ فایلی تغییر نکرده است.** این سند فقط تحلیل است.

---

## TL;DR — پاسخ مستقیم به سؤال اصلی

> «چه چیزی در این قالب می‌تواند بعد از فعال‌سازی، CPU و MySQL را زیر فشار بگذارد؟»

در قالب **هیچ حلقهٔ بی‌نهایت، recursion، فراخوانی HTTP خروجی (curl/wp_remote) یا cron رویداد ثبت‌شده‌ای وجود ندارد.** مشکل یک بمب تکی نیست؛ یک **مدل هزینهٔ اشتباه** است:

هر Request — مخصوصاً صفحه اصلی — چند بارِ مکرر و بدون کشِ پایدار، مجموعه‌ای از Queryهای سنگین WooCommerce را اجرا می‌کند و **همان داده‌ها را دو تا سه بار در یک Request دوباره می‌سازد** (یک‌بار برای HTML سمت سرور «SSR» و یک‌بار برای JSON جاوااسکریپت `window.TKA_WP`، به‌علاوه فراخوانی‌های تکراری توابع بدون static cache). با ورودی همزمان کاربران + خزنده‌ها و نبود Page Cache / Object Cache، این یعنی ضربِ «هزینهٔ هر Request × ترافیک» که دقیقاً به‌صورت اشباع CPU (ساخت آبجکت‌های PHP) و اشباع MySQL (تعداد و نوع Queryها) بروز می‌کند. الگوی 500→500→200 که در لاگ‌ها دیده‌اید، با **اشباع استخر PHP-FPM / max_connections مای‌اس‌کیو‌ال** سازگار است، نه با یک باگ منطقی خاص.

و دربارهٔ curlهای داخلی در لاگ‌ها: **قالب هیچ فراخوانی HTTP صادر نمی‌کند.** این درخواست‌ها با بالاترین احتمال **WP-Cron loopback** هستند (هستهٔ وردپرس روی `wp-cron.php?doing_wp_cron` یک درخواست loopback با curl می‌زند و ووکامرس چندین رویداد زمان‌بندی‌شده دارد) که وقتی هر Request سایت کند باشد، صف می‌شوند و فشار را چند برابر می‌کنند.

---

## 1. Root Cause (علت اصلی و محتمل‌ترین علت‌ها)

**علت اصلی (محتمل‌ترین): هزینهٔ غیرعادی و تکراری ساخت داده در هر Request، بدون کشِ بین-Requestی.**
شش عامل با هم ترکیب می‌شوند (به ترتیب اهمیت):

1. **دوباره‌سازی کامل داده‌های صفحه اصلی در دو مسیر موازی** — HTML (SSR) + JSON جاوااسکریپت — که هر دو Queryهای محصول را جداگانه اجرا می‌کنند؛ به‌علاوه سه‌بار فراخوانی شدن برخی بخش‌ها به‌خاطر نبود static cache در توابع SSR.
2. **ساخت درخت دسته‌بندی‌ها با الگوی N+1** روی صفحهٔ «دسته‌بندی‌ها» (و به‌طور شرطی روی همهٔ صفحات وقتی منوی `cats` ست نشده باشد) از طریق دراور فوتر.
3. **مرتب‌سازی پیش‌فرض آرشیو فروشگاه با `meta_key=total_sales` و `orderby=meta_value_num`** روی Query اصلی ووکامرس — JOIN به `wp_postmeta` + filesort در **هر** بازدید از فروشگاه/دسته/برند.
4. **Endpoint جستجوی زندهٔ عمومی، بدون کش و بدون محدودیت نرخ** (هم admin-ajax هم REST) — دو Query `LIKE '%...%'` در هر فراخوانی؛ برای بات‌ها هدفی ارزان و بی‌دفاع است.
5. **فال‌بک «اسکن ۱۰۰ محصول کامل» در ریل شگفت‌انگیز** هر زمان که فهرست محصولات حراجی خالی باشد — تا ۲ بار در هر بازدید صفحه اصلی، فقط برای گرفتن چند ID.
6. **ویجت پیشخوان مدیریت با `wc_get_orders( limit => -1 )`** — هیدریشن کامل همهٔ سفارش‌های امروز و ماه در هر بازدید از داشبورد (مرتبط با فشار MySQL در محیط XAMPP/لوکال که کاربر در پیشخوان کار می‌کند).

---

## 2. Evidence (شواهد کد)

### 2.1 — دوباره‌سازی داده‌های صفحه اصلی در دو مسیر موازی

**مسیر ۱ — HTML سمت سرور (`front-page.php`):**

- `front-page.php:91,103` — ریل شگفت‌انگیز: شرط و خروجی هر دو `toykindangel_ssr_products()['amazing']` را صدا می‌زنند (این تابع static cache دارد، پس فقط یک‌بار Query اجرا می‌شود ✅).
- `front-page.php:77` و `front-page.php:86` — `toykindangel_ssr_stories()` **دو بار** صدا زده می‌شود (شرط + echo) و این تابع **static cache ندارد** (بنگرید `inc/front-ssr.php:130-149`) → هر بار `toykindangel_wc_stories()` با دو `get_terms` (یکی با `meta_query` روی termmeta و `orderby meta_value_num`) اجرا می‌شود (`inc/front-data.php:177-230`).
- `front-page.php:108,109` — `toykindangel_ssr_grid()` دو بار؛ `front-page.php:144,150` — `toykindangel_ssr_brands()` دو بار → هر بار یک `get_terms` + تا ۱۶ × `get_term_meta` + `usort` با `get_term_meta` (`inc/front-data.php:237-288`).
- `front-page.php:156-160` — یک `get_terms` دیگر برای گرید دسته‌های اصلی.

**مسیر ۲ — JSON جاوااسکریپت:**

- `inc/front-data.php:410-432` — `toykindangel_localize_front_data()` روی `wp_enqueue_scripts` (اولویت ۲۰) کل `toykindangel_front_data()` را می‌سازد که **دوباره** صدا می‌زند:
  - `toykindangel_wc_stories()` (خط ۳۸۸) — سومین بار در همین Request؛
  - `toykindangel_wc_brands()` (خط ۳۹۳) — سومین بار؛
  - `toykindangel_wc_rails()` (خط ۳۹۸) — شامل همان سه Queryِ ریل‌ها به‌صورت مستقل و جدا از `toykindangel_ssr_products()` (`inc/front-data.php:94-170` در مقابل `inc/front-ssr.php:27-109`).

> **نتیجه:** در یک بازدید ساده از صفحهٔ اصلی، ریل‌های محصول ~۲ بار، استوری‌ها ۳ بار و برندها ۳ بار ساخته می‌شوند و خروجی JSON محصول‌ها (`window.TKA_WP.products`) هیچ کاربردی در HTML اولیه ندارد — فقط سربار.

### 2.2 — N+1 درخت دسته‌بندی‌ها

`inc/cat-data.php:108-269` — `toykindangel_cats_data()`:

- خط ۱۱۵-۱۲۸: یک `get_terms` کاوشی با `meta_query` روی `tka_rail_order`؛
- خط ۱۵۰: `get_terms` حداکثر ۴۰ دستهٔ ریشه (با `meta_key` + `meta_query NOT EXISTS` روی termmeta)؛
- خط ۱۷۸-۱۸۵: **به‌ازای هر دستهٔ ریشه** یک `get_terms` برای فرزندان (تا ۱۰۰)؛
- خط ۱۹۵-۲۰۲: **به‌ازای هر فرزند** یک `get_terms` برای نوه‌ها (تا ۱۰۰)؛

→ تعداد Query ≈ `2 + T + C` که در آن T تعداد دسته‌های ریشه (≤۴۰) و C مجموع فرزندان است. با ۴۰ ریشه و ۱۰ فرزند به‌طور میانگین: **~۴۴۲ Query فقط برای یک صفحه.**

**کجا اجرا می‌شود؟**

- `footer.php:138` → `toykindangel_cats_drawer()` → `toykindangel_drawer_data()` (`inc/cat-drawer.php:279-305`) → در نبود منوی `cats` → `toykindangel_drawer_cats_from_terms()` → `toykindangel_cats_data()`. یعنی **شرطی روی همهٔ صفحات**. (نکتهٔ مهم: بعد از درون‌ریزی دمو، `inc/demo-import.php:1175` مکان منوی `cats` را ست می‌کند و مسیر ارزان منو جایگزین می‌شود — پس این مسیر عمدتاً قبل از دمو/با حذف منو فعال است.)
- `inc/cat-data.php:277-300` — `toykindangel_localize_cats_data()` روی صفحهٔ «دسته‌بندی‌ها» **همیشه** مستقیماً `toykindangel_cats_data()` را صدا می‌زند؛ و چون static cache در `toykindangel_drawer_terms_payload()` (خط ۱۵۷-۱۶۴) فقط فراخوانی‌های دراور را پوشش می‌دهد، روی همین صفحه **دو درخت کامل** در یک Request ساخته می‌شود.

### 2.3 — مرتب‌سازی آرشیو با meta در Query اصلی

`inc/wc-hooks.php:55-93` — `toykindangel_wc_product_query()` روی `woocommerce_product_query`:

- خط ۸۴-۹۰: حالت پیش‌فرض (`''` یا `best`) → `$q->set('meta_key','total_sales')` + `$q->set('orderby','meta_value_num')`.
- خط ۷۲-۸۳: `cheap`/`exp` → `meta_key=_price` + `orderby meta_value_num`.

این یعنی **هر بازدید از فروشگاه / دسته / برند / برچسب** (بدون `orderby` در URL، یعنی حالت عادی کاربر و خزنده) یک Query با `JOIN wp_postmeta ... WHERE meta_key='total_sales' ORDER BY meta_value_num` تولید می‌کند — روی کاتالوگ بزرگ = filesort سنگین در هر بازدید، و هیچ کشی روی آن نیست.

### 2.4 — Endpoint جستجوی زندهٔ بدون کش و بدون Rate Limit

- `inc/ajax-search.php:120-173` — `toykindangel_ls_products()`: `WP_Query` با `'s' => $term` روی `product` (جستجوی `LIKE` روی عنوان/محتوا) + به‌ازای هر نتیجه `wc_get_product` + `get_the_terms` + `get_price_html`.
- `inc/ajax-search.php:182-220` — `toykindangel_ls_posts()`: Query دوم `LIKE` روی نوشته‌ها.
- `inc/ajax-search.php:294-295` — ثبت روی `wp_ajax` **و** `wp_ajax_nopriv` (عمومی، بدون nonce — برای جستجو قابل قبول، اما بدون هیچ محدودیت نرخ/کش)؛ `inc/rest-search.php:41-67` همان payload را روی REST هم منتشر می‌کند.
- سمت کلاینت debounce فقط ۳۰۰ms است (`assets/js/live-search.js:172-193`) — رفتار طبیعی کاربر مشکلی ندارد، اما هیچ `Cache-Control`/Transient/Rate-limit سمت سرور وجود ندارد؛ چند درخواست همزمان بات به `admin-ajax.php?action=tka_live_search` مستقیماً به Queryهای LIKE می‌رسد.

### 2.5 — فال‌بک اسکن ۱۰۰ محصول کامل

- `inc/front-ssr.php:57-66` و `inc/front-data.php:106-115` — اگر `wc_get_product_ids_on_sale()` خالی برگردد: `wc_get_products( array( 'limit' => 100, 'status' => 'publish' ) )` یعنی **هیدریشن کامل ۱۰۰ آبجکت WC_Product** (post + همهٔ meta + قیمت‌ها) فقط برای استخراج چند ID، سپس یک Query دیگر برای ۱۰ محصول نهایی. این فال‌بک در بدترین حالت (فهرست حراجی خالی) **دو بار در هر بازدید صفحهٔ اصلی** اجرا می‌شود (یک‌بار در SSR، یک‌بار در dataset).
- شرط رخ دادن: فروشگاهی که هیچ محصول حراجی ندارد (مثلاً در کمپین «جشنواره» که محصولات با فلگ `_tka_festival` و **بدون تخفیف** علامت می‌خورند — `inc/festival.php:46-55` صریحاً می‌گوید «even when they carry no discount»). توجه: پل `toykindangel_festival_sync_onsale_cache` (`inc/festival.php:133-158`) بعد از اولین rebuild، IDهای جشنواره را داخل transient `wc_products_onsale` ادغام می‌کند و فال‌بک را غیرفعال می‌سازد؛ اما تا آن لحظه (و هر بار که transient خالی از حراجی rebuild شود) فال‌بک اجرا می‌شود.

### 2.6 — ویجت داشبورد با limit -1 (فقط ادمین)

`inc/admin-meta.php:306-324` — دو فراخوانی `wc_get_orders( ... 'limit' => -1 )` برای سفارش‌های «امروز» و «این ماه» با return پیش‌فرض `objects` — هیدریشن کامل همهٔ سفارش‌ها در هر بازدید از داشبورد. سپس خط ۳۴۷-۳۵۴: `wc_get_products( limit => 200, return => 'objects' )` برای شمارش موجودی کم. روی فروشگاه پرترافیک/پرسفارش این بخش در محیط XAMPP (که کاربر زمان زیادی در پیشخوان می‌گذراند) می‌تواند MySQL را درگیر کند.

### 2.7 — هزینه‌های پایهٔ هر Request (جمع‌کننده)

- `header.php:58` — `WC()->cart->get_cart_contents_count()` و `inc/minicart.php:118` — رندر کامل مینی‌سبد روی `wp_footer` برای همهٔ بازدیدکننده‌ها → راه‌اندازی Session ووکامرس و خواندن/نوشتن جدول `woocommerce_sessions` در هر Request (استاندارد ووکامرس است، اما در ترکیب با بقیهٔ بار، جمع‌کنندهٔ فشار است).
- `inc/product-card.php:41-54` — برای هر کارت محصول متغیر: `$product->get_variation_prices( true )` (بار اول هر نسخه: خواندن همهٔ variationها؛ بعدش transient-cached).
- `inc/seo-schema.php:300-330` — `toykindangel_jsonld_itemlist()` در `wp_head` آرشیو، حلقهٔ اصلی را دوباره می‌چرخاند و تا ۲۴ بار `wc_get_product` اجرا می‌کند (کران‌دار ولی تکراری).
- `woocommerce/single-product.php:219,541-547` — `wc_get_related_products()` + ۱۰ بار `wc_get_product` و رندر pcard (کران‌دار).
- `inc/wishlist.php:84-109` — endpoint عمومی `tka_wishlist_render`: تا ۶۰ بار `wc_get_product` در هر فراخوانی (فقط از صفحهٔ علاقه‌مندی‌ها صدا زده می‌شود — `assets/js/main.js:322-360`).
- `inc/wc-pages-fix.php:114` و `inc/wishlist.php:137` — دو کار روی `admin_init` در **هر** Request ادمین (سبک و idempotent، ولی اضافه).

### 2.8 — آنچه بررسی شد و «مقصر نیست» (مهم برای جداسازی)

| مورد | نتیجه |
|---|---|
| فراخوانی HTTP خروجی (curl / wp_remote / file_get_contents http) | **صفر مورد در PHP قالب** — grep کامل انجام شد |
| WP-Cron رویداد ثبت‌شده (`wp_schedule_*`) | **صفر مورد** در قالب؛ loopbackهای curl در لاگ = رفتار هسته + ووکامرس |
| حلقهٔ بی‌نهایت / recursion PHP | **یافت نشد** (پل `festival_sync_onsale_cache` روی `woocommerce_delete_product_transients` نیز بازبینی شد — recursion ندارد) |
| Polling شبکه از JS | `setInterval`ها فقط تایمر/اسلایدر سمت کلاینت‌اند (`assets/js/app.js:56,88`)؛ `fetch`ها فقط one-shot (جستجو و wishlist) |
| REST API قالب | فقط یک مسیر read-only جستجو (`inc/rest-search.php`) |
| Session/COOKIE دستکاری‌شده | هیچ `session_start` دستی وجود ندارد |
| تغییر Query اصلی (pre_get_posts و…) | فقط `woocommerce_product_query` برای مرتب‌سازی آرشیو (بند ۲.۳) |
| `fa-text.php` (فیلتر gettext روی همهٔ رشته‌ها) | str_replace سبک — ناچیز |
| `format.php` (تاریخ جلالی/ارقام فارسی) | پردازش نمایشی سبک — ناچیز |

---

## 3. Impact (چگونه این کد علائم شما را تولید می‌کند)

| علامت گزارش‌شده | سازوکار از روی کد |
|---|---|
| **CPU بالا در سرور** | (الف) ساخت مکرر آبجکت‌های PHP: تا ۲۰۰ آبجکت WC_Product در یک بازدید صفحهٔ اصلی (فال‌بک ۱۰۰تایی ×۲) + کارت‌های pcard + `get_variation_prices`. (ب) رندر سه‌گانهٔ استوری/برندها و دوباره‌سازی dataset — همه PHP-Intensive و بدون کش بین-Requestی. (ج) زیر فشار، هر Request طولانی‌تر می‌شود → workerها اشغال می‌مانند → صف → 500. |
| **فشار MySQL / کرش MySQL** | N+1 درخت دسته‌ها (تا ~۸۰۰+ Query در یک Request صفحهٔ دسته‌بندی‌ها)، Queryهای `meta_query`/`orderby meta_value_num` روی `wp_postmeta` و `wp_termmeta` (ستون‌های بدون ایندکس ترکیبی)، LIKE-searchهای جستجوی زنده، و مرتب‌سازی `total_sales` در **هر** بازدید آرشیو. این‌ها با ترافیک همزمان ضرب می‌شوند؛ روی XAMPP با دیتابیس کوچک‌تر اما منابع محدودتر و کار مداوم در پیشخوان (limit -1 سفارش‌ها) همین نتیجه را می‌دهد. |
| **RAM بالا / هنگ سرور** | هیدریشن آبجکت‌های سنگین (۱۰۰ محصول ×۲، سفارش‌های limit -1) + ماندگاری طولانی workerها زیر صف + Session ووکامرس برای هر مهمان؛ در سرورهای کوچک → swap → به‌ظاهر «هنگ». |
| **HTTP 500 سپس 200** | الگوی کلاسیک اشباع منابع: وقتی همهٔ workerها/Mutexهای MySQL مشغول‌اند، Requestهای جدید 500/503 می‌گیرند؛ با آزاد شدن منابع دوباره 200. هیچ مسیر کدی در قالب 500 «منطقی» تولید نمی‌کند (خطای مهلک ثابتی یافت نشد). |
| **درخواست‌های داخلی curl در لاگ** | WP-Cron loopback هسته (`wp-cron.php?doing_wp_cron`) — ووکامرس رویدادهای زمان‌بندی‌شده دارد و قالب هیچ cron ای ثبت نمی‌کند. وقتی پاسخ‌دهی سایت کند شود، صف cron پشت هم spawn می‌شود و هر loopback یک بوت کامل WP+WooCommerce است → تقویت‌کنندهٔ حلقهٔ فشار. |
| **بدتر شدن بعد از فعال‌سازی قالب** | همهٔ بندهای بالا فقط با فعال شدن قالب به جریان درخواست‌های عمومی وصل می‌شوند (مخصوصاً صفحهٔ اصلی که سنگین‌ترین صفحه است). |

---

## 4. SSR Analysis (بررسی مستقل قابلیت «SSR» صفحهٔ اصلی)

### پاسخ به ۸ سؤال

**۱. آیا وردپرس به‌صورت عادی خودش SSR انجام نمی‌دهد؟**
بله — قطعاً. وردپرس یک CMS رندر-سمت-سرور PHP است؛ هر قالب کلاسیک به‌طور پیش‌فرض HTML نهایی را روی سرور می‌سازد. مفهوم «SSR» در ادبیات React/SPA جا افتاده است، نه در دنیای PHP. قالب‌های کلاسیک وردپرس از روز اول «SSR کامل» دارند.

**۲. آیا برای SEO واقعاً به این SSR سفارشی نیاز دارد؟**
به «معماری SSR سفارشی» نه؛ به «رندر PHP محتوا» بله. مشکل SEO که در کامنت‌های کد ذکر شده (`inc/front-ssr.php:3-12`) واقعی بوده، اما علتش این بود که **دموی اصلی HTML محتوای ریل‌ها را با جاوااسکریپت از `data.js` می‌کشید** (app.js). راه‌حل درست، همان کاری است که هر قالب استاندارد وردپرس می‌کند: رندر در PHP قالب. هیچ سیستم «SSR» جداگانه‌ای لازم نبود — تابع `toykindangel_ssr_products()` در واقع فقط یک data-provider است، نه یک لایهٔ SSR.

**۳. این پیاده‌سازی دقیقاً چه کاری انجام می‌دهد؟**
سه کار همزمان: (الف) رندر ریل‌ها/استوری‌ها/برندها/هیرو/بنرها در PHP از دادهٔ واقعی (`inc/front-ssr.php`)؛ (ب) ساخت همان داده به‌صورت JSON در `window.TKA_WP` برای JS (`inc/front-data.php:410-432`)؛ (ج) نگه‌داشتن app.js دمو به‌عنوان fallback برای کانتینرهای خالی (گاردِ «فقط اگر خالی بود پر کن»). یعنی **دو مسیر رندر کامل + یک مسیر دمویی** به‌طور همزمان زندگی می‌کنند.

**۴. آیا واقعاً مزیت SEO ایجاد می‌کند؟**
مزیت SEO از «حضور محتوا در HTML اولیه» می‌آید که با مسیر (الف) — یعنی رندر معمولی PHP — تحقق می‌یابد. JSON دوم (`TKA_WP`) هیچ مزیت SEO ای ندارد (اسکریپت inline است و محصول‌ها تکرار می‌شوند) و markup دمویی fallback هم که به‌طور پیش‌فرض خاموش است (`toykindangel_front_fallback_enabled()` — `inc/front-data.php:297-299`). پس مزیت SEO واقعی هست، اما **متعلق به رندر PHP است، نه به معماری فعلی**.

**۵. آیا Query اضافی به WooCommerce/DB ایجاد می‌کند؟**
بله، به‌طور قابل‌اندازه‌گیری: ریل‌های محصول ۲ بار (SSR + dataset)، استوری‌ها ۳ بار، برندها ۳ بار، و در حالت بدون فهرست حراجی، تا ۲ هیدریشن کامل ۱۰۰ محصول. بخش عمدهٔ این‌ها با یک static/transient cache یا حذف مسیر JSON حذف می‌شود.

**۶. آیا پیاده‌سازی بیش از حد پیچیده یا پرهزینه است؟**
بله. سه منبع حقیقت (SSR HTML، TKA_WP JSON، دموی data.js) و سه نسخه از markup کارت (PHP pcard، JS pcard، دمو) یعنی هم هزینهٔ اجرا و هم هزینهٔ نگهداری (هر تغییر کارت باید در دو زبان دو بار پیاده شود). تابع‌های `ssr_stories`/`ssr_brands`/`ssr_grid` حتی static cache ندارند و در همان فایل قالب دوبار صدا زده می‌شوند.

**۷. اگر حذف یا ساده شود، SEO یا قابلیتی از بین می‌رود؟**
نه، به شرطی که **رندر PHP حفظ شود** (که خودش همان SSR «واقعی» است). قابل حذف: payload `TKA_WP.products` در صفحهٔ اصلی، فراخوانی‌های دوبارهٔ stories/brands/grid، و کل مسیر demo-fallback (از v0.15.0 به‌طور پیش‌فرض خاموش است). چیزی که باید بماند: wiring اسلایدر/تایمر در app.js که از قبل گارد دارد و فقط به markup موجود جلوه می‌دهد. **حذف کامل رندر PHP و برگشت به رندر JS = از دست دادن SEO — این تنها سناریوی ممنوع است.**

**۸. معماری پیشنهادی بهتر؟**
یک منبع حقیقت: PHP رندر کند؛ JS فقط رفتار (اسلایدر/تایمر/دراور). دادهٔ JSON فقط برای بخش‌هایی که واقعاً به آن نیاز دارند (مثل صفحهٔ دسته‌بندی‌ها که مرورگر تعاملی است). به‌علاوه لایهٔ کش:
- Transient/Object-cache برای خروجی `toykindangel_cats_data()`، `toykindangel_wc_stories()`، `toykindangel_wc_brands()` و ریل‌ها (TTL مثلاً ۵-۱۵ دقیقه یا invalidation روی `woocommerce_update_product`)؛
- حذف فال‌بک ۱۰۰تایی و جایگزینی با ID-list کش‌شده؛
- (تصمیم زیرساختی) Page Cache در سطح سرور که بار صفحهٔ اصلی را تقریباً صفر می‌کند.

**جمع‌بندی وضعیت SSR:** «مفید در هدف، اشتباه در معماری، پرهزینه در پیاده‌سازی فعلی» — رندر سمت سرور باید بماند؛ پیاده‌سازی فعلی (دو مسیر موازی + عدم کش + عدم static cache) عامل بخش بزرگی از مشکل پرفورمنس صفحهٔ اصلی است و باید ساده شود.

---

## 5. جدول Severity

| # | مشکل | Severity | Confidence |
|---|---|---|---|
| 1 | دوباره‌سازی دادهٔ صفحهٔ اصلی در دو مسیر + نبود static cache در ssr_stories/ssr_brands/ssr_grid | **High** | Confirmed (کد) — اثر بر بحران: Strong suspicion |
| 2 | N+1 درخت دسته‌ها: همیشه روی صفحهٔ دسته‌بندی‌ها (×۲)، شرطی روی همهٔ صفحات بدون منوی cats | **High** | Confirmed (کد) — اثر بر بحران: Strong suspicion |
| 3 | مرتب‌سازی پیش‌فرض آرشیو با `meta_key total_sales` / `meta_value_num` روی Query اصلی | **High** (برای کاتالوگ بزرگ؛ Medium برای کاتالوگ کوچک) | Strong suspicion |
| 4 | فال‌بک هیدریشن ۱۰۰ محصول ×۲ در صفحهٔ اصلی وقتی فهرست حراجی خالی است | **Medium-High** (شرطی) | Confirmed (کد)؛ فراوانی رخداد: Hypothesis |
| 5 | Endpoint جستجوی زندهٔ عمومی بدون کش/Rate-limit (دو LIKE در هر فراخوانی، دو مسیر admin-ajax + REST) | **Medium** | Strong suspicion |
| 6 | ویجت داشبورد: `wc_get_orders limit -1` ×۲ + ۲۰۰ آبجکت محصول | **Medium** (فقط ادمین — مرتبط با XAMPP) | Confirmed (کد) |
| 7 | Session ووکامرس + مینی‌سبد/فرگمنت‌ها در هر Request (استاندارد، جمع‌کننده) | **Low-Medium** | Confirmed (کد) — سهم مطلق کم |
| 8 | WP-Cron loopback به‌عنوان توضیح‌دهندهٔ curlهای داخلی در لاگ | **Low** (خودش علت نیست، تقویت‌کننده است) | Strong suspicion |
| 9 | JSON-LD ItemList چرخش دوبارهٔ حلقهٔ آرشیو در wp_head؛ Related products PDP؛ endpoint wishlist عمومی | **Low** | Confirmed (کد)، اثر محدود |
| 10 | نبود Page Cache / Object Cache در استک (عامل تشدید همهٔ موارد) | **High** (محیطی، نه کد قالب) | Hypothesis تا تأیید سرور |

**عدم قطعیت صادقانه:** اثبات قطعی اینکه کدام‌یک «همان» عامل بحران است، بدون Query Monitor / `wp profile` / Slow Query Log و نمودار همبستگی زمانی (چه صفحاتی در لحظهٔ spike بازدید می‌شده‌اند) ممکن نیست. آنچه Confirmed است «وجود و تعداد این Queryها در کد در هر Request» است؛ انتساب نهایی به spikeها Strong suspicion / Hypothesis است.

---

## 6. Recommended Fix (پیشنهاد — بدون اجرا)

### فیکس ۱ — یک‌بار ساختن دادهٔ صفحهٔ اصلی (بند ۲.۱)
- **گزینهٔ الف (توصیه‌شده):** حذف آرایهٔ products از `window.TKA_WP` در صفحهٔ اصلی (چون SSR جایگزینش شده) و بستن dataset فقط به fallback حالت بدون داده. مزیت: حذف نصف Queryهای صفحهٔ اصلی؛ ریسک: باید مطمئن شد app.js در حالت بدون دمو چیزی را دوباره نمی‌کشد (گاردها موجودند). 
- **گزینهٔ ب:** نگه‌داشتن JSON، ولی ساخت `toykindangel_front_data()` از خروجی `toykindangel_ssr_products()` (منبع واحد + static cache مشترک) به‌جای دو pipeline مستقل. مزیت: رفتار JS فعلی دست‌نخورده؛ عیب: هنوز خروجی JSON اضافه می‌ماند.
- **در هر دو:** static cache برای `ssr_stories/ssr_brands/ssr_grid` (مشابه `ssr_products`) تا فراخوانی دوباره در همان قالب هم حذف شود.

### فیکس ۲ — کش درخت دسته‌ها (بند ۲.۲)
- **گزینهٔ الف:** cache کردن خروجی `toykindangel_cats_data()` در transient با invalidation روی `edited_product_cat`/`created_product_cat`/`delete_product_cat`. مزیت: از ~۴۰۰ Query به ~۰؛ عیب: کد invalidation لازم دارد.
- **گزینهٔ ب:** اتصال دراور فقط به منوی `cats` (مسیر ارزان موجود) و حذف مسیر terms در دراور؛ صفحهٔ دسته‌بندی‌ها از همان transient کش‌شده بخواند.
- همچنین دو فراخوانی مجزا (`toykindangel_localize_cats_data` و دراور) باید از یک cache مشترک بخوانند.

### فیکس ۳ — مرتب‌سازی آرشیو (بند ۲.۳)
- **گزینهٔ الف:** نگه‌داشتن UX ولی کش کردن IDهای «پرفروش‌ترین» (مثلاً transient هر ۱۲ ساعت یا محاسبه در cron) و `post__in` + `orderby post__in`. مزیت: JOIN حذف می‌شود؛ عیب: دادهٔ کمی کهنه.
- **گزینهٔ ب:** تغییر پیش‌فرض به `orderby=date` (ایندکس‌شده) و گذاشتن sortهای متا فقط با انتخاب صریح کاربر. مزیت: ساده؛ عیب: تغییر رفتار دمو.
- **گزینهٔ ج (اگر کاتالوگ بزرگ می‌شود):** افزودن ایندکس ترکیبی روی `wp_postmeta(meta_key, meta_value)` — راه‌حل دیتابیسی، نه کد.

### فیکس ۴ — فال‌بک ۱۰۰ محصول (بند ۲.۵)
- حذف هیدریشن کامل؛ شناسایی IDها با یک Query سبک روی `wp_postmeta` (`_regular_price` > `_price` — با `$wpdb->get_col`) و cache در transient (مثلاً ۳۰ دقیقه). فقط ID لازم است، نه آبجکت.

### فیکس ۵ — جستجوی زنده (بند ۲.۴)
- کش transient کوتاه (۳۰-۶۰ ثانیه) به کلید نرمال‌شدهٔ عبارت؛ پاسخ ۴۲۹/Throttle برای فرکانس‌های غیرعادی هر IP؛ محدود کردن مسیر REST با همان کد. مزیت: بات‌ها دیگر نمی‌توانند LIKE بچرخانند؛ عیب: نگهداری کلید کش.

### فیکس ۶ — داشبورد (بند ۲.۶)
- جای `limit -1` با `wc_get_orders( 'return' => 'ids' )` + جمع از یک Query تجمیعی `$wpdb` روی HPOS، یا حداقل `limit => 500`. (کالس‌های آماری هستهٔ ووکامرس هم الگوی مشابه دارند.)

### فیکس ۷ (زیرساختی — بدون تغییر کد قالب)
- Page Cache در سطح سرور (LiteSpeed/Nginx FastCGI cache) برای کاربران مهمان + Object Cache (Redis/Memcached) در صورت دسترسی. این یک قلم، فشار بندهای ۱ تا ۴ را برای ترافیک مهمان عملاً حذف می‌کند.

### ریسک‌های عمومی
- هر کشی که invalidation درست نداشته باشد، خطر «دادهٔ کهنه» (قیمت/موجودی) دارد — invalidation باید به `woocommerce_update_product`, `edited_term`, `customize_save_after` وصل شود.
- حذف dataset JSON قبل از تست رگرسیون صفحهٔ اصلی (اسلایدر/تایمر) انجام شود؛ گاردهای app.js موجودند اما باید تأیید شود.

---

## 7. Fix Priority (ترتیب اجرا پیشنهادی)

1. **قدم صفر — اندازه‌گیری:** یک بازدید از صفحهٔ اصلی / آرشیو / صفحهٔ دسته‌بندی‌ها با Query Monitor (یا `wp profile stage --url=…`) و ثبت تعداد Query و زمان. بدون این، اثر فیکس‌ها قابل اثبات نیست. (هیچ تغییری لازم ندارد.)
2. **فیکس ۱ + فیکس ۲** (دادهٔ صفحهٔ اصلی و درخت دسته‌ها) — بیشترین کاهش فشار با کمترین ریسک رفتاری.
3. **فیکس ۴ + فیکس ۳** (فال‌بک ۱۰۰تایی و مرتب‌سازی آرشیو).
4. **فیکس ۶ + فیکس ۵** (داشبورد برای XAMPP، جستجوی زنده برای مقاوم‌سازی در برابر بات).
5. **فیکس ۷** (Page Cache سرور) — می‌تواند همزمان با قدم ۲ فعال شود اگر هاست اجازه دهد؛ تأثیر فوری بر علائم.
6. **بررسی‌های مکمل بعد از پایداری:** Slow Query Log مای‌اس‌کیو‌ال برای تأیید بندهای ۳ و ۵؛ لاگ WP-Cron برای تأیید منشأ curlها؛ در صورت ادامهٔ 500ها، بررسی `memory_limit`، `max_connections` و OPcache در سطح هاست.

---

## پیوست — نقشهٔ فایل‌های کلیدی ارجاع‌شده

| فایل | بخش‌های مرتبط |
|---|---|
| `inc/front-ssr.php` | `toykindangel_ssr_products()` ۲۷-۱۰۹، `ssr_stories()` ۱۳۰-۱۴۹، `ssr_brands()` ۲۴۵-۲۶۲ |
| `inc/front-data.php` | `toykindangel_wc_rails()` ۹۴-۱۷۰، `wc_stories()` ۱۷۷-۲۳۰، `wc_brands()` ۲۳۷-۲۸۸، `front_data()` ۳۳۶-۴۰۴، localize ۴۱۰-۴۳۲ |
| `front-page.php` | فراخوانی‌های دوبل ۷۷/۸۶، ۹۱/۱۰۳، ۱۰۸/۱۰۹، ۱۴۴/۱۵۰، گرید دسته‌ها ۱۵۶-۱۹۰ |
| `inc/cat-data.php` | `toykindangel_cats_data()` ۱۰۸-۲۶۹، localize ۲۷۷-۳۰۰ |
| `inc/cat-drawer.php` | `drawer_data()` ۲۷۹-۳۰۵، مسیر منو ۸۳-۱۴۹ |
| `footer.php` | فراخوانی دراور ۱۳۸ |
| `inc/wc-hooks.php` | مرتب‌سازی آرشیو ۵۵-۹۳ |
| `inc/ajax-search.php` / `inc/rest-search.php` | ۱۲۰-۲۹۵ / ۴۱-۶۷ |
| `inc/festival.php` | ۴۶-۵۵، ۸۱-۹۶، ۱۳۳-۱۵۸ |
| `inc/admin-meta.php` | ویجت داشبورد ۳۰۶-۳۵۴ |
| `inc/product-card.php` | `pcard_price_parts()` ۴۱-۵۴ |
| `inc/wishlist.php` / `inc/minicart.php` | ۸۴-۱۳۷ / ۹۵-۱۳۳ |
| `assets/js/live-search.js` / `assets/js/app.js` | debounce ۱۷۲-۱۹۳ / intervalهای کلاینت ۵۶، ۸۸ |

*گزارش صرفاً تحلیلی است؛ مطابق دستور، هیچ فایل، تنظیم یا داده‌ای تغییر نکرده است.*
