<?php
/**
 * Mini-cart drawer — slide-in cart for every page (PDP included).
 *
 * Printed once from wp_footer so it also exists on the bare PDP/footer
 * pages. The inner markup is registered as a WooCommerce cart fragment
 * (.tka-minicart__inner), so AJAX add-to-cart / remove keeps it and the
 * header badge in sync without a reload. The drawer opens with the same
 * main.js system as the menu/cats drawers (data-tka-drawer="minicart").
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Render the mini-cart inner markup (items + totals + actions).
 *
 * @param bool $do_echo Print (true) or return (false).
 * @return string HTML when $do_echo is false.
 */
function toykindangel_minicart_inner( $do_echo = true ) {
        $html = '';

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
                if ( $do_echo ) {
                        return '';
                }
                return $html;
        }

        $html .= '<div class="tka-minicart__inner">';

        if ( WC()->cart->is_empty() ) {
                $html .= '<div class="tka-minicart__empty">';
                $html .= '<span class="tka-minicart__empty-art" aria-hidden="true"><svg class="ic"><use href="#i-cart"></use></svg></span>';
                $html .= '<p>' . esc_html__( 'سبد خرید شما خالی است', 'toykindangel' ) . '</p>';
                $html .= '<a class="tka-minicart__cta" href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'رفتن به فروشگاه', 'toykindangel' ) . '</a>';
                $html .= '</div>';
        } else {

                $html .= '<ul class="tka-minicart__items">';
                foreach ( WC()->cart->get_cart() as $tka_key => $tka_item ) {
                        $tka_product = apply_filters( 'woocommerce_cart_item_product', $tka_item['data'], $tka_item, $tka_key );
                        if ( ! $tka_product || ! $tka_product->exists() || $tka_item['quantity'] <= 0 ) {
                                continue;
                        }

                        $tka_thumb_id = $tka_product->get_image_id();
                        $tka_thumb    = $tka_thumb_id
                                ? wp_get_attachment_image_url( $tka_thumb_id, 'woocommerce_thumbnail' )
                                : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_thumbnail' ) : '' );

                        $html .= '<li class="tka-minicart__item">';
                        $html .= '<a class="tka-minicart__thumb" href="' . esc_url( $tka_product->get_permalink() ) . '">';
                        if ( $tka_thumb ) {
                                $html .= '<img src="' . esc_url( $tka_thumb ) . '" alt="' . esc_attr( $tka_product->get_name() ) . '" loading="lazy" decoding="async">';
                        }
                        $html .= '</a>';
                        $html .= '<div class="tka-minicart__info">';
                        $html .= '<a class="tka-minicart__name" href="' . esc_url( $tka_product->get_permalink() ) . '">' . esc_html( $tka_product->get_name() ) . '</a>';
                        $html .= '<span class="tka-minicart__qty num">' . esc_html( $tka_item['quantity'] ) . ' × ' . wp_kses_post( WC()->cart->get_product_price( $tka_product ) ) . '</span>';
                        $html .= '</div>';
                        $html .= '<a class="tka-minicart__remove" href="' . esc_url( wc_get_cart_remove_url( $tka_key ) ) . '" aria-label="' . esc_attr__( 'حذف از سبد خرید', 'toykindangel' ) . '"><svg class="ic" aria-hidden="true"><use href="#i-plus"></use></svg></a>';
                        $html .= '</li>';
                }
                $html .= '</ul>';

                $html .= '<div class="tka-minicart__footer">';
                $html .= '<div class="tka-minicart__total"><span>' . esc_html__( 'مبلغ قابل پرداخت', 'toykindangel' ) . '</span><strong class="num">' . wp_kses_post( WC()->cart->get_cart_subtotal() ) . '</strong></div>';
                $html .= '<div class="tka-minicart__actions">';
                $html .= '<a class="tka-minicart__cta" href="' . esc_url( wc_get_checkout_url() ) . '">' . esc_html__( 'تسویه حساب', 'toykindangel' ) . '</a>';
                $html .= '<a class="tka-minicart__ghost" href="' . esc_url( wc_get_cart_url() ) . '">' . esc_html__( 'مشاهدهٔ سبد', 'toykindangel' ) . '</a>';
                $html .= '</div>';
                $html .= '</div>';
        }

        $html .= '</div>';

        if ( $do_echo ) {
                echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above with esc_html/wp_kses_post.
                return '';
        }

        return $html;
}

/**
 * Print the mini-cart drawer + shared backdrop (bare pages only get the
 * backdrop here; full pages already print it from footer.php, which sets
 * $GLOBALS['tka_backdrop_printed'] to avoid a duplicate id).
 */
function toykindangel_minicart_footer() {
        if ( ! function_exists( 'WC' ) ) {
                return;
        }
        ?>
        <?php if ( empty( $GLOBALS['tka_backdrop_printed'] ) ) : ?>
                <div class="tka-drawer__backdrop" id="tka-drawer-backdrop" hidden></div>
        <?php endif; ?>

        <div class="tka-drawer tka-drawer--minicart" id="tka-minicart-drawer" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'سبد خرید', 'toykindangel' ); ?>" hidden>
                <div class="tka-drawer__head">
                        <span class="tka-drawer__title">
                                <svg class="ic" aria-hidden="true"><use href="#i-cart"></use></svg>
                                <?php esc_html_e( 'سبد خرید', 'toykindangel' ); ?>
                        </span>
                        <button type="button" class="tka-drawer__close" data-tka-close aria-label="<?php esc_attr_e( 'بستن', 'toykindangel' ); ?>">
                                <svg class="ic" aria-hidden="true"><use href="#i-plus"></use></svg>
                        </button>
                </div>
                <?php toykindangel_minicart_inner( true ); ?>
        </div>
        <?php
}
add_action( 'wp_footer', 'toykindangel_minicart_footer', 25 );

/**
 * Register the mini-cart body as a cart fragment (AJAX sync).
 *
 * @param array $fragments Fragments.
 * @return array
 */
function toykindangel_minicart_fragment( $fragments ) {
        $tka_html = toykindangel_minicart_inner( false );
        if ( '' !== $tka_html ) {
                $fragments['div.tka-minicart__inner'] = $tka_html;
        }
        return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'toykindangel_minicart_fragment' );
