<?php
/**
 * Site header — 1:1 demo markup (topstrip + promo-strip + header).
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}


?><!doctype html>
<html <?php language_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
<head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
        <meta name="theme-color" content="#b400ae">
        <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link screen-reader-text" href="#content"><?php esc_html_e( 'پرش به محتوا', 'toykindangel' ); ?></a>

<div class="app app--padded"<?php echo is_front_page() ? ' id="home"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>

        <?php
        /*
         * بنر تمام‌عرض بالای صفحه — تصویر از Customizer (tka_strip_img).
         * v0.21.1: عکس حالا مستقیماً سمت سرور رندر می‌شود (قبلاً app.js
         * از دیتاست دمو آن را می‌ریخت، ولی در refactor v0.21.0 آن مسیر حذف
         * شد و بنر خالی می‌ماند). وقتی تصویری تنظیم نشده، کل .topstrip
         * چاپ نمی‌شود تا فضای خالی نیفتد.
         * v0.21.1: وقتی کاربر پایین اسکرول می‌کند، بنر با CSS sticky هدر
         * محو می‌شود (به‌جای جا ماندن فضای خالی) — پیاده‌سازی در main.js.
         */
        $tka_strip_img   = get_theme_mod( 'tka_strip_img' );
        $tka_strip_link  = get_theme_mod( 'tka_strip_link', home_url( '/' ) );
        $tka_strip_html  = '';
        if ( $tka_strip_img ) :
                $tka_strip_html = '<a class="topstrip" id="topStrip" href="' . esc_url( $tka_strip_link ) . '" aria-label="' . esc_attr__( 'بنر ویژه فروشگاه', 'toykindangel' ) . '">'
                        . '<img src="' . esc_url( $tka_strip_img ) . '" alt="' . esc_attr__( 'بنر ویژه فروشگاه', 'toykindangel' ) . '" loading="eager" decoding="async">'
                        . '</a>';
        endif;
        echo $tka_strip_html; // phpcs:ignore WordPress.Security.EscapeOutput -- escaped above.
        ?>

        <!-- نوار اطلاع‌رسانی -->
        <div class="promo-strip">
                <svg class="ic" aria-hidden="true"><use href="#i-truck"></use></svg>
                <div class="marquee"><span><?php echo esc_html( get_theme_mod( 'tka_promo_text', 'ارسال رایگان برای خریدهای بالای ۵۰۰ هزار تومان در مشهد &nbsp;•&nbsp; پشتیبانی ۷ روز هفته &nbsp;•&nbsp; ضمانت سلامت فیزیکی کالا' ) ); ?></span></div>
        </div>

        <!-- هدر -->
        <header class="header" role="banner">
                <div class="header__row">
                        <?php /* همبرگر موبایل = دراور منوی اصلی (inc/cat-drawer.php) */ ?>
                        <button type="button" class="icon-btn icon-btn--menu" data-tka-drawer="menu" aria-expanded="false" aria-controls="tka-menu-drawer" aria-label="<?php esc_attr_e( 'منوی اصلی', 'toykindangel' ); ?>">
                                <svg class="ic" aria-hidden="true"><use href="#i-menu"></use></svg>
                        </button>
                        <a class="logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <?php
                                $tka_logo_id = get_theme_mod( 'custom_logo' );
                                if ( $tka_logo_id ) {
                                        echo wp_get_attachment_image( $tka_logo_id, 'full', false, array( 'class' => 'logo__img' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                                }
                                ?>
                                <span class="logo__txt"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
                        </a>
                        <?php $tka_wish_url = function_exists( 'toykindangel_wishlist_url' ) ? toykindangel_wishlist_url() : ''; ?>
                        <a class="icon-btn" href="<?php echo esc_url( $tka_wish_url ? $tka_wish_url : home_url( '/' ) ); ?>" aria-label="<?php esc_attr_e( 'علاقه‌مندی‌ها', 'toykindangel' ); ?>">
                                <svg class="ic" aria-hidden="true"><use href="#i-heart"></use></svg>
                                <span class="tka-fav-badge num" id="tka-fav-badge" hidden aria-hidden="true">0</span>
                        </a>
                        <?php
                        /*
                         * آیکون حساب کاربری (دسکتاپ/تبلت) — در موبایل با CSS پنهان است
                         * چون bottomnav همان کار را می‌کند.
                         * وقتی کاربر لاگین نیست → صفحهٔ ورود/عضویت ووکامرس.
                         * وقتی لاگین هست → داشبورد حساب کاربری (سفارش‌ها، دانلودها، …).
                         */
                        $tka_is_logged_in = is_user_logged_in();
                        $tka_account_href = $tka_is_logged_in ? toykindangel_account_url() : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : wp_login_url() );
                        $tka_account_label = $tka_is_logged_in ? __( 'حساب کاربری', 'toykindangel' ) : __( 'ورود و عضویت', 'toykindangel' );
                        ?>
                        <a class="icon-btn icon-btn--account" href="<?php echo esc_url( $tka_account_href ); ?>" aria-label="<?php echo esc_attr( $tka_account_label ); ?>" title="<?php echo esc_attr( $tka_account_label ); ?>">
                                <svg class="ic" aria-hidden="true"><use href="#i-user"></use></svg>
                        </a>
                        <button type="button" class="icon-btn" data-tka-drawer="minicart" aria-expanded="false" aria-controls="tka-minicart-drawer" aria-label="<?php esc_attr_e( 'سبد خرید', 'toykindangel' ); ?>">
                                <svg class="ic" aria-hidden="true"><use href="#i-cart"></use></svg>
                                <span class="badge-count num"><?php echo esc_html( function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0 ); ?></span>
                        </button>
                </div>
                <div class="header__search">
                        <?php get_search_form(); ?>
                </div>

                <?php /* منوی افقی دسکتاپ/تبلت — در موبایل با CSS پنهان است */ ?>
                <nav class="header__nav" role="navigation" aria-label="<?php esc_attr_e( 'منوی اصلی', 'toykindangel' ); ?>">
                        <?php /* دکمهٔ دسته‌بندی‌ها (دسکتاپ/تبلت) — همان دراور مرورگر دسته‌بندی‌ها */ ?>
                        <button type="button" class="header__cats-btn" data-tka-drawer="cats" aria-expanded="false" aria-controls="tka-cats-drawer">
                                <svg class="ic" aria-hidden="true"><use href="#i-grid"></use></svg><?php esc_html_e( 'دسته‌بندی‌ها', 'toykindangel' ); ?>
                        </button>
                        <?php
                        wp_nav_menu(
                                array(
                                        'theme_location' => 'primary',
                                        'container'      => false,
                                        'menu_class'     => 'header__menu',
                                        'depth'          => 2,
                                        'fallback_cb'    => 'toykindangel_menu_fallback',
                                )
                        );
                        ?>
                </nav>
        </header>

        <main id="content" class="tka-site-main" role="main">
