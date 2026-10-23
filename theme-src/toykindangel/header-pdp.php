<?php
/**
 * PDP header — 1:1 demo product.html head.
 *
 * The demo product page ships its own compact top bar (pdp__bar) and has
 * no topstrip / promo-strip / site header, so this variant only opens
 * the document and the .app frame; the bar itself is rendered by the
 * product template (it needs the product title).
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

<div class="app tka-pdp">
