<?php
/**
 * Main fallback template — فهرست وبلاگ با کارت‌های غنی.
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


get_header();

toykindangel_breadcrumbs();
?>
<div class="tka-container">
	<?php if ( is_home() && ! is_front_page() ) : ?>
		<header class="tka-blog-hero">
			<h1 class="tka-page-title"><?php single_post_title(); ?></h1>
			<p class="tka-blog-hero__sub"><?php esc_html_e( 'مقالات، راهنمای خرید اسباب‌بازی و ایده‌های بازی برای فرزندان دلبندتان', 'toykindangel' ); ?></p>
		</header>
		<?php toykindangel_blog_chips(); ?>
	<?php endif; ?>

	<?php
	$tka_featured_id = 0;
	if ( have_posts() ) :
		// پست ویژه فقط در صفحهٔ اول فهرست وبلاگ (نوشتهٔ چسبان یا اولین نوشته).
		if ( is_home() && ! is_front_page() && ! is_paged() ) :
			the_post();
			$tka_featured_id = get_the_ID();
			toykindangel_featured_hero();
		endif;
		?>
		<div class="tka-blog-grid">
			<?php
			while ( have_posts() ) :
				the_post();
				if ( get_the_ID() === $tka_featured_id ) {
					continue;
				}
				toykindangel_blog_card();
			endwhile;
			?>
		</div>

		<?php
		the_posts_pagination(
			array(
				'prev_text'          => '&laquo; ' . __( 'قبلی', 'toykindangel' ),
				'next_text'          => __( 'بعدی', 'toykindangel' ) . ' &raquo;',
				'screen_reader_text' => __( 'صفحه‌بندی نوشته‌ها', 'toykindangel' ),
				'class'              => 'tka-pagination',
			)
		);
	else :
		?>
		<div class="tka-card">
			<h2><?php esc_html_e( 'موردی یافت نشد', 'toykindangel' ); ?></h2>
			<p><?php esc_html_e( 'متأسفانه محتوایی برای نمایش وجود ندارد.', 'toykindangel' ); ?></p>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
