<?php
/**
 * Performance helpers: resource hints, defer scripts.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Preload the web fonts the demo actually paints with (LCP text) and the
 * first hero/strip image when configured in the Customizer.
 *
 * Only real above-the-fold assets are preloaded — everything else keeps
 * normal priority so bandwidth is not wasted.
 */
function toykindangel_preload_assets() {
        if ( is_admin() ) {
                return;
        }

        $base = TOYKINDANGEL_URI . '/assets/fonts';

        /*
         * Single optimized variable font (وزیرمتن / Vazirmatn) covers all
         * weights 100–900 in one woff2 file — exactly one font download.
         */
        $fonts = array(
                'Vazirmatn-Variable.woff2' => 'font/woff2',
        );

        foreach ( $fonts as $file => $type ) {
                printf(
                        '<link rel="preload" href="%s" as="font" type="%s" crossorigin>' . "\n",
                        esc_url( $base . '/' . $file ),
                        esc_attr( $type )
                );
        }

        /* Above-the-fold images from the Customizer (first paint candidates). */
        $candidates = array(
                get_theme_mod( 'tka_strip_img' ),
                get_theme_mod( 'tka_hero_1_img' ),
        );

        foreach ( $candidates as $img ) {
                if ( $img ) {
                        printf(
                                '<link rel="preload" href="%s" as="image" fetchpriority="high">' . "\n",
                                esc_url( $img )
                        );
                }
        }
}
add_action( 'wp_head', 'toykindangel_preload_assets', 2 );

/**
 * Defer the theme scripts (all dependency-free, loaded in the footer).
 *
 * @param string $tag    Script tag HTML.
 * @param string $handle Script handle.
 */
function toykindangel_defer_scripts( $tag, $handle ) {
        $defer = array(
                'toykindangel-data',
                'toykindangel-app',
                'toykindangel-main',
        );

        if ( in_array( $handle, $defer, true ) && ! is_admin() ) {
                $tag = str_replace( ' src=', ' defer src=', $tag );
        }

        return $tag;
}
add_filter( 'script_loader_tag', 'toykindangel_defer_scripts', 10, 2 );

/**
 * Trim default WP bloat: emojis handled in functions.php; here disable
 * global styles/inline SVG noise of block themes we don't need (classic mode).
 */
function toykindangel_remove_block_bloat() {
        wp_deregister_style( 'wp-block-library-theme' );
}
add_action( 'wp_enqueue_scripts', 'toykindangel_remove_block_bloat', 100 );
