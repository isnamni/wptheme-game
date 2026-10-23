<?php
/**
 * One-click demo importer (Step: درج محتوای نمونه).
 *
 * Reproduces the original demo exactly — menus, product categories tree,
 * brands, products (with local bundled images, no hotlinks to the original
 * site), pages, banners and Customizer banner settings — on any WordPress
 * + WooCommerce install.
 *
 * v0.15.0 adds the settings-only import: required pages + necessary
 * settings, WITHOUT demo products/categories/brands/menus — for store
 * owners who build their own catalogue.
 *
 * v0.16.0 (Task 18-e) upgrades the demo catalogue to exercise the full
 * standard WooCommerce product capabilities: global attributes pa_color
 * (رنگ) + pa_size (سایز) with Persian terms, variable products with
 * real variations (price/stock/SKU per variation), scheduled sale
 * prices (_sale_price_dates_from/to), the «جشنواره» campaign meta
 * (_tka_festival), product tags, SKUs, weights/dimensions, stock
 * management and sold-individually — both through the one-click
 * importer and the WXR export.
 *
 * Manual route: Appearance → «درج محتوای نمونه فرشته مهربون»
 * CLI route:    wp eval "toykindangel_demo_import();"
 *               wp eval "toykindangel_demo_settings_import();"
 *
 * The importer is idempotent: existing slugs/terms are reused, so re-running
 * never duplicates content.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'toykindangel_demo_att_meta' ) ) {
        /**
         * Build attachment metadata manually (no GD dependency): the theme bundles
         * the true image dimensions (images.json) and every generated size points
         * to the original optimized file. This keeps image URLs working on hosts
         * where PHP GD lacks JPEG support (e.g. static PHP builds).
         *
         * @param string $file Absolute path of the uploaded attachment file.
         * @return array Attachment metadata.
         */
        function toykindangel_demo_att_meta( $file ) {
		$up   = wp_get_upload_dir();
		$rel  = ltrim( str_replace( trailingslashit( $up['basedir'] ), '', $file ), '/' );
		$dims = array();
		$map  = TOYKINDANGEL_DIR . '/assets/demo/images.json';
		if ( is_readable( $map ) ) {
				$dims = (array) json_decode( (string) file_get_contents( $map ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local bundled file, not a remote URL
			}

		$tka_w = 1024;
		$tka_h = 1024;
		foreach ( $dims as $tka_src => $tka_d ) {
				if ( basename( (string) $tka_src ) === basename( $file ) ) {
				$tka_w = max( 1, (int) ( $tka_d['w'] ?? 1024 ) );
				$tka_h = max( 1, (int) ( $tka_d['h'] ?? 1024 ) );
				break;
				}
			}

		$tka_sizes = array(
		'thumbnail'             => 150,
		'medium'                => 300,
		'medium_large'          => 768,
		'large'                 => 1024,
		'woocommerce_thumbnail' => 800,
		'woocommerce_single'    => 600,
		'shop_catalog'          => 300,
		'shop_single'           => 600,
		'shop_thumbnail'        => 100,
		);
		$sizes = array();
		foreach ( $tka_sizes as $tka_sname => $tka_max ) {
				/* همه سایزها همیشه ثبت می‌شوند و به فایل اصلی اشاره دارند؛
				 * حذف سایز باعث خالی‌بودن wp_get_attachment_image_url می‌شود. */
				$tka_scale = min( 1, $tka_max / max( $tka_w, $tka_h ) );
				$sizes[ $tka_sname ] = array(
						'file'      => basename( $file ),
						'width'     => max( 1, (int) round( $tka_w * $tka_scale ) ),
						'height'    => max( 1, (int) round( $tka_h * $tka_scale ) ),
						'mime-type' => 'image/jpeg',
				);
                }

		return array(
		'width'      => $tka_w,
		'height'     => $tka_h,
		'file'       => $rel,
		'sizes'      => $sizes,
		'image_meta' => array(),
		);
        }
}

if ( ! function_exists( 'toykindangel_demo_sideload' ) ) {
        /**
         * Import a bundled theme file into the media library (once).
         *
         * @param string $file  Absolute file path.
         * @param string $title Attachment title.
         * @return int Attachment ID (0 on failure).
         */
        function toykindangel_demo_sideload( $file, $title = '' ) {
		if ( ! is_readable( $file ) ) {
				return 0;
			}

		$map = get_option( 'tka_demo_attmap', array() );
		$key = md5( $file . '|' . (string) filesize( $file ) );
		if ( isset( $map[ $key ] ) && get_post( (int) $map[ $key ] ) ) {
				return (int) $map[ $key ];
			}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
				require_once ABSPATH . 'wp-admin/includes/image.php';
			}

		$bits  = wp_check_filetype( basename( $file ) );
		$usb   = wp_upload_bits( basename( $file ), null, (string) file_get_contents( $file ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- reading the local bundled asset to sideload
		if ( ! empty( $usb['error'] ) ) {
				return 0;
			}

		$att_id = wp_insert_attachment(
		array(
		'post_mime_type' => ( $bits['type'] ? $bits['type'] : 'image/jpeg' ),
		'post_title'     => ( $title ? $title : preg_replace( '/\.[^.]+$/', '', basename( $file ) ) ),
		'post_status'    => 'inherit',
		),
		$usb['file']
	);
	if ( is_wp_error( $att_id ) || ! $att_id ) {
		return 0;
		}

	/* متادیتای دستی — مستقل از GD (ببین toykindangel_demo_att_meta) */
	wp_update_attachment_metadata( $att_id, toykindangel_demo_att_meta( $usb['file'] ) );

	$map[ $key ] = (int) $att_id;
	update_option( 'tka_demo_attmap', $map, false );

	return (int) $att_id;
        }
}

if ( ! function_exists( 'toykindangel_demo_term' ) ) {
        /**
         * Find-or-create a product_cat term by name.
         *
         * @param string $name   Term name.
         * @param int    $parent_id Parent term id (0 = top level).
         * @param string $slug   Optional explicit slug.
         * @return int Term id.
         */
        function toykindangel_demo_term( $name, $parent_id = 0, $slug = '' ) {
		$tka_existing = get_term_by( 'name', $name, 'product_cat' );
		if ( $tka_existing && ! is_wp_error( $tka_existing ) ) {
				return (int) $tka_existing->term_id;
			}

		$tka_args = array( 'parent' => (int) $parent_id );
		if ( $slug ) {
				$tka_args['slug'] = $slug;
			}

		$tka_res = wp_insert_term( $name, 'product_cat', $tka_args );
		if ( is_wp_error( $tka_res ) ) {
				$tka_existing = get_term_by( 'name', $name, 'product_cat' );
				return ( $tka_existing && ! is_wp_error( $tka_existing ) ) ? (int) $tka_existing->term_id : 0;
			}

		return (int) $tka_res['term_id'];
        }
}

/* -------------------------------------------------------------------------
 * Commerce enrichment (Task 18-e): رنگ/سایز/تخفیف/جشنواره/برچسب/SKU
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'toykindangel_demo_commerce_data' ) ) {
        /**
         * Commerce upgrade spec — keyed by demo product slug.
         *
         * Kept here (not in inc/demo/data.php) so the base dataset stays
         * 1:1 with the original demo while this layer adds the standard
         * WooCommerce capabilities on top of it.
         *
         * @return array
         */
        function toykindangel_demo_commerce_data() {
		return array(
		/* ویژگی‌های سراسری: رنگ + سایز (پارسیان) */
		'attributes' => array(
		'color' => array(
		'label' => 'رنگ',
		'terms' => array(
		'قرمز'  => 'red',
		'آبی'   => 'blue',
		'سبز'   => 'green',
		'زرد'   => 'yellow',
		'صورتی' => 'pink',
		),
		),
		'size'  => array(
		'label' => 'سایز',
		'terms' => array(
		'سایز کوچک' => 'size-s',
		'سایز متوسط' => 'size-m',
		'سایز بزرگ' => 'size-l',
		'۱۰۰×۶۰'    => 'size-100x60',
		'۱۲۰×۸۰'    => 'size-120x80',
		'۱۴۰×۹۰'    => 'size-140x90',
		),
		),
		),
		'products'   => array(

		/* ---------- شگفت‌انگیز (تخفیف فعال + تقویم حراج) ---------- */
		'plush-fruit-piano-trio-box'       => array(
		'sku'         => 'TKA-1001',
		'festival'    => true,
		'sale_dates'  => true,
		'tags'        => array( 'پولیشی', 'هدیه' ),
		'stock'       => array( 'qty' => 12, 'low' => 4 ),
		'weight'      => '0.9',
		),
		'plush-fruit-piano-carrot-box'     => array( 'sku' => 'TKA-1002', 'sale_dates' => true, 'tags' => array( 'پولیشی' ) ),
		'plush-fruit-piano-strawberry-box' => array( 'sku' => 'TKA-1003', 'sale_dates' => true, 'tags' => array( 'پولیشی' ) ),
		'plush-fruit-piano-banana-box'     => array( 'sku' => 'TKA-1004', 'sale_dates' => true, 'tags' => array( 'پولیشی' ) ),

		'plush-sofa-bed-minion'            => array(
		'sku'        => 'TKA-1005',
		'festival'   => true,
		'type'       => 'variable',
		'tags'       => array( 'پولیشی', 'شخصیت کارتونی' ),
		'weight'     => '1.3',
		'dims'       => array( '100', '60', '35' ),
		'attrs'      => array(
		'color' => array( 'قرمز' ),
		'size'  => array( '۱۰۰×۶۰', '۱۲۰×۸۰' ),
		),
		'variations' => array(
		array( 'color' => 'قرمز', 'size' => '۱۰۰×۶۰', 'regular' => 3880000, 'sale' => 3090000, 'stock' => 6 ),
		array( 'color' => 'قرمز', 'size' => '۱۲۰×۸۰', 'regular' => 4650000, 'sale' => 3720000, 'stock' => 4 ),
		),
		),

		'plush-sofa-bed-husky'             => array(
		'sku'     => 'TKA-1006',
		'sale_dates' => true,
		'tags'    => array( 'پولیشی' ),
		'weight'  => '1.6',
		'dims'    => array( '100', '60', '35' ),
		'stock'   => array( 'qty' => 6, 'low' => 3 ),
		),

		'silicone-musical-doll-grey'       => array( 'sku' => 'TKA-1007', 'sale_dates' => true, 'tags' => array( 'عروسک' ) ),

		'joy-toys-air-hockey'              => array(
		'sku'               => 'TKA-1008',
		'festival'          => true,
		'sale_dates'        => true,
		'tags'              => array( 'ورزشی', 'بازی فکری' ),
		'weight'            => '12',
		'dims'              => array( '180', '90', '12' ),
		'stock'             => array( 'qty' => 3, 'low' => 2 ),
		'sold_individually' => true,
		),

		/* ---------- جدیدترین‌ها ---------- */
		'plush-sofa-bed-capybara'          => array(
		'sku'        => 'TKA-1009',
		'type'       => 'variable',
		'tags'       => array( 'پولیشی', 'جدید' ),
		'weight'     => '1.4',
		'dims'       => array( '100', '60', '30' ),
		'attrs'      => array(
		'color' => array( 'قرمز', 'آبی', 'سبز' ),
		'size'  => array( '۱۰۰×۶۰', '۱۲۰×۸۰', '۱۴۰×۹۰' ),
		),
		'variations' => array(
		array( 'color' => 'قرمز', 'size' => '۱۰۰×۶۰', 'regular' => 3880000, 'sale' => 3190000, 'stock' => 5 ),
		array( 'color' => 'قرمز', 'size' => '۱۲۰×۸۰', 'regular' => 4650000, 'sale' => 3890000, 'stock' => 3 ),
		array( 'color' => 'قرمز', 'size' => '۱۴۰×۹۰', 'regular' => 5480000, 'stock' => 2 ),
		array( 'color' => 'آبی', 'size' => '۱۰۰×۶۰', 'regular' => 3880000, 'sale' => 3390000, 'stock' => 7 ),
		array( 'color' => 'آبی', 'size' => '۱۲۰×۸۰', 'regular' => 4650000, 'stock' => 6 ),
		array( 'color' => 'آبی', 'size' => '۱۴۰×۹۰', 'regular' => 5480000, 'stock' => 4 ),
		array( 'color' => 'سبز', 'size' => '۱۰۰×۶۰', 'regular' => 3880000, 'stock' => 9 ),
		array( 'color' => 'سبز', 'size' => '۱۲۰×۸۰', 'regular' => 4650000, 'stock' => 5 ),
		array( 'color' => 'سبز', 'size' => '۱۴۰×۹۰', 'regular' => 5480000, 'stock' => 3 ),
		),
		),

		'plush-sofa-bed-green-clown'       => array(
		'sku'        => 'TKA-1010',
		'festival'   => true,
		'tags'       => array( 'پولیشی' ),
		/* حراج تازه: بدون تخفیف در دیتاست اصلی → اینجا فعال می‌شود */
		'sale'       => array( 'regular' => 3880000, 'sale' => 2990000 ),
		'stock'      => array( 'qty' => 5, 'low' => 2 ),
		),

		'plush-sofa-bed-red-monster'       => array( 'sku' => 'TKA-1011', 'tags' => array( 'پولیشی', 'شخصیت کارتونی' ) ),
		'plush-sofa-bed-green-yellow-dinosaur' => array( 'sku' => 'TKA-1012', 'tags' => array( 'پولیشی', 'شخصیت کارتونی' ) ),
		'plush-sofa-bed-purple-rabbit'     => array( 'sku' => 'TKA-1013', 'tags' => array( 'پولیشی', 'جدید' ) ),
		'plush-sofa-bed-green-monster'     => array( 'sku' => 'TKA-1014', 'tags' => array( 'پولیشی' ) ),

		'wall-boxing-set-7792a'            => array(
		'sku'        => 'TKA-1015',
		'type'       => 'variable',
		'tags'       => array( 'ورزشی' ),
		'weight'     => '4.8',
		'attrs'      => array(
		'size' => array( 'سایز کوچک', 'سایز بزرگ' ),
		),
		'variations' => array(
		array( 'size' => 'سایز کوچک', 'regular' => 8800000, 'sale' => 7090000, 'stock' => 8 ),
		array( 'size' => 'سایز بزرگ', 'regular' => 11400000, 'stock' => 3 ),
		),
		),

		'led-racing-track-a51-9a'          => array( 'sku' => 'TKA-1016', 'tags' => array( 'ماشین و ریسینگ' ), 'weight' => '7.2', 'dims' => array( '600', '60', '28' ) ),

		'teppanyaki-grill-toy-889-285'     => array(
		'sku'        => 'TKA-1017',
		'festival'   => true,
		'type'       => 'variable',
		'tags'       => array( 'ست بازی', 'آشپزخانه' ),
		'weight'     => '9.5',
		'dims'       => array( '80', '40', '30' ),
		'attrs'      => array(
		'color' => array( 'صورتی', 'آبی' ),
		),
		'variations' => array(
		array( 'color' => 'صورتی', 'regular' => 18800000, 'sale' => 16900000, 'stock' => 2 ),
		array( 'color' => 'آبی', 'regular' => 18800000, 'stock' => 5 ),
		),
		),

		'wooden-air-hockey-545l'           => array( 'sku' => 'TKA-1018', 'tags' => array( 'ورزشی' ), 'weight' => '11', 'stock' => array( 'qty' => 7 ) ),
		'racing-track-560-a51-3a'          => array(
		'sku'     => 'TKA-1019',
		'festival' => true,
		'tags'    => array( 'ماشین و ریسینگ' ),
		'weight'  => '6.5',
		'dims'    => array( '560', '60', '30' ),
		'stock'   => array( 'qty' => 10 ),
		),
		'racing-track-520-a64-12a'         => array( 'sku' => 'TKA-1020', 'tags' => array( 'ماشین و ریسینگ' ) ),

		'silicone-musical-doll-yellow'     => array(
		'sku'        => 'TKA-1021',
		'type'       => 'variable',
		'tags'       => array( 'عروسک', 'هدیه' ),
		'weight'     => '0.8',
		'attrs'      => array(
		'color' => array( 'زرد', 'صورتی' ),
		'size'  => array( 'سایز کوچک', 'سایز بزرگ' ),
		),
		'variations' => array(
		array( 'color' => 'زرد', 'size' => 'سایز کوچک', 'regular' => 7700000, 'sale' => 6490000, 'stock' => 4 ),
		array( 'color' => 'زرد', 'size' => 'سایز بزرگ', 'regular' => 8900000, 'stock' => 2 ),
		array( 'color' => 'صورتی', 'size' => 'سایز کوچک', 'regular' => 7700000, 'stock' => 5 ),
		array( 'color' => 'صورتی', 'size' => 'سایز بزرگ', 'regular' => 8900000, 'sale' => 7490000, 'stock' => 3 ),
		),
		),
		),
		);
        }
}

if ( ! function_exists( 'toykindangel_demo_sale_window' ) ) {
        /**
         * Active sale window (تقویم حراج جشنواره) — relative to "now" so a
         * freshly imported shop always has a live schedule.
         *
         * @return int[] { from, to } Unix timestamps.
         */
        function toykindangel_demo_sale_window() {
		return array(
		'from' => time() - 2 * DAY_IN_SECONDS,
		'to'   => time() + 25 * DAY_IN_SECONDS,
		);
        }
}

if ( ! function_exists( 'toykindangel_demo_attributes_import' ) ) {
        /**
         * Create the global attributes pa_color/pa_size + their Persian terms.
         * Idempotent: attributes/terms are only created when missing.
         *
         * @param array $out Stats accumulator (attributes/terms counters).
         * @return array attr_key => array( term_name => term_slug )
         */
        function toykindangel_demo_attributes_import( &$out ) {
		$tka_data     = toykindangel_demo_commerce_data();
		$tka_attr_map = array();

		if ( ! function_exists( 'wc_create_attribute' ) ) {
				return $tka_attr_map;
			}

		foreach ( $tka_data['attributes'] as $tka_key => $tka_attr ) {
				$tka_exists = false;
				foreach ( (array) wc_get_attribute_taxonomies() as $tka_a ) {
				if ( isset( $tka_a->attribute_name ) && $tka_key === $tka_a->attribute_name ) {
					$tka_exists = true;
					break;
					}
					}

				if ( ! $tka_exists ) {
				$tka_new = wc_create_attribute(
				array(
				'name'         => $tka_attr['label'],
				'slug'         => $tka_key,
				'type'         => 'select',
				'order_by'     => 'menu_order',
				'has_archives' => false,
				)
			);
			if ( is_wp_error( $tka_new ) ) {
				continue;
				}
			$out['attributes'] = ( isset( $out['attributes'] ) ? (int) $out['attributes'] : 0 ) + 1;
			delete_transient( 'wc_attribute_taxonomies' );
					}

				$tka_tax = 'pa_' . $tka_key;

				/* Ensure the pa_* taxonomy is registered in this request. */
				if ( ! taxonomy_exists( $tka_tax ) ) {
				if ( class_exists( 'WC_Post_types' ) ) {
						WC_Post_types::register_taxonomies();
					}
				if ( ! taxonomy_exists( $tka_tax ) ) {
						register_taxonomy(
								$tka_tax,
								array( 'product' ),
								array(
										'hierarchical'          => false,
										'show_ui'               => true,
										'show_in_quick_edit'    => false,
										'show_admin_column'     => false,
										'query_var'             => true,
										'rewrite'               => false,
								)
						);
				}
				if ( ! taxonomy_exists( $tka_tax ) ) {
						continue;
					}
                        }

				$tka_terms = array();
				foreach ( $tka_attr['terms'] as $tka_name => $tka_tslug ) {
				$tka_t = get_term_by( 'slug', $tka_tslug, $tka_tax );
				if ( ! $tka_t || is_wp_error( $tka_t ) ) {
						$tka_ins = wp_insert_term( $tka_name, $tka_tax, array( 'slug' => $tka_tslug ) );
						if ( is_wp_error( $tka_ins ) ) {
						$tka_t = get_term_by( 'slug', $tka_tslug, $tka_tax );
						if ( ! $tka_t || is_wp_error( $tka_t ) ) {
									continue;
						}
							} else {
						$out['terms'] = ( isset( $out['terms'] ) ? (int) $out['terms'] : 0 ) + 1;
							}
					}
				$tka_terms[ $tka_name ] = $tka_tslug;
                        }

				$tka_attr_map[ $tka_key ] = $tka_terms;
			}

		return $tka_attr_map;
        }
}

if ( ! function_exists( 'toykindangel_demo_convert_variable' ) ) {
        /**
         * Convert one product to a variable product and create its variations
         * (regular/sale price, sale window, stock, SKU per variation).
         * Idempotent: existing variations (matched by their attribute slugs)
         * are never duplicated.
         *
         * @param int   $tka_pid      Product id.
         * @param array $tka_spec     Commerce spec for this product.
         * @param array $tka_attr_map Result of toykindangel_demo_attributes_import().
         * @param array $out          Stats accumulator (variations counter).
         * @return void
         */
        function toykindangel_demo_convert_variable( $tka_pid, $tka_spec, $tka_attr_map, &$out ) {
		$tka_product = wc_get_product( $tka_pid );
		if ( ! $tka_product || empty( $tka_spec['attrs'] ) ) {
				return;
			}

		/* ۱) ویژگی‌ها روی والد + متای استاندارد _product_attributes */
		$tka_pa  = array();
		$tka_pos = 0;
		foreach ( $tka_spec['attrs'] as $tka_key => $tka_names ) {
				if ( empty( $tka_attr_map[ $tka_key ] ) ) {
				continue;
				}
				$tka_tax   = 'pa_' . $tka_key;
				$tka_slugs = array();
				foreach ( (array) $tka_names as $tka_n ) {
				if ( isset( $tka_attr_map[ $tka_key ][ $tka_n ] ) ) {
						$tka_slugs[] = $tka_attr_map[ $tka_key ][ $tka_n ];
					}
                        }
				if ( $tka_slugs ) {
				wp_set_object_terms( $tka_pid, $tka_slugs, $tka_tax, false );
				$tka_pa[ $tka_tax ] = array(
				'name'        => $tka_tax,
				'value'       => '',
				'position'    => (string) $tka_pos,
				'is_visible'  => 1,
				'is_variation' => 1,
				'is_taxonomy' => 1,
				);
                        }
				$tka_pos++;
			}
		if ( $tka_pa ) {
				update_post_meta( $tka_pid, '_product_attributes', $tka_pa );
			}

		/* ۲) نوع محصول → variable */
		if ( ! $tka_product->is_type( 'variable' ) ) {
				wp_set_object_terms( $tka_pid, 'variable', 'product_type', false );
			}

		/* ۳) پاک‌سازی قیمت سادهٔ والد (والد متغیر قیمتش را از متغیرها می‌گیرد) */
		foreach ( array( '_regular_price', '_sale_price', '_price', '_sale_price_dates_from', '_sale_price_dates_to' ) as $tka_meta ) {
				delete_post_meta( $tka_pid, $tka_meta );
			}

		/* ۴) متغیرها — گارد تکراری‌شدن با هش اسلاگِ ویژگی‌ها */
		$tka_product = wc_get_product( $tka_pid );
		$tka_existing = array();
		foreach ( $tka_product ? $tka_product->get_children() : array() as $tka_vid ) {
				$tka_v = wc_get_product( $tka_vid );
				if ( ! $tka_v instanceof WC_Product_Variation ) {
				continue;
				}
				$tka_hashes = array();
				foreach ( $tka_v->get_attributes() as $tka_k => $tka_val ) {
				if ( '' !== (string) $tka_val ) {
						$tka_hashes[] = $tka_k . '=' . $tka_val;
					}
                        }
				sort( $tka_hashes );
				$tka_existing[ implode( '|', $tka_hashes ) ] = (int) $tka_vid;
			}

		$tka_window = toykindangel_demo_sale_window();
		foreach ( (array) $tka_spec['variations'] as $tka_i => $tka_vspec ) {
				$tka_attrs = array();
				foreach ( $tka_spec['attrs'] as $tka_key => $tka_names ) {
				if ( ! empty( $tka_vspec[ $tka_key ] ) && isset( $tka_attr_map[ $tka_key ][ $tka_vspec[ $tka_key ] ] ) ) {
					$tka_attrs[ 'pa_' . $tka_key ] = $tka_attr_map[ $tka_key ][ $tka_vspec[ $tka_key ] ];
					}
					}
				if ( count( $tka_attrs ) !== count( $tka_spec['attrs'] ) ) {
				continue; /* ترکیب ناقص */
					}

				$tka_hashes = array();
				foreach ( $tka_attrs as $tka_k => $tka_val ) {
				$tka_hashes[] = $tka_k . '=' . $tka_val;
					}
				sort( $tka_hashes );
				$tka_hash = implode( '|', $tka_hashes );
				if ( isset( $tka_existing[ $tka_hash ] ) ) {
				continue; /* از قبل موجود — دوباره ساخته نشود */
					}

				$tka_var = new WC_Product_Variation();
				$tka_var->set_parent_id( $tka_pid );
				$tka_var->set_status( 'publish' );
				$tka_var->set_attributes( $tka_attrs );
				$tka_var->set_regular_price( (string) $tka_vspec['regular'] );
				if ( ! empty( $tka_vspec['sale'] ) ) {
				$tka_var->set_sale_price( (string) $tka_vspec['sale'] );
				$tka_var->set_price( (string) $tka_vspec['sale'] );
				$tka_var->set_date_on_sale_from( $tka_window['from'] );
				$tka_var->set_date_on_sale_to( $tka_window['to'] );
					}
				if ( ! empty( $tka_spec['sku'] ) ) {
				$tka_var->set_sku( $tka_spec['sku'] . '-A' . ( $tka_i + 1 ) );
					}
				$tka_var->set_manage_stock( true );
				$tka_var->set_stock_quantity( isset( $tka_vspec['stock'] ) ? (int) $tka_vspec['stock'] : 5 );
				$tka_var->set_backorders( 'no' );
				$tka_var->save();

				$out['variations'] = ( isset( $out['variations'] ) ? (int) $out['variations'] : 0 ) + 1;
			}

		/* ۵) همگام‌سازی قیمت/موجودی والد */
		if ( class_exists( 'WC_Product_Variable' ) ) {
				WC_Product_Variable::sync( $tka_pid );
			}
		wc_delete_product_transients( $tka_pid );
        }
}

if ( ! function_exists( 'toykindangel_demo_commerce_import' ) ) {
        /**
         * Apply the commerce upgrade to every demo product — works both on a
         * fresh install (right after the products loop) and on the current
         * database (products are enriched in place, never deleted).
         * Re-running is safe: attributes/terms/variations are deduped, SKUs
         * are only filled when empty, sale windows only when missing.
         *
         * @param array $out Stats accumulator.
         * @return void
         */
        function toykindangel_demo_commerce_import( &$out ) {
		if ( ! post_type_exists( 'product' ) || ! function_exists( 'wc_get_product' ) ) {
				return;
			}

		$tka_data     = toykindangel_demo_commerce_data();
		$tka_attr_map = toykindangel_demo_attributes_import( $out );
		$tka_window   = toykindangel_demo_sale_window();

		foreach ( $tka_data['products'] as $tka_slug => $tka_spec ) {
				$tka_post = get_page_by_path( $tka_slug, OBJECT, 'product' );
				if ( ! $tka_post ) {
				continue;
				}
				$tka_pid     = (int) $tka_post->ID;
				$tka_product = wc_get_product( $tka_pid );
				if ( ! $tka_product ) {
				continue;
					}
				$tka_is_variable = $tka_product->is_type( 'variable' );

				/* SKU — فقط وقتی خالی است (دست‌کاری مدیر حفظ می‌شود) */
				if ( '' === $tka_product->get_sku() && ! empty( $tka_spec['sku'] ) ) {
				$tka_product->set_sku( $tka_spec['sku'] );
					}

				/* برچسب‌ها (product_tag) — ضمیمه، نه جایگزین */
				if ( ! empty( $tka_spec['tags'] ) ) {
				wp_set_object_terms( $tka_pid, array_map( 'sanitize_text_field', (array) $tka_spec['tags'] ), 'product_tag', true );
				$out['tags'] = ( isset( $out['tags'] ) ? (int) $out['tags'] : 0 ) + count( (array) $tka_spec['tags'] );
					}

				/* جشنواره: متا + برچسب */
				if ( ! empty( $tka_spec['festival'] ) ) {
				update_post_meta( $tka_pid, '_tka_festival', 'yes' );
				wp_set_object_terms( $tka_pid, 'جشنواره', 'product_tag', true );
					}

				/* وزن/ابعاد برای ارسال */
				if ( isset( $tka_spec['weight'] ) ) {
				$tka_product->set_weight( (string) $tka_spec['weight'] );
					}
				if ( ! empty( $tka_spec['dims'] ) ) {
				$tka_product->set_length( (string) $tka_spec['dims'][0] );
				$tka_product->set_width( (string) $tka_spec['dims'][1] );
				$tka_product->set_height( (string) $tka_spec['dims'][2] );
					}

				/* خرید تک‌موردی */
				if ( ! empty( $tka_spec['sold_individually'] ) ) {
				$tka_product->set_sold_individually( true );
					}

				/* مدیریت موجودی (محصولات ساده — متغیرها در سطح متغیر مدیریت می‌شوند) */
				if ( ! $tka_is_variable && ! empty( $tka_spec['stock'] ) ) {
				$tka_product->set_manage_stock( true );
				$tka_product->set_stock_quantity( (int) $tka_spec['stock']['qty'] );
				if ( ! empty( $tka_spec['stock']['low'] ) ) {
						$tka_product->set_low_stock_amount( (int) $tka_spec['stock']['low'] );
					}
                        }

				if ( ! $tka_is_variable ) {
				/* تقویم حراج برای تخفیف‌های فعلی */
				if ( ! empty( $tka_spec['sale_dates'] ) && '' !== (string) $tka_product->get_sale_price() && ! $tka_product->get_date_on_sale_from() ) {
						$tka_product->set_date_on_sale_from( $tka_window['from'] );
						$tka_product->set_date_on_sale_to( $tka_window['to'] );
						$tka_product->set_price( (string) $tka_product->get_sale_price() );
					}
				/* حراج تازه (سبز دلقک) */
				if ( ! empty( $tka_spec['sale'] ) ) {
						$tka_product->set_regular_price( (string) $tka_spec['sale']['regular'] );
						$tka_product->set_sale_price( (string) $tka_spec['sale']['sale'] );
						$tka_product->set_price( (string) $tka_spec['sale']['sale'] );
						$tka_product->set_date_on_sale_from( $tka_window['from'] );
						$tka_product->set_date_on_sale_to( $tka_window['to'] );
					}
                        }

				$tka_product->save();

				/* متغیرسازی */
				if ( ! empty( $tka_spec['variations'] ) ) {
				toykindangel_demo_convert_variable( $tka_pid, $tka_spec, $tka_attr_map, $out );
					}

				$out['enriched'] = ( isset( $out['enriched'] ) ? (int) $out['enriched'] : 0 ) + 1;
			}

		wc_delete_product_transients();
        }
}

/* -------------------------------------------------------------------------
 * Pages helper (shared by full demo import and settings-only import)
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'toykindangel_demo_import_pages' ) ) {
        /**
         * Create the required pages and wire the woocommerce_*_page_id options:
         * WooCommerce pages (shop/cart/checkout/my-account), the static demo
         * pages (about/contact/categories) and the refund policy page the
         * footer links to. Idempotent — existing slugs are reused.
         *
         * @param array $out Stats accumulator (['pages'] is incremented).
         * @return int[] Map of slug => page id for the static pages.
         */
        function toykindangel_demo_import_pages( &$out ) {
		$tka_page_ids = array();

		/* ---------- برگه‌های ووکامرس ---------- */
		if ( post_type_exists( 'product' ) ) {
				$tka_wc_pages = array(
						'shop'      => array( 'shop', 'فروشگاه', '' ),
						'cart'      => array( 'cart', 'سبد خرید', '[woocommerce_cart]' ),
						'checkout'  => array( 'checkout', 'تسویه‌حساب', '[woocommerce_checkout]' ),
						'myaccount' => array( 'myaccount', 'حساب کاربری', '[woocommerce_my_account]' ),
				);
				foreach ( $tka_wc_pages as $tka_key => list( $tka_slug, $tka_title, $tka_sc ) ) {
						$tka_opt   = "woocommerce_{$tka_key}_page_id";
						$tka_pid   = (int) get_option( $tka_opt );
						$tka_valid = $tka_pid && get_post( $tka_pid ) && 'publish' === get_post_status( $tka_pid );

						if ( ! $tka_valid ) {
						$tka_found = get_page_by_path( $tka_slug, OBJECT, 'page' );
						if ( $tka_found ) {
							$tka_pid = (int) $tka_found->ID;
							} else {
							$tka_pid = (int) wp_insert_post(
							array(
							'post_type'    => 'page',
							'post_status'  => 'publish',
							'post_title'   => $tka_title,
							'post_name'    => $tka_slug,
							'post_content' => $tka_sc,
							)
							);
							$out['pages'] = ( isset( $out['pages'] ) ? (int) $out['pages'] : 0 ) + 1;
							}
                                }
						if ( $tka_pid ) {
						update_option( $tka_opt, $tka_pid, false );
							}
                        }
                }

		/* ---------- برگه‌های ثابت (درباره/تماس/دسته‌بندی‌ها) ---------- */
		foreach ( toykindangel_demo_data()['pages'] as $tka_pg ) {
				$tka_found = get_page_by_path( $tka_pg['slug'], OBJECT, 'page' );
				if ( $tka_found ) {
				$tka_page_ids[ $tka_pg['slug'] ] = (int) $tka_found->ID;
				continue;
				}
				$tka_pid = wp_insert_post(
						array(
								'post_type'    => 'page',
								'post_status'  => 'publish',
								'post_title'   => $tka_pg['title'],
								'post_name'    => $tka_pg['slug'],
								'post_content' => $tka_pg['content'],
						)
				);
				if ( $tka_pid && ! is_wp_error( $tka_pid ) ) {
				if ( ! empty( $tka_pg['template'] ) ) {
							update_post_meta( (int) $tka_pid, '_wp_page_template', $tka_pg['template'] );
				}
				$tka_page_ids[ $tka_pg['slug'] ] = (int) $tka_pid;
				$out['pages'] = ( isset( $out['pages'] ) ? (int) $out['pages'] : 0 ) + 1;
					}
			}

		/* ---------- برگهٔ بازگشت کالا (فوتر به آن لینک می‌دهد) ---------- */
		$tka_refund = get_page_by_path( 'refund_returns', OBJECT, 'page' );
		if ( ! $tka_refund ) {
				$tka_rid = wp_insert_post(
						array(
								'post_type'    => 'page',
								'post_status'  => 'publish',
								'post_title'   => 'بازگشت کالا',
								'post_name'    => 'refund_returns',
								'post_content' => "<p>تا ۷ روز پس از دریافت کالا، در صورت وجود مشکل физیکِ محصول، امکان بازگشت کالا وجود دارد.</p>\n<p>لطفاً پیش از ارسال مرجوعی، با پشتیبانی فروشگاه تماس بگیرید.</p>",
						)
				);
				if ( $tka_rid && ! is_wp_error( $tka_rid ) ) {
				$out['pages'] = ( isset( $out['pages'] ) ? (int) $out['pages'] : 0 ) + 1;
				}
			}

		return $tka_page_ids;
        }
}

/* -------------------------------------------------------------------------
 * Importer
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'toykindangel_demo_import' ) ) {
        /**
         * Run the demo import. Safe to call repeatedly (idempotent).
         *
         * @return array Summary { products, terms, media, pages, menu, ok, msg }
         */
        function toykindangel_demo_import() {
		$out = array(
		'ok'       => false,
		'products' => 0,
		'terms'    => 0,
		'media'    => 0,
		'pages'    => 0,
		'menu'     => 0,
		'attributes' => 0,
		'variations' => 0,
		'enriched'   => 0,
		'msg'      => '',
		);

		if ( ! taxonomy_exists( 'product_cat' ) || ! post_type_exists( 'product' ) ) {
				$out['msg'] = __( 'برای درج محتوای نمونه ابتدا افزونه ووکامرس باید فعال باشد.', 'toykindangel' );
				return $out;
			}

		$data = toykindangel_demo_data();

		/* ---------- 1+2) برگه‌های ووکامرس و برگه‌های ثابت ---------- */
		$tka_page_ids = toykindangel_demo_import_pages( $out );

		/* ---------- 3) استوری‌ها (۱۰ دسته اول صفحه اصلی) ---------- */
		$tka_story_ids = array();
		foreach ( array_values( $data['stories'] ) as $tka_i => $tka_s ) {
				/* اسلاگ صریح: لینک دسته‌ها تمیز می‌ماند (dolls-plush, vehicles, …).
				 * برای اصطلاحات موجود هم اسلاگ اصلاح می‌شود تا WXR و درون‌ریز
				 * یک‌کلیکی همیشه یک خروجی بدهند. */
				$tka_tid = toykindangel_demo_term( $tka_s['name'], 0, $tka_s['slug'] ?? '' );
				if ( ! $tka_tid ) {
				continue;
				}
				$tka_story_ids[ $tka_s['name'] ] = $tka_tid;

				if ( ! empty( $tka_s['slug'] ) ) {
				$tka_term = get_term( $tka_tid, 'product_cat' );
				if ( $tka_term && ! is_wp_error( $tka_term ) && $tka_term->slug !== $tka_s['slug'] ) {
						wp_update_term( $tka_tid, 'product_cat', array( 'slug' => $tka_s['slug'] ) );
					}
                        }

				if ( ! get_term_meta( $tka_tid, 'tka_is_story', true ) ) {
				update_term_meta( $tka_tid, 'tka_is_story', '1' );
				update_term_meta( $tka_tid, 'tka_story_order', (string) ( $tka_i + 1 ) );
					}
				if ( ! get_term_meta( $tka_tid, 'thumbnail_id', true ) ) {
				$tka_att = toykindangel_demo_sideload( $tka_s['file'], $tka_s['name'] );
				if ( $tka_att ) {
						update_term_meta( $tka_tid, 'thumbnail_id', $tka_att );
						$out['media']++;
					}
                        }
			}

		/* ---------- 4) درخت دسته‌بندی‌ها (۳ سطح) ---------- */
		$tka_rail = 0;
		foreach ( $data['tree'] as $tka_top ) {
				$tka_top_id = toykindangel_demo_term( $tka_top['name'] );
				if ( ! $tka_top_id ) {
				continue;
				}

				/* استوری‌های هم‌نام، دسته‌ی مادر همان شاخه می‌شوند (بدون تکرار). */
				if ( ! get_term_meta( $tka_top_id, 'tka_is_story', true ) ) {
				$tka_rail += 10;
				if ( ! get_term_meta( $tka_top_id, 'tka_rail_order', true ) ) {
						update_term_meta( $tka_top_id, 'tka_rail_order', (string) $tka_rail );
					}
				if ( ! get_term_meta( $tka_top_id, 'thumbnail_id', true ) ) {
						$tka_att = toykindangel_demo_sideload( $tka_top['file'], $tka_top['name'] );
						if ( $tka_att ) {
						update_term_meta( $tka_top_id, 'thumbnail_id', $tka_att );
						$out['media']++;
						}
					}
                        }

				foreach ( $tka_top['groups'] as $tka_group ) {
				$tka_group_id = toykindangel_demo_term( $tka_group['title'], $tka_top_id );
				if ( ! $tka_group_id ) {
						continue;
					}
				foreach ( $tka_group['items'] as $tka_item ) {
						if ( toykindangel_demo_term( $tka_item, $tka_group_id ) ) {
						$out['terms']++;
						}
					}
                        }
				$out['terms']++;
			}

		/* ---------- 5) برندها ---------- */
		$tka_brand_tax = taxonomy_exists( 'product_brand' ) ? 'product_brand' : 'tka_brand';
		$tka_brand_ids = array();
		foreach ( $data['brands'] as $tka_i => $tka_bname ) {
				$tka_bterm = get_term_by( 'name', $tka_bname, $tka_brand_tax );
				if ( $tka_bterm && ! is_wp_error( $tka_bterm ) ) {
				$tka_bid = (int) $tka_bterm->term_id;
				} else {
				$tka_ins = wp_insert_term( $tka_bname, $tka_brand_tax );
				$tka_bid = is_wp_error( $tka_ins ) ? 0 : (int) $tka_ins['term_id'];
					}
				if ( $tka_bid ) {
				$tka_brand_ids[ $tka_bname ] = $tka_bid;
				if ( ! get_term_meta( $tka_bid, 'tka_brand_order', true ) ) {
						update_term_meta( $tka_bid, 'tka_brand_order', (string) ( $tka_i + 1 ) );
					}
                        }
			}

		/* ---------- 6) محصولات ---------- */
		/* جدیدترین‌ها بالاترین تاریخ را می‌گیرند تا ریل «جدیدترین‌ها» دقیق باشد. */
		$tka_names = array();
		foreach ( $data['products'] as $tka_p ) {
				$tka_names[] = $tka_p['name'];
			}
		$tka_newest_names = array();
		foreach ( $data['products'] as $tka_p ) {
				if ( empty( $tka_p['old'] ) ) {
				$tka_newest_names[] = $tka_p['name'];
				}
			}

		$tka_base = time();
		foreach ( $data['products'] as $tka_idx => $tka_p ) {
				$tka_date = $tka_base - 86400 - ( $tka_idx + 1 ) * 60; /* پیش‌فرض: قدیمی‌تر */
				$tka_pos  = array_search( $tka_p['name'], $tka_newest_names, true );
				if ( false !== $tka_pos ) {
				/* جدیدترین‌ها: نزدیک‌ترین تاریخِ «گذشته» تا ریل دقیق باشد (تاریخ آینده → status=future!) */
				$tka_date = $tka_base - ( ( (int) $tka_pos + 1 ) * 60 );
				}

				$tka_exists = get_page_by_path( $tka_p['slug'], OBJECT, 'product' );
				if ( $tka_exists ) {
				/* خودترمیمی ۱: وضعیت future (تاریخ آینده) را منتشر کن */
				if ( 'publish' !== get_post_status( $tka_exists ) ) {
						wp_update_post(
								array(
										'ID'            => $tka_exists->ID,
										'post_status'   => 'publish',
										'post_date'     => gmdate( 'Y-m-d H:i:s', $tka_date ),
										'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $tka_date ),
								)
						);
				}
				/* خودترمیمی ۲: اگر تصویر ندارد، تصویر را اضافه کن */
				if ( ! get_post_thumbnail_id( $tka_exists ) && is_readable( $tka_p['img'] ) ) {
						$tka_att = toykindangel_demo_sideload( $tka_p['img'], $tka_p['name'] );
						if ( $tka_att ) {
						set_post_thumbnail( $tka_exists, $tka_att );
						$out['media']++;
						}
					}
				continue;
                        }

				$tka_desc = sprintf(
						'%s با کیفیت ساخت بالا و ضمانت سلامت فیزیکی کالا — مناسب هدیه و بازی روزمره کودکان. خرید از فروشگاه فرشته مهربون با ارسال سریع.',
						$tka_p['name']
				);

				$tka_pid = wp_insert_post(
						array(
								'post_type'      => 'product',
								'post_status'    => 'publish',
								'post_title'     => $tka_p['name'],
								'post_name'      => $tka_p['slug'],
								'post_content'   => '<p>' . $tka_desc . '</p>',
								'post_excerpt'   => $tka_desc,
								'post_date'      => gmdate( 'Y-m-d H:i:s', $tka_date ),
								'post_date_gmt'  => gmdate( 'Y-m-d H:i:s', $tka_date ),
								'comment_status' => 'open',
						)
				);
				if ( is_wp_error( $tka_pid ) || ! $tka_pid ) {
				continue;
				}
				$tka_pid = (int) $tka_pid;

				update_post_meta( $tka_pid, '_regular_price', (string) ( $tka_p['old'] ?? $tka_p['price'] ) );
				update_post_meta( $tka_pid, '_sale_price', ! empty( $tka_p['old'] ) ? (string) $tka_p['price'] : '' );
				update_post_meta( $tka_pid, '_price', (string) $tka_p['price'] );
				update_post_meta( $tka_pid, '_stock_status', 'instock' );
				update_post_meta( $tka_pid, '_manage_stock', 'no' );
				update_post_meta( $tka_pid, '_virtual', 'no' );
				update_post_meta( $tka_pid, '_downloadable', 'no' );
				update_post_meta( $tka_pid, '_visibility', 'visible' );
				update_post_meta( $tka_pid, 'total_sales', (string) ( $tka_p['sales'] ?? 0 ) );

				/* دسته‌ها: برگ‌ها (و در نبود برگ، والد) را بر اساس نام پیدا کن */
				$tka_cat_ids = array();
				foreach ( (array) ( $tka_p['cats'] ?? array() ) as $tka_cname ) {
				$tka_cterm = get_term_by( 'name', $tka_cname, 'product_cat' );
				if ( $tka_cterm && ! is_wp_error( $tka_cterm ) ) {
					$tka_cat_ids[] = (int) $tka_cterm->term_id;
					}
					}
				if ( $tka_cat_ids ) {
				wp_set_object_terms( $tka_pid, $tka_cat_ids, 'product_cat', true );
					}

				if ( ! empty( $tka_p['brand'] ) && isset( $tka_brand_ids[ $tka_p['brand'] ] ) ) {
				wp_set_object_terms( $tka_pid, array( $tka_brand_ids[ $tka_p['brand'] ] ), $tka_brand_tax, true );
					}

				$tka_att = toykindangel_demo_sideload( $tka_p['img'], $tka_p['name'] );
				if ( $tka_att ) {
				set_post_thumbnail( $tka_pid, $tka_att );
				$out['media']++;
					}

				$out['products']++;
			}

		/* ---------- 6ب) ارتقای تجاری (Task 18-e): رنگ/سایز/تخفیف/جشنواره/برچسب/SKU ---------- */
		toykindangel_demo_commerce_import( $out );

		/* ---------- 7) منوی اصلی ---------- */
		$tka_menu_name = 'منوی اصلی فرشته مهربون';
		$tka_menu      = wp_get_nav_menu_object( $tka_menu_name );
		if ( ! $tka_menu ) {
				$tka_menu_id = wp_create_nav_menu( $tka_menu_name );
			} else {
			$tka_menu_id = (int) $tka_menu->term_id;
			}

		if ( $tka_menu_id && ! is_wp_error( $tka_menu_id ) ) {
				if ( empty( wp_get_nav_menu_items( $tka_menu_id ) ) ) {
				foreach ( $data['menu'] as $tka_m ) {
					if ( isset( $tka_m['url'] ) ) {
							wp_update_nav_menu_item(
									$tka_menu_id,
									0,
									array(
											'menu-item-title'  => $tka_m['title'],
											'menu-item-url'    => home_url( $tka_m['url'] ),
											'menu-item-type'   => 'custom',
											'menu-item-status' => 'publish',
									)
							);
					} else {
							$tka_target = 0;
							if ( 'shop' === $tka_m['page'] ) {
							$tka_target = (int) get_option( 'woocommerce_shop_page_id' );
							} elseif ( isset( $tka_page_ids[ $tka_m['page'] ] ) ) {
							$tka_target = $tka_page_ids[ $tka_m['page'] ];
									}
									if ( ! $tka_target ) {
					continue;
									}
									wp_update_nav_menu_item(
									$tka_menu_id,
									0,
									array(
											'menu-item-title'     => $tka_m['title'],
											'menu-item-object'    => 'page',
											'menu-item-object-id' => $tka_target,
											'menu-item-type'      => 'post_type',
											'menu-item-status'    => 'publish',
									)
							);
						}
					$out['menu']++;
					}
					}

				$tka_locations            = get_theme_mod( 'nav_menu_locations', array() );
				$tka_locations['primary'] = $tka_menu_id;
				set_theme_mod( 'nav_menu_locations', $tka_locations );
			}

		/* ---------- 7ب) منوی دسته‌بندی‌ها (دراور مرورگر دسکتاپ/موبایل) ---------- */
		$tka_cats_menu_name = 'منوی دسته‌بندی‌ها فرشته مهربون';
		$tka_cats_menu      = wp_get_nav_menu_object( $tka_cats_menu_name );
		if ( ! $tka_cats_menu ) {
				$tka_cats_menu_id = wp_create_nav_menu( $tka_cats_menu_name );
			} else {
			$tka_cats_menu_id = (int) $tka_cats_menu->term_id;
			}

		if ( $tka_cats_menu_id && ! is_wp_error( $tka_cats_menu_id ) && empty( wp_get_nav_menu_items( $tka_cats_menu_id ) ) ) {
				$tka_cat_tax = taxonomy_exists( 'product_cat' ) ? 'product_cat' : 'tka_store_cat';
				$tka_cat_tops = get_terms(
						array(
								'taxonomy'   => $tka_cat_tax,
								'parent'     => 0,
								'hide_empty' => false,
								'number'     => 40,
								'exclude'    => array( (int) get_option( 'default_product_cat', 0 ) ),
								'meta_key'   => 'tka_rail_order', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one-off admin import process
								'orderby'    => 'meta_value_num',
								'order'      => 'ASC',
						)
				);
				if ( is_wp_error( $tka_cat_tops ) ) {
			$tka_cat_tops = array();
				}
				foreach ( $tka_cat_tops as $tka_ct ) {
				$tka_parent_item = wp_update_nav_menu_item(
				$tka_cats_menu_id,
				0,
				array(
				'menu-item-title'     => $tka_ct->name,
				'menu-item-object'    => $tka_cat_tax,
				'menu-item-object-id' => $tka_ct->term_id,
				'menu-item-type'      => 'taxonomy',
				'menu-item-status'    => 'publish',
				)
			);
			if ( $tka_parent_item && ! is_wp_error( $tka_parent_item ) ) {
				$out['menu']++;
				}
			/* زیردسته‌ها به‌عنوان فرزند (تا یک سطح) */
			$tka_kids = get_terms(
			array(
			'taxonomy'   => $tka_cat_tax,
			'parent'     => $tka_ct->term_id,
			'hide_empty' => false,
			'number'     => 30,
			)
			);
			if ( is_wp_error( $tka_kids ) ) {
				continue;
				}
			foreach ( $tka_kids as $tka_kid ) {
				wp_update_nav_menu_item(
						$tka_cats_menu_id,
						0,
						array(
								'menu-item-title'     => $tka_kid->name,
								'menu-item-object'    => $tka_cat_tax,
								'menu-item-object-id' => $tka_kid->term_id,
								'menu-item-type'      => 'taxonomy',
								'menu-item-status'    => 'publish',
								'menu-item-parent-id' => (int) $tka_parent_item,
						)
				);
			}
					}
			}
		if ( $tka_cats_menu_id && ! is_wp_error( $tka_cats_menu_id ) ) {
				$tka_locations            = get_theme_mod( 'nav_menu_locations', array() );
				$tka_locations['cats']    = $tka_cats_menu_id;
				set_theme_mod( 'nav_menu_locations', $tka_locations );
			}

		/* ---------- 7ج) فهرست ستون «راهنمای خرید» فوتر ---------- */
		$tka_f1_name = 'راهنمای خرید فرشته مهربون';
		$tka_f1_menu = wp_get_nav_menu_object( $tka_f1_name );
		if ( ! $tka_f1_menu ) {
				$tka_f1_id = wp_create_nav_menu( $tka_f1_name );
			} else {
			$tka_f1_id = (int) $tka_f1_menu->term_id;
			}
		if ( $tka_f1_id && ! is_wp_error( $tka_f1_id ) && empty( wp_get_nav_menu_items( $tka_f1_id ) ) ) {
				$tka_f1_pages = array( 'about-us', 'contact-us', 'refund_returns' );
				foreach ( $tka_f1_pages as $tka_f1_slug ) {
				$tka_f1_target = isset( $tka_page_ids[ $tka_f1_slug ] ) ? (int) $tka_page_ids[ $tka_f1_slug ] : (int) get_page_by_path( $tka_f1_slug )->ID;
				if ( ! $tka_f1_target ) {
					continue;
					}
				wp_update_nav_menu_item(
				$tka_f1_id,
				0,
				array(
				'menu-item-title'     => get_the_title( $tka_f1_target ),
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $tka_f1_target,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				)
			);
			$out['menu']++;
				}
			}
		if ( $tka_f1_id && ! is_wp_error( $tka_f1_id ) ) {
				$tka_locations             = get_theme_mod( 'nav_menu_locations', array() );
				$tka_locations['footer_col1'] = $tka_f1_id;
				set_theme_mod( 'nav_menu_locations', $tka_locations );
			}

		/* ---------- 8) بنرهای سفارشی‌ساز (hero/grid/strip) ---------- */
		$tka_hm = array();
		foreach ( $data['banners']['hero'] as $tka_i => $tka_b ) {
				$tka_att = toykindangel_demo_sideload( $tka_b['file'], 'اسلاید ' . ( $tka_i + 1 ) );
				if ( $tka_att ) {
				/* TKA fix: keys must be 1-based (customizer + front-ssr read 1..4). */
				$tka_hm[ 'tka_hero_' . ( $tka_i + 1 ) . '_img' ]  = wp_make_link_relative( wp_get_attachment_url( $tka_att ) );
				$tka_hm[ 'tka_hero_' . ( $tka_i + 1 ) . '_link' ] = ( $tka_b['link'] ? $tka_b['link'] : '#' );
				$out['media']++;
				}
			}
		foreach ( $data['banners']['grid'] as $tka_i => $tka_b ) {
				$tka_att = toykindangel_demo_sideload( $tka_b['file'], 'بنر ' . ( $tka_i + 1 ) );
				if ( $tka_att ) {
				/* TKA fix: keys must be 1-based (customizer + front-ssr read 1..4). */
				$tka_hm[ 'tka_banner_' . ( $tka_i + 1 ) . '_img' ]  = wp_make_link_relative( wp_get_attachment_url( $tka_att ) );
				$tka_hm[ 'tka_banner_' . ( $tka_i + 1 ) . '_link' ] = ( $tka_b['link'] ? $tka_b['link'] : '#' );
				$out['media']++;
				}
			}
		$tka_strip_att = toykindangel_demo_sideload( $data['banners']['strip']['file'], 'نوار بالای سایت' );
		if ( $tka_strip_att ) {
				$tka_hm['tka_strip_img']  = wp_make_link_relative( wp_get_attachment_url( $tka_strip_att ) );
				$tka_hm['tka_strip_link'] = ( $data['banners']['strip']['link'] ? $data['banners']['strip']['link'] : '#' );
				$out['media']++;
			}
		foreach ( $tka_hm as $tka_key => $tka_val ) {
				set_theme_mod( $tka_key, $tka_val );
			}

		/* ---------- 9) پایان ---------- */
		flush_rewrite_rules();
		update_option( 'tka_demo_imported', TOYKINDANGEL_VERSION, false );

		$out['ok']  = true;
		$out['msg'] = __( 'محتوای نمونه با موفقیت درج شد.', 'toykindangel' );

		return $out;
        }
}

if ( ! function_exists( 'toykindangel_demo_settings_import' ) ) {
        /**
         * Settings-only import (v0.15.0): create the required pages and apply
         * the necessary WordPress/WooCommerce settings — WITHOUT importing
         * demo products, categories, brands or menus. For store owners who
         * want to start from a clean catalogue.
         *
         * @return array Summary { ok, pages, settings, msg }
         */
        function toykindangel_demo_settings_import() {
		$out = array(
		'ok'       => false,
		'pages'    => 0,
		'settings' => array(),
		'msg'      => '',
		);

		/* ---------- برگه‌های لازم (ووکامرس + ثابت + بازگشت کالا) ---------- */
		toykindangel_demo_import_pages( $out );

		/* ---------- تنظیمات لازم ---------- */
		$tka_settings = array();

		/* پیوند یکتا: لینک‌های قالب/دمو (/product/<slug>/) فقط با
	 * پیوند یکتای زیبا کار می‌کنند — فقط وقتی هرگز تنظیم نشده. */
		if ( ! get_option( 'permalink_structure' ) ) {
				update_option( 'permalink_structure', '/%postname%/' );
				$tka_settings[] = __( 'ساختار پیوند یکتا (/%postname%/)', 'toykindangel' );
			}

		/* جایگاه‌های فهرست بدون ساخت منو دست‌نخورده می‌مانند؛
	 * برگهٔ دسته‌بندی‌ها اگر تنها برگهٔ قالب باشد، قالبش اعمال شده است. */

		$out['settings'] = $tka_settings;

		flush_rewrite_rules();
		update_option( 'tka_settings_imported', TOYKINDANGEL_VERSION, false );

		$out['ok']  = true;
		$out['msg'] = __( 'برگه‌ها و تنظیمات لازم اعمال شد — بدون محصول، دسته‌بندی، برند و منو.', 'toykindangel' );

		return $out;
        }
}

/* -------------------------------------------------------------------------
 * WXR export: فایل ایمپورت استاندارد وردپرس (ابزار → وارد کردن)
 * ---------------------------------------------------------------------- */

if ( ! function_exists( 'toykindangel_demo_wxr_url' ) ) {
        /**
         * Convert a bundled theme file path to its public URL on the target site.
         * The WP importer downloads attachment URLs, so demo images are referenced
         * from the installed theme folder — no external host involved.
         *
         * @param string $file Absolute server path inside the theme.
         * @return string Absolute URL.
         */
        function toykindangel_demo_wxr_url( $file ) {
		$rel = str_replace( TOYKINDANGEL_DIR, 'wp-content/themes/toykindangel', wp_normalize_path( $file ) );
		return home_url( '/' . ltrim( $rel, '/' ) );
        }
}

if ( ! function_exists( 'toykindangel_demo_wxr_cat_slug' ) ) {
        /**
         * Deterministic product_cat slug for the WXR file.
         *
         * @param string $name Term name.
         * @return string
         */
        function toykindangel_demo_wxr_cat_slug( $name ) {
		return sanitize_title( $name );
        }
}

if ( ! function_exists( 'toykindangel_demo_wxr_item_head' ) ) {
        /**
         * Render the shared head of one WXR <item>.
         *
         * @param int    $id     Source post id.
         * @param string $title  Post title.
         * @param string $type   Post type.
         * @param string $date   Post date (Y-m-d H:i:s).
         * @param string $name   Post slug ('' = skip).
         * @param string $guid   GUID.
         * @param int    $post_parent Parent post id (product_variation → product).
         * @return string
         */
        function toykindangel_demo_wxr_item_head( $id, $title, $type, $date, $name = '', $guid = '', $post_parent = 0 ) {
		$xml  = "\t<item>\n";
		$xml .= "\t\t<title><![CDATA[$title]]></title>\n";
		$xml .= "\t\t<link>" . esc_url( home_url( '/' ) ) . "</link>\n";
		$xml .= "\t\t<pubDate>" . gmdate( 'D, d M Y H:i:s +0000', strtotime( $date ) ) . "</pubDate>\n";
		$xml .= "\t\t<dc:creator><![CDATA[admin]]></dc:creator>\n";
		$xml .= "\t\t<guid isPermaLink=\"false\">" . esc_html( $guid ) . "</guid>\n";
		$xml .= "\t\t<description></description>\n";
		$xml .= "\t\t<content:encoded><![CDATA[]]></content:encoded>\n";
		$xml .= "\t\t<excerpt:encoded><![CDATA[]]></excerpt:encoded>\n";
		$xml .= "\t\t<wp:post_id>$id</wp:post_id>\n";
		$xml .= "\t\t<wp:post_date><![CDATA[$date]]></wp:post_date>\n";
		$xml .= "\t\t<wp:post_date_gmt><![CDATA[$date]]></wp:post_date_gmt>\n";
		$xml .= "\t\t<wp:comment_status><![CDATA[closed]]></wp:comment_status>\n";
		$xml .= "\t\t<wp:ping_status><![CDATA[closed]]></wp:ping_status>\n";
		if ( $name ) {
				$xml .= "\t\t<wp:post_name><![CDATA[$name]]></wp:post_name>\n";
			}
		$xml .= "\t\t<wp:status><![CDATA[publish]]></wp:status>\n";
		$xml .= "\t\t<wp:post_parent>" . (int) $post_parent . "</wp:post_parent>\n";
		$xml .= "\t\t<wp:menu_order>0</wp:menu_order>\n";
		$xml .= "\t\t<wp:post_type><![CDATA[$type]]></wp:post_type>\n";
		$xml .= "\t\t<wp:post_password><![CDATA[]]></wp:post_password>\n";
		$xml .= "\t\t<wp:is_sticky>0</wp:is_sticky>\n";
		return $xml;
        }
}

if ( ! function_exists( 'toykindangel_demo_wxr_meta' ) ) {
        /**
         * Render one <wp:postmeta> element.
         *
         * @param string $key   Meta key.
         * @param string $value Meta value.
         * @return string
         */
        function toykindangel_demo_wxr_meta( $key, $value ) {
		return "\t\t<wp:postmeta>\n\t\t\t<wp:meta_key><![CDATA[$key]]></wp:meta_key>\n\t\t\t<wp:meta_value><![CDATA[$value]]></wp:meta_value>\n\t\t</wp:postmeta>\n";
        }
}

if ( ! function_exists( 'toykindangel_demo_wxr_build' ) ) {
        /**
         * Build the complete WXR 1.2 document for the demo content:
         * nav_menu term, pages, products (with product_cat/product_brand terms,
         * prices and featured images), demo images as attachments and the main
         * menu (custom items with relative URLs — no ID remapping needed).
         *
         * @param bool $include_media Embed attachment items for the bundled
         *                            demo images. The URLs are built from the
         *                            current site (theme folder), so they only
         *                            resolve where the theme is installed. Set
         *                            false for a portable file that imports
         *                            without any media fetching.
         * @return string
         */
        function toykindangel_demo_wxr_build( $include_media = true ) {
		$data = toykindangel_demo_data();
		$home = home_url( '/' );
		$base = strtotime( '2026-01-01 08:00:00' );

		/* Task 18-e: commerce layer (رنگ/سایز/تخفیف/جشنواره/برچسب/SKU) در خروجی WXR */
		$tka_commerce = toykindangel_demo_commerce_data();
		$tka_cwin     = toykindangel_demo_sale_window();
		$tka_cfrom    = (string) $tka_cwin['from'];
		$tka_cto      = (string) $tka_cwin['to'];
		$tka_vnext    = 800; /* post id متغیرها */

		$xml  = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
		$xml .= '<rss version="2.0"' . "\n";
		$xml .= "\txmlns:excerpt=\"http://wordpress.org/export/1.2/excerpt/\"\n";
		$xml .= "\txmlns:content=\"http://purl.org/rss/1.0/modules/content/\"\n";
		$xml .= "\txmlns:wfw=\"http://wellformedweb.org/CommentAPI/\"\n";
		$xml .= "\txmlns:dc=\"http://purl.org/dc/elements/1.1/\"\n";
		$xml .= "\txmlns:wp=\"http://wordpress.org/export/1.2/\">\n";
		$xml .= "<channel>\n";
		$xml .= "\t<title>فرشته مهربون — محتوای نمونه قالب</title>\n";
		$xml .= "\t<link>" . esc_url( $home ) . "</link>\n";
		$xml .= "\t<description>محتوای نمونه فروشگاه اسباب‌بازی (منو، محصولات، دسته‌ها و برگه‌ها)</description>\n";
		$xml .= "\t<pubDate>" . gmdate( 'D, d M Y H:i:s +0000' ) . "</pubDate>\n";
		$xml .= "\t<language>fa-IR</language>\n";
		$xml .= "\t<wp:wxr_version>1.2</wp:wxr_version>\n";
		$xml .= "\t<wp:base_site_url>" . esc_url( $home ) . "</wp:base_site_url>\n";
		$xml .= "\t<wp:base_blog_url>" . esc_url( $home ) . "</wp:base_blog_url>\n";
		$xml .= "\t<wp:author>\n\t\t<wp:author_id>1</wp:author_id>\n\t\t<wp:author_login><![CDATA[admin]]></wp:author_login>\n\t\t<wp:author_email><![CDATA[info@toykindangel.com]]></wp:author_email>\n\t\t<wp:author_display_name><![CDATA[فرشته مهربون]]></wp:author_display_name>\n\t\t<wp:author_first_name><![CDATA[]]></wp:author_first_name>\n\t\t<wp:author_last_name><![CDATA[]]></wp:author_last_name>\n\t</wp:author>\n";

		/* ---------- nav_menu term ---------- */
		$xml .= "\t<wp:term>\n\t\t<wp:term_id>500</wp:term_id>\n\t\t<wp:term_taxonomy>nav_menu</wp:term_taxonomy>\n";
		$xml .= "\t\t<wp:term_slug><![CDATA[toykindangel-main-menu]]></wp:term_slug>\n";
		$xml .= "\t\t<wp:term_name><![CDATA[منوی اصلی فرشته مهربون]]></wp:term_name>\n\t</wp:term>\n";

		/* ---------- برگه‌های ثابت ---------- */
		foreach ( $data['pages'] as $tka_i => $tka_pg ) {
				$tka_id = 110 + $tka_i;
				$tka_date = gmdate( 'Y-m-d H:i:s', $base - 3600 - $tka_i * 60 );
				$xml .= toykindangel_demo_wxr_item_head( $tka_id, $tka_pg['title'], 'page', $tka_date, $tka_pg['slug'], $home . '?tka-demo=page-' . $tka_pg['slug'] );
				$xml .= "\t\t<content:encoded><![CDATA[" . ( $tka_pg['content'] ?? '' ) . "]]></content:encoded>\n";
				if ( ! empty( $tka_pg['template'] ) ) {
				$xml .= toykindangel_demo_wxr_meta( '_wp_page_template', $tka_pg['template'] );
				}
				$xml .= "\t</item>\n";
			}

		/* ---------- تصاویر دمو به‌عنوان پیوست (کشف تکراری‌ها با نام فایل) ---------- */
		$tka_seen = array();
		$tka_next = 900;

		$tka_collect = static function ( $file, $title ) use ( &$tka_seen, &$tka_next, $base, $home, $include_media ) {
				if ( ! $file || isset( $tka_seen[ $file ] ) ) {
				return '';
				}
				$tka_id = ++$tka_next;
				$tka_seen[ $file ] = $tka_id;
				if ( ! $include_media ) {
				return '';
					}

				$tka_date = gmdate( 'Y-m-d H:i:s', $base + $tka_next );
				$tka_name = basename( $file );

				$xml  = toykindangel_demo_wxr_item_head( $tka_id, $title, 'attachment', $tka_date, sanitize_title( pathinfo( $tka_name, PATHINFO_FILENAME ) ), $home . '?tka-demo=att-' . $tka_name );
				$xml .= "\t\t<wp:attachment_url><![CDATA[" . toykindangel_demo_wxr_url( $file ) . "]]></wp:attachment_url>\n";
				$xml .= toykindangel_demo_wxr_meta( '_wp_attached_file', '2026/01/' . $tka_name );
				$xml .= "\t</item>\n";
				return $xml;
			};

			$tka_att_buffer = '';
			$tka_collect_render = static function ( $file, $title ) use ( &$tka_att_buffer, $tka_collect ) {
					$tka_att_buffer .= $tka_collect( $file, $title );
                };

			foreach ( $data['products'] as $tka_p ) {
					$tka_collect_render( $tka_p['img'], $tka_p['name'] );
                }
			foreach ( $data['banners']['hero'] as $tka_i => $tka_b ) {
					$tka_collect_render( $tka_b['file'], 'اسلاید صفحه اصلی ' . ( $tka_i + 1 ) );
                }
			foreach ( $data['banners']['grid'] as $tka_i => $tka_b ) {
					$tka_collect_render( $tka_b['file'], 'بنر صفحه اصلی ' . ( $tka_i + 1 ) );
                }
			$tka_collect_render( $data['banners']['strip']['file'], 'نوار بالای سایت' );

			$xml .= $tka_att_buffer;

			/* ---------- محصولات ---------- */
			foreach ( $data['products'] as $tka_i => $tka_p ) {
					$tka_id     = 200 + $tka_i;
					$tka_date   = gmdate( 'Y-m-d H:i:s', $base - 60 - $tka_i * 60 );
					$tka_cspec  = isset( $tka_commerce['products'][ $tka_p['slug'] ] ) ? $tka_commerce['products'][ $tka_p['slug'] ] : array();
					$tka_is_var = ! empty( $tka_cspec['variations'] );
					$tka_reg    = (string) ( $tka_p['old'] ?? $tka_p['price'] );
					$tka_sale   = ! empty( $tka_p['old'] ) ? (string) $tka_p['price'] : '';

					$xml .= toykindangel_demo_wxr_item_head( $tka_id, $tka_p['name'], 'product', $tka_date, $tka_p['slug'], $home . '?tka-demo=product-' . $tka_p['slug'] );
					$xml .= "\t\t<content:encoded><![CDATA[<p>" . $tka_p['name'] . " با کیفیت ساخت بالا و ضمانت سلامت فیزیکی کالا — مناسب هدیه و بازی روزمره کودکان. خرید از فروشگاه فرشته مهربون با ارسال سریع.</p>]]></content:encoded>\n";
					$xml .= "\t\t<excerpt:encoded><![CDATA[<p>" . $tka_p['name'] . " با کیفیت ساخت بالا و ضمانت سلامت فیزیکی کالا.</p>]]></excerpt:encoded>\n";

					/* دسته‌ها (برگ‌ها) + برند + برچسب‌ها (product_tag) */
					foreach ( (array) ( $tka_p['cats'] ?? array() ) as $tka_cname ) {
					$xml .= "\t\t<category domain=\"product_cat\" nicename=\"" . esc_attr( toykindangel_demo_wxr_cat_slug( $tka_cname ) ) . "\"><![CDATA[$tka_cname]]></category>\n";
					}
					if ( ! empty( $tka_p['brand'] ) ) {
					$tka_bname = $tka_p['brand'];
					$xml .= "\t\t<category domain=\"product_brand\" nicename=\"" . esc_attr( toykindangel_demo_wxr_cat_slug( $tka_bname ) ) . "\"><![CDATA[$tka_bname]]></category>\n";
                        }
					foreach ( (array) ( $tka_cspec['tags'] ?? array() ) as $tka_tag ) {
					$xml .= "\t\t<category domain=\"product_tag\" nicename=\"" . esc_attr( toykindangel_demo_wxr_cat_slug( $tka_tag ) ) . "\"><![CDATA[$tka_tag]]></category>\n";
                        }
					if ( ! empty( $tka_cspec['festival'] ) ) {
					$xml .= "\t\t<category domain=\"product_tag\" nicename=\"festival\"><![CDATA[جشنواره]]></category>\n";
                        }
					if ( $tka_is_var ) {
					/* نوع محصول + ترم‌های ویژگی رنگ/سایز */
					$xml .= "\t\t<category domain=\"product_type\" nicename=\"variable\"><![CDATA[variable]]></category>\n";
					foreach ( (array) $tka_cspec['attrs'] as $tka_ckey => $tka_cnames ) {
							foreach ( (array) $tka_cnames as $tka_cname ) {
							$tka_cslug = isset( $tka_commerce['attributes'][ $tka_ckey ]['terms'][ $tka_cname ] ) ? $tka_commerce['attributes'][ $tka_ckey ]['terms'][ $tka_cname ] : sanitize_title( $tka_cname );
							$xml .= "\t\t<category domain=\"pa_" . esc_attr( $tka_ckey ) . '" nicename="' . esc_attr( $tka_cslug ) . "\"><![CDATA[$tka_cname]]></category>\n";
							}
						}
                        }

					/* قیمت‌ها و وضعیت (والد متغیر قیمتش را از متغیرها می‌گیرد) */
					if ( ! $tka_is_var ) {
					$xml .= toykindangel_demo_wxr_meta( '_regular_price', $tka_reg );
					$xml .= toykindangel_demo_wxr_meta( '_sale_price', $tka_sale );
					$xml .= toykindangel_demo_wxr_meta( '_price', (string) $tka_p['price'] );
					if ( ! empty( $tka_cspec['sale_dates'] ) && $tka_sale ) {
							$xml .= toykindangel_demo_wxr_meta( '_sale_price_dates_from', $tka_cfrom );
							$xml .= toykindangel_demo_wxr_meta( '_sale_price_dates_to', $tka_cto );
						}
					if ( ! empty( $tka_cspec['sale'] ) ) {
							$xml .= toykindangel_demo_wxr_meta( '_regular_price', (string) $tka_cspec['sale']['regular'] );
							$xml .= toykindangel_demo_wxr_meta( '_sale_price', (string) $tka_cspec['sale']['sale'] );
							$xml .= toykindangel_demo_wxr_meta( '_price', (string) $tka_cspec['sale']['sale'] );
							$xml .= toykindangel_demo_wxr_meta( '_sale_price_dates_from', $tka_cfrom );
							$xml .= toykindangel_demo_wxr_meta( '_sale_price_dates_to', $tka_cto );
						}
                        }
					$xml .= toykindangel_demo_wxr_meta( '_stock_status', 'instock' );
					$xml .= toykindangel_demo_wxr_meta( '_manage_stock', ( ! $tka_is_var && ! empty( $tka_cspec['stock'] ) ) ? 'yes' : 'no' );
					if ( ! $tka_is_var && ! empty( $tka_cspec['stock'] ) ) {
					$xml .= toykindangel_demo_wxr_meta( '_stock', (string) $tka_cspec['stock']['qty'] );
					if ( ! empty( $tka_cspec['stock']['low'] ) ) {
							$xml .= toykindangel_demo_wxr_meta( '_low_stock_amount', (string) $tka_cspec['stock']['low'] );
						}
                        }
					if ( ! empty( $tka_cspec['sold_individually'] ) ) {
					$xml .= toykindangel_demo_wxr_meta( '_sold_individually', 'yes' );
                        }
					$xml .= toykindangel_demo_wxr_meta( '_virtual', 'no' );
					$xml .= toykindangel_demo_wxr_meta( '_downloadable', 'no' );
					$xml .= toykindangel_demo_wxr_meta( '_visibility', 'visible' );
					$xml .= toykindangel_demo_wxr_meta( 'total_sales', (string) ( $tka_p['sales'] ?? 0 ) );
					if ( ! empty( $tka_cspec['sku'] ) ) {
					$xml .= toykindangel_demo_wxr_meta( '_sku', (string) $tka_cspec['sku'] );
                        }
					if ( ! empty( $tka_cspec['weight'] ) ) {
					$xml .= toykindangel_demo_wxr_meta( '_weight', (string) $tka_cspec['weight'] );
                        }
					if ( ! empty( $tka_cspec['dims'] ) ) {
					$xml .= toykindangel_demo_wxr_meta( '_length', (string) $tka_cspec['dims'][0] );
					$xml .= toykindangel_demo_wxr_meta( '_width', (string) $tka_cspec['dims'][1] );
					$xml .= toykindangel_demo_wxr_meta( '_height', (string) $tka_cspec['dims'][2] );
                        }
					if ( ! empty( $tka_cspec['festival'] ) ) {
					$xml .= toykindangel_demo_wxr_meta( '_tka_festival', 'yes' );
                        }
					if ( $tka_is_var ) {
					/* متای استاندارد ویژگی‌های والد */
					$tka_pa_meta = array();
					$tka_pa_pos  = 0;
					foreach ( (array) $tka_cspec['attrs'] as $tka_ckey => $tka_cnames ) {
							$tka_pa_meta[ 'pa_' . $tka_ckey ] = array(
									'name'        => 'pa_' . $tka_ckey,
									'value'       => '',
									'position'    => (string) $tka_pa_pos,
									'is_visible'  => 1,
									'is_variation' => 1,
									'is_taxonomy' => 1,
							);
							$tka_pa_pos++;
							}
					$xml .= toykindangel_demo_wxr_meta( '_product_attributes', (string) maybe_serialize( $tka_pa_meta ) );
                        }
					if ( $include_media && ! empty( $tka_seen[ $tka_p['img'] ] ) ) {
					$xml .= toykindangel_demo_wxr_meta( '_thumbnail_id', (string) $tka_seen[ $tka_p['img'] ] );
                        }
					$xml .= "\t</item>\n";

					/* ---------- متغیرهای محصول ---------- */
					if ( $tka_is_var ) {
					foreach ( (array) $tka_cspec['variations'] as $tka_vi => $tka_vspec ) {
							++$tka_vnext;
							$tka_vmeta   = array();
							$tka_vtitle  = array();
							$tka_vhash   = array();
							foreach ( (array) $tka_cspec['attrs'] as $tka_ckey => $tka_cnames ) {
							$tka_vname = isset( $tka_vspec[ $tka_ckey ] ) ? $tka_vspec[ $tka_ckey ] : '';
							$tka_vslug = isset( $tka_commerce['attributes'][ $tka_ckey ]['terms'][ $tka_vname ] ) ? $tka_commerce['attributes'][ $tka_ckey ]['terms'][ $tka_vname ] : sanitize_title( $tka_vname );
							$tka_vmeta[ 'attribute_pa_' . $tka_ckey ] = $tka_vslug;
							$tka_vtitle[] = $tka_commerce['attributes'][ $tka_ckey ]['label'] . ' ' . $tka_vname;
							$tka_vhash[]  = $tka_vslug;
							}
							$tka_vreg  = (string) $tka_vspec['regular'];
							$tka_vsale = ! empty( $tka_vspec['sale'] ) ? (string) $tka_vspec['sale'] : '';
							$tka_vdate = gmdate( 'Y-m-d H:i:s', $base - 30 - $tka_vnext * 30 );

							$xml .= toykindangel_demo_wxr_item_head( $tka_vnext, $tka_p['name'] . ' — ' . implode( '، ', $tka_vtitle ), 'product_variation', $tka_vdate, '', $home . '?tka-demo=var-' . $tka_p['slug'] . '-' . implode( '-', $tka_vhash ), $tka_id );
							$xml .= toykindangel_demo_wxr_meta( '_regular_price', $tka_vreg );
							if ( $tka_vsale ) {
							$xml .= toykindangel_demo_wxr_meta( '_sale_price', $tka_vsale );
							$xml .= toykindangel_demo_wxr_meta( '_price', $tka_vsale );
							$xml .= toykindangel_demo_wxr_meta( '_sale_price_dates_from', $tka_cfrom );
							$xml .= toykindangel_demo_wxr_meta( '_sale_price_dates_to', $tka_cto );
								} else {
							$xml .= toykindangel_demo_wxr_meta( '_sale_price', '' );
							$xml .= toykindangel_demo_wxr_meta( '_price', $tka_vreg );
								}
							$xml .= toykindangel_demo_wxr_meta( '_manage_stock', 'yes' );
							$xml .= toykindangel_demo_wxr_meta( '_stock', (string) ( $tka_vspec['stock'] ?? 5 ) );
							$xml .= toykindangel_demo_wxr_meta( '_stock_status', 'instock' );
							$xml .= toykindangel_demo_wxr_meta( '_backorders', 'no' );
							if ( ! empty( $tka_cspec['sku'] ) ) {
							$xml .= toykindangel_demo_wxr_meta( '_sku', $tka_cspec['sku'] . '-A' . ( $tka_vi + 1 ) );
								}
							foreach ( $tka_vmeta as $tka_vmkey => $tka_vmval ) {
							$xml .= toykindangel_demo_wxr_meta( $tka_vmkey, $tka_vmval );
								}
							$xml .= "\t</item>\n";
						}
                        }
                }

			/* ---------- آیتم‌های منو (نوع custom با URL نسبی — مستقل از ID) ---------- */
			$tka_menu_urls = array(
			'خانه'        => '/',
			'فروشگاه'     => '/shop/',
			'دسته‌بندی‌ها' => '/categories',
			'درباره ما'   => '/about-us',
			'تماس با ما'  => '/contact-us',
			);
			$tka_mi = 0;
			foreach ( $tka_menu_urls as $tka_title => $tka_url ) {
					++$tka_mi;
					$tka_id = 600 + $tka_mi;
					$tka_date = gmdate( 'Y-m-d H:i:s', $base - 1800 - $tka_mi * 30 );
					$xml .= toykindangel_demo_wxr_item_head( $tka_id, $tka_title, 'nav_menu_item', $tka_date, '', $home . '?tka-demo=menu-' . $tka_mi );
					$xml .= "\t\t<category domain=\"nav_menu\" nicename=\"toykindangel-main-menu\"><![CDATA[منوی اصلی فرشته مهربون]]></category>\n";
					$xml .= toykindangel_demo_wxr_meta( '_menu_item_type', 'custom' );
					$xml .= toykindangel_demo_wxr_meta( '_menu_item_menu_item_parent', '0' );
					$xml .= toykindangel_demo_wxr_meta( '_menu_item_object', 'custom' );
					$xml .= toykindangel_demo_wxr_meta( '_menu_item_object_id', (string) $tka_id );
					$xml .= toykindangel_demo_wxr_meta( '_menu_item_url', $tka_url );
					$xml .= toykindangel_demo_wxr_meta( '_menu_item_target', '' );
					$xml .= toykindangel_demo_wxr_meta( '_menu_item_classes', 'a:0:{}' );
					$xml .= toykindangel_demo_wxr_meta( '_menu_item_xfn', '' );
					$xml .= "\t</item>\n";
                }

			$xml .= "</channel>\n</rss>\n";
			return $xml;
        }
}

add_action(
        'admin_post_tka_demo_wxr',
        function () {
                if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( 'دسترسی مجاز نیست.' );
                }
                check_admin_referer( 'tka_demo_wxr' );

                $xml = toykindangel_demo_wxr_build();
                nocache_headers();
                header( 'Content-Type: application/xml; charset=utf-8' );
                header( 'Content-Disposition: attachment; filename=toykindangel-demo-content.xml' );
                header( 'Content-Length: ' . strlen( $xml ) );
                echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WXR XML document
                exit;
        }
);

/* -------------------------------------------------------------------------
 * Admin page: نمایش → درج محتوای نمونه
 * ---------------------------------------------------------------------- */

add_action(
        'admin_menu',
        function () {
                add_theme_page(
                        'درج محتوای نمونه فرشته مهربون',
                        'درج محتوای نمونه',
                        'manage_options',
                        'toykindangel-demo-import',
                        'toykindangel_demo_render_page'
                );
        }
);

if ( ! function_exists( 'toykindangel_demo_render_page' ) ) {
        /**
         * Render the demo-import admin screen.
         *
         * @return void
         */
        function toykindangel_demo_render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

		$tka_done     = get_option( 'tka_demo_imported' );
		$tka_set_done = get_option( 'tka_settings_imported' );
		$tka_stats    = null;
		$tka_set_stat = null;

		if ( isset( $_POST['tka_demo_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tka_demo_nonce'] ) ), 'tka_demo_import' ) ) {
				$tka_stats = toykindangel_demo_import();
				$tka_done  = get_option( 'tka_demo_imported' );
			}
		if ( isset( $_POST['tka_settings_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tka_settings_nonce'] ) ), 'tka_settings_import' ) ) {
				$tka_set_stat = toykindangel_demo_settings_import();
				$tka_set_done = get_option( 'tka_settings_imported' );
			}
		?>
                <div class="wrap">
                        <h1><?php esc_html_e( 'درج محتوای نمونه فرشته مهربون', 'toykindangel' ); ?></h1>

		<?php if ( $tka_stats ) : ?>
                                <?php if ( $tka_stats['ok'] ) : ?>
                                        <div class="notice notice-success is-dismissible">
                                                <p><strong><?php echo esc_html( $tka_stats['msg'] ); ?></strong></p>
                                                <p>
                                                        <?php esc_html_e( 'محصولات:', 'toykindangel' ); ?> <?php echo (int) $tka_stats['products']; ?> —
                                                        <?php esc_html_e( 'دسته‌ها', 'toykindangel' ); ?> <?php echo (int) $tka_stats['terms']; ?> —
                                                        <?php esc_html_e( 'رسانه‌ها', 'toykindangel' ); ?> <?php echo (int) $tka_stats['media']; ?> —
                                                        <?php esc_html_e( 'برگه‌ها', 'toykindangel' ); ?> <?php echo (int) $tka_stats['pages']; ?> —
                                                        <?php esc_html_e( 'آیتم منو', 'toykindangel' ); ?> <?php echo (int) $tka_stats['menu']; ?>
                                                        <?php
                                                        if ( ! empty( $tka_stats['attributes'] ) ) :
?>
<?php esc_html_e( '— ویژگی (رنگ/سایز):', 'toykindangel' ); ?> <?php echo (int) $tka_stats['attributes']; ?><?php endif; ?>
                                                        <?php
                                                        if ( isset( $tka_stats['variations'] ) && $tka_stats['variations'] > 0 ) :
?>
<?php esc_html_e( '— متغیر ساخته‌شده:', 'toykindangel' ); ?> <?php echo (int) $tka_stats['variations']; ?><?php endif; ?>
                                                </p>
                                        </div>
                                <?php else : ?>
                                        <div class="notice notice-error"><p><?php echo esc_html( $tka_stats['msg'] ); ?></p></div>
                                <?php endif; ?>
                        <?php endif; ?>

<?php if ( $tka_set_stat ) : ?>
                                <?php if ( $tka_set_stat['ok'] ) : ?>
                                        <div class="notice notice-success is-dismissible">
                                                <p><strong><?php echo esc_html( $tka_set_stat['msg'] ); ?></strong></p>
                                                <p>
                                                        <?php esc_html_e( 'برگه‌های ساخته/بررسی‌شده', 'toykindangel' ); ?> <?php echo (int) $tka_set_stat['pages']; ?>
                                                        <?php if ( ! empty( $tka_set_stat['settings'] ) ) : ?>
                                                                <?php esc_html_e( '— تنظیمات اعمال‌شده:', 'toykindangel' ); ?> <?php echo esc_html( implode( '، ', $tka_set_stat['settings'] ) ); ?>
                                                        <?php else : ?>
                                                                <?php esc_html_e( '— همهٔ تنظیمات لازم از قبل درست بود.', 'toykindangel' ); ?>
                                                        <?php endif; ?>
                                                </p>
                                        </div>
                                <?php else : ?>
                                        <div class="notice notice-error"><p><?php echo esc_html( $tka_set_stat['msg'] ); ?></p></div>
                                <?php endif; ?>
                        <?php endif; ?>

<?php if ( ! taxonomy_exists( 'product_cat' ) ) : ?>
                                <div class="notice notice-warning"><p><?php esc_html_e( 'برای درون‌ریزی کامل دمو، افزونه', 'toykindangel' ); ?> <strong><?php esc_html_e( 'ووکامرس', 'toykindangel' ); ?></strong> <?php esc_html_e( 'باید نصب و فعال باشد. (گزینهٔ «فقط برگه‌ها و تنظیمات» بدون ووکامرس هم برگه‌ها را می‌سازد.)', 'toykindangel' ); ?></p></div>
                        <?php endif; ?>

                        <p style="max-width:720px;line-height:1.9">
<?php esc_html_e( 'طبق استاندارد قالب‌های وردپرس،', 'toykindangel' ); ?> <strong><?php esc_html_e( 'تا زمانی که دمو درون‌ریزی نشده باشد صفحه اصلی چیزی از دمو نشان نمی‌دهد', 'toykindangel' ); ?></strong>
<?php esc_html_e( '(فقط کارت راه‌اندازی). دو راه دارید:', 'toykindangel' ); ?>
                        </p>

                        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;max-width:960px">
                                <!-- ۱) درون‌ریزی کامل دمو -->
                                <div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px">
                                        <h2 style="margin-top:0"><?php esc_html_e( '۱) درون‌ریزی کامل دمو', 'toykindangel' ); ?></h2>
                                        <p style="line-height:1.9">
<?php esc_html_e( 'کل محتوای دمو بازسازی می‌شود: منوی اصلی، درخت کامل دسته‌بندی‌ها، برندها،', 'toykindangel' ); ?>
<?php echo count( toykindangel_demo_data()['products'] ); ?> محصول با تصویر، برگه‌های
<?php esc_html_e( 'درباره ما/تماس با ما/دسته‌بندی‌ها، برگه‌های ووکامرس و بنرهای صفحه اصلی.', 'toykindangel' ); ?>
<?php esc_html_e( 'همه تصاویر از خود قالب بارگذاری می‌شوند و', 'toykindangel' ); ?> <strong><?php esc_html_e( 'هیچ لینکی به سایت خارجی نمی‌ماند', 'toykindangel' ); ?></strong>.
<?php esc_html_e( 'اجرای دوباره بی‌خطر است.', 'toykindangel' ); ?>
                                        </p>
                                        <form method="post">
<?php wp_nonce_field( 'tka_demo_import', 'tka_demo_nonce' ); ?>
                                                <p style="margin-bottom:0">
                                                        <button type="submit" class="button button-primary button-hero" <?php disabled( ! taxonomy_exists( 'product_cat' ) ); ?>>
<?php echo $tka_done ? esc_html__( 'اجرای دوباره درون‌ریزی (بی‌خطر)', 'toykindangel' ) : esc_html__( 'شروع درون‌ریزی محتوای نمونه', 'toykindangel' ); ?>
                                                        </button>
                                                </p>
                                        </form>
<?php if ( $tka_done ) : ?>
                                                <p style="color:#46b450;font-weight:600;margin:10px 0 0">
                                                        <?php esc_html_e( '✔ درون‌ریزی نسخه', 'toykindangel' ); ?> <?php echo esc_html( $tka_done ); ?> <?php esc_html_e( 'قبلاً انجام شده است.', 'toykindangel' ); ?>
                                                </p>
                                        <?php endif; ?>
                                </div>

                                <!-- ۲) فقط برگه‌ها و تنظیمات -->
                                <div id="tka-settings-only" style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px">
                                        <h2 style="margin-top:0"><?php esc_html_e( '۲) فقط برگه‌ها و تنظیمات', 'toykindangel' ); ?> <span style="font-weight:400;color:#787c82"><?php esc_html_e( '(بدون محصول و منو)', 'toykindangel' ); ?></span></h2>
                                        <p style="line-height:1.9">
<?php esc_html_e( 'اگر می‌خواهید فروشگاه را با محصولات خودتان بسازید، این گزینه فقط', 'toykindangel' ); ?>
                                                <strong><?php esc_html_e( 'برگه‌های لازم', 'toykindangel' ); ?></strong> <?php esc_html_e( 'را می‌سازد (فروشگاه، سبد خرید، تسویه‌حساب، حساب کاربری،', 'toykindangel' ); ?>
                                                درباره ما، تماس با ما، دسته‌بندی‌ها، بازگشت کالا) و
                                                <strong><?php esc_html_e( 'تنظیمات لازم', 'toykindangel' ); ?></strong> <?php esc_html_e( 'را اعمال می‌کند (پیوند یکتا در صورت خالی‌بودن).', 'toykindangel' ); ?>
<?php esc_html_e( 'هیچ محصول، دسته‌بندی، برند یا منویی ساخته نمی‌شود.', 'toykindangel' ); ?>
                                        </p>
                                        <form method="post">
<?php wp_nonce_field( 'tka_settings_import', 'tka_settings_nonce' ); ?>
                                                <p style="margin-bottom:0">
                                                        <button type="submit" class="button button-secondary button-hero">
<?php echo $tka_set_done ? esc_html__( 'اجرای دوباره (بی‌خطر)', 'toykindangel' ) : esc_html__( 'اعمال برگه‌ها و تنظیمات', 'toykindangel' ); ?>
                                                        </button>
                                                </p>
                                        </form>
<?php if ( $tka_set_done ) : ?>
                                                <p style="color:#46b450;font-weight:600;margin:10px 0 0">
                                                        <?php esc_html_e( '✔ تنظیمات نسخه', 'toykindangel' ); ?> <?php echo esc_html( $tka_set_done ); ?> <?php esc_html_e( 'اعمال شده است.', 'toykindangel' ); ?>
                                                </p>
                                        <?php endif; ?>
                                        <p style="font-size:12px;color:#787c82;line-height:1.9;margin:12px 0 0">
<?php esc_html_e( 'نکته: پس از این حالت، برای نمایش سکشن‌های صفحه اصلی یا محصول خودتان را منتشر کنید', 'toykindangel' ); ?>
                                                یا از <a href="<?php echo esc_url( admin_url( 'customize.php' ) ); ?>"><?php esc_html_e( 'سفارشی‌سازی ← سکشن‌های صفحه اول', 'toykindangel' ); ?></a>
<?php esc_html_e( 'گزینهٔ «نمایش صفحه اصلی بدون درون‌ریزی دمو» را روشن کنید.', 'toykindangel' ); ?>
                                        </p>
                                </div>
                        </div>

                        <hr style="max-width:960px" />
                        <h2><?php esc_html_e( 'فایل ایمپورت استاندارد وردپرس (WXR)', 'toykindangel' ); ?></h2>
                        <p style="max-width:720px;line-height:1.9">
<?php esc_html_e( 'اگر می‌خواهید محتوای نمونه را با ابزار استاندارد وردپرس بازسازی کنید', 'toykindangel' ); ?>
                                (<code>ابزار → وارد کردن → WordPress</code>)، فایل XML دمو را دانلود کنید:
<?php esc_html_e( 'شامل', 'toykindangel' ); ?> <?php echo count( toykindangel_demo_data()['products'] ); ?> <?php esc_html_e( 'محصول با قیمت و تصویر،', 'toykindangel' ); ?>
<?php esc_html_e( 'دسته‌های محصول، برگه‌ها و منوی اصلی. تصاویر از پوشهٔ خود قالب خوانده می‌شوند.', 'toykindangel' ); ?>
<?php esc_html_e( 'پس از ایمپورت، منو را از', 'toykindangel' ); ?> <code><?php esc_html_e( 'نمایش → فهرست‌ها', 'toykindangel' ); ?></code> <?php esc_html_e( 'به جایگاه «فهرست اصلی» اختصاص دهید', 'toykindangel' ); ?>
<?php esc_html_e( 'یا یک‌بار دکمهٔ درون‌ریزی بالا را اجرا کنید (بی‌خطر است و تنظیمات را کامل می‌کند).', 'toykindangel' ); ?>
                        </p>
                        <p>
                                <a class="button button-secondary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=tka_demo_wxr' ), 'tka_demo_wxr' ) ); ?>">
<?php esc_html_e( 'دانلود فایل toykindangel-demo-content.xml', 'toykindangel' ); ?>
                                </a>
                        </p>
                </div>
<?php
        }
}
