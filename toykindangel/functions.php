<?php
/**
 * ToyKind Angel theme bootstrap.
 *
 * Step 2: the demo asset pipeline is wired 1:1 — demo.css carries the
 * untouched demo stylesheet, icons/data/app.js run exactly like the demo,
 * and main.js/main.css add the WordPress glue layer on top.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

define( 'TOYKINDANGEL_VERSION', '0.21.1' );
define( 'TOYKINDANGEL_DIR', get_template_directory() );
define( 'TOYKINDANGEL_URI', get_template_directory_uri() );

/**
 * Load modular includes.
 */
require_once TOYKINDANGEL_DIR . '/inc/customizer.php';
require_once TOYKINDANGEL_DIR . '/inc/seo-schema.php';
require_once TOYKINDANGEL_DIR . '/inc/seo-meta.php';
require_once TOYKINDANGEL_DIR . '/inc/performance.php';
require_once TOYKINDANGEL_DIR . '/inc/cache.php';
require_once TOYKINDANGEL_DIR . '/inc/breadcrumbs.php';
require_once TOYKINDANGEL_DIR . '/inc/product-card.php';
require_once TOYKINDANGEL_DIR . '/inc/front-data.php';
require_once TOYKINDANGEL_DIR . '/inc/front-ssr.php';
require_once TOYKINDANGEL_DIR . '/inc/cat-data.php';
require_once TOYKINDANGEL_DIR . '/inc/demo/data.php';
require_once TOYKINDANGEL_DIR . '/inc/demo-import.php';
require_once TOYKINDANGEL_DIR . '/inc/wc-hooks.php';
require_once TOYKINDANGEL_DIR . '/inc/cat-drawer.php';
require_once TOYKINDANGEL_DIR . '/inc/minicart.php';
require_once TOYKINDANGEL_DIR . '/inc/admin-meta.php';
require_once TOYKINDANGEL_DIR . '/inc/wishlist.php';
require_once TOYKINDANGEL_DIR . '/inc/blog.php';
require_once TOYKINDANGEL_DIR . '/inc/ajax-search.php';
require_once TOYKINDANGEL_DIR . '/inc/icons.php';
require_once TOYKINDANGEL_DIR . '/inc/rest-search.php';
require_once TOYKINDANGEL_DIR . '/inc/format.php';
require_once TOYKINDANGEL_DIR . '/inc/fa-text.php';
require_once TOYKINDANGEL_DIR . '/inc/wc-pages-fix.php';
require_once TOYKINDANGEL_DIR . '/inc/ig-stories.php';

/**
 * Theme supports.
 */
function toykindangel_setup() {
        load_theme_textdomain( 'toykindangel', TOYKINDANGEL_DIR . '/languages' );

        add_theme_support( 'title-tag' );
        add_theme_support( 'post-thumbnails' );
        add_theme_support( 'automatic-feed-links' );
        add_theme_support( 'responsive-embeds' );
        add_theme_support( 'align-wide' );
        add_theme_support( 'customize-selective-refresh-widgets' );
        add_theme_support(
                'html5',
                array( 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' )
        );
        add_theme_support(
                'custom-logo',
                array(
                        'height'      => 64,
                        'width'       => 200,
                        'flex-height' => true,
                        'flex-width'  => true,
                )
        );

        // Match the editor (visual) look to the frontend.
        add_editor_style( 'assets/css/editor.css' );

        register_nav_menus(
                array(
                        'primary'     => __( 'منوی اصلی', 'toykindangel' ),
                        'cats'        => __( 'منوی دسته‌بندی‌ها (دراور مرورگر)', 'toykindangel' ),
                        'footer'      => __( 'منوی فوتر', 'toykindangel' ),
                        'footer_col1' => __( 'فوتر — ستون راهنمای خرید', 'toykindangel' ),
                )
        );

        // WooCommerce declaration (standard + gallery features).
        add_theme_support( 'woocommerce' );
        add_theme_support( 'wc-product-gallery-zoom' );
        add_theme_support( 'wc-product-gallery-lightbox' );
        add_theme_support( 'wc-product-gallery-slider' );
}
add_action( 'after_setup_theme', 'toykindangel_setup' );

/**
 * Content width.
 */
function toykindangel_content_width() {
        $GLOBALS['content_width'] = 1200;
}
add_action( 'after_setup_theme', 'toykindangel_content_width', 0 );

/**
 * Enqueue styles and scripts — WordPress-first asset pipeline.
 *
 * v0.21.0 refactor — the demo dataset (data.js) and demo renderers are
 * gone; every section is server-rendered. The script chain is now:
 *   app.js    → interactions only (hero slider, PDP gallery, countdown
 *               timers); reads from the DOM, never from a demo dataset.
 *   main.js   → WordPress glue (cats-tree interactions, back-to-top,
 *               drawers, wishlist, cart, PDP, sticky header, …).
 *   live-search.js → header searchbox dropdown.
 *   wc.js     → WooCommerce glue (qty steppers, mini-cart drawer).
 *
 * CSS chain unchanged: base → demo → main → responsive → wc-pages/pdp.
 */
function toykindangel_scripts() {
        // WordPress standard header stylesheet (theme header only).
        // Uses the template URI so a child theme can safely replace
        // its own style.css without breaking this base layer.
        wp_enqueue_style( 'toykindangel-base', TOYKINDANGEL_URI . '/style.css', array(), TOYKINDANGEL_VERSION );

        // Demo stylesheet, byte-for-byte identical to the HTML demo.
        wp_enqueue_style( 'toykindangel-demo', TOYKINDANGEL_URI . '/assets/css/demo.css', array( 'toykindangel-base' ), TOYKINDANGEL_VERSION );

        // WordPress compatibility layer on top of the demo.
        wp_enqueue_style( 'toykindangel-main', TOYKINDANGEL_URI . '/assets/css/main.css', array( 'toykindangel-demo' ), TOYKINDANGEL_VERSION );

        // Tablet + desktop layer (mobile stays 1:1 with the demo).
        wp_enqueue_style( 'toykindangel-responsive', TOYKINDANGEL_URI . '/assets/css/responsive.css', array( 'toykindangel-main' ), TOYKINDANGEL_VERSION );

        // Cart / checkout / account dashboard styling (WooCommerce pages only).
        if ( function_exists( 'is_woocommerce' ) && ( is_cart() || is_checkout() || is_account_page() || is_wc_endpoint_url() || is_order_received_page() ) ) {
                wp_enqueue_style( 'toykindangel-wc-pages', TOYKINDANGEL_URI . '/assets/css/wc-pages.css', array( 'toykindangel-responsive' ), TOYKINDANGEL_VERSION );
        }

        // WooCommerce glue: qty steppers, login tabs, mini-cart drawer open on AJAX add.
        wp_enqueue_script( 'toykindangel-wc', TOYKINDANGEL_URI . '/assets/js/wc.js', array( 'toykindangel-main' ), TOYKINDANGEL_VERSION, true );
        wp_add_inline_script(
                'toykindangel-wc',
                'window.TKA_CART_URL=' . wp_json_encode( function_exists( 'toykindangel_cart_url' ) ? toykindangel_cart_url() : '' ) . ';'
                        . 'window.TKA_CHECKOUT_URL=' . wp_json_encode( function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '' ) . ';',
                'before'
        );

        /*
         * v0.21.0 refactor — WordPress-first script loading.
         *
         * Previously the homepage and /categories/ pages also loaded a demo
         * dataset (data.js) and the full demo renderer (app.js) so JS could
         * paint sections from window.TKA. All those sections are now server-
         * rendered by inc/front-ssr.php, so:
         *   - data.js is no longer enqueued at all (the file is kept on disk
         *     only as a historical reference for the demo-importer source).
         *   - app.js is loaded only where its three real interactions are
         *     needed: hero slider (homepage), gallery (PDP), timers (any page
         *     with [data-timer]). It no longer reads window.TKA.
         *   - main.js is the WordPress glue layer, loaded everywhere.
         */
        $tka_needs_app = is_front_page()
                || is_page( 'categories' )
                || is_page_template( 'page-categories.php' )
                || is_singular( 'product' );

        if ( $tka_needs_app ) {
                wp_enqueue_script( 'toykindangel-app', TOYKINDANGEL_URI . '/assets/js/app.js', array(), TOYKINDANGEL_VERSION, true );
        }

        // WordPress glue layer (always last in the theme chain).
        wp_enqueue_script(
                'toykindangel-main',
                TOYKINDANGEL_URI . '/assets/js/main.js',
                $tka_needs_app ? array( 'toykindangel-app' ) : array(),
                TOYKINDANGEL_VERSION,
                true
        );
        wp_add_inline_script(
                'toykindangel-main',
                'window.TKA_BASE=' . wp_json_encode( TOYKINDANGEL_URI ) . ';',
                'before'
        );
        /*
         * v0.21.0 — a tiny global config so the cats-drawer search (which
         * runs on every page via footer.php) can jump to the WP search page
         * without needing the full homepage/categories TKA_WP payload.
         */
        wp_add_inline_script(
                'toykindangel-main',
                'window.TKA_WP=Object.assign({searchUrl:' . wp_json_encode( home_url( '/' ) ) . '}, window.TKA_WP||{});',
                'before'
        );

        // 5) Live search (header searchbox dropdown) — REST-first, ajax fallback.
        wp_enqueue_script( 'toykindangel-live-search', TOYKINDANGEL_URI . '/assets/js/live-search.js', array( 'toykindangel-main' ), TOYKINDANGEL_VERSION, true );
        wp_add_inline_script(
                'toykindangel-live-search',
                'window.TKA_LIVE=' . wp_json_encode(
                        array(
                                'url'     => admin_url( 'admin-ajax.php?action=tka_live_search' ),
                                'restUrl' => rest_url( 'toykindangel/v1/search' ),
                                'min'     => 2,
                                'loading' => __( 'در حال جستجو…', 'toykindangel' ),
                                'error'   => __( 'خطا در دریافت نتایج؛ دوباره تلاش کنید.', 'toykindangel' ),
                        )
                ) . ';',
                'before'
        );

        if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
                wp_enqueue_script( 'comment-reply' );
        }
}
add_action( 'wp_enqueue_scripts', 'toykindangel_scripts' );

/**
 * Performance: remove emoji scripts & other head noise (speed requirement).
 */
function toykindangel_head_cleanup() {
        remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
        remove_action( 'wp_print_styles', 'print_emoji_styles' );
        remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
        remove_action( 'admin_print_styles', 'print_emoji_styles' );
        remove_action( 'wp_head', 'wp_generator' );
        remove_action( 'wp_head', 'wlwmanifest_link' );
        remove_action( 'wp_head', 'rsd_link' );
}
add_action( 'init', 'toykindangel_head_cleanup' );

/**
 * Widget areas (available for shop/blog layouts; the demo-styled footer
 * uses its own markup).
 */
function toykindangel_widgets_init() {
        register_sidebar(
                array(
                        'name'          => __( 'سایدبار فروشگاه', 'toykindangel' ),
                        'id'            => 'shop-sidebar',
                        'description'   => __( 'فیلترها و ویجت‌های صفحه دسته‌بندی محصولات', 'toykindangel' ),
                        'before_widget' => '<section id="%1$s" class="widget tka-card %2$s">',
                        'after_widget'  => '</section>',
                        'before_title'  => '<h3 class="widget-title">',
                        'after_title'   => '</h3>',
                )
        );

        register_sidebar(
                array(
                        'name'          => __( 'ویجت‌های فوتر', 'toykindangel' ),
                        'id'            => 'footer-widgets',
                        'description'   => __( 'ستون‌های فوتر سایت', 'toykindangel' ),
                        'before_widget' => '<section id="%1$s" class="widget %2$s">',
                        'after_widget'  => '</section>',
                        'before_title'  => '<h3 class="widget-title">',
                        'after_title'   => '</h3>',
                )
        );
}
add_action( 'widgets_init', 'toykindangel_widgets_init' );

/**
 * URL helpers shared by header/footer (WooCommerce-aware with graceful
 * fallbacks while WooCommerce is not installed yet).
 */

/**
 * Categories / shop URL.
 *
 * @return string
 */
function toykindangel_categories_url() {
        if ( function_exists( 'wc_get_page_id' ) ) {
                $tka_shop_id = wc_get_page_id( 'shop' );
                if ( $tka_shop_id > 0 && get_post( $tka_shop_id ) ) {
                        return get_permalink( $tka_shop_id );
                }
        }

        $tka_page = get_page_by_path( 'categories' );
        if ( $tka_page instanceof WP_Post ) {
                return get_permalink( $tka_page );
        }

        return home_url( '/' );
}

/**
 * Cart URL (falls back to home until WooCommerce is active).
 *
 * @return string
 */
function toykindangel_cart_url() {
        if ( function_exists( 'wc_get_page_id' ) ) {
                $tka_cart_id = wc_get_page_id( 'cart' );
                if ( $tka_cart_id > 0 && get_post( $tka_cart_id ) ) {
                        return get_permalink( $tka_cart_id );
                }
        }

        return home_url( '/' );
}

/**
 * My-account / login URL.
 *
 * @return string
 */
function toykindangel_account_url() {
        if ( function_exists( 'wc_get_page_id' ) ) {
                $tka_account_id = wc_get_page_id( 'myaccount' );
                if ( $tka_account_id > 0 && get_post( $tka_account_id ) ) {
                        return get_permalink( $tka_account_id );
                }
        }

        return wp_login_url();
}

/**
 * Fallback primary menu when no menu is assigned yet.
 * Used by both the mobile shortcut and the desktop nav bar.
 */
function toykindangel_menu_fallback() {
        $tka_items = array(
                home_url( '/' )                          => __( 'خانه', 'toykindangel' ),
                toykindangel_categories_url()            => __( 'دسته‌بندی‌ها', 'toykindangel' ),
                toykindangel_cart_url()                  => __( 'سبد خرید', 'toykindangel' ),
                toykindangel_account_url()               => __( 'حساب کاربری', 'toykindangel' ),
        );
        echo '<ul class="header__menu">';
        foreach ( $tka_items as $tka_url => $tka_label ) {
                echo '<li><a href="' . esc_url( $tka_url ) . '">' . esc_html( $tka_label ) . '</a></li>';
        }
        echo '</ul>';
}

/* TKA 0.16.0 (18-d): stylesheet صفحهٔ دسته‌بندی‌ها (Category Hub) */
add_action(
        'wp_enqueue_scripts',
        function () {
                if ( is_page( 'categories' ) || is_page_template( 'page-categories.php' ) ) {
                        wp_enqueue_style(
                                'toykindangel-cats',
                                TOYKINDANGEL_URI . '/assets/css/cats-page.css',
                                array( 'toykindangel-responsive' ),
                                TOYKINDANGEL_VERSION
                        );
                }
        },
        20
);

/* TKA 0.16.0 (18-e): commerce layer — festival (جشنواره) badge + product data CSS */
require_once TOYKINDANGEL_DIR . '/inc/festival.php';
add_action(
        'wp_enqueue_scripts',
        function () {
                wp_enqueue_style(
                        'toykindangel-commerce',
                        TOYKINDANGEL_URI . '/assets/css/commerce.css',
                        array( 'toykindangel-main' ),
                        '16.0'
                );
        },
        20
);

/* TKA 0.16.0 (18-f): PDP professional sections — مشخصات/توضیحات/دیدگاه‌ها/اشتراک‌گذاری */
add_action(
        'wp_enqueue_scripts',
        function () {
                if ( function_exists( 'is_product' ) && is_product() ) {
                        wp_enqueue_style(
                                'toykindangel-pdp',
                                TOYKINDANGEL_URI . '/assets/css/pdp.css',
                                array( 'toykindangel-responsive' ),
                                TOYKINDANGEL_VERSION
                        );
                }
        },
        20
);

/* TKA 0.18.0: سفارشی‌سازی‌های اختصاصی پروژه — آخرین لایهٔ CSS (همیشه بارگذاری می‌شود).
 * نسخه با filemtime ساخته می‌شود تا با هر آپلود فایل، کش مرورگر خودبه‌خود بشکند. */
add_action(
        'wp_enqueue_scripts',
        function () {
                $tka_custom_file = TOYKINDANGEL_DIR . '/assets/css/custom.css';
                if ( ! file_exists( $tka_custom_file ) ) {
                        return;
                }
                wp_enqueue_style(
                        'toykindangel-custom',
                        TOYKINDANGEL_URI . '/assets/css/custom.css',
                        array( 'toykindangel-responsive' ),
                        (string) filemtime( $tka_custom_file )
                );
        },
        30
);
