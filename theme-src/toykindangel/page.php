<?php
/**
 * Static page template.
 *
 * WooCommerce pages (cart/checkout/account) render their own titles and
 * layout, so the article card + page title are skipped there.
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


get_header();

$tka_is_wc_page = function_exists( 'is_cart' ) && ( is_cart() || is_checkout() || is_account_page() );

if ( ! $tka_is_wc_page ) {
        toykindangel_breadcrumbs();
}
?>
<div class="tka-container<?php echo $tka_is_wc_page ? ' tka-wc-page' : ''; ?>">
        <?php
        while ( have_posts() ) :
                the_post();
                if ( $tka_is_wc_page ) {
                        /* SEO/a11y: these pages render their own headings. */
                        printf( '<h1 class="screen-reader-text">%s</h1>', esc_html( get_the_title() ) );
                        the_content();
                        continue;
                }
                ?>
                <article id="post-<?php the_ID(); ?>" <?php post_class( 'tka-card tka-page' ); ?>>
                        <h1 class="tka-page-title"><?php the_title(); ?></h1>
                        <div class="tka-entry-content">
                                <?php
                                the_content();
                                wp_link_pages();
                                ?>
                        </div>
                </article>
                <?php
                if ( comments_open() || get_comments_number() ) {
                        comments_template();
                }
        endwhile;
        ?>
</div>
<?php
get_footer();
