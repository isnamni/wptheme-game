<?php
/**
 * Search form — demo searchbox markup + live AJAX search panel.
 *
 * The dropdown (.tka-ls) is filled by assets/js/live-search.js via the
 * tka_live_search AJAX endpoint (inc/ajax-search.php).
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


$tka_ls_id = toykindangel_ls_instance();
?>
<form role="search" method="get" class="searchbox" action="<?php echo esc_url( home_url( '/' ) ); ?>" data-tka-live="1">
	<svg class="ic" aria-hidden="true"><use href="#i-search"></use></svg>
	<input
		type="search"
		aria-label="<?php esc_attr_e( 'جستجو در فروشگاه', 'toykindangel' ); ?>"
		placeholder="<?php esc_attr_e( 'جستجو در فرشته مهربون…', 'toykindangel' ); ?>"
		value="<?php echo get_search_query(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
		name="s"
		autocomplete="off"
		role="combobox"
		aria-autocomplete="list"
		aria-expanded="false"
		aria-controls="tka-ls-panel-<?php echo esc_attr( (string) $tka_ls_id ); ?>"
	>
	<?php if ( function_exists( 'WC' ) ) : ?>
		<input type="hidden" name="post_type" value="product">
	<?php endif; ?>
	<div
		class="tka-ls"
		id="tka-ls-panel-<?php echo esc_attr( (string) $tka_ls_id ); ?>"
		role="listbox"
		aria-label="<?php esc_attr_e( 'نتایج جستجوی زنده', 'toykindangel' ); ?>"
		hidden
	></div>
</form>
