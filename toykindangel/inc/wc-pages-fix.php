<?php
/**
 * WooCommerce pages auto-repair.
 *
 * چرا این فایل هست؟
 * ویزارد نصب ووکامرس (نسخه‌های ۸ به بعد) برگه‌های «سبد خرید» و «تسویه حساب»
 * را با **بلوک گوتنبرگ** (wp:woocommerce/cart و wp:woocommerce/checkout)
 * می‌سازد. قالب ToyKind Angel برای سبد/تسویه طراحی اختصاصی RTL دارد که از
 * مسیر قالب‌های کلاسیک (woocommerce/cart/cart.php و checkout/form-checkout.php)
 * بارگذاری می‌شود؛ بلوک‌ها اصلاً به آن قالب‌ها نمی‌روند و نتیجه، صفحه‌ای
 * بی‌استایل و انگلیسی است (مشکل گزارش‌شدهٔ کاربران پس از نصب تمیز).
 *
 * این کلاس‌ها دو کار می‌کنند:
 *  1) اگر برگهٔ سبد/تسویه/حساب محتوای بلوکی داشته باشد → با شورت‌کد کلاسیک
 *     جایگزین می‌شود تا طراحی دمویی قالب فعال شود.
 *  2) اگر برگه اصلاً وجود نداشته باشد → با شورت‌کد درست ساخته و به ووکامرس
 *     متصل می‌شود.
 *
 * اجرا: فعال‌سازی پوسته + admin_init (سبک؛ فقط ۳ برگهٔ شناخته‌شدهٔ WC را
 * می‌خواند و فقط وقتی تغییر بلوکی دیده شد در DB می‌نویسد).
 *
 * @package ToyKindAngel
 * @version 0.14.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * نقشهٔ برگه‌های ووکامرس → شورت‌کد کلاسیک + نام بلوک معادل.
 *
 * @return array<string,array{shortcode:string,title:string,slug:string,block:string}>
 */
function toykindangel_wc_page_map() {
        return array(
                'cart'      => array(
                        'shortcode' => '[woocommerce_cart]',
                        'title'     => __( 'سبد خرید', 'toykindangel' ),
                        'slug'      => 'cart',
                        'block'     => '<!-- wp:woocommerce/cart',
                ),
                'checkout'  => array(
                        'shortcode' => '[woocommerce_checkout]',
                        'title'     => __( 'تسویه حساب', 'toykindangel' ),
                        'slug'      => 'checkout',
                        'block'     => '<!-- wp:woocommerce/checkout',
                ),
                'myaccount' => array(
                        'shortcode' => '[woocommerce_my_account]',
                        'title'     => __( 'حساب کاربری من', 'toykindangel' ),
                        'slug'      => 'my-account',
                        'block'     => '<!-- wp:woocommerce/customer-account',
                ),
        );
}

/**
 * برگه‌های ووکامرس را اصلاح/ساخت می‌کند (idempotent).
 *
 * @return void
 */
function toykindangel_fix_wc_pages() {
        if ( ! function_exists( 'wc_get_page_id' ) || ! function_exists( 'wp_update_post' ) ) {
		return;
        }

        foreach ( toykindangel_wc_page_map() as $tka_key => $tka_cfg ) {
		$tka_page_id = (int) wc_get_page_id( $tka_key );

		/* برگه وجود ندارد → بساز و به ووکامرس معرفی کن. */
		if ( $tka_page_id <= 0 || 'page' !== get_post_type( $tka_page_id ) ) {
				$tka_new_id = wp_insert_post(
						array(
								'post_title'   => $tka_cfg['title'],
								'post_name'    => $tka_cfg['slug'],
								'post_content' => $tka_cfg['shortcode'],
								'post_status'  => 'publish',
								'post_type'    => 'page',
						)
				);
				if ( $tka_new_id && ! is_wp_error( $tka_new_id ) ) {
			/*
			 * 0.17.0 — found by PHPStan (audit §8): the old
			 * call wc_set_page_id( $key, $id ) never worked —
			 * that function takes a single argument and has
			 * been REMOVED from WooCommerce (11.x), so the
			 * function_exists() guard silently skipped the
			 * registration. The modern equivalent is the
			 * woocommerce_{key}_page_id option.
			 */
			update_option( 'woocommerce_' . $tka_key . '_page_id', (int) $tka_new_id );
				}
				continue;
			}

		/* برگه هست؛ فقط اگر محتوا «بلوک ووکامرس» بود → شورت‌کد کلاسیک. */
		$tka_post = get_post( $tka_page_id );
		if ( ! $tka_post instanceof WP_Post ) {
				continue;
			}
		$tka_content = (string) $tka_post->post_content;
		if ( '' === trim( $tka_content ) || false !== strpos( $tka_content, $tka_cfg['block'] ) ) {
				if ( $tka_content !== $tka_cfg['shortcode'] ) {
				wp_update_post(
				array(
				'ID'           => $tka_page_id,
				'post_content' => $tka_cfg['shortcode'],
				)
			);
				}
			}
        }
}
add_action( 'after_switch_theme', 'toykindangel_fix_wc_pages', 5 );
add_action( 'admin_init', 'toykindangel_fix_wc_pages', 5 );

/**
 * سایدبار «فروشگاه (سایدبار فیلترها)» را در فعال‌سازی پوسته پاک‌سازی می‌کند،
 * اما فقط وقتی محتوای آن ویجت‌های پیش‌فرض هستهٔ وردپرس (جستجو/نوشته‌های
 * اخیر/دیدگاه‌های اخیر/برچسب‌ها/آرشیو/… ) باشد — نه تنظیمات واقعی مدیر.
 *
 * هدف: فیلترهای پیش‌فرض قالب (دسته‌ها/قیمت/برندها) بلافاصله بعد از نصب
 * دیده شوند؛ ویجت‌های سفارشی مدیر دست‌نخورده می‌مانند.
 *
 * @return void
 */
function toykindangel_reset_shop_sidebar() {
        $tka_junk_pattern = '/^(block-\d+|search-|recent-posts-|recent-comments-|archives-|categories-|meta-|pages-|calendar-|tag_cloud-|rss-)/';

        $tka_sidebars = get_option( 'sidebars_widgets', array() );
        if ( empty( $tka_sidebars['shop-sidebar'] ) || ! is_array( $tka_sidebars['shop-sidebar'] ) ) {
		return;
        }

        foreach ( $tka_sidebars['shop-sidebar'] as $tka_widget_id ) {
		if ( ! preg_match( $tka_junk_pattern, (string) $tka_widget_id ) ) {
				return; /* ویجت معتبر مدیر → دست نزن. */
			}
        }

        $tka_sidebars['shop-sidebar'] = array();
        update_option( 'sidebars_widgets', $tka_sidebars );
}
add_action( 'after_switch_theme', 'toykindangel_reset_shop_sidebar', 6 );
