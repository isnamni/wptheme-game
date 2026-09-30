<?php
/**
 * Instagram-style stories — CPT «استوری‌ها» + نوار دایره‌ای + نمایشگر تمام‌صفحه.
 *
 * TKA 0.18.1 — feature:
 * - پست‌تایپ «tka_story»: هر پست یک استوری است (عنوان + تصویر شاخص عمودی ۹:۱۶).
 * - سایز کوچک ۱۵۰×۱۵۰ (tka-story-thumb) در زمان آپلود برای آواتار دایره‌ای ساخته می‌شود.
 * - تنظیمات سفارشی‌ساز: فعال/غیرفعال + محل نمایش (فقط صفحهٔ اصلی / صفحهٔ اصلی + صفحات فروشگاه).
 *   «صفحات فروشگاه» = آرشیو فروشگاه/دسته/برچسب — سبد خرید، پرداخت و حساب کاربری هرگز شامل نمی‌شوند.
 * - نوار و نمایشگر با کلاس‌های tka-ig* جدامانده‌اند تا با هیچ بخش دیگری تداخل نکنند.
 * منطق نمایش در assets/js/ig-stories.js و استایل در assets/css/custom.css (بخش ۱۰) است.
 *
 * @package ToyKindAngel
 */

defined( 'ABSPATH' ) || exit;

/* ------------------------------------------------------------------ */
/* CPT + سایز تصویر                                                    */
/* ------------------------------------------------------------------ */

add_action( 'init', 'toykindangel_ig_stories_cpt' );
/**
 * Register the «tka_story» post type.
 */
function toykindangel_ig_stories_cpt() {
	register_post_type(
		'tka_story',
		array(
			'labels'              => array(
				'name'                  => __( 'استوری‌ها', 'toykindangel' ),
				'singular_name'         => __( 'استوری', 'toykindangel' ),
				'add_new'               => __( 'افزودن استوری', 'toykindangel' ),
				'add_new_item'          => __( 'افزودن استوری جدید', 'toykindangel' ),
				'edit_item'             => __( 'ویرایش استوری', 'toykindangel' ),
				'new_item'              => __( 'استوری جدید', 'toykindangel' ),
				'search_items'          => __( 'جستجوی استوری‌ها', 'toykindangel' ),
				'not_found'             => __( 'هنوز استوری‌ای نساخته‌اید؛ «افزودن استوری» را بزنید و تصویر شاخص (عمودی ۹:۱۶) بگذارید.', 'toykindangel' ),
				'featured_image'        => __( 'تصویر استوری (عمودی ۹:۱۶)', 'toykindangel' ),
				'set_featured_image'    => __( 'انتخاب تصویر استوری', 'toykindangel' ),
				'remove_featured_image' => __( 'حذف تصویر استوری', 'toykindangel' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 22,
			'menu_icon'           => 'dashicons-format-gallery',
			'supports'            => array( 'title', 'thumbnail' ),
			'has_archive'         => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'show_in_rest'        => false,
		)
	);
}

/* سایز کوچک آواتار — در زمان آپلود خودکار ساخته می‌شود. */
add_image_size( 'tka-story-thumb', 150, 150, true );

/* ستون تصویر در فهرست مدیریتی استوری‌ها. */
add_filter(
	'manage_tka_story_posts_columns',
	function ( $tka_cols ) {
		$tka_cols['tka_story_thumb'] = __( 'تصویر', 'toykindangel' );
		return $tka_cols;
	}
);
add_action(
	'manage_tka_story_posts_custom_column',
	function ( $tka_col, $tka_post_id ) {
		if ( 'tka_story_thumb' !== $tka_col ) {
			return;
		}
		$tka_url = get_the_post_thumbnail_url( $tka_post_id, 'tka-story-thumb' );
		echo $tka_url
			? '<img src="' . esc_url( $tka_url ) . '" width="48" height="48" style="border-radius:50%;object-fit:cover" alt="">'
			: '—';
	},
	10,
	2
);

/* ------------------------------------------------------------------ */
/* سفارشی‌ساز — پنل «تنظیمات قالب ToyKind Angel»                       */
/* ------------------------------------------------------------------ */

add_action( 'customize_register', 'toykindangel_ig_stories_customizer', 20 );
/**
 * Section «استوری‌های اینستاگرامی» + تنظیم محل نمایش.
 *
 * @param WP_Customize_Manager $wp_customize Customizer instance.
 */
function toykindangel_ig_stories_customizer( $wp_customize ) {
	$wp_customize->add_section(
		'tka_igstories',
		array(
			'title'       => __( 'استوری‌های اینستاگرامی', 'toykindangel' ),
			'description' => __( 'هر استوری یک پست با تصویر شاخص عمودی (۹:۱۶) است. نمایش: نوار دایره‌ای بالای صفحه + نمایشگر تمام‌صفحه با پروگرس‌بار ۵ ثانیه‌ای.', 'toykindangel' ),
			'panel'       => 'tka_shop_panel',
		)
	);

	$wp_customize->add_setting(
		'tka_igstories_enable',
		array(
			'default'           => true,
			'sanitize_callback' => 'wp_validate_boolean',
		)
	);
	$wp_customize->add_control(
		'tka_igstories_enable',
		array(
			'section' => 'tka_igstories',
			'label'   => __( 'نمایش استوری‌ها', 'toykindangel' ),
			'type'    => 'checkbox',
		)
	);

	$wp_customize->add_setting(
		'tka_igstories_scope',
		array(
			'default'           => 'home',
			'sanitize_callback' => 'sanitize_key',
		)
	);
	$wp_customize->add_control(
		'tka_igstories_scope',
		array(
			'section' => 'tka_igstories',
			'label'   => __( 'محل نمایش', 'toykindangel' ),
			'type'    => 'radio',
			'choices' => array(
				'home' => __( 'فقط صفحهٔ اصلی', 'toykindangel' ),
				'shop' => __( 'صفحهٔ اصلی + صفحات فروشگاه (سبد خرید/پرداخت/حساب هرگز)', 'toykindangel' ),
			),
		)
	);
}

/* ------------------------------------------------------------------ */
/* منطق نمایش                                                          */
/* ------------------------------------------------------------------ */

/**
 * آیا نوار استوری در صفحهٔ فعلی باید نمایش داده شود؟
 *
 * @return bool
 */
function toykindangel_ig_stories_active() {
	if ( ! get_theme_mod( 'tka_igstories_enable', true ) ) {
		return false;
	}

	$tka_scope = get_theme_mod( 'tka_igstories_scope', 'home' );

	if ( 'home' === $tka_scope ) {
		return is_front_page();
	}

	if ( 'shop' === $tka_scope ) {
		return is_front_page()
			|| ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) );
	}

	return false;
}

/**
 * فهرست استوری‌ها از پست‌تایپ.
 *
 * @return array[] {id,name,thumb,full}
 */
function toykindangel_ig_stories_data() {
	$tka_posts = get_posts(
		array(
			'post_type'        => 'tka_story',
			'posts_per_page'   => 20,
			'orderby'          => array( 'date' => 'DESC' ),
			'no_found_rows'    => true,
			'suppress_filters' => false,
		)
	);
	if ( empty( $tka_posts ) ) {
		return array();
	}

	$tka_out = array();
	foreach ( $tka_posts as $tka_post ) {
		$tka_full = get_the_post_thumbnail_url( $tka_post, 'large' );
		if ( ! $tka_full ) {
			continue; /* بدون تصویر شاخص، استوری بی‌معناست. */
		}
		$tka_thumb = get_the_post_thumbnail_url( $tka_post, 'tka-story-thumb' );
		$tka_out[] = array(
			'id'    => (int) $tka_post->ID,
			'name'  => get_the_title( $tka_post ),
			'thumb' => $tka_thumb ? $tka_thumb : $tka_full,
			'full'  => $tka_full,
		);
	}
	return $tka_out;
}

/**
 * نوار استوری‌ها — در front-page.php (بالای اسلایدر) و archive-product.php
 * (بالای فروشگاه) صدا زده می‌شود.
 */
function toykindangel_ig_stories_bar() {
	if ( ! toykindangel_ig_stories_active() ) {
		return;
	}

	$tka_items = toykindangel_ig_stories_data();
	if ( empty( $tka_items ) ) {
		return;
	}

	echo '<div class="tka-igbar" role="region" aria-label="' . esc_attr__( 'استوری‌ها', 'toykindangel' ) . '" data-tka-igstories>';
	foreach ( $tka_items as $tka_i => $tka_item ) {
		echo '<button type="button" class="tka-igbar__item" data-ig-index="' . esc_attr( $tka_i ) . '"'
			. ' data-ig-full="' . esc_url( $tka_item['full'] ) . '"'
			. ' data-ig-name="' . esc_attr( $tka_item['name'] ) . '"'
			. ' data-ig-thumb="' . esc_url( $tka_item['thumb'] ) . '">';
		echo '<span class="tka-igbar__ring"><img src="' . esc_url( $tka_item['thumb'] ) . '"'
			. ' alt="' . esc_attr( $tka_item['name'] ) . '" width="150" height="150" loading="lazy" decoding="async"></span>';
		echo '<span class="tka-igbar__name">' . esc_html( $tka_item['name'] ) . '</span>';
		echo '</button>';
	}
	echo '</div>';
}

/* ------------------------------------------------------------------ */
/* اسکریپت نمایشگر — فقط وقتی نوار رندر می‌شود                          */
/* ------------------------------------------------------------------ */

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! toykindangel_ig_stories_active() ) {
			return;
		}
		$tka_js = TOYKINDANGEL_DIR . '/assets/js/ig-stories.js';
		if ( ! file_exists( $tka_js ) ) {
			return;
		}
		wp_enqueue_script(
			'toykindangel-igstories',
			TOYKINDANGEL_URI . '/assets/js/ig-stories.js',
			array(),
			(string) filemtime( $tka_js ),
			true
		);
	},
	30
);
