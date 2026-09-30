<?php
/**
 * SEO: meta description, canonical, Open Graph and Twitter cards.
 *
 * All tags are skipped automatically when RankMath / Yoast / AIOSEO is
 * active — those plugins print richer versions of the same tags and we
 * never want duplicates.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/* toykindangel_seo_plugin_active() lives in inc/seo-schema.php which is
 * always loaded together with this file (see functions.php). */

/**
 * Build the meta description for the current view.
 *
 * Priority: manual excerpt → trimmed content → archive description →
 * tagline (front page).
 *
 * @return string
 */
function toykindangel_meta_description() {
        $desc = '';

        if ( is_singular() ) {
                $post = get_post();
                if ( $post ) {
                        if ( '' !== trim( (string) $post->post_excerpt ) ) {
                                $desc = $post->post_excerpt;
                        } else {
                                $desc = wp_strip_all_tags( $post->post_content );
                        }
                }

                /* Product: prefer the short description when present. */
                if ( function_exists( 'wc_get_product' ) && is_singular( 'product' ) ) {
                        $product = wc_get_product( get_the_ID() );
                        if ( $product && $product->get_short_description() ) {
                                $desc = wp_strip_all_tags( $product->get_short_description() );
                        }
                }
        } elseif ( is_category() || is_tag() || is_tax() ) {
                $desc = term_description();
        } elseif ( is_front_page() ) {
                $desc = get_bloginfo( 'description' );

                /* Shops usually want a richer front-page description. */
                if ( ! $desc ) {
                        $desc = sprintf(
                                /* translators: %s: site name. */
                                __( 'فروشگاه اینترنتی %s — خرید آنلاین اسباب‌بازی و سیسمونی با ارسال سریع و ضمانت اصالت کالا', 'toykindangel' ),
                                get_bloginfo( 'name' )
                        );
                }
        }

        $desc = trim( preg_replace( '/\s+/u', ' ', (string) $desc ) );

        if ( ! $desc ) {
                return '';
        }

        return wp_trim_words( $desc, 30, '…' );
}

/**
 * Resolve the best social share image for the current view.
 *
 * Product image → post thumbnail → customizer strip/hero → site logo →
 * site icon. Returns an absolute URL or ''.
 *
 * @return string
 */
function toykindangel_share_image() {
        $url = '';

        if ( is_singular() ) {
                if ( function_exists( 'wc_get_product' ) && is_singular( 'product' ) ) {
                        $product = wc_get_product( get_the_ID() );
                        if ( $product && $product->get_image_id() ) {
                                $url = wp_get_attachment_image_url( $product->get_image_id(), 'large' );
                        }
                }

                if ( ! $url && has_post_thumbnail() ) {
                        $url = get_the_post_thumbnail_url( null, 'large' );
                }
        }

        if ( ! $url ) {
                /* Customizer strip/hero images double as the brand share image. */
                $url = get_theme_mod( 'tka_strip_img' );
                if ( ! $url ) {
                        $url = get_theme_mod( 'tka_hero_1_img' );
                }
        }

        if ( ! $url ) {
                $logo_id = get_theme_mod( 'custom_logo' );
                if ( $logo_id ) {
                        $url = wp_get_attachment_image_url( (int) $logo_id, 'full' );
                }
        }

        if ( ! $url ) {
                $icon_id = get_option( 'site_icon' );
                if ( $icon_id ) {
                        $url = wp_get_attachment_image_url( (int) $icon_id, 'full' );
                }
        }

        return $url ? esc_url_raw( set_url_scheme( $url ) ) : '';
}

/**
 * Compute the canonical URL for the current non-singular view
 * (page 1 and paged archives: /page/N/ appended).
 *
 * @return string
 */
function toykindangel_current_canonical() {
        $canon = '';

        if ( is_front_page() ) {
                $canon = home_url( '/' );
        } elseif ( is_category() || is_tag() || is_tax() ) {
                $link = get_term_link( get_queried_object() );
                if ( ! is_wp_error( $link ) ) {
                        $canon = $link;
                }
        } elseif ( is_post_type_archive() ) {
                $canon = get_post_type_archive_link( get_query_var( 'post_type' ) );
        }

        if ( $canon && is_paged() ) {
                $paged = max( 2, (int) get_query_var( 'paged' ) );
                $canon = user_trailingslashit( trailingslashit( $canon ) . 'page/' . $paged );
        }

        return $canon;
}

/**
 * Print the robots meta (noindex for search / 404 / paged archives).
 *
 * WordPress core only noindexes search via plugins; without it, /?s=*
 * and /page/2/ dumps dilute crawl budget and create duplicate content.
 */
function toykindangel_robots_meta() {
        if ( toykindangel_seo_plugin_active() ) {
                return;
        }

        $noindex = is_search() || is_404();

        /* Archive pages beyond page 1: thin, duplicate-first-page content. */
        if ( ! $noindex && is_paged() && ! is_singular() ) {
                $noindex = true;
        }

        if ( $noindex ) {
                echo '<meta name="robots" content="noindex, follow">' . "\n";
        }
}
add_action( 'wp_head', 'toykindangel_robots_meta', 1 );

/**
 * Print meta description + canonical.
 */
function toykindangel_head_meta() {
        if ( toykindangel_seo_plugin_active() ) {
                return;
        }

        $desc = toykindangel_meta_description();
        if ( $desc ) {
                printf( '<meta name="description" content="%s">' . "\n", esc_attr( $desc ) );
        }

        /* Canonical: WP core only covers singular; add archives + search. */
        if ( is_singular() ) {
                return; // rel_canonical() already prints it.
        }

        if ( is_search() ) {
                return; // Noindex-style: no canonical for search results.
        }

        $canon = toykindangel_current_canonical();

        if ( $canon ) {
                printf( '<link rel="canonical" href="%s">' . "\n", esc_url( $canon ) );
        }
}
add_action( 'wp_head', 'toykindangel_head_meta', 1 );

/**
 * Print Open Graph + Twitter card tags.
 */
function toykindangel_og_meta() {
        if ( toykindangel_seo_plugin_active() ) {
                return;
        }

        $title = wp_strip_all_tags( get_the_title() );
        if ( is_front_page() ) {
                $title = get_bloginfo( 'name' );
                $desc  = get_bloginfo( 'description' );
        } elseif ( is_archive() ) {
                $title = wp_strip_all_tags( get_the_archive_title() );
        } elseif ( is_search() ) {
                /* translators: %s: search query. */
                $title = sprintf( __( 'نتایج جستجو برای «%s»', 'toykindangel' ), get_search_query( false ) );
        }

        $desc = toykindangel_meta_description();
        $type = is_singular( 'post' ) ? 'article' : 'website';

        /* og:url follows the canonical of every view (archives included). */
        $url = '';
        if ( is_singular() ) {
                $url = get_permalink();
        } elseif ( is_front_page() ) {
                $url = home_url( '/' );
        } elseif ( ! is_search() ) {
                $url = toykindangel_current_canonical();
        }

        if ( function_exists( 'is_product' ) && is_product() ) {
                $type = 'product';
        }

        $img = toykindangel_share_image();

        $tags = array(
                'og:locale'     => get_locale(),
                'og:type'       => $type,
                'og:site_name'  => get_bloginfo( 'name' ),
        );

        if ( $title ) {
                $tags['og:title'] = $title;
        }
        if ( $desc ) {
                $tags['og:description'] = $desc;
        }
        if ( $url ) {
                $tags['og:url'] = $url;
        }
        if ( $img ) {
                $tags['og:image'] = $img;

                /* Real dimensions help networks render the preview instantly. */
                $img_id = attachment_url_to_postid( $img );
                if ( $img_id ) {
                        $meta = wp_get_attachment_metadata( $img_id );
                        if ( ! empty( $meta['width'] ) ) {
                                $tags['og:image:width'] = (int) $meta['width'];
                        }
                        if ( ! empty( $meta['height'] ) ) {
                                $tags['og:image:height'] = (int) $meta['height'];
                        }
                }

                $tags['twitter:card'] = 'summary_large_image';
                $tags['twitter:image'] = $img;
        } else {
                $tags['twitter:card'] = 'summary';
        }

        $out = '';
        foreach ( $tags as $property => $value ) {
                if ( is_null( $value ) || '' === $value ) {
                        continue;
                }
                $out .= sprintf(
                        '<meta property="%1$s" content="%2$s">' . "\n",
                        esc_attr( $property ),
                        esc_attr( is_scalar( $value ) ? (string) $value : '' )
                );
        }

        echo $out; // phpcs:ignore WordPress.Security.EscapeOutput -- Built above with esc_attr().
}
add_action( 'wp_head', 'toykindangel_og_meta', 2 );
