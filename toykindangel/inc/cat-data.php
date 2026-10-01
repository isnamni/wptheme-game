<?php
/**
 * Categories-browser data (Step 4 — phase 1).
 *
 * v0.21.0 refactor — the bulky window.TKA_WP payload (categories,
 * catLinks, catByName, productImgs) is no longer shipped to JS. The
 * full category tree is server-rendered by toykindangel_ssr_categories_tree()
 * (inc/front-ssr.php) directly from toykindangel_cats_data(). JS only
 * needs two URLs (catUrl, searchUrl) for the drawer/tree interactions.
 *
 * Data source priority (unchanged):
 *   1. product_cat   (WooCommerce product categories — production)
 *   2. tka_store_cat (theme fallback taxonomy — no-WC installs / test)
 *   3. empty         (the SSR helper returns an empty string and the
 *      template shows a "no categories yet" state — no demo fallback)
 *
 * Tree mapping (consumed by the SSR helper, mirrors the original demo
 * contract so the rendered markup matches app.js 1:1):
 *   top-level term          → rail item   { name, img }
 *   child term w/ children  → group       { title, items[] }
 *   child term w/o children → item inside an implicit "زیردسته‌ها" group
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Active shop-category taxonomy (product_cat when WooCommerce registers it,
 * otherwise the theme's own hierarchical fallback taxonomy).
 *
 * @return string
 */
function toykindangel_shop_cat_tax() {
        return taxonomy_exists( 'product_cat' ) ? 'product_cat' : 'tka_store_cat';
}

/**
 * Register the fallback store-category taxonomy for installs without
 * WooCommerce, so the categories browser works with real WP terms even
 * before the shop goes live. WooCommerce registers product_cat at init 5,
 * so checking at init 20 is reliable.
 */
function toykindangel_register_shop_cat() {
        if ( taxonomy_exists( 'product_cat' ) || taxonomy_exists( 'tka_store_cat' ) ) {
                return;
        }

        register_taxonomy(
                'tka_store_cat',
                array( 'post' ),
                array(
                        'labels'            => array(
                                'name'          => __( 'دسته‌های فروشگاه', 'toykindangel' ),
                                'singular_name' => __( 'دسته فروشگاه', 'toykindangel' ),
                                'menu_name'     => __( 'دسته‌های فروشگاه', 'toykindangel' ),
                                'all_items'     => __( 'همه دسته‌های فروشگاه', 'toykindangel' ),
                                'add_new_item'  => __( 'افزودن دسته فروشگاه جدید', 'toykindangel' ),
                                'edit_item'     => __( 'ویرایش دسته فروشگاه', 'toykindangel' ),
                                'parent_item'   => __( 'دسته مادر', 'toykindangel' ),
                        ),
                        'hierarchical'      => true,
                        'public'            => true,
                        'show_ui'           => true,
                        'show_in_rest'      => true,
                        'show_admin_column' => false,
                        'rewrite'           => array(
                                'slug'         => 'shop-cat',
                                'hierarchical' => true,
                        ),
                )
        );
}
add_action( 'init', 'toykindangel_register_shop_cat', 20 );

/**
 * Term thumbnail URL (thumbnail_id meta — same key WooCommerce uses for
 * product_cat images, so the produced markup is identical).
 *
 * @param int $term_id Term ID.
 * @return string
 */
function toykindangel_term_img( $term_id ) {
        $tka_att = (int) get_term_meta( $term_id, 'thumbnail_id', true );
        if ( ! $tka_att ) {
                return '';
        }

        $tka_url = wp_get_attachment_image_url( $tka_att, 'medium' );

        return $tka_url ? $tka_url : '';
}

/**
 * Safe term link (WP_Error tolerant).
 *
 * @param WP_Term $term Term object.
 * @return string
 */
function toykindangel_term_link( $term ) {
        $tka_link = get_term_link( $term );

        return is_wp_error( $tka_link ) ? '' : $tka_link;
}

/**
 * Build the full categories-browser dataset.
 *
 * v0.19.0: cached wrapper. The uncached builder is N+1 by design
 * (top terms → children → grandchildren, ~2+T+C queries) and used to run
 * on every page render via the footer drawer (when no "cats" nav menu is
 * assigned) and twice on the categories page itself. Now: one build per
 * request at most, and the transient keeps it across requests until a
 * product_cat/tka_brand/tka_store_cat term changes or the permalink
 * structure is rebuilt (inc/cache.php owns the invalidation).
 *
 * @return array|null Null when no terms exist (demo fallback keeps showing).
 */
function toykindangel_cats_data() {
        static $tka_local = null;
        if ( null !== $tka_local ) {
                return $tka_local;
        }

        $tka_local = toykindangel_cache_get( 'cats_tree' );
        if ( null === $tka_local ) {
                $tka_local = toykindangel_cats_data_uncached();
                if ( null !== $tka_local ) {
                        toykindangel_cache_set( 'cats_tree', $tka_local, 12 * HOUR_IN_SECONDS );
                }
        }

        return $tka_local;
}

/**
 * Uncached categories-browser builder (see toykindangel_cats_data()).
 *
 * @return array|null
 */
function toykindangel_cats_data_uncached() {
        $tka_tax = toykindangel_shop_cat_tax();
        if ( ! taxonomy_exists( $tka_tax ) ) {
                return null;
        }

        // Rail ordering: seeded demo order (meta) when available, else name.
        $tka_has_order = get_terms(
                array(
                        'taxonomy'   => $tka_tax,
                        'parent'     => 0,
                        'hide_empty' => false,
                        'number'     => 1,
                        'fields'     => 'ids',
                        'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- indexed theme meta on product_cat; page-level cached query
                                array(
                                        'key' => 'tka_rail_order',
                                ),
                        ),
                )
        );
        $tka_has_order = ! is_wp_error( $tka_has_order ) && ! empty( $tka_has_order );

        $tka_top_args = array(
                'taxonomy'   => $tka_tax,
                'parent'     => 0,
                'hide_empty' => false,
                'number'     => 40,
                'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ), // «دسته‌بندی‌نشده» را نشان نده.
        );
        if ( $tka_has_order ) {
                // Ordered rail + skip story-flagged terms (front-page stories only).
                $tka_top_args['meta_key']   = 'tka_rail_order'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- indexed theme meta, cached rails
                $tka_top_args['orderby']    = 'meta_value_num';
                $tka_top_args['order']      = 'ASC';
                $tka_top_args['meta_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- indexed theme meta on product_cat; cached rails
                        array(
                                'key'     => 'tka_is_story',
                                'compare' => 'NOT EXISTS',
                        ),
                );
        }
        $tka_tops = get_terms( $tka_top_args );
        if ( is_wp_error( $tka_tops ) || empty( $tka_tops ) ) {
                return null;
        }

        $tka_categories = array();
        $tka_links      = array();
        $tka_by_name    = array();
        $tka_img_pool   = array();

        foreach ( $tka_tops as $tka_top ) {
                $tka_img = toykindangel_term_img( $tka_top->term_id );
                if ( $tka_img ) {
                        $tka_img_pool[] = $tka_img;
                }

                $tka_top_url = toykindangel_term_link( $tka_top );

                $tka_cat = array(
                        'name'   => $tka_top->name,
                        'img'    => $tka_img,
                        'groups' => array(),
                );
                $tka_lnk = array(
                        'url'    => $tka_top_url,
                        'groups' => array(),
                );

                $tka_children = get_terms(
                        array(
                                'taxonomy'   => $tka_tax,
                                'parent'     => $tka_top->term_id,
                                'hide_empty' => false,
                                'number'     => 100,
                        )
                );

                $tka_grouped = array(); // Groups built from children WITH children.
                $tka_items   = array(); // Childless children become plain items.
                $tka_iurls   = array();

                if ( ! is_wp_error( $tka_children ) ) {
                        foreach ( $tka_children as $tka_child ) {
                                $tka_ch_url = toykindangel_term_link( $tka_child );

                                $tka_grand = get_terms(
                                        array(
                                                'taxonomy'   => $tka_tax,
                                                'parent'     => $tka_child->term_id,
                                                'hide_empty' => false,
                                                'number'     => 100,
                                        )
                                );

                                if ( ! is_wp_error( $tka_grand ) && ! empty( $tka_grand ) ) {
                                        $tka_names = array();
                                        $tka_urls  = array();
                                        foreach ( $tka_grand as $tka_g ) {
                                                $tka_names[]       = $tka_g->name;
                                                $tka_gu            = toykindangel_term_link( $tka_g );
                                                $tka_urls[]        = $tka_gu;
                                                $tka_by_name[ $tka_g->name ] = $tka_gu;
                                                $tka_gimg          = toykindangel_term_img( $tka_g->term_id );
                                                if ( $tka_gimg ) {
                                                        $tka_img_pool[] = $tka_gimg;
                                                }
                                        }
                                        $tka_grouped[] = array(
                                                'title' => $tka_child->name,
                                                'items' => $tka_names,
                                        );
                                        $tka_lnk['groups'][] = array(
                                                'url'  => $tka_ch_url,
                                                'urls' => $tka_urls,
                                        );
                                        $tka_chimg = toykindangel_term_img( $tka_child->term_id );
                                        if ( $tka_chimg ) {
                                                $tka_img_pool[] = $tka_chimg;
                                        }
                                } else {
                                        $tka_items[]         = $tka_child->name;
                                        $tka_iurls[]         = $tka_ch_url;
                                        $tka_by_name[ $tka_child->name ] = $tka_ch_url;
                                        $tka_chimg2          = toykindangel_term_img( $tka_child->term_id );
                                        if ( $tka_chimg2 ) {
                                                $tka_img_pool[] = $tka_chimg2;
                                        }
                                }
                        }
                }

                // Implicit accordion group for childless children.
                if ( ! empty( $tka_items ) ) {
                        $tka_grouped[]       = array(
                                'title' => __( 'زیردسته‌ها', 'toykindangel' ),
                                'items' => $tka_items,
                        );
                        $tka_lnk['groups'][] = array(
                                'url'  => $tka_top_url,
                                'urls' => $tka_iurls,
                        );
                }

                $tka_cat['groups'] = $tka_grouped;
                $tka_categories[]  = $tka_cat;
                $tka_links[]       = $tka_lnk;
                $tka_by_name[ $tka_top->name ] = $tka_top_url;
        }

        if ( empty( $tka_categories ) ) {
                return null;
        }

        return array(
                'categories'  => $tka_categories,
                'catLinks'    => $tka_links,
                'catByName'   => $tka_by_name,
                'productImgs' => array_values( array_unique( array_filter( $tka_img_pool ) ) ),
        );
}

/**
 * Print a small window.TKA_WP config for the /categories/ page.
 *
 * v0.21.0 refactor — the full categories tree is now server-rendered by
 * toykindangel_ssr_categories_tree() (inc/front-ssr.php), so the bulky
 * categories/catLinks/catByName/productImgs payload is no longer shipped
 * to JS. Only the handful of URLs the interactions actually need remain.
 */
function toykindangel_localize_cats_data() {
        if ( ! is_page( 'categories' ) ) {
                return;
        }

        $tka_payload = array(
                'catUrl'    => toykindangel_categories_url(),
                'searchUrl' => home_url( '/' ),
        );

        wp_add_inline_script(
                'toykindangel-main',
                'window.TKA_WP=' . wp_json_encode( $tka_payload ) . ';',
                'before'
        );
}
add_action( 'wp_enqueue_scripts', 'toykindangel_localize_cats_data', 20 );
