<?php
/**
 * Server-rendered demo product card + WooCommerce glue.
 *
 * Replicates the demo app.js `pcard()` markup 1:1 so archives look
 * identical to the demo rails. demo.css provides all styles; main.css
 * only adds the WP glue (grid, add-to-cart button, sort bar).
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Demo-style number formatting (Latin digits + comma thousands).
 *
 * Mirrors app.js fmt() so server-rendered cards match the demo.
 *
 * @param float|int $number Number.
 * @return string
 */
function toykindangel_fmt( $number ) {
        $tka_formatted = number_format( (float) $number, 0, '.', ',' );
        return function_exists( 'toykindangel_fa_num' ) ? toykindangel_fa_num( $tka_formatted ) : $tka_formatted;
}

/**
 * Active price + regular price used by the card badges.
 *
 * Task 18-e: variable products report the min variation price; the raw
 * get_regular_price() is empty for WC_Product_Variable, so the min
 * variation regular price is used instead — this makes the % off badge
 * and the struck-through old price work for variable products too.
 * get_variation_prices(true) already honours active sale-date windows.
 *
 * @param WC_Product $product Product object.
 * @return float[] { price, regular }
 */
function toykindangel_pcard_price_parts( $product ) {
        $tka_price   = (float) $product->get_price();
        $tka_regular = (float) $product->get_regular_price();

        if ( $product->is_type( 'variable' ) ) {
                $tka_vp = $product->get_variation_prices( true );
                if ( ! empty( $tka_vp['price'] ) ) {
                        $tka_price   = (float) min( $tka_vp['price'] );
                        $tka_regular = (float) min( $tka_vp['regular_price'] );
                }
        }

        return array( $tka_price, $tka_regular );
}

/**
 * Render one demo-style product card.
 *
 * @param WC_Product $product Product object.
 * @return string HTML.
 */
function toykindangel_pcard( $product ) {
        if ( ! $product instanceof WC_Product ) {
                return '';
        }

        list( $tka_price, $tka_regular ) = toykindangel_pcard_price_parts( $product );
        $tka_off     = 0;
        if ( $tka_regular > 0 && $tka_price > 0 && $tka_price < $tka_regular ) {
                $tka_off = (int) round( ( ( $tka_regular - $tka_price ) / $tka_regular ) * 100 );
        }

        $tka_bnpl = wp_validate_boolean( get_post_meta( $product->get_id(), 'tka_bnpl', true ) );

        $tka_img_id  = $product->get_image_id();
        $tka_img_url = $tka_img_id ? wp_get_attachment_image_url( $tka_img_id, 'woocommerce_thumbnail' ) : '';
        if ( ! $tka_img_url && function_exists( 'wc_placeholder_img_src' ) ) {
                $tka_img_url = wc_placeholder_img_src( 'woocommerce_thumbnail' );
        }

        /* Intrinsic dimensions prevent layout shift (CLS) while loading. */
        $tka_img_wh = '';
        if ( $tka_img_id ) {
                $tka_meta = wp_get_attachment_metadata( $tka_img_id );
                if ( ! empty( $tka_meta['width'] ) && ! empty( $tka_meta['height'] ) ) {
                        $tka_img_wh = sprintf( ' width="%d" height="%d"', (int) $tka_meta['width'], (int) $tka_meta['height'] );
                }
        }

        $tka_name = $product->get_name();
        /* translators: %s: product name for the image alt attribute. */
        $tka_alt = sprintf( __( 'تصویر %s', 'toykindangel' ), $tka_name );

        $html  = '<a class="pcard" href="' . esc_url( $product->get_permalink() ) . '">';
        $html .= '<div class="pcard__media">';
        if ( $tka_img_url ) {
                $html .= '<img src="' . esc_url( $tka_img_url ) . '" alt="' . esc_attr( $tka_alt ) . '"' . $tka_img_wh . ' loading="lazy" decoding="async" referrerpolicy="no-referrer" onerror="this.remove()">';
        }
        $html .= '<span class="ph"><svg class="ic" aria-hidden="true"><use href="#i-cat-camera"></use></svg></span>';
        if ( $tka_off > 0 ) {
                $html .= '<span class="pcard__off num">' . esc_html( (string) $tka_off ) . '%</span>';
        }
        /* TKA (18-e): «جشنواره» campaign ribbon — distinct corner from the red % badge. */
        if ( function_exists( 'toykindangel_festival_active' ) && function_exists( 'toykindangel_is_festival_product' )
                && toykindangel_festival_active() && toykindangel_is_festival_product( $product ) ) {
                $html .= '<span class="pcard__festival"><svg class="ic" aria-hidden="true"><use href="#i-fire"></use></svg>'
                        . esc_html__( 'جشنواره', 'toykindangel' ) . '</span>';
        }
        $html .= '</div>';
        if ( $tka_bnpl ) {
                $html .= '<span class="pcard__bnpl"><svg class="ic" aria-hidden="true"><use href="#i-tag"></use></svg>' . esc_html__( 'خرید قسطی', 'toykindangel' ) . '</span>';
        }
        $html .= '<div class="pcard__name">' . esc_html( $tka_name ) . '</div>';
        $html .= '<div class="pcard__prices">';
        if ( $tka_off > 0 && $tka_regular > 0 ) {
                $html .= '<span class="pcard__old num">' . esc_html( toykindangel_fmt( $tka_regular ) ) . '</span>';
        } else {
                $html .= '<span class="pcard__old"></span>';
        }
        $html .= '<div class="pcard__price num"><span>' . esc_html( toykindangel_fmt( $tka_price ) ) . '</span><small>' . esc_html__( 'تومان', 'toykindangel' ) . '</small></div>';
        $html .= '</div>';
        $html .= '</a>';

        return $html;
}

/**
 * Wrap a card with an AJAX add-to-cart button (outside the anchor).
 *
 * @param WC_Product $product Product object.
 * @return string HTML.
 */
function toykindangel_pcard_wrap( $product ) {
        $html = '<div class="pcard-wrap">';
        $html .= toykindangel_pcard( $product );

        if ( $product->is_type( 'simple' ) && $product->is_purchasable() && $product->is_in_stock() ) {
                $html .= '<a href="' . esc_url( $product->add_to_cart_url() ) . '"'
                        . ' data-quantity="1"'
                        . ' data-product_id="' . esc_attr( (string) $product->get_id() ) . '"'
                        . ' data-product_sku="' . esc_attr( $product->get_sku() ) . '"'
                        . ' rel="nofollow"'
                        . ' class="pcard__atc add_to_cart_button ajax_add_to_cart"'
                        . ' aria-label="' . esc_attr__( 'افزودن به سبد خرید', 'toykindangel' ) . '">'
                        . '<svg class="ic" aria-hidden="true"><use href="#i-cart"></use></svg>'
                        . '</a>';
        } elseif ( $product->is_type( 'variable' ) ) {
                /* TKA fix (15-c): variable products get a "choose options" link instead of add-to-cart. */
                $html .= '<a href="' . esc_url( $product->get_permalink() ) . '"'
                        . ' class="pcard__atc pcard__atc--choose"'
                        . ' aria-label="' . esc_attr__( 'انتخاب گزینه‌ها', 'toykindangel' ) . '">'
                        . esc_html__( 'انتخاب گزینه‌ها', 'toykindangel' )
                        . '</a>';
        }

        $html .= '</div>';
        return $html;
}

/**
 * Keep the header cart badge in sync after AJAX add-to-cart.
 *
 * @param array $fragments Cart fragments.
 * @return array
 */
function toykindangel_cart_fragment( $fragments ) {
        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
                return $fragments;
        }
        ob_start();
        echo '<span class="badge-count num">' . esc_html( WC()->cart->get_cart_contents_count() ) . '</span>';
        $fragments['span.badge-count'] = ob_get_clean();
        return $fragments;
}
add_filter( 'woocommerce_add_to_cart_fragments', 'toykindangel_cart_fragment' );

/**
 * Show «تومان» instead of «ریال» for the IRR currency.
 *
 * Iranian shops price in Toman; WooCommerce only ships IRR, so the
 * symbol is swapped at display time (amounts are untouched).
 *
 * @param string $symbol   Currency symbol.
 * @param string $currency Currency code.
 * @return string
 */
function toykindangel_currency_symbol( $symbol, $currency ) {
        if ( 'IRR' === $currency ) {
                return 'تومان';
        }
        return $symbol;
}
add_filter( 'woocommerce_currency_symbol', 'toykindangel_currency_symbol', 10, 2 );
