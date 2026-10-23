<?php
/**
 * Thank-you / order received — demo celebration card.
 *
 * The order-details table is rendered ONCE through the standard
 * do_action('woocommerce_thankyou') hook (core hooks
 * woocommerce_order_details_table() onto it — see order/order-details.php
 * in wc-template-functions.php). Never call
 * woocommerce_order_details_table() here as well: that prints the
 * details twice.
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="tka-thanks">

        <div class="tka-thanks__card">
                <div class="tka-thanks__badge" aria-hidden="true">
                        <svg class="ic"><use href="#i-check"></use></svg>
                </div>

                <?php if ( $order && $order->has_status( 'failed' ) ) : ?>

                        <h1 class="tka-thanks__title tka-thanks__title--fail"><?php esc_html_e( 'پرداخت ناموفق بود', 'toykindangel' ); ?></h1>
                        <p class="tka-thanks__text">
                                <?php esc_html_e( 'متأسفانه تراکنش تکمیل نشد. می‌توانید دوباره تلاش کنید یا با پشتیبانی تماس بگیرید.', 'toykindangel' ); ?>
                        </p>
                        <div class="tka-thanks__actions">
                                <a class="tka-thanks__cta" href="<?php echo esc_url( $order->get_checkout_payment_url() ); ?>">
                                        <?php esc_html_e( 'پرداخت مجدد', 'toykindangel' ); ?>
                                </a>
                        </div>

                <?php else : ?>

                        <h1 class="tka-thanks__title"><?php esc_html_e( 'سفارش شما با موفقیت ثبت شد!', 'toykindangel' ); ?></h1>

                        <p class="tka-thanks__text">
                                <?php
                                /* translators: %s: customer first name. */
                                printf( esc_html__( 'ممنون از اعتماد شما %s 🎁 سفارش‌تان آمادهٔ بسته‌بندی است و کدرهگیری از طریق پیامک ارسال می‌شود.', 'toykindangel' ), esc_html( $order ? $order->get_billing_first_name() : '' ) );
                                ?>
                        </p>

                        <?php if ( $order ) : ?>

                                <?php do_action( 'woocommerce_before_thankyou', $order->get_id() ); ?>

                                <ul class="tka-thanks__meta">
                                        <li class="tka-thanks__meta-item">
                                                <span><?php esc_html_e( 'شماره سفارش', 'toykindangel' ); ?></span>
                                                <strong class="num"><?php echo esc_html( $order->get_order_number() ); ?></strong>
                                        </li>
                                        <li class="tka-thanks__meta-item">
                                                <span><?php esc_html_e( 'تاریخ', 'toykindangel' ); ?></span>
                                                <strong><?php echo esc_html( toykindangel_order_date_jalali( $order, 'Y/m/d' ) ); ?></strong>
                                        </li>
                                        <li class="tka-thanks__meta-item">
                                                <span><?php esc_html_e( 'مبلغ کل', 'toykindangel' ); ?></span>
                                                <strong class="num"><?php echo wp_kses_post( $order->get_formatted_order_total() ); ?></strong>
                                        </li>
                                        <li class="tka-thanks__meta-item">
                                                <span><?php esc_html_e( 'روش پرداخت', 'toykindangel' ); ?></span>
                                                <strong><?php echo esc_html( $order->get_payment_method_title() ); ?></strong>
                                        </li>
                                </ul>

                                <div class="tka-thanks__actions">
                                        <a class="tka-thanks__cta" href="<?php echo esc_url( $order->get_view_order_url() ); ?>">
                                                <?php esc_html_e( 'پیگیری سفارش در حساب کاربری', 'toykindangel' ); ?>
                                        </a>
                                        <a class="tka-thanks__ghost" href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
                                                <?php esc_html_e( 'ادامهٔ خرید', 'toykindangel' ); ?>
                                        </a>
                                </div>

                        <?php endif; ?>
                <?php endif; ?>
        </div>

        <?php if ( $order ) : ?>
                <section class="tka-thanks__details">
                        <?php
                        /*
                         * Single source of truth for the order-details table: the
                         * standard core hook (woocommerce_order_details_table() is
                         * attached to it by WooCommerce). The previous DIRECT call to
                         * woocommerce_order_details_table() printed the table twice.
                         */
                        do_action( 'woocommerce_thankyou', $order->get_id() );
                        ?>
                </section>
        <?php else : ?>
                <?php do_action( 'woocommerce_thankyou', '' ); ?>
        <?php endif; ?>
</div>
