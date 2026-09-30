<?php
/**
 * Front-page dynamic data (Step 3).
 *
 * Builds the window.TKA_WP dataset consumed by main.js which swaps it into
 * the untouched demo dataset (window.TKA) before app.js renders. Every
 * section only overrides when real WordPress data exists; otherwise the
 * demo sample keeps showing (graceful fallback) unless the demo fallback
 * is disabled in the Customizer.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Register a lightweight brand taxonomy (used by the "برندها" rail).
 * Only when WooCommerce is available (needs the product post type).
 */
function toykindangel_register_brand_tax() {
        // WooCommerce 9.4+ ships a native product_brand taxonomy — prefer it
        // (its 'brand' rewrite slug also collides with ours).
        if ( taxonomy_exists( 'product_brand' ) ) {
                return;
        }
        if ( ! function_exists( 'wc_get_page_id' ) || taxonomy_exists( 'tka_brand' ) ) {
                return;
        }

        register_taxonomy(
                'tka_brand',
                array( 'product' ),
                array(
                        'labels'            => array(
                                'name'          => __( 'برندها', 'toykindangel' ),
                                'singular_name' => __( 'برند', 'toykindangel' ),
                                'menu_name'     => __( 'برندها', 'toykindangel' ),
                                'add_new_item'  => __( 'افزودن برند جدید', 'toykindangel' ),
                        ),
                        'hierarchical'      => false,
                        'public'            => true,
                        'show_admin_column' => true,
                        'show_in_rest'      => true,
                        'rewrite'           => array( 'slug' => 'brand' ),
                )
        );
}
add_action( 'init', 'toykindangel_register_brand_tax' );

/**
 * Map WC_Product objects to the demo pcard field contract:
 * { name, price, old, off, bnpl, img, url }.
 *
 * @param WC_Product[] $products Products.
 * @return array[]
 */
function toykindangel_map_products( $products ) {
        $out  = array();
        $bnpl = wp_validate_boolean( get_theme_mod( 'tka_show_bnpl', true ) );

        foreach ( $products as $p ) {
                $product = ( $p instanceof WC_Product ) ? $p : wc_get_product( $p );
                if ( ! $product instanceof WC_Product ) {
                        continue;
                }

                $img_id  = $product->get_image_id();
                $img     = $img_id ? wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' ) : '';
                $price   = (float) $product->get_price();
                $regular = (float) $product->get_regular_price();
                $off     = ( $regular > 0 && $price > 0 && $price < $regular ) ? (int) round( ( 1 - $price / $regular ) * 100 ) : 0;

                $out[] = array(
                        'name'  => $product->get_name(),
                        'price' => $price > 0 ? (int) $price : 0,
                        'old'   => ( $off && $regular > 0 ) ? (int) $regular : 0,
                        'off'   => $off,
                        'bnpl'  => $bnpl && $price > 0,
                        'img'   => $img,
                        'url'   => get_permalink( $product->get_id() ),
                );
        }

        return $out;
}

/**
 * Single source of truth for the three homepage product rails
 * (amazing offers / newest / best sellers).
 *
 * v0.19.0: both renderers consume this — the SSR markup
 * (inc/front-ssr.php::toykindangel_ssr_products()) and the window.TKA_WP
 * dataset (toykindangel_wc_rails()). Previously each pipeline ran its own
 * WooCommerce queries, so every homepage view paid the full rail cost
 * twice. Static-cached per request: the rails query exactly once.
 *
 * @return array<string, WC_Product[]> amazing/newest/best arrays (may be empty).
 */
function toykindangel_home_rail_products() {
        static $tka_cache = null;
        if ( null !== $tka_cache ) {
                return $tka_cache;
        }

        $tka_rails = array(
                'amazing' => array(),
                'newest'  => array(),
                'best'    => array(),
        );

        if ( ! function_exists( 'wc_get_products' ) ) {
                return $tka_rails;
        }

        /* Amazing offers → products on sale (same price-diff fallback as the JS dataset). */
        $tka_sale_ids = function_exists( 'wc_get_product_ids_on_sale' ) ? wc_get_product_ids_on_sale() : array();

        /* TKA (18-e): «جشنواره» flagged products join the amazing-offer rail
         * even when they carry no discount — the ribbon must always have a
         * card to sit on. Merged locally (not into the WC transient) so the
         * rail is correct immediately after the Customizer toggle flips. */
        if ( function_exists( 'toykindangel_festival_active' ) && function_exists( 'toykindangel_festival_ids' ) && toykindangel_festival_active() ) {
                $tka_festival_ids = toykindangel_festival_ids();
                if ( ! empty( $tka_festival_ids ) ) {
                        $tka_sale_ids = array_unique( array_merge( array_map( 'intval', (array) $tka_sale_ids ), $tka_festival_ids ) );
                }
        }

        if ( empty( $tka_sale_ids ) ) {
                /* v0.19.0 — the old fallback hydrated 100 full WC_Product
                 * objects just to read two price fields per product; a lean
                 * meta_key-scoped SQL returns the same IDs directly. */
                $tka_sale_ids = toykindangel_price_diff_sale_ids( 10 );
        }
        if ( ! empty( $tka_sale_ids ) ) {
                $tka_rails['amazing'] = wc_get_products(
                        array(
                                'include' => $tka_sale_ids,
                                'limit'   => 10,
                                'orderby' => 'date',
                                'order'   => 'DESC',
                        )
                );
        }

        /* Newest products. */
        $tka_rails['newest'] = wc_get_products(
                array(
                        'limit'   => 10,
                        'orderby' => 'date',
                        'order'   => 'DESC',
                )
        );

        /* Best sellers by total_sales meta. */
        $tka_best_query = new WP_Query(
                array(
                        'post_type'      => 'product',
                        'posts_per_page' => 10,
                        'meta_key'       => 'total_sales', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                        'orderby'        => 'meta_value_num', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value_num
                        'order'          => 'DESC',
                        'no_found_rows'  => true,
                        'post_status'    => 'publish',
                )
        );
        foreach ( $tka_best_query->posts as $tka_post ) {
                $tka_product = wc_get_product( $tka_post->ID );
                if ( $tka_product ) {
                        $tka_rails['best'][] = $tka_product;
                }
        }
        wp_reset_postdata();

        $tka_cache = $tka_rails;
        return $tka_cache;
}

/**
 * Cheap ID-only scan for products whose active price is below the regular
 * price (imports that set _price < _regular_price without filling
 * _sale_price — invisible to wc_get_product_ids_on_sale()).
 *
 * Same matching semantics as the previous object-hydration loop
 * (simple/external products only: variable parents carry an empty
 * _regular_price and were skipped there too), but one meta_key-scoped
 * SQL instead of 100 hydrated objects. Transient-cached and flushed by
 * inc/cache.php whenever WooCommerce rebuilds its own product caches.
 *
 * @param int $limit Max IDs.
 * @return int[]
 */
function toykindangel_price_diff_sale_ids( $limit = 10 ) {
        global $wpdb;

        $tka_ids = toykindangel_cache_get( 'price_diff_ids' );
        if ( null === $tka_ids ) {
                $tka_ids = $wpdb->get_col(
                        "SELECT DISTINCT price_meta.post_id
                         FROM {$wpdb->postmeta} AS price_meta
                         INNER JOIN {$wpdb->postmeta} AS regular_meta
                                 ON regular_meta.post_id = price_meta.post_id
                                 AND regular_meta.meta_key = '_regular_price'
                         INNER JOIN {$wpdb->posts} AS posts
                                 ON posts.ID = price_meta.post_id
                         WHERE price_meta.meta_key = '_price'
                                AND posts.post_type = 'product'
                                AND posts.post_status = 'publish'
                                AND price_meta.meta_value + 0 > 0
                                AND regular_meta.meta_value + 0 > 0
                                AND price_meta.meta_value + 0 < regular_meta.meta_value + 0"
                );
                $tka_ids = array_map( 'intval', (array) $tka_ids );
                toykindangel_cache_set( 'price_diff_ids', $tka_ids, 12 * HOUR_IN_SECONDS );
        }

        return array_slice( array_values( (array) $tka_ids ), 0, (int) $limit );
}

/**
 * Product rails: amazing (on-sale) / newest / best-selling.
 *
 * v0.19.0: a thin mapper over toykindangel_home_rail_products() — the
 * queries themselves live in the shared provider so the SSR renderer and
 * this dataset builder never duplicate them again.
 *
 * @return array|null
 */
function toykindangel_wc_rails() {
        if ( ! function_exists( 'wc_get_products' ) ) {
                return null;
        }

        $tka_products = toykindangel_home_rail_products();

        $rails = array();
        if ( ! empty( $tka_products['amazing'] ) ) {
                $rails['amazing'] = toykindangel_map_products( $tka_products['amazing'] );
        }
        $rails['newest'] = toykindangel_map_products( (array) $tka_products['newest'] );
        if ( ! empty( $tka_products['best'] ) ) {
                $rails['best'] = toykindangel_map_products( $tka_products['best'] );
        }

        if ( empty( $rails['newest'] ) ) {
                return empty( $rails ) ? null : $rails;
        }

        return $rails;
}

/**
 * First-level product categories for the stories row.
 *
 * v0.19.0: cached (static per request + transient across requests, flushed
 * by inc/cache.php on product_cat/product_brand term changes) — the SSR
 * renderer and the TKA_WP dataset used to run this two/three times per
 * homepage view.
 *
 * @return array|null
 */
function toykindangel_wc_stories() {
	static $tka_local = null;
	if ( null !== $tka_local ) {
		return $tka_local;
	}

	$tka_local = toykindangel_cache_get( 'home_stories' );
	if ( null === $tka_local ) {
		$tka_local = toykindangel_wc_stories_uncached();
		if ( null !== $tka_local ) {
			toykindangel_cache_set( 'home_stories', $tka_local, 12 * HOUR_IN_SECONDS );
		}
	}

	return $tka_local;
}

/**
 * Uncached stories builder (see toykindangel_wc_stories()).
 *
 * @return array|null
 */
function toykindangel_wc_stories_uncached() {
        if ( ! taxonomy_exists( 'product_cat' ) ) {
                return null;
        }

        // Preferred: story-flagged terms in their seeded demo order.
        $terms = get_terms(
                array(
                        'taxonomy'   => 'product_cat',
                        'parent'     => 0,
                        'hide_empty' => false,
                        'number'     => 10,
                        'meta_key'   => 'tka_story_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- indexed theme meta; cached story ranks
                        'orderby'    => 'meta_value_num',
                        'order'      => 'ASC',
                        'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- theme meta filtering; cached in transients
                                array(
                                        'key'   => 'tka_is_story',
                                        'value' => '1',
                                ),
                        ),
                )
        );

        if ( is_wp_error( $terms ) || empty( $terms ) ) {
                // Fallback: first top-level terms, name-ordered (Uncategorized skipped below).
                $terms = get_terms(
                        array(
                                'taxonomy'   => 'product_cat',
                                'parent'     => 0,
                                'hide_empty' => false,
                                'number'     => 11,
                        )
                );
        }
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
                return null;
        }

        $out = array();
        foreach ( $terms as $term ) {
                if ( 'uncategorized' === $term->slug ) {
                        continue;
                }
                $thumb_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
                $out[]    = array(
                        'name' => $term->name,
                        'img'  => $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'full' ) : '',
                        'href' => get_term_link( $term ),
                );
        }

        return $out ? $out : null;
}

/**
 * Brands from the native product_brand taxonomy (fallback tka_brand).
 *
 * v0.19.0: cached (static per request + transient across requests, flushed
 * by inc/cache.php on term changes) — same rationale as the stories cache.
 *
 * @return array|null
 */
function toykindangel_wc_brands() {
	static $tka_local = null;
	if ( null !== $tka_local ) {
		return $tka_local;
	}

	$tka_local = toykindangel_cache_get( 'home_brands' );
	if ( null === $tka_local ) {
		$tka_local = toykindangel_wc_brands_uncached();
		if ( null !== $tka_local ) {
			toykindangel_cache_set( 'home_brands', $tka_local, 12 * HOUR_IN_SECONDS );
		}
	}

	return $tka_local;
}

/**
 * Uncached brands builder (see toykindangel_wc_brands()).
 *
 * @return array|null
 */
function toykindangel_wc_brands_uncached() {
        $tka_brand_tax = taxonomy_exists( 'product_brand' ) ? 'product_brand' : 'tka_brand';
        if ( ! taxonomy_exists( $tka_brand_tax ) ) {
                return null;
        }

        /* tka_brand_order is optional: brands without it must still show
         * (sorted last, otherwise by name) — v0.7.0 robustness fix. */
        $terms = get_terms(
                array(
                        'taxonomy'   => $tka_brand_tax,
                        'hide_empty' => false,
                        'number'     => 12,
                        'orderby'    => 'name',
                        'order'      => 'ASC',
                )
        );
        if ( is_wp_error( $terms ) || empty( $terms ) ) {
                return null;
        }

        usort(
                $terms,
                static function ( $tka_a, $tka_b ) {
                        $tka_oa = (int) get_term_meta( $tka_a->term_id, 'tka_brand_order', true );
                        $tka_ob = (int) get_term_meta( $tka_b->term_id, 'tka_brand_order', true );
                        if ( $tka_oa && $tka_ob && $tka_oa !== $tka_ob ) {
                                return $tka_oa <=> $tka_ob;
                        }
                        if ( $tka_oa && ! $tka_ob ) {
                                return -1;
                        }
                        if ( ! $tka_oa && $tka_ob ) {
                                return 1;
                        }
                        return strcasecmp( $tka_a->name, $tka_b->name );
                }
        );
        $terms = array_slice( $terms, 0, 8 );

        $out = array();
        foreach ( $terms as $term ) {
                $img_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
                $out[]  = array(
                        'name' => $term->name,
                        'img'  => $img_id ? wp_get_attachment_image_url( $img_id, 'full' ) : '',
                        'href' => get_term_link( $term ),
                );
        }

        return $out;
}

/**
 * Front-page dataset default: demo fallback is OFF by default (v0.15.0).
 * A standard WordPress theme never shows sample data on a real store —
 * the Customizer toggle can re-enable it for previews.
 *
 * @return bool
 */
function toykindangel_front_fallback_enabled() {
        return wp_validate_boolean( get_theme_mod( 'tka_fallback_demo', false ) );
}

/**
 * Whether the homepage sections are allowed to render.
 *
 * Standard WordPress theme behaviour (v0.15.0): until the demo is imported
 * the homepage shows nothing but a setup card — no sample products, no
 * banners, no JS fallback.
 *
 * Unlocks when any of:
 *  - the one-click demo import has run (tka_demo_imported option),
 *  - the store has its own published products (self-built or WXR import),
 *  - the Customizer toggle "tka_front_without_demo" is on.
 *
 * @return bool
 */
function toykindangel_front_unlocked() {
        if ( get_option( 'tka_demo_imported' ) ) {
                return true;
        }
        if ( wp_validate_boolean( get_theme_mod( 'tka_front_without_demo', false ) ) ) {
                return true;
        }
        if ( post_type_exists( 'product' ) ) {
                $tka_counts = wp_count_posts( 'product' );
                if ( $tka_counts && (int) $tka_counts->publish > 0 ) {
                        return true;
                }
        }
        return false;
}

/**
 * Build the full front-page dataset.
 *
 * @return array
 */
function toykindangel_front_data() {
        $data = array(
                'fallback' => toykindangel_front_fallback_enabled(),
                'catUrl'   => toykindangel_categories_url(),
                'strip'    => null,
                'hero'     => null,
                'grid'     => null,
                'stories'  => null,
                'brands'   => null,
                'products' => null,
        );

        // Top strip banner.
        $tka_strip_img = get_theme_mod( 'tka_strip_img' );
        if ( $tka_strip_img ) {
                $data['strip'] = array(
                        'img'  => $tka_strip_img,
                        'href' => get_theme_mod( 'tka_strip_link', home_url( '/' ) ),
                );
        }

        // Hero slider slides (up to 4).
        $tka_hero = array();
        for ( $i = 1; $i <= 4; $i++ ) {
                $tka_img = get_theme_mod( "tka_hero_{$i}_img" );
                if ( $tka_img ) {
                        $tka_hero[] = array(
                                'img'  => $tka_img,
                                'href' => get_theme_mod( "tka_hero_{$i}_link", '#' ),
                        );
                }
        }
        if ( ! empty( $tka_hero ) ) {
                $data['hero'] = $tka_hero;
        }

        // 2x2 promotional banner grid.
        $tka_grid = array();
        for ( $i = 1; $i <= 4; $i++ ) {
                $tka_img = get_theme_mod( "tka_banner_{$i}_img" );
                if ( $tka_img ) {
                        $tka_grid[] = array(
                                'img'  => $tka_img,
                                'href' => get_theme_mod( "tka_banner_{$i}_link", '#' ),
                        );
                }
        }
        if ( ! empty( $tka_grid ) ) {
                $data['grid'] = $tka_grid;
        }

        // WooCommerce-powered sections.
        $tka_stories = toykindangel_wc_stories();
        if ( ! empty( $tka_stories ) ) {
                $data['stories'] = $tka_stories;
        }

        $tka_brands = toykindangel_wc_brands();
        if ( ! empty( $tka_brands ) ) {
                $data['brands'] = $tka_brands;
        }

        $tka_rails = toykindangel_wc_rails();
        if ( ! empty( $tka_rails ) ) {
                $data['products'] = $tka_rails;
        }

        return $data;
}

/**
 * Print window.TKA_WP just before the WordPress glue script (main.js),
 * which applies it onto the untouched demo dataset before app.js renders.
 */
function toykindangel_localize_front_data() {
        if ( ! is_front_page() ) {
                return;
        }

        /* Homepage gate: until demo import the JS demo dataset must never
         * paint sample content — emit fallback:false (or skip entirely). */
        if ( ! toykindangel_front_unlocked() ) {
                wp_add_inline_script(
                        'toykindangel-main',
                        'window.TKA_WP={"fallback":false};',
                        'before'
                );
                return;
        }

        wp_add_inline_script(
                'toykindangel-main',
                'window.TKA_WP=' . wp_json_encode( toykindangel_front_data() ) . ';',
                'before'
        );
}
add_action( 'wp_enqueue_scripts', 'toykindangel_localize_front_data', 20 );
