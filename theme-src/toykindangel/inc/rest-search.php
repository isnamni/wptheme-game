<?php
/**
 * REST live search — جستجوی زندهٔ هدر روی مسیر REST (Task 20-c).
 *
 * Route    : GET /wp-json/toykindangel/v1/search?q=<term>&limit=<n>
 * Response : { html, count, term, all_url }
 *            (همان payload ای که assets/js/live-search.js بعد از باز کردن
 *            پاکتِ admin-ajax یعنی res.data مصرف می‌کند — فقط بدون پاکت.)
 *
 * The route is the REST twin of the legacy admin-ajax endpoint
 * `tka_live_search` (inc/ajax-search.php) and REUSES its pipeline
 * through `toykindangel_live_search_payload()` — single source of truth
 * for the query/render/escape logic; nothing is duplicated here. The
 * admin-ajax handler stays intact for backward compatibility.
 *
 * Why `permission_callback => __return_true` is safe (same posture as
 * the nopriv ajax handler, which also runs without a nonce): the route
 * is a read-only search over public content only (post_status=publish),
 * input is sanitized (sanitize_text_field / absint) and clamped, it
 * exposes no privileged data (no drafts, users or private meta) and the
 * frontend debounce keeps request volume sane — mirroring core's own
 * public search behaviour.
 *
 * Note: this file expects inc/ajax-search.php to be loaded first
 * (functions.php requires both; the require_once of this file is owned
 * by functions.php, see window.TKA_LIVE.restUrl for the JS side).
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ثبت مسیر REST جستجوی زنده (rest_api_init).
 *
 * @return void
 */
function toykindangel_register_rest_search() {
	register_rest_route(
		'toykindangel/v1',
		'/search',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'toykindangel_rest_search',
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only search, see file header.
			'permission_callback' => '__return_true',
			'args'                => array(
				'q'     => array(
					'type'              => 'string',
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
					'description'       => 'واژهٔ جستجو (حداقل ۲ نویسه).',
				),
				'limit' => array(
					'type'              => 'integer',
					'default'           => 6,
					'sanitize_callback' => 'absint',
					// سقف ۸ در toykindangel_live_search_payload() اعمال می‌شود.
					'description'       => 'سقف نتایج محصول (۱ تا ۸).',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'toykindangel_register_rest_search' );

/**
 * کال‌بک REST جستجوی زنده — همان payload هندلر admin-ajax.
 *
 * v0.19.0: WP_Error (آستانهٔ throttle هر IP) به‌صورت استاندارد به پاسخ
 * خطای REST (429) تبدیل می‌شود؛ کش/throttle داخل payload پیاده‌سازی شده
 * و هر دو endpoint آن را به ارث می‌برند.
 *
 * @param WP_REST_Request $request درخواست REST.
 * @return WP_REST_Response|WP_Error
 */
function toykindangel_rest_search( WP_REST_Request $request ) {
	$term = trim( (string) $request->get_param( 'q' ) );

	// limit=0 (absint) → پیش‌فرض ۶؛ سقف ۸ داخل payload کلیپ می‌شود.
	$limit = (int) $request->get_param( 'limit' );
	if ( $limit < 1 ) {
		$limit = 6;
	}

	$tka_payload = toykindangel_live_search_payload( $term, $limit );
	if ( is_wp_error( $tka_payload ) ) {
		return $tka_payload;
	}

	return rest_ensure_response( $tka_payload );
}
