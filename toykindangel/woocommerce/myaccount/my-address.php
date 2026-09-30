<?php
/**
 * My Addresses — demo-style address cards (RTL).
 *
 * Based on WooCommerce core myaccount/my-address.php; hooks and the
 * address rendering (wc_get_account_formatted_address) unchanged.
 *
 * Re-synced against WooCommerce 11.2.0: filters
 * (woocommerce_my_account_get_addresses, woocommerce_my_account_my_address_description)
 * and the woocommerce_my_account_after_my_address action (since 8.7.0)
 * are unchanged since 9.3.0.
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

$customer_id = get_current_user_id();

if ( ! wc_ship_to_billing_address_only() && wc_shipping_enabled() ) {
        $get_addresses = apply_filters(
                'woocommerce_my_account_get_addresses',
                array(
                        'billing'  => __( 'آدرس صورت‌حساب', 'toykindangel' ),
                        'shipping' => __( 'آدرس ارسال', 'toykindangel' ),
                ),
                $customer_id
        );
} else {
        $get_addresses = apply_filters(
                'woocommerce_my_account_get_addresses',
                array(
                        'billing' => __( 'آدرس صورت‌حساب', 'toykindangel' ),
                ),
                $customer_id
        );
}
?>
<p class="tka-addr__desc">
        <?php echo apply_filters( 'woocommerce_my_account_my_address_description', esc_html__( 'این آدرس‌ها به‌صورت پیش‌فرض در صفحهٔ تسویه حساب استفاده می‌شوند.', 'toykindangel' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</p>

<div class="tka-addr__grid <?php echo ( 1 < count( $get_addresses ) ) ? 'tka-addr__grid--two' : ''; ?>">

        <?php foreach ( $get_addresses as $tka_name => $tka_title ) : ?>
                <?php $tka_address = wc_get_account_formatted_address( $tka_name ); ?>

                <div class="tka-addr__card tka-card woocommerce-Address">
                        <header class="tka-addr__head">
                                <h3 class="tka-addr__title">
                                        <svg class="ic" aria-hidden="true"><use href="#i-pin"></use></svg>
                                        <?php echo esc_html( $tka_title ); ?>
                                </h3>
                                <a class="tka-addr__edit" href="<?php echo esc_url( wc_get_endpoint_url( 'edit-address', $tka_name ) ); ?>">
                                        <?php echo $tka_address ? esc_html__( 'ویرایش', 'toykindangel' ) : esc_html__( 'افزودن', 'toykindangel' ); ?>
                                        <svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg>
                                </a>
                        </header>

                        <address class="tka-addr__body">
                                <?php
                                echo $tka_address ? wp_kses_post( $tka_address ) : esc_html__( 'هنوز آدرسی ثبت نشده است.', 'toykindangel' );

                                do_action( 'woocommerce_my_account_after_my_address', $tka_name );
                                ?>
                        </address>
                </div>

        <?php endforeach; ?>
</div>
