<?php
/**
 * SVG icon sprite — server-rendered (audit P2-4 fix, v0.17.0).
 *
 * Replaces the former assets/js/icons.js (≈72KB of JS that carried the
 * sprite as a template string and injected it into <body> at runtime):
 * the sprite now ships as a static, XML-validated file
 * (assets/img/icons.svg) and is printed inline right after <body> via
 * wp_body_open — the standard <use href="#i-*"> sprite pattern.
 *
 * Wins over the JS approach (per the wp-performance / WPDS guidance):
 * - zero JS bytes on the render path (no download/parse/execute);
 * - icons paint with the first frame — no icon flash (FOUC);
 * - one fewer HTTP request on every page.
 *
 * Templates keep using their existing markup untouched:
 *   <svg class="ic"><use href="#i-cart"></use></svg>
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Print the inline SVG symbol sprite once per request.
 *
 * The sprite file is read once per request (static cache) and echoed
 * as-is inside the hidden <svg> root it ships with. The markup is a
 * static, trusted asset bundled with the theme (validated at build
 * time), so it is intentionally not escaped.
 *
 * @return void
 */
function toykindangel_icon_sprite() {
	static $sprite = null;

	if ( null === $sprite ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- bundled theme asset, not user data.
		$sprite = (string) file_get_contents( TOYKINDANGEL_DIR . '/assets/img/icons.svg' );
	}

	if ( '' === $sprite ) {
		return;
	}

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static trusted theme asset, XML-validated.
	echo $sprite;
}
add_action( 'wp_body_open', 'toykindangel_icon_sprite' );
