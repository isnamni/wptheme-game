<?php
/**
 * Login / Register — demo-style tabs card (RTL).
 *
 * Based on WooCommerce core myaccount/form-login.php: every field name,
 * nonce and hook is preserved so core auth flows, CAPTCHA and
 * social-login plugins keep working. The two columns become the demo
 * tab switcher (ورود / ثبت‌نام).
 *
 * Re-synced against WooCommerce 11.2.0: hook sequence
 * (before_customer_login_form → login_form_start/login/login_end →
 * register_form_tag/register_form_start/register/register_form_end →
 * after_customer_login_form) and the 9.9.0 hardening (required/
 * aria-required, autocomplete, is_string() guard) are unchanged.
 *
 * @package ToyKindAngel
 * @version 11.2.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_customer_login_form' );
?>

<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

<div class="tka-login" id="customer_login">

        <div class="tka-login__tabs" role="tablist">
                <button type="button" class="tka-login__tab is-active" role="tab" aria-selected="true" data-tka-login-tab="login">
                        <?php esc_html_e( 'ورود', 'toykindangel' ); ?>
                </button>
                <button type="button" class="tka-login__tab" role="tab" aria-selected="false" data-tka-login-tab="register">
                        <?php esc_html_e( 'ثبت‌نام', 'toykindangel' ); ?>
                </button>
        </div>

        <div class="tka-login__pane tka-login__pane--login is-active" role="tabpanel" data-tka-login-pane="login">
<?php endif; ?>

                <div class="tka-login__card">
                        <h2 class="tka-login__heading">
                                <svg class="ic" aria-hidden="true"><use href="#i-user"></use></svg>
                                <?php esc_html_e( 'ورود به حساب کاربری', 'toykindangel' ); ?>
                        </h2>
                        <p class="tka-login__hint"><?php esc_html_e( 'برای پیگیری سفارش‌ها و خرید سریع‌تر وارد شوید.', 'toykindangel' ); ?></p>

                        <form class="tka-login__form woocommerce-form woocommerce-form-login login" method="post" novalidate>

                                <?php do_action( 'woocommerce_login_form_start' ); ?>

                                <p class="tka-login__row woocommerce-form-row form-row form-row-wide">
                                        <label for="username"><?php esc_html_e( 'نام کاربری یا نشانی ایمیل', 'toykindangel' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
                                        <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) && is_string( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // @codingStandardsIgnoreLine ?>
                                </p>
                                <p class="tka-login__row woocommerce-form-row form-row form-row-wide">
                                        <label for="password"><?php esc_html_e( 'گذرواژه', 'toykindangel' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
                                        <input class="woocommerce-Input woocommerce-Input--text input-text" type="password" name="password" id="password" autocomplete="current-password" required aria-required="true" />
                                </p>

                                <?php do_action( 'woocommerce_login_form' ); ?>

                                <p class="tka-login__row tka-login__row--split form-row">
                                        <label class="woocommerce-form__label woocommerce-form__label-for-checkbox woocommerce-form-login__rememberme tka-login__remember">
                                                <input class="woocommerce-form__input woocommerce-form__input-checkbox" name="rememberme" type="checkbox" id="rememberme" value="forever" />
                                                <span><?php esc_html_e( 'مرا به خاطر بسپار', 'toykindangel' ); ?></span>
                                        </label>
                                        <?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
                                        <button type="submit" class="tka-login__submit woocommerce-button button woocommerce-form-login__submit" name="login" value="<?php esc_attr_e( 'ورود', 'toykindangel' ); ?>">
                                                <?php esc_html_e( 'ورود', 'toykindangel' ); ?>
                                                <svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg>
                                        </button>
                                </p>
                                <p class="tka-login__lost woocommerce-LostPassword lost_password">
                                        <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>"><?php esc_html_e( 'گذرواژه‌ام را فراموش کرده‌ام', 'toykindangel' ); ?></a>
                                </p>

                                <?php do_action( 'woocommerce_login_form_end' ); ?>

                        </form>
                </div>

<?php if ( 'yes' === get_option( 'woocommerce_enable_myaccount_registration' ) ) : ?>

        </div>

        <div class="tka-login__pane tka-login__pane--register" role="tabpanel" data-tka-login-pane="register">

                <div class="tka-login__card">
                        <h2 class="tka-login__heading">
                                <svg class="ic" aria-hidden="true"><use href="#i-gift"></use></svg>
                                <?php esc_html_e( 'ساخت حساب جدید', 'toykindangel' ); ?>
                        </h2>
                        <p class="tka-login__hint"><?php esc_html_e( 'عضویت رایگان است؛ سفارش‌ها و آدرس‌ها همیشه در دسترس شماست.', 'toykindangel' ); ?></p>

                        <form method="post" class="tka-login__form woocommerce-form woocommerce-form-register register" <?php do_action( 'woocommerce_register_form_tag' ); ?>>

                                <?php do_action( 'woocommerce_register_form_start' ); ?>

                                <?php if ( 'no' === get_option( 'woocommerce_registration_generate_username' ) ) : ?>

                                        <p class="tka-login__row woocommerce-form-row form-row form-row-wide">
                                                <label for="reg_username"><?php esc_html_e( 'نام کاربری', 'toykindangel' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
                                                <input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="username" id="reg_username" autocomplete="username" value="<?php echo ( ! empty( $_POST['username'] ) ) ? esc_attr( wp_unslash( $_POST['username'] ) ) : ''; ?>" required aria-required="true" /><?php // @codingStandardsIgnoreLine ?>
                                        </p>

                                <?php endif; ?>

                                <p class="tka-login__row woocommerce-form-row form-row form-row-wide">
                                        <label for="reg_email"><?php esc_html_e( 'نشانی ایمیل', 'toykindangel' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
                                        <input type="email" class="woocommerce-Input woocommerce-Input--text input-text" name="email" id="reg_email" autocomplete="email" value="<?php echo ( ! empty( $_POST['email'] ) ) ? esc_attr( wp_unslash( $_POST['email'] ) ) : ''; ?>" required aria-required="true" /><?php // @codingStandardsIgnoreLine ?>
                                </p>

                                <?php if ( 'no' === get_option( 'woocommerce_registration_generate_password' ) ) : ?>

                                        <p class="tka-login__row woocommerce-form-row form-row form-row-wide">
                                                <label for="reg_password"><?php esc_html_e( 'گذرواژه', 'toykindangel' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span><span class="screen-reader-text"><?php esc_html_e( 'Required', 'woocommerce' ); ?></span></label>
                                                <input type="password" class="woocommerce-Input woocommerce-Input--text input-text" name="password" id="reg_password" autocomplete="new-password" required aria-required="true" />
                                        </p>

                                <?php else : ?>

                                        <p class="tka-login__hint"><?php esc_html_e( 'لینک تنظیم گذرواژهٔ جدید به ایمیل شما ارسال می‌شود.', 'toykindangel' ); ?></p>

                                <?php endif; ?>

                                <?php do_action( 'woocommerce_register_form' ); ?>

                                <p class="tka-login__row woocommerce-form-row form-row">
                                        <?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
                                        <button type="submit" class="tka-login__submit woocommerce-Button woocommerce-button button woocommerce-form-register__submit" name="register" value="<?php esc_attr_e( 'ثبت‌نام', 'toykindangel' ); ?>">
                                                <?php esc_html_e( 'ثبت‌نام', 'toykindangel' ); ?>
                                                <svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg>
                                        </button>
                                </p>

                                <?php do_action( 'woocommerce_register_form_end' ); ?>

                        </form>
                </div>

        </div>

</div>
<?php endif; ?>

<?php do_action( 'woocommerce_after_customer_login_form' ); ?>
