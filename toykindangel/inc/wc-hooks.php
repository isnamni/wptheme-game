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
 * Keep 12 products per page (parity with the pre-standardization query).
 *
 * @param int $per_page Products per page.
 * @return int
 */
function toykindangel_loop_shop_per_page( $per_page ) {
        return 12;
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
 * @param WP_Query $q Main query (passed by WooCommerce).
 * @return void
 */
function toykindangel_wc_product_query( $q ) {
        if ( ! $q instanceof WP_Query || ! $q->is_main_query() ) {
                return;
        }
        if ( ! ( $q->is_post_type_archive( 'product' ) || $q->is_tax( array( 'product_cat', 'product_tag', 'tka_brand' ) ) ) ) {
                return;
        }

        $tka_sort = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

        switch ( $tka_sort ) {
                case 'new':
                        /* جدیدترین */
                        $q->set( 'meta_key', '' );
                        $q->set( 'orderby', 'date' );
                        $q->set( 'order', 'DESC' );
                        break;
                case 'cheap':
                        /* ارزان‌ترین */
                        $q->set( 'meta_key', '_price' );
                        $q->set( 'orderby', 'meta_value_num' );
                        $q->set( 'order', 'ASC' );
                        break;
                case 'exp':
                        /* گران‌ترین */
                        $q->set( 'meta_key', '_price' );
                        $q->set( 'orderby', 'meta_value_num' );
                        $q->set( 'order', 'DESC' );
                        break;
                case '':
                case 'best':
                        /* پرفروش‌ترین — the demo default order. */
                        $q->set( 'meta_key', 'total_sales' );
                        $q->set( 'orderby', 'meta_value_num' );
                        $q->set( 'order', 'DESC' );
                        break;
        }
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
 * demo layout (pdp__title / .rating / .pricebox / توضیحات). Only the
 * add-to-cart region (priority 30 — the real WC output with quantity
 * input) is rendered through woocommerce_single_product_summary.
 */
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_title', 5 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_sharing', 50 );

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
