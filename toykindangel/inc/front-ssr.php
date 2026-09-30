<?php
/**
 * Front-page server-side rendering (SSR).
 *
 * P0 review fix #1 — the demo app.js used to paint #storyRow,
 * #amazingTrack, #newestTrack, #bestTrack, #bannerGrid, #brandRow and the
 * hero slider from a JSON dataset. That is SEO-blind (empty HTML for
 * crawlers) and causes layout shift. This module renders the exact same
 * demo markup 1:1 from real WooCommerce data / Customizer settings, and
 * app.js is guarded to only fill containers that are still empty — the
 * JS becomes a progressive enhancement (demo fallback) instead of the
 * primary renderer.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Real WooCommerce products for the three product rails (static-cached:
 * front-page.php calls this for each rail — queries must run once).
 *
 * @return array<string, WC_Product[]> amazing/newest/best arrays (may be empty).
 */
function toykindangel_ssr_products() {
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
                foreach ( wc_get_products( array( 'limit' => 100, 'status' => 'publish' ) ) as $tka_p ) {
                        $tka_reg = (float) $tka_p->get_regular_price();
                        $tka_now = (float) $tka_p->get_price();
                        if ( $tka_reg > 0 && $tka_now > 0 && $tka_now < $tka_reg ) {
                                $tka_sale_ids[] = $tka_p->get_id();
                        }
                }
                $tka_sale_ids = array_slice( array_unique( $tka_sale_ids ), 0, 10 );
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

        /* Newest. */
        $tka_rails['newest'] = wc_get_products(
                array(
                        'limit'   => 10,
                        'orderby' => 'date',
                        'order'   => 'DESC',
                )
        );

        /* Best sellers by total_sales meta. */
        $tka_best = new WP_Query(
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
        foreach ( $tka_best->posts as $tka_post ) {
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
 * Demo img() helper, server-side (matches app.js img() markup).
 *
 * @param string $src Image URL.
 * @param string $alt Alt text (demo uses "" — real alt is better for SEO).
 * @return string
 */
function toykindangel_ssr_img( $src, $alt = '' ) {
        if ( ! $src ) {
                return '';
        }
        return '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.remove()">';
}

/**
 * Story row SSR (categories as stories) — matches app.js renderHome().
 *
 * @return string
 */
function toykindangel_ssr_stories() {
        $tka_stories = toykindangel_wc_stories();
        if ( empty( $tka_stories ) ) {
                return ''; /* Leave empty → demo fallback via app.js (if enabled). */
        }

        $tka_html = '';
        foreach ( $tka_stories as $tka_s ) {
                $tka_html .= '<a class="story" href="' . esc_url( $tka_s['href'] ) . '">';
                $tka_html .= '<span class="story__img">' . toykindangel_ssr_img( $tka_s['img'], esc_html( $tka_s['name'] ) ) . '</span>';
                $tka_html .= '<span class="story__label">' . esc_html( $tka_s['name'] ) . '</span></a>';
        }

        /* «همه دسته‌ها» closing card — same as app.js. */
        $tka_html .= '<a class="story" href="' . esc_url( toykindangel_categories_url() ) . '">';
        $tka_html .= '<span class="story__img" style="display:grid;place-items:center;background:var(--primary-light);color:var(--primary)"><svg class="ic" style="font-size:34px" aria-hidden="true"><use href="#i-grid"></use></svg></span>';
        $tka_html .= '<span class="story__label">' . esc_html__( 'همه دسته‌ها', 'toykindangel' ) . '</span></a>';

        return $tka_html;
}

/**
 * One product rail SSR: pcards + «مشاهده همه» morecard.
 *
 * @param WC_Product[] $products Products.
 * @param string       $more_url «مشاهده همه» target.
 * @return string
 */
function toykindangel_ssr_rail( $products, $more_url = '' ) {
        if ( empty( $products ) || ! is_array( $products ) ) {
                return '';
        }

        $tka_html = '';
        foreach ( $products as $tka_product ) {
                if ( $tka_product instanceof WC_Product ) {
                        $tka_html .= toykindangel_pcard_wrap( $tka_product );
                }
        }
        if ( '' === $tka_html ) {
                return '';
        }

        $tka_html .= '<a class="morecard" href="' . esc_url( $more_url ? $more_url : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ) ) . '"><i><svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg></i>' . esc_html__( 'مشاهده همه', 'toykindangel' ) . '</a>';

        return $tka_html;
}

/**
 * Hero slider SSR (Customizer slides) — matches app.js hero().
 *
 * @return array{track:string,dots:string} Empty strings when unset.
 */
function toykindangel_ssr_hero() {
        static $tka_cache = null;
        if ( null !== $tka_cache ) {
                return $tka_cache;
        }

        $tka_track = '';
        $tka_dots  = '';
        $tka_n     = 0;

        for ( $i = 1; $i <= 4; $i++ ) {
                $tka_img = get_theme_mod( "tka_hero_{$i}_img" );

                /* TKA 0.18.0 fix: بخش سفارشی‌ساز قول می‌دهد «اگر تصویری تنظیم
                 * نشود تصویر نمونهٔ دمو نمایش داده می‌شود»، ولی اسلایدهای خالی
                 * رد می‌شدند و با خالی شدن تنظیمات، کل اسلایدر حذف می‌شد.
                 * فال‌بک به بنر محلی دمو اضافه شد. */
                if ( ! $tka_img ) {
                        $tka_img = TOYKINDANGEL_URI . '/assets/img/tiles/tile-' . ( $i - 1 ) . '.jpg';
                }

                $tka_link  = get_theme_mod( "tka_hero_{$i}_link", '#' );
                $tka_track .= '<a class="hero__slide" href="' . esc_url( $tka_link ) . '">' . toykindangel_ssr_img( $tka_img, get_bloginfo( 'name' ) . ' — ' . __( 'پیشنهاد ویژه', 'toykindangel' ) ) . '</a>';
                $tka_dots  .= '<i class="' . ( 0 === $tka_n ? 'on' : '' ) . '"></i>';
                $tka_n++;
        }

        $tka_cache = array(
                'track' => $tka_track,
                'dots'  => $tka_dots,
        );

        return $tka_cache;
}

/**
 * 2×2 promotional banner grid SSR — matches app.js bannerGrid.
 *
 * @return string
 */
function toykindangel_ssr_grid() {
        $tka_html = '';
        for ( $i = 1; $i <= 4; $i++ ) {
                $tka_img = get_theme_mod( "tka_banner_{$i}_img" );

                /* TKA 0.18.0 fix: فال‌بک دمو — مثل هیرو، تا بنرهای ۲×۲ هم با
                 * خالی بودن تنظیمات حذف نشوند. */
                if ( ! $tka_img ) {
                        $tka_img = TOYKINDANGEL_URI . '/assets/img/tiles/tile-' . ( $i + 3 ) . '.jpg';
                }

                $tka_link  = get_theme_mod( "tka_banner_{$i}_link", '#' );
                $tka_html .= '<a class="banner" href="' . esc_url( $tka_link ) . '">' . toykindangel_ssr_img( $tka_img, __( 'بنر تبلیغاتی فروشگاه', 'toykindangel' ) ) . '</a>';
        }
        return $tka_html;
}

/**
 * Brands row SSR — matches app.js brandRow.
 *
 * @return string
 */
function toykindangel_ssr_brands() {
        $tka_brands = toykindangel_wc_brands();
        if ( empty( $tka_brands ) ) {
                return '';
        }

        $tka_html = '';
        foreach ( $tka_brands as $tka_b ) {
                /* TKA fix (13): empty brand boxes -> monogram badge with the first letter. */
                $tka_mono = '';
                if ( empty( $tka_b['img'] ) ) {
                        $tka_letter = function_exists( 'mb_substr' ) ? mb_substr( wp_strip_all_tags( $tka_b['name'] ), 0, 1, 'UTF-8' ) : substr( $tka_b['name'], 0, 1 );
                        $tka_mono   = '<span class="brand__img brand__img--mono" aria-hidden="true">' . esc_html( $tka_letter ) . '</span>';
                }
                $tka_html .= '<a class="brand" href="' . esc_url( $tka_b['href'] ) . '">' . ( $tka_mono ? $tka_mono : '<span class="brand__img">' . toykindangel_ssr_img( $tka_b['img'], esc_html( $tka_b['name'] ) ) . '</span>' ) . '<span>' . esc_html( $tka_b['name'] ) . '</span></a>';
        }
        return $tka_html;
}
