<?php
/**
 * Theme Customizer: shop contact info, social links, store texts and
 * homepage section toggles (wired to the real demo sections in Step 2).
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Register customizer settings.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function toykindangel_customize_register( $wp_customize ) {

        // ---------- Panel ----------
        $wp_customize->add_panel(
                'tka_shop_panel',
                array(
                        'title'    => __( 'تنظیمات قالب ToyKind Angel', 'toykindangel' ),
                        'priority' => 10,
                )
        );

        // ---------- Section: Contact ----------
        $wp_customize->add_section(
                'tka_contact',
                array(
                        'title' => __( 'اطلاعات تماس فروشگاه', 'toykindangel' ),
                        'panel' => 'tka_shop_panel',
                )
        );

        $contact_fields = array(
                'tka_phone'    => array( __( 'شماره تلفن', 'toykindangel' ), 'text' ),
                'tka_email'    => array( __( 'ایمیل', 'toykindangel' ), 'email' ),
                'tka_address'  => array( __( 'آدرس', 'toykindangel' ), 'textarea' ),
                'tka_worktime' => array( __( 'ساعات کاری', 'toykindangel' ), 'text' ),
        );

        foreach ( $contact_fields as $id => $field ) {
                $wp_customize->add_setting(
                        $id,
                        array(
                                'default'           => '',
                                'sanitize_callback' => 'textarea' === $field[1] ? 'sanitize_textarea_field' : 'sanitize_text_field',
                        )
                );
                $wp_customize->add_control(
                        $id,
                        array(
                                'label'   => $field[0],
                                'section' => 'tka_contact',
                                'type'    => $field[1],
                        )
                );
        }

        // ---------- Section: Store texts ----------
        $wp_customize->add_section(
                'tka_texts',
                array(
                        'title' => __( 'متن‌های فروشگاه', 'toykindangel' ),
                        'panel' => 'tka_shop_panel',
                )
        );

        $wp_customize->add_setting(
                'tka_promo_text',
                array(
                        'default'           => 'ارسال رایگان برای خریدهای بالای ۵۰۰ هزار تومان در مشهد &nbsp;•&nbsp; پشتیبانی ۷ روز هفته &nbsp;•&nbsp; ضمانت سلامت فیزیکی کالا',
                        'sanitize_callback' => 'sanitize_text_field',
                )
        );
        $wp_customize->add_control(
                'tka_promo_text',
                array(
                        'label'       => __( 'متن نوار اطلاع‌رسانی بالای سایت', 'toykindangel' ),
                        'description' => __( 'موارد را با • از هم جدا کنید.', 'toykindangel' ),
                        'section'     => 'tka_texts',
                        'type'        => 'text',
                )
        );

        $wp_customize->add_setting(
                'tka_footer_seo',
                array(
                        'default'           => 'فروشگاه اسباب‌بازی فرشته مهربون با هزاران کالای متنوع، تمامی گروه‌های سنی را پوشش می‌دهد. انواع عروسک، پولیشی، اسباب‌بازی‌های ساختنی و آموزشی، وسایل نقلیه، آب‌بازی، ست‌های بازی، لوازم‌التحریر و ملزومات نوزاد با بهترین قیمت و ضمانت سلامت فیزیکی کالا ارائه می‌شود. مشهد، امامت ۴۲، فروشگاه فرشته مهربون — پشتیبانی: ۰۹۱۵۲۰۰۶۶۳۰',
                        'sanitize_callback' => 'sanitize_textarea_field',
                )
        );
        $wp_customize->add_control(
                'tka_footer_seo',
                array(
                        'label'   => __( 'متن سئوی فوتر', 'toykindangel' ),
                        'section' => 'tka_texts',
                        'type'    => 'textarea',
                )
        );

        // ---------- Section: Social ----------
        $wp_customize->add_section(
                'tka_social',
                array(
                        'title' => __( 'شبکه‌های اجتماعی', 'toykindangel' ),
                        'panel' => 'tka_shop_panel',
                )
        );

        $social_fields = array(
                'tka_instagram' => 'Instagram',
                'tka_telegram'  => 'Telegram',
                'tka_whatsapp'  => 'WhatsApp',
                'tka_aparat'    => 'Aparat',
        );

        foreach ( $social_fields as $id => $label ) {
                $wp_customize->add_setting(
                        $id,
                        array(
                                'default'           => '',
                                'sanitize_callback' => 'esc_url_raw',
                        )
                );
                $wp_customize->add_control(
                        $id,
                        array(
                                'label'   => $label,
                                'section' => 'tka_social',
                                'type'    => 'url',
                        )
                );
        }

        // ---------- Section: Banners & slider (Step 3) ----------
        $wp_customize->add_section(
                'tka_banners',
                array(
                        'title'       => __( 'اسلایدر و بنرهای صفحه اول', 'toykindangel' ),
                        'description' => __( 'اگر تصویری تنظیم نشود، همان تصویر نمونهٔ دمو نمایش داده می‌شود.', 'toykindangel' ),
                        'panel'       => 'tka_shop_panel',
                )
        );

        /**
         * Helper-style loop: image + link pairs.
         * $fields = array( setting_base => label )
         */
        $tka_banner_fields = array(
                'tka_strip'      => __( 'بنر نوار بالای صفحه', 'toykindangel' ),
                'tka_hero_1'     => __( 'اسلاید ۱', 'toykindangel' ),
                'tka_hero_2'     => __( 'اسلاید ۲', 'toykindangel' ),
                'tka_hero_3'     => __( 'اسلاید ۳', 'toykindangel' ),
                'tka_hero_4'     => __( 'اسلاید ۴', 'toykindangel' ),
                'tka_banner_1'   => __( 'بنر گرید ۱', 'toykindangel' ),
                'tka_banner_2'   => __( 'بنر گرید ۲', 'toykindangel' ),
                'tka_banner_3'   => __( 'بنر گرید ۳', 'toykindangel' ),
                'tka_banner_4'   => __( 'بنر گرید ۴', 'toykindangel' ),
        );

        foreach ( $tka_banner_fields as $base => $label ) {
                $wp_customize->add_setting(
                        $base . '_img',
                        array(
                                'default'           => '',
                                'sanitize_callback' => 'esc_url_raw',
                        )
                );
                $wp_customize->add_control(
                        new WP_Customize_Image_Control(
                                $wp_customize,
                                $base . '_img',
                                array(
                                        'label'   => $label . ' — تصویر',
                                        'section' => 'tka_banners',
                                )
                        )
                );

                $wp_customize->add_setting(
                        $base . '_link',
                        array(
                                'default'           => '#',
                                'sanitize_callback' => 'esc_url_raw',
                        )
                );
                $wp_customize->add_control(
                        $base . '_link',
                        array(
                                'label'   => $label . ' — لینک',
                                'section' => 'tka_banners',
                                'type'    => 'url',
                        )
                );
        }

        // ---------- Section: Homepage toggles ----------
        $wp_customize->add_section(
                'tka_homepage',
                array(
                        'title'       => __( 'سکشن‌های صفحه اول', 'toykindangel' ),
                        'description' => __( 'فعال/غیرفعال کردن سکشن‌های صفحه اصلی مطابق دمو.', 'toykindangel' ),
                        'panel'       => 'tka_shop_panel',
                )
        );

        $toggle_fields = array(
                'tka_show_slider'     => __( 'نمایش اسلایدر', 'toykindangel' ),
                'tka_show_categories' => __( 'نمایش دسته‌بندی‌ها', 'toykindangel' ),
                'tka_show_featured'   => __( 'نمایش پیشنهادهای شگفت‌انگیز', 'toykindangel' ),
                'tka_show_banner'     => __( 'نمایش بنرهای تبلیغاتی', 'toykindangel' ),
                'tka_show_newest'     => __( 'نمایش جدیدترین محصولات', 'toykindangel' ),
                'tka_show_best'       => __( 'نمایش پرفروش‌ترین‌ها', 'toykindangel' ),
                'tka_show_brands'     => __( 'نمایش برندها', 'toykindangel' ),
                'tka_show_usps'       => __( 'نمایش بخش مزایا', 'toykindangel' ),
                'tka_show_bnpl'       => __( 'نمایش برچسب «خرید قسطی» روی کارت محصولات', 'toykindangel' ),
                'tka_fallback_demo'   => __( 'وقتی داده وردپرس/ووکامرس نیست، از داده نمونهٔ دمو استفاده شود (پیشنهاد: خاموش)', 'toykindangel' ),
                'tka_front_without_demo' => __( 'نمایش صفحه اصلی بدون درون‌ریزی دمو (حالت استاندارد وردپرس)', 'toykindangel' ),
        );

        foreach ( $toggle_fields as $id => $label ) {
                $wp_customize->add_setting(
                        $id,
                        array(
                                'default'           => ( 'tka_front_without_demo' === $id || 'tka_fallback_demo' === $id ) ? false : true,
                                'sanitize_callback' => 'wp_validate_boolean',
                        )
                );
                $wp_customize->add_control(
                        $id,
                        array(
                                'label'   => $label,
                                'section' => 'tka_homepage',
                                'type'    => 'checkbox',
                        )
                );
        }

        // ---------- Section: Product page (PDP) ----------
        $wp_customize->add_section(
                'tka_pdp',
                array(
                        'title'       => __( 'صفحه محصول', 'toykindangel' ),
                        'description' => __( 'متن‌های گارانتی، روش‌های ارسال و ریل محصولات مشابه (پیش‌فرض‌ها مطابق دمو هستند).', 'toykindangel' ),
                        'panel'       => 'tka_shop_panel',
                )
        );

        $tka_pdp_text_fields = array(
                'tka_warranty_text' => array(
                        __( 'متن گارانتی', 'toykindangel' ),
                        __( 'گارانتی سلامت فیزیکی کالا', 'toykindangel' ),
                ),
                'tka_ship_1_title'  => array(
                        __( 'عنوان روش ارسال اول', 'toykindangel' ),
                        __( 'عادی', 'toykindangel' ),
                ),
                'tka_ship_1_note'   => array(
                        __( 'توضیح روش ارسال اول', 'toykindangel' ),
                        __( 'ارسال از انبار فروشگاه — ۲ تا ۴ روز کاری', 'toykindangel' ),
                ),
                'tka_ship_2_title'  => array(
                        __( 'عنوان روش ارسال دوم', 'toykindangel' ),
                        __( 'اکسپرس', 'toykindangel' ),
                ),
                'tka_ship_2_note'   => array(
                        __( 'توضیح روش ارسال دوم', 'toykindangel' ),
                        __( 'ارسال در سریع‌ترین زمان — ویژه مشهد', 'toykindangel' ),
                ),
        );

        foreach ( $tka_pdp_text_fields as $tka_id => $tka_field ) {
                $wp_customize->add_setting(
                        $tka_id,
                        array(
                                'default'           => $tka_field[1],
                                'sanitize_callback' => 'sanitize_text_field',
                        )
                );
                $wp_customize->add_control(
                        $tka_id,
                        array(
                                'label'   => $tka_field[0],
                                'section' => 'tka_pdp',
                                'type'    => 'text',
                        )
                );
        }

        $wp_customize->add_setting(
                'tka_pdp_related',
                array(
                        'default'           => true,
                        'sanitize_callback' => 'wp_validate_boolean',
                )
        );
        $wp_customize->add_control(
                'tka_pdp_related',
                array(
                        'label'   => __( 'نمایش ریل «محصولات مشابه» در صفحه محصول', 'toykindangel' ),
                        'section' => 'tka_pdp',
                        'type'    => 'checkbox',
                )
        );

        /* TKA (18-e): جشنواره — show/hide the campaign ribbon on cards + PDP. */
        $wp_customize->add_setting(
                'tka_festival_badge',
                array(
                        'default'           => true,
                        'sanitize_callback' => 'wp_validate_boolean',
                )
        );
        $wp_customize->add_control(
                'tka_festival_badge',
                array(
                        'label'       => __( 'نمایش نشان «جشنواره» روی کارت محصولات و صفحه محصول', 'toykindangel' ),
                        'description' => __( 'محصولات دارای برچسب جشنواره، نشان بنفش/طلایی جشنواره را نشان می‌دهند و در «پیشنهادهای شگفت‌انگیز» هم می‌آیند.', 'toykindangel' ),
                        'section'     => 'tka_pdp',
                        'type'        => 'checkbox',
                )
        );
}
add_action( 'customize_register', 'toykindangel_customize_register' );
