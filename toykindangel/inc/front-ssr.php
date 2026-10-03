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
 * Real WooCommerce products for the three product rails.
 *
 * v0.19.0: thin wrapper over toykindangel_home_rail_products()
 * (inc/front-data.php) — the single query source shared with the
 * TKA_WP dataset builder, so the rails run exactly once per request
 * instead of once per renderer.
 *
 * @return array<string, WC_Product[]> amazing/newest/best arrays (may be empty).
 */
function toykindangel_ssr_products() {
        if ( ! function_exists( 'wc_get_products' ) ) {
                return array(
                        'amazing' => array(),
                        'newest'  => array(),
                        'best'    => array(),
                );
        }

        return toykindangel_home_rail_products();
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
 * Eager-loaded img for above-the-fold hero slides.
 *
 * v0.22.3: hero slides use transform:translateX in desktop, so slides 2+
 * are off-screen but must be loaded immediately — loading="lazy" would
 * skip them until the user scrolls, leaving blank slides.
 *
 * @param string $src Image URL.
 * @param string $alt Alt text.
 * @return string
 */
function toykindangel_ssr_img_eager( $src, $alt = '' ) {
        if ( ! $src ) {
                return '';
        }
        return '<img src="' . esc_url( $src ) . '" alt="' . esc_attr( $alt ) . '" loading="eager" decoding="async" referrerpolicy="no-referrer" onerror="this.remove()">';
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

                /* v0.22.1: fallback دمو حذف شد. اگر تصویری تنظیم نشود،
                 * اسلاید اصلا چاپ نمی‌شود — فقط همان تعدادی که کاربر
                 * تصویر گذاشته نمایش داده می‌شود (۲ تا، ۳ تا یا ۴ تا). */
                if ( ! $tka_img ) {
                        continue;
                }

                $tka_link  = get_theme_mod( "tka_hero_{$i}_link", '#' );
                $tka_track .= '<a class="hero__slide" href="' . esc_url( $tka_link ) . '">' . toykindangel_ssr_img_eager( $tka_img, get_bloginfo( 'name' ) . ' — ' . __( 'پیشنهاد ویژه', 'toykindangel' ) ) . '</a>';
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

/**
 * Top-level product categories grid for the homepage «دسته‌بندی‌های فروشگاه»
 * rail.
 *
 * v0.20.0 audit fix C1 — the previous inline block in front-page.php ran an
 * UNBOUNDED get_terms(parent=0) and then a get_term_meta(thumbnail_id) +
 * wp_get_attachment_image_url() per term inside the render loop, with no
 * transient wrap. get_terms() does NOT prime term meta, so every term was
 * a separate termmeta query + (on cache miss) a separate attachment lookup
 * — a real N+1 on every homepage view.
 *
 * This helper:
 *   - bounds the scan to 24 top-level terms (same as the visible grid),
 *   - primes term meta in one shot via _prime_term_caches(),
 *   - primes the attachment post+meta cache in one shot for the thumbnails
 *     that actually exist,
 *   - caches the built HTML array in the shared theme cache (transient +
 *     per-request static), flushed by inc/cache.php on product_cat term
 *     create/edit/delete — same invalidation contract as the stories/brands
 *     caches that already live there.
 *
 * Output is identical markup to the previous inline block (1:1), so app.js
 * and demo.css are untouched.
 *
 * @return array[] { name, href, img } — empty array when no terms.
 */
function toykindangel_ssr_catgrid() {
        static $tka_local = null;
        if ( null !== $tka_local ) {
                return $tka_local;
        }

        $tka_local = toykindangel_cache_get( 'home_catgrid' );
        if ( null !== $tka_local ) {
                return $tka_local;
        }

        $tka_local = array();
        if ( ! taxonomy_exists( 'product_cat' ) ) {
                return $tka_local;
        }

        $tka_terms = get_terms(
                array(
                        'taxonomy'   => 'product_cat',
                        'parent'     => 0,
                        'hide_empty' => false,
                        'number'     => 24,
                        'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ),
                )
        );
        if ( is_wp_error( $tka_terms ) || empty( $tka_terms ) ) {
                toykindangel_cache_set( 'home_catgrid', $tka_local, 12 * HOUR_IN_SECONDS );
                return $tka_local;
        }

        /*
         * Prime term meta in one query (get_terms does not). Also primes
         * term-link related caches via WP_Term instances already populated.
         */
        $tka_term_ids = array();
        foreach ( $tka_terms as $tka_term ) {
                $tka_term_ids[] = (int) $tka_term->term_id;
        }
        _prime_term_caches( $tka_term_ids, false );

        /*
         * Collect thumbnail attachment IDs and prime their post + postmeta
         * cache in one shot, so wp_get_attachment_image_url() below is a
         * cache hit instead of 2N queries.
         */
        $tka_thumb_ids = array();
        foreach ( $tka_terms as $tka_term ) {
                $tka_thumb = (int) get_term_meta( (int) $tka_term->term_id, 'thumbnail_id', true );
                if ( $tka_thumb ) {
                        $tka_thumb_ids[] = $tka_thumb;
                }
        }
        if ( ! empty( $tka_thumb_ids ) ) {
                _prime_post_caches( array(), $tka_thumb_ids, false, false );
        }

        foreach ( $tka_terms as $tka_term ) {
                $tka_link = get_term_link( $tka_term );
                if ( is_wp_error( $tka_link ) ) {
                        continue;
                }
                $tka_thumb = (int) get_term_meta( (int) $tka_term->term_id, 'thumbnail_id', true );
                $tka_img   = $tka_thumb ? wp_get_attachment_image_url( $tka_thumb, 'woocommerce_thumbnail' ) : '';

                $tka_local[] = array(
                        'name' => $tka_term->name,
                        'href' => $tka_link,
                        'img'  => $tka_img ? $tka_img : '',
                );
        }

        toykindangel_cache_set( 'home_catgrid', $tka_local, 12 * HOUR_IN_SECONDS );
        return $tka_local;
}

/**
 * Server-rendered /categories/ tree browser (section 5 of page-categories.php).
 *
 * v0.21.0 refactor — the two-column category browser (#catRail / #catPanel)
 * used to be painted entirely by app.js from window.TKA_WP.categories, which
 * was SEO-blind (crawlers saw only empty containers + a <noscript>). The data
 * was already built in PHP by toykindangel_cats_data() (inc/cat-data.php) —
 * only the rendering was delegated to JS.
 *
 * This helper emits the exact same markup app.js produced (crail/cpanel/
 * cgroup/ctile) with REAL hrefs straight from the term links, so:
 *   - crawlers see the full category tree (internal linking + SEO),
 *   - the page works without JavaScript (progressive enhancement),
 *   - window.TKA_WP.categories / catLinks / catByName are no longer needed,
 *   - app.js renderCategories() and main.js section 4 (URL attacher) become
 *     dead code and are removed.
 *
 * JavaScript keeps only the three interactions that need it:
 *   - rail click → switch the visible panel (show/hide pre-rendered panels),
 *   - accordion (open/close a cgroup),
 *   - live filter (DOM-based search over the pre-rendered tiles).
 *
 * @return string HTML markup (empty when no categories exist).
 */
function toykindangel_ssr_categories_tree() {
        $tka_data = toykindangel_cats_data();
        if ( ! $tka_data || empty( $tka_data['categories'] ) ) {
                return '';
        }

        $tka_categories = $tka_data['categories'];
        $tka_links      = $tka_data['catLinks'];
        $tka_img_pool   = ! empty( $tka_data['productImgs'] ) ? array_values( $tka_data['productImgs'] ) : array();
        $tka_cat_url    = toykindangel_categories_url();

        /* img() helper — matches app.js img() markup 1:1. */
        $tka_img = static function ( $src ) {
                if ( ! $src ) {
                        return '';
                }
                return '<img src="' . esc_url( $src ) . '" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.remove()">';
        };

        /* catTile() helper — matches app.js catTile() markup 1:1.
         * $fb is a fallback image src used when the main image fails to load. */
        $tka_tile = static function ( $name, $src, $fb, $href = '#' ) use ( $tka_img ) {
                $tka_tag = $fb
                        ? '<img src="' . esc_url( $src ) . '" alt="" loading="lazy" referrerpolicy="no-referrer" onerror="this.onerror=null;this.src=\'' . esc_url( $fb ) . '\'">'
                        : $tka_img( $src );
                return '<a class="ctile" href="' . esc_url( $href ) . '">'
                        . '<span class="ctile__img">' . $tka_tag . '</span>'
                        . '<span class="ctile__lb">' . esc_html( $name ) . '</span>'
                        . '</a>';
        };

        /* Pool iterator — mirrors app.js nextImg(): cycles through the unique
         * term thumbnails so every tile gets an image even when the term has
         * no thumbnail of its own. */
        $tka_n = 0;
        $tka_next_img = static function () use ( &$tka_n, $tka_img_pool ) {
                $tka_i = $tka_n++;
                return array(
                        'src' => ! empty( $tka_img_pool ) ? $tka_img_pool[ $tka_i % count( $tka_img_pool ) ] : '',
                        'fb'  => '',
                );
        };

        /* Rail (left column): one button per top-level category. */
        $tka_rail_html = '';
        foreach ( $tka_categories as $tka_i => $tka_cat ) {
                $tka_rail_html .= '<button type="button" class="crail__item' . ( 0 === $tka_i ? ' on' : '' ) . '" data-i="' . esc_attr( (string) $tka_i ) . '">'
                        . ( ! empty( $tka_cat['img'] ) ? '<img class="crail__ico" src="' . esc_url( $tka_cat['img'] ) . '" alt="" loading="lazy">' : '' )
                        . '<span>' . esc_html( $tka_cat['name'] ) . '</span>'
                        . '</button>';
        }

        /* Panel (right column): one full panel per top-level category, all
         * pre-rendered. CSS shows only the active one (.cpanel__page.on).
         * This is the key change vs. app.js: instead of repainting panel
         * innerHTML on every rail click, all panels exist in the DOM and
         * JS only toggles visibility — much faster and SEO-visible. */
        $tka_panels_html = '';
        foreach ( $tka_categories as $tka_i => $tka_cat ) {
                $tka_lnk    = isset( $tka_links[ $tka_i ] ) ? $tka_links[ $tka_i ] : array( 'url' => $tka_cat_url, 'groups' => array() );
                $tka_topurl = ! empty( $tka_lnk['url'] ) ? $tka_lnk['url'] : $tka_cat_url;

                $tka_panel  = '<div class="cpanel__page' . ( 0 === $tka_i ? ' on' : '' ) . '" data-i="' . esc_attr( (string) $tka_i ) . '">';
                $tka_panel .= '<a class="call" href="' . esc_url( $tka_topurl ) . '"><span>' . esc_html__( 'مشاهده همه محصولات', 'toykindangel' ) . '</span><svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg></a>';

                if ( ! empty( $tka_cat['groups'] ) ) {
                        foreach ( $tka_cat['groups'] as $tka_gi => $tka_group ) {
                                $tka_gdata = isset( $tka_lnk['groups'][ $tka_gi ] ) ? $tka_lnk['groups'][ $tka_gi ] : array( 'url' => $tka_topurl, 'urls' => array() );
                                $tka_gurl  = ! empty( $tka_gdata['url'] ) ? $tka_gdata['url'] : $tka_topurl;

                                $tka_panel .= '<div class="cgroup' . ( 0 === $tka_gi ? ' cgroup--open' : '' ) . '">'
                                        . '<button type="button" class="cgroup__head"><b>' . esc_html( $tka_group['title'] ) . '</b><svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg></button>'
                                        . '<div class="cgrid">';

                                if ( ! empty( $tka_group['items'] ) ) {
                                        foreach ( $tka_group['items'] as $tka_k => $tka_item_name ) {
                                                $tka_p    = $tka_next_img();
                                                $tka_href = ( isset( $tka_gdata['urls'][ $tka_k ] ) && $tka_gdata['urls'][ $tka_k ] ) ? $tka_gdata['urls'][ $tka_k ] : $tka_gurl;
                                                $tka_panel .= $tka_tile( $tka_item_name, $tka_p['src'], $tka_p['fb'], $tka_href );
                                        }
                                }

                                /* «همه کالاها» closing tile — matches app.js. */
                                $tka_p_all = $tka_next_img();
                                $tka_panel .= $tka_tile( __( 'همه کالاها', 'toykindangel' ), $tka_p_all['src'], $tka_p_all['fb'], $tka_gurl );

                                $tka_panel .= '</div></div>';
                        }
                }

                $tka_panel .= '</div>';
                $tka_panels_html .= $tka_panel;
        }

        /* Assemble the two-column browser. */
        $tka_html  = '<div class="cpage cthub-cpage">';
        $tka_html .= '<aside class="crail" id="catRail" aria-label="' . esc_attr__( 'دسته‌های اصلی', 'toykindangel' ) . '">' . $tka_rail_html . '</aside>';
        $tka_html .= '<div class="cpanel" id="catPanel" aria-label="' . esc_attr__( 'زیردسته‌ها', 'toykindangel' ) . '">' . $tka_panels_html . '</div>';
        $tka_html .= '</div>';

        return $tka_html;
}
