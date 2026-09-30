<?php
/**
 * Bare header — 1:1 demo categories.html header (chead: search + logo).
 *
 * Used by the categories-browser page (page-categories.php). It intentionally
 * omits the topstrip / promo-strip / main header of header.php because the
 * demo categories page ships its own compact header.
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

<div class="app">

	<!-- هدر صفحه دسته‌بندی‌ها (جستجو + لوگو) — دقیقاً مطابق دمو -->
	<header class="chead" role="banner">
		<label class="chead__search">
			<svg class="ic" aria-hidden="true"><use href="#i-search"></use></svg>
			<span class="screen-reader-text"><?php esc_html_e( 'جستجو در دسته‌بندی‌ها', 'toykindangel' ); ?></span>
			<input type="search" id="catSearch" placeholder="<?php esc_attr_e( 'جستجو در فرشته مهربون', 'toykindangel' ); ?>">
		</label>
		<a class="chead__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<?php
			$tka_logo_id = get_theme_mod( 'custom_logo' );
			if ( $tka_logo_id ) {
				echo wp_get_attachment_image( $tka_logo_id, 'full', false, array( 'class' => '' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				?>
				<span class="chead__logo__txt"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
			<?php } ?>
		</a>
	</header>
