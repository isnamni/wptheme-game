<?php
/**
 * Edit account form — demo-style card (RTL).
 *
 * Based on WooCommerce core myaccount/form-edit-account.php (11.0.0);
 * field names, nonces and hooks unchanged.
 *
 * @package ToyKindAngel
 * @version 11.0.0
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_before_edit_account_form' );
?>
<div class="tka-edit tka-card">
	<h2 class="tka-card__title">
		<svg class="ic" aria-hidden="true"><use href="#i-user"></use></svg>
		<?php esc_html_e( 'اطلاعات حساب', 'toykindangel' ); ?>
	</h2>

	<form class="tka-edit__form woocommerce-EditAccountForm edit-account" action="" method="post" <?php do_action( 'woocommerce_edit_account_form_tag' ); ?>>

		<?php do_action( 'woocommerce_edit_account_form_start' ); ?>

		<div class="tka-edit__grid">
			<p class="tka-edit__row woocommerce-form-row form-row form-row-first">
				<label for="account_first_name"><?php esc_html_e( 'نام', 'toykindangel' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_first_name" id="account_first_name" autocomplete="given-name" value="<?php echo esc_attr( $user->first_name ); ?>" aria-required="true" />
			</p>
			<p class="tka-edit__row woocommerce-form-row form-row form-row-last">
				<label for="account_last_name"><?php esc_html_e( 'نام خانوادگی', 'toykindangel' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_last_name" id="account_last_name" autocomplete="family-name" value="<?php echo esc_attr( $user->last_name ); ?>" aria-required="true" />
			</p>

			<p class="tka-edit__row tka-edit__row--wide woocommerce-form-row form-row form-row-wide">
				<label for="account_display_name"><?php esc_html_e( 'نام نمایشی', 'toykindangel' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="text" class="woocommerce-Input woocommerce-Input--text input-text" name="account_display_name" id="account_display_name" aria-describedby="account_display_name_description" value="<?php echo esc_attr( $user->display_name ); ?>" aria-required="true" />
				<span id="account_display_name_description" class="tka-edit__hint"><em><?php echo wc_reviews_enabled() ? esc_html__( 'این نام در حساب کاربری و دیدگاه‌ها نمایش داده می‌شود', 'toykindangel' ) : esc_html__( 'این نام در حساب کاربری شما نمایش داده می‌شود', 'toykindangel' ); ?></em></span>
			</p>

			<p class="tka-edit__row tka-edit__row--wide woocommerce-form-row form-row form-row-wide">
				<label for="account_email"><?php esc_html_e( 'نشانی ایمیل', 'toykindangel' ); ?>&nbsp;<span class="required" aria-hidden="true">*</span></label>
				<input type="email" class="woocommerce-Input woocommerce-Input--email input-text" name="account_email" id="account_email" autocomplete="email" value="<?php echo esc_attr( $user->user_email ); ?>" aria-required="true" />
			</p>
		</div>

		<?php do_action( 'woocommerce_edit_account_form_fields' ); ?>

		<fieldset class="tka-edit__pass">
			<legend><?php esc_html_e( 'تغییر گذرواژه', 'toykindangel' ); ?></legend>

			<div class="tka-edit__grid">
				<p class="tka-edit__row tka-edit__row--wide woocommerce-form-row form-row form-row-wide">
					<label for="password_current"><?php esc_html_e( 'گذرواژهٔ کنونی (بدون تغییر بماند اگر نمی‌خواهید عوض شود)', 'toykindangel' ); ?></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_current" id="password_current" autocomplete="current-password" />
				</p>
				<p class="tka-edit__row tka-edit__row--wide woocommerce-form-row form-row form-row-wide">
					<label for="password_1"><?php esc_html_e( 'گذرواژهٔ جدید', 'toykindangel' ); ?></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_1" id="password_1" autocomplete="new-password" />
				</p>
				<p class="tka-edit__row tka-edit__row--wide woocommerce-form-row form-row form-row-wide">
					<label for="password_2"><?php esc_html_e( 'تکرار گذرواژهٔ جدید', 'toykindangel' ); ?></label>
					<input type="password" class="woocommerce-Input woocommerce-Input--password input-text" name="password_2" id="password_2" autocomplete="new-password" />
				</p>
			</div>
		</fieldset>

		<?php do_action( 'woocommerce_edit_account_form' ); ?>

		<div class="tka-edit__actions">
			<?php wp_nonce_field( 'save_account_details', 'save-account-details-nonce' ); ?>
			<button type="submit" class="tka-login__submit woocommerce-Button button" name="save_account_details" value="<?php esc_attr_e( 'ذخیرهٔ تغییرات', 'toykindangel' ); ?>">
				<?php esc_html_e( 'ذخیرهٔ تغییرات', 'toykindangel' ); ?>
				<svg class="ic" aria-hidden="true"><use href="#i-check"></use></svg>
			</button>
			<input type="hidden" name="action" value="save_account_details" />
		</div>

		<?php do_action( 'woocommerce_edit_account_form_end' ); ?>
	</form>
</div>

<?php do_action( 'woocommerce_after_edit_account_form' ); ?>
