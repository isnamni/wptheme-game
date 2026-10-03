<?php
/**
 * Front page — 1:1 demo sections, server-rendered (SSR) from real
 * WooCommerce/Customizer data (P0 #1).
 *
 * V0.15.0 — Standard WordPress theme behaviour:
 * Until the demo is imported (or the store has its own products, or the
 * Customizer toggle "tka_front_without_demo" is on) the homepage shows a
 * setup card only — no sample products, no banners, no JS fallback.
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
        exit;
}


get_header();

if ( ! toykindangel_front_unlocked() ) :

        /* ------------------------------------------------------------
         * حالت استاندارد وردپرس: تا درون‌ریزی دمو هیچ‌چیز نشان داده نمی‌شود
         * --------------------------------------------------------- */
        $tka_is_admin = current_user_can( 'manage_options' );
        ?>
        <section class="tka-onboard" aria-label="<?php esc_attr_e( 'راه‌اندازی فروشگاه', 'toykindangel' ); ?>">
                <div class="tka-onboard__card">
                        <span class="tka-onboard__icon" aria-hidden="true">
                                <svg class="ic"><use href="#i-box"></use></svg>
                        </span>
                        <h1 class="tka-onboard__title"><?php esc_html_e( 'قالب فرشته مهربون نصب شد!', 'toykindangel' ); ?></h1>
                        <?php if ( $tka_is_admin ) : ?>
                                <p class="tka-onboard__text">
                                        <?php esc_html_e( 'برای نمایش فروشگاه مطابق دمو، محتوای نمونه (برگه‌ها، دسته‌بندی‌ها، محصولات و منوها) را درون‌ریزی کنید.', 'toykindangel' ); ?>
                                </p>
                                <div class="tka-onboard__actions">
                                        <a class="tka-onboard__btn" href="<?php echo esc_url( admin_url( 'themes.php?page=toykindangel-demo-import' ) ); ?>">
                                                <?php esc_html_e( 'درج محتوای نمونه', 'toykindangel' ); ?>
                                        </a>
                                        <a class="tka-onboard__btn tka-onboard__btn--ghost" href="<?php echo esc_url( admin_url( 'themes.php?page=toykindangel-demo-import' ) ); ?>#tka-settings-only">
                                                <?php esc_html_e( 'فقط برگه‌ها و تنظیمات', 'toykindangel' ); ?>
                                        </a>
                                        <a class="tka-onboard__btn tka-onboard__btn--ghost" href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>">
                                                <?php esc_html_e( 'سفارشی‌سازی', 'toykindangel' ); ?>
                                        </a>
                                </div>
                                <p class="tka-onboard__hint">
                                        <?php esc_html_e( 'می‌خواهید بدون دمو فروشگاه خودتان را بسازید؟ از سفارشی‌سازی ← سکشن‌های صفحه اول، گزینهٔ «نمایش صفحه اصلی بدون درون‌ریزی دمو» را روشن کنید.', 'toykindangel' ); ?>
                                </p>
                        <?php else : ?>
                                <p class="tka-onboard__text">
                                        <?php esc_html_e( 'فروشگاه به‌زودی راه‌اندازی می‌شود. لطفاً کمی بعد دوباره سر بزنید.', 'toykindangel' ); ?>
                                </p>
                        <?php endif; ?>
                </div>
        </section>
        <?php

else :

        $tka_hero = toykindangel_ssr_hero();
        ?>

        <!-- استوری‌های اینستاگرامی — TKA 0.18.1 (بالای اسلایدر) -->
        <?php toykindangel_ig_stories_bar(); ?>

        <!-- اسلایدر بنرها -->
        <?php if ( get_theme_mod( 'tka_show_slider', true ) && $tka_hero['track'] ) : ?>
        <section class="hero">
                <div class="hero__track"><?php echo $tka_hero['track']; // phpcs:ignore WordPress.Security.EscapeOutput -- built with esc_url()/esc_attr() ?></div>
                <div class="hero__dots"><?php echo $tka_hero['dots']; // phpcs:ignore WordPress.Security.EscapeOutput -- static <i> tags ?></div>
        </section>
        <?php endif; ?>

        <!-- دسته‌بندی‌ها (استوری‌مانند) -->
        <?php if ( get_theme_mod( 'tka_show_categories', true ) && toykindangel_ssr_stories() ) : ?>
        <section class="section">
                <div class="section__head">
                        <span class="section__title">
                                <svg class="ic" aria-hidden="true"><use href="#i-grid"></use></svg>
                                <?php esc_html_e( 'دسته‌بندی‌ها', 'toykindangel' ); ?>
                        </span>
                        <a class="section__more" href="<?php echo esc_url( toykindangel_categories_url() ); ?>"><?php esc_html_e( 'همه دسته‌ها', 'toykindangel' ); ?> <svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg></a>
                </div>
                <div class="stories" id="storyRow"><?php echo toykindangel_ssr_stories(); // phpcs:ignore WordPress.Security.EscapeOutput -- all children escaped ?></div>
        </section>
        <?php endif; ?>

        <!-- پیشنهادهای شگفت‌انگیز + تایمر -->
        <?php if ( get_theme_mod( 'tka_show_featured', true ) && toykindangel_ssr_products()['amazing'] ) : ?>
        <section class="offer">
                <div class="offer__head">
                        <span class="offer__title">
                                <svg class="ic" aria-hidden="true"><use href="#i-fire"></use></svg>
                                <?php esc_html_e( 'پیشنهادهای شگفت‌انگیز', 'toykindangel' ); ?>
                        </span>
                        <div class="timer">
                                <span class="timer__label"><?php esc_html_e( 'زمان باقیمانده:', 'toykindangel' ); ?></span>
                                <span class="timer__digits" data-timer></span>
                        </div>
                </div>
                <div class="offer__track" id="amazingTrack"><?php echo toykindangel_ssr_rail( toykindangel_ssr_products()['amazing'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- pcard markup ?></div>
        </section>
        <?php endif; ?>

        <!-- بنرهای تبلیغاتی ۲×۲ -->
        <?php if ( get_theme_mod( 'tka_show_banner', true ) && toykindangel_ssr_grid() ) : ?>
        <div class="banners" id="bannerGrid"><?php echo toykindangel_ssr_grid(); // phpcs:ignore WordPress.Security.EscapeOutput -- all children escaped ?></div>
        <?php endif; ?>

        <!-- جدیدترین محصولات -->
        <?php if ( get_theme_mod( 'tka_show_newest', true ) && toykindangel_ssr_products()['newest'] ) : ?>
        <section class="section">
                <div class="section__head">
                        <span class="section__title"><?php esc_html_e( 'جدیدترین محصولات', 'toykindangel' ); ?></span>
                </div>
                <div class="plist__track" id="newestTrack"><?php echo toykindangel_ssr_rail( toykindangel_ssr_products()['newest'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- pcard markup ?></div>
        </section>
        <?php endif; ?>

        <!-- پرفروش‌ترین‌ها -->
        <?php if ( get_theme_mod( 'tka_show_best', true ) && toykindangel_ssr_products()['best'] ) : ?>
        <section class="section">
                <div class="section__head">
                        <span class="section__title">
                                <svg class="ic" aria-hidden="true"><use href="#i-cat-stars"></use></svg>
                                <?php esc_html_e( 'پرفروش‌ترین‌ها', 'toykindangel' ); ?>
                        </span>
                </div>
                <div class="plist__track" id="bestTrack"><?php echo toykindangel_ssr_rail( toykindangel_ssr_products()['best'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- pcard markup ?></div>
        </section>
        <?php endif; ?>

        <!-- برندها -->
        <?php if ( get_theme_mod( 'tka_show_brands', true ) && toykindangel_ssr_brands() ) : ?>
        <section class="section">
                <div class="section__head">
                        <span class="section__title"><?php esc_html_e( 'محبوب‌ترین برندها', 'toykindangel' ); ?></span>
                        <a class="section__more" href="#"><?php esc_html_e( 'همه برندها', 'toykindangel' ); ?> <svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg></a>
                </div>
                <div class="brands" id="brandRow"><?php echo toykindangel_ssr_brands(); // phpcs:ignore WordPress.Security.EscapeOutput -- all children escaped ?></div>
        </section>
        <?php endif; ?>

        <!-- دسته‌بندی‌های اصلی فروشگاه — TKA 0.18.0 -->
        <?php
        /*
         * v0.20.0 audit fix C1 — moved to toykindangel_ssr_catgrid() (inc/front-ssr.php).
         * The inline block ran an unbounded get_terms(parent=0) + a per-term
         * get_term_meta + wp_get_attachment_image_url on every homepage view
         * with no transient wrap. The helper bounds the scan, primes term &
         * attachment caches, and serves from the shared theme cache (12h TTL,
         * event-driven invalidation via inc/cache.php).
         */
        $tka_catgrid_items = toykindangel_ssr_catgrid();
        if ( ! empty( $tka_catgrid_items ) ) :
                ?>
                <section class="section tka-catgrid-sec" aria-label="<?php esc_attr_e( 'دسته‌بندی‌های فروشگاه', 'toykindangel' ); ?>">
                        <div class="section__head">
                                <span class="section__title"><?php esc_html_e( 'دسته‌بندی‌های فروشگاه', 'toykindangel' ); ?></span>
                                <a class="section__more" href="<?php echo esc_url( toykindangel_categories_url() ); ?>"><?php esc_html_e( 'همه دسته‌ها', 'toykindangel' ); ?> <svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg></a>
                        </div>
                        <div class="tka-catgrid">
                                <?php
                                foreach ( $tka_catgrid_items as $tka_cat ) {
                                        echo '<a class="tka-catgrid__item" href="' . esc_url( $tka_cat['href'] ) . '">';
                                        echo '<span class="tka-catgrid__img">';
                                        if ( $tka_cat['img'] ) {
                                                echo '<img src="' . esc_url( $tka_cat['img'] ) . '" alt="' . esc_attr( $tka_cat['name'] ) . '" loading="lazy" decoding="async">';
                                        } else {
                                                echo '<svg class="ic" aria-hidden="true"><use href="#i-store"></use></svg>';
                                        }
                                        echo '</span>';
                                        echo '<span class="tka-catgrid__label">' . esc_html( $tka_cat['name'] ) . '</span>';
                                        echo '</a>';
                                }
                                ?>
                        </div>
                </section>
        <?php endif; ?>

        <!-- مزایا -->
        <?php if ( get_theme_mod( 'tka_show_usps', true ) ) : ?>
        <div class="usps">
                <div class="usp">
                        <svg class="ic" aria-hidden="true"><use href="#i-return"></use></svg>
                        <b><?php esc_html_e( 'بازگشت کالا تا ۷ روز', 'toykindangel' ); ?></b>
                        <small><?php esc_html_e( 'در صورت وجود مشکل در سفارش', 'toykindangel' ); ?></small>
                </div>
                <div class="usp">
                        <svg class="ic" aria-hidden="true"><use href="#i-headset"></use></svg>
                        <b><?php esc_html_e( 'پشتیبانی ۷ روز هفته', 'toykindangel' ); ?></b>
                        <small><?php esc_html_e( 'از ۹ صبح تا ۱۲ بامداد', 'toykindangel' ); ?></small>
                </div>
                <div class="usp">
                        <svg class="ic" aria-hidden="true"><use href="#i-truck"></use></svg>
                        <b><?php esc_html_e( 'ارسال سریع', 'toykindangel' ); ?></b>
                        <small><?php esc_html_e( 'تحویل در کوتاه‌ترین زمان', 'toykindangel' ); ?></small>
                </div>
        </div>
        <?php endif; ?>

<?php
endif;

get_footer();
