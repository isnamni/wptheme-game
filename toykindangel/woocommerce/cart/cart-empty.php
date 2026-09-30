<?php
/**
 * Empty cart — demo-style empty state.
 *
 * Re-synced against WooCommerce 11.2.0: kept the
 * woocommerce_cart_is_empty action and re-attached core's
 * woocommerce_return_to_shop_redirect / woocommerce_return_to_shop_text
 * filters to the themed CTA so plugins that retarget or relabel the
 * "return to shop" link keep working.
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="tka-emptycart">
        <div class="tka-emptycart__card">
                <div class="tka-emptycart__art" aria-hidden="true">
                        <svg class="ic"><use href="#i-cart"></use></svg>
                </div>
                <h1 class="tka-emptycart__title"><?php esc_html_e( 'سبد خرید شما خالی است', 'toykindangel' ); ?></h1>
                <p class="tka-emptycart__text"><?php esc_html_e( 'هنوز چیزی برای خرید انتخاب نکرده‌اید؛ از دسته‌بندی‌ها شروع کنید یا پرفروش‌ترین اسباب‌بازی‌ها را ببینید.', 'toykindangel' ); ?></p>
                <div class="tka-emptycart__actions">
                        <a class="tka-emptycart__cta" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
                                <svg class="ic" aria-hidden="true"><use href="#i-grid"></use></svg>
                                <?php
                                /**
                                 * Filter "Return To Shop" text (core parity, since 4.6.0).
                                 *
                                 * @param string $default_text Default text.
                                 */
                                echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'رفتن به فروشگاه', 'toykindangel' ) ) );
                                ?>
                        </a>
                        <?php if ( function_exists( 'toykindangel_categories_url' ) ) : ?>
                                <a class="tka-emptycart__ghost" href="<?php echo esc_url( toykindangel_categories_url() ); ?>">
                                        <?php esc_html_e( 'مرور دسته‌بندی‌ها', 'toykindangel' ); ?>
                                </a>
                        <?php endif; ?>
                </div>
                <?php do_action( 'woocommerce_cart_is_empty' ); ?>
        </div>
</div>
