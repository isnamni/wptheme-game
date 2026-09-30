<?php
/**
 * Archive template (blog categories, tags, author, date).
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
	<header class="tka-archive-header">
		<h1 class="tka-page-title"><?php the_archive_title(); ?></h1>
		<?php the_archive_description( '<p class="tka-muted">', '</p>' ); ?>
	</header>

	<?php toykindangel_blog_chips(); ?>

	<?php
	if ( have_posts() ) :
		echo '<div class="tka-blog-grid">';
		while ( have_posts() ) :
			the_post();
			toykindangel_blog_card();
		endwhile;
		echo '</div>';

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
			<h2><?php esc_html_e( 'چیزی پیدا نشد', 'toykindangel' ); ?></h2>
			<p><?php esc_html_e( 'برای این بخش هنوز محتوایی ثبت نشده است.', 'toykindangel' ); ?></p>
			<?php get_search_form(); ?>
		</div>
	<?php endif; ?>
</div>
<?php
get_footer();
