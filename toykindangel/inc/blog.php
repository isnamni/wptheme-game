<?php
/**
 * Blog helpers — ارقام فارسی، زمان مطالعه، کارت غنی، پست ویژه، چیپ دسته‌ها.
 *
 * بین صفحهٔ وبلاگ (page-blog.php)، فهرست پیش‌فرض (index.php) و آرشیوها
 * (archive.php) مشترک است تا ظاهر وبلاگ همه‌جا یکدست بماند.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * تبدیل ارقام لاتین به فارسی.
 *
 * @param string|int $str رشته/عدد ورودی.
 * @return string
 */
function toykindangel_fa_digits( $str ) {
        $en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
        $fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
        return str_replace( $en, $fa, (string) $str );
}

/**
 * زمان مطالعه (دقیقه، بر پایهٔ شمارش واژه‌ها با سرعت ~۱۹۰ واژه/دقیقه).
 *
 * @param int $post_id شناسهٔ نوشته (۰ = نوشتهٔ جاری).
 * @return string دقیقه با ارقام فارسی.
 */
function toykindangel_reading_time( $post_id = 0 ) {
        $post_id = $post_id ? absint( $post_id ) : get_the_ID();
        $content = get_post_field( 'post_content', $post_id );

        $words = 0;
        if ( $content ) {
                $parts = preg_split( '/\s+/u', wp_strip_all_tags( $content ), -1, PREG_SPLIT_NO_EMPTY );
                if ( is_array( $parts ) ) {
                        $words = count( $parts );
                }
        }

        $minutes = max( 1, (int) ceil( $words / 190 ) );
        return toykindangel_fa_digits( $minutes );
}

/**
 * متن کامل زمان مطالعه: «۴ دقیقه مطالعه».
 *
 * @param int $post_id شناسهٔ نوشته (۰ = نوشتهٔ جاری).
 * @return string
 */
function toykindangel_reading_time_text( $post_id = 0 ) {
        /* translators: %s: minutes with Persian digits */
        return sprintf( __( '%s دقیقه مطالعه', 'toykindangel' ), toykindangel_reading_time( $post_id ) );
}

/**
 * جلوگیری از ریدایرکت canonical برای صفحهٔ ۲+ وبلاگ (کوئری سفارشی روی برگه).
 *
 * @param string|false $redirect_url مقصد ریدایرکت.
 * @return string|false
 */
function toykindangel_blog_keep_paged( $redirect_url ) {
        if ( is_page_template( 'page-blog.php' ) && max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ) > 1 ) {
                return false;
        }
        return $redirect_url;
}
add_filter( 'redirect_canonical', 'toykindangel_blog_keep_paged' );

/**
 * URL صفحهٔ وبلاگ (برگه با قالب «وبلاگ»، fallback خانه).
 *
 * @return string
 */
function toykindangel_blog_url() {
        $page = get_page_by_path( 'blog' );
        if ( $page instanceof WP_Post && 'publish' === $page->post_status ) {
                return get_permalink( $page );
        }
        return home_url( '/' );
}

/**
 * اولین دستهٔ نوشته (برای بج روی کارت).
 *
 * @param int $post_id شناسهٔ نوشته (۰ = نوشتهٔ جاری).
 * @return WP_Term|null
 */
function toykindangel_post_first_category( $post_id = 0 ) {
        $cats = get_the_category( $post_id );
        if ( ! empty( $cats ) && ! is_wp_error( $cats ) ) {
                return $cats[0];
        }
        return null;
}

/**
 * حذف ترم‌های هم‌نام (چیپ تکراری) — فقط نخستین ترم با هر نام می‌ماند.
 *
 * دموی وبلاگ برچسب‌هایی با نامِ دسته‌ها دارد؛ بدون این پاک‌سازی کارت/مقاله
 * دو pill هم‌نام نشان می‌دهد (PROBLEMS.md #9).
 *
 * @param WP_Term[]|array $terms فهرست ترم‌ها.
 * @return WP_Term[] ترم‌های یکتا بر اساس نام.
 */
function toykindangel_unique_terms_by_name( $terms ) {
        if ( empty( $terms ) || ! is_array( $terms ) || is_wp_error( $terms ) ) {
                return array();
        }

        $seen  = array();
        $final = array();
        foreach ( $terms as $term ) {
                if ( ! $term instanceof WP_Term ) {
                        continue;
                }
                $key = mb_strtolower( trim( $term->name ) );
                if ( '' === $key || isset( $seen[ $key ] ) ) {
                        continue;
                }
                $seen[ $key ] = true;
                $final[]      = $term;
        }
        return $final;
}

/**
 * چیپ دسته‌های وبلاگ (همان زبان بصری چیپ‌های دمو).
 *
 * @param string $active_slug اسلاگ دستهٔ فعال (خالی = «همه»).
 */
function toykindangel_blog_chips( $active_slug = '' ) {
        $cats = get_categories(
                array(
                        'hide_empty' => true,
                        'number'     => 12,
                        'orderby'    => 'count',
                        'order'      => 'DESC',
                )
        );

        if ( empty( $cats ) || is_wp_error( $cats ) ) {
                return;
        }

        echo '<nav class="tka-blog-chips" aria-label="' . esc_attr__( 'دسته‌های وبلاگ', 'toykindangel' ) . '">';
        echo '<a class="chip' . ( '' === $active_slug ? ' on' : '' ) . '" href="' . esc_url( toykindangel_blog_url() ) . '">' . esc_html__( 'همهٔ مطالب', 'toykindangel' ) . '</a>';

        foreach ( $cats as $cat ) {
                echo '<a class="chip' . ( $active_slug === $cat->slug ? ' on' : '' ) . '" href="' . esc_url( get_category_link( $cat ) ) . '">'
                        . esc_html( $cat->name )
                        . '<span class="tka-chip-count num">' . esc_html( toykindangel_fa_digits( $cat->count ) ) . '</span>'
                        . '</a>';
        }

        echo '</nav>';
}

/**
 * هیروی «پست ویژه» — باید داخل حلقه و با دادهٔ پست تنظیم‌شده صدا زده شود.
 */
function toykindangel_featured_hero() {
        ?>
        <a class="tka-feat" href="<?php the_permalink(); ?>">
                <?php if ( has_post_thumbnail() ) : ?>
                        <?php the_post_thumbnail( 'large', array( 'class' => 'tka-feat__img', 'loading' => 'eager' ) ); ?>
                <?php else : ?>
                        <span class="tka-feat__img tka-feat__img--ph" aria-hidden="true"></span>
                <?php endif; ?>
                <span class="tka-feat__shade" aria-hidden="true"></span>
                <span class="tka-feat__body">
                        <span class="tka-feat__badge">
                                <svg class="ic" aria-hidden="true"><use href="#i-fire"></use></svg>
                                <?php esc_html_e( 'پست ویژه', 'toykindangel' ); ?>
                        </span>
                        <span class="tka-feat__title"><?php the_title(); ?></span>
                        <span class="tka-feat__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24, '…' ) ); ?></span>
                        <span class="tka-feat__meta">
                                <time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
                                <span class="tka-dot">•</span>
                                <?php echo esc_html( toykindangel_reading_time_text() ); ?>
                        </span>
                </span>
        </a>
        <?php
}

/**
 * کارت غنیٔ وبلاگ — باید داخل حلقه صدا زده شود.
 *
 * @param array $args {thumb_size}.
 */
function toykindangel_blog_card( $args = array() ) {
        $args     = wp_parse_args(
                $args,
                array(
                        'thumb_size' => 'medium_large',
                )
        );

        /*
         * چیپ دسته: فقط دستهٔ اول — و فقط پس از حذف ترم‌های هم‌نام تا دو
         * pill یکسان روی کارت رندر نشود (PROBLEMS.md #9).
         */
        $cat      = toykindangel_post_first_category();
        if ( $cat ) {
                $unique = toykindangel_unique_terms_by_name( array( $cat ) );
                $cat    = $unique ? $unique[0] : null;
        }
        ?>
        <article id="post-<?php the_ID(); ?>" <?php post_class( 'tka-card tka-post-card' ); ?>>
                <?php if ( has_post_thumbnail() ) : ?>
                        <a class="tka-post-card__thumb" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
                                <?php the_post_thumbnail( $args['thumb_size'], array( 'loading' => 'lazy' ) ); ?>
                        </a>
                <?php else : ?>
                        <a class="tka-post-card__thumb tka-post-card__thumb--ph" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
                                <svg class="ic" aria-hidden="true"><use href="#i-thumb"></use></svg>
                        </a>
                <?php endif; ?>

                <div class="tka-post-card__body">
                        <div class="tka-post-card__top">
                                <?php if ( is_sticky() ) : ?>
                                        <span class="tka-post-card__badge tka-post-card__badge--sticky"><?php esc_html_e( 'ویژه', 'toykindangel' ); ?></span>
                                <?php endif; ?>
                                <?php if ( $cat ) : ?>
                                        <a class="tka-post-card__badge" href="<?php echo esc_url( get_category_link( $cat ) ); ?>"><?php echo esc_html( $cat->name ); ?></a>
                                <?php endif; ?>
                        </div>

                        <h2 class="tka-post-card__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
                        <p class="tka-post-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18, '…' ) ); ?></p>

                        <div class="tka-post-card__meta">
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
                        </div>
                </div>
        </article>
        <?php
}
