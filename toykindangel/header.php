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

        <!-- بنر تمام‌عرض بالای صفحه (تصویر از Customizer/دیتاست دمو) -->
        <a class="topstrip" id="topStrip" href="<?php echo esc_url( get_theme_mod( 'tka_strip_link', home_url( '/' ) ) ); ?>" aria-label="<?php esc_attr_e( 'بنر ویژه فروشگاه', 'toykindangel' ); ?>"></a>

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
