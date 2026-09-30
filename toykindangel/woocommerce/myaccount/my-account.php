<?php
/**
 * My Account wrapper — demo-style RTL dashboard (side nav + content).
 *
 * All endpoint content (dashboard, orders, downloads, addresses,
 * account details, payment methods …) is rendered by the standard
 * woocommerce_account_content action, so every endpoint and plugin
 * works unchanged.
 *
 * Re-synced against WooCommerce 11.2.0: core logic is unchanged since
 * 3.5.0 (woocommerce_account_navigation + woocommerce_account_content),
 * only the docblock stamp was refreshed.
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

$tka_user      = wp_get_current_user();
$tka_greeting  = $tka_user->display_name ? $tka_user->display_name : $tka_user->user_login;
$tka_pending   = 0;
if ( function_exists( 'wc_get_customer_order_count' ) ) {
        $tka_pending = wc_get_customer_order_count( $tka_user->ID );
}
?>
<div class="tka-acc">

        <header class="tka-acc__hero">
                <div class="tka-acc__avatar" aria-hidden="true">
                        <svg class="ic"><use href="#i-user"></use></svg>
                </div>
                <div class="tka-acc__who">
                        <h1 class="tka-acc__title"><?php esc_html_e( 'حساب کاربری', 'toykindangel' ); ?></h1>
                        <p class="tka-acc__name">
                                <?php
                                /* translators: %s: user display name. */
                                printf( esc_html__( 'سلام %s 👋', 'toykindangel' ), esc_html( $tka_greeting ) );
                                ?>
                        </p>
                </div>
                <div class="tka-acc__stat">
                        <span class="tka-acc__stat-num num"><?php echo (int) $tka_pending; ?></span>
                        <span class="tka-acc__stat-label"><?php esc_html_e( 'سفارش', 'toykindangel' ); ?></span>
                </div>
        </header>

        <div class="tka-acc__layout">

                <?php
                /**
                 * Side navigation (endpoint menu).
                 *
                 * @hooked woocommerce_account_navigation — 10 (overridden template)
                 */
                do_action( 'woocommerce_account_navigation' );
                ?>

                <section class="tka-acc__content woocommerce-MyAccount-content">
                        <?php
                        /**
                         * My Account content.
                         *
                         * @since 2.6.0
                         */
                        do_action( 'woocommerce_account_content' );
                        ?>
                </section>
        </div>
</div>
