<?php
/**
 * Bare footer — 1:1 demo categories.html footer (bottomnav only).
 *
 * The demo categories page has no site footer; just the fixed bottom
 * navigation bar.
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


?>
</main><!-- #content -->

</div><!-- /.app -->

<!-- نوار پایین موبایل -->
<nav class="bottomnav" aria-label="<?php esc_attr_e( 'ناوبری اصلی', 'toykindangel' ); ?>">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="<?php echo is_front_page() ? 'on' : ''; ?>">
		<svg class="ic" aria-hidden="true"><use href="#i-store"></use></svg><?php esc_html_e( 'فروشگاه', 'toykindangel' ); ?>
	</a>
	<a href="<?php echo esc_url( toykindangel_categories_url() ); ?>" class="<?php echo ( is_page( 'categories' ) || ( function_exists( 'is_shop' ) && is_shop() ) ) ? 'on' : ''; ?>">
		<svg class="ic" aria-hidden="true"><use href="#i-grid"></use></svg><?php esc_html_e( 'دسته‌بندی‌ها', 'toykindangel' ); ?>
	</a>
	<a href="<?php echo esc_url( toykindangel_cart_url() ); ?>" class="<?php echo ( function_exists( 'is_cart' ) && is_cart() ) ? 'on' : ''; ?>">
		<svg class="ic" aria-hidden="true"><use href="#i-cart"></use></svg><?php esc_html_e( 'سبد خرید', 'toykindangel' ); ?>
	</a>
	<a href="<?php echo esc_url( toykindangel_account_url() ); ?>">
		<svg class="ic" aria-hidden="true"><use href="#i-user"></use></svg><?php esc_html_e( 'ورود و عضویت', 'toykindangel' ); ?>
	</a>
</nav>

<?php wp_footer(); ?>
</body>
</html>
