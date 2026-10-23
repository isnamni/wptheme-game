<?php
/**
 * TKA Product Filters — ویجت حرفه‌ای فیلتر محصولات (v0.27.0 — نیاز ۷).
 *
 * یک ویجت برای سایدبار فروشگاه که فیلترهای استاندارد ووکامرس را
 * یک‌جا و به سبک فروشگاه‌های مرجع (دیجی‌کالا) ارائه می‌دهد:
 *
 *   ۱. «فیلترهای فعال» — چیپ‌های قابل‌حذف برای هر پارامتر فعال.
 *   ۲. «محدوده قیمت» — دو ورودی عددی با پارامترهای استاندارد
 *      min_price / max_price هستهٔ ووکامرس (بدون AJAX، لینک‌محور).
 *   3. «فقط موجود» / «فقط تخفیف‌دار» — با پارامترهای اختصاصی قالب
 *      tka_stock / tka_sale (سمت PHP با pre_get_posts اعمال می‌شود تا
 *      با هیچ افزونه‌ای تداخل نداشته باشد).
 *   ۴. «برندها» — تکسونومی برند (product_brand هسته یا tka_brand قالب)
 *      با شمارنده، به‌صورت پیوند مستقیم آرشیو برند (قابل ایندکس شدن).
 *   ۵. «دسته‌بندی‌ها» — زیرشاخهٔ همان‌جا / هم‌سطح‌ها / دسته‌های اصلی.
 *   ۶. «ویژگی‌ها» (رنگ، سایز و…) — مکانیزم استاندارد لایرد-نِو هستهٔ
 *      ووکامرس (filter_{attr} + query_type_{attr}) با انتخاب چندگانه.
 *
 * همهٔ بدنه‌ها با <details> قابل باز/بسته‌شدن‌اند؛ در موبایل کل سایدبار
 * از طریق <details class="tka-filters"> قالب (archive-product.php) بسته
 * شروع می‌شود و با کلیک باز می‌شود — رفتار موردنیاز مشتری.
 *
 * هر دو فیلتر قیمت و ویژگی‌ها از پارامترهای خودِ هستهٔ ووکامرس
 * استفاده می‌کنند، پس با افزونه‌های فیلتر و بلوک‌های هسته سازگارند.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * اعمال پارامترهای اختصاصی ویجت (tka_stock / tka_sale) روی کوئری اصلی.
 *
 * فقط روی آرشیو محصولات و فقط برای درخواست‌های فرانت اجرا می‌شود؛
 * مشابه قواعد inc/wc-hooks.php (بدون دست‌زدن به پیشخوان/REST).
 *
 * @param WP_Query $q Main query.
 * @return void
 */
function toykindangel_pfilter_query( $q ) {
        if ( is_admin() || ! $q instanceof WP_Query || ! $q->is_main_query() ) {
                return;
        }
        if ( ! ( $q->is_post_type_archive( 'product' ) || $q->is_tax( array( 'product_cat', 'product_tag', 'tka_brand' ) ) ) ) {
                return;
        }

        /* فقط کالاهای با وضعیت موجودی مشخص. */
        $tka_stock = isset( $_GET['tka_stock'] ) ? sanitize_key( wp_unslash( $_GET['tka_stock'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( in_array( $tka_stock, array( 'instock', 'onbackorder', 'outofstock' ), true ) ) {
                $tka_meta = (array) $q->get( 'meta_query' );
                $tka_meta[] = array(
                        'key'     => '_stock_status',
                        'value'   => $tka_stock,
                        'compare' => '=',
                );
                $q->set( 'meta_query', $tka_meta );
        }

        /* فقط کالاهای تخفیف‌دار (قیمت فعال < قیمت عادی، همان منبع هسته). */
        $tka_sale = isset( $_GET['tka_sale'] ) && '1' === sanitize_text_field( wp_unslash( (string) $_GET['tka_sale'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( $tka_sale && function_exists( 'wc_get_product_ids_on_sale' ) ) {
                $tka_ids = array_map( 'intval', (array) wc_get_product_ids_on_sale() );
                $tka_in  = (array) $q->get( 'post__in' );
                if ( ! empty( $tka_in ) ) {
                        $tka_ids = array_values( array_intersect( $tka_in, $tka_ids ) );
                }
                if ( empty( $tka_ids ) ) {
                        $tka_ids = array( 0 ); /* هیچ نتیجه‌ای — نمایش حالت خالی */
                }
                $q->set( 'post__in', $tka_ids );
        }
}
add_action( 'pre_get_posts', 'toykindangel_pfilter_query', 20 );

/**
 * ویجت فیلتر حرفه‌ای محصولات.
 */
class TKA_Product_Filters_Widget extends WP_Widget {

        /**
         * پارامترهای لایرد-نِو که باید هنگام ساخت «پایهٔ پاک» حذف شوند.
         *
         * @var string[]
         */
        private $nav_keys = array(
                'min_price', 'max_price', 'rating_filter',
                'tka_stock', 'tka_sale',
                'orderby', 'paged',
        );

        /** Cache-safe constructor. */
        public function __construct() {
                parent::__construct(
                        'tka_product_filters',
                        __( 'فیلتر حرفه‌ای محصولات — فرشته مهربون', 'toykindangel' ),
                        array(
                                'description' => __( 'فیلتر قیمت، موجودی، تخفیف، برند، دسته‌بندی و ویژگی‌ها برای سایدبار فروشگاه.', 'toykindangel' ),
                                'classname'   => 'tka-pfilter',
                        )
                );
        }

        /**
         * پایهٔ پاک URL: آدرس فعلی بدون هیچ پارامتر فیلتر.
         *
         * @return string
         */
        private function base_url() {
                $tka_remove = $this->nav_keys;
                if ( taxonomy_exists( 'product_brand' ) ) {
                        $tka_remove[] = 'filter_product_brand';
                        $tka_remove[] = 'query_type_product_brand';
                }
                if ( taxonomy_exists( 'tka_brand' ) ) {
                        $tka_remove[] = 'filter_tka_brand';
                        $tka_remove[] = 'query_type_tka_brand';
                }

                /* ویژگی‌ها: هر filter_* / query_type_* که ثبت شده حذف شود. */
                $tka_attrs = $this->attribute_taxonomies();
                foreach ( $tka_attrs as $tka_attr ) {
                        $tka_remove[] = 'filter_' . $tka_attr['slug'];
                        $tka_remove[] = 'query_type_' . $tka_attr['slug'];
                }

                return remove_query_arg( array_values( array_unique( $tka_remove ) ) );
        }

        /**
         * ویژگی‌های ووکامرس که حداقل یک اصطلاح استفاده‌شده دارند.
         *
         * @return array[] { slug, label, terms }
         */
        private function attribute_taxonomies() {
                static $tka_cache = null;
                if ( null !== $tka_cache ) {
                        return $tka_cache;
                }

                $tka_cache = array();
                if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
                        return $tka_cache;
                }

                $tka_attrs = wc_get_attribute_taxonomies();
                if ( empty( $tka_attrs ) ) {
                        return $tka_cache;
                }

                foreach ( $tka_attrs as $tka_attr ) {
                        $tka_tax = 'pa_' . $tka_attr->attribute_name;
                        if ( ! taxonomy_exists( $tka_tax ) ) {
                                continue;
                        }
                        $tka_terms = get_terms(
                                array(
                                        'taxonomy'   => $tka_tax,
                                        'hide_empty' => true,
                                        'number'     => 20,
                                )
                        );
                        if ( is_wp_error( $tka_terms ) || empty( $tka_terms ) ) {
                                continue;
                        }
                        $tka_cache[] = array(
                                /* هسته: pa_color → پارامتر filter_color */
                                'slug'  => $tka_attr->attribute_name,
                                'label' => $tka_attr->attribute_label ? $tka_attr->attribute_label : $tka_attr->attribute_name,
                                'terms' => $tka_terms,
                        );
                }

                return $tka_cache;
        }

        /**
         * خروجی ویجت.
         *
         * @param array $args     Widget args.
         * @param array $instance Settings.
         * @return void
         */
        public function widget( $args, $instance ) {
                if ( ! function_exists( 'is_woocommerce' ) ) {
                        return;
                }
                if ( ! ( is_shop() || is_product_taxonomy() ) ) {
                        return;
                }

                $tka_title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'فیلتر محصولات', 'toykindangel' );

                echo isset( $args['before_widget'] ) ? $args['before_widget'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- standard widget shell.

                if ( ! empty( $args['before_title'] ) ) {
                        echo isset( $args['before_title'] ) ? $args['before_title'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                        echo esc_html( $tka_title );
                        echo isset( $args['after_title'] ) ? $args['after_title'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
                }

                echo '<div class="tka-pfilter" data-tka-pfilter>';

                $this->section_active();
                if ( ! empty( $instance['show_price'] ) ) {
                        $this->section_price();
                }
                if ( ! empty( $instance['show_stock_sale'] ) ) {
                        $this->section_stock_sale();
                }
                if ( ! empty( $instance['show_brands'] ) ) {
                        $this->section_brands( $instance );
                }
                if ( ! empty( $instance['show_cats'] ) ) {
                        $this->section_cats();
                }
                if ( ! empty( $instance['show_attrs'] ) ) {
                        $this->section_attrs( $instance );
                }

                echo '</div>';

                echo isset( $args['after_widget'] ) ? $args['after_widget'] : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        }

        /* ------------------------------------------------------------------ */
        /* بخش ۱ — فیلترهای فعال                                               */
        /* ------------------------------------------------------------------ */
        private function section_active() {
                $tka_base = $this->base_url();

                $tka_chips = array();

                /* قیمت. */
                $tka_min = isset( $_GET['min_price'] ) ? preg_replace( '/[^\d]/', '', wp_unslash( (string) $_GET['min_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $tka_max = isset( $_GET['max_price'] ) ? preg_replace( '/[^\d]/', '', wp_unslash( (string) $_GET['max_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                if ( '' !== $tka_min || '' !== $tka_max ) {
                        $tka_label = 'قیمت: ' . ( '' !== $tka_min ? toykindangel_fmt( (int) $tka_min ) : '۰' )
                                . ' تا ' . ( '' !== $tka_max ? toykindangel_fmt( (int) $tka_max ) : '∞' );
                        $tka_chips[] = array(
                                'label' => $tka_label,
                                'href'  => remove_query_arg( array( 'min_price', 'max_price' ) ),
                        );
                }

                /* موجودی. */
                $tka_stock = isset( $_GET['tka_stock'] ) ? sanitize_key( wp_unslash( (string) $_GET['tka_stock'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                if ( 'instock' === $tka_stock ) {
                        $tka_chips[] = array(
                                'label' => 'فقط کالاهای موجود',
                                'href'  => remove_query_arg( 'tka_stock' ),
                        );
                }

                /* تخفیف‌دار. */
                if ( isset( $_GET['tka_sale'] ) && '1' === (string) $_GET['tka_sale'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        $tka_chips[] = array(
                                'label' => 'فقط کالاهای تخفیف‌دار',
                                'href'  => remove_query_arg( 'tka_sale' ),
                        );
                }

                /* برند (لایرد-نِو در صورت فعال بودن افزونه‌ها). */
                foreach ( array( 'product_brand', 'tka_brand' ) as $tka_tax ) {
                        if ( ! taxonomy_exists( $tka_tax ) ) {
                                continue;
                        }
                        $tka_val = isset( $_GET[ 'filter_' . $tka_tax ] ) ? sanitize_text_field( wp_unslash( (string) $_GET[ 'filter_' . $tka_tax ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        if ( '' === $tka_val ) {
                                continue;
                        }
                        foreach ( explode( ',', $tka_val ) as $tka_slug ) {
                                $tka_term = get_term_by( 'slug', $tka_slug, $tka_tax );
                                if ( ! $tka_term ) {
                                        continue;
                                }
                                $tka_chips[] = array(
                                        'label' => 'برند: ' . $tka_term->name,
                                        'href'  => remove_query_arg( array( 'filter_' . $tka_tax, 'query_type_' . $tka_tax ) ),
                                );
                        }
                }

                /* ویژگی‌ها. */
                foreach ( $this->attribute_taxonomies() as $tka_attr ) {
                        $tka_key = 'filter_' . $tka_attr['slug'];
                        $tka_val = isset( $_GET[ $tka_key ] ) ? sanitize_text_field( wp_unslash( (string) $_GET[ $tka_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        if ( '' === $tka_val ) {
                                continue;
                        }
                        $tka_slugs = array_filter( explode( ',', $tka_val ) );
                        foreach ( $tka_slugs as $tka_slug ) {
                                $tka_term = get_term_by( 'slug', $tka_slug, 'pa_' . $tka_attr['slug'] );
                                if ( ! $tka_term ) {
                                        continue;
                                }
                                $tka_rest = array_diff( $tka_slugs, array( $tka_slug ) );
                                $tka_href = $tka_rest
                                        ? add_query_arg( array( $tka_key => implode( ',', $tka_rest ) ), $tka_base )
                                        : remove_query_arg( array( $tka_key, 'query_type_' . $tka_attr['slug'] ) );
                                $tka_chips[] = array(
                                        'label' => $tka_attr['label'] . ': ' . $tka_term->name,
                                        'href'  => $tka_href,
                                );
                        }
                }

                if ( empty( $tka_chips ) ) {
                        return;
                }

                echo '<div class="tka-pfilter__active">';
                echo '<span class="tka-pfilter__active-t">' . esc_html__( 'فیلترهای فعال', 'toykindangel' ) . '</span>';
                echo '<div class="tka-pfilter__chips">';
                foreach ( $tka_chips as $tka_chip ) {
                        echo '<a class="tka-pfilter__chip" href="' . esc_url( $tka_chip['href'] ) . '">'
                                . esc_html( $tka_chip['label'] )
                                . '<svg class="ic" aria-hidden="true"><use href="#i-plus"></use></svg></a>';
                }
                echo '</div></div>';
        }

        /* ------------------------------------------------------------------ */
        /* بخش ۲ — محدوده قیمت                                                 */
        /* ------------------------------------------------------------------ */
        private function section_price() {
                /* رنج واقعی محصولات موجود در همین آرشیو برای placeholder هوشمند. */
                global $wpdb;
                $tka_min_hint = 0;
                $tka_max_hint = 0;
                if ( function_exists( 'wc_get_product_ids_on_sale' ) && ! empty( $wpdb->wc_product_meta_lookup ) ) {
                        $tka_range = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                                "SELECT MIN(min_price) AS mn, MAX(max_price) AS mx
                                 FROM {$wpdb->wc_product_meta_lookup}
                                 INNER JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->wc_product_meta_lookup}.product_id
                                 WHERE {$wpdb->posts}.post_type = 'product' AND {$wpdb->posts}.post_status = 'publish'
                                 LIMIT 1"
                        );
                        if ( $tka_range && $tka_range->mx > 0 ) {
                                $tka_min_hint = (int) floor( (float) $tka_range->mn / 10000 ) * 10000;
                                $tka_max_hint = (int) ceil( (float) $tka_range->mx / 10000 ) * 10000;
                        }
                }

                $tka_min_cur = isset( $_GET['min_price'] ) ? preg_replace( '/[^\d]/', '', wp_unslash( (string) $_GET['min_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $tka_max_cur = isset( $_GET['max_price'] ) ? preg_replace( '/[^\d]/', '', wp_unslash( (string) $_GET['max_price'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

                /*
                 * اکشن فرم: مسیر فعلی بدون /page/N (پس از فیلتر، از صفحهٔ ۱ شمرده
                 * می‌شود) — پارامترهای فیلتر فعلی به‌صورت hidden حفظ می‌شوند تا با
                 * اعمال قیمت، بقیهٔ فیلترها از دست نروند.
                 */
                $tka_path  = isset( $GLOBALS['wp'] ) ? (string) $GLOBALS['wp']->request : '';
                $tka_path  = preg_replace( '#/page/\d+/?$#u', '/', $tka_path );
                $tka_action = esc_url( home_url( '/' . ltrim( $tka_path, '/' ) ) );

                $tka_hidden = array( 'orderby' );
                foreach ( array( 'tka_stock', 'tka_sale' ) as $tka_key ) {
                        if ( isset( $_GET[ $tka_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                                $tka_hidden[] = $tka_key;
                        }
                }
                foreach ( array( 'product_brand', 'tka_brand' ) as $tka_tax ) {
                        if ( taxonomy_exists( $tka_tax ) && isset( $_GET[ 'filter_' . $tka_tax ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                                $tka_hidden[] = 'filter_' . $tka_tax;
                                $tka_hidden[] = 'query_type_' . $tka_tax;
                        }
                }
                foreach ( $this->attribute_taxonomies() as $tka_attr ) {
                        $tka_key = 'filter_' . $tka_attr['slug'];
                        if ( isset( $_GET[ $tka_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                                $tka_hidden[] = $tka_key;
                                $tka_hidden[] = 'query_type_' . $tka_attr['slug'];
                        }
                }

                echo '<details class="tka-pfilter__sec" open>';
                echo '<summary class="tka-pfilter__head"><span>' . esc_html__( 'محدوده قیمت', 'toykindangel' ) . '</span><svg class="ic tka-pfilter__chev" aria-hidden="true"><use href="#i-chev-left"></use></svg></summary>';
                echo '<div class="tka-pfilter__body">';
                echo '<form method="get" action="' . $tka_action . '" class="tka-pfilter__priceform">';
                foreach ( $tka_hidden as $tka_key ) {
                        $tka_val = isset( $_GET[ $tka_key ] ) ? sanitize_text_field( wp_unslash( (string) $_GET[ $tka_key ] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        if ( '' !== $tka_val ) {
                                echo '<input type="hidden" name="' . esc_attr( $tka_key ) . '" value="' . esc_attr( $tka_val ) . '">';
                        }
                }
                echo '<div class="tka-pfilter__price">';
                echo '<input type="text" inputmode="numeric" name="min_price" placeholder="' . esc_attr( $tka_min_hint ? toykindangel_fmt( $tka_min_hint ) : 'از' ) . '" value="' . esc_attr( $tka_min_cur ) . '" aria-label="' . esc_attr__( 'حداقل قیمت', 'toykindangel' ) . '">';
                echo '<span class="tka-pfilter__dash">تا</span>';
                echo '<input type="text" inputmode="numeric" name="max_price" placeholder="' . esc_attr( $tka_max_hint ? toykindangel_fmt( $tka_max_hint ) : 'تا' ) . '" value="' . esc_attr( $tka_max_cur ) . '" aria-label="' . esc_attr__( 'حداکثر قیمت', 'toykindangel' ) . '">';
                echo '</div>';
                echo '<button type="submit" class="tka-pfilter__apply">' . esc_html__( 'اعمال قیمت', 'toykindangel' ) . '</button>';
                echo '</form>';
                echo '</div></details>';
        }

        /* ------------------------------------------------------------------ */
        /* بخش ۳ — موجودی و تخفیف                                              */
        /* ------------------------------------------------------------------ */
        private function section_stock_sale() {
                $tka_base = $this->base_url();

                $tka_stock_on  = isset( $_GET['tka_stock'] ) && 'instock' === sanitize_key( wp_unslash( (string) $_GET['tka_stock'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                $tka_sale_on   = isset( $_GET['tka_sale'] ) && '1' === (string) $_GET['tka_sale']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

                echo '<details class="tka-pfilter__sec" open>';
                echo '<summary class="tka-pfilter__head"><span>' . esc_html__( 'وضعیت کالا', 'toykindangel' ) . '</span><svg class="ic tka-pfilter__chev" aria-hidden="true"><use href="#i-chev-left"></use></svg></summary>';
                echo '<div class="tka-pfilter__body">';

                $tka_stock_href = $tka_stock_on ? remove_query_arg( 'tka_stock' ) : add_query_arg( 'tka_stock', 'instock', $tka_base );
                echo '<a class="tka-pfilter__item' . ( $tka_stock_on ? ' on' : '' ) . '" href="' . esc_url( $tka_stock_href ) . '">';
                echo '<span class="tka-pfilter__check" aria-hidden="true"><svg class="ic"><use href="#i-check"></use></svg></span>';
                echo '<span>' . esc_html__( 'فقط کالاهای موجود', 'toykindangel' ) . '</span>';
                echo '</a>';

                $tka_sale_href = $tka_sale_on ? remove_query_arg( 'tka_sale' ) : add_query_arg( 'tka_sale', '1', $tka_base );
                echo '<a class="tka-pfilter__item' . ( $tka_sale_on ? ' on' : '' ) . '" href="' . esc_url( $tka_sale_href ) . '">';
                echo '<span class="tka-pfilter__check" aria-hidden="true"><svg class="ic"><use href="#i-check"></use></svg></span>';
                echo '<span>' . esc_html__( 'فقط کالاهای تخفیف‌دار', 'toykindangel' ) . '</span>';
                echo '</a>';

                echo '</div></details>';
        }

        /* ------------------------------------------------------------------ */
        /* بخش ۴ — برندها                                                      */
        /* ------------------------------------------------------------------ */
        private function section_brands( $instance ) {
                $tka_tax = taxonomy_exists( 'product_brand' ) ? 'product_brand' : ( taxonomy_exists( 'tka_brand' ) ? 'tka_brand' : '' );
                if ( ! $tka_tax ) {
                        return;
                }

                $tka_max   = max( 5, (int) ( isset( $instance['brands_max'] ) ? $instance['brands_max'] : 15 ) );
                $tka_counts = ! empty( $instance['show_counts'] );

                $tka_terms = get_terms(
                        array(
                                'taxonomy'   => $tka_tax,
                                'hide_empty' => true,
                                'number'     => 50,
                        )
                );
                if ( is_wp_error( $tka_terms ) || empty( $tka_terms ) ) {
                        return;
                }

                /* پرتکرارترین‌ها اول تا سقف مجاز. */
                usort(
                        $tka_terms,
                        static function ( $tka_a, $tka_b ) {
                                return ( (int) $tka_b->count ) <=> ( (int) $tka_a->count );
                        }
                );
                $tka_terms = array_slice( $tka_terms, 0, $tka_max );

                echo '<details class="tka-pfilter__sec">';
                echo '<summary class="tka-pfilter__head"><span>' . esc_html__( 'برند', 'toykindangel' ) . '</span><svg class="ic tka-pfilter__chev" aria-hidden="true"><use href="#i-chev-left"></use></svg></summary>';
                echo '<div class="tka-pfilter__body"><div class="tka-pfilter__list">';
                foreach ( $tka_terms as $tka_term ) {
                        $tka_link = get_term_link( $tka_term );
                        if ( is_wp_error( $tka_link ) ) {
                                continue;
                        }
                        echo '<a class="tka-pfilter__item" href="' . esc_url( $tka_link ) . '">';
                        echo '<span class="tka-pfilter__name">' . esc_html( $tka_term->name ) . '</span>';
                        if ( $tka_counts ) {
                                echo '<span class="tka-pfilter__count num">' . esc_html( toykindangel_fmt( (int) $tka_term->count ) ) . '</span>';
                        }
                        echo '</a>';
                }
                echo '</div></div></details>';
        }

        /* ------------------------------------------------------------------ */
        /* بخش ۵ — دسته‌بندی‌ها                                                 */
        /* ------------------------------------------------------------------ */
        private function section_cats() {
                if ( ! taxonomy_exists( 'product_cat' ) ) {
                        return;
                }

                $tka_queried = get_queried_object();
                $tka_args    = array(
                        'taxonomy'   => 'product_cat',
                        'hide_empty' => true,
                        'number'     => 20,
                );

                if ( is_product_category() && $tka_queried instanceof WP_Term ) {
                        /* زیردسته‌ها؛ اگر نبود هم‌سطح‌ها؛ اگر نبود دسته‌های اصلی. */
                        $tka_args['parent']    = (int) $tka_queried->term_id;
                        $tka_terms             = get_terms( $tka_args );
                        if ( is_wp_error( $tka_terms ) || empty( $tka_terms ) ) {
                                $tka_args['parent'] = (int) $tka_queried->parent;
                                $tka_terms          = get_terms( $tka_args );
                        }
                } else {
                        $tka_args['parent'] = 0;
                        $tka_args['exclude'] = array( (int) get_option( 'default_product_cat', 0 ) );
                        $tka_terms          = get_terms( $tka_args );
                }

                if ( is_wp_error( $tka_terms ) || empty( $tka_terms ) ) {
                        return;
                }

                echo '<details class="tka-pfilter__sec">';
                echo '<summary class="tka-pfilter__head"><span>' . esc_html__( 'دسته‌بندی‌ها', 'toykindangel' ) . '</span><svg class="ic tka-pfilter__chev" aria-hidden="true"><use href="#i-chev-left"></use></svg></summary>';
                echo '<div class="tka-pfilter__body"><div class="tka-pfilter__list">';
                foreach ( $tka_terms as $tka_term ) {
                        $tka_link = get_term_link( $tka_term );
                        if ( is_wp_error( $tka_link ) ) {
                                continue;
                        }
                        $tka_on = ( $tka_queried instanceof WP_Term && (int) $tka_queried->term_id === (int) $tka_term->term_id ) ? ' on' : '';
                        echo '<a class="tka-pfilter__item' . $tka_on . '" href="' . esc_url( $tka_link ) . '">';
                        echo '<span class="tka-pfilter__name">' . esc_html( $tka_term->name ) . '</span>';
                        echo '<span class="tka-pfilter__count num">' . esc_html( toykindangel_fmt( (int) $tka_term->count ) ) . '</span>';
                        echo '</a>';
                }
                echo '</div></div></details>';
        }

        /* ------------------------------------------------------------------ */
        /* بخش ۶ — ویژگی‌ها (لایرد-نِو استاندارد ووکامرس)                       */
        /* ------------------------------------------------------------------ */
        private function section_attrs( $instance ) {
                $tka_attrs = $this->attribute_taxonomies();
                if ( empty( $tka_attrs ) ) {
                        return;
                }

                $tka_max_attrs = max( 1, (int) ( isset( $instance['attrs_max'] ) ? $instance['attrs_max'] : 4 ) );
                $tka_attrs     = array_slice( $tka_attrs, 0, $tka_max_attrs );
                $tka_base      = $this->base_url();

                foreach ( $tka_attrs as $tka_attr ) {
                        $tka_key   = 'filter_' . $tka_attr['slug'];
                        $tka_qtype = 'query_type_' . $tka_attr['slug'];
                        $tka_cur   = array();
                        if ( isset( $_GET[ $tka_key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                                $tka_cur = array_filter( explode( ',', sanitize_text_field( wp_unslash( (string) $_GET[ $tka_key ] ) ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
                        }
                        $tka_cur_type = isset( $_GET[ $tka_qtype ] ) ? ( 'and' === sanitize_key( wp_unslash( (string) $_GET[ $tka_qtype ] ) ) ? 'and' : 'or' ) : 'or'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

                        echo '<details class="tka-pfilter__sec">';
                        echo '<summary class="tka-pfilter__head"><span>' . esc_html( $tka_attr['label'] ) . '</span><svg class="ic tka-pfilter__chev" aria-hidden="true"><use href="#i-chev-left"></use></svg></summary>';
                        echo '<div class="tka-pfilter__body"><div class="tka-pfilter__list">';

                        foreach ( $tka_attr['terms'] as $tka_term ) {
                                $tka_on = in_array( $tka_term->slug, $tka_cur, true );

                                if ( $tka_on ) {
                                        $tka_rest = array_values( array_diff( $tka_cur, array( $tka_term->slug ) ) );
                                        $tka_url  = $tka_rest
                                                ? add_query_arg( array( $tka_key => implode( ',', $tka_rest ), $tka_qtype => $tka_cur_type ), $tka_base )
                                                : remove_query_arg( array( $tka_key, $tka_qtype ) );
                                } else {
                                        $tka_next = array_merge( $tka_cur, array( $tka_term->slug ) );
                                        $tka_url  = add_query_arg( array( $tka_key => implode( ',', $tka_next ), $tka_qtype => 'or' ), $tka_base );
                                }

                                echo '<a class="tka-pfilter__item' . ( $tka_on ? ' on' : '' ) . '" href="' . esc_url( $tka_url ) . '">';
                                echo '<span class="tka-pfilter__check" aria-hidden="true"><svg class="ic"><use href="#i-check"></use></svg></span>';
                                echo '<span class="tka-pfilter__name">' . esc_html( $tka_term->name ) . '</span>';
                                echo '<span class="tka-pfilter__count num">' . esc_html( toykindangel_fmt( (int) $tka_term->count ) ) . '</span>';
                                echo '</a>';
                        }

                        echo '</div></div></details>';
                }
        }

        /* ------------------------------------------------------------------ */
        /* فرم مدیریت                                                          */
        /* ------------------------------------------------------------------ */

        /**
         * فرم تنظیمات ویجت در پیشخوان.
         *
         * @param array $instance Settings.
         * @return string
         */
        public function form( $instance ) {
                $tka_title        = isset( $instance['title'] ) ? $instance['title'] : __( 'فیلتر محصولات', 'toykindangel' );
                $tka_show_price   = ! empty( $instance['show_price'] );
                $tka_show_ss      = ! empty( $instance['show_stock_sale'] );
                $tka_show_brands  = ! empty( $instance['show_brands'] );
                $tka_show_cats    = ! empty( $instance['show_cats'] );
                $tka_show_attrs   = ! empty( $instance['show_attrs'] );
                $tka_brands_max   = isset( $instance['brands_max'] ) ? (int) $instance['brands_max'] : 15;
                $tka_attrs_max    = isset( $instance['attrs_max'] ) ? (int) $instance['attrs_max'] : 4;
                $tka_show_counts  = ! empty( $instance['show_counts'] );
                $tka_field_id     = $this->get_field_id( 'title' );
                ?>
                <p>
                        <label for="<?php echo esc_attr( $tka_field_id ); ?>"><?php esc_html_e( 'عنوان:', 'toykindangel' ); ?></label>
                        <input class="widefat" id="<?php echo esc_attr( $tka_field_id ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $tka_title ); ?>">
                </p>
                <p>
                        <label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_price' ) ); ?>" value="1" <?php checked( $tka_show_price ); ?>> <?php esc_html_e( 'محدوده قیمت', 'toykindangel' ); ?></label><br>
                        <label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_stock_sale' ) ); ?>" value="1" <?php checked( $tka_show_ss ); ?>> <?php esc_html_e( 'وضعیت کالا (موجود / تخفیف‌دار)', 'toykindangel' ); ?></label><br>
                        <label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_brands' ) ); ?>" value="1" <?php checked( $tka_show_brands ); ?>> <?php esc_html_e( 'برندها', 'toykindangel' ); ?></label><br>
                        <label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_cats' ) ); ?>" value="1" <?php checked( $tka_show_cats ); ?>> <?php esc_html_e( 'دسته‌بندی‌ها', 'toykindangel' ); ?></label><br>
                        <label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_attrs' ) ); ?>" value="1" <?php checked( $tka_show_attrs ); ?>> <?php esc_html_e( 'ویژگی‌ها (رنگ، سایز و…)', 'toykindangel' ); ?></label><br>
                        <label><input type="checkbox" name="<?php echo esc_attr( $this->get_field_name( 'show_counts' ) ); ?>" value="1" <?php checked( $tka_show_counts ); ?>> <?php esc_html_e( 'نمایش شمارندهٔ کالا', 'toykindangel' ); ?></label>
                </p>
                <p>
                        <label><?php esc_html_e( 'حداکثر برندها:', 'toykindangel' ); ?>
                                <input class="tiny-text" type="number" min="5" max="50" name="<?php echo esc_attr( $this->get_field_name( 'brands_max' ) ); ?>" value="<?php echo esc_attr( (string) $tka_brands_max ); ?>">
                        </label><br>
                        <label><?php esc_html_e( 'حداکثر بخش‌های ویژگی:', 'toykindangel' ); ?>
                                <input class="tiny-text" type="number" min="1" max="10" name="<?php echo esc_attr( $this->get_field_name( 'attrs_max' ) ); ?>" value="<?php echo esc_attr( (string) $tka_attrs_max ); ?>">
                        </label>
                </p>
                <?php
                return '';
        }

        /**
         * ذخیرهٔ تنظیمات.
         *
         * @param array $new_instance New values.
         * @param array $old_instance Old values.
         * @return array
         */
        public function update( $new_instance, $old_instance ) {
                $instance = array();

                $instance['title']          = isset( $new_instance['title'] ) ? sanitize_text_field( $new_instance['title'] ) : '';
                $instance['show_price']     = ! empty( $new_instance['show_price'] ) ? 1 : 0;
                $instance['show_stock_sale'] = ! empty( $new_instance['show_stock_sale'] ) ? 1 : 0;
                $instance['show_brands']    = ! empty( $new_instance['show_brands'] ) ? 1 : 0;
                $instance['show_cats']      = ! empty( $new_instance['show_cats'] ) ? 1 : 0;
                $instance['show_attrs']     = ! empty( $new_instance['show_attrs'] ) ? 1 : 0;
                $instance['show_counts']    = ! empty( $new_instance['show_counts'] ) ? 1 : 0;
                $instance['brands_max']     = min( 50, max( 5, (int) ( isset( $new_instance['brands_max'] ) ? $new_instance['brands_max'] : 15 ) ) );
                $instance['attrs_max']      = min( 10, max( 1, (int) ( isset( $new_instance['attrs_max'] ) ? $new_instance['attrs_max'] : 4 ) ) );

                return $instance;
        }
}

/**
 * ثبت ویجت.
 *
 * @return void
 */
function toykindangel_register_filter_widget() {
        register_widget( 'TKA_Product_Filters_Widget' );
}
add_action( 'widgets_init', 'toykindangel_register_filter_widget' );
