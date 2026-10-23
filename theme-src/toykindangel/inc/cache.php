<?php
/**
 * Shared cache layer (v0.19.0 refactor).
 *
 * یک لایهٔ کش سبک برای داده‌های «گرانِ محاسبه، آهسته‌به‌تغییر» که قبلاً
 * در هر Request از اول ساخته می‌شدند (درخت دسته‌بندی‌ها، استوری‌ها،
 * برندها، IDهای تخفیف قیمتی، نتایج جستجوی زنده).
 *
 * قواعد طراحی:
 * - خروجی توابع داده هیچ تغییری نمی‌کند؛ فقط تعداد دفعات اجرای Query.
 * - دو سطح: static درون-Request (بدون هزینه) + transient بین-Requestها.
 * - باطل‌سازی رویدادمحور است؛ TTL فقط ضمانتِ نهایی (backstop) است:
 *   * دادهٔ ترم‌ها (درخت/استوری/برند) → created_term/edited_term/delete_term
 *     برای product_cat، product_brand، tka_brand و tka_store_cat + تغییر
 *     ساختار پیوند یکتا.
 *   * دادهٔ قیمت محصول → هوک‌های transient ووکامرس (هر تغییر محصول که
 *     WC را به پاک‌سازی کش‌هایش وادار کند این کش را هم می‌شوید).
 * - روی نصب‌هایی که Object Cache پایدار دارند transientها خودکار به
 *   memory map می‌شوند؛ روی بقیه، گزینه‌های non-autoload در جدول options.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read a theme cache entry.
 *
 * Per-request static first (free), then transient. Returns null on miss
 * (a stored empty array() is a valid hit — only false/absent means miss).
 *
 * @param string $key Cache key (without prefix).
 * @return mixed|null
 */
function toykindangel_cache_get( $key ) {
	$tka_key = 'tka_cache_' . $key;
	if ( ! isset( $GLOBALS['tka_cache_local'] ) ) {
		$GLOBALS['tka_cache_local'] = array();
	}
	if ( array_key_exists( $tka_key, $GLOBALS['tka_cache_local'] ) ) {
		return $GLOBALS['tka_cache_local'][ $tka_key ];
	}

	$tka_value = get_transient( $tka_key );
	$tka_value = ( false === $tka_value ) ? null : $tka_value;

	$GLOBALS['tka_cache_local'][ $tka_key ] = $tka_value;
	return $tka_value;
}

/**
 * Store a theme cache entry (updates the per-request static too, so a
 * value computed and stored in the same request is not recomputed).
 *
 * @param string $key   Cache key.
 * @param mixed  $value Serializable value.
 * @param int    $ttl   TTL seconds (backstop only; hooks flush earlier).
 * @return void
 */
function toykindangel_cache_set( $key, $value, $ttl ) {
	if ( ! isset( $GLOBALS['tka_cache_local'] ) ) {
		$GLOBALS['tka_cache_local'] = array();
	}
	$GLOBALS['tka_cache_local'][ 'tka_cache_' . $key ] = $value;
	set_transient( 'tka_cache_' . $key, $value, (int) $ttl );
}

/**
 * Flush every cache entry derived from taxonomy terms.
 *
 * @return void
 */
function toykindangel_cache_flush_term_data() {
	delete_transient( 'tka_cache_cats_tree' );
	delete_transient( 'tka_cache_home_stories' );
	delete_transient( 'tka_cache_home_brands' );
	delete_transient( 'tka_cache_home_catgrid' );
	delete_transient( 'tka_cache_cats_page_grid' );
	delete_transient( 'tka_cache_cats_page_popular' );
	delete_transient( 'tka_cache_drawer_menu' );
	unset( $GLOBALS['tka_cache_local']['tka_cache_cats_tree'] );
	unset( $GLOBALS['tka_cache_local']['tka_cache_home_stories'] );
	unset( $GLOBALS['tka_cache_local']['tka_cache_home_brands'] );
	unset( $GLOBALS['tka_cache_local']['tka_cache_home_catgrid'] );
	unset( $GLOBALS['tka_cache_local']['tka_cache_cats_page_grid'] );
	unset( $GLOBALS['tka_cache_local']['tka_cache_cats_page_popular'] );
	unset( $GLOBALS['tka_cache_local']['tka_cache_drawer_menu'] );
}

/**
 * Flush every cache entry derived from product price data.
 *
 * @return void
 */
function toykindangel_cache_flush_product_data() {
	delete_transient( 'tka_cache_price_diff_ids' );
	unset( $GLOBALS['tka_cache_local']['tka_cache_price_diff_ids'] );
}

/**
 * Term create/edit/delete → flush term-derived caches (only for the
 * shop taxonomies; blog categories are irrelevant to the cached values).
 *
 * @param int    $term_id  Term ID.
 * @param int    $tt_id    Term taxonomy ID.
 * @param string $taxonomy Taxonomy slug.
 * @return void
 */
function toykindangel_cache_on_term_change( $term_id, $tt_id, $taxonomy ) {
	if ( in_array( $taxonomy, array( 'product_cat', 'product_brand', 'tka_brand', 'tka_store_cat' ), true ) ) {
		toykindangel_cache_flush_term_data();
	}
}
add_action( 'created_term', 'toykindangel_cache_on_term_change', 10, 3 );
add_action( 'edited_term', 'toykindangel_cache_on_term_change', 10, 3 );
add_action( 'delete_term', 'toykindangel_cache_on_term_change', 10, 3 );

/* Permalink rebuilds can change every cached term link. */
add_action( 'permalink_structure_changed', 'toykindangel_cache_flush_term_data' );

/* Product price/stock changes flow through WooCommerce's own transient
 * cleanup; ride the same events so the price-diff ID cache stays honest. */
add_action( 'woocommerce_delete_product_transients', 'toykindangel_cache_flush_product_data', 5 );
add_action( 'woocommerce_new_product', 'toykindangel_cache_flush_product_data' );
add_action( 'woocommerce_update_product', 'toykindangel_cache_flush_product_data' );
add_action( 'woocommerce_delete_product', 'toykindangel_cache_flush_product_data' );

/* Theme switch = fresh settings state. */
add_action( 'after_switch_theme', 'toykindangel_cache_flush_term_data' );
