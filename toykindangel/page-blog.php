<?php
/**
 * Template Name: وبلاگ (فهرست مطالب)
 *
 * صفحهٔ وبلاگ غنی‌تر: هیروی پست ویژه + چیپ دسته‌ها + گرید کارت‌ها + صفحه‌بندی.
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


get_header();

toykindangel_breadcrumbs();

the_post(); // دادهٔ برگه برای عنوان/توضیح هیرو.

$tka_paged = max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) );

$blog_q = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => 6,
		'paged'          => $tka_paged,
	)
);

$featured_id = 0;
?>
<div class="tka-container">
	<header class="tka-blog-hero">
		<h1 class="tka-page-title"><?php echo esc_html( get_the_title() ); ?></h1>
		<?php
		$tka_blog_desc = get_the_excerpt();
		if ( ! $tka_blog_desc ) {
			$tka_blog_desc = __( 'مقالات، راهنمای خرید اسباب‌بازی و ایده‌های بازی برای فرزندان دلبندتان', 'toykindangel' );
		}
		?>
		<p class="tka-blog-hero__sub"><?php echo esc_html( $tka_blog_desc ); ?></p>
	</header>

	<?php toykindangel_blog_chips(); ?>

	<?php if ( $blog_q->have_posts() ) : ?>

		<?php if ( 1 === $tka_paged ) : ?>
			<?php
			// پست ویژه: نخستین نوشتهٔ چسبان؛ اگر نبود، اولین نوشتهٔ فهرست.
			$tka_sticky = get_option( 'sticky_posts', array() );
			if ( ! empty( $tka_sticky ) ) {
				$tka_feat_q = new WP_Query(
					array(
						'post_type'           => 'post',
						'post_status'         => 'publish',
						'posts_per_page'      => 1,
						'post__in'            => array_map( 'absint', $tka_sticky ),
						'ignore_sticky_posts' => true,
					)
				);
				if ( $tka_feat_q->have_posts() ) {
					$tka_feat_q->the_post();
					$featured_id = get_the_ID();
					toykindangel_featured_hero();
					wp_reset_postdata();
				}
			}
			if ( ! $featured_id && $blog_q->have_posts() ) {
				$blog_q->the_post();
				$featured_id = get_the_ID();
				toykindangel_featured_hero();
			}
			?>
		<?php endif; ?>

		<div class="tka-blog-grid">
			<?php
			while ( $blog_q->have_posts() ) :
				$blog_q->the_post();
				if ( get_the_ID() === $featured_id ) {
					continue;
				}
				toykindangel_blog_card();
			endwhile;
			?>
		</div>

		<?php if ( $blog_q->max_num_pages > 1 ) : ?>
			<nav class="tka-pagination" aria-label="<?php esc_attr_e( 'صفحه‌بندی وبلاگ', 'toykindangel' ); ?>">
				<?php
				echo paginate_links( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core-generated links
					array(
						'total'     => $blog_q->max_num_pages,
						'current'   => $tka_paged,
						'prev_text' => '&laquo; ' . __( 'قبلی', 'toykindangel' ),
						'next_text' => __( 'بعدی', 'toykindangel' ) . ' &raquo;',
						'type'      => 'list',
						'mid_size'  => 2,
					)
				);
				?>
			</nav>
		<?php endif; ?>

	<?php else : ?>
		<div class="tka-card">
			<h2><?php esc_html_e( 'هنوز مطلبی منتشر نشده', 'toykindangel' ); ?></h2>
			<p><?php esc_html_e( 'به‌زودی مقالات و ایده‌های بازی جدید را همین‌جا بخوانید.', 'toykindangel' ); ?></p>
		</div>
	<?php endif; ?>

	<?php wp_reset_postdata(); ?>
</div>
<?php
get_footer();
