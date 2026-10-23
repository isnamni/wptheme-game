<?php
/**
 * Standard WooCommerce compatibility layer — query, hooks & assets.
 *
 * Bridges the demo-style templates with the WooCommerce standards the
 * P0 code review requires:
 *
 * - FIX 1: demo archive ordering (best-selling default + the custom
 *   orderby keys «best/new/cheap/exp») is applied to the MAIN product
 *   query via `woocommerce_product_query`, so archive-product.php can
 *   iterate the standard main loop (single DB query, working pagination,
 *   plugin/filter compatibility).
 * - FIX 2: the standard single-product hooks stay fireable for plugins,
 *   while core callbacks that would duplicate the demo markup (WC
 *   gallery, WC title/rating/price/excerpt/meta, WC tabs/upsells/related,
 *   WC result count/ordering) are unhooked here.
 * - FIX 4: WooCommerce's default layout/small-screen stylesheets only
 *   fight the theme on the pages the theme renders itself (archive +
 *   PDP); cart/checkout/account keep the full WC styleset.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/* ------------------------------------------------------------------ */
/* FIX 1 — Archive ordering on the main query (demo parity)            */
/* ------------------------------------------------------------------ */

/**
 * Keep 16 products per page — parity with the reference site (4 columns ×
 * 4 rows on the 4-column archive grid, and the same result count the old
 * site reported: «نمایش 1–16 از N نتیجه»).
 *
 * v0.25.0: was 12.
 *
 * @param int $per_page Products per page.
 * @return int
 */
function toykindangel_loop_shop_per_page( $per_page ) {
        return 16;
}
add_filter( 'loop_shop_per_page', 'toykindangel_loop_shop_per_page', 20 );

/**
 * Apply the demo sort keys to the MAIN product query.
 *
 * Archive-product.php no longer runs its own WP_Query; the ordering the
 * old custom query performed is moved here so the main query itself is
 * ordered. Standard WooCommerce orderby values (popularity, price, date,
 * rating …) are left untouched so core widgets and filter plugins keep
 * working.
 *
 * v0.25.0 — باگ جدی v0.24.0 برطرف شد: کلوز meta_query با compare «=»
 * روی _stock_status به‌عنوان «فیلتر» عمل می‌کرد نه کلید مرتب‌سازی
 * (WHERE meta_value = 'outofstock')؛ نتیجه: فقط محصولات ناموجود نمایش
 * داده می‌شدند، محصولات موجود حذف و تعداد کل کمتر از واقعیت بود.
 *
 * راه‌حل درست (بدون حذف حتی یک محصول):
 *   1. هیچ meta_query اضافه نمی‌شود.
 *   2. جدول lookup خود ووکامرس (wc_product_meta_lookup — برای هر محصول
 *      یک ردیف) با LEFT JOIN وصل می‌شود؛ LEFT JOIN یعنی محصول‌هایی هم که
 *      ردیف lookup ندارند (واردات قدیمی) حذف نمی‌شوند.
 *   3. مرتب‌سازی نهایی از طریق posts_orderby:
 *      اول «ناموجود = 1» (آخر لیست)، بعد ستون مرتب‌سازی انتخابی
 *      (total_sales / post_date / min_price) به‌جای meta_key +
 *      meta_value_num که با INNER JOIN محصول‌های بدون متا را حذف می‌کرد.
 *
 * @param WP_Query $q Main query (passed by WooCommerce).
 * @return void
 */
function toykindangel_wc_product_query( $q ) {
        global $wpdb;

        if ( ! $q instanceof WP_Query || ! $q->is_main_query() ) {
                return;
        }
        if ( ! ( $q->is_post_type_archive( 'product' ) || $q->is_tax( array( 'product_cat', 'product_tag', 'tka_brand' ) ) ) ) {
                return;
        }

        /* کلید مرتب‌سازی → ستون SQL. meta_key ست نمی‌شود تا هیچ JOIN داخلی
         * (INNER) محصولی را از نتیجه حذف نکند. */
        $tka_sort = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        switch ( $tka_sort ) {
                case 'new':
                        $tka_column = $wpdb->posts . '.post_date';
                        $tka_dir    = 'DESC';
                        break;
                case 'cheap':
                        $tka_column = 'tka_lookup.min_price';
                        $tka_dir    = 'ASC';
                        break;
                case 'exp':
                        $tka_column = 'tka_lookup.min_price';
                        $tka_dir    = 'DESC';
                        break;
                case '':
                case 'best':
                        $tka_column = 'tka_lookup.total_sales';
                        $tka_dir    = 'DESC';
                        break;
                default:
                        /* کلیدهای خارجی/افزونه‌ای — فقط «ناموجود آخر» را پیش‌وند می‌کنیم. */
                        $tka_column = null;
                        $tka_dir    = '';
        }

        /*
         * LEFT JOIN جدول lookup فقط برای همین کوئری اصلی (بستن کلوزر روی
         * شیء کوئری تا روی سایر کوئری‌های همان درخواست — منو/ویجت —
         * اعمال نشود). اگر افزونه‌ای قبلاً همان جدول را وصل کرده باشد،
         * JOIN اضافه نمی‌شود و از alias استاندارد ووکامرس استفاده می‌کنیم.
         */
        $tka_alias = '';

        add_filter( 'posts_join', function ( $tka_join, $tka_qry ) use ( $q, &$tka_alias, $wpdb ) {
                if ( $tka_qry !== $q ) {
                        return $tka_join;
                }
                if ( false !== strpos( (string) $tka_join, 'wc_product_meta_lookup' ) ) {
                        $tka_alias = 'wc_product_meta_lookup';
                        return $tka_join;
                }
                $tka_alias = 'tka_lookup';
                return $tka_join . " LEFT JOIN {$wpdb->wc_product_meta_lookup} tka_lookup ON {$wpdb->posts}.ID = tka_lookup.product_id ";
        }, 10, 2 );

        add_filter( 'posts_orderby', function ( $tka_orderby, $tka_qry ) use ( $q, &$tka_alias, $tka_column, $tka_dir ) {
                if ( $tka_qry !== $q || '' === $tka_alias ) {
                        return $tka_orderby;
                }

                /* ناموجود = ۱ (انتهای لیست)، موجود/بدون وضعیت = ۰ (اول لیست). */
                $tka_stock_first = "CASE WHEN COALESCE({$tka_alias}.stock_status,'instock') = 'outofstock' THEN 1 ELSE 0 END ASC";

                if ( null === $tka_column ) {
                        /* اگر orderby خالی بود فقط کلوز موجودی برگردد (SQL نامعتبر نشود). */
                        if ( '' === trim( (string) $tka_orderby ) ) {
                                return $tka_stock_first;
                        }
                        return $tka_stock_first . ', ' . $tka_orderby;
                }
                return $tka_stock_first . ', ' . $tka_column . ' ' . $tka_dir . ', ' . $tka_orderby;
        }, 10, 2 );
}
add_action( 'woocommerce_product_query', 'toykindangel_wc_product_query' );

/* ------------------------------------------------------------------ */
/* FIX 2 — Unhook core UI that duplicates the demo design              */
/* ------------------------------------------------------------------ */

/*
 * Shop archive: the theme renders its own sort bar + result count
 * (tka-sortbar / tka-shophead). The hook itself stays fireable before
 * the sort bar so filter plugins can inject their UI.
 */
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );

/*
 * Shop archive: the theme renders its own paginate_links() inside
 * .tka-pagination-wrap (archive-product.php). Without this, WooCommerce's
 * core woocommerce_pagination() fires on woocommerce_after_shop_loop and a
 * second pagination bar shows up under the theme's one.
 */
remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );

/*
 * PDP gallery area: the theme renders the demo gallery (#galleryTrack)
 * and its own sale badge. Core gallery/sale-flash output is unhooked;
 * plugins may still hook into woocommerce_before_single_product_summary.
 */
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10 );
remove_action( 'woocommerce_before_single_product_summary', 'woocommerce_show_product_images', 20 );

/*
 * PDP summary: title/rating/price/excerpt/meta are part of the custom
 * demo layout (pdp__title / .rating / .pricebox / توضیحات). v0.25.0: the
 * add-to-cart region is gone from the pricebox too — the sticky bottom
 * bar is the single buy control (variable products keep their form via
 * toykindangel_summary_buy_block() below).
 */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

/*
 * v0.25.0 — تک‌دکمه‌ای شدن خرید در PDP (نیاز #۸ + بازخورد مشتری):
 * کور «woocommerce_template_single_add_to_cart» (اولویت ۳۰) در قالب
 * دمویی داخل pricebox یک دکمهٔ افزودن به سبد + کنترل تعداد چاپ می‌کرد؛
 * همان چیزها در نوار چسبان پایین صفحه هم هست → دو «افزودن به سبد
 * خرید» در یک صفحه. حالا به‌جای کور، یک دیسپچر جایگزین می‌نشینیم:
 *   - محصول متغیر: فرم انتخاب واریاسیون با دکمهٔ خودش سرِ جایش می‌ماند
 *     (بدون فرم، انتخاب واریانت ممکن نیست).
 *   - محصول ساده: هیچ‌چیز — تنها کنترل خرید، نوار چسبان پایین است.
 * خود هوک fireable می‌ماند تا افزونه‌ها خراب نشوند.
 */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_add_to_cart', 30 );
add_action( 'woocommerce_single_product_summary', 'toykindangel_summary_buy_block', 30 );

/**
 * Variable products keep their variation form; simple products render
 * nothing here (the sticky buy bar is the single add-to-cart control).
 *
 * @return void
 */
function toykindangel_summary_buy_block() {
        global $product;
        if ( $product instanceof WC_Product && $product->is_type( 'variable' ) ) {
                woocommerce_template_single_add_to_cart();
        }
}

/*
 * v0.25.0 — اصلاح ترجمهٔ معیوب بستهٔ فارسی در فرم دیدگاه: گزینهٔ خالی
 * دراپ‌داون امتیاز «Rate…» به «رای دهید;» ترجمه شده بود (با «;»!). متن
 * درست جایگزین می‌شود؛ با JS هم ستاره‌ای روی همین select سوار می‌شود.
 */
add_filter( 'gettext_woocommerce', 'toykindangel_fix_rate_option_text', 10, 3 );

/**
 * Replace the broken fa_IR translation of «Rate…».
 *
 * @param string $translated Translated text.
 * @param string $text       Original text.
 * @param string $domain     Text domain.
 * @return string
 */
function toykindangel_fix_rate_option_text( $translated, $text, $domain ) {
        if ( 'woocommerce' === $domain && in_array( $text, array( 'Rate&hellip;', 'Rate…', 'Rate...' ), true ) && false !== mb_strpos( $translated, ';' ) ) {
                return 'امتیاز خود را انتخاب کنید';
        }
        return $translated;
}

/*
 * PDP footer: specs/description/reviews and the related rail are rendered
 * by the template itself; core tabs/upsells/related would duplicate them.
 */
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_upsell_display', 15 );
remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );

/**
 * Hide WooCommerce's stock paragraph on the PDP.
 *
 * The real add-to-cart form echoes wc_get_stock_html(), which renders an
 * empty <p class="stock"> for in-stock products and would duplicate the
 * seller-box availability row / the low-stock notice. The template owns
 * those states; everything else (cart etc.) keeps the core output.
 *
 * @param string $html Stock HTML.
 * @return string
 */
function toykindangel_wc_stock_html( $html ) {
        if ( is_singular( 'product' ) ) {
                return '';
        }
        return $html;
}
add_filter( 'woocommerce_get_stock_html', 'toykindangel_wc_stock_html' );

/* ------------------------------------------------------------------ */
/* FIX 4 — Stylesheets, pagination                                     */
/* ------------------------------------------------------------------ */

/**
 * Drop WooCommerce's default layout/small-screen CSS on custom pages.
 *
 * The product archive and the PDP are 100% theme-rendered markup
 * (pcard grid / demo gallery) — WooCommerce's grid floats and
 * small-screen table tweaks have no matching selectors there and only
 * risk fighting the demo design. Cart/checkout/account pages use real
 * WooCommerce markup and keep every stylesheet.
 *
 * woocommerce-general is kept everywhere (review star font + misc).
 *
 * @param array $styles Enqueued WooCommerce stylesheets.
 * @return array
 */
function toykindangel_wc_enqueue_styles( $styles ) {
        if ( ! is_array( $styles ) ) {
                return $styles;
        }

        $tka_custom_markup = is_post_type_archive( 'product' ) || is_singular( 'product' )
                || ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() );

        if ( $tka_custom_markup ) {
                unset( $styles['woocommerce-layout'], $styles['woocommerce-smallscreen'] );
        }

        return $styles;
}
add_filter( 'woocommerce_enqueue_styles', 'toykindangel_wc_enqueue_styles' );

/**
 * Match WooCommerce pagination strings to the theme pagination.
 *
 * The archive renders its own paginate_links() inside
 * .tka-pagination-wrap; whenever WooCommerce's own pagination is used
 * (plugins, shortcodes) it gets the same «قبلی / بعدی» strings.
 *
 * @param array $args Pagination arguments.
 * @return array
 */
function toykindangel_wc_pagination_args( $args ) {
        $args['prev_text'] = '« ' . __( 'قبلی', 'toykindangel' );
        $args['next_text'] = __( 'بعدی', 'toykindangel' ) . ' »';
        return $args;
}
add_filter( 'woocommerce_pagination_args', 'toykindangel_wc_pagination_args' );

/**
 * TKA fix: the custom cart-empty template already shows a styled empty
 * state; drop WC's duplicate default "cart is empty" info message.
 */
remove_action( 'woocommerce_cart_is_empty', 'wc_empty_cart_message', 10 );
