<?php
/**
 * Search results template — grouped & deduplicated.
 *
 * Results are split into three unique groups:
 *   «محصولات»  → demo pcards (toykindangel_pcard_wrap, inc/product-card.php)
 *   «مقالات»   → the shared rich blog card (toykindangel_blog_card)
 *   «سایر»     → simple cards for pages/other post types
 * Empty groups are not printed. Every group is deduplicated by post ID:
 * the previous template rendered via setup_postdata() without resetting
 * the global $post, so every row echoed the LAST result of the split
 * loop (four identical cards).
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


get_header();

global $wp_query;

/* ------------------------------------------------------------------ */
/* Split results: products vs posts vs the rest                        */
/* ------------------------------------------------------------------ */
$tka_q_post_type    = get_query_var( 'post_type' );
$tka_product_search = 'product' === $tka_q_post_type || ( is_array( $tka_q_post_type ) && in_array( 'product', (array) $tka_q_post_type, true ) );

$tka_products = array();
$tka_posts    = array();
$tka_other    = array();
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		$tka_result_id = get_the_ID();
		if ( 'product' === get_post_type() && function_exists( 'wc_get_product' ) && wc_get_product( $tka_result_id ) instanceof WC_Product ) {
			$tka_products[] = $tka_result_id;
		} elseif ( 'post' === get_post_type() ) {
			$tka_posts[] = $tka_result_id;
		} else {
			$tka_other[] = $tka_result_id;
		}
	}
	rewind_posts();
}

/* Dedupe every group by ID (defensive — search SQL can repeat rows). */
$tka_products = array_values( array_unique( $tka_products ) );
$tka_posts    = array_values( array_unique( $tka_posts ) );
$tka_other    = array_values( array_unique( $tka_other ) );

$tka_has_results    = $tka_products || $tka_posts || $tka_other;
$tka_shop_style     = $tka_product_search || (bool) $tka_products;
$tka_product_count  = $tka_product_search ? (int) $wp_query->found_posts : count( $tka_products );
?>
<div class="tka-container">
	<header class="tka-archive-header">
		<h1 class="tka-page-title">
			<?php
			printf(
				/* translators: %s: search query */
				esc_html__( 'نتایج جستجو برای: %s', 'toykindangel' ),
				'<span>' . esc_html( get_search_query() ) . '</span>'
			);
			?>
			<?php if ( $tka_shop_style && $tka_product_count ) : ?>
				<span class="tka-shophead__count num"><?php echo esc_html( toykindangel_fa_digits( $tka_product_count ) ); ?> <?php esc_html_e( 'کالا', 'toykindangel' ); ?></span>
			<?php endif; ?>
		</h1>
		<?php get_search_form(); ?>
	</header>

	<?php if ( $tka_has_results ) : ?>

		<?php if ( $tka_products ) : ?>
			<section class="tka-search-group">
				<h2 class="section__title">
					<svg class="ic" aria-hidden="true"><use href="#i-cart"></use></svg>
					<?php esc_html_e( 'محصولات', 'toykindangel' ); ?>
					<span class="tka-shophead__count num"><?php echo esc_html( toykindangel_fa_digits( count( $tka_products ) ) ); ?></span>
				</h2>
				<div class="tka-pgrid">
					<?php
					foreach ( $tka_products as $tka_pid ) {
						echo toykindangel_pcard_wrap( wc_get_product( $tka_pid ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside renderer.
					}
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $tka_posts ) : ?>
			<section class="tka-search-group">
				<h2 class="section__title">
					<svg class="ic" aria-hidden="true"><use href="#i-thumb"></use></svg>
					<?php esc_html_e( 'مقالات', 'toykindangel' ); ?>
					<span class="tka-shophead__count num"><?php echo esc_html( toykindangel_fa_digits( count( $tka_posts ) ) ); ?></span>
				</h2>
				<div class="tka-blog-grid">
					<?php
					foreach ( $tka_posts as $tka_post_id ) {
						$tka_post = get_post( $tka_post_id );
						if ( ! $tka_post ) {
							continue;
						}

						/*
						 * setup_postdata() alone does NOT move the global $post
						 * pointer — template tags kept reading the last result
						 * of the split loop above and printed the same card
						 * over and over. Assign the global explicitly.
						 */
						$GLOBALS['post'] = $tka_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- required for template tags outside the loop.
						setup_postdata( $tka_post );
						toykindangel_blog_card();
					}
					wp_reset_postdata();
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php if ( $tka_other ) : ?>
			<section class="tka-search-group">
				<h2 class="section__title">
					<svg class="ic" aria-hidden="true"><use href="#i-tag"></use></svg>
					<?php esc_html_e( 'سایر نتایج', 'toykindangel' ); ?>
					<span class="tka-shophead__count num"><?php echo esc_html( toykindangel_fa_digits( count( $tka_other ) ) ); ?></span>
				</h2>
				<div class="tka-grid tka-grid-2">
					<?php
					foreach ( $tka_other as $tka_post_id ) {
						$tka_post = get_post( $tka_post_id );
						if ( ! $tka_post ) {
							continue;
						}
						$GLOBALS['post'] = $tka_post; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- required for template tags outside the loop.
						setup_postdata( $tka_post );
						?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( 'tka-card' ); ?>>
							<h2 class="tka-entry-title">
								<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
							</h2>
							<div class="tka-entry-excerpt"><?php the_excerpt(); ?></div>
						</article>
						<?php
					}
					wp_reset_postdata();
					?>
				</div>
			</section>
		<?php endif; ?>

		<?php
		the_posts_pagination(
			array(
				'prev_text' => '&laquo; ' . __( 'قبلی', 'toykindangel' ),
				'next_text' => __( 'بعدی', 'toykindangel' ) . ' &raquo;',
			)
		);
		?>

	<?php elseif ( $tka_product_search ) : ?>

		<div class="tka-empty">
			<svg class="ic tka-empty__ic" aria-hidden="true"><use href="#i-cat-camera"></use></svg>
			<p><?php esc_html_e( 'کالایی با این عبارت پیدا نشد!', 'toykindangel' ); ?></p>
			<a class="tka-btn" href="<?php echo esc_url( toykindangel_categories_url() ); ?>"><?php esc_html_e( 'دیدن دسته‌بندی‌ها', 'toykindangel' ); ?></a>
		</div>

	<?php else : ?>

		<div class="tka-card">
			<h2><?php esc_html_e( 'نتیجه‌ای یافت نشد', 'toykindangel' ); ?></h2>
			<p><?php esc_html_e( 'عبارت دیگری را امتحان کنید یا از دسته‌بندی‌ها استفاده کنید.', 'toykindangel' ); ?></p>
		</div>

	<?php endif; ?>
</div>
<?php
get_footer();
