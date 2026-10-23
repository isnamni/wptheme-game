<?php
/**
 * Template Name: فرشته مهربون — علاقه‌مندی‌ها
 * Template Post Type: page
 *
 * Wishlist page — the product IDs live in the browser's localStorage
 * (`tka_favs`, see main.js); main.js fetches real WooCommerce cards for
 * them through the tka_wishlist_render AJAX endpoint. The page itself
 * renders instantly with a skeleton grid (no JS-dependent blank page).
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();
?>

        <main class="section tka-wishlist" id="tka-wishlist-page" aria-labelledby="tka-wishlist-title">
                <div class="section__head">
                        <span class="section__title" id="tka-wishlist-title">
                                <svg class="ic" aria-hidden="true"><use href="#i-heart"></use></svg>
                                <?php esc_html_e( 'علاقه‌مندی‌های من', 'toykindangel' ); ?>
                        </span>
                        <span class="tka-wishlist__count num" id="tka-wish-count" aria-live="polite"></span>
                </div>

                <div class="plist tka-wishlist__grid" id="tkaWishGrid" aria-busy="true">
                        <div class="tka-wishlist__loading" id="tkaWishLoading">
                                <span class="spinner is-active" aria-hidden="true"></span>
                                <?php esc_html_e( 'در حال بارگذاری علاقه‌مندی‌ها…', 'toykindangel' ); ?>
                        </div>
                </div>

                <div class="tka-wishlist__empty" id="tkaWishEmpty" hidden>
                        <span class="tka-wishlist__empty-ic" aria-hidden="true">
                                <svg class="ic"><use href="#i-heart"></use></svg>
                        </span>
                        <b><?php esc_html_e( 'هنوز چیزی به علاقه‌مندی‌ها اضافه نکرده‌اید', 'toykindangel' ); ?></b>
                        <small><?php esc_html_e( 'روی قلبِ هر محصول بزنید تا اینجا ذخیره شود.', 'toykindangel' ); ?></small>
                        <a class="btn-primary" href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/' ) ); ?>">
                                <?php esc_html_e( 'رفتن به فروشگاه', 'toykindangel' ); ?>
                        </a>
                </div>
        </main>

<?php
get_footer();
