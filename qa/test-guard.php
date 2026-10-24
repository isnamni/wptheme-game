<?php
/**
 * Behaviour test for toykindangel_cache_version_guard() — v0.28.0.
 *
 * Stubs the WordPress API surface that inc/cache.php touches and asserts
 * via OBSERVABLE state (no call counters):
 *  fresh : first run after an update (no stored version, stale transients
 *          present) → every tka_cache_* transient purged + version stored.
 *  same  : run again with the SAME version → nothing touched.
 *  bump  : stored version differs (theme updated again) → purge runs again.
 *
 * Usage: php test-guard.php <fresh|same|bump>
 */

error_reporting( E_ALL );

$TKA_MODE = isset( $argv[1] ) ? $argv[1] : 'fresh';
$TKA_VER  = 'bump' === $TKA_MODE ? '0.29.0' : '0.28.0';

/* ---------- WP stubs ---------- */
$GLOBALS['__options']    = array();
$GLOBALS['__transients'] = array();

function get_option( $name, $default = false ) {
	return array_key_exists( $name, $GLOBALS['__options'] ) ? $GLOBALS['__options'][ $name ] : $default;
}
function update_option( $name, $value, $autoload = null ) {
	$GLOBALS['__options'][ $name ] = $value;
	return true;
}
function get_transient( $key ) {
	return isset( $GLOBALS['__transients'][ $key ] ) ? $GLOBALS['__transients'][ $key ] : false;
}
function set_transient( $key, $value, $ttl = 0 ) {
	$GLOBALS['__transients'][ $key ] = $value;
	return true;
}
function delete_transient( $key ) {
	unset( $GLOBALS['__transients'][ $key ] );
	return true;
}
function add_action( ...$args ) {} /* hooks not exercised; guard is called directly. */

class FakeWPDB {
	public $options = 'wp_options';
	public function query( $sql ) {
		/* Simulate the LIKE sweep: drop every _transient(_timeout)?_tka_cache_* row. */
		foreach ( array_keys( $GLOBALS['__transients'] ) as $k ) {
			if ( 0 === strpos( $k, '_transient_tka_cache_' ) || 0 === strpos( $k, '_transient_timeout_tka_cache_' ) ) {
				unset( $GLOBALS['__transients'][ $k ] );
			}
		}
		return 1;
	}
}
$GLOBALS['wpdb'] = new FakeWPDB();

define( 'ABSPATH', '/tmp/' );
define( 'TOYKINDANGEL_VERSION', $TKA_VER );

require '/home/z/wptheme-game/theme-src/toykindangel/inc/cache.php';

function t( $label, $cond ) {
	echo ( $cond ? 'PASS' : 'FAIL' ) . " — $label\n";
	if ( ! $cond ) { exit( 1 ); }
}

function seed_site_state() {
	/* The state an upgraded production site would have. */
	$GLOBALS['__transients']['_transient_tka_cache_cats_tree']          = 'STALE_TREE_ONLY_AB_BAZI';
	$GLOBALS['__transients']['_transient_timeout_tka_cache_cats_tree'] = time() + 999;
	$GLOBALS['__transients']['_transient_tka_cache_drawer_menu']       = 'STALE';
	$GLOBALS['__transients']['_transient_tka_cache_home_stories']      = 'STALE';
	$GLOBALS['__transients']['_transient_tka_cache_home_trending']     = 'STALE';
	$GLOBALS['__transients']['_transient_tka_cache_price_diff_ids']    = 'STALE';
	$GLOBALS['__transients']['_transient_tka_cache_orphan_unknown']    = 'STALE'; /* unknown key from an older build */
	$GLOBALS['__transients']['_transient_tka_unrelated']               = 'keep me'; /* different family — must survive */
}

switch ( $TKA_MODE ) {
	case 'fresh':
		seed_site_state();
		toykindangel_cache_version_guard();
		t( 'new version stored', '0.28.0' === get_option( 'tka_cache_version', '' ) );
		t( 'stale cats_tree purged (named flush)', ! isset( $GLOBALS['__transients']['_transient_tka_cache_cats_tree'] ) );
		t( 'stale drawer_menu purged', ! isset( $GLOBALS['__transients']['_transient_tka_cache_drawer_menu'] ) );
		t( 'stale home_stories purged', ! isset( $GLOBALS['__transients']['_transient_tka_cache_home_stories'] ) );
		t( 'stale home_trending purged', ! isset( $GLOBALS['__transients']['_transient_tka_cache_home_trending'] ) );
		t( 'stale price_diff_ids purged (product flush)', ! isset( $GLOBALS['__transients']['_transient_tka_cache_price_diff_ids'] ) );
		t( 'unknown orphan key purged (LIKE sweep)', ! isset( $GLOBALS['__transients']['_transient_tka_cache_orphan_unknown'] ) );
		t( 'unrelated transient untouched', 'keep me' === $GLOBALS['__transients']['_transient_tka_unrelated'] );
		break;

	case 'same':
		seed_site_state();
		$GLOBALS['__options']['tka_cache_version'] = '0.28.0'; /* already up-to-date */
		toykindangel_cache_version_guard();
		t( 'same version → cache untouched (stale tree still served)', 'STALE_TREE_ONLY_AB_BAZI' === $GLOBALS['__transients']['_transient_tka_cache_cats_tree'] );
		t( 'same version → unrelated untouched', 'keep me' === $GLOBALS['__transients']['_transient_tka_unrelated'] );
		break;

	case 'bump':
		seed_site_state();
		$GLOBALS['__options']['tka_cache_version'] = '0.28.0'; /* previous theme version */
		toykindangel_cache_version_guard();
		t( 'bumped version stored', '0.29.0' === get_option( 'tka_cache_version', '' ) );
		t( 'stale tree purged again on version bump', ! isset( $GLOBALS['__transients']['_transient_tka_cache_cats_tree'] ) );
		break;
}

echo "MODE $TKA_MODE: ALL PASS\n";
