<?php
/**
 * Site footer — 1:1 demo markup (footer + fixed bottomnav) + back-to-top.
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$tka_footer_seo_default = 'فروشگاه اسباب‌بازی فرشته مهربون با هزاران کالای متنوع، تمامی گروه‌های سنی را پوشش می‌دهد. انواع عروسک، پولیشی، اسباب‌بازی‌های ساختنی و آموزشی، وسایل نقلیه، آب‌بازی، ست‌های بازی، لوازم‌التحریر و ملزومات نوزاد با بهترین قیمت و ضمانت سلامت فیزیکی کالا ارائه می‌شود. مشهد، امامت ۴۲، فروشگاه فرشته مهربون — پشتیبانی: ۰۹۱۵۲۰۰۶۶۳۰';
?>
</main><!-- #content -->

<footer class="footer" role="contentinfo">
        <div class="footer__cols">
                <div>
                        <h5><?php esc_html_e( 'راهنمای خرید', 'toykindangel' ); ?></h5>
                        <?php if ( has_nav_menu( 'footer_col1' ) ) : ?>
                                <?php
                                wp_nav_menu(
                                        array(
                                                'theme_location' => 'footer_col1',
                                                'container'      => false,
                                                'menu_class'     => 'footer__menu',
                                                'depth'          => 1,
                                        )
                                );
                                ?>
                        <?php else : ?>
                        <ul>
                                <li><a href="#"><?php esc_html_e( 'نحوه ثبت سفارش', 'toykindangel' ); ?></a></li>
                                <li><a href="#"><?php esc_html_e( 'رویه‌های ارسال', 'toykindangel' ); ?></a></li>
                                <li><a href="#"><?php esc_html_e( 'روش‌های پرداخت', 'toykindangel' ); ?></a></li>
                                <li><a href="#"><?php esc_html_e( 'شرایط مرجوعی', 'toykindangel' ); ?></a></li>
                        </ul>
                        <?php endif; ?>
                </div>
                <div>
                        <h5><?php esc_html_e( 'خدمات مشتریان', 'toykindangel' ); ?></h5>
                        <?php if ( has_nav_menu( 'footer' ) ) : ?>
                                <?php
                                wp_nav_menu(
                                        array(
                                                'theme_location' => 'footer',
                                                'container'      => false,
                                                'menu_class'     => 'footer__menu',
                                                'depth'          => 1,
                                        )
                                );
                                ?>
                        <?php else : ?>
                                <ul>
                                        <li><a href="#"><?php esc_html_e( 'پرسش‌های متداول', 'toykindangel' ); ?></a></li>
                                        <li><a href="#"><?php esc_html_e( 'تماس با ما', 'toykindangel' ); ?></a></li>
                                        <li><a href="#"><?php esc_html_e( 'پیگیری سفارش', 'toykindangel' ); ?></a></li>
                                        <li><a href="#"><?php esc_html_e( 'قوانین و مقررات', 'toykindangel' ); ?></a></li>
                                </ul>
                        <?php endif; ?>
                </div>

                <?php /* ستون اطلاعات تماس و شبکه‌های اجتماعی — فقط دسکتاپ (responsive.css) */ ?>
                <div class="footer__col footer__col--desktop">
                        <h5><?php esc_html_e( 'اطلاعات تماس', 'toykindangel' ); ?></h5>
                        <ul class="footer__contact-list">
                                <li>
                                        <svg class="ic" aria-hidden="true"><use href="#i-pin"></use></svg>
                                        <span><?php echo esc_html( get_theme_mod( 'tka_address', __( 'مشهد، امامت ۴۲، فروشگاه فرشته مهربون', 'toykindangel' ) ) ); ?></span>
                                </li>
                                <li>
                                        <svg class="ic" aria-hidden="true"><use href="#i-headset"></use></svg>
                                        <a class="num" href="tel:<?php echo esc_attr( get_theme_mod( 'tka_phone_raw', '09152006630' ) ); ?>"><?php echo esc_html( get_theme_mod( 'tka_phone', __( '۰۹۱۵۲۰۰۶۶۳۰', 'toykindangel' ) ) ); ?></a>
                                </li>
                                <li>
                                        <svg class="ic" aria-hidden="true"><use href="#i-share"></use></svg>
                                        <a href="mailto:<?php echo esc_attr( get_theme_mod( 'tka_email_raw', 'info@toykindangel.com' ) ); ?>"><?php echo esc_html( get_theme_mod( 'tka_email', __( 'info@toykindangel.com', 'toykindangel' ) ) ); ?></a>
                                </li>
                                <li>
                                        <svg class="ic" aria-hidden="true"><use href="#i-clock"></use></svg>
                                        <span><?php echo esc_html( get_theme_mod( 'tka_worktime', __( 'هر روز از ۹ صبح تا ۱۲ بامداد', 'toykindangel' ) ) ); ?></span>
                                </li>
                        </ul>
                </div>
                <div class="footer__col footer__col--desktop">
                        <h5><?php esc_html_e( 'همراه شوید', 'toykindangel' ); ?></h5>
                        <div class="footer__social">
                                <?php
                                $tka_socials = array(
                                        'instagram' => array( __( 'اینستاگرام', 'toykindangel' ), __( 'https://instagram.com/', 'toykindangel' ) ),
                                        'telegram'  => array( __( 'تلگرام', 'toykindangel' ), __( 'https://t.me/', 'toykindangel' ) ),
                                        'whatsapp'  => array( __( 'واتس‌اپ', 'toykindangel' ), __( 'https://wa.me/', 'toykindangel' ) ),
                                        'aparat'    => array( __( 'آپارات', 'toykindangel' ), __( 'https://aparat.com/', 'toykindangel' ) ),
                                );
                                foreach ( $tka_socials as $tka_key => $tka_soc ) :
                                        $tka_url = get_theme_mod( 'tka_' . $tka_key, $tka_soc[1] );
                                        if ( '' === $tka_url ) {
                                                continue;
                                        }
                                        ?>
                                        <a href="<?php echo esc_url( $tka_url ); ?>" target="_blank" rel="noopener noreferrer">
                                                <?php echo esc_html( $tka_soc[0] ); ?>
                                                <svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg>
                                        </a>
                                <?php endforeach; ?>
                        </div>
                </div>
        </div>
        <p class="footer__seo"><?php echo esc_html( get_theme_mod( 'tka_footer_seo', $tka_footer_seo_default ) ); ?></p>
        <div class="footer__copy"><?php esc_html_e( 'کلیه حقوق استفاده از محتوای این وب‌سایت نزد فروشگاه', 'toykindangel' ); ?> <?php echo esc_html( get_bloginfo( 'name' ) ); ?> <?php esc_html_e( 'محفوظ است.', 'toykindangel' ); ?></div>
</footer>

</div><!-- /.app -->

<!-- نوار پایین موبایل -->
<nav class="bottomnav" aria-label="<?php esc_attr_e( 'ناوبری اصلی', 'toykindangel' ); ?>">
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="<?php echo is_front_page() ? 'on' : ''; ?>">
                <svg class="ic" aria-hidden="true"><use href="#i-store"></use></svg><?php esc_html_e( 'فروشگاه', 'toykindangel' ); ?>
        </a>
        <button type="button" class="<?php echo ( is_page( 'categories' ) || ( function_exists( 'is_shop' ) && is_shop() ) ) ? 'on' : ''; ?>" data-tka-drawer="cats" aria-expanded="false" aria-controls="tka-cats-drawer">
                <svg class="ic" aria-hidden="true"><use href="#i-grid"></use></svg><?php esc_html_e( 'دسته‌بندی‌ها', 'toykindangel' ); ?>
        </button>
        <a href="<?php echo esc_url( toykindangel_cart_url() ); ?>" class="<?php echo ( function_exists( 'is_cart' ) && is_cart() ) ? 'on' : ''; ?>">
                <svg class="ic" aria-hidden="true"><use href="#i-cart"></use></svg><?php esc_html_e( 'سبد خرید', 'toykindangel' ); ?>
        </a>
        <a href="<?php echo esc_url( toykindangel_account_url() ); ?>">
                <svg class="ic" aria-hidden="true"><use href="#i-user"></use></svg><?php esc_html_e( 'ورود و عضویت', 'toykindangel' ); ?>
        </a>
</nav>

<!-- بازگشت به بالا -->
<button type="button" id="tka-backtop" class="tka-backtop" aria-label="<?php esc_attr_e( 'بازگشت به بالا', 'toykindangel' ); ?>" hidden>
        <svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg>
</button>

<?php
/* دراورها: منوی اصلی + مرورگر دسته‌بندی‌ها (inc/cat-drawer.php) + پس‌زمینهٔ مشترک */
toykindangel_cats_drawer();
?>
<div class="tka-drawer__backdrop" id="tka-drawer-backdrop" hidden></div>
<?php $GLOBALS['tka_backdrop_printed'] = true; /* دراور مینی‌سبد (inc/minicart.php) پس‌زمینهٔ تکراری چاپ نکند */ ?>

<?php wp_footer(); ?>
</body>
</html>
