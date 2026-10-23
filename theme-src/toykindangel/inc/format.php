<?php
/**
 * ToyKind Angel — Persian numerals & Jalali (شمسی) dates.
 *
 * Display-layer only: stored prices/dates are never touched.
 *
 * - toykindangel_fa_num()      converts 0-9 → ۰-۹ (and , → ٬ inside digit runs)
 *   without corrupting HTML markup; hooked on `wc_price` so every
 *   WooCommerce price renders Persian digits.
 * - toykindangel_jdate()       formats a timestamp as a Jalali date with
 *   Persian month/day names (فروردین … اسفند).
 * - get_the_date/get_the_time  render Jalali on the frontend (admin keeps
 *   Gregorian so dashboards/exports stay machine-readable).
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

/* ------------------------------------------------------------------------- */
/* Persian digits                                                             */
/* ------------------------------------------------------------------------- */

if ( ! function_exists( 'toykindangel_fa_digits' ) ) {
        /**
         * Convert Latin digits to Persian (fallback — inc/blog.php loads first
         * and already provides this helper).
         *
         * @param string|int $str Input.
         * @return string
         */
        function toykindangel_fa_digits( $str ) {
                $en = array( '0', '1', '2', '3', '4', '5', '6', '7', '8', '9' );
                $fa = array( '۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹' );
                return str_replace( $en, $fa, (string) $str );
        }
}

if ( ! function_exists( 'toykindangel_fa_num' ) ) {
        /**
         * Persianize numbers inside a text — 0-9 → ۰-۹ and thousands "," → "٬".
         *
         * Conversion happens ONLY inside digit runs ("7,700,000" →
         * "۷٬۷۰۰٬۰۰۰"), so HTML tags/attributes, email addresses, URLs and
         * plain words never change. Idempotent: already-Persian text passes
         * through untouched.
         *
         * v0.21.2 fix: HTML numeric character references (&#x062A;,
         * &#1580;, &amp;…) are now preserved — their inner hex/decimal
         * digits are no longer Persianized. Without this, WooCommerce
         * price HTML that contains entities (e.g. when a SEO/filter
         * plugin escapes the currency symbol «تومان» → &#x062A;…)
         * would be corrupted into &#x۰۶۲A; which the browser cannot
         * decode, showing raw entity text in the DOM.
         *
         * @param string $text Input (may contain HTML).
         * @return string
         */
        function toykindangel_fa_num( $text ) {
                $text = (string) $text;

                /*
                 * Single-pass Persianization that protects HTML entities.
                 *
                 * The regex matches either:
                 *   (A) an HTML entity (&#x062A; &#1580; &amp; …) — returned
                 *       verbatim, digits inside never Persianized; or
                 *   (B) a digit run with optional thousands separators —
                 *       Persianized (0-9 → ۰-۹, , → ٬).
                 *
                 * Without branch (A), the digit-run regex would reach into
                 * the hex/decimal digits of an entity (&#x062A; → &#x۰۶۲A;)
                 * and corrupt it so the browser can no longer decode it.
                 * That showed up as raw `&#x۰۶۲A;…` text in price HTML.
                 */
                return preg_replace_callback(
                        '/&[#a-zA-Z0-9]+;|\d+(?:,\d+)*/',
                        static function ( $m ) {
                                /* Branch A: HTML entity — return as-is. */
                                if ( '&' === substr( $m[0], 0, 1 ) ) {
                                        return $m[0];
                                }
                                /* Branch B: digit run — Persianize. */
                                return toykindangel_fa_digits( str_replace( ',', '٬', $m[0] ) );
                        },
                        $text
                );
        }
}

/**
 * Every WooCommerce price (price_html, cart totals, checkout, order
 * tables, mini-cart …) goes through wc_price() — Persianize at priority
 * 100 so it runs after all price-composition filters.
 *
 * @param string $price Formatted price HTML.
 * @return string
 */
function toykindangel_wc_price_fa( $price ) {
        return toykindangel_fa_num( $price );
}
add_filter( 'wc_price', 'toykindangel_wc_price_fa', 100 );

/* ------------------------------------------------------------------------- */
/* Jalali dates                                                               */
/* ------------------------------------------------------------------------- */

/**
 * Gregorian → Jalali conversion (classic 33-year-cycle algorithm).
 *
 * @param int $gy Gregorian year.
 * @param int $gm Gregorian month 1-12.
 * @param int $gd Gregorian day 1-31.
 * @return array{0:int,1:int,2:int} [ year, month, day ] in the Jalali calendar.
 */
function toykindangel_gregorian_to_jalali( $gy, $gm, $gd ) {
        $g_d_m = array( 0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334 );

        $gy = (int) $gy;
        $gm = max( 1, min( 12, (int) $gm ) );
        $gd = max( 1, min( 31, (int) $gd ) );

        $jy = ( $gy > 1600 ) ? 979 : 0;
        $gy -= ( $gy > 1600 ) ? 1600 : 621;

        $gy2  = ( $gm > 2 ) ? ( $gy + 1 ) : $gy;
        $days = ( 365 * $gy )
                + (int) ( ( $gy2 + 3 ) / 4 )
                - (int) ( ( $gy2 + 99 ) / 100 )
                + (int) ( ( $gy2 + 399 ) / 400 )
                - 80 + $gd + $g_d_m[ $gm - 1 ];

        $jy += 33 * (int) ( $days / 12053 );
        $days %= 12053;
        $jy += 4 * (int) ( $days / 1461 );
        $days %= 1461;

        if ( $days > 365 ) {
                $jy += (int) ( ( $days - 1 ) / 365 );
                $days = ( $days - 1 ) % 365; // phpcs:ignore Squiz.Operators.IncrementDecrementUsage.Found -- Jalali math, not a standalone decrement
        }

        $jm = ( $days < 186 ) ? 1 + (int) ( $days / 31 ) : 7 + (int) ( ( $days - 186 ) / 30 );
        $jd = 1 + ( ( $days < 186 ) ? ( $days % 31 ) : ( ( $days - 186 ) % 30 ) );

        return array( $jy, $jm, $jd );
}

/**
 * Jalali leap year (mod-33 cycle).
 *
 * @param int $jy Jalali year.
 * @return bool
 */
function toykindangel_jalali_is_leap( $jy ) {
        return in_array( (int) $jy % 33, array( 1, 5, 9, 13, 17, 22, 26, 30 ), true );
}

/**
 * Format a timestamp as a Jalali (Persian) date/time string.
 *
 * Supported format tokens: d D j l N w S z F M m n t Y y a A g G h H i s
 * L e P O T Z — plus U / c / r which intentionally stay Gregorian/Unix
 * (machine-readable formats used in <time datetime> attributes and feeds).
 *
 * @param string   $format    PHP date() style format string.
 * @param int|null $timestamp Unix timestamp (null = now).
 * @return string
 */
function toykindangel_jdate( $format, $timestamp = null ) {
        if ( null === $timestamp || ! is_numeric( $timestamp ) ) {
                $timestamp = time();
        }
        $timestamp = (int) $timestamp;

        /* Machine-readable formats keep the Gregorian/Unix value. */
        if ( 'U' === $format ) {
                return (string) $timestamp;
        }
        if ( 'c' === $format || 'r' === $format ) {
                return toykindangel_jdate_wp_dt( $timestamp )->format( $format );
        }

        $dt = toykindangel_jdate_wp_dt( $timestamp );

        $months          = array( 'فروردین', 'اردیبهشت', 'خرداد', 'تیر', 'مرداد', 'شهریور', 'مهر', 'آبان', 'آذر', 'دی', 'بهمن', 'اسفند' );
        /* Indexed by PHP date('w'): 0 = Sunday … 6 = Saturday. */
        $weekdays        = array( 'یکشنبه', 'دوشنبه', 'سه‌شنبه', 'چهارشنبه', 'پنجشنبه', 'جمعه', 'شنبه' );
        $weekdays_short  = array( 'ی', 'د', 'س', 'چ', 'پ', 'ج', 'ش' );

        list( $jy, $jm, $jd ) = toykindangel_gregorian_to_jalali( (int) $dt->format( 'Y' ), (int) $dt->format( 'n' ), (int) $dt->format( 'j' ) );

        $dow     = (int) $dt->format( 'w' );                       /* 0=Sun … 6=Sat */
        $j_dow   = ( $dow + 1 ) % 7;                                /* 0=شنبه … 6=جمعه */
        $h24     = (int) $dt->format( 'G' );
        $h12     = $h24 % 12;
        if ( 0 === $h12 ) {
                $h12 = 12;
        }
        $doy     = ( $jm <= 6 ) ? ( ( $jm - 1 ) * 31 + $jd ) : ( 186 + ( $jm - 7 ) * 30 + $jd );
        $leap    = toykindangel_jalali_is_leap( $jy ) ? 1 : 0;
        $month_days = ( $jm <= 6 ) ? 31 : ( ( $jm <= 11 ) ? 30 : ( $leap ? 30 : 29 ) );

        $out = '';
        $len = strlen( $format );
        for ( $i = 0; $i < $len; $i++ ) {
                $ch = $format[ $i ];

                if ( '\\' === $ch && $i + 1 < $len ) { /* Escaped literal. */
                                $out .= $format[ ++$i ];
                                continue;
                        }

                switch ( $ch ) {
                                case 'd':
                                $out .= toykindangel_fa_digits( sprintf( '%02d', $jd ) );
                                                break;
                                case 'j':
                                $out .= toykindangel_fa_digits( $jd );
                                                break;
                                case 'S': /* English ordinal suffix — none in Persian. */
                                                break;
                                case 'l':
                                $out .= $weekdays[ $dow ];
                                                break;
                                case 'D':
                                $out .= $weekdays_short[ $dow ];
                                                break;
                                case 'N': /* ISO: 1=شنبه … 7=جمعه. */
                                $out .= (string) ( $j_dow + 1 );
                                                break;
                                case 'w': /* 0=شنبه. */
                                $out .= (string) $j_dow;
                                                break;
                                case 'z':
                                $out .= toykindangel_fa_digits( $doy );
                                                break;
                                case 'F':
                                $out .= $months[ $jm - 1 ];
                                                break;
                                case 'M':
                                $out .= mb_substr( $months[ $jm - 1 ], 0, 3 );
                                                break;
                                case 'm':
                                $out .= toykindangel_fa_digits( sprintf( '%02d', $jm ) );
                                                break;
                                case 'n':
                                $out .= toykindangel_fa_digits( $jm );
                                                break;
                                case 't':
                                $out .= toykindangel_fa_digits( $month_days );
                                                break;
                                case 'Y':
                                $out .= toykindangel_fa_digits( $jy );
                                                break;
                                case 'y':
                                $out .= toykindangel_fa_digits( sprintf( '%02d', $jy % 100 ) );
                                                break;
                                case 'a':
                                $out .= ( $h24 < 12 ) ? 'ق.ظ' : 'ب.ظ';
                                                break;
                                case 'A':
                                $out .= ( $h24 < 12 ) ? 'قبل از ظهر' : 'بعد از ظهر';
                                                break;
                                case 'g':
                                $out .= toykindangel_fa_digits( $h12 );
                                                break;
                                case 'G':
                                $out .= toykindangel_fa_digits( $h24 );
                                                break;
                                case 'h':
                                $out .= toykindangel_fa_digits( sprintf( '%02d', $h12 ) );
                                                break;
                                case 'H':
                                $out .= toykindangel_fa_digits( sprintf( '%02d', $h24 ) );
                                                break;
                                case 'i':
                                $out .= toykindangel_fa_digits( $dt->format( 'i' ) );
                                                break;
                                case 's':
                                $out .= toykindangel_fa_digits( $dt->format( 's' ) );
                                                break;
                                case 'L':
                                $out .= toykindangel_fa_digits( $leap );
                                                break;
                                case 'e':
                                        case 'P':
                                        case 'O':
                                        case 'T':
                                        case 'Z':
                                $out .= $dt->format( $ch );
                                                break;
                                default:
                                $out .= $ch;
                }
        }

        return $out;
}

if ( ! function_exists( 'toykindangel_jdate_wp_dt' ) ) {
        /**
         * Immutable datetime for a timestamp in the site timezone.
         *
         * @param int $timestamp Unix timestamp.
         * @return DateTimeImmutable
         */
        function toykindangel_jdate_wp_dt( $timestamp ) {
                static $tz = null;
                if ( null === $tz ) {
                                $tz = function_exists( 'wp_timezone' ) ? wp_timezone() : new DateTimeZone( 'UTC' );
                        }
                $dt = new DateTimeImmutable( '@' . (int) $timestamp );
                return $dt->setTimezone( $tz );
        }
}

/* ------------------------------------------------------------------------- */
/* Frontend date filters                                                      */
/* ------------------------------------------------------------------------- */

if ( ! function_exists( 'toykindangel_jalali_post_date' ) ) {
        /**
         * Render get_the_date()/get_the_time() as Jalali on the frontend.
         *
         * Admin screens (non-AJAX) and REST responses keep the Gregorian value
         * so dashboards, exports and block REST consumers stay predictable.
         *
         * @param string|int  $the_date Formatted date (or a Unix timestamp for get_post_time('U')).
         * @param string      $format   Requested format (empty = site default).
         * @param int|WP_Post $post    Post.
         * @param bool        $gmt     Whether to use GMT (passed by get_post_time).
         * @param string      $kind     'date' or 'time'.
         * @return string
         */
        function toykindangel_jalali_post_date( $the_date, $format = '', $post = 0, $gmt = false, $kind = 'date' ) {
                if ( is_admin() && ! wp_doing_ajax() ) {
                                return $the_date;
                        }
                if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
                                return $the_date;
                        }
                if ( is_feed() ) {
                                return $the_date; /* RFC date formats must stay Gregorian. */
                        }

                if ( '' === $format ) {
                                $format = ( 'time' === $kind )
                                                ? get_option( 'time_format', 'g:i a' )
                                                : get_option( 'date_format', 'F j, Y' );
                        }

                $timestamp = get_post_timestamp( $post );
                if ( false === $timestamp || null === $timestamp ) {
                                return $the_date;
                        }

                return toykindangel_jdate( $format, $timestamp );
        }
}

/**
 * Get_the_date() filter → Jalali on the frontend.
 *
 * @param string      $the_date Formatted date.
 * @param string      $format   Format.
 * @param int|WP_Post $post     Post.
 * @return string
 */
function toykindangel_jalali_the_date( $the_date, $format = '', $post = 0 ) {
        return toykindangel_jalali_post_date( $the_date, $format, $post, false, 'date' );
}
add_filter( 'get_the_date', 'toykindangel_jalali_the_date', 10, 3 );

/**
 * Get_the_time() filter → Jalali on the frontend.
 *
 * @param string      $the_time Formatted time.
 * @param string      $format   Format.
 * @param int|WP_Post $post     Post.
 * @return string
 */
function toykindangel_jalali_the_time( $the_time, $format = '', $post = 0 ) {
        return toykindangel_jalali_post_date( $the_time, $format, $post, false, 'time' );
}
add_filter( 'get_the_time', 'toykindangel_jalali_the_time', 10, 3 );

/**
 * Get_post_time()/get_post_modified_time() filter (used by feeds & plugins)
 * → Jalali on the frontend.
 *
 * @param string      $time   Formatted time.
 * @param string      $format Format.
 * @param bool        $gmt    GMT flag.
 * @param int|WP_Post $post   Post.
 * @return string
 */
function toykindangel_jalali_post_time( $time, $format = '', $gmt = false, $post = 0 ) {
        if ( 'U' === $format || 'u' === $format ) {
                return $time; /* Unix output must stay numeric. */
        }
        return toykindangel_jalali_post_date( $time, $format, $post, $gmt, 'date' );
}
add_filter( 'get_post_time', 'toykindangel_jalali_post_time', 10, 4 );

/**
 * Order created date in Jalali (helper for WC templates).
 *
 * @param WC_Order|WC_Order_Refund $order  Order object.
 * @param string                   $format PHP format string ('' = site date format).
 * @return string
 */
function toykindangel_order_date_jalali( $order, $format = '' ) {
        if ( ! $order || ! is_callable( array( $order, 'get_date_created' ) ) || ! $order->get_date_created() ) {
                return '';
        }
        if ( '' === $format ) {
                $format = get_option( 'date_format', 'F j, Y' );
        }
        return toykindangel_jdate( $format, $order->get_date_created()->getTimestamp() );
}
