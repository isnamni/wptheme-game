<?php
/**
 * Cart page — demo-style RTL redesign.
 *
 * Keeps every core field name (cart[KEY][qty], update_cart, apply_coupon,
 * coupon_code, _wpnonce …) so WooCommerce's cart processing, coupons and
 * the quantity AJAX stay 100% functional. Only the markup around them is
 * themed with the demo design tokens.
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_cart' );
?>
<div class="tka-cart">

        <form class="tka-cart__form woocommerce-cart-form" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">

                <?php do_action( 'woocommerce_before_cart_table' ); ?>

                <div class="tka-cart__card">
                        <header class="tka-cart__head">
                                <svg class="ic" aria-hidden="true"><use href="#i-cart"></use></svg>
                                <h1 class="tka-cart__title"><?php esc_html_e( 'سبد خرید', 'toykindangel' ); ?></h1>
                                <span class="tka-cart__count num">
                                        <?php
                                        /* translators: %d: number of items in the cart. */
                                        printf( esc_html( _n( '%d کالا', '%d کالا', WC()->cart->get_cart_contents_count(), 'toykindangel' ) ), (int) WC()->cart->get_cart_contents_count() );
                                        ?>
                                </span>
                        </header>

                        <ul class="tka-cart__items woocommerce-cart-form__contents">
                                <?php
                                do_action( 'woocommerce_before_cart_contents' );

                                foreach ( WC()->cart->get_cart() as $tka_key => $tka_item ) {
                                        $tka_product = apply_filters( 'woocommerce_cart_item_product', $tka_item['data'], $tka_item, $tka_key );
                                        $tka_visible = apply_filters( 'woocommerce_cart_item_visible', true, $tka_item, $tka_key );

                                        if ( ! $tka_product || ! $tka_visible || ! $tka_product->exists() || $tka_item['quantity'] <= 0 ) {
                                                continue;
                                        }

                                        $tka_permalink = apply_filters( 'woocommerce_cart_item_permalink', $tka_product->get_permalink( $tka_item ), $tka_item, $tka_key );
                                        $tka_thumb_id  = $tka_product->get_image_id();
                                        $tka_thumb     = $tka_thumb_id
                                                ? wp_get_attachment_image_url( $tka_thumb_id, 'woocommerce_thumbnail' )
                                                : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src( 'woocommerce_thumbnail' ) : '' );
                                        $tka_name      = apply_filters( 'woocommerce_cart_item_name', $tka_product->get_name(), $tka_item, $tka_key );
                                        $tka_price     = apply_filters( 'woocommerce_cart_item_price', WC()->cart->get_product_price( $tka_product ), $tka_item, $tka_key );
                                        $tka_subtotal  = apply_filters( 'woocommerce_cart_item_subtotal', WC()->cart->get_product_subtotal( $tka_product, $tka_item['quantity'] ), $tka_item, $tka_key );
                                        $tka_remove    = wc_get_cart_remove_url( $tka_key );
                                        $tka_max       = $tka_product->get_max_purchase_quantity();
                                        $tka_args      = array(
                                                'input_name'   => 'cart[' . $tka_key . '][qty]',
                                                'input_value'  => $tka_item['quantity'],
                                                'max_value'    => ( 0 < $tka_max ) ? $tka_max : '',
                                                'min_value'    => '0',
                                                'product_name' => $tka_name,
                                        );
                                        ?>
                                        <li class="tka-cart__item woocommerce-cart-form__cart-item <?php echo esc_attr( apply_filters( 'woocommerce_cart_item_class', 'cart_item', $tka_item, $tka_key ) ); ?>">

                                                <a class="tka-cart__thumb" href="<?php echo esc_url( $tka_permalink ); ?>">
                                                        <?php if ( $tka_thumb ) : ?>
                                                                <img src="<?php echo esc_url( $tka_thumb ); ?>"
                                                                        <?php /* translators: %s: product name */ ?>
                                                                        alt="<?php echo esc_attr( sprintf( __( 'تصویر %s', 'toykindangel' ), wp_strip_all_tags( $tka_name ) ) ); ?>"
                                                                        loading="lazy" decoding="async" onerror="this.remove()">
                                                        <?php endif; ?>
                                                </a>

                                                <div class="tka-cart__info">
                                                        <a class="tka-cart__name" href="<?php echo esc_url( $tka_permalink ); ?>"><?php echo esc_html( wp_strip_all_tags( $tka_name ) ); ?></a>

                                                        <?php
                                                        /* Variations + custom item data (same data WC core prints). */
                                                        echo wc_get_formatted_cart_item_data( $tka_item ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                                        ?>

                                                        <div class="tka-cart__price num"><?php echo wp_kses_post( $tka_price ); ?></div>

                                                        <div class="tka-cart__row">
                                                                <div class="tka-cart__qty">
                                                                        <?php
                                                                        if ( $tka_product->is_sold_individually() ) {
                                                                                printf( '<input type="hidden" name="cart[%s][qty]" value="1">', esc_attr( $tka_key ) );
                                                                                echo '<span class="tka-cart__solo num">۱×</span>';
                                                                        } else {
                                                                                woocommerce_quantity_input( $tka_args, $tka_product );
                                                                        }
                                                                        ?>
                                                                </div>
                                                                <div class="tka-cart__subtotal num"><?php echo wp_kses_post( $tka_subtotal ); ?></div>
                                                        </div>
                                                </div>

                                                <a class="tka-cart__remove" href="<?php echo esc_url( $tka_remove ); ?>"
                                                        aria-label="<?php esc_attr_e( 'حذف از سبد خرید', 'toykindangel' ); ?>">
                                                        <svg class="ic" aria-hidden="true"><use href="#i-plus"></use></svg>
                                                </a>
                                        </li>
                                        <?php
                                }

                                do_action( 'woocommerce_after_cart_contents' );
                                ?>
                        </ul>

                        <?php do_action( 'woocommerce_after_cart_table' ); ?>

                        <footer class="tka-cart__foot">
                                <button type="submit" class="button tka-cart__update" name="update_cart"
                                        value="<?php esc_attr_e( 'به‌روزرسانی سبد خرید', 'toykindangel' ); ?>">
                                        <svg class="ic" aria-hidden="true"><use href="#i-return"></use></svg>
                                        <?php esc_html_e( 'به‌روزرسانی سبد', 'toykindangel' ); ?>
                                </button>
                                <?php wp_nonce_field( 'woocommerce-cart', 'woocommerce-cart-nonce' ); ?>
                        </footer>
                </div>
        </form>

        <?php
        /* Coupon row (core field names — coupons process through this form). */
        if ( wc_coupons_enabled() ) {
                ?>
                <form class="tka-coupon" action="<?php echo esc_url( wc_get_cart_url() ); ?>" method="post">
                        <div class="tka-coupon__field">
                                <svg class="ic" aria-hidden="true"><use href="#i-tag"></use></svg>
                                <input type="text" name="coupon_code" class="input-text" id="coupon_code"
                                        placeholder="<?php esc_attr_e( 'کد تخفیف دارید؟ اینجا وارد کنید', 'toykindangel' ); ?>">
                        </div>
                        <button type="submit" class="button tka-coupon__btn" name="apply_coupon" value="<?php esc_attr_e( 'اعمال کد', 'toykindangel' ); ?>">
                                <?php esc_html_e( 'اعمال کد', 'toykindangel' ); ?>
                        </button>
                        <?php do_action( 'woocommerce_cart_coupon' ); ?>
                </form>
                <?php
        }

        do_action( 'woocommerce_cart_actions' );
        ?>

        <aside class="tka-totals cart-collaterals cart_totals">
                <?php do_action( 'woocommerce_before_cart_collaterals' ); ?>
                <div class="tka-totals__card">
                        <h2 class="tka-totals__title"><?php esc_html_e( 'مجموع سفارش', 'toykindangel' ); ?></h2>

                        <div class="tka-totals__row">
                                <span><?php esc_html_e( 'جمع کالاها', 'toykindangel' ); ?></span>
                                <strong class="num"><?php echo wp_kses_post( WC()->cart->get_cart_subtotal() ); ?></strong>
                        </div>

                        <?php foreach ( WC()->cart->get_coupons() as $tka_code => $tka_coupon ) : ?>
                                <div class="tka-totals__row tka-totals__row--discount">
                                        <span><?php esc_html_e( 'کد تخفیف', 'toykindangel' ); ?>: <?php echo esc_html( $tka_code ); ?></span>
                                        <strong class="num"><?php echo wp_kses_post( WC()->cart->get_coupon_discount_amount( $tka_code ) ); ?></strong>
                                </div>
                        <?php endforeach; ?>

                        <?php if ( WC()->cart->needs_shipping() && WC()->cart->show_shipping() ) : ?>
                                <div class="tka-totals__shipping">
                                        <?php wc_cart_totals_shipping_html(); ?>
                                </div>
                        <?php endif; ?>

                        <?php foreach ( WC()->cart->get_fees() as $tka_fee ) : ?>
                                <div class="tka-totals__row">
                                        <span><?php echo esc_html( $tka_fee->name ); ?></span>
                                        <strong class="num"><?php echo wp_kses_post( wc_cart_totals_fee_html( $tka_fee ) ); ?></strong>
                                </div>
                        <?php endforeach; ?>

                        <?php if ( wc_tax_enabled() && ! WC()->cart->display_prices_including_tax() ) : ?>
                                <div class="tka-totals__row">
                                        <span><?php esc_html_e( 'مالیات', 'toykindangel' ); ?></span>
                                        <strong class="num"><?php echo wp_kses_post( WC()->cart->get_taxes_total() ); ?></strong>
                                </div>
                        <?php endif; ?>

                        <div class="tka-totals__row tka-totals__row--total">
                                <span><?php esc_html_e( 'مبلغ قابل پرداخت', 'toykindangel' ); ?></span>
                                <strong class="num"><?php echo wp_kses_post( WC()->cart->get_total() ); ?></strong>
                        </div>

                        <a href="<?php echo esc_url( wc_get_checkout_url() ); ?>" class="tka-totals__cta">
                                <?php esc_html_e( 'ادامه و تسویه حساب', 'toykindangel' ); ?>
                                <svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg>
                        </a>

                        <p class="tka-totals__note">
                                <svg class="ic" aria-hidden="true"><use href="#i-shield"></use></svg>
                                <?php esc_html_e( 'پرداخت امن · ضمانت اصالت کالا · ۷ روز مهلت بازگشت', 'toykindangel' ); ?>
                        </p>
                </div>
        </aside>

        <?php do_action( 'woocommerce_after_cart' ); ?>
</div>
