<?php
/**
 * Downloads — demo-style card (RTL).
 *
 * Faithful to WooCommerce core myaccount/downloads.php: the list itself
 * is rendered by the standard woocommerce_available_downloads action
 * (templates/order/order-downloads.php) and themed by wc-pages.css.
 *
 * Re-synced against WooCommerce 11.2.0: hook sequence
 * (before_account_downloads → before/available/after_available_downloads →
 * after_account_downloads) is unchanged since 7.8.0.
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

$downloads     = WC()->customer->get_downloadable_products();
$has_downloads = (bool) $downloads;

do_action( 'woocommerce_before_account_downloads', $has_downloads );
?>

<div class="tka-dl tka-card">
        <h2 class="tka-card__title">
                <svg class="ic" aria-hidden="true"><use href="#i-transit"></use></svg>
                <?php esc_html_e( 'فایل‌های دانلود', 'toykindangel' ); ?>
        </h2>

        <?php if ( $has_downloads ) : ?>

                <?php do_action( 'woocommerce_before_available_downloads' ); ?>

                <div class="tka-dl__scroll">
                        <?php do_action( 'woocommerce_available_downloads', $downloads ); ?>
                </div>

                <?php do_action( 'woocommerce_after_available_downloads' ); ?>

        <?php else : ?>

                <p class="tka-dl__empty">
                        <?php esc_html_e( 'هیچ فایل قابل دانلودی در دسترس نیست.', 'toykindangel' ); ?>
                        <a href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
                                <?php esc_html_e( 'مشاهدهٔ محصولات', 'toykindangel' ); ?>
                        </a>
                </p>

        <?php endif; ?>
</div>

<?php do_action( 'woocommerce_after_account_downloads', $has_downloads ); ?>
