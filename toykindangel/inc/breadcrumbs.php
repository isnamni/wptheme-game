<?php
/**
 * Breadcrumb trail (visual) — used across templates.
 * Falls back silently on the front page.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Render breadcrumb nav.
 */
function toykindangel_breadcrumbs() {
        if ( is_front_page() ) {
                return;
        }

        $items = array(
                array(
                        'label' => __( 'خانه', 'toykindangel' ),
                        'url'   => home_url( '/' ),
                ),
        );

        if ( is_singular() ) {
                $post_type = get_post_type();
                if ( 'post' === $post_type ) {
                        $blog_page = get_option( 'page_for_posts' );
                        if ( $blog_page ) {
                                $items[] = array(
                                        'label' => get_the_title( $blog_page ),
                                        'url'   => get_permalink( $blog_page ),
                                );
                        }
                } elseif ( 'page' !== $post_type && post_type_exists( $post_type ) ) {
                        $pto = get_post_type_object( $post_type );
                        if ( $pto && ! empty( $pto->has_archive ) ) {
                                $items[] = array(
                                        'label' => $pto->labels->name,
                                        'url'   => get_post_type_archive_link( $post_type ),
                                );
                        }
                }
                $items[] = array( 'label' => get_the_title() );
        } elseif ( is_search() ) {
                $items[] = array( 'label' => __( 'نتایج جستجو', 'toykindangel' ) );
        } elseif ( is_archive() ) {
                $tka_obj = get_queried_object();
                $tka_is_shop_tax = $tka_obj instanceof WP_Term && in_array( $tka_obj->taxonomy, array( 'product_cat', 'product_tag', 'product_brand', 'tka_brand' ), true );
                if ( $tka_is_shop_tax ) {
                        // Shop root crumb, then parent chain, then term name.
                        if ( function_exists( 'wc_get_page_id' ) && wc_get_page_id( 'shop' ) > 0 && get_post( wc_get_page_id( 'shop' ) ) ) {
                                $items[] = array(
                                        'label' => get_the_title( wc_get_page_id( 'shop' ) ),
                                        'url'   => get_permalink( wc_get_page_id( 'shop' ) ),
                                );
                        }
                        if ( 'product_cat' === $tka_obj->taxonomy && $tka_obj->parent ) {
                                $tka_ancestors = array_reverse( get_ancestors( $tka_obj->term_id, 'product_cat' ) );
                                foreach ( $tka_ancestors as $tka_anc ) {
                                        $tka_anc_term = get_term( $tka_anc, 'product_cat' );
                                        if ( $tka_anc_term && ! is_wp_error( $tka_anc_term ) ) {
                                                $items[] = array(
                                                        'label' => $tka_anc_term->name,
                                                        'url'   => get_term_link( $tka_anc_term ),
                                                );
                                        }
                                }
                        }
                        $items[] = array( 'label' => $tka_obj->name );
                } else {
                        $items[] = array( 'label' => wp_strip_all_tags( get_the_archive_title() ) );
                }
        } elseif ( is_404() ) {
                $items[] = array( 'label' => __( 'صفحه یافت نشد', 'toykindangel' ) );
        }

        if ( count( $items ) < 2 ) {
                return;
        }

        echo '<nav class="tka-breadcrumbs tka-container" aria-label="' . esc_attr__( 'مسیر صفحه', 'toykindangel' ) . '"><ol>';
        $last = count( $items ) - 1;
        foreach ( $items as $i => $item ) {
                if ( $i === $last || empty( $item['url'] ) ) {
                        echo '<li aria-current="page">' . esc_html( $item['label'] ) . '</li>';
                } else {
                        echo '<li><a href="' . esc_url( $item['url'] ) . '">' . esc_html( $item['label'] ) . '</a><span aria-hidden="true"> / </span></li>';
                }
        }
        echo '</ol></nav>';
}
