<?php
/**
 * Edit address form — demo-style card (RTL).
 *
 * Based on WooCommerce core myaccount/form-edit-address.php; field loop,
 * nonces and actions unchanged.
 *
 * Re-synced against WooCommerce 11.2.0: hook sequence
 * (before_edit_account_address_form → title filter →
 * before/after_edit_address_form_{$load_address} →
 * after_edit_account_address_form) is unchanged since 9.3.0.
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

$page_title = ( 'billing' === $load_address ) ? esc_html__( 'آدرس صورت‌حساب', 'toykindangel' ) : esc_html__( 'آدرس ارسال', 'toykindangel' );

do_action( 'woocommerce_before_edit_account_address_form' );
?>

<?php if ( ! $load_address ) : ?>

        <?php wc_get_template( 'myaccount/my-address.php' ); ?>

<?php else : ?>

        <div class="tka-addrform tka-card">
                <h2 class="tka-card__title">
                        <svg class="ic" aria-hidden="true"><use href="#i-pin"></use></svg>
                        <?php echo esc_html( apply_filters( 'woocommerce_my_account_edit_address_title', $page_title, $load_address ) ); ?>
                </h2>

                <form class="tka-addrform__form" method="post" novalidate>

                        <div class="woocommerce-address-fields">
                                <?php do_action( "woocommerce_before_edit_address_form_{$load_address}" ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>

                                <div class="tka-addrform__grid woocommerce-address-fields__field-wrapper">
                                        <?php
                                        foreach ( $address as $tka_key => $tka_field ) {
                                                woocommerce_form_field( $tka_key, $tka_field, wc_get_post_data_by_key( $tka_key, $tka_field['value'] ) );
                                        }
                                        ?>
                                </div>

                                <?php do_action( "woocommerce_after_edit_address_form_{$load_address}" ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>

                                <div class="tka-addrform__actions">
                                        <button type="submit" class="tka-login__submit button" name="save_address" value="<?php esc_attr_e( 'ذخیرهٔ آدرس', 'toykindangel' ); ?>">
                                                <?php esc_html_e( 'ذخیرهٔ آدرس', 'toykindangel' ); ?>
                                                <svg class="ic" aria-hidden="true"><use href="#i-check"></use></svg>
                                        </button>
                                        <?php wp_nonce_field( 'woocommerce-edit_address', 'woocommerce-edit-address-nonce' ); ?>
                                        <input type="hidden" name="action" value="edit_address" />
                                </div>
                        </div>
                </form>
        </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_edit_account_address_form' ); ?>
