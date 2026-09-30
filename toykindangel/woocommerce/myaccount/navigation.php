<?php
/**
 * My Account navigation — demo pill-style endpoint menu (RTL).
 *
 * Mirrors WC core navigation.php exactly (same endpoints, same active
 * state logic with aria-current, same before/after hooks) with theme
 * markup so both the desktop side rail and the mobile chip row look like
 * the demo.
 *
 * Re-synced against WooCommerce 11.2.0: adopted the aria-current="page"
 * attribute on the active endpoint (wc_is_current_account_menu_item()).
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_navigation' );
?>
<nav class="tka-acc__nav woocommerce-MyAccount-navigation" role="navigation" aria-label="<?php esc_attr_e( 'منوی حساب کاربری', 'toykindangel' ); ?>">
        <ul class="tka-acc__menu">
                <?php foreach ( wc_get_account_menu_items() as $tka_endpoint => $tka_label ) : ?>
                        <li class="tka-acc__menu-item <?php echo esc_attr( implode( ' ', (array) wc_get_account_menu_item_classes( $tka_endpoint ) ) ); ?>">
                                <a href="<?php echo esc_url( wc_get_account_endpoint_url( $tka_endpoint ) ); ?>"
                                        class="tka-acc__menu-link"
                                        <?php echo wc_is_current_account_menu_item( $tka_endpoint ) ? 'aria-current="page"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                        <?php echo esc_html( $tka_label ); ?>
                                </a>
                        </li>
                <?php endforeach; ?>
        </ul>
</nav>
<?php do_action( 'woocommerce_after_account_navigation' ); ?>
