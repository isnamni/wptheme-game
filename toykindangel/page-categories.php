<?php
/**
 * Categories hub page — «دسته‌بندی محصولات» (0.16.0 redesign, issue #7).
 *
 * Uses the STANDARD site header/footer (get_header/get_footer — topstrip,
 * promo strip, full header with menu/search/cart) instead of the old
 * demo-only header-bare.php, and renders a designed "Category Hub" inside
 * the normal content flow:
 *
 *   1. Page head band (title + subtitle + big product-search form).
 *   2. Quick stats strip (products / categories / brands pills).
 *   3. Main categories grid — one card per top-level product_cat with
 *      thumbnail (or first-product-image / monogram fallback), count
 *      badge and subcategory chips linking to the real archives.
 *   4. Popular subcategories rail (horizontal scroll on mobile).
 *   5. The interactive two-column tree browser (#catRail/#catPanel,
 *      filled by app.js from window.TKA_WP — inc/cat-data.php) with an
 *      in-section #catSearch input, as a second section.
 *
 * Styles: assets/css/cats-page.css (enqueued in functions.php, 18-d).
 *
 * @package ToyKindAngel
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


get_header();

toykindangel_breadcrumbs();

/**
 * Active shop-category taxonomy (WooCommerce-aware, shared helper).
 *
 * @return string
 */
function toykindangel_cats_page_tax() {
        return function_exists( 'toykindangel_shop_cat_tax' ) ? toykindangel_shop_cat_tax() : 'product_cat';
}

/**
 * Curated top-level terms for the main grid.
 *
 * Mirrors inc/cat-data.php curation: skips «Uncategorized» and the
 * front-page story-only terms, and follows the demo rail order
 * (tka_rail_order) when the demo import created it.
 *
 * @return WP_Term[]
 */
function toykindangel_cats_page_top_terms() {
        $tka_tax = toykindangel_cats_page_tax();
        if ( ! taxonomy_exists( $tka_tax ) ) {
                return array();
        }

        $tka_args = array(
                'taxonomy'   => $tka_tax,
                'parent'     => 0,
                'hide_empty' => false,
                'number'     => 40,
                'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ), // «دسته‌بندی‌نشده».
                'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                        array(
                                'key'     => 'tka_is_story', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                                'compare' => 'NOT EXISTS',
                        ),
                ),
        );

        // Curated ordering (demo import) when available — else by name.
        $tka_has_order = get_terms(
                array(
                        'taxonomy'   => $tka_tax,
                        'parent'     => 0,
                        'hide_empty' => false,
                        'number'     => 1,
                        'fields'     => 'ids',
                        'meta_query' => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
                                array( 'key' => 'tka_rail_order' ), // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                        ),
                )
        );
        if ( ! is_wp_error( $tka_has_order ) && ! empty( $tka_has_order ) ) {
                $tka_args['meta_key'] = 'tka_rail_order'; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
                $tka_args['orderby']  = 'meta_value_num';
                $tka_args['order']    = 'ASC';
        } else {
                $tka_args['orderby'] = 'name';
        }

        $tka_terms = get_terms( $tka_args );

        return ( is_wp_error( $tka_terms ) || empty( $tka_terms ) ) ? array() : array_values( $tka_terms );
}

/**
 * Most-populated child terms of a top-level term (for card chips).
 *
 * @param WP_Term $term  Parent term.
 * @param int     $limit Max chips.
 * @return WP_Term[]
 */
function toykindangel_cats_page_children( $term, $limit = 4 ) {
        $tka_children = get_terms(
                array(
                        'taxonomy'   => $term->taxonomy,
                        'parent'     => (int) $term->term_id,
                        'hide_empty' => false,
                        'number'     => 24,
                        'orderby'    => 'count',
                        'order'      => 'DESC',
                )
        );
        if ( is_wp_error( $tka_children ) || empty( $tka_children ) ) {
                return array();
        }

        return array_slice( array_values( $tka_children ), 0, $limit );
}

/**
 * Card media: term thumbnail → first product image inside the term
 * (products usually live on child terms) → Persian monogram tile.
 *
 * @param WP_Term $term Term object.
 * @return string HTML (img or span).
 */
function toykindangel_cats_page_card_media( $term ) {
        $tka_img_args = array(
                'class'    => 'cthub-card__img',
                'alt'      => $term->name,
                'loading'  => 'lazy',
                'decoding' => 'async',
        );

        $tka_thumb = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
        if ( $tka_thumb ) {
                return wp_get_attachment_image( $tka_thumb, 'medium', false, $tka_img_args );
        }

        if ( $term->count > 0 ) {
                $tka_ids = get_posts(
                        array(
                                'post_type'        => 'product',
                                'post_status'      => 'publish',
                                'posts_per_page'   => 1,
                                'fields'           => 'ids',
                                'no_found_rows'    => true,
                                'suppress_filters' => true,
                                'tax_query'        => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
                                        array(
                                                'taxonomy'         => $term->taxonomy,
                                                'field'            => 'term_id',
                                                'terms'            => $term->term_id,
                                                'include_children' => true,
                                        ),
                                ),
                        )
                );
                $tka_pid = ( $tka_ids && has_post_thumbnail( (int) reset( $tka_ids ) ) ) ? (int) get_post_thumbnail_id( (int) reset( $tka_ids ) ) : 0;
                if ( $tka_pid ) {
                        return wp_get_attachment_image( $tka_pid, 'medium', false, $tka_img_args );
                }
        }

        // Monogram tile (first Persian letter — gradient set rotates in CSS).
        $tka_letter = function_exists( 'mb_substr' ) ? mb_substr( wp_strip_all_tags( $term->name ), 0, 1, 'UTF-8' ) : substr( $term->name, 0, 1 );

        return '<span class="cthub-card__mono" aria-hidden="true">' . esc_html( $tka_letter ) . '</span>';
}

/**
 * Subcategories with the most products across the site (rail).
 *
 * @param int $limit Max items.
 * @return WP_Term[]
 */
function toykindangel_cats_page_popular_terms( $limit = 12 ) {
        $tka_tax = toykindangel_cats_page_tax();
        if ( ! taxonomy_exists( $tka_tax ) ) {
                return array();
        }

        $tka_terms = get_terms(
                array(
                        'taxonomy'   => $tka_tax,
                        'hide_empty' => false,
                        'number'     => 40,
                        'orderby'    => 'count',
                        'order'      => 'DESC',
                        'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ),
                )
        );
        if ( is_wp_error( $tka_terms ) || empty( $tka_terms ) ) {
                return array();
        }

        $tka_out = array();
        foreach ( $tka_terms as $tka_term ) {
                if ( $tka_term->parent <= 0 ) {
                        continue; // فقط زیردسته (والد ≠ ریشه).
                }
                $tka_parent = get_term( (int) $tka_term->parent, $tka_tax );
                if ( ! $tka_parent || is_wp_error( $tka_parent ) ) {
                        continue; // والد یتیم/حذف‌شده.
                }
                $tka_out[] = $tka_term;
                if ( count( $tka_out ) >= $limit ) {
                        break;
                }
        }

        return $tka_out;
}

/**
 * Small round media for the popular rail (thumb or mini monogram).
 *
 * @param WP_Term $term Term object.
 * @return string HTML.
 */
function toykindangel_cats_page_pop_media( $term ) {
        $tka_thumb = (int) get_term_meta( $term->term_id, 'thumbnail_id', true );
        if ( $tka_thumb ) {
                return wp_get_attachment_image(
                        $tka_thumb,
                        'thumbnail',
                        false,
                        array(
                                'class'    => '',
                                'alt'      => $term->name,
                                'loading'  => 'lazy',
                                'decoding' => 'async',
                        )
                );
        }

        $tka_letter = function_exists( 'mb_substr' ) ? mb_substr( wp_strip_all_tags( $term->name ), 0, 1, 'UTF-8' ) : substr( $term->name, 0, 1 );

        return '<span class="cthub-pop__mono" aria-hidden="true">' . esc_html( $tka_letter ) . '</span>';
}

/**
 * Count of published products (0 when WooCommerce is absent).
 *
 * @return int
 */
function toykindangel_cats_page_product_count() {
        $tka_counts = wp_count_posts( 'product' );

        return ( $tka_counts && isset( $tka_counts->publish ) ) ? (int) $tka_counts->publish : 0;
}

/**
 * Count of the first available brand taxonomy.
 *
 * @return int
 */
function toykindangel_cats_page_brand_count() {
        foreach ( array( 'product_brand', 'tka_brand' ) as $tka_tax ) {
                if ( taxonomy_exists( $tka_tax ) ) {
                        $tka_n = wp_count_terms(
                                array(
                                        'taxonomy'   => $tka_tax,
                                        'hide_empty' => false,
                                )
                        );

                        return is_wp_error( $tka_n ) ? 0 : (int) $tka_n;
                }
        }

        return 0;
}
?>
<div class="cthub">

        <!-- ۱) سربرگ صفحه: عنوان + معرفی + جستجوی بزرگ محصولات -->
        <section class="cthub-band">
                <div class="cthub-band__in">
                        <h1 class="cthub-band__title"><?php esc_html_e( 'دسته‌بندی محصولات', 'toykindangel' ); ?></h1>
                        <p class="cthub-band__sub"><?php esc_html_e( 'هرچه برای بازی، یادگیری و دنیای خیالی کودک لازم دارید؛ دسته‌به‌دسته مرتب و آمادهٔ کشف.', 'toykindangel' ); ?></p>
                        <form role="search" method="get" class="cthub-search" action="<?php echo esc_url( home_url( '/' ) ); ?>">
                                <svg class="ic" aria-hidden="true"><use href="#i-search"></use></svg>
                                <label class="screen-reader-text" for="cthub-search-input"><?php esc_html_e( 'جستجوی محصولات', 'toykindangel' ); ?></label>
                                <input
                                        id="cthub-search-input"
                                        type="search"
                                        name="s"
                                        autocomplete="off"
                                        placeholder="<?php esc_attr_e( 'مثلاً: عروسک پولیشی، ماشین کنترلی، پازل…', 'toykindangel' ); ?>"
                                >
                                <?php if ( function_exists( 'WC' ) ) : ?>
                                        <input type="hidden" name="post_type" value="product">
                                <?php endif; ?>
                                <button type="submit" class="cthub-search__btn"><?php esc_html_e( 'جستجو', 'toykindangel' ); ?></button>
                        </form>
                </div>
        </section>

        <!-- ۲) نوار آمار فروشگاه -->
        <div class="cthub-stats" role="list" aria-label="<?php esc_attr_e( 'آمار فروشگاه', 'toykindangel' ); ?>">
                <?php
                $tka_products = toykindangel_cats_page_product_count();
                $tka_cats     = wp_count_terms(
                        array(
                                'taxonomy'   => toykindangel_cats_page_tax(),
                                'hide_empty' => false,
                                'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ), // «دسته‌بندی‌نشده».
                        )
                );
                $tka_cats     = is_wp_error( $tka_cats ) ? 0 : (int) $tka_cats;
                $tka_brands   = toykindangel_cats_page_brand_count();
                ?>
                <span class="cthub-stat" role="listitem">
                        <svg class="ic" aria-hidden="true"><use href="#i-box"></use></svg>
                        <b><?php echo esc_html( toykindangel_fa_num( number_format( $tka_products ) ) ); ?></b>
                        <?php esc_html_e( 'کالا', 'toykindangel' ); ?>
                </span>
                <span class="cthub-stat" role="listitem">
                        <svg class="ic" aria-hidden="true"><use href="#i-grid"></use></svg>
                        <b><?php echo esc_html( toykindangel_fa_num( number_format( $tka_cats ) ) ); ?></b>
                        <?php esc_html_e( 'دسته‌بندی', 'toykindangel' ); ?>
                </span>
                <?php if ( $tka_brands > 0 ) : ?>
                        <span class="cthub-stat" role="listitem">
                                <svg class="ic" aria-hidden="true"><use href="#i-tag"></use></svg>
                                <b><?php echo esc_html( toykindangel_fa_num( number_format( $tka_brands ) ) ); ?></b>
                                <?php esc_html_e( 'برند', 'toykindangel' ); ?>
                        </span>
                <?php endif; ?>
        </div>

        <!-- ۳) گرید دسته‌بندی‌های اصلی -->
        <section class="cthub-sec cthub-cats" aria-label="<?php esc_attr_e( 'همه دسته‌بندی‌ها', 'toykindangel' ); ?>">
                <div class="cthub-sec__in">
                        <div class="cthub-sec__head">
                                <h2 class="cthub-sec__title">
                                        <svg class="ic" aria-hidden="true"><use href="#i-grid"></use></svg>
                                        <?php esc_html_e( 'همه دسته‌بندی‌ها', 'toykindangel' ); ?>
                                </h2>
                                <a class="cthub-sec__more" href="<?php echo esc_url( toykindangel_categories_url() ); ?>">
                                        <?php esc_html_e( 'مشاهده همه محصولات', 'toykindangel' ); ?>
                                        <svg class="ic" aria-hidden="true"><use href="#i-chev-left"></use></svg>
                                </a>
                        </div>
                        <div class="cthub-grid">
                                <?php foreach ( toykindangel_cats_page_top_terms() as $tka_term ) : ?>
                                        <?php
                                        $tka_url   = toykindangel_term_link( $tka_term );
                                        $tka_kids  = toykindangel_cats_page_children( $tka_term, 4 );
                                        $tka_count = (int) $tka_term->count;
                                        ?>
                                        <article class="cthub-card">
                                                <a class="cthub-card__link" href="<?php echo esc_url( $tka_url ); ?>">
                                                        <figure class="cthub-card__media"><?php echo toykindangel_cats_page_card_media( $tka_term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- img/span امن داخل تابع. ?></figure>
                                                        <h3 class="cthub-card__name"><?php echo esc_html( $tka_term->name ); ?></h3>
                                                        <?php if ( $tka_count > 0 ) : ?>
                                                                <span class="cthub-card__count num"><?php echo esc_html( toykindangel_fa_num( number_format( $tka_count ) ) ); ?>&nbsp;<?php esc_html_e( 'کالا', 'toykindangel' ); ?></span>
                                                        <?php else : ?>
                                                                <span class="cthub-card__count cthub-card__count--empty"><?php esc_html_e( 'به‌زودی', 'toykindangel' ); ?></span>
                                                        <?php endif; ?>
                                                </a>
                                                <?php if ( ! empty( $tka_kids ) ) : ?>
                                                        <div class="cthub-card__chips">
                                                                <?php foreach ( $tka_kids as $tka_kid ) : ?>
                                                                        <?php $tka_kid_url = toykindangel_term_link( $tka_kid ); ?>
                                                                        <a class="cthub-chip" href="<?php echo esc_url( $tka_kid_url ); ?>" title="<?php echo esc_attr( $tka_kid->name ); ?>"><?php echo esc_html( $tka_kid->name ); ?></a>
                                                                <?php endforeach; ?>
                                                        </div>
                                                <?php endif; ?>
                                        </article>
                                <?php endforeach; ?>
                        </div>
                </div>
        </section>

        <!-- ۴) ریل زیردسته‌های پرطرفدار (اسکرول افقی در موبایل) -->
        <?php $tka_popular = toykindangel_cats_page_popular_terms( 12 ); ?>
        <?php if ( ! empty( $tka_popular ) ) : ?>
                <section class="cthub-sec cthub-pop" aria-label="<?php esc_attr_e( 'زیردسته‌های پرطرفدار', 'toykindangel' ); ?>">
                        <div class="cthub-sec__in">
                                <div class="cthub-sec__head">
                                        <h2 class="cthub-sec__title">
                                                <svg class="ic" aria-hidden="true"><use href="#i-fire"></use></svg>
                                                <?php esc_html_e( 'زیردسته‌های پرطرفدار', 'toykindangel' ); ?>
                                        </h2>
                                </div>
                                <div class="cthub-pop__rail">
                                        <?php foreach ( $tka_popular as $tka_term ) : ?>
                                                <?php
                                                $tka_url = toykindangel_term_link( $tka_term );
                                                if ( ! $tka_url ) {
                                                        continue;
                                                }
                                                $tka_count = (int) $tka_term->count;
                                                ?>
                                                <a class="cthub-pop__item" href="<?php echo esc_url( $tka_url ); ?>">
                                                        <span class="cthub-pop__img"><?php echo toykindangel_cats_page_pop_media( $tka_term ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- img/span امن داخل تابع. ?></span>
                                                        <span class="cthub-pop__meta">
                                                                <span class="cthub-pop__name"><?php echo esc_html( $tka_term->name ); ?></span>
                                                                <span class="cthub-pop__count num"><?php echo esc_html( toykindangel_fa_num( number_format( $tka_count ) ) ); ?>&nbsp;<?php esc_html_e( 'کالا', 'toykindangel' ); ?></span>
                                                        </span>
                                                </a>
                                        <?php endforeach; ?>
                                </div>
                        </div>
                </section>
        <?php endif; ?>

        <!-- ۵) مرور درختی دسته‌بندی‌ها (دوستونهٔ تعاملی — app.js + window.TKA_WP) -->
        <section class="cthub-sec cthub-tree" aria-label="<?php esc_attr_e( 'مرور درختی دسته‌بندی‌ها', 'toykindangel' ); ?>">
                <div class="cthub-sec__in">
                        <div class="cthub-sec__head">
                                <h2 class="cthub-sec__title">
                                        <svg class="ic" aria-hidden="true"><use href="#i-cat-variant"></use></svg>
                                        <?php esc_html_e( 'مرور درختی دسته‌بندی‌ها', 'toykindangel' ); ?>
                                </h2>
                                <span class="cthub-sec__sub"><?php esc_html_e( 'دستهٔ اصلی را از ریل انتخاب کنید', 'toykindangel' ); ?></span>
                        </div>

                        <label class="cthub-tree__search">
                                <svg class="ic" aria-hidden="true"><use href="#i-search"></use></svg>
                                <span class="screen-reader-text"><?php esc_html_e( 'جستجو در درخت دسته‌بندی‌ها', 'toykindangel' ); ?></span>
                                <input type="search" id="catSearch" placeholder="<?php esc_attr_e( 'جستجوی سریع در دسته‌ها…', 'toykindangel' ); ?>">
                        </label>

                        <div class="cpage cthub-cpage">
                                <aside class="crail" id="catRail" aria-label="<?php esc_attr_e( 'دسته‌های اصلی', 'toykindangel' ); ?>"></aside>
                                <div class="cpanel" id="catPanel" aria-label="<?php esc_attr_e( 'زیردسته‌ها', 'toykindangel' ); ?>"></div>
                        </div>

                        <noscript>
                                <div class="tka-cats-noscript">
                                        <?php esc_html_e( 'برای مشاهده مرور درختی دسته‌بندی‌ها، جاوااسکریپت مرورگر را فعال کنید؛ تا آن زمان از کارت‌های دسته‌بندی بالا استفاده کنید.', 'toykindangel' ); ?>
                                </div>
                        </noscript>
                </div>
        </section>

</div><!-- /.cthub -->
<?php
get_footer();
