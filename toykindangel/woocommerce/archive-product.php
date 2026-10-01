<?php
/**
 * Product archive — demo-style product listing (standard main loop).
 *
 * Handles: shop root (/shop/), product_cat archives, product_tag and
 * tka_brand term archives (via the taxonomy-tka_brand shim).
 *
 * Standardization notes (P0 review fix):
 * - Iterates the MAIN query (have_posts()/the_post()) instead of a
 *   custom WP_Query, so pagination runs on the main query, WooCommerce
 *   filter widgets/plugins work and no duplicate DB query is made.
 * - The demo ordering (best-selling default + the orderby keys
 *   «best/new/cheap/exp») is applied to the main query from
 *   inc/wc-hooks.php via the woocommerce_product_query hook.
 * - The product cards replicate the demo app.js `pcard()` markup 1:1;
 *   styles come from demo.css plus the WP glue layer in main.css.
 *
 * Filter sidebar (v0.14.0 — user request):
 * - RTL-first column with product filters (categories / price / brands
 *   / active-filter chips). Uses the registered «shop-sidebar» widget
 *   area when the manager has widgets in it, otherwise renders a smart
 *   default set so filters exist out of the box.
 * - Collapsible <details> on mobile (starts closed), always-open rail
 *   on desktop (summary hidden via CSS).
 *
 * @version 8.6.0
 * @package ToyKindAngel
 */

defined( 'ABSPATH' ) || exit;

get_header();

/* ------------------------------------------------------------------ */
/* Context                                                             */
/* ------------------------------------------------------------------ */
global $wp_query;

$tka_queried = get_queried_object();
$tka_is_term = is_tax( array( 'product_cat', 'product_tag', 'tka_brand' ) );

$tka_title = $tka_is_term ? single_term_title( '', false ) : woocommerce_page_title( false );
$tka_desc  = $tka_is_term && $tka_queried instanceof WP_Term ? term_description( $tka_queried ) : '';

/* Always count from the main query: a parent category's term->count only
 * includes DIRECT products, while the archive shows the whole subtree —
 * the mismatch looked like «0 کالا» under a grid full of products. */
$tka_count = (int) $wp_query->found_posts;

/* ------------------------------------------------------------------ */
/* Sorting (labels only — ordering happens on the main query)          */
/* ------------------------------------------------------------------ */
$tka_sorts = array(
        'best'  => __( 'پرفروش‌ترین', 'toykindangel' ),
        'new'   => __( 'جدیدترین', 'toykindangel' ),
        'cheap' => __( 'ارزان‌ترین', 'toykindangel' ),
        'exp'   => __( 'گران‌ترین', 'toykindangel' ),
);

$tka_sort = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'best'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( ! array_key_exists( $tka_sort, $tka_sorts ) ) {
        $tka_sort = 'best';
}
?>
<div class="tka-shoppage">
        <?php
        /* استوری‌های اینستاگرامی — TKA 0.18.1 (طبق تنظیم «محل نمایش») */
        toykindangel_ig_stories_bar();
        ?>

        <?php toykindangel_breadcrumbs(); ?>

        <div class="tka-container">

                <div class="tka-shophead">
                        <h1 class="tka-shophead__title">
                                <?php echo esc_html( $tka_title ); ?>
                                <span class="tka-shophead__count num"><?php echo esc_html( toykindangel_fmt( $tka_count ) ); ?> <?php esc_html_e( 'کالا', 'toykindangel' ); ?></span>
                        </h1>
                        <?php if ( $tka_desc ) : ?>
                                <div class="tka-shophead__desc"><?php echo wp_kses_post( wpautop( $tka_desc ) ); ?></div>
                        <?php endif; ?>
                </div>

                <?php
                /*
                 * TKA 0.18.0 — نوار دسته‌های مرتبط:
                 * روی آرشیو هر دسته، اگر زیردسته داشت زیردسته‌هایش و اگر نداشت
                 * دسته‌های هم‌سطحش به‌صورت باکس‌های افقی اسکرول‌دار نمایش داده
                 * می‌شود؛ باکس دستهٔ فعلی با حاشیهٔ تیره «انتخاب‌شده» است.
                 */
                if ( is_product_category() && $tka_queried instanceof WP_Term ) :
                        $tka_sub_terms = get_terms( array(
                                'taxonomy'   => 'product_cat',
                                'parent'     => (int) $tka_queried->term_id,
                                'hide_empty' => false,
                        ) );
                        if ( is_wp_error( $tka_sub_terms ) || empty( $tka_sub_terms ) ) {
                                $tka_sub_terms = $tka_queried->parent
                                        ? get_terms( array(
                                                'taxonomy'   => 'product_cat',
                                                'parent'     => (int) $tka_queried->parent,
                                                'hide_empty' => false,
                                        ) )
                                        : array();
                        }
                        if ( ! is_wp_error( $tka_sub_terms ) && ! empty( $tka_sub_terms ) ) :
                                /*
                                 * v0.20.0 audit fix C4 — get_terms() does NOT prime term
                                 * meta, so the per-term get_term_meta(thumbnail_id) calls
                                 * below were a real N+1 (one termmeta query per subcat on
                                 * every category archive view). Prime them in one shot.
                                 */
                                $tka_sub_ids = array();
                                foreach ( $tka_sub_terms as $tka_sub ) {
                                        $tka_sub_ids[] = (int) $tka_sub->term_id;
                                }
                                _prime_term_caches( $tka_sub_ids, false );
                                ?>
                                <nav class="tka-subcats" aria-label="<?php esc_attr_e( 'دسته‌های مرتبط', 'toykindangel' ); ?>">
                                        <?php
                                        foreach ( $tka_sub_terms as $tka_sub ) {
                                                $tka_sub_link = get_term_link( $tka_sub );
                                                if ( is_wp_error( $tka_sub_link ) ) {
                                                        continue;
                                                }
                                                $tka_sub_on      = ( (int) $tka_sub->term_id === (int) $tka_queried->term_id ) ? ' on' : '';
                                                $tka_sub_thumb   = (int) get_term_meta( $tka_sub->term_id, 'thumbnail_id', true );
                                                $tka_sub_img_url = $tka_sub_thumb ? wp_get_attachment_image_url( $tka_sub_thumb, 'woocommerce_gallery_thumbnail' ) : '';
                                                echo '<a class="tka-subcat' . esc_attr( $tka_sub_on ) . '" href="' . esc_url( $tka_sub_link ) . '">';
                                                echo '<span class="tka-subcat__img">';
                                                if ( $tka_sub_img_url ) {
                                                        echo '<img src="' . esc_url( $tka_sub_img_url ) . '" alt="' . esc_attr( $tka_sub->name ) . '" loading="lazy" decoding="async">';
                                                } else {
                                                        echo '<svg class="ic" aria-hidden="true"><use href="#i-grid"></use></svg>';
                                                }
                                                echo '</span>';
                                                echo '<span class="tka-subcat__label">' . esc_html( $tka_sub->name ) . '</span>';
                                                echo '</a>';
                                        }
                                        ?>
                                </nav>
                        <?php endif; ?>
                <?php endif; ?>

                <?php
                /*
                 * Core result count / catalog ordering are unhooked in
                 * inc/wc-hooks.php (the theme renders its own UI below);
                 * filter plugins may still attach here.
                 */
                do_action( 'woocommerce_before_shop_loop' );
                ?>

                <div class="tka-shoppage__layout">

                        <?php /* -------------------- سایدبار فیلترها (راست در RTL) -------------------- */ ?>
                        <details class="tka-filters"<?php echo wp_is_mobile() ? '' : ' open'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
                                <summary class="tka-filters__toggle">
                                        <svg class="ic" aria-hidden="true"><use href="#i-filter"></use></svg>
                                        <span><?php esc_html_e( 'فیلترها', 'toykindangel' ); ?></span>
                                        <svg class="ic tka-filters__chev" aria-hidden="true"><use href="#i-chev-right"></use></svg>
                                </summary>

                                <div class="tka-filters__panel">
                                        <?php if ( is_active_sidebar( 'shop-sidebar' ) ) : ?>

                                                <?php dynamic_sidebar( 'shop-sidebar' ); ?>

                                        <?php else : ?>

                                                <?php
                                                /* حالت پیش‌فرض: تا وقتی مدیر ویجتی در «فروشگاه (سایدبار)» نچیده. */
                                                if ( class_exists( 'WC_Widget_Layered_Nav_Filters' ) ) {
                                                        the_widget(
                                                                'WC_Widget_Layered_Nav_Filters',
                                                                array( 'title' => __( 'فیلترهای فعال', 'toykindangel' ) ),
                                                                array( 'widget_id' => 'tka-flt-active' )
                                                        );
                                                }
                                                if ( class_exists( 'WC_Widget_Product_Categories' ) ) {
                                                        the_widget(
                                                                'WC_Widget_Product_Categories',
                                                                array(
                                                                        'title'        => __( 'دسته‌بندی محصولات', 'toykindangel' ),
                                                                        'orderby'      => 'name',
                                                                        'count'        => 1,
                                                                        'hierarchical' => 1,
                                                                        'hide_empty'   => 0,
                                                                ),
                                                                array( 'widget_id' => 'tka-flt-cats' )
                                                        );
                                                }
                                                if ( class_exists( 'WC_Widget_Price_Filter' ) ) {
                                                        the_widget(
                                                                'WC_Widget_Price_Filter',
                                                                array( 'title' => __( 'محدوده قیمت', 'toykindangel' ) ),
                                                                array( 'widget_id' => 'tka-flt-price' )
                                                        );
                                                }

                                                /* برندها — تکسونومی اختصاصی قالب. */
                                                $tka_brands = get_terms(
                                                        array(
                                                                'taxonomy'   => 'tka_brand',
                                                                'hide_empty' => false,
                                                                'number'     => 30,
                                                        )
                                                );
                                                if ( ! is_wp_error( $tka_brands ) && ! empty( $tka_brands ) ) :
                                                        ?>
                                                        <section class="widget tka-flt-brands">
                                                                <h2 class="widgettitle"><?php esc_html_e( 'برندها', 'toykindangel' ); ?></h2>
                                                                <ul class="tka-flt-brands__list">
                                                                        <?php foreach ( $tka_brands as $tka_brand ) : ?>
                                                                                <li>
                                                                                        <a href="<?php echo esc_url( get_term_link( $tka_brand ) ); ?>">
                                                                                                <span class="tka-flt-brands__name"><?php echo esc_html( $tka_brand->name ); ?></span>
                                                                                                <span class="num tka-flt-brands__count"><?php echo esc_html( function_exists( 'toykindangel_fmt' ) ? toykindangel_fmt( (int) $tka_brand->count ) : (string) (int) $tka_brand->count ); ?></span>
                                                                                        </a>
                                                                                </li>
                                                                        <?php endforeach; ?>
                                                                </ul>
                                                        </section>
                                                <?php endif; ?>

                                        <?php endif; ?>
                                </div>
                        </details>

                        <?php /* -------------------- ستون محصولات -------------------- */ ?>
                        <div class="tka-shoppage__main">

                                <nav class="tka-sortbar" aria-label="<?php esc_attr_e( 'ترتیب نمایش', 'toykindangel' ); ?>">
                                        <?php
                                        foreach ( $tka_sorts as $tka_key => $tka_label ) {
                                                $tka_url = add_query_arg( 'orderby', $tka_key );
                                                if ( 'best' === $tka_key ) {
                                                        $tka_url = remove_query_arg( 'orderby' );
                                                }
                                                $tka_on = ( $tka_sort === $tka_key ) ? ' on' : '';
                                                echo '<a class="tka-sortbar__item' . esc_attr( $tka_on ) . '" href="' . esc_url( $tka_url ) . '">' . esc_html( $tka_label ) . '</a>';
                                        }
                                        ?>
                                </nav>

                                <?php if ( have_posts() ) : ?>

                                        <div class="tka-pgrid">
                                                <?php
                                                while ( have_posts() ) {
                                                        the_post();
                                                        $tka_product = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
                                                        $GLOBALS['product'] = $tka_product; // Keep the standard loop global for plugins.
                                                        echo toykindangel_pcard_wrap( $tka_product ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside renderer.
                                                }
                                                ?>
                                        </div>

                                        <?php do_action( 'woocommerce_after_shop_loop' ); ?>

                                        <?php
                                        $tka_total_pages = (int) $wp_query->max_num_pages;
                                        if ( $tka_total_pages > 1 ) {
                                                echo '<nav class="tka-pagination-wrap" aria-label="' . esc_attr__( 'صفحه‌بندی محصولات', 'toykindangel' ) . '">';
                                                echo wp_kses_post( paginate_links(
                                                        array(
                                                                'total'     => $tka_total_pages,
                                                                'current'   => max( 1, (int) get_query_var( 'paged' ) ),
                                                                'prev_text' => '« قبلی',
                                                                'next_text' => 'بعدی »',
                                                                'type'      => 'list',
                                                        )
                                                ) );
                                                echo '</nav>';
                                        }
                                        ?>

                                <?php else : ?>

                                        <div class="tka-empty">
                                                <svg class="ic tka-empty__ic" aria-hidden="true"><use href="#i-cat-camera"></use></svg>
                                                <p><?php esc_html_e( 'هنوز کالایی اینجا نیست!', 'toykindangel' ); ?></p>
                                                <a class="tka-btn" href="<?php echo esc_url( toykindangel_categories_url() ); ?>"><?php esc_html_e( 'دیدن دسته‌بندی‌ها', 'toykindangel' ); ?></a>
                                        </div>

                                        <?php do_action( 'woocommerce_after_shop_loop' ); ?>

                                <?php endif; ?>

                        </div>
                </div>
        </div>
</div>
<?php
get_footer();
