# TKA Architecture & Performance Audit

**قالب مورد ممیزی:** `toykindangel` (نسخه `0.19.0`، شاخه `dev`، commit `96841ad`)
**منبع:** https://github.com/isnamni/wptheme-game
**نوع ممیزی:** استاتیک + استنتاج query-count، مستقل، فقط‌خواندنی
**تاریخ:** ۲۰۲۶-۰۹-۳۰
**هیچ فایلی تغییر نکرده است.** هیچ push به GitHub انجام نشده است.

---

## Executive Summary

معماری کلی قالب **سالم‌تر از حد انتظار** برای یک قالب ساخته‌شده با AI است. نویسنده (یا AI Agent قبلی) در نسخهٔ `v0.19.0` به‌طور صریح چندین مشکل واقعی را حل کرده است: pipeline تکراری homepage (SSR + JSON) اکنون از یک منبع واحدِ statically-cached می‌خواند (`toykindangel_home_rail_products`)؛ لایهٔ کش دو‌سطحی (static درون-Request + transient بین-Request) با invalidation رویدادمحور برای taxonomies و محصولات پیاده شده (`inc/cache.php`)؛ Live Search اکنون هم throttle دارد (۶۰ درخواست/۱۵ثانیه per-IP) و هم کش ۳ دقیقه‌ای per-term؛ Shop archive از main query استفاده می‌کند (۱۲ محصول/صفحه) نه WP_Query سفارشی.

با این حال، **مشکل بحرانی وجود دارد**: چندین template partial بزرگ (homepage catgrid، صفحهٔ `/categories/`، دراور فوتر، آرشیو دسته) همچنان **N+1 واقعی و بدون کش** دارند که در هر بار بارگذاری اجرا می‌شوند. این موارد توسط ابزار قبلی حل نشده‌اند چون در template files نه در inc/ functions قرار دارند و از wrapper‌های cached عبور می‌کنند.

- **N+1 وجود دارد؟** بله — ۴ محل بحرانی (homepage catgrid، page-categories SSR، archive-product subcats، footer drawer from_menu) + چندین محل کم‌اهمیت‌تر.
- **Over-fetching وجود دارد؟** خفیف — `window.TKA_WP` (~۱۴-۱۶KB) با SSR HTML هم‌پوشانی دارد ولی منبع query واحد است.
- **آیا Shop کل catalog را می‌خواند؟** خیر — ۱۲ محصول/صفحه با main query + pagination استاندارد. اما ordering پیش‌فرض `meta_key=total_sales` روی هر بار بازدید JOIN به postmeta می‌زند.
- **آیا Homepage بیش از نیاز hydrate می‌کند؟** نه برای rails (۳۰ محصول = ۳ ریل × ۱۰، یک‌بار fetch). اما catgrid آن همه top-level دسته را fetch + per-term meta می‌زند بدون کش.
- **Memory خطرناک است؟** در frontend خیر. در **admin dashboard** بله — `wc_get_orders('limit'=>-1)` تمام order IDهای ماه را در آرایه PHP می‌ریزد.
- **CPU-heavy operation وجود دارد؟** خفیف — `usort` برندها با `get_term_meta` در comparator؛ rebuild درخت دسته‌ها روی flush cache.

**Verdict کلی:** معماری پایه مستحکم است؛ ایرادات واقعی در **template partials بدون کش** متمرکزند، نه در طراحی هسته. این قابل اصلاح است بدون refactor معماری.

---

## Critical Findings

> «Critical» = مشکلات واقعی و اثبات‌شده که در هر بار بارگذاری صفحه اجرا می‌شوند و با افزایش catalog به‌صورت **خطی یا بدتر** رشد می‌کنند.

### C1 — Homepage «دسته‌بندی‌های فروشگاه» rail: N+1 بدون کش روی هر homepage

- **Severity:** CRITICAL (در catalog بزرگ)
- **File:** `front-page.php`
- **Line:** `156-176` (get_terms) + `170-187` (foreach با get_term_meta + wp_get_attachment_image_url)
- **Problem:**
  ```php
  $tka_main_cats = get_terms( array(
      'taxonomy'   => 'product_cat',
      'parent'     => 0,
      'hide_empty' => false,
      // ❌ NO 'number' => UNBOUNDED — همهٔ top-level دسته‌ها
  ) );
  foreach ( $tka_main_cats as $tka_main_cat ) {
      $tka_main_thumb   = (int) get_term_meta( $tka_main_cat->term_id, 'thumbnail_id', true );
      $tka_main_img_url = $tka_main_thumb ? wp_get_attachment_image_url( $tka_main_thumb, 'woocommerce_thumbnail' ) : '';
      // ...
  }
  ```
  `get_terms()` در وردپرس **term meta cache را prime نمی‌کند**. پس هر `get_term_meta` یک query جدا است. `wp_get_attachment_image_url` هم به post + post_meta یک attachment دست می‌زند که در این context primed نیست.
- **Evidence (code):** بدون wrapper `toykindangel_cache_get`/`cache_set`، برخلاف `toykindangel_wc_stories()` که ۵ خط بالاتر transient-cached است.
- **Runtime Evidence (تخمین محاسبه‌شده از کد):**
  - ۱ query `get_terms` + N × (`get_term_meta` + تا ۲ query attachment) = `1 + 3N`
  - N=20 دسته: ~۶۱ query
  - N=40 دسته: ~۱۲۱ query
  - N=200 دسته: ~۶۰۱ query
- **Impact:** در هر بار بارگذاری homepage، سرد و گرم. با ۵٬۰۰۰ محصول و ~۱۰۰ دستهٔ top-level، homepage ~۳۰۰ query فقط برای این rail می‌زند. در ترافیک همزمان → اشباع MySQL.
- **Recommended Fix:** (الف) اضافه کردن `'number' => 24` به get_terms؛ (ب) `_prime_term_caches($ids, false)` بعد از get_terms برای priming دسته‌جمعی term meta؛ (ج) wrap کل بلوک در `toykindangel_cache_get('home_catgrid')` با همان الگوی `home_stories`.

### C2 — صفحهٔ `/categories/` SSR grid + popular rail: N+1 بدون transient

- **Severity:** CRITICAL
- **File:** `page-categories.php`
- **Line:** `349-374` (SSR grid با `toykindangel_cats_page_children` + `card_media` per top term) و `380-406` (popular rail با `pop_media` per term)
- **Problem:** چهار تابع helper هر کدام query جدا می‌زنند و **هیچ‌کدام transient-cached نیستند** (برخلاف `toykindangel_cats_data()` در `inc/cat-data.php` که transient `tka_cache_cats_tree` دارد):
  - `toykindangel_cats_page_top_terms()` (line 51): `get_terms(number=40, meta_query)` — خودش ۱ query ولی `meta_query` روی termmeta می‌زند.
  - `toykindangel_cats_page_children($term, 4)` (line 104): به‌ازای هر top term یک `get_terms` جدا → **۴۰ query**.
  - `toykindangel_cats_page_card_media($term)` (line 129): به‌ازای هر top term: `get_term_meta(thumbnail_id)` + در fallback `get_posts(tax_query)` → **۴۰ تا ۸۰ query**.
  - `toykindangel_cats_page_popular_terms(12)` (line 179): `get_terms(number=40)` سپس foreach با `get_term($term->parent)` per item → **۱۲ تا ۴۰ query**.
  - `toykindangel_cats_page_pop_media($term)` (line 223): `get_term_meta` per popular term → **۱۲ query**.
- **Evidence:** grep `cache_get|cache_set|transient` در `page-categories.php` → **۰ match**. ولی `inc/cat-data.php` (که درخت تعاملی same-page را می‌سازد) transient دارد.
- **Runtime Evidence (تخمین):** ~۱۲۰ تا ۲۰۰ query در هر بار بارگذاری `/categories/`، سرد و گرم.
- **Impact:** این صفحه معمولاً پر‌بازدید است (لینک از header/nav). با ۱۰۰ top category + ۵ child هر کدام → ~۲۰۰ query стабиль. ناهماهنگی طراحی: نسخهٔ تعاملی (cat-data.php) کش شده ولی نسخهٔ SSR همان صفحه نه.
- **Recommended Fix:** wrap هر ۵ helper در `toykindangel_cache_get('cats_page_*')` با همان TTL/invalidation `cache.php` (flush روی `created_term/edited_term/delete_term`).

### C3 — Footer category drawer (from_menu branch): N+1 روی هر صفحه

- **Severity:** CRITICAL (وقتی منوی `cats` اختصاص یافته باشد)
- **File:** `inc/cat-drawer.php`
- **Line:** `83-149` (`toykindangel_drawer_cats_from_menu`) → `105` (`toykindangel_term_img`)؛ caller: `footer.php:138` روی هر صفحه
- **Problem:**
  ```php
  // cat-drawer.php:99-106
  foreach ( $tka_items as $tka_item ) {
      if ( 0 === $tka_parent ) {
          if ( 'taxonomy' === $tka_item->type && $tka_tax === $tka_item->object ) {
              $tka_img = toykindangel_term_img( (int) $tka_item->object_id ); // ← N+1
          }
      }
  }
  ```
  `toykindangel_term_img()` (`cat-data.php:80`) = `get_term_meta(thumbnail_id)` + `wp_get_attachment_image_url(medium)`. nav-menu items term meta را prime نمی‌کنند.
- **Evidence:** `toykindangel_drawer_data()` (line 279) و `toykindangel_cats_drawer()` (line 379) **هیچ static/transient ندارند**. branch `from_menu` (line 282) زودتر return می‌کند و هرگز به branch `from_terms` (که از `cats_tree` transient می‌خواند) نمی‌رسد.
- **Runtime Evidence:** به‌ازای هر top-level taxonomy nav-menu item = ۲ query. منوی `cats` با ۲۰ top cat = ۴۰ query در هر صفحه‌ای از سایت (homepage، PDP، shop، blog، همه).
- **Impact:** این روی **هر صفحه** اجرا می‌شود نه فقط homepage. اگر ۵۰ صفحه/روز per user × ۴۰ query = ۲۰۰۰ query اضافه per user. با ۱۰۰ user همزمان → فشار قابل‌توجه.
- **Recommended Fix:** (الف) جمع‌آوری همهٔ term IDs و `_prime_term_caches($ids, false)` یکجا؛ (ج) wrap `toykindangel_drawer_data()` در static per-request + transient با flush روی term change.

### C4 — Archive-product subcategories: N+1 روی هر صفحهٔ دسته

- **Severity:** CRITICAL (در دسته‌های با زیردسته زیاد)
- **File:** `woocommerce/archive-product.php`
- **Line:** `93-118` (`get_terms` برای sub-terms + foreach با `get_term_meta(thumbnail_id)` + `wp_get_attachment_image_url`)
- **Problem:** الگوی دقیقاً مشابه C1 ولی روی هر آرشیو دسته. `get_terms` term meta را prime نمی‌کند.
- **Evidence:** بدون transient.
- **Runtime Evidence:** ۱ query get_terms + N × (get_term_meta + تا ۲ attachment query). دسته با ۳۰ زیردسته = ~۹۱ query.
- **Impact:** صفحهٔ دسته پر‌بازدید است. در catalog بزرگ با دسته‌های عمیق، هر بار خرید کاربر از یک دسته ~۱۰۰ query اضافه می‌زند.
- **Recommended Fix:** `_prime_term_caches($sub_term_ids, false)` بعد از get_terms.

---

## High Priority

### H1 — Admin Dashboard: `wc_get_orders('limit' => -1)` تمام order IDهای ماه در memory

- **Severity:** HIGH (admin-only، اما در فروشگاه پُر‌تراکنش بحرانی)
- **File:** `inc/admin-meta.php`
- **Line:** `313-339` (`toykindangel_order_total_sum`)؛ caller: `361-362` (دو بار: today + month)
- **Problem:**
  ```php
  $tka_ids = wc_get_orders( array(
      'status'     => $tka_statuses,
      'date_after' => gmdate( 'Y-m-d H:i:s', (int) $tka_from_ts ),
      'limit'      => -1,   // ❌ همهٔ orderهای ماه
      'return'     => 'ids',
  ) );
  $tka_in = implode( ',', $tka_ids );   // ❌ IN(... 5000 ids ...)
  $wpdb->get_var( "SELECT SUM(total_amount) FROM {$wpdb->prefix}wc_orders WHERE id IN ({$tka_in})" );
  ```
- **Evidence:** نویسنده در v0.19.0 از hydration همهٔ objects به IDs+aggregate SQL بهبود داده (comment line 354-358) — بهبود واقعی. اما `limit => -1` همچنان ریسک دارد.
- **Runtime Evidence:** فروشگاه با ۵٬۰۰۰ سفارش/ماه → آرایه PHP با ۵٬۰۰۰ int + SQL string با `IN(1,2,...,5000)` ~۴۰KB. `max_allowed_packet` پیش‌فرض MySQL ۴MB است، خطر مستقیم نیست، ولی query planner روی IN بزرگ کند است و memory PHP رشد می‌کند.
- **Impact:** dashboard widget کند در فروشگاه پُر‌تراکنش؛ admin که dashboard را باز می‌کند waits چند ثانیه.
- **Recommended Fix:** حذف `wc_get_orders` و انجام SUM مستقیم در SQL با WHERE روی `date_created` و `status` (هم HPOS هم CPT).

### H2 — Admin: low-stock count با cap مخفی ۲۰۰ — correctness bug

- **Severity:** HIGH (correctness، admin-only)
- **File:** `inc/admin-meta.php`
- **Line:** `377-398`
- **Problem:** `wc_get_products(['limit' => 200, 'return' => 'ids'])` فقط ۲۰۰ محصول manage-stock اول را بررسی می‌کند. اگر فروشگاه ۵٬۰۰۰ محصول manage-stock داشته باشد، ۴٬۸۰۰ محصول نادیده گرفته می‌شوند و شمارش low-stock **اشتباه** است.
- **Evidence:** نویسنده comment نوشته «Same counting semantics as the previous 200-object hydration» — یعنی cap از قبل هم وجود داشته، اما این یک correctness bug است نه optimization.
- **Impact:** dashboard عدد low-stock را اشتباه نشان می‌دهد. admin تصمیم خرید اشتباه می‌گیرد.
- **Recommended Fix:** `wc_get_products` با `limit => -1` برای گرفتن همهٔ IDs (products معمولاً کمتر از orders است) یا SQL مستقیم `SELECT COUNT(*) FROM postmeta WHERE meta_key='_stock' AND meta_value+0 <= threshold`.

### H3 — PDP: rail «محصولات مشابه» ۱۰× wc_get_product بدون transient

- **Severity:** HIGH-MEDIUM
- **File:** `woocommerce/single-product.php`
- **Line:** `219` (`wc_get_related_products($id, 10)` → IDs) + `541-547` (foreach با `wc_get_product` per ID)
- **Problem:** `wc_get_related_products` IDs را برمی‌گرداند (خوب). اما hydration ۱۰ محصول یکی‌یکی با `wc_get_product` در foreach انجام می‌شود، و هر `pcard_wrap` هم ۲ attachment-meta query می‌زند.
- **Evidence:** بدون transient روی related rail. WC خودش `wc_get_related_products` را transient-cached می‌کند (خوب) ولی hydration نه.
- **Runtime Evidence:** ~۳۰ query per PDP (۱۰ wc_get_product + ۲۰ attachment). با ۱۰۰ PDP بازدید/روز = ۳٬۰۰۰ query.
- **Impact:** PDP کندتر از لازم. در ۵٬۰۰۰ محصول با ترافیک، تجمع‌پذیر.
- **Recommended Fix:** `wc_get_products(['include' => $tka_related, 'limit' => 10])` به‌جای foreach + `wc_get_product` — batch hydration یک‌بار.

### H4 — SEO: تکرار Product JSON-LD با WC core روی PDP

- **Severity:** HIGH (SEO)
- **File:** `inc/seo-schema.php`
- **Line:** `199-279` (Product schema در `wp_head`)
- **Problem:** قالب Product JSON-LD را در `wp_head` چاپ می‌کند، اما `WC_Structured_Data` ووکامرس هم در `wp_footer` Product schema چاپ می‌کند (`woocommerce_structured_data` → `WC()->structured_data->output_structured_data`). قالب هوک WC را `remove_action` نکرده (grep `structured_data` در کل قالب = ۰ match).
- **Evidence:** وقتی SEO plugin فعال نباشد، Google دو Product schema با field-set متفاوت می‌بیند → Search Console warning «Duplicate Product».
- **Impact:** در نصب بدون SEO plugin (که برای storefront ایرانی رایج است)، schema duplication.
- **Recommended Fix:** `remove_action('wp_footer', [WC()->structured_data, 'output_structured_data'], 10)` وقتی `!toykindangel_seo_plugin_active()` و روی PDP.

### H5 — Product card: attachment meta per card (N+1 روی هر listing)

- **Severity:** MEDIUM-HIGH
- **File:** `inc/product-card.php`
- **Line:** `76` (`wp_get_attachment_image_url`) + `84` (`wp_get_attachment_metadata`)
- **Problem:** هر `toykindangel_pcard()` این دو را برای attachment ID تصویر محصول می‌زند. WP_Query اصلی post_meta را prime می‌کند ولی attachment‌ها پست‌های جدا هستند و meta cache آن‌ها primed نیست.
- **Evidence:** روی shop archive با ۱۲ محصول = ۲۴ attachment-meta query. روی wishlist با ۶۰ محصول = ۱۲۰.
- **Runtime Evidence:** معمولاً cache hit بعد از اولین load attachment در همان request، ولی در listing اولین بار هر attachment جدید query می‌زند.
- **Impact:** در صفحه‌های با کارت زیاد (wishlist، search، homepage rails) تجمع‌پذیر.
- **Recommended Fix:** جمع‌آوری همهٔ attachment IDs در listing و `_prime_post_caches([], $attachment_ids)` یکجا قبل از render.

---

## Medium Priority

### M1 — `cat-data.php` درخت دسته‌بندی: thundering herd روی flush cache

- **File:** `inc/cat-data.php`
- **Line:** `138-299` (nested 3-level tree builder)
- **Problem:** transient `tka_cache_cats_tree` (12h TTL) محتویات را کش می‌کند (خوب). ولی وقتی cache flush می‌شود (روی هر `edited_term`)، N request همزمان همه می‌خواهند rebuild کنند چون **lock نیست**. در worst-case (۴۰ top × ۱۰۰ child × ۱۰۰ grandchild) rebuild = هزاران query.
- **Evidence:** `toykindangel_cache_get` → اگر null، بلافاصله `cache_set` بعد از rebuild. بدون semaphore.
- **Impact:** وقتی admin یک term را ویرایش می‌کند، چند ثانیه cache سرد است و همهٔ requestهای همزمان rebuild می‌کنند.
- **Recommended Fix:** pattern "cache stampede lock" — اولین request rebuild می‌کند، بقیه stale یا short-TTL بازمی‌گردند.

### M2 — `festival.php`: ۵۰ `get_posts` در هر homepage

- **File:** `inc/festival.php`
- **Line:** `81-96` (`toykindangel_festival_ids`)
- **Problem:** وقتی festival فعال است، `get_posts(post_type=product, meta_key=tka_festival, numberposts=50)` در هر homepage اجرا می‌شود. بدون transient.
- **Evidence:** caller: `front-data.php:124-129` در `toykindangel_home_rail_products` که خود static-cached است (خوب — یعنی فقط یک‌بار per request) ولی بین-Request کش نمی‌شود.
- **Impact:** ۱ query اضافی per homepage. کم، ولی با `meta_key` filesort دارد.
- **Recommended Fix:** transient کوتاه (۱۵ دقیقه) flush روی save_post_product.

### M3 — `inc/front-data.php`: best-sellers با WP_Query + per-post wc_get_product

- **File:** `inc/front-data.php`
- **Line:** `158-175`
- **Problem:** `new WP_Query(meta_key=total_sales, posts_per_page=10)` سپس foreach با `wc_get_product($post->ID)` per post. می‌توانست `wc_get_products` با `orderby=>popularity` باشد.
- **Evidence:** نویسنده این را می‌داند (comment line 157) ولی برای parity با WC transient روی total_sales این الگو را نگه داشته.
- **Impact:** ۱۰ hydration اضافی per homepage (به‌خاطر static cache فقط یک‌بار per request). کم.
- **Recommended Fix:** `wc_get_products(['orderby' => 'popularity', 'limit' => 10])`.

### M4 — `inc/front-data.php`: usort برندها با get_term_meta در comparator

- **File:** `inc/front-data.php`
- **Line:** `392-408`
- **Problem:** `usort` comparator `get_term_meta` ×۲ در هر مقایسه می‌زند. برای ۱۲ brand ~۷۲ comparison = ~۱۴۴ get_term_meta call. ولی این داخل `toykindangel_wc_brands_uncached` است که transient-cached است (`home_brands`، 12h) — پس فقط روی cache miss اجرا می‌شود.
- **Evidence:** wrap شده در cache (خوب).
- **Impact:** کم — فقط cold cache.
- **Recommended Fix:** pre-fetch همهٔ `tka_brand_order` meta با یک query قبل از sort.

### M5 — `window.TKA_WP` ~۱۴-۱۶KB هم‌پوشان با SSR HTML

- **File:** `inc/front-data.php`
- **Line:** `470-538` (`toykindangel_front_data`) + `544-566` (emission)
- **Problem:** روی homepage با SSR فعال، همان داده‌های rail/stories/brands/hero/grid هم در HTML render می‌شوند هم در `window.TKA_WP` JSON. در storefront واقعی (که `tka_fallback_demo=false`)، app.js فقط `fallback` و `catUrl` را استفاده می‌کند، بقیه payload بدون مصرف‌کننده است.
- **Evidence:** app.js SSR guards (functions.php:144-148 comment) فقط container خالی را fill می‌کنند. در storefront واقعی container خالی نیست.
- **Runtime Evidence:** ~۱۴-۱۶KB JSON (۳۰ محصول × ~۴۰۰B + ۱۰ story + ۸ brand + ۸ banner). روی mobile = ~۳-۵KB gzipped. بزرگ نیست ولی اضافی.
- **Impact:** کم. bandwidth + parse time.
- **Recommended Fix:** وقتی `tka_fallback_demo=false`، payload minimal (`fallback`, `catUrl` فقط) emit کنید.

### M6 — `data.js` (۱۸۸ خط demo dataset) روی هر homepage load می‌شود

- **File:** `functions.php`
- **Line:** `152-166` (`$tka_needs_dataset` gating)
- **Problem:** وقتی `is_front_page()`، `data.js` (~۱۵KB) همیشه enqueue می‌شود حتی اگر `tka_fallback_demo=false`. این فقط fallback است.
- **Evidence:** functions.php:152 فقط صفحه را چک می‌کند، نه setting را.
- **Impact:** ~۳KB gzipped اضافی per homepage.
- **Recommended Fix:** `&& toykindangel_front_fallback_enabled()` به شرط اضافه کنید.

---

## Low Priority

### L1 — `seo-schema.php`: ItemList در `wp_head` ۲۴ WC_Product hydrate می‌کند

- **File:** `inc/seo-schema.php`
- **Line:** `300-337`
- **Problem:** ItemList schema برای shop/category archive در `wp_head` ساخته می‌شود و ۲۴ محصول را hydrate می‌کند قبل از body. WC request-cache دومی را cache-hit می‌کند ولی اولی TTFB را تأخیر می‌دهد.
- **Recommended Fix:** move به `wp_footer` (پس از render اصلی).

### L2 — `seo-schema.php`: `brand` field از `product_cat` اول استفاده می‌کند نه `product_brand`

- **File:** `inc/seo-schema.php`
- **Line:** `256-263`
- **Problem:** schema `Brand` را از FIRST `product_cat` می‌گیرد در حالی که قالب هم `product_brand` هم `tka_brand` را query می‌کند. نادرست از نظر schema semantics.
- **Recommended Fix:** استفاده از `product_brand`/`tka_brand`.

### L3 — `seo-schema.php`: SEO-plugin guard ناقص

- **File:** `inc/seo-schema.php`
- **Line:** `16-18`
- **Problem:** فقط Yoast/RankMath/AIOSEO را چک می‌کند. SEOPress، The SEO Framework، Slim SEO، Squirrly را نمی‌بیند.
- **Recommended Fix:** بررسی `class_exists` یا `is_plugin_active` برای لیست گسترده‌تر.

### L4 — `ajax-search.php`: throttle قبل از cache-check اجرا می‌شود

- **File:** `inc/ajax-search.php`
- **Line:** `283-295` (throttle در `283`، cache در `292`)
- **Problem:** کاربرهای CGNAT پشت یک IP حتی روی cache-hit throttle slot می‌سوزانند. cache-hit نباید throttle کند.
- **Recommended Fix:** cache-check اول، throttle فقط روی cache-miss.

### L5 — `header-bare.php` / `footer-bare.php`: dead code

- **File:** `header-bare.php`, `footer-bare.php`
- **Problem:** هیچ template ای `get_header('bare')` / `get_footer('bare')` صدا نمی‌زند (از v0.16.0 redesign). orphan.
- **Recommended Fix:** حذف (یا مستندسازی اگر برای آینده نگه داشته شده).

### L6 — `inc/blog.php`: `toykindangel_reading_time_text` per post در loops

- **File:** `inc/blog.php` + caller `ajax-search.php:205`
- **Problem:** محاسبه reading time per post. bounded (۳ در live search, ۱۲ در blog page). کم.
- **Recommended Fix:** transient per post یا post_meta cache.

### L7 — `wp_count_posts('shop_order')` در admin under HPOS

- **File:** `inc/admin-meta.php`
- **Line:** `364`
- **Problem:** تحت HPOS بدون sync، `wp_count_posts('shop_order')` صفر برمی‌گرداند چون orders در post table نیستند.
- **Impact:** admin-only، عدد processing-count اشتباه.
- **Recommended Fix:** `wc_get_orders(['status'=>'wc-processing','return'=>'ids','limit'=>PHP_INT_MAX])` count یا `OrderUtil::custom_orders_table_usage_is_enabled()` branch.

---

## Optimization Opportunities

> این‌ها bug نیستند؛ بهبودهای مهندسی معقول.

| # | Location | Opportunity | Estimated gain |
|---|----------|------------|---------------|
| O1 | `inc/cache.php` | افزودن stampede lock (semaphore یا "stale-while-revalidate") | جلوگیری از rebuild همزمان |
| O2 | `inc/front-data.php:138-146` | `wc_get_products(['include'=>$sale_ids])` به‌جای split | یک‌بار batch hydration |
| O3 | `inc/cat-data.php` | `_prime_term_caches` بعد از هر `get_terms` | حذف N+1 term-meta |
| O4 | `inc/product-card.php` | lazy `wp_get_attachment_metadata` (فقط اگر width/height واقعاً لازم است) | کاهش query per card |
| O5 | `woocommerce/single-product.php:286` + `seo-schema.php:215` | gallery attachment meta را یک‌بار fetch و share | حذف ~۸-۱۶ query تکراری per PDP |
| O6 | `inc/admin-meta.php:313` | SQL مستقیم SUM با WHERE date/status | حذف PHP array + IN clause |
| O7 | `inc/wc-hooks.php:87` | ordering پیش‌فرض `total_sales` → `date DESC` (رایج‌تر در WooCommerce core) | حذف filesort روی هر shop load |
| O8 | `functions.php:152` | dequeue `data.js`+`app.js` وقتی `tka_fallback_demo=false` | ~۱۸KB کم per homepage |
| O9 | `inc/front-data.php:544` | minimal `window.TKA_WP` وقتی fallback off | ~۱۴KB کم per homepage |
| O10 | `inc/ajax-search.php:291` | cache-check قبل از throttle | UX بهتر برای CGNAT |

---

## False Positives / Not Problems

> مواردی که در بررسی اولیه شبیه مشکل بودند ولی بعد از بررسی code-level مشخص شد مشکل واقعی نیستند.

| # | Pattern | Location | Why NOT a problem |
|---|---------|----------|-------------------|
| F1 | `wc_get_product(get_the_ID())` داخل loop | `archive-product.php:242` | WC_Product object cache (in-memory + persistent) دومی را cache-hit می‌کند. main query posts را قبلاً load کرده. استاندارد WC. |
| F2 | `wc_get_product` در search.php split loop (line 38) + render loop (line 87) | `search.php:38,87` | دومین call cache-hit است. split برای grouping لازم است. |
| F3 | `get_post_meta($product->get_id(), 'tka_bnpl', true)` per card | `product-card.php:73` | WP_Query main `update_post_meta_cache=true` (default) همهٔ post_meta را prime کرده. cache hit. |
| F4 | `get_the_terms(get_the_ID(), 'product_cat')` در live-search loop | `ajax-search.php:144` | WP_Query با `update_post_term_cache=true` (default) term relationships را prime کرده. cache hit. |
| F5 | `get_permalink` در loop‌ها | همه‌جا | WP post cache پر شده، cache hit. |
| F6 | `get_post($tka_post_id)` در search.php posts/other loops | `search.php:104,135` | main query posts را قبلاً load کرده، `get_post` از cache برمی‌گرداند. |
| F7 | Variable product `get_available_variations` | `single-product.php:404` | این WC core behavior است (via `woocommerce_single_product_summary` hook). قالب هیچ کار اضافه‌ای نمی‌کند. هزینه ذاتی WC برای variable products. |
| F8 | `get_variation_prices(true)` در pcard برای variable | `product-card.php:46` |WC این را cache می‌کند (`wc_get_product_variation_prices` transient). برای storefront با variable products لازم است. |
| F9 | `wp_count_posts('product')` در `front_unlocked` | `front-data.php:457` | فقط یک‌بار per request (cached در WP options). فقط برای gate check. |
| F10 | `set_transient` در throttle | `ajax-search.php:244` | short-TTL (15s)، overhead بسیار کم. |
| F11 | درخت دسته‌بندی `cats_tree` transient | `cat-data.php` | خود transient-cached است (۱۲h). فقط thundering-herd (M1) مشکل است نه خود query. |

---

## Page-by-Page Analysis

### Homepage (`front-page.php`)
- **Query اصلی rails:** `toykindangel_home_rail_products()` (static-cached per request) — ۳ rail (amazing/newest/best) × ۱۰ محصول. منبع واحد برای SSR + JSON. ✅
- **Queries:** sale_ids (1 wc_get_product_ids_on_sale یا 1 SQL price_diff) + 3 wc_get_products + 1 WP_Query best. ~۵ query برای rails.
- **Stories/brands:** transient-cached (`home_stories`, `home_brands`). ✅
- **Catgrid (C1):** ❌ UNBOUNDED get_terms + per-term meta، بدون transient. ~۶۰-۶۰۰ query.
- **Festival:** ۱ get_posts(50) بدون transient (M2).
- **JSON payload:** ~۱۴-۱۶KB `window.TKA_WP` + ~۱۵KB `data.js` — اضافی وقتی fallback off (M5, M6).
- **Cold queries:** ~۳۳۰ (با ۴۰ top cat). **Warm:** ~۲۲۰ (catgrid همچنان می‌زند).
- **Verdict:** rails طراحی‌شده‌اند بهینه؛ catgrid بحرانی است.

### Shop (`woocommerce/archive-product.php`)
- **Main query:** ۱۲/page، pagination استاندارد، ordering روی main query. ✅
- **Default sort:** `meta_key=total_sales` → filesort روی postmeta در هر shop load (WC-standard ولی سنگین).
- **Subcats (C4):** ❌ N+1 روی term_meta. ~۹۱ query با ۳۰ زیردسته.
- **Sidebar brands:** ۱ get_terms(number=30). bounded. ✅
- **Cold/Warm:** ~۱۳۰ / ~۱۰۰ (با ۳۰ subcat).
- **Verdict:** Shop **کل catalog را نمی‌خواند** (تصور غلط رد شد). فقط ۱۲ محصول hydrate می‌شود. اما subcat N+1 واقعی است.

### Category Archive
همان Shop + taxonomy filter روی main query. subcat N+1 (C4) اینجا هم فعال. term_description و breadcrumbs (cached) OK.

### Product (`woocommerce/single-product.php`)
- **Main product:** ۱ hydrate از main query. ✅
- **Gallery:** attachment meta fetch (O5 duplication با schema).
- **Related (H3):** ۱۰ wc_get_product + ۱۰ pcard. ~۳۰ query. بدون transient.
- **Variable (F7):** WC core `get_available_variations` — هزینه ذاتی، نه bug.
- **Reviews:** `comments_template` bounded ۵۰/page. ✅
- **Schema:** Product JSON-LD + WC core duplicate (H4).
- **Cold/Warm:** ~۷۵ / ~۵۰ (simple). ~۱۰۰ / ~۷۰ (variable V=20).
- **Verdict:** PDP منطقی است. related rail قابل بهبود.

### Search (`search.php`)
- **Main query:** وردپرس core search، pagination. ✅
- **Split loop:** wc_get_product per product (F2 false positive — cached). ✅
- **Cold/Warm:** بستگی به result count دارد.

### Categories (`page-categories.php`)
- **SSR grid (C2):** ❌ ۴ helper بدون transient. ~۱۲۰-۲۰۰ query.
- **Interactive tree:** `cat-data.php` transient-cached. ✅
- **Cold/Warm:** ~۲۰۰ / ~۲۰۰ (SSR grid هرگز cached).
- **Verdict:** بحرانی. ناهماهنگی: نسخهٔ تعاملی کش شده، SSR نه.

### AJAX Live Search (`inc/ajax-search.php`)
- **Throttle:** ۶۰/15s per-IP. ✅
- **Cache:** ۳-min per-term. ✅
- **Limit:** ۱-۸ products, ۳ posts. bounded. ✅
- **Auth:** public (مثل core search، بدون nonce). ✅
- **Verdict:** well-engineered. فقط L4 (throttle order).

### REST Search (`inc/rest-search.php`)
- **Same payload as AJAX** (single source). ✅
- **permission_callback:** `__return_true` — justified برای public read-only search.
- **Verdict:** clean.

### Admin (`inc/admin-meta.php`)
- **Dashboard widget:** H1 (wc_get_orders limit=-1) + H2 (low-stock cap 200) + L7 (wp_count_posts shop_order under HPOS).
- **Demo-import (`inc/demo-import.php`, 1860 lines):** frontend-safe. فقط `admin_post_tka_demo_wxr` (manage_options + nonce) و `admin_menu`. هیچ `init`/`wp_loaded`/`save_post`/cron. bounded توسط demo-data (~۳۵ محصول). ✅
- **Verdict:** admin-only مشکلات، ولی H1/H2 واقعی.

### Cron
- **Theme registers ZERO wp-cron events.** grep تمام قالب: ۰ match برای `wp_schedule_event`، `wp_schedule_single_event`، `register_activation_hook`. فقط ۳ `after_switch_theme` one-shot idempotent.
- **Verdict:** ✅ قالب عامل wp-cron نیست. اگر فشار wp-cron دیده می‌شود، از WooCommerce core یا WordPress core است، نه از این قالب.

---

## Query Map

| Location | Query Type | Estimated Count | Actual Count | Risk | Reason |
| -------- | ---------- | --------------: | -----------: | ---- | ------ |
| front-page.php:156 (catgrid) | get_terms + get_term_meta + wp_get_attachment_image_url | 1+3N | 1+3N | **High** | Unbounded + uncached |
| front-page.php rails | wc_get_products × 3 + WP_Query | ~5 | ~5 (static-cached) | Low | Single source per request |
| front-page.php stories/brands | get_terms + per-term meta | ~24 | ~0 warm (transient) | Low | 12h transient |
| page-categories.php SSR grid | get_terms × N + get_term_meta × N + get_posts fallback | ~120-200 | ~120-200 | **High** | No transient |
| archive-product.php subcats | get_terms + get_term_meta × N | 1+3N | 1+3N | **High** | Uncached |
| archive-product.php main loop | main query + wc_get_product per post | 1+12 | 1+0 (OC hit) | Low | Standard WC |
| cat-drawer.php from_menu | wp_get_nav_menu_items + term_img × N | 1+2N | 1+2N | **High** | Every page, uncached |
| single-product.php related | wc_get_product × 10 + pcard × 10 | ~30 | ~30 | Med | Bounded, no transient |
| single-product.php gallery | wp_get_attachment_image × ~5 | ~10 | ~10 | Low | Bounded |
| ajax-search.php | WP_Query × 2 + wc_get_product × 6 | ~16 | ~0 warm (3-min cache) | Low | Throttled + cached |
| admin-meta.php dashboard | wc_get_orders(limit=-1) × 2 + wc_get_products(200) | ~4 + memory | ~4 + memory | **Med** | limit=-1 IN clause |
| wishlist render | wc_get_product × ≤60 | ~60 | ~60 (OC hit warm) | Low-Med | Bounded at 60 |

---

## Product Hydration Map

| Location | Call | Estimated executions per page | IDs first? | Batched? | Notes |
|----------|------|------------------------------:|-----------|----------|-------|
| `archive-product.php:242` | `wc_get_product(get_the_ID())` | 12 | Yes (main query) | No (per-post) | Object cache hit after 1st; standard WC |
| `front-data.php:138` (amazing) | `wc_get_products(include, limit=10)` | 1 call → 10 objects | Yes | **Yes** ✅ | Batched |
| `front-data.php:149` (newest) | `wc_get_products(limit=10)` | 1 call → 10 objects | Yes | **Yes** ✅ | Batched |
| `front-data.php:158-174` (best) | `WP_Query` + `wc_get_product` per post | 10 hydrations | Yes (IDs) | **No** ❌ | Could be wc_get_products |
| `front-data.php:63-64` (map_products) | `wc_get_product` if not WC_Product | 0 (rails pass objects) | n/a | n/a | Defensive only |
| `single-product.php:219,541` (related) | `wc_get_related_products` + `wc_get_product` per ID | 10 hydrations | Yes (IDs) | **No** ❌ | Could be wc_get_products |
| `search.php:38,87` (split + render) | `wc_get_product` per product × 2 | 2N (cache hit 2nd) | Yes | No | Cached 2nd pass |
| `ajax-search.php:138` | `wc_get_product(get_the_ID())` | ≤8 | Yes | No | Bounded, cached |
| `wishlist.php:103` | `wc_get_product` per client ID | ≤60 | Yes (client) | No | Bounded at 60 |

**Total WC_Product hydrated per homepage:** ~30 (rails) + 0 catgrid = 30. ✅ معقول.
**Total per PDP:** 1 (main) + 10 (related) + V (variable variations via WC core) = ~11-50.

---

## N+1 Map

> فقط N+1های **واقعی** (uncached یا cache-miss).

| File | Line | Operation | N | Queries | Severity |
| ---- | ---: | --------- | -: | ------: | -------- |
| front-page.php | 156-176 | get_terms + get_term_meta + wp_get_attachment_image_url per top cat | unbounded | 1+3N | **CRITICAL** |
| page-categories.php | 349-374 | cats_page_children + card_media per top term | ≤40 | ~4-8N | **CRITICAL** |
| page-categories.php | 380-406 | popular_terms get_term + pop_media per term | ≤12 | ~2-3N | **HIGH** |
| archive-product.php | 93-118 | get_term_meta + wp_get_attachment_image_url per subcat | ≤30 | 1+3N | **CRITICAL** |
| cat-drawer.php | 99-106 | term_img per top nav-menu item | ≤20-60 | 2N | **CRITICAL** |
| single-product.php | 541-547 | wc_get_product + pcard per related ID | 10 | ~3N | **HIGH** |
| product-card.php | 76,84 | wp_get_attachment_image_url + wp_get_attachment_metadata per card | 12-60 | ~2N | **MED** |
| front-data.php | 158-174 | wc_get_product per best-seller post | 10 | ~1N | **LOW** (static-cached per request) |
| front-data.php | 392-408 | get_term_meta × 2 in usort comparator per comparison | ~12 brands → ~72 comparisons | ~144 | **LOW** (transient-cached) |
| admin-meta.php | 313-339 | wc_get_orders(limit=-1) + IN clause SQL | unbounded orders | 2 calls | **HIGH** (admin) |
| wishlist.php | 103-108 | wc_get_product per client ID | ≤60 | ~60 | **LOW** (bounded, OC warm) |

---

## Architecture Problems

> مشکلات ساختاری، جدا از optimization.

### A1 — ناهماهنگی cache: template partials از wrapper‌های cached عبور می‌کنند
قالب لایهٔ cache تمیز `inc/cache.php` دارد با invalidation رویدادمحور. اما template files (`front-page.php`، `page-categories.php`، `archive-product.php`) مستقیماً `get_terms`/`get_term_meta` می‌زنند بدون wrapper. این یعنی cache layer وجود دارد ولی **به‌طور یکپارچه اعمال نشده**. این الگوی «cache برای inc/ functions، نه برای templates» خطرناک است چون template‌ها پر‌بازدیدترین مسیرها هستند.

### A2 — ordering پیش‌فرض Shop بر `total_sales` meta
`inc/wc-hooks.php:87` default sort را `meta_key=total_sales, orderby=meta_value_num` می‌کند. این JOIN به postmeta + filesort روی هر shop/category بازدید است. WooCommerce core default `menu_order/date` است. این انتخاب تجاری قابل‌دفاع است (پرفروش‌ترین اول) ولی باید آگاهانه پذیرفته شود.

### A3 — دو منبع داده برای category tree
`inc/cat-data.php` (تعاملی، transient) و `page-categories.php` helpers (SSR، بدون transient) دو pipeline مجزا برای same data دارند. این دقیقاً نوع «Duplicate Data Pipeline» ای است که audit می‌خواهد پیدا کند.

### A4 — `window.TKA_WP` در storefront واقعی بدون مصرف‌کننده
وقتی `tka_fallback_demo=false` (default در storefront واقعی)، app.js فقط `fallback`/`catUrl` را می‌خواند. بقیه ۱۴-۱۶KB بدون مصرف‌کننده است. این data-over-shipping است.

### A5 — Admin queries بدون cache
`admin-meta.php` هیچ transient/static‌ای ندارد. dashboard widget در هر بازدید از نو محاسبه می‌کند. admin کم‌بازدید است ولی در فروشگاه پُر‌تراکنش کند می‌شود.

---

## Recommended Roadmap

### v0.20 — Must Fix

فقط مواردی که evidence قطعی دارند و ارزش فوری:

1. **C1 — Homepage catgrid:** اضافه‌کردن `'number' => 24` به get_terms + `_prime_term_caches` + wrap در `toykindangel_cache_get('home_catgrid')`. ~۱ ساعت کار. حذف ~۶۰۰ query در catalog بزرگ.
2. **C2 — page-categories SSR:** wrap پنج helper در transient با flush روی term change. ~۲ ساعت. حذف ~۲۰۰ query/page.
3. **C3 — cat-drawer from_menu:** `_prime_term_caches` برای term IDs + static per-request + transient. ~۱ ساعت. حذف ~۴۰ query در هر صفحه.
4. **C4 — archive-product subcats:** `_prime_term_caches` بعد از get_terms. ~۱۵ دقیقه. حذف ~۹۰ query.
5. **H2 — admin low-stock cap:** `limit => -1` یا SQL مستقیم. ~۳۰ دقیقه. اصلاح correctness.

### v0.21 — Should Optimize

6. **H1 — admin wc_get_orders:** SQL مستقیم SUM. ~۱ ساعت.
7. **H3 — PDP related rail:** `wc_get_products(include)`. ~۳۰ دقیقه.
8. **H4 — schema duplication:** `remove_action` WC structured_data روی PDP. ~۱۵ دقیقه.
9. **H5 — pcard attachment meta:** `_prime_post_caches` در listings. ~۱ ساعت.
10. **M1 — cat-data stampede lock:** ~۲ ساعت.
11. **M5/M6 — TKA_WP + data.js gating:** ~۳۰ دقیقه. ~۳۰KB کم per homepage.
12. **L4 — throttle order:** cache-check اول. ~۱۰ دقیقه.

### Later

13. **M2 — festival transient:** کم‌اثر.
14. **M3 — best-sellers wc_get_products:** کم‌اثر (static-cached).
15. **M4 — usort brand meta:** کم‌اثر (transient-cached).
16. **L1 — ItemList schema to wp_footer:** کم‌اثر.
17. **L2/L3 — schema brand field + plugin guard:** semantic correctness.
18. **L5 — dead header-bare/footer-bare:** cleanup.
19. **L7 — wp_count_posts shop_order HPOS:** admin-only.
20. **O7 — default sort total_sales → date:** تصمیم تجاری.

---

## Final Verdict

### **C — Needs Targeted Fixes Before Production**

**توضیح مبتنی بر evidence:**

قالب از نظر **معماری هسته** در وضعیت خوب است — به‌طور قابل‌توجهی بهتر از حد انتظار یک قالب AI-built. لایهٔ cache تمیز، single-source-of-truth برای homepage rails، standard WC main-query pattern روی Shop، throttle+cache روی live search، و **صفر cron ثبت‌شده** همگی نشانهٔ مهندسی آگاهانه هستند. v0.19.0 به‌طور صریح چندین مشکل v0.18.0 را حل کرده (duplicate pipelines، uncached search، hydration ۱۰۰‌تایی sale IDs).

اما **۴ N+1 واقعی در template partials پر‌بازدید** (homepage catgrid، page-categories SSR، archive-product subcats، footer drawer) در هر بار بارگذاری اجرا می‌شوند و با افزایش catalog **خطی رشد می‌کنند**. این‌ها توسط cache layer موجود قابل اصلاح هستند (الگوی `toykindangel_cache_get` + `_prime_term_caches`)، نه به refactor معماری نیاز دارند. با این حال، تا اصلاح نشوند، قالب در catalog ۵۰۰+ محصولی تحت ترافیک همزمان، MySQL را تحت فشار می‌گذارد.

علاوه بر این، **دو correctness bug در admin** (H2 low-stock cap، L7 HPOS wp_count_posts) و **یک SEO issue** (H4 duplicate schema) وجود دارد که باید قبل از production پاک شوند.

**چرا نه D؟** چون مشکلات الگوی تکراری یکسانی هستند (uncached term-meta N+1) که با یک الگوی fix واحد (`_prime_term_caches` + transient wrapper) همگی حل می‌شوند. این نیاز به refactor اساسی ندارد.

**چرا نه B؟** چون N+1های C1-C4 روی صفحات پر‌بازدید (homepage، shop، category، هر صفحه از طریق footer) هستند و در catalog متوسط (۵۰۰ محصول، ۴۰ دسته) قابل‌احساس می‌شوند. B مناسب وضعیتی است که فقط optimization opportunities داشته باشد بدون critical.

**شرط ارتقا به B (Production Ready with Minor Optimizations):** اجرای v0.20 roadmap (۵ fix، ~۵ ساعت کار) — بعد از آن قالب production-ready است.

**هیچ کدی در این audit تغییر نکرده است.** این گزارش فقط تحلیل است.
