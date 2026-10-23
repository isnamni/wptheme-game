<?php
/**
 * 404 template.
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


get_header();
?>
<div class="tka-container">
	<div class="tka-card tka-error-404">
		<p class="tka-404-icon" aria-hidden="true">🧸</p>
		<h1 class="tka-page-title">۴۰۴ — <?php esc_html_e( 'صفحه پیدا نشد!', 'toykindangel' ); ?></h1>
		<p><?php esc_html_e( 'به نظر می‌رسد این صفحه گم شده، مثل یک اسباب‌بازی زیر تخت! از جستجو استفاده کنید یا به صفحه اصلی برگردید.', 'toykindangel' ); ?></p>
		<?php get_search_form(); ?>
		<p>
			<a class="tka-btn" href="<?php echo esc_url( home_url( '/' ) ); ?>">
				<?php esc_html_e( 'بازگشت به فروشگاه', 'toykindangel' ); ?>
			</a>
		</p>
	</div>
</div>
<?php
get_footer();
