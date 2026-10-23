<?php
/**
 * ToyKind Angel — Persian gettext overrides & WooCommerce language fixes.
 *
 * The fa_IR language pack is loaded, but a few core strings still leak
 * English (or an awkward translation) on the shop:
 *
 * 1. Privacy/terms texts on checkout + my-account registration. The
 *    defaults are translatable, BUT the demo store has the ENGLISH
 *    defaults STORED in options (woocommerce_checkout_privacy_policy_text
 *    / woocommerce_registration_privacy_policy_text) — stored options
 *    bypass gettext entirely. Both layers are fixed here:
 *      - gettext override (fresh installs / defaults),
 *      - `woocommerce_get_privacy_policy_text` filter that swaps the
 *        known English sentences (keeping the [privacy_policy] /
 *        [terms] placeholders intact) even when they come from options.
 * 2. «بیرون رفتن» → «خروج» (fa_IR rendering of "Log out" in the
 *    my-account navigation).
 * 3. The CustomerEmailVerification prompt on My Account → Orders prints
 *    a mis-rendered translated banner with a dangling button for
 *    logged-in customers. It is redundant UX for this shop, so the
 *    `woocommerce_customer_email_verification_should_show_prompt` filter
 *    (WC 11.1+) disables it; a container fallback removes the callback
 *    on older builds.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------------- */
/* 1) gettext overrides (domain: woocommerce) — section header, not code. phpcs:ignore Squiz.PHP.CommentedOutCode.Found */
/* ------------------------------------------------------------------------- */

if ( ! function_exists( 'toykindangel_fa_gettext' ) ) {
	/**
	 * Replace selected WooCommerce strings before they hit the screen.
	 *
	 * @param string $translation Current translation.
	 * @param string $text        Original (untranslated) string.
	 * @param string $domain      Text domain.
	 * @return string
	 */
	function toykindangel_fa_gettext( $translation, $text, $domain ) {
		if ( 'woocommerce' !== $domain ) {
			return $translation;
		}

		switch ( $text ) {
			case 'Log out':
				return 'خروج';

			case 'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our %s.':
				return 'اطلاعات شخصی شما برای پردازش سفارش، پشتیبانی از تجربهٔ شما در سراسر این وب‌سایت و سایر مقاصد شرح‌داده‌شده در %s ما استفاده می‌شود.';

			case 'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our %s.':
				return 'اطلاعات شخصی شما برای پشتیبانی از تجربهٔ شما در سراسر این وب‌سایت، مدیریت دسترسی به حساب کاربری‌تان و سایر مقاصد شرح‌داده‌شده در %s ما استفاده می‌شود.';

			case 'I have read and agree to the website %s':
				return 'من %s را خوانده‌ام و می‌پذیرم.';
		}

		return $translation;
	}
}
add_filter( 'gettext', 'toykindangel_fa_gettext', 10, 3 );

/* ------------------------------------------------------------------------- */
/* 2) privacy/terms texts even when the ENGLISH text is stored in options     */
/* ------------------------------------------------------------------------- */

if ( ! function_exists( 'toykindangel_fa_policy_text' ) ) {
	/**
	 * Swap the known English privacy/terms sentences for Persian while
	 * keeping the [privacy_policy] / [terms] placeholders (they are
	 * replaced with real page links by wc_replace_policy_page_link_placeholders()).
	 *
	 * Runs for both stored option values (which never pass through
	 * gettext) and the translatable defaults.
	 *
	 * @param string $text Policy/checkbox text, placeholders included.
	 * @return string
	 */
	function toykindangel_fa_policy_text( $text ) {
		$map = array(
			'Your personal data will be used to process your order, support your experience throughout this website, and for other purposes described in our [privacy_policy].'
				=> 'اطلاعات شخصی شما برای پردازش سفارش، پشتیبانی از تجربهٔ شما در سراسر این وب‌سایت و سایر مقاصد شرح‌داده‌شده در [privacy_policy] ما استفاده می‌شود.',

			'Your personal data will be used to support your experience throughout this website, to manage access to your account, and for other purposes described in our [privacy_policy].'
				=> 'اطلاعات شخصی شما برای پشتیبانی از تجربهٔ شما در سراسر این وب‌سایت، مدیریت دسترسی به حساب کاربری‌تان و سایر مقاصد شرح‌داده‌شده در [privacy_policy] ما استفاده می‌شود.',

			'I have read and agree to the website [terms]'
				=> 'من [terms] را خوانده‌ام و می‌پذیرم.',
		);

		return strtr( (string) $text, $map );
	}
}
add_filter( 'woocommerce_get_privacy_policy_text', 'toykindangel_fa_policy_text', 10 );
add_filter( 'woocommerce_get_terms_and_conditions_checkbox_text', 'toykindangel_fa_policy_text', 10 );

/* ------------------------------------------------------------------------- */
/* 3) disable the mis-rendered email-verification banner on My Account       */
/* ------------------------------------------------------------------------- */

if ( ! function_exists( 'toykindangel_hide_email_verification_prompt' ) ) {
	/**
	 * Never show the CustomerEmailVerification prompt on My Account.
	 *
	 * The banner ships with a broken-looking fa_IR string + floating
	 * button (see screenshots/PROBLEMS.md #3) and duplicates flows the
	 * verification email already covers.
	 *
	 * @param bool $should_show Whether WC wants to show the prompt.
	 * @return bool
	 */
	function toykindangel_hide_email_verification_prompt( $should_show ) {
		return false;
	}
}
add_filter( 'woocommerce_customer_email_verification_should_show_prompt', 'toykindangel_hide_email_verification_prompt', 10 );

/**
 * Defensive fallback for WC builds without the 11.1 filter: unhook the
 * controller's render_prompt() callback once WooCommerce is bootstrapped.
 *
 * @return void
 */
function toykindangel_unhook_email_verification_prompt() {
	if ( ! function_exists( 'wc_get_container' ) || ! class_exists( '\Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController' ) ) {
		return;
	}

	try {
		$controller = wc_get_container()->get( \Automattic\WooCommerce\Internal\CustomerEmailVerification\VerificationController::class );
	} catch ( \Throwable $t ) { // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- not output.
		return;
	}

	if ( $controller && has_action( 'woocommerce_before_account_orders', array( $controller, 'render_prompt' ) ) ) {
		remove_action( 'woocommerce_before_account_orders', array( $controller, 'render_prompt' ) );
	}
}
add_action( 'woocommerce_init', 'toykindangel_unhook_email_verification_prompt', 20 );
