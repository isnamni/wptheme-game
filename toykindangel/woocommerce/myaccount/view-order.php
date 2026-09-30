<?php
/**
 * View Order — demo-style order details (RTL).
 *
 * Based on WooCommerce core myaccount/view-order.php (10.6.0). The order
 * line items come from the core woocommerce_view_order action →
 * order/order-details.php, themed by wc-pages.css.
 *
 * @package ToyKindAngel
 * @version 10.6.0
 */

defined( 'ABSPATH' ) || exit;

$notes = $order->get_customer_order_notes();
?>
<div class="tka-vieworder">

        <div class="tka-vieworder__card tka-card">
                <header class="tka-vieworder__head">
                        <h2 class="tka-card__title">
                                <svg class="ic" aria-hidden="true"><use href="#i-box"></use></svg>
                                <?php esc_html_e( 'جزئیات سفارش', 'toykindangel' ); ?>
                                <span class="tka-vieworder__num num"><?php echo esc_html( _x( '#', 'hash before order number', 'woocommerce' ) . $order->get_order_number() ); ?></span>
                        </h2>
                        <span class="tka-badge tka-badge--<?php echo esc_attr( $order->get_status() ); ?>">
                                <?php echo esc_html( wc_get_order_status_name( $order->get_status() ) ); ?>
                        </span>
                </header>

                <p class="tka-vieworder__meta num">
                        <?php
                        printf(
                                /* translators: %s: order date. */
                                esc_html__( 'ثبت‌شده در %s', 'toykindangel' ),
                                '<time datetime="' . esc_attr( $order->get_date_created()->date( 'c' ) ) . '">' . esc_html( toykindangel_order_date_jalali( $order ) ) . '</time>'
                        );
                        ?>
                </p>

                <?php do_action( 'woocommerce_view_order', $order_id ); ?>
        </div>

        <?php if ( $notes ) : ?>
                <div class="tka-vieworder__notes tka-card">
                        <h3 class="tka-card__title"><?php esc_html_e( 'به‌روزرسانی‌های سفارش', 'toykindangel' ); ?></h3>
                        <ol class="tka-vieworder__list woocommerce-OrderUpdates commentlist notes">
                                <?php foreach ( $notes as $tka_note ) : ?>
                                        <li class="tka-vieworder__note woocommerce-OrderUpdate comment note">
                                                <p class="tka-vieworder__note-meta num"><?php echo esc_html( toykindangel_jdate( 'Y/m/d — H:i', strtotime( $tka_note->comment_date ) ) ); ?></p>
                                                <div class="tka-vieworder__note-text">
                                                        <?php echo wp_kses_post( wpautop( wptexturize( $tka_note->comment_content ) ) ); ?>
                                                </div>
                                        </li>
                                <?php endforeach; ?>
                        </ol>
                </div>
        <?php endif; ?>
</div>
