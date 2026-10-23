<?php
/**
 * Orders — demo-style RTL orders table with status badges.
 *
 * Based on WooCommerce core myaccount/orders.php; all hooks, column
 * names and the pagination logic are preserved, only markup and classes
 * are themed.
 *
 * Re-synced against WooCommerce 11.2.0: adopted the aria-label on the
 * order-number link ("View order number %s") and the aria-label
 * fallback for order actions introduced in 9.5.0.
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_account_orders', $has_orders );
?>

<?php if ( $has_orders ) : ?>

        <div class="tka-orders__card tka-card">
                <h2 class="tka-card__title">
                        <svg class="ic" aria-hidden="true"><use href="#i-box"></use></svg>
                        <?php esc_html_e( 'سفارش‌های من', 'toykindangel' ); ?>
                </h2>

                <div class="tka-orders__scroll">
                        <table class="tka-orders__table woocommerce-orders-table woocommerce-MyAccount-orders account-orders-table">
                                <thead>
                                        <tr>
                                                <?php foreach ( wc_get_account_orders_columns() as $tka_col_id => $tka_col_name ) : ?>
                                                        <th scope="col" class="woocommerce-orders-table__header woocommerce-orders-table__header-<?php echo esc_attr( $tka_col_id ); ?>"><span class="nobr"><?php echo esc_html( $tka_col_name ); ?></span></th>
                                                <?php endforeach; ?>
                                        </tr>
                                </thead>

                                <tbody>
                                        <?php
                                        foreach ( $customer_orders->orders as $tka_customer_order ) {
                                                $tka_order      = wc_get_order( $tka_customer_order ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                                                $tka_item_count = $tka_order->get_item_count() - $tka_order->get_item_count_refunded();
                                                ?>
                                                <tr class="tka-orders__row woocommerce-orders-table__row woocommerce-orders-table__row--status-<?php echo esc_attr( $tka_order->get_status() ); ?> order">
                                                        <?php
                                                        foreach ( wc_get_account_orders_columns() as $tka_col_id => $tka_col_name ) :
                                                                $tka_is_number = 'order-number' === $tka_col_id;
                                                                ?>
                                                                <?php if ( $tka_is_number ) : ?>
                                                                        <th class="tka-orders__cell woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr( $tka_col_id ); ?>" data-title="<?php echo esc_attr( $tka_col_name ); ?>" scope="row">
                                                                <?php else : ?>
                                                                        <td class="tka-orders__cell woocommerce-orders-table__cell woocommerce-orders-table__cell-<?php echo esc_attr( $tka_col_id ); ?>" data-title="<?php echo esc_attr( $tka_col_name ); ?>">
                                                                <?php endif; ?>

                                                                <?php if ( has_action( 'woocommerce_my_account_my_orders_column_' . $tka_col_id ) ) : ?>

                                                                        <?php do_action( 'woocommerce_my_account_my_orders_column_' . $tka_col_id, $tka_order ); ?>

                                                                <?php elseif ( $tka_is_number ) : ?>

                                                                        <?php /* translators: %s: order number */ ?>
                                                                        <a class="tka-orders__num" href="<?php echo esc_url( $tka_order->get_view_order_url() ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'View order number %s', 'woocommerce' ), $tka_order->get_order_number() ) ); ?>">
                                                                                <?php echo esc_html( _x( '#', 'hash before order number', 'woocommerce' ) . $tka_order->get_order_number() ); ?>
                                                                        </a>

                                                                <?php elseif ( 'order-date' === $tka_col_id ) : ?>

                                                                        <time datetime="<?php echo esc_attr( $tka_order->get_date_created()->date( 'c' ) ); ?>"><?php echo esc_html( toykindangel_order_date_jalali( $tka_order ) ); ?></time>

                                                                <?php elseif ( 'order-status' === $tka_col_id ) : ?>

                                                                        <span class="tka-badge tka-badge--<?php echo esc_attr( $tka_order->get_status() ); ?>">
                                                                                <?php echo esc_html( wc_get_order_status_name( $tka_order->get_status() ) ); ?>
                                                                        </span>

                                                                <?php elseif ( 'order-total' === $tka_col_id ) : ?>

                                                                        <span class="num"><?php echo wp_kses_post( $tka_order->get_formatted_order_total() ); ?></span>
                                                                        <small class="tka-orders__items num">
                                                                                <?php
                                                                                /* translators: %d: number of items. */
                                                                                printf( esc_html__( '%d کالا', 'toykindangel' ), (int) $tka_item_count );
                                                                                ?>
                                                                        </small>

                                                                <?php elseif ( 'order-actions' === $tka_col_id ) : ?>

                                                                        <?php
                                                                        $tka_actions = wc_get_account_orders_actions( $tka_order );
                                                                        if ( ! empty( $tka_actions ) ) {
                                                                                foreach ( $tka_actions as $tka_action_key => $tka_action ) { // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
                                                                                        if ( empty( $tka_action['aria-label'] ) ) {
                                                                                                // Generate the aria-label based on the action name (core 9.5.0 behavior).
                                                                                                /* translators: %1$s Action name, %2$s Order number. */
                                                                                                $tka_action_aria_label = sprintf( __( '%1$s order number %2$s', 'woocommerce' ), $tka_action['name'], $tka_order->get_order_number() );
                                                                                        } else {
                                                                                                $tka_action_aria_label = $tka_action['aria-label'];
                                                                                        }
                                                                                        echo '<a href="' . esc_url( $tka_action['url'] ) . '" class="tka-orders__action tka-orders__action--' . esc_attr( sanitize_html_class( $tka_action_key ) ) . '" aria-label="' . esc_attr( $tka_action_aria_label ) . '">'
                                                                                                . esc_html( $tka_action['name'] ) . '</a>';
                                                                                        unset( $tka_action_aria_label );
                                                                                }
                                                                        }
                                                                        ?>

                                                                <?php endif; ?>

                                                                <?php if ( $tka_is_number ) : ?>
                                                                        </th>
                                                                <?php else : ?>
                                                                        </td>
                                                                <?php endif; ?>
                                                        <?php endforeach; ?>
                                                </tr>
                                                <?php
                                        }
                                        ?>
                                </tbody>
                        </table>
                </div>

                <?php do_action( 'woocommerce_before_account_orders_pagination' ); ?>

                <?php if ( 1 < $customer_orders->max_num_pages ) : ?>
                        <div class="tka-orders__pager woocommerce-pagination woocommerce-pagination--without-numbers">
                                <?php if ( 1 !== $current_page ) : ?>
                                        <a class="tka-orders__pager-btn tka-orders__pager-btn--prev" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page - 1 ) ); ?>">« <?php esc_html_e( 'قبلی', 'toykindangel' ); ?></a>
                                <?php endif; ?>

                                <?php if ( intval( $customer_orders->max_num_pages ) !== $current_page ) : ?>
                                        <a class="tka-orders__pager-btn tka-orders__pager-btn--next" href="<?php echo esc_url( wc_get_endpoint_url( 'orders', $current_page + 1 ) ); ?>"><?php esc_html_e( 'بعدی', 'toykindangel' ); ?> »</a>
                                <?php endif; ?>
                        </div>
                <?php endif; ?>
        </div>

<?php else : ?>

        <div class="tka-emptycart__card tka-card tka-orders__empty">
                <div class="tka-emptycart__art" aria-hidden="true">
                        <svg class="ic"><use href="#i-box"></use></svg>
                </div>
                <p class="tka-emptycart__text"><?php esc_html_e( 'هنوز سفارشی ثبت نکرده‌اید.', 'toykindangel' ); ?></p>
                <div class="tka-emptycart__actions">
                        <a class="tka-emptycart__cta" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
                                <?php esc_html_e( 'شروع خرید', 'toykindangel' ); ?>
                        </a>
                </div>
        </div>

<?php endif; ?>

<?php do_action( 'woocommerce_after_account_orders', $has_orders ); ?>
