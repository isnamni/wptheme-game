<?php
/**
 * SEO: JSON-LD structured data (Organization, WebSite, BreadcrumbList).
 * Skipped automatically when RankMath/Yoast handles schema.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/**
 * Whether an SEO plugin already outputs JSON-LD.
 *
 * v0.20.1 audit fix H5 — class_exists('WPSEO_Frontend') was removed in Yoast 14
 * (the class no longer exists in any modern Yoast version), so the check always
 * returned false under Yoast and the theme emitted duplicate JSON-LD / OG tags
 * alongside Yoast's own output. The stable Yoast detection since 14.x is the
 * WPSEO_VERSION constant. The legacy class check is kept as a belt-and-braces
 * fallback for very old Yoast installs, but it no longer gates the result.
 */
function toykindangel_seo_plugin_active() {
        return defined( 'WPSEO_VERSION' )
                || class_exists( 'WPSEO_Frontend' )
                || defined( 'RANK_MATH_VERSION' )
                || defined( 'AIOSEO_VERSION' );
}

/**
 * Print WebSite + Organization JSON-LD sitewide.
 */
function toykindangel_jsonld_site() {
        if ( toykindangel_seo_plugin_active() ) {
                return;
        }

        $data = array(
                '@context' => 'https://schema.org',
                '@type'    => 'WebSite',
                'name'     => get_bloginfo( 'name' ),
                'url'      => home_url( '/' ),
                'inLanguage' => get_locale(),
                'potentialAction' => array(
                        '@type'       => 'SearchAction',
                        'target'      => home_url( '/?s={search_term_string}' ),
                        'query-input' => 'required name=search_term_string',
                ),
        );

        $phone = get_theme_mod( 'tka_phone' );
        $email = get_theme_mod( 'tka_email' );

        $org = array(
                '@type' => 'Organization',
                'name'  => get_bloginfo( 'name' ),
                'url'   => home_url( '/' ),
        );

        if ( $phone ) {
                $org['telephone'] = $phone;
        }
        if ( $email ) {
                $org['email'] = $email;
        }

        $sameas = array_filter(
                array(
                        get_theme_mod( 'tka_instagram' ),
                        get_theme_mod( 'tka_telegram' ),
                        get_theme_mod( 'tka_whatsapp' ),
                        get_theme_mod( 'tka_aparat' ),
                )
        );
        if ( $sameas ) {
                $org['sameAs'] = array_values( $sameas );
        }

        echo '<script type="application/ld+json">' .
                wp_json_encode(
                        array(
                                '@context'           => 'https://schema.org',
                                '@graph'             => array( $data, $org ),
                        ),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ) . '</script>' . "\n";
}
add_action( 'wp_head', 'toykindangel_jsonld_site', 5 );

/**
 * BreadcrumbList JSON-LD (posts, pages, archives, WooCommerce-ready hook).
 */
function toykindangel_jsonld_breadcrumbs() {
        if ( toykindangel_seo_plugin_active() || is_front_page() ) {
                return;
        }

        $items = array(
                array(
                        '@type'    => 'ListItem',
                        'position' => 1,
                        'name'     => __( 'خانه', 'toykindangel' ),
                        'item'     => home_url( '/' ),
                ),
        );

        $position = 2;

        /* WooCommerce archives get the full chain: خانه → فروشگاه → parent… → term. */
        if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
                $shop_link = get_permalink( wc_get_page_id( 'shop' ) );
                if ( $shop_link ) {
                        $items[] = array(
                                '@type'    => 'ListItem',
                                'position' => $position++,
                                'name'     => __( 'فروشگاه', 'toykindangel' ),
                                'item'     => $shop_link,
                        );
                }

                $tka_term = get_queried_object();
                if ( $tka_term instanceof WP_Term ) {
                        $tka_chain = array( $tka_term );
                        $tka_parent = $tka_term->parent ? get_term( $tka_term->parent ) : null;
                        while ( $tka_parent && ! is_wp_error( $tka_parent ) && $tka_parent->term_id ) {
                                array_unshift( $tka_chain, $tka_parent );
                                $tka_parent = $tka_parent->parent ? get_term( $tka_parent->parent ) : null;
                        }
                        foreach ( $tka_chain as $tka_i => $tka_c ) {
                                $tka_last = ( count( $tka_chain ) - 1 === $tka_i );
                                $tka_entry = array(
                                        '@type'    => 'ListItem',
                                        'position' => $position++,
                                        'name'     => $tka_c->name,
                                );
                                if ( ! $tka_last ) {
                                        $tka_link = get_term_link( $tka_c );
                                        if ( ! is_wp_error( $tka_link ) ) {
                                                $tka_entry['item'] = $tka_link;
                                        }
                                }
                                $items[] = $tka_entry;
                        }
                }
        } elseif ( is_singular() ) {
                $post_type = get_post_type();
                if ( 'post' === $post_type ) {
                        $blog_page = get_option( 'page_for_posts' );
                        if ( $blog_page ) {
                                $items[] = array(
                                        '@type'    => 'ListItem',
                                        'position' => $position++,
                                        'name'     => get_the_title( $blog_page ),
                                        'item'     => get_permalink( $blog_page ),
                                );
                        }
                } elseif ( 'page' !== $post_type && post_type_exists( $post_type ) ) {
                        $pto = get_post_type_object( $post_type );
                        if ( $pto && ! empty( $pto->has_archive ) ) {
                                $items[] = array(
                                        '@type'    => 'ListItem',
                                        'position' => $position++,
                                        'name'     => $pto->labels->name,
                                        'item'     => get_post_type_archive_link( $post_type ),
                                );
                        }
                }
                $items[] = array(
                        '@type'    => 'ListItem',
                        'position' => $position++,
                        'name'     => get_the_title(),
                );
        } elseif ( is_archive() ) {
                $items[] = array(
                        '@type'    => 'ListItem',
                        'position' => $position++,
                        'name'     => wp_strip_all_tags( get_the_archive_title() ),
                );
        } elseif ( is_search() ) {
                $items[] = array(
                        '@type'    => 'ListItem',
                        'position' => $position++,
                        'name'     => __( 'نتایج جستجو', 'toykindangel' ),
                );
        }

        if ( count( $items ) < 2 ) {
                return;
        }

        echo '<script type="application/ld+json">' .
                wp_json_encode(
                        array(
                                '@context'        => 'https://schema.org',
                                '@type'           => 'BreadcrumbList',
                                'itemListElement' => $items,
                        ),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ) . '</script>' . "\n";
}
add_action( 'wp_head', 'toykindangel_jsonld_breadcrumbs', 5 );

/**
 * Print Product JSON-LD on single product pages.
 *
 * Runs only when no major SEO plugin is active (same guard as the other
 * schema blocks) so RankMath/Yoast can take over without duplicates.
 */
function toykindangel_jsonld_product() {
        if ( toykindangel_seo_plugin_active() || ! function_exists( 'wc_get_product' ) ) {
                return;
        }
        if ( ! is_singular( 'product' ) ) {
                return;
        }

        $product = wc_get_product( get_the_ID() );
        if ( ! $product ) {
                return;
        }

        /*
         * v0.20.0 audit fix H4 — WooCommerce core's WC_Structured_Data also
         * emits a Product JSON-LD in wp_footer (woocommerce_structured_data
         * → WC()->structured_data->output_structured_data, hooked at
         * priority 10 on wp_footer). Without an SEO plugin, Google would
         * see TWO Product schemas on the PDP with different field sets,
         * which Search Console flags as duplication. Unhook WC's output
         * here so the theme is the single source of Product schema on the
         * PDP (and only on the PDP — every other page keeps WC's schema).
         */
        if ( function_exists( 'WC' ) && WC()->structured_data ) {
                remove_action( 'wp_footer', array( WC()->structured_data, 'output_structured_data' ), 10 );
        }

        /* Images (featured + gallery). */
        $images = array();
        $ids    = array_filter( array_merge( array( (int) $product->get_image_id() ), $product->get_gallery_image_ids() ) );
        foreach ( $ids as $id ) {
                $url = wp_get_attachment_image_url( $id, 'large' );
                if ( $url ) {
                        $images[] = $url;
                }
        }

        /* Offer block. */
        $price = $product->get_price();
        $offer = array(
                '@type'         => 'Offer',
                'url'           => get_permalink( $product->get_id() ),
                'priceCurrency' => get_woocommerce_currency(),
                'itemCondition' => 'https://schema.org/NewCondition',
                'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        );
        if ( '' !== $price ) {
                $offer['price']        = wc_format_decimal( $price, wc_get_price_decimals() );
                $offer['priceValidUntil'] = gmdate( 'Y-12-31', time() + YEAR_IN_SECONDS );
        }

        $data = array(
                '@context' => 'https://schema.org',
                '@type'    => 'Product',
                'name'     => $product->get_name(),
                'url'      => get_permalink( $product->get_id() ),
                'sku'      => $product->get_sku() ? $product->get_sku() : (string) $product->get_id(),
                'offers'   => $offer,
        );

        $description = $product->get_short_description();
        if ( ! $description ) {
                $description = wp_trim_words( wp_strip_all_tags( $product->get_description() ), 40 );
        }
        if ( $description ) {
                $data['description'] = $description;
        }
        if ( $images ) {
                $data['image'] = $images;
        }

        /* Brand: the first product category acts as the brand line. */
        $tka_terms = get_the_terms( $product->get_id(), 'product_cat' );
        if ( $tka_terms && ! is_wp_error( $tka_terms ) && isset( $tka_terms[0]->name ) ) {
                $data['brand'] = array(
                        '@type' => 'Brand',
                        'name'  => wp_strip_all_tags( $tka_terms[0]->name ),
                );
        }

        /* Aggregate rating (only when real reviews exist). */
        $count = (int) $product->get_rating_count();
        if ( $count > 0 ) {
                $data['aggregateRating'] = array(
                        '@type'       => 'AggregateRating',
                        'ratingValue' => (float) $product->get_average_rating(),
                        'reviewCount' => $count,
                );
        }

        echo '<script type="application/ld+json">' .
                wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) .
                '</script>' . "\n";
}
add_action( 'wp_head', 'toykindangel_jsonld_product', 5 );

/**
 * Print ItemList JSON-LD on the shop + product_cat archives.
 *
 * Search engines use it to understand the product listing order.
 * Skipped when an SEO plugin is active (same guard as the rest).
 */
function toykindangel_jsonld_itemlist() {
        if ( toykindangel_seo_plugin_active() || ! function_exists( 'wc_get_product' ) ) {
                return;
        }
        if ( ! is_shop() && ! is_product_category() ) {
                return;
        }

        $items = array();
        $position = 1;

        /* woocommerce_archive_description runs inside the loop-safe spot;
         * the loop itself is available at wp_head for the main query. */
        while ( have_posts() && $position <= 24 ) {
                the_post();
                $product = wc_get_product( get_the_ID() );
                if ( ! $product || ! $product->is_visible() ) {
                        continue;
                }

                $item = array(
                        '@type'    => 'ListItem',
                        'position' => $position++,
                        'name'     => $product->get_name(),
                        'url'      => get_permalink( $product->get_id() ),
                );

                $price = $product->get_price();
                if ( '' !== $price ) {
                        $item['item'] = array(
                                '@type'  => 'Product',
                                'name'   => $product->get_name(),
                                'url'    => get_permalink( $product->get_id() ),
                                'offers' => array(
                                        '@type'         => 'Offer',
                                        'price'         => wc_format_decimal( $price, wc_get_price_decimals() ),
                                        'priceCurrency' => get_woocommerce_currency(),
                                        'availability'  => $product->is_in_stock() ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
                                ),
                        );
                }

                $items[] = $item;
        }

        if ( count( $items ) < 2 ) {
                rewind_posts();
                return;
        }

        rewind_posts();

        echo '<script type="application/ld+json">' .
                wp_json_encode(
                        array(
                                '@context'        => 'https://schema.org',
                                '@type'           => 'ItemList',
                                'itemListElement' => $items,
                        ),
                        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
                ) . '</script>' . "\n";
}
add_action( 'wp_head', 'toykindangel_jsonld_itemlist', 5 );
