<?php
/**
 * Festival / campaign layer (جشنواره) — Task 18-e.
 *
 * A product is flagged for the store campaign with the meta
 * `_tka_festival = 'yes'` (set by the demo importer / product admin).
 * Flagged products get:
 *
 * - a distinctive «جشنواره» ribbon on every product card
 *   (printed from inc/product-card.php next to the red % off badge);
 * - a matching pill inside the PDP price box, printed through the
 *   standard `woocommerce_single_product_summary` hook (the template
 *   itself is NOT touched here);
 * - inclusion in the «پیشنهادهای شگفت‌انگیز» rail — the SSR renderer
 *   reads the WooCommerce on-sale id list, so flagged ids are merged
 *   into WooCommerce's `wc_products_onsale` cache (see the transient
 *   bridge below).
 *
 * The whole layer can be switched off from the Customizer with the
 * `tka_festival_badge` toggle (صفحه محصول → «نمایش نشان جشنواره»).
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------ */
/* Helpers                                                            */
/* ------------------------------------------------------------------ */

if ( ! function_exists( 'toykindangel_festival_active' ) ) {
	/**
	 * Is the festival badge layer enabled? (Customizer toggle, default on.)
	 *
	 * @return bool
	 */
	function toykindangel_festival_active() {
		return (bool) get_theme_mod( 'tka_festival_badge', true );
	}
}

if ( ! function_exists( 'toykindangel_is_festival_product' ) ) {
	/**
	 * Is the product flagged for the current campaign?
	 *
	 * @param WC_Product|int $product Product object or id.
	 * @return bool
	 */
	function toykindangel_is_festival_product( $product ) {
		$tka_pid = ( $product instanceof WC_Product ) ? $product->get_id() : (int) $product;
		if ( ! $tka_pid ) {
			return false;
		}
		return 'yes' === get_post_meta( $tka_pid, '_tka_festival', true );
	}
}

if ( ! function_exists( 'toykindangel_festival_badge_html' ) ) {
	/**
	 * Festival pill markup (icon + label).
	 *
	 * @param string $extra_class Optional modifier class.
	 * @return string
	 */
	function toykindangel_festival_badge_html( $extra_class = '' ) {
		return '<span class="tka-festival' . ( $extra_class ? ' ' . esc_attr( $extra_class ) : '' ) . '">'
			. '<svg class="ic" aria-hidden="true"><use href="#i-fire"></use></svg>'
			. esc_html__( 'جشنواره', 'toykindangel' )
			. '</span>';
	}
}

if ( ! function_exists( 'toykindangel_festival_ids' ) ) {
	/**
	 * All published product ids flagged for the festival.
	 *
	 * @return int[]
	 */
	function toykindangel_festival_ids() {
		$tka_ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'publish',
				'fields'         => 'ids',
				'posts_per_page' => 50,
				// phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_key, WordPress.DB.SlowDBQuery.slow_db_query_meta_value
				'meta_key'       => '_tka_festival',
				'meta_value'     => 'yes',
				// phpcs:enable
				'no_found_rows'  => true,
			)
		);
		return array_map( 'intval', (array) $tka_ids );
	}
}

/* ------------------------------------------------------------------ */
/* PDP: pill near the price (standard hook — template untouched)      */
/* ------------------------------------------------------------------ */

if ( ! function_exists( 'toykindangel_festival_pdp_badge' ) ) {
	/**
	 * Print the festival pill at the top of the WooCommerce summary area
	 * (inside the demo .pricebox, right above the add-to-cart region).
	 *
	 * @return void
	 */
	function toykindangel_festival_pdp_badge() {
		global $product;
		if ( ! toykindangel_festival_active() || ! toykindangel_is_festival_product( $product ) ) {
			return;
		}
		echo toykindangel_festival_badge_html( 'tka-festival--pdp' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped in the helper.
	}
}
add_action( 'woocommerce_single_product_summary', 'toykindangel_festival_pdp_badge', 2 );

/* ------------------------------------------------------------------ */
/* Amazing-offer rail: merge flagged ids into WooCommerce on-sale     */
/* ------------------------------------------------------------------ */

/**
 * «پیشنهادهای شگفت‌انگیز» is fed by wc_get_product_ids_on_sale() (cached in
 * the `wc_products_onsale` transient). WooCommerce 10+ exposes no filter
 * there, so flagged ids are merged straight into the cache whenever the
 * cache is (re)built. This keeps the SSR rail showing festival products
 * even when their price is not discounted — without touching the SSR file.
 *
 * @return void
 */
function toykindangel_festival_sync_onsale_cache() {
	if ( ! function_exists( 'wc_get_product_ids_on_sale' ) ) {
		return;
	}

	if ( ! toykindangel_festival_active() ) {
		/* Toggle off → drop the cache so a clean rebuild has no festival ids. */
		delete_transient( 'wc_products_onsale' );
		return;
	}

	$tka_ids = get_transient( 'wc_products_onsale' );
	if ( false === $tka_ids ) {
		$tka_ids = wc_get_product_ids_on_sale(); /* Rebuilds + stores the transient. */
	}

	$tka_festival = toykindangel_festival_ids();
	if ( empty( $tka_festival ) ) {
		return;
	}

	$tka_merged = array_unique( array_merge( array_map( 'intval', (array) $tka_ids ), $tka_festival ) );
	if ( $tka_merged !== (array) $tka_ids ) {
		set_transient( 'wc_products_onsale', $tka_merged, DAY_IN_SECONDS * 30 );
	}
}
add_action( 'woocommerce_delete_product_transients', 'toykindangel_festival_sync_onsale_cache', 5 );

/**
 * Keep the cache honest after the Customizer toggle changes.
 *
 * @return void
 */
function toykindangel_festival_cache_flush() {
	if ( function_exists( 'wc_delete_product_transients' ) ) {
		wc_delete_product_transients();
	} else {
		delete_transient( 'wc_products_onsale' );
	}
}
add_action( 'customize_save_after', 'toykindangel_festival_cache_flush' );
