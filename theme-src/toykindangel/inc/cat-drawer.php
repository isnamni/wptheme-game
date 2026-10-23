<?php
/**
 * Categories drawer (two-column demo browser as a slide-in drawer) +
 * primary-menu drawer.
 *
 * Printed once from footer.php before wp_footer() (never on the bare
 * categories page, which has its own #catRail/#catPanel browser painted
 * by the untouched demo app.js — the drawer reuses the same demo CLASSES
 * but different ids, so nothing conflicts).
 *
 * Categories data source priority:
 *   1. "منوی دسته‌بندی‌ها" nav menu (location "cats"):
 *        top-level items       → rail
 *        their children (flat) → panel tiles
 *        rail image            → product_cat term thumbnail when the item
 *                                is a term, else bundled demo icon pool.
 *   2. toykindangel_cats_data() (inc/cat-data.php) — product_cat /
 *      tka_store_cat term tree with positional catLinks mapping.
 *   3. Demo sample tree (inc/demo/data.php) — same shape, local search
 *      links for the tiles.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Vertical fallback for the primary-menu drawer (menu location "primary").
 * Mirrors toykindangel_menu_fallback() (functions.php) with drawer styling.
 */
function toykindangel_drawer_menu_fallback() {
        $tka_items = array(
                home_url( '/' )               => __( 'خانه', 'toykindangel' ),
                toykindangel_categories_url() => __( 'دسته‌بندی‌ها', 'toykindangel' ),
                toykindangel_cart_url()       => __( 'سبد خرید', 'toykindangel' ),
                toykindangel_account_url()    => __( 'حساب کاربری', 'toykindangel' ),
        );
        echo '<ul class="tka-drawer__menu">';
        foreach ( $tka_items as $tka_url => $tka_label ) {
                echo '<li><a href="' . esc_url( $tka_url ) . '">' . esc_html( $tka_label ) . '</a></li>';
        }
        echo '</ul>';
}

/**
 * Bundled demo image pools (local theme files only — no external domains).
 *
 * @return array{tiles:string[],rail:string[]}
 */
function toykindangel_drawer_img_pools() {
        static $tka_pools = null;
        if ( null !== $tka_pools ) {
                return $tka_pools;
        }

        $tka_to_uri = static function ( $tka_file ) {
                return str_replace( TOYKINDANGEL_DIR, TOYKINDANGEL_URI, (string) $tka_file );
        };

        $tka_tile_files = glob( TOYKINDANGEL_DIR . '/assets/demo/products/*.jpg' );
        $tka_rail_files = glob( TOYKINDANGEL_DIR . '/assets/img/cats/cat-*.png' );
        $tka_tiles      = array_map( $tka_to_uri, ( $tka_tile_files ? $tka_tile_files : array() ) );
        $tka_rail       = array_map( $tka_to_uri, ( $tka_rail_files ? $tka_rail_files : array() ) );

        sort( $tka_tiles );
        sort( $tka_rail );

        $tka_pools = array(
                'tiles' => array_values( array_filter( $tka_tiles ) ),
                'rail'  => array_values( array_filter( $tka_rail ) ),
        );

        return $tka_pools;
}

/**
 * Build the drawer categories from the "cats" nav menu.
 *
 * v0.20.0 audit fix C3 — this helper is called from footer.php on every
 * page load. The previous version ran toykindangel_term_img() (a
 * get_term_meta + wp_get_attachment_image_url pair) per top-level taxonomy
 * nav-menu item, with no cache and no term-meta priming — a real N+1 on
 * every page of the site when the "cats" menu is assigned.
 *
 * The drawer markup itself is now produced from a cached dataset built
 * here: term meta + attachment caches are primed in one shot, and the
 * built data array is cached in the shared theme cache (transient +
 * per-request static), flushed by inc/cache.php on product_cat term
 * create/edit/delete.
 *
 * @return array[] list of {name,img,url,groups[]}
 */
function toykindangel_drawer_cats_from_menu() {
        static $tka_local = null;
        if ( null !== $tka_local ) {
                return $tka_local;
        }

        $tka_local = toykindangel_cache_get( 'drawer_menu' );
        if ( null !== $tka_local ) {
                return $tka_local;
        }

        $tka_local = toykindangel_drawer_cats_from_menu_uncached();
        toykindangel_cache_set( 'drawer_menu', $tka_local, 12 * HOUR_IN_SECONDS );
        return $tka_local;
}

/**
 * Uncached builder for toykindangel_drawer_cats_from_menu().
 *
 * @return array[]
 */
function toykindangel_drawer_cats_from_menu_uncached() {
        $tka_locs = get_nav_menu_locations();
        if ( empty( $tka_locs['cats'] ) ) {
                return array();
        }

        $tka_items = wp_get_nav_menu_items( $tka_locs['cats'] );
        if ( ! is_array( $tka_items ) ) {
                return array();
        }

        $tka_cats     = array();
        $tka_tiles    = array(); // top index => tiles.
        $tka_item_top = array(); // menu-item ID => top index.
        $tka_tax      = toykindangel_shop_cat_tax();

        /*
         * First pass: collect the product_cat term IDs referenced by top-level
         * menu items so we can prime term meta + attachment caches in one shot
         * before the per-item image lookup below.
         */
        $tka_term_ids   = array();
        $tka_thumb_ids  = array();
        foreach ( $tka_items as $tka_item ) {
                if ( 0 === (int) $tka_item->menu_item_parent
                        && 'taxonomy' === $tka_item->type
                        && $tka_tax === $tka_item->object ) {
                        $tka_term_ids[] = (int) $tka_item->object_id;
                }
        }
        if ( ! empty( $tka_term_ids ) ) {
                _prime_term_caches( $tka_term_ids, false );
                foreach ( $tka_term_ids as $tka_tid ) {
                        $tka_thumb = (int) get_term_meta( $tka_tid, 'thumbnail_id', true );
                        if ( $tka_thumb ) {
                                $tka_thumb_ids[] = $tka_thumb;
                        }
                }
                if ( ! empty( $tka_thumb_ids ) ) {
                        _prime_post_caches( array(), $tka_thumb_ids, false, false );
                }
        }

        foreach ( $tka_items as $tka_item ) {
                $tka_parent = (int) $tka_item->menu_item_parent;

                if ( 0 === $tka_parent ) {
                        $tka_img = '';
                        if ( 'taxonomy' === $tka_item->type && $tka_tax === $tka_item->object ) {
                                $tka_img = toykindangel_term_img( (int) $tka_item->object_id );
                        }

                        $tka_item_top[ $tka_item->ID ] = count( $tka_cats );
                        $tka_cats[]                    = array(
                                'name'   => (string) $tka_item->title,
                                'img'    => $tka_img,
                                'url'    => (string) $tka_item->url,
                                'groups' => array(),
                        );
                } elseif ( isset( $tka_item_top[ $tka_parent ] ) ) {
                        // Direct child or grandchild (flattened) of a top-level item.
                        $tka_top = $tka_item_top[ $tka_parent ];

                        $tka_name = (string) $tka_item->title;
                        if ( '' === trim( $tka_name ) ) {
                                $tka_name = (string) $tka_item->post_title;
                        }

                        $tka_item_top[ $tka_item->ID ] = $tka_top;
                        $tka_tiles[ $tka_top ][]       = array(
                                'name' => $tka_name,
                                'url'  => (string) $tka_item->url,
                        );
                }
        }

        if ( empty( $tka_cats ) ) {
                return array();
        }

        // Children become one implicit accordion group per top item.
        foreach ( $tka_cats as $tka_i => $tka_cat ) {
                if ( ! empty( $tka_tiles[ $tka_i ] ) ) {
                        $tka_cats[ $tka_i ]['groups'] = array(
                                array(
                                        'title' => __( 'زیرشاخه‌ها', 'toykindangel' ),
                                        'items' => $tka_tiles[ $tka_i ],
                                ),
                        );
                }
        }

        return $tka_cats;
}

/**
 * Per-request cached toykindangel_cats_data() (the drawer needs it twice:
 * once for the tree mapping, once for the productImgs tile pool).
 *
 * @return array|null
 */
function toykindangel_drawer_terms_payload() {
        static $tka_payload = null;
        if ( null === $tka_payload ) {
                $tka_payload = toykindangel_cats_data();
        }

        return $tka_payload;
}

/**
 * Map toykindangel_cats_data() onto the drawer shape.
 *
 * @return array[]
 */
function toykindangel_drawer_cats_from_terms() {
        $tka_data = toykindangel_drawer_terms_payload();
        if ( ! $tka_data || empty( $tka_data['categories'] ) ) {
                return array();
        }

        $tka_cats = array();

        foreach ( $tka_data['categories'] as $tka_i => $tka_c ) {
                $tka_links    = isset( $tka_data['catLinks'][ $tka_i ] ) ? $tka_data['catLinks'][ $tka_i ] : array();
                $tka_top_url  = isset( $tka_links['url'] ) ? $tka_links['url'] : '';
                $tka_groups   = array();
                $tka_gi       = 0;

                foreach ( $tka_c['groups'] as $tka_g ) {
                        $tka_gurls = ( isset( $tka_links['groups'][ $tka_gi ] ) && isset( $tka_links['groups'][ $tka_gi ]['urls'] ) )
                                ? $tka_links['groups'][ $tka_gi ]['urls']
                                : array();
                        $tka_gurl  = ( isset( $tka_links['groups'][ $tka_gi ] ) && isset( $tka_links['groups'][ $tka_gi ]['url'] ) )
                                ? $tka_links['groups'][ $tka_gi ]['url']
                                : $tka_top_url;

                        $tka_items = array();
                        foreach ( $tka_g['items'] as $tka_k => $tka_name ) {
                                $tka_u = isset( $tka_gurls[ $tka_k ] ) && $tka_gurls[ $tka_k ] ? $tka_gurls[ $tka_k ] : '';
                                if ( ! $tka_u && isset( $tka_data['catByName'][ $tka_name ] ) ) {
                                        $tka_u = $tka_data['catByName'][ $tka_name ];
                                }
                                $tka_items[] = array(
                                        'name' => $tka_name,
                                        'url'  => $tka_u ? $tka_u : $tka_gurl,
                                );
                        }

                        $tka_groups[] = array(
                                'title' => $tka_g['title'],
                                'items' => $tka_items,
                        );
                        $tka_gi++;
                }

                $tka_cats[] = array(
                        'name'   => $tka_c['name'],
                        'img'    => isset( $tka_c['img'] ) ? $tka_c['img'] : '',
                        'url'    => $tka_top_url,
                        'groups' => $tka_groups,
                );
        }

        return $tka_cats;
}

/**
 * Fallback: demo sample tree (same shape as the demo categories page).
 * Tiles link to the local WP search so the drawer stays useful before
 * any real terms exist.
 *
 * @return array[]
 */
function toykindangel_drawer_cats_from_demo() {
        if ( ! function_exists( 'toykindangel_demo_data' ) ) {
                return array();
        }

        $tka_demo = toykindangel_demo_data();
        if ( empty( $tka_demo['tree'] ) || ! is_array( $tka_demo['tree'] ) ) {
                return array();
        }

        $tka_cats = array();

        foreach ( $tka_demo['tree'] as $tka_t ) {
                $tka_img = '';
                if ( ! empty( $tka_t['file'] ) && file_exists( $tka_t['file'] ) ) {
                        $tka_img = str_replace( TOYKINDANGEL_DIR, TOYKINDANGEL_URI, $tka_t['file'] );
                }

                $tka_groups = array();
                foreach ( ( isset( $tka_t['groups'] ) ? $tka_t['groups'] : array() ) as $tka_g ) {
                        $tka_items = array();
                        foreach ( $tka_g['items'] as $tka_name ) {
                                $tka_items[] = array(
                                        'name' => $tka_name,
                                        'url'  => esc_url_raw( add_query_arg( 's', $tka_name, home_url( '/' ) ) ),
                                );
                        }
                        $tka_groups[] = array(
                                'title' => $tka_g['title'],
                                'items' => $tka_items,
                        );
                }

                $tka_cats[] = array(
                        'name'   => $tka_t['name'],
                        'img'    => $tka_img,
                        'url'    => toykindangel_categories_url(),
                        'groups' => $tka_groups,
                );
        }

        return $tka_cats;
}

/**
 * Drawer dataset (source priority: nav menu → terms → demo tree).
 *
 * @return array{cats:array[],tiles:string[]}
 */
function toykindangel_drawer_data() {
        $tka_pools = toykindangel_drawer_img_pools();

        $tka_cats = toykindangel_drawer_cats_from_menu();
        if ( ! empty( $tka_cats ) ) {
                return array(
                        'cats'  => $tka_cats,
                        'tiles' => $tka_pools['tiles'],
                );
        }

        $tka_cats = toykindangel_drawer_cats_from_terms();
        if ( ! empty( $tka_cats ) ) {
                $tka_real = toykindangel_drawer_terms_payload();
                $tka_imgs = ( $tka_real && ! empty( $tka_real['productImgs'] ) ) ? $tka_real['productImgs'] : array();

                return array(
                        'cats'  => $tka_cats,
                        'tiles' => array_values( array_unique( array_merge( $tka_imgs, $tka_pools['tiles'] ) ) ),
                );
        }

        return array(
                'cats'  => toykindangel_drawer_cats_from_demo(),
                'tiles' => $tka_pools['tiles'],
        );
}

/**
 * Tile/rail image markup (img when a URL exists, sprite icon otherwise).
 *
 * @param string $url     Image URL ('' → icon fallback).
 * @param string $ico_id  Sprite icon id used as fallback.
 * @param string $ico_class Extra class for the svg fallback.
 * @return string
 */
function toykindangel_drawer_img( $url, $ico_id = 'i-cat-variant', $ico_class = '' ) {
        if ( $url ) {
                return '<img src="' . esc_url( $url ) . '" alt="" loading="lazy" decoding="async">';
        }

        return '<svg class="ic' . ( $ico_class ? ' ' . esc_attr( $ico_class ) : '' ) . '" aria-hidden="true"><use href="#' . esc_attr( $ico_id ) . '"></use></svg>';
}

/**
 * One drawer panel page (.call + accordion groups) for a top category.
 *
 * @param array $tka_cat  {name,img,url,groups}.
 * @param int   $tka_i    Rail index.
 * @param array $tka_pool Tile image pool.
 * @param int   $tka_n    Rotation counter (by reference).
 * @return string
 */
function toykindangel_drawer_page( $tka_cat, $tka_i, $tka_pool, &$tka_n ) {
        $tka_out = '<div class="tka-drawer__page" data-page="' . absint( $tka_i ) . '"' . ( 0 === $tka_i ? '' : ' hidden' ) . '>';

        $tka_out .= '<a class="call" href="' . esc_url( $tka_cat['url'] ? $tka_cat['url'] : home_url( '/' ) ) . '">'
                . '<span>' . esc_html__( 'مشاهده همه محصولات', 'toykindangel' ) . '</span>'
                . '<svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg></a>';

        foreach ( $tka_cat['groups'] as $tka_gi => $tka_g ) {
                $tka_out .= '<div class="cgroup' . ( 0 === $tka_gi ? ' cgroup--open' : '' ) . '">';
                $tka_out .= '<button class="cgroup__head" type="button"><b>' . esc_html( $tka_g['title'] ) . '</b>'
                        . '<svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg></button>';
                $tka_out .= '<div class="cgrid">';

                foreach ( $tka_g['items'] as $tka_it ) {
                        $tka_img = ! empty( $tka_pool ) ? $tka_pool[ $tka_n % count( $tka_pool ) ] : '';
                        $tka_n++;

                        $tka_out .= '<a class="ctile" href="' . esc_url( $tka_it['url'] ? $tka_it['url'] : home_url( '/' ) ) . '" data-name="' . esc_attr( $tka_it['name'] ) . '">'
                                . '<span class="ctile__img">' . toykindangel_drawer_img( $tka_img ) . '</span>'
                                . '<span class="ctile__lb">' . esc_html( $tka_it['name'] ) . '</span>'
                                . '</a>';
                }

                $tka_out .= '</div></div>';
        }

        $tka_out .= '</div>';

        return $tka_out;
}

/**
 * Close button shared by both drawers (the sprite has no i-close, so the
 * X is inlined — same fill:currentColor style as the sprite icons).
 *
 * @return string
 */
function toykindangel_drawer_close_btn() {
        return '<button type="button" class="tka-drawer__close" data-tka-close aria-label="' . esc_attr__( 'بستن', 'toykindangel' ) . '">'
                . '<svg class="ic" viewBox="0 0 24 24" aria-hidden="true"><path d="M18.3 5.71 12 12.01l-6.3-6.3-1.4 1.41 6.29 6.3-6.3 6.3 1.41 1.4 6.3-6.29 6.29 6.3 1.42-1.4-6.3-6.3 6.3-6.3z"/></svg>'
                . '</button>';
}

/**
 * Print both drawers (primary menu + categories browser).
 * Called from footer.php just before wp_footer().
 */
function toykindangel_cats_drawer() {
        $tka_pack = toykindangel_drawer_data();
        $tka_cats = $tka_pack['cats'];
        $tka_pool = $tka_pack['tiles'];

        /* ---------- دراور منوی اصلی (دکمهٔ همبرگر موبایل) ---------- */
        echo '<div class="tka-drawer tka-drawer--menu" id="tka-menu-drawer" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'منوی اصلی', 'toykindangel' ) . '" hidden>';
        echo '<div class="tka-drawer__head">';
        echo '<b class="tka-drawer__title">' . esc_html__( 'منوی اصلی', 'toykindangel' ) . '</b>';
        echo toykindangel_drawer_close_btn(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static safe markup.
        echo '</div>';
        echo '<nav class="tka-drawer__menuwrap" aria-label="' . esc_attr__( 'منوی اصلی', 'toykindangel' ) . '">';
        wp_nav_menu(
                array(
                        'theme_location' => 'primary',
                        'container'      => false,
                        'menu_class'     => 'tka-drawer__menu',
                        'depth'          => 2,
                        'fallback_cb'    => 'toykindangel_drawer_menu_fallback',
                )
        );
        echo '</nav>';
        echo '</div>';

        /* ---------- دراور مرورگر دسته‌بندی‌ها (هم‌طرح صفحهٔ دسته‌بندی‌ها) ---------- */
        echo '<div class="tka-drawer tka-drawer--cats" id="tka-cats-drawer" role="dialog" aria-modal="true" aria-label="' . esc_attr__( 'دسته‌بندی‌ها', 'toykindangel' ) . '" hidden>';
        echo '<div class="tka-drawer__head">';
        echo '<b class="tka-drawer__title">' . esc_html__( 'دسته‌بندی‌ها', 'toykindangel' ) . '</b>';
        echo toykindangel_drawer_close_btn(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo '</div>';

        echo '<label class="tka-drawer__search">'
                . '<svg class="ic" aria-hidden="true"><use href="#i-search"></use></svg>'
                . '<span class="screen-reader-text">' . esc_html__( 'جستجو در دسته‌بندی‌ها', 'toykindangel' ) . '</span>'
                . '<input type="search" data-tka-cats-search placeholder="' . esc_attr__( 'جستجو در فرشته مهربون…', 'toykindangel' ) . '">'
                . '</label>';

        echo '<div class="tka-drawer__body">';

        if ( empty( $tka_cats ) ) {
                echo '<div class="cempty">'
                        . '<svg class="ic" aria-hidden="true"><use href="#i-cat-variant"></use></svg>'
                        . esc_html__( 'هنوز دسته‌بندی‌ای ساخته نشده است.', 'toykindangel' )
                        . '</div>';
        } else {
                $tka_n = 0;

                echo '<aside class="crail tka-drawer__rail" aria-label="' . esc_attr__( 'دسته‌های اصلی', 'toykindangel' ) . '">';
                $tka_rail_pool = toykindangel_drawer_img_pools();
                foreach ( $tka_cats as $tka_i => $tka_cat ) {
                        $tka_img = $tka_cat['img'];
                        if ( ! $tka_img && ! empty( $tka_rail_pool['rail'] ) ) {
                                $tka_img = $tka_rail_pool['rail'][ $tka_i % count( $tka_rail_pool['rail'] ) ];
                        }

                        echo '<button type="button" class="crail__item' . ( 0 === $tka_i ? ' on' : '' ) . '" data-panel="' . absint( $tka_i ) . '">';
                        if ( $tka_img ) {
                                echo '<img class="crail__ico" src="' . esc_url( $tka_img ) . '" alt="" loading="lazy" decoding="async">';
                        } else {
                                echo toykindangel_drawer_img( '', 'i-cat-variant', 'crail__ico' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
                        }
                        echo '<span>' . esc_html( $tka_cat['name'] ) . '</span>';
                        echo '</button>';
                }
                echo '</aside>';

                echo '<div class="cpanel tka-drawer__panel" aria-label="' . esc_attr__( 'زیردسته‌ها', 'toykindangel' ) . '">';
                foreach ( $tka_cats as $tka_i => $tka_cat ) {
                        echo toykindangel_drawer_page( $tka_cat, $tka_i, $tka_pool, $tka_n ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside.
                }
                echo '</div>';
        }

        echo '</div>'; // /.tka-drawer__body.
        echo '</div>'; // /#tka-cats-drawer.
}
