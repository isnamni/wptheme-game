<?php
/**
 * Single product (PDP) — 1:1 demo product.html markup fed by real
 * WooCommerce data.
 *
 * Demo parity notes:
 * - pdp__bar (back / title / share / cart) replaces the site header.
 * - gallery (#galleryTrack / #galleryThumbs) is auto-wired by the
 *   untouched demo app.js gallery() routine.
 * - The sticky purchase bar (.pdp__cta) sits above the bottomnav.
 *
 * Standardization notes (P0 review fix) — the real WooCommerce hooks
 * fire inside this layout (core callbacks that would duplicate the
 * demo markup are unhooked in inc/wc-hooks.php):
 * - woocommerce_before_single_product        → notices.
 * - woocommerce_before_single_product_summary → gallery area (plugins).
 * - woocommerce_single_product_summary        → add-to-cart region: the
 *   real output (quantity input + form for simple, variation form for
 *   variable). Title/rating/price stay custom (demo markup).
 * - woocommerce_after_single_product_summary / _after_single_product.
 * - The product is wrapped in the standard #product-N div via post_class().
 *
 * 18-f (professional PDP): the sections خلاصه + چیپ‌های ویژگی، ردیف
 * اشتراک‌گذاری (واتساپ/تلگرام/کپی لینک)، و سه کارت تمام‌عرض
 * «توضیحات محصول» / «مشخصات محصول» (جدول کلید/مقدار از دیتای واقعی:
 * دسته‌بندی، برند، ویژگی‌ها، وزن، ابعاد، کد محصول، برچسب‌ها) /
 * «دیدگاه‌ها» (با شمارنده) + نوار انکر دسکتاپ .pdp__tabs.
 *
 * @version 1.7.0
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

get_header( 'pdp' );

the_post();

global $product;

if ( ! $product instanceof WC_Product ) {
        $product = wc_get_product( get_the_ID() );
}

$tka_id        = $product ? $product->get_id() : get_the_ID();
$tka_name      = $product ? $product->get_name() : get_the_title();
$tka_price     = $product ? (float) $product->get_price() : 0;
$tka_regular   = $product ? (float) $product->get_regular_price() : 0;
$tka_off       = 0;
if ( $tka_regular > 0 && $tka_price > 0 && $tka_price < $tka_regular ) {
        $tka_off = (int) round( ( ( $tka_regular - $tka_price ) / $tka_regular ) * 100 );
}
$tka_is_simple = $product && $product->is_type( 'simple' );
$tka_purchasable = $product && $product->is_purchasable() && $product->is_in_stock();
$tka_cart_count = ( function_exists( 'WC' ) && WC()->cart ) ? WC()->cart->get_cart_contents_count() : 0;

/* Short description (خلاصه) + share targets + review count (18-f). */
$tka_short     = $product ? (string) $product->get_short_description() : '';
$tka_link      = get_permalink( $tka_id );
$tka_wa        = 'https://wa.me/?text=' . rawurlencode( $tka_name . ' — ' . $tka_link );
$tka_tg        = 'https://t.me/share/url?url=' . rawurlencode( $tka_link ) . '&text=' . rawurlencode( $tka_name );
$tka_reviews_n = $product ? (int) $product->get_review_count() : 0;

/* Gallery images: featured + product gallery. */
$tka_gallery_ids = array();
if ( $product ) {
        $tka_gallery_ids = array_merge( array( (int) $product->get_image_id() ), $product->get_gallery_image_ids() );
        $tka_gallery_ids = array_filter( array_unique( $tka_gallery_ids ) );
}

/* Category chain for the breadcrumb (demo shows leaf first, root last). */
$tka_bc_terms = array();
if ( $product ) {
        $tka_cats = get_the_terms( $tka_id, 'product_cat' );
        if ( $tka_cats && ! is_wp_error( $tka_cats ) ) {
		$tka_main = null;
		foreach ( $tka_cats as $tka_cat ) {
				if ( 'uncategorized' !== $tka_cat->slug ) {
				$tka_main = $tka_cat;
				break;
				}
			}
		if ( ! $tka_main && $tka_cats ) {
				$tka_main = $tka_cats[0];
			}
		if ( $tka_main ) {
				$tka_chain     = array_reverse( get_ancestors( $tka_main->term_id, 'product_cat' ) );
				$tka_bc_terms  = array();
				foreach ( $tka_chain as $tka_anc_id ) {
				$tka_anc = get_term( $tka_anc_id, 'product_cat' );
				if ( $tka_anc && ! is_wp_error( $tka_anc ) ) {
					$tka_bc_terms[] = $tka_anc;
					}
					}
				$tka_bc_terms[] = $tka_main;
				/* Demo order: leaf → root (the root is rendered as text). */
				$tka_bc_terms = array_reverse( $tka_bc_terms );
			}
        }
}

/* Rating data (demo shows filled stars = floor of the average). */
$tka_rating   = $product ? (float) $product->get_average_rating() : 0;
$tka_rcount   = $product ? (int) $product->get_rating_count() : 0;
$tka_rfilled  = (int) floor( $tka_rating );

/* Low stock notice (demo shows «تنها N عدد باقی‌مانده در انبار»). */
$tka_stock_qty  = $product && $product->managing_stock() ? (int) $product->get_stock_quantity() : 0;
$tka_low_amount = function_exists( 'wc_get_low_stock_amount' ) ? (int) wc_get_low_stock_amount( $product ) : 0;
$tka_show_low   = $product && $product->managing_stock() && $tka_purchasable && $tka_low_amount > 0 && $tka_stock_qty <= $tka_low_amount;

/* Shipping methods + warranty (Customizer with demo defaults). */
$tka_ships = array(
        array(
                'icon' => 'i-truck',
                't'    => get_theme_mod( 'tka_ship_1_title', __( 'عادی', 'toykindangel' ) ),
                'n'    => get_theme_mod( 'tka_ship_1_note', __( 'ارسال از انبار فروشگاه — ۲ تا ۴ روز کاری', 'toykindangel' ) ),
        ),
        array(
                'icon' => 'i-fire',
                't'    => get_theme_mod( 'tka_ship_2_title', __( 'اکسپرس', 'toykindangel' ) ),
                'n'    => get_theme_mod( 'tka_ship_2_note', __( 'ارسال در سریع‌ترین زمان — ویژه مشهد', 'toykindangel' ) ),
        ),
);
$tka_warranty = get_theme_mod( 'tka_warranty_text', __( 'گارانتی سلامت فیزیکی کالا', 'toykindangel' ) );

/* Stock status row of the warranty box. */
$tka_stock_row = '';
if ( $product ) {
        if ( ! $product->is_in_stock() ) {
		$tka_stock_row = array( 'ok' => false, 'text' => __( 'فعلاً ناموجود', 'toykindangel' ) );
        } elseif ( $product->is_on_backorder( 1 ) ) {
		$tka_stock_row = array( 'ok' => true, 'text' => __( 'پیش‌خرید — به‌زودی شارژ می‌شود', 'toykindangel' ) );
        } else {
		$tka_stock_row = array( 'ok' => true, 'text' => __( 'موجود در انبار فروشگاه', 'toykindangel' ) );
        }
}

/* Specs: دسته‌بندی/برند + ویژگی‌های قابل‌نمایش + وزن/ابعاد/کد محصول/برچسب‌ها
    (جدول «مشخصات محصول» — 18-f). */
$tka_specs = array();
if ( $product ) {
        /* دسته‌بندی: همان زنجیرهٔ بردکرامب، این‌بار ریشه → برگ. */
        if ( $tka_bc_terms ) {
		$tka_cat_names = array();
		foreach ( array_reverse( $tka_bc_terms ) as $tka_cat ) {
				$tka_cat_names[] = $tka_cat->name;
			}
		$tka_specs[] = array(
		'k' => __( 'دسته‌بندی', 'toykindangel' ),
		'v' => implode( ' › ', $tka_cat_names ),
		);
        }

        /* برند (اولین تکسونومی برند موجود). */
        foreach ( array( 'product_brand', 'tka_brand' ) as $tka_brand_tax ) {
		$tka_brand_terms = get_the_terms( $tka_id, $tka_brand_tax );
		if ( $tka_brand_terms && ! is_wp_error( $tka_brand_terms ) ) {
				$tka_specs[] = array(
						'k' => __( 'برند', 'toykindangel' ),
						'v' => $tka_brand_terms[0]->name,
				);
				break;
                }
        }

        foreach ( $product->get_attributes() as $tka_attr ) {
		if ( ! $tka_attr instanceof WC_Product_Attribute || ! $tka_attr->get_visible() ) {
				continue;
			}
		if ( $tka_attr->is_taxonomy() ) {
				$tka_vals = wc_get_product_terms( $tka_id, $tka_attr->get_name(), array( 'fields' => 'names' ) );
			} else {
			$tka_vals = $tka_attr->get_options();
			}
		if ( empty( $tka_vals ) ) {
				continue;
			}
		$tka_specs[] = array(
		'k' => wc_attribute_label( $tka_attr->get_name() ),
		'v' => implode( '، ', (array) $tka_vals ),
		);
        }
        if ( $product->has_weight() ) {
		$tka_weight_v = wc_format_weight( $product->get_weight() );
		$tka_specs[] = array(
		'k' => __( 'وزن', 'toykindangel' ),
		'v' => function_exists( 'toykindangel_fa_num' ) ? toykindangel_fa_num( $tka_weight_v ) : $tka_weight_v,
		);
        }
        if ( $product->has_dimensions() ) {
		/* wc_format_dimensions «&times;» HTML-entity برمی‌گرداند؛ برای esc_html به نویسهٔ واقعی تبدیل می‌شود. */
		$tka_dims_v = str_replace( '&times;', '×', wc_format_dimensions( $product->get_dimensions( false ) ) );
		$tka_specs[] = array(
		'k' => __( 'ابعاد بسته‌بندی', 'toykindangel' ),
		'v' => function_exists( 'toykindangel_fa_num' ) ? toykindangel_fa_num( $tka_dims_v ) : $tka_dims_v,
		);
        }
        if ( $product->get_sku() ) {
		$tka_specs[] = array(
		'k' => __( 'کد محصول', 'toykindangel' ),
		'v' => $product->get_sku(),
		);
        }

        /* برچسب‌ها. */
        $tka_tag_terms = get_the_terms( $tka_id, 'product_tag' );
        if ( $tka_tag_terms && ! is_wp_error( $tka_tag_terms ) ) {
		$tka_specs[] = array(
		'k' => __( 'برچسب‌ها', 'toykindangel' ),
		'v' => implode( '، ', wp_list_pluck( $tka_tag_terms, 'name' ) ),
		);
        }
}

/* Related products rail (optional via Customizer). */
$tka_related = ( $product && get_theme_mod( 'tka_pdp_related', true ) ) ? wc_get_related_products( $tka_id, 10 ) : array();

/* Anchor tabs (18-f) — only for the sections that actually render. */
$tka_has_desc = (bool) trim( get_the_content() );
$tka_tabs = array();
if ( $tka_has_desc ) {
        $tka_tabs[] = array( 'id' => 'tka-desc', 't' => __( 'توضیحات', 'toykindangel' ) );
}
if ( $tka_specs ) {
        $tka_tabs[] = array( 'id' => 'tka-specs', 't' => __( 'مشخصات', 'toykindangel' ) );
}
$tka_tabs[] = array( 'id' => 'reviews', 't' => __( 'دیدگاه‌ها', 'toykindangel' ) );

?>
<main id="content" class="tka-pdp-main" role="main">

        <!-- ============ نوار بالای محصول ============ -->
        <div class="pdp__bar">
                <button type="button" class="icon-btn tka-back" aria-label="<?php esc_attr_e( 'بازگشت', 'toykindangel' ); ?>">
                        <svg class="ic" style="width:22px;height:22px" aria-hidden="true"><use href="#i-back"></use></svg>
                </button>
                <span class="t"><?php echo esc_html( $tka_name ); ?></span>
                <button type="button" class="icon-btn tka-share" data-share-url="<?php echo esc_url( get_permalink( $tka_id ) ); ?>" data-share-title="<?php echo esc_attr( $tka_name ); ?>" aria-label="<?php esc_attr_e( 'اشتراک‌گذاری', 'toykindangel' ); ?>">
                        <svg class="ic" style="width:20px;height:20px" aria-hidden="true"><use href="#i-share"></use></svg>
                </button>
                <button type="button" class="icon-btn" data-tka-drawer="minicart" aria-expanded="false" aria-controls="tka-minicart-drawer" aria-label="<?php esc_attr_e( 'سبد خرید', 'toykindangel' ); ?>">
                        <svg class="ic" style="width:20px;height:20px" aria-hidden="true"><use href="#i-cart"></use></svg>
                        <span class="badge-count num"><?php echo esc_html( $tka_cart_count ); ?></span>
                </button>
        </div>

        <?php
        /* اعلان‌های ووکامرس (کارت‌های main.css). */
        do_action( 'woocommerce_before_single_product' );
        ?>

        <?php if ( $tka_tabs ) : ?>
        <!-- ============ ناوبری بخش‌های صفحه (انکر — دسکتاپ) ============ -->
        <nav class="pdp__tabs" aria-label="<?php esc_attr_e( 'بخش‌های صفحهٔ محصول', 'toykindangel' ); ?>">
                <div class="pdp__tabs-in">
                        <?php foreach ( $tka_tabs as $tka_tab ) : ?>
                                <a href="#<?php echo esc_attr( $tka_tab['id'] ); ?>"><?php echo esc_html( $tka_tab['t'] ); ?></a>
                        <?php endforeach; ?>
                </div>
        </nav>
        <?php endif; ?>

        <?php if ( $product ) : ?>

        <div id="product-<?php the_ID(); ?>" <?php post_class(); ?>>

        <?php
        /* گالری — گالری/بج هسته غیرفعال شده؛ افزونه‌ها می‌توانند هوک کنند. */
        do_action( 'woocommerce_before_single_product_summary' );
        ?>

        <!-- TKA fix (18-f/QA): قاب دوستونهٔ گالری+بدنه. قبلاً گالریِ چسبان به‌خاطر
            display:contents والد، محدودیتش کل #content می‌شد و روی سکشن‌های
            توضیحات/مشخصات/دیدگاه‌ها سُر می‌خورد؛ حالا محدودیت چسبانش همین قاب است. -->
        <div class="tka-pdp-cols">

        <!-- ============ گالری تصاویر ============ -->
        <section class="gallery">
                <div class="gallery__track" id="galleryTrack">
                        <?php if ( $tka_gallery_ids ) : ?>
                                <?php foreach ( $tka_gallery_ids as $tka_gi => $tka_img_id ) : ?>
                                        <div class="gallery__slide">
                                                <img src="<?php echo esc_url( wp_get_attachment_image_url( $tka_img_id, 'large' ) ); ?>"
                                                        data-small="<?php echo esc_url( wp_get_attachment_image_url( $tka_img_id, 'medium' ) ); ?>"
                                                        alt="<?php echo esc_attr( sprintf( /* translators: %s: product name */ __( 'تصویر %s', 'toykindangel' ), $tka_name ) ); ?>"
                                                        <?php echo 0 === $tka_gi ? '' : 'loading="lazy"'; ?>
                                                        referrerpolicy="no-referrer"
                                                        onerror="if(!this.dataset.f){this.dataset.f=1;this.src=this.dataset.small}else{this.remove()}">
                                                <span class="ph"><svg class="ic" aria-hidden="true"><use href="#i-cat-camera"></use></svg></span>
                                                <?php if ( 0 === $tka_gi && $tka_off > 0 ) : ?>
                                                        <span class="gallery__off num"><?php echo esc_html( (string) $tka_off ); ?>%</span>
                                                <?php endif; ?>
                                        </div>
                                <?php endforeach; ?>
                        <?php else : ?>
                                <div class="gallery__slide">
                                        <span class="ph"><svg class="ic" aria-hidden="true"><use href="#i-cat-camera"></use></svg></span>
                                </div>
                        <?php endif; ?>
                </div>
                <div class="gallery__thumbs" id="galleryThumbs"></div>
        </section>

        <!-- ============ بدنه ============ -->
        <div class="pdp__body">

                <?php if ( $tka_bc_terms ) : ?>
                <nav class="breadcrumb" aria-label="<?php esc_attr_e( 'مسیر صفحه', 'toykindangel' ); ?>">
                        <?php foreach ( $tka_bc_terms as $tka_bi => $tka_bt ) : ?>
                                <?php if ( count( $tka_bc_terms ) - 1 === $tka_bi ) : ?>
                                        <span><?php echo esc_html( $tka_bt->name ); ?></span>
                                <?php else : ?>
                                        <a href="<?php echo esc_url( get_term_link( $tka_bt ) ); ?>"><?php echo esc_html( $tka_bt->name ); ?></a><span>/</span>
                                <?php endif; ?>
                        <?php endforeach; ?>
                </nav>
                <?php endif; ?>

                <h1 class="pdp__title"><?php echo esc_html( $tka_name ); ?></h1>

                <div class="rating">
                        <?php if ( $tka_rcount > 0 ) : ?>
                                <span class="stars" aria-hidden="true">
                                        <?php for ( $tka_s = 1; $tka_s <= 5; $tka_s++ ) : ?>
                                                <svg class="ic <?php echo $tka_s <= $tka_rfilled ? 'ic-f' : ''; ?>" style="<?php echo $tka_s <= $tka_rfilled ? '' : 'color:#dcdce2'; ?>width:15px;height:15px" aria-hidden="true"><use href="#i-star"></use></svg>
                                        <?php endfor; ?>
                                </span>
                                <b class="num"><?php echo esc_html( number_format_i18n( $tka_rating, 1 ) ); ?></b>
                                <span class="num">(<?php echo esc_html( (string) $tka_rcount ); ?> <?php esc_html_e( 'امتیاز', 'toykindangel' ); ?>)</span>
                                <span>·</span>
                                <a class="tka-reviews-link num" href="#reviews"><?php echo esc_html( (string) $tka_rcount ); ?> <?php esc_html_e( 'نظر', 'toykindangel' ); ?></a>
                        <?php else : ?>
                                <a class="tka-reviews-link" href="#reviews"><?php esc_html_e( 'اولین دیدگاه را شما ثبت کنید', 'toykindangel' ); ?></a>
                        <?php endif; ?>
                </div>

                <?php if ( $tka_short ) : ?>
                <!-- خلاصهٔ محصول + چیپ‌های ویژگی (18-f) -->
                <div class="pdp__summary"><?php echo wp_kses_post( wpautop( $tka_short ) ); ?></div>
                <ul class="pdp__chips">
                        <li><svg class="ic" aria-hidden="true"><use href="#i-shield"></use></svg><?php echo esc_html( $tka_warranty ); ?></li>
                        <li><svg class="ic" aria-hidden="true"><use href="#i-truck"></use></svg><?php esc_html_e( 'ارسال در کوتاه‌ترین زمان', 'toykindangel' ); ?></li>
                        <li><svg class="ic" aria-hidden="true"><use href="#i-return"></use></svg><?php esc_html_e( '۷ روز ضمانت بازگشت', 'toykindangel' ); ?></li>
                </ul>
                <?php endif; ?>

                <!-- گارانتی و موجودی (فروشگاه تک‌فروشنده — بدون بخش فروشنده) -->
                <div class="sellerbox">
                        <?php if ( $tka_warranty ) : ?>
                        <div class="sellerbox__row">
                                <svg class="ic" aria-hidden="true"><use href="#i-shield"></use></svg>
                                <span><?php echo esc_html( $tka_warranty ); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="sellerbox__row <?php echo $tka_stock_row['ok'] ? 'ok' : 'tka-out'; ?>">
                                <svg class="ic" aria-hidden="true"><use href="#i-truck"></use></svg>
                                <span><?php echo esc_html( $tka_stock_row['text'] ); ?></span>
                        </div>
                </div>

                <!-- روش‌های ارسال -->
                <div class="shipmethods">
                        <?php foreach ( $tka_ships as $tka_ship ) : ?>
                                <?php
                                if ( ! $tka_ship['t'] && ! $tka_ship['n'] ) {
continue; }
?>
                                <div class="shipmethod">
                                        <svg class="ic" aria-hidden="true"><use href="#<?php echo esc_attr( $tka_ship['icon'] ); ?>"></use></svg>
                                        <div><b><?php echo esc_html( $tka_ship['t'] ); ?></b><small><?php echo esc_html( $tka_ship['n'] ); ?></small></div>
                                </div>
                        <?php endforeach; ?>
                </div>

                <!-- قیمت -->
                <div class="pricebox">
                        <?php if ( $tka_off > 0 && $tka_regular > 0 ) : ?>
                        <div class="pricebox__row">
                                <span class="pricebox__off num"><?php echo esc_html( (string) $tka_off ); ?>%</span>
                                <span class="pricebox__old num"><?php echo esc_html( toykindangel_fmt( $tka_regular ) ); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if ( $tka_is_simple || $product->is_type( 'external' ) || $product->is_type( 'grouped' ) ) : ?>
                        <div class="pricebox__now"><b class="num"><?php echo esc_html( toykindangel_fmt( $tka_price > 0 ? $tka_price : 0 ) ); ?></b><small><?php esc_html_e( 'تومان', 'toykindangel' ); ?></small></div>
                        <?php else : ?>
                        <div class="pricebox__now tka-price-html"><?php echo wp_kses_post( $product->get_price_html() ); ?></div>
                        <?php endif; ?>

                        <?php if ( $tka_is_simple ) : ?>
                                <?php if ( $tka_purchasable ) : ?>
                                        <?php
                                        /* خروجی واقعی ووکامرس: ورودی تعداد + دکمه (priority 30 از هوک استاندارد). */
                                        do_action( 'woocommerce_single_product_summary' );
                                        ?>
                                <?php else : ?>
                                <button type="button" class="btn-primary tka-atc" disabled>
                                        <svg class="ic" style="width:20px;height:20px" aria-hidden="true"><use href="#i-cart"></use></svg><?php esc_html_e( 'افزودن به سبد خرید', 'toykindangel' ); ?>
                                </button>
                                <?php endif; ?>
                        <?php elseif ( $product->is_type( 'variable' ) ) : ?>
                                <div class="tka-varform"><?php do_action( 'woocommerce_single_product_summary' ); ?></div>
                        <?php endif; ?>

                        <?php if ( $tka_show_low ) : ?>
                        <div class="stock">
                                <svg class="ic" style="width:16px;height:16px" aria-hidden="true"><use href="#i-clock"></use></svg>
                                <?php
                                printf(
                                        /* translators: %s: remaining stock quantity */
                                        esc_html__( 'تنها %s عدد باقی‌مانده در انبار', 'toykindangel' ),
                                        '<span class="num">' . esc_html( (string) $tka_stock_qty ) . '</span>'
                                );
                                ?>
                        </div>
                        <?php endif; ?>

                        <div class="actions">
                                <button type="button" class="btn-ghost tka-fav" data-fav-id="<?php echo esc_attr( (string) $tka_id ); ?>">
                                        <svg class="ic" style="width:18px;height:18px" aria-hidden="true"><use href="#i-heart"></use></svg><?php esc_html_e( 'مورد علاقه', 'toykindangel' ); ?>
                                </button>
                                <button type="button" class="btn-ghost tka-share" data-share-url="<?php echo esc_url( get_permalink( $tka_id ) ); ?>" data-share-title="<?php echo esc_attr( $tka_name ); ?>">
                                        <svg class="ic" style="width:18px;height:18px" aria-hidden="true"><use href="#i-share"></use></svg><?php esc_html_e( 'اشتراک گذاری', 'toykindangel' ); ?>
                                </button>
                        </div>
                </div>

                <!-- اشتراک‌گذاری محصول (18-f) -->
                <div class="pdp-share">
                        <span class="pdp-share__t"><?php esc_html_e( 'اشتراک‌گذاری:', 'toykindangel' ); ?></span>
                        <a class="pdp-share__btn pdp-share__btn--wa" href="<?php echo esc_url( $tka_wa ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php esc_attr_e( 'اشتراک‌گذاری در واتساپ', 'toykindangel' ); ?>" title="<?php esc_attr_e( 'واتساپ', 'toykindangel' ); ?>">
                                <svg viewBox="0 0 448 512" aria-hidden="true"><path fill="currentColor" d="M380.9 97.1C339 55.1 283.2 32 223.9 32c-122.4 0-222 99.6-222 222 0 39.1 10.2 77.3 29.6 111L0 480l117.7-30.9c32.4 17.7 68.9 27 106.1 27h.1c122.3 0 224.1-99.6 224.1-222 0-59.3-25.2-115-67.1-157zm-157 341.6c-33.2 0-65.7-8.9-94-25.7l-6.7-4-69.8 18.3L72 359.2l-4.4-7c-18.5-29.4-28.2-63.3-28.2-98.2 0-101.7 82.8-184.5 184.6-184.5 49.3 0 95.6 19.2 130.4 54.1 34.8 34.9 56.2 81.2 56.1 130.5 0 101.8-84.9 184.6-186.6 184.6zm101.2-138.2c-5.5-2.8-32.8-16.2-37.9-18-5.1-1.9-8.8-2.8-12.5 2.8-3.7 5.6-14.3 18-17.6 21.8-3.2 3.7-6.5 4.2-12 1.4-32.6-16.3-54-29.1-75.5-66-5.7-9.8 5.7-9.1 16.3-30.3 1.8-3.7.9-6.9-.5-9.7-1.4-2.8-12.5-30.1-17.1-41.2-4.5-10.8-9.1-9.3-12.5-9.5-3.2-.2-6.9-.2-10.6-.2-3.7 0-9.7 1.4-14.8 6.9-5.1 5.6-19.4 19-19.4 46.3 0 27.3 19.9 53.7 22.6 57.4 2.8 3.7 39.1 59.7 94.8 83.8 35.2 15.2 49 16.5 66.6 13.9 10.7-1.6 32.8-13.4 37.4-26.4 4.6-13 4.6-24.1 3.2-26.4-1.3-2.5-5-3.9-10.5-6.6z"/></svg>
                        </a>
                        <a class="pdp-share__btn pdp-share__btn--tg" href="<?php echo esc_url( $tka_tg ); ?>" target="_blank" rel="noopener nofollow" aria-label="<?php esc_attr_e( 'اشتراک‌گذاری در تلگرام', 'toykindangel' ); ?>" title="<?php esc_attr_e( 'تلگرام', 'toykindangel' ); ?>">
                                <svg viewBox="0 0 496 512" aria-hidden="true"><path fill="currentColor" d="M248 8C111 8 0 119 0 256s111 248 248 248 248-111 248-248S385 8 248 8zm114.6 171.3l-39 183.9c-2.9 13-10.6 16.2-21.5 10.1l-59.4-43.8-28.7 27.6c-3.2 3.2-5.8 5.8-11.9 5.8l4.2-60.5 109.7-99.1c4.8-4.2-1-6.6-7.3-2.4L174.3 281.3l-58.6-18.3c-12.7-4-12.9-12.7 2.7-18.8l228.9-88.3c10.6-3.8 19.9 2.6 15.3 17.4z"/></svg>
                        </a>
                        <button type="button" class="pdp-share__btn pdp-share__btn--copy tka-share" data-share-url="<?php echo esc_url( $tka_link ); ?>" data-share-title="<?php echo esc_attr( $tka_name ); ?>" aria-label="<?php esc_attr_e( 'کپی لینک محصول', 'toykindangel' ); ?>" title="<?php esc_attr_e( 'کپی لینک', 'toykindangel' ); ?>">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"/><path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/></svg>
                        </button>
                </div>

                <!-- مزایا -->
                <div class="usps" style="margin-top:20px">
                        <div class="usp">
                                <svg class="ic" style="width:24px;height:24px" aria-hidden="true"><use href="#i-return"></use></svg>
                                <b><?php esc_html_e( 'بازگشت کالا تا ۷ روز', 'toykindangel' ); ?></b>
                        </div>
                        <div class="usp">
                                <svg class="ic" style="width:24px;height:24px" aria-hidden="true"><use href="#i-headset"></use></svg>
                                <b><?php esc_html_e( 'پشتیبانی ۷ روز هفته', 'toykindangel' ); ?></b>
                        </div>
                        <div class="usp">
                                <svg class="ic" style="width:24px;height:24px" aria-hidden="true"><use href="#i-truck"></use></svg>
                                <b><?php esc_html_e( 'ارسال در کوتاه‌ترین زمان', 'toykindangel' ); ?></b>
                        </div>
                </div>

                <div style="height:18px"></div>
        </div><!-- /.pdp__body -->

        <?php
        /* تب‌ها/مشابه‌های هسته غیرفعال‌اند (قالب خودش رندر می‌کند)؛ افزونه‌ها می‌توانند هوک کنند. */
        do_action( 'woocommerce_after_single_product_summary' );
        ?>

        </div><!-- /.tka-pdp-cols -->

        <?php if ( $tka_has_desc ) : ?>
        <!-- ============ توضیحات محصول (18-f) ============ -->
        <section id="tka-desc" class="pdp-sec pdp-sec--desc" aria-labelledby="tka-desc-title">
                <div class="pdp-sec__card">
                        <div class="pdp-sec__head">
                                <h2 class="pdp-sec__title" id="tka-desc-title">
                                        <svg class="ic" aria-hidden="true"><use href="#i-box"></use></svg>
                                        <?php esc_html_e( 'توضیحات محصول', 'toykindangel' ); ?>
                                </h2>
                        </div>
                        <div class="desc tka-entry"><?php the_content(); ?></div>
                </div>
        </section>
        <?php endif; ?>

        <?php if ( $tka_specs ) : ?>
        <!-- ============ مشخصات محصول — جدول کلید/مقدار از دیتای واقعی (18-f) ============ -->
        <section id="tka-specs" class="pdp-sec pdp-sec--specs" aria-labelledby="tka-specs-title">
                <div class="pdp-sec__card">
                        <div class="pdp-sec__head">
                                <h2 class="pdp-sec__title" id="tka-specs-title">
                                        <svg class="ic" aria-hidden="true"><use href="#i-info"></use></svg>
                                        <?php esc_html_e( 'مشخصات محصول', 'toykindangel' ); ?>
                                </h2>
                        </div>
                        <table class="pdp-specs">
                                <tbody>
                                        <?php foreach ( $tka_specs as $tka_spec ) : ?>
                                        <tr>
                                                <th scope="row"><?php echo esc_html( $tka_spec['k'] ); ?></th>
                                                <td><?php echo esc_html( $tka_spec['v'] ); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                </tbody>
                        </table>
                </div>
        </section>
        <?php endif; ?>

        <!-- ============ دیدگاه‌ها (18-f: سکشن کارت‌شده با شمارنده) ============ -->
        <section id="reviews" class="pdp-sec pdp-sec--reviews" aria-labelledby="tka-reviews-title">
                <div class="pdp-sec__card">
                        <div class="pdp-sec__head">
                                <h2 class="pdp-sec__title" id="tka-reviews-title">
                                        <svg class="ic" aria-hidden="true"><use href="#i-star"></use></svg>
                                        <?php esc_html_e( 'دیدگاه‌ها', 'toykindangel' ); ?>
                                </h2>
                                <?php if ( $tka_reviews_n > 0 ) : ?>
                                <?php /* translators: %s: number of reviews */ ?>
                                <span class="pdp-sec__count num"><?php echo esc_html( sprintf( _n( '%s دیدگاه', '%s دیدگاه', $tka_reviews_n, 'toykindangel' ), number_format_i18n( $tka_reviews_n ) ) ); ?></span>
                                <?php else : ?>
                                <span class="pdp-sec__count pdp-sec__count--empty"><?php esc_html_e( 'اولین دیدگاه را شما ثبت کنید', 'toykindangel' ); ?></span>
                                <?php endif; ?>
                        </div>
                        <div class="tka-reviews">
                                <?php comments_template(); ?>
                        </div>
                </div>
        </section>

        <?php if ( $tka_related ) : ?>
        <!-- محصولات مشابه — بیرون از pdp__body تا در دسکتاپ تمام‌عرض شود (v0.7.0) -->
        <section class="section tka-related">
                <div class="section__head">
                        <span class="section__title">
                                <svg class="ic" aria-hidden="true"><use href="#i-cat-stars"></use></svg>
                                <?php esc_html_e( 'محصولات مشابه', 'toykindangel' ); ?>
                        </span>
                </div>
                <div class="plist__track">
                        <?php
                        foreach ( $tka_related as $tka_rel_id ) {
                                $tka_rel = wc_get_product( $tka_rel_id );
                                if ( $tka_rel ) {
								// phpcs:ignore WordPress.Security.EscapeOutput -- theme-generated markup contains inline SVG (wp_kses_post would strip it).
								echo toykindangel_pcard_wrap( $tka_rel );
                                }
                        }
                        ?>
                </div>
        </section>
        <?php endif; ?>

        <!-- ============ نوار چسبان خرید ============ -->
        <div class="pdp__cta">
                <div class="p">
                        <b class="num"><?php echo esc_html( toykindangel_fmt( $tka_price > 0 ? $tka_price : 0 ) ); ?></b>
                        <?php if ( $tka_off > 0 && $tka_regular > 0 ) : ?>
                        <small class="num"><?php echo esc_html( toykindangel_fmt( $tka_regular ) ); ?></small>
                        <?php endif; ?>
                </div>
                <?php if ( $tka_is_simple && $tka_purchasable ) : ?>
                <a href="<?php echo esc_url( $product->add_to_cart_url() ); ?>"
                        data-quantity="1"
                        data-product_id="<?php echo esc_attr( (string) $tka_id ); ?>"
                        data-product_sku="<?php echo esc_attr( $product->get_sku() ); ?>"
                        rel="nofollow"
                        class="btn-primary tka-atc add_to_cart_button ajax_add_to_cart">
                        <svg class="ic" style="width:20px;height:20px" aria-hidden="true"><use href="#i-cart"></use></svg>
                        <?php esc_html_e( 'افزودن به سبد خرید', 'toykindangel' ); ?>
                </a>
                <?php else : ?>
                <a class="btn-primary tka-cta-alt" href="#pricebox-top"><?php esc_html_e( 'مشاهده و خرید', 'toykindangel' ); ?></a>
                <?php endif; ?>
        </div>

        </div><!-- /#product-<?php the_ID(); ?> -->
        <?php do_action( 'woocommerce_after_single_product' ); ?>

        <?php else : ?>

        <div class="pdp__body">
                <h1 class="pdp__title"><?php esc_html_e( 'محصول یافت نشد', 'toykindangel' ); ?></h1>
                <div class="desc"><p><?php esc_html_e( 'این محصول در دسترس نیست.', 'toykindangel' ); ?></p></div>
        </div>

        <?php endif; ?>

</main>
<?php

get_footer( 'bare' );
