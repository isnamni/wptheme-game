<?php
/**
 * Checkout form — demo-style RTL two-column layout.
 *
 * All logic (billing/shipping fields, order review, payment gateways,
 * the checkout JS) runs through the standard WooCommerce actions, so
 * every gateway/filter plugin keeps working. Only the wrapper markup is
 * themed.
 *
 * Re-synced against WooCommerce 11.2.0: adopted the early-return +
 * woocommerce_checkout_must_be_logged_in_message filter for the
 * registration-required branch, the
 * woocommerce_checkout_before_customer_details /
 * woocommerce_checkout_before_order_review_heading /
 * woocommerce_checkout_before_order_review /
 * woocommerce_checkout_after_order_review hooks and the form
 * aria-label; after_customer_details moved inside the
 * has-checkout-fields branch (core 9.4.0 sequence).
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_checkout_form', WC()->checkout() );

/* If checkout registration is disabled and not logged in → login notice, then stop (core parity). */
if ( ! is_user_logged_in() && ! WC()->checkout()->is_registration_enabled() && WC()->checkout()->is_registration_required() ) {
        echo '<div class="tka-co__login-notice">' . esc_html( apply_filters( 'woocommerce_checkout_must_be_logged_in_message', __( 'برای ادامهٔ خرید ابتدا وارد حساب کاربری خود شوید.', 'toykindangel' ) ) ) . '</div>';
        return;
}
?>
<div class="tka-co">

        <header class="tka-co__head">
                <h1 class="tka-co__title">
                        <svg class="ic" aria-hidden="true"><use href="#i-shield"></use></svg>
                        <?php esc_html_e( 'تسویه حساب', 'toykindangel' ); ?>
                </h1>
                <div class="tka-co__steps" aria-hidden="true">
                        <span class="tka-co__step"><?php esc_html_e( 'سبد خرید', 'toykindangel' ); ?></span>
                        <span class="tka-co__step tka-co__step--on"><?php esc_html_e( 'اطلاعات و پرداخت', 'toykindangel' ); ?></span>
                        <span class="tka-co__step"><?php esc_html_e( 'تحویل', 'toykindangel' ); ?></span>
                </div>
        </header>

        <form name="checkout" method="post" class="checkout woocommerce-checkout tka-co__form"
                action="<?php echo esc_url( wc_get_checkout_url() ); ?>" enctype="multipart/form-data" aria-label="<?php echo esc_attr__( 'Checkout', 'woocommerce' ); ?>">

                <div class="tka-co__main">

                        <?php do_action( 'woocommerce_checkout_before_customer_details' ); ?>

                        <?php if ( WC()->checkout()->get_checkout_fields() ) : ?>

                                <section class="tka-co__fields card">
                                        <?php do_action( 'woocommerce_checkout_billing' ); ?>
                                        <?php do_action( 'woocommerce_checkout_shipping' ); ?>
                                </section>

                                <?php do_action( 'woocommerce_checkout_after_customer_details' ); ?>

                        <?php endif; ?>
                </div>

                <aside class="tka-co__side">
                        <section class="tka-co__review tka-totals__card">
                                <?php do_action( 'woocommerce_checkout_before_order_review_heading' ); ?>
                                <h2 class="tka-totals__title">
                                        <svg class="ic" aria-hidden="true"><use href="#i-box"></use></svg>
                                        <?php esc_html_e( 'سفارش شما', 'toykindangel' ); ?>
                                </h2>
                                <?php do_action( 'woocommerce_checkout_before_order_review' ); ?>
                                <?php do_action( 'woocommerce_checkout_order_review' ); ?>
                                <?php do_action( 'woocommerce_checkout_after_order_review' ); ?>
                        </section>

                        <section class="tka-co__payment">
                                <?php do_action( 'woocommerce_checkout_payment' ); ?>
                        </section>

                        <?php if ( WC()->checkout()->is_registration_enabled() && ! is_user_logged_in() ) : ?>
                                <div class="tka-co__register woocommerce-form__toggle woocommerce-form-login__submit">
                                        <?php do_action( 'woocommerce_before_checkout_registration_form', WC()->checkout() ); ?>
                                        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
                                                <input type="checkbox" class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox" name="createaccount" value="1" <?php checked( true, WC()->checkout()->get_checkout_fields() && WC()->checkout()->get_value( 'createaccount' ) ? true : false, true ); ?>>
                                                <span><?php esc_html_e( 'حساب کاربری بسازم (سفارش‌ها و کدرهگیری سریع‌تر)', 'toykindangel' ); ?></span>
                                        </label>
                                        <?php do_action( 'woocommerce_after_checkout_registration_form', WC()->checkout() ); ?>
                                </div>
                        <?php endif; ?>
                </aside>

        </form>

        <?php do_action( 'woocommerce_after_checkout_form', WC()->checkout() ); ?>
</div>
