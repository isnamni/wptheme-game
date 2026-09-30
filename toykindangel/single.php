<?php
/**
 * Single post template.
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
        <?php
        while ( have_posts() ) :
                the_post();
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'tka-card tka-single' ); ?>>
                        <header class="tka-entry-header">
                                <div class="tka-post-card__top">
                                        <?php
                                        $tka_first_cat = toykindangel_post_first_category();
                                        if ( $tka_first_cat ) :
                                                ?>
                                                <a class="tka-post-card__badge" href="<?php echo esc_url( get_category_link( $tka_first_cat ) ); ?>"><?php echo esc_html( $tka_first_cat->name ); ?></a>
                                        <?php endif; ?>
                                </div>
                                <h1 class="tka-page-title"><?php the_title(); ?></h1>
                                <p class="tka-entry-meta tka-muted">
                                        <span class="tka-post-card__author">
                                                <?php echo get_avatar( get_the_author_meta( 'ID' ), 24, '', '', array( 'extra_attr' => 'aria-hidden="true" loading="lazy"' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                                <span><?php the_author(); ?></span>
                                        </span>
                                        <span class="tka-dot" aria-hidden="true">•</span>
                                        <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                                        <span class="tka-dot" aria-hidden="true">•</span>
                                        <span class="tka-read-time">
                                                <svg class="ic" aria-hidden="true"><use href="#i-clock"></use></svg>
                                                <?php echo esc_html( toykindangel_reading_time_text() ); ?>
                                        </span>
                                </p>
                        </header>

                        <?php if ( has_post_thumbnail() ) : ?>
                                <div class="tka-post-thumb"><?php the_post_thumbnail( 'large' ); ?></div>
                        <?php endif; ?>

                        <div class="tka-entry-content">
                                <?php
                                the_content();
                                wp_link_pages();
                                ?>
                        </div>

                        <?php
                        /*
                         * برچسب‌ها: حذف برچسب‌های هم‌نام و برچسبی که همان نام
                         * بج دستهٔ بالای مقاله دارد تا چیپ تکراری رندر نشود
                         * (PROBLEMS.md #9).
                         */
                        $tka_tags        = get_the_tags();
                        $tka_badge_cat   = toykindangel_post_first_category();
                        $tka_badge_name  = $tka_badge_cat ? $tka_badge_cat->name : '';
                        if ( $tka_tags && ! is_wp_error( $tka_tags ) ) :
                                $tka_tags = toykindangel_unique_terms_by_name( $tka_tags );
                                $tka_tags = array_filter(
                                        $tka_tags,
                                        static function ( $tka_tag ) use ( $tka_badge_name ) {
                                                return trim( (string) $tka_tag->name ) !== $tka_badge_name;
                                        }
                                );
                        endif;
                        if ( $tka_tags ) :
                                ?>
                                <div class="tka-tags">
                                        <span class="tka-tags__label"><?php esc_html_e( 'برچسب‌ها:', 'toykindangel' ); ?></span>
                                        <?php
                                        foreach ( $tka_tags as $tka_tag ) {
                                                echo '<a class="chip" href="' . esc_url( get_tag_link( $tka_tag ) ) . '">#' . esc_html( $tka_tag->name ) . '</a>';
                                        }
                                        ?>
                                </div>
                        <?php endif; ?>
                </article>

                <?php
                if ( comments_open() || get_comments_number() ) {
                        comments_template();
                }
        endwhile;

        the_post_navigation(
                array(
                        'prev_text' => '&laquo; ' . __( 'مطلب قبلی', 'toykindangel' ),
                        'next_text' => __( 'مطلب بعدی', 'toykindangel' ) . ' &raquo;',
                )
        );
        ?>
</div>
<?php
get_footer();
