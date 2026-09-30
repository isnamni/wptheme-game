<?php
/**
 * Admin: WooCommerce extras for the store manager.
 *
 * - Product metabox «فرشته مهربون» (خرید قسطی badge — tka_bnpl meta read
 *   by inc/product-card.php and the PDP).
 * - Persian labels for the My Account orders table columns.
 * - Product list columns (قسطی badge + موجودی).
 * - Term meta quick fields: story/rail ordering for product_cat and
 *   brand ordering for tka_brand (read by inc/cat-data.php + drawer).
 * - wp-admin dashboard widget «وضعیت فروشگاه» (today/month sales,
 *   orders to fulfil, low stock, latest orders).
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/* ================================================================== */
/* ۱) متاباکس محصول: خرید قسطی                                        */
/* ================================================================== */

/**
 * Register the product metabox.
 */
function toykindangel_product_metabox() {
        add_meta_box(
                'tka_product_box',
                __( 'فرشته مهربون — تنظیمات نمایش', 'toykindangel' ),
                'toykindangel_product_metabox_html',
                'product',
                'side',
                'default'
        );
}
add_action( 'add_meta_boxes', 'toykindangel_product_metabox' );

/**
 * Metabox markup.
 *
 * @param WP_Post $post Post.
 */
function toykindangel_product_metabox_html( $post ) {
        wp_nonce_field( 'tka_product_meta', 'tka_product_meta_nonce' );

        $tka_bnpl       = get_post_meta( $post->ID, 'tka_bnpl', true );
        $tka_bnpl       = ( '' !== $tka_bnpl ) ? wp_validate_boolean( $tka_bnpl ) : false;
        $tka_story_rank = (int) get_post_meta( $post->ID, 'tka_story_rank', true );
        ?>
        <p>
                <label style="display:flex;align-items:center;gap:6px;font-weight:600">
                        <input type="checkbox" name="tka_bnpl" value="1" <?php checked( $tka_bnpl ); ?>>
                        <?php esc_html_e( 'خرید قسطی (نمایش بج روی کارت محصول)', 'toykindangel' ); ?>
                </label>
        </p>
        <p>
                <label for="tka_story_rank" style="display:block;font-weight:600;margin-bottom:4px">
                        <?php esc_html_e( 'اولویت نمایش در ریل «پیشنهاد شگفت‌انگیز»', 'toykindangel' ); ?>
                </label>
                <input type="number" id="tka_story_rank" name="tka_story_rank" min="0" max="99" step="1"
                        value="<?php echo esc_attr( (string) $tka_story_rank ); ?>" style="width:80px">
                <span style="color:#757575;font-size:11px"><?php esc_html_e( '(۰ = پیش‌فرض)', 'toykindangel' ); ?></span>
        </p>
        <?php
}

/**
 * Save the metabox values.
 *
 * @param int $post_id Post id.
 */
function toykindangel_product_metabox_save( $post_id ) {
        if ( ! isset( $_POST['tka_product_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['tka_product_meta_nonce'] ) ), 'tka_product_meta' ) ) {
                return;
        }
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
        }
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
                return;
        }

        update_post_meta( $post_id, 'tka_bnpl', isset( $_POST['tka_bnpl'] ) ? '1' : '0' );
        update_post_meta( $post_id, 'tka_story_rank', isset( $_POST['tka_story_rank'] ) ? (int) $_POST['tka_story_rank'] : 0 );
}
add_action( 'save_post_product', 'toykindangel_product_metabox_save' );

/* ================================================================== */
/* ۲) ستون‌های فارسی جدول سفارش‌ها (پنل کاربری)                       */
/* ================================================================== */

/**
 * Persian labels for wc_get_account_orders_columns().
 *
 * @param array $columns Columns.
 * @return array
 */
function toykindangel_account_orders_columns( $columns ) {
        if ( isset( $columns['order-number'] ) ) {
                $columns['order-number'] = __( 'سفارش', 'toykindangel' );
        }
        if ( isset( $columns['order-date'] ) ) {
                $columns['order-date'] = __( 'تاریخ', 'toykindangel' );
        }
        if ( isset( $columns['order-status'] ) ) {
                $columns['order-status'] = __( 'وضعیت', 'toykindangel' );
        }
        if ( isset( $columns['order-total'] ) ) {
                $columns['order-total'] = __( 'مبلغ', 'toykindangel' );
        }
        if ( isset( $columns['order-actions'] ) ) {
                $columns['order-actions'] = __( 'عملیات', 'toykindangel' );
        }
        return $columns;
}
add_filter( 'woocommerce_account_orders_columns', 'toykindangel_account_orders_columns' );

/* ================================================================== */
/* ۳) ستون‌های فهرست محصولات ادمین                                    */
/* ================================================================== */

/**
 * Add BNPL + stock columns to the admin product list.
 *
 * @param array $columns Columns.
 * @return array
 */
function toykindangel_admin_product_columns( $columns ) {
        $tka_new = array();
        foreach ( $columns as $tka_key => $tka_label ) {
                $tka_new[ $tka_key ] = $tka_label;
                if ( 'name' === $tka_key ) {
                        $tka_new['tka_bnpl'] = __( 'قسطی', 'toykindangel' );
                }
        }
        return $tka_new;
}
add_filter( 'manage_edit-product_columns', 'toykindangel_admin_product_columns', 20 );

/**
 * Render the BNPL column.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post id.
 */
function toykindangel_admin_product_column_content( $column, $post_id ) {
        if ( 'tka_bnpl' !== $column ) {
                return;
        }
        if ( wp_validate_boolean( get_post_meta( $post_id, 'tka_bnpl', true ) ) ) {
                echo '<span style="display:inline-block;background:#f7e5f5;color:#b400ae;border-radius:999px;padding:2px 10px;font-size:11px;font-weight:700">' . esc_html__( 'قسطی', 'toykindangel' ) . '</span>';
        } else {
                echo '<span style="color:#bdbdbd">—</span>';
        }
}
add_action( 'manage_product_posts_custom_column', 'toykindangel_admin_product_column_content', 10, 2 );

/* ================================================================== */
/* ۴) متای ترم: استوری/ریل/برند                                       */
/* ================================================================== */

/**
 * Add-story/rail fields on the product_cat add form.
 */
function toykindangel_cat_add_fields() {
        ?>
        <?php wp_nonce_field( 'tka_term_meta', 'tka_term_meta_nonce' ); ?>
        <div class="form-field">
                <label><input type="checkbox" name="tka_is_story" value="1"> <?php esc_html_e( 'نمایش در استوری‌های صفحهٔ اصلی', 'toykindangel' ); ?></label>
        </div>
        <div class="form-field">
                <label for="tka_story_order"><?php esc_html_e( 'ترتیب در استوری‌ها', 'toykindangel' ); ?></label>
                <input type="number" id="tka_story_order" name="tka_story_order" min="0" max="99" value="0">
        </div>
        <div class="form-field">
                <label for="tka_rail_order"><?php esc_html_e( 'ترتیب در ریل دسته‌بندی‌ها', 'toykindangel' ); ?></label>
                <input type="number" id="tka_rail_order" name="tka_rail_order" min="0" max="99" value="0">
        </div>
        <?php
}
add_action( 'product_cat_add_form_fields', 'toykindangel_cat_add_fields' );

/**
 * Story/rail fields on the product_cat edit screen.
 *
 * @param WP_Term $term Term.
 */
function toykindangel_cat_edit_fields( $term ) {
        $tka_is_story = wp_validate_boolean( get_term_meta( $term->term_id, 'tka_is_story', true ) );
        $tka_story    = (int) get_term_meta( $term->term_id, 'tka_story_order', true );
        $tka_rail     = (int) get_term_meta( $term->term_id, 'tka_rail_order', true );
        ?>
        <?php wp_nonce_field( 'tka_term_meta', 'tka_term_meta_nonce' ); ?>
        <tr class="form-field">
                <th scope="row"><label><?php esc_html_e( 'استوری صفحهٔ اصلی', 'toykindangel' ); ?></label></th>
                <td>
                        <label style="display:flex;align-items:center;gap:6px">
                                <input type="checkbox" name="tka_is_story" value="1" <?php checked( $tka_is_story ); ?>>
                                <?php esc_html_e( 'این دسته در ریل استوری‌ها نمایش داده شود', 'toykindangel' ); ?>
                        </label>
                </td>
        </tr>
        <tr class="form-field">
                <th scope="row"><label for="tka_story_order"><?php esc_html_e( 'ترتیب در استوری‌ها', 'toykindangel' ); ?></label></th>
                <td><input type="number" id="tka_story_order" name="tka_story_order" min="0" max="99" value="<?php echo esc_attr( (string) $tka_story ); ?>"></td>
        </tr>
        <tr class="form-field">
                <th scope="row"><label for="tka_rail_order"><?php esc_html_e( 'ترتیب در ریل دسته‌بندی‌ها', 'toykindangel' ); ?></label></th>
                <td><input type="number" id="tka_rail_order" name="tka_rail_order" min="0" max="99" value="<?php echo esc_attr( (string) $tka_rail ); ?>"></td>
        </tr>
        <?php
}
add_action( 'product_cat_edit_form_fields', 'toykindangel_cat_edit_fields' );

/**
 * Brand order field on tka_brand add/edit screens.
 *
 * @param WP_Term $term     Term (edit only).
 * @param string  $taxonomy Taxonomy.
 */
function toykindangel_brand_fields( $term = null, $taxonomy = '' ) {
        $tka_order = $term ? (int) get_term_meta( $term->term_id, 'tka_brand_order', true ) : 0;
        wp_nonce_field( 'tka_term_meta', 'tka_term_meta_nonce' );
        if ( $term ) {
                ?>
                <tr class="form-field">
                        <th scope="row"><label for="tka_brand_order"><?php esc_html_e( 'ترتیب نمایش برند', 'toykindangel' ); ?></label></th>
                        <td><input type="number" id="tka_brand_order" name="tka_brand_order" min="0" max="99" value="<?php echo esc_attr( (string) $tka_order ); ?>"></td>
                </tr>
                <?php
        } else {
                ?>
                <div class="form-field">
                        <label for="tka_brand_order"><?php esc_html_e( 'ترتیب نمایش برند', 'toykindangel' ); ?></label>
                        <input type="number" id="tka_brand_order" name="tka_brand_order" min="0" max="99" value="0">
                </div>
                <?php
        }
}
add_action( 'tka_brand_add_form_fields', 'toykindangel_brand_fields' );
add_action( 'tka_brand_edit_form_fields', 'toykindangel_brand_fields', 10, 2 );

/**
 * Save term meta (create + edit).
 *
 * @param int $term_id Term id.
 */
function toykindangel_save_term_meta( $term_id ) {
        if ( ! current_user_can( 'manage_product_terms' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- real WooCommerce capability
                return;
        }

        /*
         * Audit P2-8: verify our own nonce, but only when at least one of the
         * theme's term fields is actually in the request — WP core's Quick Edit
         * posts a plain tag update (no custom fields, nonce verified by core)
         * and must keep working untouched.
         */
        $tka_has_our_fields = isset( $_POST['tka_is_story'] ) || isset( $_POST['tka_story_order'] )
                || isset( $_POST['tka_rail_order'] ) || isset( $_POST['tka_brand_order'] );
        if ( $tka_has_our_fields ) {
                check_admin_referer( 'tka_term_meta', 'tka_term_meta_nonce' );
        }

        /* product_cat fields. */
        if ( isset( $_POST['tka_is_story'] ) ) {
                update_term_meta( $term_id, 'tka_is_story', '1' );
        } elseif ( isset( $_POST['tag_ID'] ) || isset( $_POST['tka_story_order'] ) ) {
                delete_term_meta( $term_id, 'tka_is_story' );
        }
        foreach ( array( 'tka_story_order', 'tka_rail_order', 'tka_brand_order' ) as $tka_key ) {
                if ( isset( $_POST[ $tka_key ] ) ) {
                        update_term_meta( $term_id, $tka_key, absint( wp_unslash( $_POST[ $tka_key ] ) ) );
                }
        }
}
add_action( 'created_term', 'toykindangel_save_term_meta' );
add_action( 'edited_term', 'toykindangel_save_term_meta' );

/* ================================================================== */
/* ۵) ویجت پیشخوان: وضعیت فروشگاه                                    */
/* ================================================================== */

/**
 * Register the dashboard widget.
 */
function toykindangel_dashboard_widget() {
        if ( ! current_user_can( 'manage_woocommerce' ) ) { // phpcs:ignore WordPress.WP.Capabilities.Unknown -- real WooCommerce capability
                return;
        }
        wp_add_dashboard_widget( 'tka_shop_status', __( 'فرشته مهربون — وضعیت فروشگاه', 'toykindangel' ), 'toykindangel_dashboard_widget_html' );
}
add_action( 'wp_dashboard_setup', 'toykindangel_dashboard_widget' );

/**
 * Widget markup.
 */
function toykindangel_dashboard_widget_html() {
        if ( ! function_exists( 'wc_get_orders' ) ) {
                echo '<p>' . esc_html__( 'ووکامرس فعال نیست.', 'toykindangel' ) . '</p>';
                return;
        }

        $tka_today_start  = strtotime( 'today' );
        $tka_month_start  = strtotime( gmdate( 'Y-m-01' ) );

        $tka_today = wc_get_orders(
                array(
                        'status'     => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
                        'date_after' => gmdate( 'Y-m-d H:i:s', $tka_today_start ),
                        'limit'      => -1,
                )
        );
        $tka_month = wc_get_orders(
                array(
                        'status'     => array( 'wc-completed', 'wc-processing', 'wc-on-hold' ),
                        'date_after' => gmdate( 'Y-m-d H:i:s', $tka_month_start ),
                        'limit'      => -1,
                )
        );

        $tka_today_sum = 0;
        foreach ( $tka_today as $tka_o ) {
                $tka_today_sum += (float) $tka_o->get_total();
        }
        $tka_month_sum = 0;
        foreach ( $tka_month as $tka_o ) {
                $tka_month_sum += (float) $tka_o->get_total();
        }

        $tka_processing = (int) wp_count_posts( 'shop_order' )->{'wc-processing'};
        $tka_low_stock  = 0;
        if ( class_exists( 'WC_Product_Query' ) ) {
                /**
                 * Low-stock count (fixed in 0.17.0 — found by PHPStan, audit §8).
                 *
                 * The previous code called wc_get_low_stock_amount() with no
                 * argument (fatal ArgumentCountError with WooCommerce active)
                 * and passed an unsupported 'stock' => array( min, max ) query
                 * arg (silently ignored by WC_Product_Query). Rewritten with
                 * supported args: manage_stock filter + the global threshold
                 * option, filtered in PHP.
                 */
                $tka_low_stock_threshold = (int) get_option( 'woocommerce_notify_low_stock_amount', 2 );
                $tka_low_stock_candidates = wc_get_products(
                        array(
                                'type'         => 'simple',
                                'limit'        => 200,
                                'manage_stock' => 'yes',
                                'return'       => 'objects',
                        )
                );
                foreach ( $tka_low_stock_candidates as $tka_low_stock_product ) {
                        $tka_low_stock_qty = (int) $tka_low_stock_product->get_stock_quantity();
                        if ( $tka_low_stock_qty > 0 && $tka_low_stock_qty <= $tka_low_stock_threshold ) {
                                $tka_low_stock++;
                        }
                }
        }

        $tka_recent = wc_get_orders( array( 'limit' => 5, 'orderby' => 'date', 'order' => 'DESC' ) );
        ?>
        <style>
                .tka-dash{font-size:13px}
                .tka-dash__stats{display:grid;grid-template-columns:repeat(2,1fr);gap:8px;margin-bottom:12px}
                .tka-dash__stat{background:#f7e5f5;border-radius:10px;padding:10px 12px}
                .tka-dash__stat b{display:block;font-size:16px;color:#b400ae}
                .tka-dash__stat span{font-size:11px;color:#616161}
                .tka-dash table{width:100%;border-collapse:collapse}
                .tka-dash td{padding:6px 4px;border-top:1px solid #f0f0f1}
        </style>
        <div class="tka-dash">
                <div class="tka-dash__stats">
                        <div class="tka-dash__stat">
                                <b><?php echo esc_html( number_format( $tka_today_sum ) ); ?></b>
                                <span><?php esc_html_e( 'فروش امروز (تومان)', 'toykindangel' ); ?></span>
                        </div>
                        <div class="tka-dash__stat">
                                <b><?php echo esc_html( number_format( $tka_month_sum ) ); ?></b>
                                <span><?php esc_html_e( 'فروش این ماه (تومان)', 'toykindangel' ); ?></span>
                        </div>
                        <div class="tka-dash__stat">
                                <b><?php echo (int) $tka_processing; ?></b>
                                <span><?php esc_html_e( 'سفارش در انتظار ارسال', 'toykindangel' ); ?></span>
                        </div>
                        <div class="tka-dash__stat">
                                <b><?php echo (int) $tka_low_stock; ?></b>
                                <span><?php esc_html_e( 'کالای کم‌موجود', 'toykindangel' ); ?></span>
                        </div>
                </div>

                <?php if ( $tka_recent ) : ?>
                        <table>
                                <?php foreach ( $tka_recent as $tka_o ) : ?>
                                        <tr>
                                                <td>
                                                        <a href="<?php echo esc_url( $tka_o->get_edit_order_url() ); ?>">
                                                                #<?php echo esc_html( $tka_o->get_order_number() ); ?>
                                                                — <?php echo esc_html( $tka_o->get_formatted_billing_full_name() ); ?>
                                                        </a>
                                                </td>
                                                <td><?php echo esc_html( wc_get_order_status_name( $tka_o->get_status() ) ); ?></td>
                                                <td style="text-align:left"><?php echo wp_kses_post( $tka_o->get_formatted_order_total() ); ?></td>
                                        </tr>
                                <?php endforeach; ?>
                        </table>
                <?php else : ?>
                        <p style="color:#757575"><?php esc_html_e( 'هنوز سفارشی ثبت نشده است.', 'toykindangel' ); ?></p>
                <?php endif; ?>
        </div>
        <?php
}
