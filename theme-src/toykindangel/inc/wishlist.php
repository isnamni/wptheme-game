<?php
/**
 * Wishlist (علاقه‌مندی‌ها) — page template + AJAX rendering + page
 * bootstrap. Product IDs live in localStorage (`tka_favs`, managed by
 * main.js); this module turns them into real WooCommerce product cards
 * server-side.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Wishlist page URL (empty when the page does not exist yet).
 *
 * @return string
 */
function toykindangel_wishlist_url() {
	static $tka_local = null;
	if ( null !== $tka_local ) {
		return $tka_local;
	}

	$tka_id = (int) get_option( 'tka_wishlist_page_id' );
	if ( $tka_id && 'publish' === get_post_status( $tka_id ) ) {
		$tka_local = (string) get_permalink( $tka_id );
		return $tka_local;
	}

        /* Fallback: any published page using the wishlist template. */
        $tka_pages = get_pages(
                array(
                        'meta_key'   => '_wp_page_template', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                        'meta_value' => 'page-wishlist.php', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
                        'number'     => 1,
                )
        );
        if ( $tka_pages ) {
                update_option( 'tka_wishlist_page_id', (int) $tka_pages[0]->ID );
                $tka_local = (string) get_permalink( $tka_pages[0]->ID );
                return $tka_local;
        }

        $tka_local = '';
        return $tka_local;
}

/**
 * Card for the wishlist grid: pcard + remove button + add-to-cart.
 *
 * @param WC_Product $product Product.
 * @return string
 */
function toykindangel_wish_card( $product ) {
        $tka_html  = '<div class="pcard-wrap tka-wish-item" data-wish-id="' . esc_attr( (string) $product->get_id() ) . '">';
        $tka_html .= toykindangel_pcard( $product );

        if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
                $tka_html .= '<a href="' . esc_url( $product->add_to_cart_url() ) . '"'
                        . ' data-quantity="1"'
                        . ' data-product_id="' . esc_attr( (string) $product->get_id() ) . '"'
                        . ' data-product_sku="' . esc_attr( $product->get_sku() ) . '"'
                        . ' rel="nofollow"'
                        . ' class="pcard__atc add_to_cart_button ajax_add_to_cart"'
                        . ' aria-label="' . esc_attr__( 'افزودن به سبد خرید', 'toykindangel' ) . '">'
                        . '<svg class="ic" aria-hidden="true"><use href="#i-cart"></use></svg>'
                        . '</a>';
        } elseif ( $product->is_type( 'variable' ) ) {
                /* TKA fix (17): variable products link to the PDP to pick options. */
                $tka_html .= '<a href="' . esc_url( $product->get_permalink() ) . '"'
                        . ' class="pcard__atc pcard__atc--choose"'
                        . ' aria-label="' . esc_attr__( 'انتخاب گزینه‌ها', 'toykindangel' ) . '">'
                        . esc_html__( 'انتخاب گزینه‌ها', 'toykindangel' )
                        . '</a>';
        }

        $tka_html .= '<button type="button" class="tka-wish-remove" data-wish-id="' . esc_attr( (string) $product->get_id() ) . '"'
                . ' aria-label="' . esc_attr__( 'حذف از علاقه‌مندی‌ها', 'toykindangel' ) . '">'
                . '<svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" aria-hidden="true"><path d="M6 6l12 12M18 6L6 18"/></svg>'
                . '</button>';
        $tka_html .= '</div>';

        return $tka_html;
}

/**
 * AJAX endpoint: render wishlist cards for the given product IDs.
 * Public read-only render — no privileged action, IDs are sanitized ints.
 *
 * v0.20.1 audit fix H3 — this is the heaviest public endpoint on the site
 * (up to 60 full WC_Product hydrations per call, ~163-500 queries measured).
 * Unlike the lighter live-search endpoint, it had no throttle and no cache.
 * Both are now applied, reusing the helpers already in the codebase:
 *   - toykindangel_ls_throttled() (inc/ajax-search.php) — same 60/15s per-IP
 *     sliding window as live-search. Shared counter means a flood on one
 *     endpoint also protects the other.
 *   - toykindangel_cache_get/set (inc/cache.php) — short-TTL (3 min) cache
 *     keyed on a hash of the sorted id list, so identical requests from
 *     different visitors share the rendered HTML.
 * The id ceiling is lowered from 60 to 24 (a wishlist grid never shows more
 * than ~24 cards in practice; the previous 60 only widened the abuse surface).
 */
function toykindangel_wishlist_render() {
        $tka_ids = isset( $_POST['ids'] ) ? array_map( 'absint', (array) wp_unslash( $_POST['ids'] ) ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- public read-only endpoint
        $tka_ids = array_filter( array_unique( array_slice( $tka_ids, 0, 24 ) ) );

        header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );

        if ( empty( $tka_ids ) || ! function_exists( 'wc_get_product' ) ) {
                wp_send_json_error( array( 'html' => '' ) );
        }

        /* Throttle: same per-IP window as live-search. */
        if ( function_exists( 'toykindangel_ls_throttled' ) && toykindangel_ls_throttled() ) {
                wp_send_json_error(
                        array( 'message' => __( 'درخواست‌های بیش از حد مجاز؛ لطفاً کمی بعد دوباره تلاش کنید.', 'toykindangel' ) )
                );
        }

        /* Cache: identical id list → identical HTML, shared across visitors. */
        $tka_cache_key = 'wish_' . md5( implode( ',', $tka_ids ) );
        $tka_cached    = function_exists( 'toykindangel_cache_get' ) ? toykindangel_cache_get( $tka_cache_key ) : null;
        if ( null !== $tka_cached ) {
                wp_send_json_success( array( 'html' => $tka_cached ) );
        }

        $tka_html = '';
        foreach ( $tka_ids as $tka_id ) {
                $tka_product = wc_get_product( $tka_id );
                if ( $tka_product && 'publish' === $tka_product->get_status() ) {
                        $tka_html .= toykindangel_wish_card( $tka_product );
                }
        }

        if ( '' === $tka_html ) {
                wp_send_json_error( array( 'html' => '' ) );
        }

        if ( function_exists( 'toykindangel_cache_set' ) ) {
                toykindangel_cache_set( $tka_cache_key, $tka_html, 3 * MINUTE_IN_SECONDS );
        }

        wp_send_json_success( array( 'html' => $tka_html ) );
}
add_action( 'wp_ajax_tka_wishlist_render', 'toykindangel_wishlist_render' );
add_action( 'wp_ajax_nopriv_tka_wishlist_render', 'toykindangel_wishlist_render' );

/**
 * Create the wishlist page once (admin requests only, idempotent).
 */
function toykindangel_wishlist_ensure_page() {
        if ( toykindangel_wishlist_url() ) {
                return;
        }
        $tka_existing = get_page_by_path( 'wishlist', OBJECT, 'page' );
        if ( $tka_existing ) {
                update_option( 'tka_wishlist_page_id', (int) $tka_existing->ID );
                set_post_type( $tka_existing->ID, 'page' );
                return;
        }
        $tka_pid = wp_insert_post(
                array(
                        'post_title'   => __( 'علاقه‌مندی‌ها', 'toykindangel' ),
                        'post_name'    => 'wishlist',
                        'post_status'  => 'publish',
                        'post_type'    => 'page',
                        'post_content' => '',
                )
        );
        if ( $tka_pid && ! is_wp_error( $tka_pid ) ) {
                update_option( 'tka_wishlist_page_id', (int) $tka_pid );
        }
}
add_action( 'admin_init', 'toykindangel_wishlist_ensure_page' );
