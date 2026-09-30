<?php
/**
 * Live AJAX search — «جستجوی زندهٔ» هدر و جعبه‌های جستجو.
 *
 * Endpoint : /wp-admin/admin-ajax.php?action=tka_live_search&q=<term>
 *            (نسخهٔ REST: inc/rest-search.php → toykindangel/v1/search)
 * Response : { success: true, data: { html, count, term, all_url } }
 *
 * جستجوی فقط-خواندنی روی محتوای عمومی است؛ مثل خود جستجوی هسته به nonce
 * نیازی ندارد. همهٔ خروجی‌ها escape می‌شوند و پنل نتیجه توسط JS باز می‌شود.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * شمارهٔ یکتا برای هر نمونهٔ فرم جستجو (اتصال ARIA input ↔ panel).
 *
 * @return int
 */
function toykindangel_ls_instance() {
        static $n = 0;
        $n++;
        return $n;
}

/**
 * واژهٔ جستجوی دریافتی (پاک‌سازی‌شده).
 *
 * @return string
 */
function toykindangel_live_search_term() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- public read-only search
        $term = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
        return trim( (string) $term );
}

/**
 * هایلایت واژهٔ جستجو داخل متنِ از قبل escape‌شده.
 *
 * @param string $escaped_text  متن escape‌شده.
 * @param string $escaped_term  واژهٔ escape‌شده.
 * @return string
 */
function toykindangel_ls_highlight( $escaped_text, $escaped_term ) {
        if ( '' === $escaped_term || '' === $escaped_text ) {
                return $escaped_text;
        }

        $found = function_exists( 'mb_stripos' ) ? mb_stripos( $escaped_text, $escaped_term ) : stripos( $escaped_text, $escaped_term );
        if ( false === $found ) {
                return $escaped_text;
        }

        // str_ireplace برای فارسی (بدون حروف بزرگ/کوچک) دقیقاً بایت‌به‌بایت و
        // برای لاتین case-insensitive است؛ هر دو رشته از قبل escape هستند.
        return (string) str_ireplace(
                $escaped_term,
                '<mark>' . $escaped_term . '</mark>',
                $escaped_text
        );
}

/**
 * ساخت یک آیتم نتیجه (لینک با نقش option برای listbox).
 *
 * @param array $args {url,title,meta,price,img,badge}.
 * @return string
 */
function toykindangel_ls_item( $args ) {
        static $opt = 0;
        $opt++;

        $defaults = array(
                'url'   => '',
                'title' => '',
                'meta'  => '',
                'price' => '',
                'img'   => '',
                'badge' => '',
        );
        $args = wp_parse_args( $args, $defaults );

        $thumb = $args['img']
                ? '<img class="tka-ls__thumb" src="' . esc_url( $args['img'] ) . '" alt="" loading="lazy" width="46" height="46">'
                : '<span class="tka-ls__thumb tka-ls__thumb--ph" aria-hidden="true"><svg class="ic"><use href="#i-search"></use></svg></span>';

        $price = $args['price']
                ? '<span class="tka-ls__price num">' . $args['price'] . '</span>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wc_price HTML
                : '';

        $badge = $args['badge']
                ? '<span class="tka-ls__badge">' . esc_html( $args['badge'] ) . '</span>'
                : '';

        $html  = '<a class="tka-ls__item" role="option" id="tka-ls-opt-' . esc_attr( (string) $opt ) . '" href="' . esc_url( $args['url'] ) . '">';
        $html .= $thumb;
        $html .= '<span class="tka-ls__body">';
        $html .= '<span class="tka-ls__title">' . wp_kses_post( $args['title'] ) . $badge . '</span>';
        if ( $args['meta'] ) {
                $html .= '<span class="tka-ls__meta">' . wp_kses_post( $args['meta'] ) . '</span>';
        }
        $html .= '</span>';
        $html .= $price;
        $html .= '</a>';

        return $html;
}

/**
 * نتایج محصولات ووکامرس.
 *
 * @param string $term  واژهٔ جستجو.
 * @param int    $limit سقف نتایج.
 * @return string HTML آماده.
 */
function toykindangel_ls_products( $term, $limit = 6 ) {
        $query = new WP_Query(
                array(
                        'post_type'           => 'product',
                        'post_status'         => 'publish',
                        'posts_per_page'      => $limit,
                        's'                   => $term,
                        'no_found_rows'       => true,
                        'ignore_sticky_posts' => true,
                )
        );

        $escaped_term = esc_html( $term );
        $out          = '';

        while ( $query->have_posts() ) {
                $query->the_post();

                $product    = function_exists( 'wc_get_product' ) ? wc_get_product( get_the_ID() ) : null;
                $img        = get_the_post_thumbnail_url( get_the_ID(), 'woocommerce_gallery_thumbnail' );
                $title_html = toykindangel_ls_highlight( esc_html( get_the_title() ), $escaped_term );

                // دستهٔ اول محصول به‌عنوان بافت.
                $cat_name = '';
                $terms    = get_the_terms( get_the_ID(), 'product_cat' );
                if ( $terms && ! is_wp_error( $terms ) ) {
                        $cat_name = $terms[0]->name;
                }

                // وضعیت موجودی.
                $stock = '';
                if ( $product ) {
                        $stock = $product->is_in_stock()
                                ? esc_html__( 'موجود', 'toykindangel' )
                                : '<span class="out">' . esc_html__( 'ناموجود', 'toykindangel' ) . '</span>';
                }

                $meta_parts = array_filter( array( esc_html( $cat_name ), $stock ) );
                $meta       = implode( '<span class="tka-ls__sep">•</span>', $meta_parts );

                $out .= toykindangel_ls_item(
                        array(
                                'url'   => get_permalink(),
                                'title' => $title_html,
                                'meta'  => $meta,
                                'price' => $product ? $product->get_price_html() : '',
                                'img'   => $img ? $img : '',
                        )
                );
        }
        wp_reset_postdata();

        return $out;
}

/**
 * نتایج نوشته‌های وبلاگ.
 *
 * @param string $term  واژهٔ جستجو.
 * @param int    $limit سقف نتایج.
 * @return string HTML آماده.
 */
function toykindangel_ls_posts( $term, $limit = 3 ) {
        $query = new WP_Query(
                array(
                        'post_type'           => 'post',
                        'post_status'         => 'publish',
                        'posts_per_page'      => $limit,
                        's'                   => $term,
                        'no_found_rows'       => true,
                        'ignore_sticky_posts' => true,
                )
        );

        $escaped_term = esc_html( $term );
        $out          = '';

        while ( $query->have_posts() ) {
                $query->the_post();

                $img        = get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' );
                $title_html = toykindangel_ls_highlight( esc_html( get_the_title() ), $escaped_term );

                $meta = esc_html( get_the_date() );
                if ( function_exists( 'toykindangel_reading_time_text' ) ) {
                        $meta .= '<span class="tka-ls__sep">•</span>' . esc_html( toykindangel_reading_time_text( get_the_ID() ) );
                }

                $out .= toykindangel_ls_item(
                        array(
                                'url'   => get_permalink(),
                                'title' => $title_html,
                                'meta'  => $meta,
                                'img'   => $img ? $img : '',
                        )
                );
        }
        wp_reset_postdata();

        return $out;
}

/**
 * ساخت payload جستجوی زنده — منبع یگانهٔ حقیقت برای هر دو endpoint
 * (admin-ajax قدیمی و REST جدید؛ بنگر inc/rest-search.php).
 *
 * منطق/خروجی دقیقاً همان رفتار قدیمی هندلر AJAX است تا back-compat
 * حفظ شود: محصولات با سقف $limit و وبلاگ با سقف ۳؛ کمتر از ۲ نویسه
 * یعنی بدون نتیجه.
 *
 * @param string $term  واژهٔ جستجو (پاک‌سازی‌شده).
 * @param int    $limit سقف نتایج محصول (۱ تا ۸؛ بیرون بازه کلیپ می‌شود).
 * @return array { html, count, term, all_url }
 */
function toykindangel_live_search_payload( $term, $limit = 6 ) {
        $limit = (int) $limit;
        if ( $limit < 1 ) {
                $limit = 6;
        }
        $limit = min( 8, $limit );

        // کمتر از ۲ نویسه → بدون نتیجه (JS هم پنل را باز نمی‌کند).
        if ( function_exists( 'mb_strlen' ) ? mb_strlen( $term ) < 2 : strlen( $term ) < 2 ) {
                return array(
                        'html'    => '',
                        'count'   => 0,
                        'term'    => $term,
                        'all_url' => '',
                );
        }

        $products_html = toykindangel_ls_products( $term, $limit );
        $posts_html    = toykindangel_ls_posts( $term, 3 );

        $html = '';
        if ( '' !== $products_html ) {
                $html .= '<div class="tka-ls__sec">' . esc_html__( 'محصولات', 'toykindangel' ) . '</div>' . $products_html;
        }
        if ( '' !== $posts_html ) {
                $html .= '<div class="tka-ls__sec">' . esc_html__( 'وبلاگ', 'toykindangel' ) . '</div>' . $posts_html;
        }

        $count = 0;
        if ( '' !== $html ) {
                $count = substr_count( $html, 'class="tka-ls__item"' );
        } else {
                // حالت بدون نتیجه.
                /* translators: %s: search term */
                $html = '<div class="tka-ls__empty">' . sprintf( esc_html__( 'نتیجه‌ای برای «%s» پیدا نشد.', 'toykindangel' ), esc_html( $term ) ) . '</div>';
        }

        // لینک «مشاهدهٔ همهٔ نتایج» — مطابق رفتار فرم (جستجوی محصولات).
        $all_url = add_query_arg(
                array(
                        's'         => $term,
                        'post_type' => 'product',
                ),
                home_url( '/' )
        );

        return array(
                'html'    => $html,
                'count'   => $count,
                'term'    => $term,
                'all_url' => $all_url,
        );
}

/**
 * هندلر AJAX جستجوی زنده (کاربران عادی و مهمان) — back-compat.
 */
function toykindangel_live_search_handler() {
        wp_send_json_success( toykindangel_live_search_payload( toykindangel_live_search_term() ) );
}
add_action( 'wp_ajax_tka_live_search', 'toykindangel_live_search_handler' );
add_action( 'wp_ajax_nopriv_tka_live_search', 'toykindangel_live_search_handler' );
