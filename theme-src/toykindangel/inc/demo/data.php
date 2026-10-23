<?php
/**
 * Demo content dataset (single source of truth for the importer).
 *
 * Derived 1:1 from the original demo dataset (assets/js/data.js) so the
 * imported WordPress content reproduces the demo exactly — with LOCAL
 * media instead of hotlinks to the original site.
 *
 * @package ToyKindAngel
 */

if ( ! defined( 'ABSPATH' ) ) {
        exit;
}

if ( ! function_exists( 'toykindangel_demo_data' ) ) {
        /**
         * Full demo dataset.
         *
         * @return array
         */
        function toykindangel_demo_data() {
                $img = TOYKINDANGEL_DIR . '/assets/';

                /* ---------- بنرها (فایل‌های لوکال قالب) ---------- */
                $banners = array(
                        'hero'  => array(
                                array( 'file' => $img . 'img/tiles/tile-0.jpg', 'link' => '' ),
                                array( 'file' => $img . 'img/tiles/tile-1.jpg', 'link' => '' ),
                                array( 'file' => $img . 'img/tiles/tile-2.jpg', 'link' => '' ),
                                array( 'file' => $img . 'img/tiles/tile-3.jpg', 'link' => '' ),
                        ),
                        'grid'  => array(
                                array( 'file' => $img . 'img/tiles/tile-4.jpg', 'link' => '' ),
                                array( 'file' => $img . 'img/tiles/tile-5.jpg', 'link' => '' ),
                                array( 'file' => $img . 'img/tiles/tile-6.jpg', 'link' => '' ),
                                array( 'file' => $img . 'img/tiles/tile-7.jpg', 'link' => '' ),
                        ),
                        'strip' => array( 'file' => $img . 'img/banners/banner-0.jpg', 'link' => '' ),
                );

                /* ---------- استوری‌های صفحه اصلی (۱۰ دسته با تصویر) ---------- */
                /* «slug» صریح برای لینک‌های تمیز /product-category/<slug>/ —
                 * هم درون‌ریز یک‌کلیکی و هم فایل WXR همین اسلاگ‌ها را می‌سازند. */
                $stories = array(
                        array( 'name' => 'عروسک و پولیشی', 'slug' => 'dolls-plush', 'file' => $img . 'img/cats/cat-11.png' ),
                        array( 'name' => 'ساختنی و پازل', 'slug' => 'building-puzzle', 'file' => $img . 'img/cats/cat-12.png' ),
                        array( 'name' => 'وسایل نقلیه', 'slug' => 'vehicles', 'file' => $img . 'img/cats/cat-06.png' ),
                        array( 'name' => 'فضای باز و ورزش', 'slug' => 'outdoor-sports', 'file' => $img . 'img/cats/cat-13.png' ),
                        array( 'name' => 'نوزاد و خردسال', 'slug' => 'baby-toddler', 'file' => $img . 'img/cats/cat-14.png' ),
                        array( 'name' => 'کتاب و لوازم‌تحریر', 'slug' => 'books-stationery', 'file' => $img . 'img/cats/cat-05.png' ),
                        array( 'name' => 'رباتیک و هوشمند', 'slug' => 'robotics-smart', 'file' => $img . 'img/cats/cat-02.png' ),
                        array( 'name' => 'خانه‌سازی و آشپزی', 'slug' => 'home-kitchen-play', 'file' => $img . 'img/cats/cat-03.png' ),
                        array( 'name' => 'تن‌پوش کارتونی', 'slug' => 'cartoon-costumes', 'file' => $img . 'img/cats/cat-04.png' ),
                        array( 'name' => 'گریم و زیورآلات', 'slug' => 'makeup-jewelry', 'file' => $img . 'img/cats/cat-01.png' ),
                );

                /* ---------- درخت دسته‌بندی‌ها (صفحه دسته‌بندی) ---------- */
                $cc = static function ( $n ) use ( $img ) {
                        return $img . 'img/cc/s' . sprintf( '%02d', $n ) . '.jpg';
                };
                $tree = array(
                        array( 'name' => 'کریسمس و هدیه', 'file' => $cc(1), 'groups' => array(
                                array( 'title' => 'کریسمس', 'items' => array( 'درخت کریسمس', 'تزئینات کریسمس', 'هدایای کریسمس', 'لباس بابانوئل', 'ست هدیه', 'کارت تبریک', 'جوراب هدیه', 'ماگ و یادگاری' ) ),
                        )),
                        array( 'name' => 'عروسک، ربات و شخصیت', 'file' => $cc(2), 'groups' => array(
                                array( 'title' => 'عروسک', 'items' => array( 'عروسک', 'عروسک پارچه‌ای', 'عروسک سیلیکونی', 'عروسک پیانویی', 'عروسک نمایشی', 'لوازم جانبی عروسک', 'رباتیک', 'عروسک سخنگو' ) ),
                                array( 'title' => 'پولیشی و شخصیت', 'items' => array( 'پولیشی‌ها', 'پتو پولیشی', 'مبل‌های پولیشی', 'شخصیت کارتونی', 'حیوانات مینیاتوری', 'سورپرایزی‌ها', 'جاکلیدی', 'بالشتک و کوسن' ) ),
                        )),
                        array( 'name' => 'ساختنی، آموزشی و بازی', 'file' => $cc(3), 'groups' => array(
                                array( 'title' => 'ساختنی', 'items' => array( 'بلاک ساختنی', 'لگو و سازه', 'پازل', 'مدل‌سازی', 'مهره و منجوق', 'مغناطیسی', 'خمیر بازی', 'مکانیکی' ) ),
                                array( 'title' => 'آموزشی', 'items' => array( 'بازی فکری و گروهی', 'اسباب‌بازی آموزشی', 'ریاضی و حروف', 'ست آزمایش', 'زبان و کلمات', 'حافظه و تمرکز', 'لوح و تخته', 'چرتکه' ) ),
                        )),
                        array( 'name' => 'وسایل نقلیه', 'file' => $cc(4), 'groups' => array(
                                array( 'title' => 'انواع وسیله نقلیه', 'items' => array( 'زمینی', 'هوایی', 'ریلی', 'دریایی', 'جنگی', 'امدادی', 'ماشین سنگین', 'مدل', 'کنترلی', 'ریسینگ' ) ),
                        )),
                        array( 'name' => 'فضای باز و سوارشدنی', 'file' => $cc(5), 'groups' => array(
                                array( 'title' => 'فضای باز', 'items' => array( 'سوارشدنی‌ها', 'اسکوتر و اسکیت', 'دوچرخه و سه‌چرخه', 'پارک بازی', 'خانه بازی', 'وسایل بادی', 'ورزشی', 'لوازم جانبی و ایمنی', 'اسکیت‌برد' ) ),
                        )),
                        array( 'name' => 'تفنگ و مبارزه', 'file' => $cc(6), 'groups' => array(
                                array( 'title' => 'تفنگ و مبارزه', 'items' => array( 'تفنگ', 'تفنگ آبپاش', 'لوازم مبارزه', 'جاسوسی', 'تیر و کمان', 'شمشیر و سپر', 'دارت و نشانه‌روی', 'ست نظامی' ) ),
                        )),
                        array( 'name' => 'ماسک و تن‌پوش کارتونی', 'file' => $cc(7), 'groups' => array(
                                array( 'title' => 'ماسک و تن‌پوش', 'items' => array( 'تن‌پوش کارتونی', 'ماسک', 'کلاه‌گیس', 'لوازم جانبی', 'بال و شنل', 'دستکش و جوراب' ) ),
                        )),
                        array( 'name' => 'کیف، کوله و چمدان', 'file' => $cc(8), 'groups' => array(
                                array( 'title' => 'کیف و کوله', 'items' => array( 'کیف', 'کوله‌پشتی', 'چمدان', 'ساک', 'جامدادی', 'کیف پول' ) ),
                        )),
                        array( 'name' => 'گریم و زیورآلات', 'file' => $cc(9), 'groups' => array(
                                array( 'title' => 'لوازم گریم', 'items' => array( 'لوازم گریم', 'زیورآلات', 'اکسسوری مو', 'ست آرایش', 'ناخن و تتو موقت' ) ),
                        )),
                        array( 'name' => 'اتاق کودک', 'file' => $cc(10), 'groups' => array(
                                array( 'title' => 'اتاق کودک', 'items' => array( 'چراغ خواب', 'تخت و روتختی', 'کمد و جعبه اسباب‌بازی', 'تزئینات اتاق', 'نیمکت و میز', 'گهواره', 'فرش و موکت' ) ),
                        )),
                        array( 'name' => 'آب بازی', 'file' => $cc(11), 'groups' => array(
                                array( 'title' => 'آب بازی', 'items' => array( 'تفنگ آبپاش', 'وسایل شنا', 'وسایل حمام', 'استخر و وسایل بادی', 'وان و تشت', 'سرسره آبی', 'اسباب‌بازی حمام' ) ),
                        )),
                        array( 'name' => 'نوزاد و خردسال', 'file' => $cc(2), 'groups' => array(
                                array( 'title' => 'نوزاد و خردسال', 'items' => array( 'اسباب‌بازی نوزاد', 'واکر و کالسکه', 'آب بازی نوزاد', 'لوازم نوزاد', 'جغجغه و دندان‌گیر', 'تشویقی آموزشی', 'کتاب پارچه‌ای' ) ),
                        )),
                        array( 'name' => 'ست‌های بازی', 'file' => $cc(3), 'groups' => array(
                                array( 'title' => 'ست‌های بازی', 'items' => array( 'ست عروسکی', 'ست ماشینی', 'ست جنگی', 'ست آشپزی', 'ست ابزار', 'ست دکتری', 'ست مکانیکی', 'ست فروشگاهی' ) ),
                        )),
                        array( 'name' => 'اسباب‌بازی چوبی', 'file' => $cc(6), 'groups' => array(
                                array( 'title' => 'اسباب‌بازی چوبی', 'items' => array( 'بلاک چوبی', 'پازل چوبی', 'قطار چوبی', 'ماشین چوبی', 'بازی چوبی', 'آشپزخانه چوبی' ) ),
                        )),
                        array( 'name' => 'کتاب', 'file' => $cc(5), 'groups' => array(
                                array( 'title' => 'کتاب', 'items' => array( 'کتاب کودک', 'قصه و داستان', 'کتاب آموزشی', 'رنگ‌آمیزی', 'کتاب لمسی و صوتی', 'دفتر و نقاشی' ) ),
                        )),
                        array( 'name' => 'لوازم‌التحریر', 'file' => $cc(8), 'groups' => array(
                                array( 'title' => 'لوازم‌التحریر', 'items' => array( 'کیف و کوله پشتی', 'نوشت‌افزار', 'ماگ و فلاسک', 'ظرف غذا', 'میز تحریر و تخته', 'ست آموزشی' ) ),
                        )),
                        array( 'name' => 'رباتیک و هوشمند', 'file' => $cc(9), 'groups' => array(
                                array( 'title' => 'هوشمند', 'items' => array( 'ربات آموزشی', 'اسباب‌بازی هوشمند', 'باتری و شارژر', 'کنترل از راه دور', 'ویدئو و پروژکتور' ) ),
                        )),
                        array( 'name' => 'حراجی‌ها', 'file' => $cc(10), 'groups' => array(
                                array( 'title' => 'تخفیف‌ها', 'items' => array( 'حراج ویژه', 'زیر قیمت', 'پیشنهاد لحظه‌ای', 'آخرین فرصت', 'پک اقتصادی' ) ),
                        )),
                        array( 'name' => 'جدیدترین‌ها', 'file' => $cc(11), 'groups' => array(
                                array( 'title' => 'تازه‌ها', 'items' => array( 'جدیدترین محصولات', 'پرفروش‌ها', 'پیشنهاد ما', 'ویدئوها', 'به‌زودی' ) ),
                        )),
                );

                /* ---------- برندها (بدون تصویر خارجی — ریل با نام رندر می‌شود) ---------- */
                $brands = array( 'اینتکس', 'باربی', 'دفالوسی', 'لگو', 'هانگر', 'ویلی' );

                /* ---------- محصولات (مطابق دیتاست دمو) ---------- */
                $pdir = TOYKINDANGEL_DIR . '/assets/demo/products/';
                $tka_img = static function ( $n ) use ( $pdir ) {
                        return $pdir . $n . '.jpg';
                };

                $products = array(
                        /* شگفت‌انگیز (تخفیف‌دار) */
                        array( 'name' => 'پولیشی پک سه عددی میوه پیانویی جعبه دار', 'slug' => 'plush-fruit-piano-trio-box', 'price' => 3720000, 'old' => 4320000, 'sales' => 62, 'img' => $tka_img('p-fruit-trio'), 'cats' => array( 'عروسک پیانویی', 'پولیشی‌ها' ) ),
                        array( 'name' => 'پولیشی میوه پیانویی طرح هویج جعبه دار', 'slug' => 'plush-fruit-piano-carrot-box', 'price' => 1440000, 'old' => 1800000, 'sales' => 40, 'img' => $tka_img('p-carrot'), 'cats' => array( 'عروسک پیانویی' ) ),
                        array( 'name' => 'پولیشی میوه پیانویی طرح توت فرنگی جعبه دار', 'slug' => 'plush-fruit-piano-strawberry-box', 'price' => 1440000, 'old' => 1800000, 'sales' => 38, 'img' => $tka_img('p-strawberry'), 'cats' => array( 'عروسک پیانویی' ) ),
                        array( 'name' => 'پولیشی میوه پیانویی طرح موز جعبه دار', 'slug' => 'plush-fruit-piano-banana-box', 'price' => 1440000, 'old' => 1800000, 'sales' => 36, 'img' => $tka_img('p-banana'), 'cats' => array( 'عروسک پیانویی' ) ),
                        array( 'name' => 'پولیشی صندلی تخت‌شو طرح مینیون', 'slug' => 'plush-sofa-bed-minion', 'price' => 3880000, 'old' => 4850000, 'sales' => 55, 'img' => $tka_img('p-sofa-minion'), 'cats' => array( 'مبل‌های پولیشی', 'شخصیت کارتونی' ) ),
                        array( 'name' => 'پولیشی صندلی تخت‌شو طرح سگ هاسکی', 'slug' => 'plush-sofa-bed-husky', 'price' => 3880000, 'old' => 4850000, 'sales' => 51, 'img' => $tka_img('p-sofa-husky'), 'cats' => array( 'مبل‌های پولیشی' ) ),
                        array( 'name' => 'عروسک سیلیکونی سیاهپوست موزیکالی تیشرت شلوارک طوسی', 'slug' => 'silicone-musical-doll-grey', 'price' => 7700000, 'old' => 9600000, 'sales' => 30, 'img' => $tka_img('p-doll-boy'), 'cats' => array( 'عروسک سیلیکونی', 'عروسک سخنگو' ) ),
                        array( 'name' => 'ایرهاکی چوبی Joy Toys', 'slug' => 'joy-toys-air-hockey', 'price' => 9800000, 'old' => 12250000, 'sales' => 44, 'img' => $tka_img('p-airhockey'), 'cats' => array( 'بازی فکری و گروهی' ), 'brand' => 'اینتکس' ),

                        /* جدیدترین‌ها */
                        array( 'name' => 'پولیشی صندلی تخت‌شو طرح کاپی‌بارا', 'slug' => 'plush-sofa-bed-capybara', 'price' => 3880000, 'sales' => 20, 'img' => $tka_img('p-sofa-capybara'), 'cats' => array( 'مبل‌های پولیشی', 'حیوانات مینیاتوری' ) ),
                        array( 'name' => 'پولیشی صندلی تخت‌شو طرح دلقک سبز', 'slug' => 'plush-sofa-bed-green-clown', 'price' => 3880000, 'sales' => 12, 'img' => $tka_img('p-sofa-clown'), 'cats' => array( 'مبل‌های پولیشی' ) ),
                        array( 'name' => 'پولیشی صندلی تخت‌شو طرح هیولا قرمز', 'slug' => 'plush-sofa-bed-red-monster', 'price' => 3880000, 'sales' => 10, 'img' => $tka_img('p-sofa-monster'), 'cats' => array( 'مبل‌های پولیشی', 'شخصیت کارتونی' ) ),
                        array( 'name' => 'پولیشی صندلی تخت‌شو طرح دایناسور زرد سبز', 'slug' => 'plush-sofa-bed-green-yellow-dinosaur', 'price' => 3880000, 'sales' => 9, 'img' => $tka_img('p-sofa-dino'), 'cats' => array( 'مبل‌های پولیشی', 'شخصیت کارتونی' ) ),
                        array( 'name' => 'پولیشی صندلی تخت‌شو طرح خرگوش بنفش', 'slug' => 'plush-sofa-bed-purple-rabbit', 'price' => 3880000, 'sales' => 8, 'img' => $tka_img('p-sofa-rabbit'), 'cats' => array( 'مبل‌های پولیشی' ) ),
                        array( 'name' => 'پولیشی صندلی تخت‌شو طرح هیولا سبز پاستیلی', 'slug' => 'plush-sofa-bed-green-monster', 'price' => 3880000, 'sales' => 7, 'img' => $tka_img('p-sofa-green-monster'), 'cats' => array( 'مبل‌های پولیشی' ) ),
                        array( 'name' => 'بوکس دیواری شش‌تایی مدل 7792_A', 'slug' => 'wall-boxing-set-7792a', 'price' => 8800000, 'sales' => 14, 'img' => $tka_img('p-boxing'), 'cats' => array( 'ورزشی' ), 'brand' => 'هانگر' ),
                        array( 'name' => 'ریسینگ ال‌ای‌دی‌دار ۶ متری مدل A51/9A', 'slug' => 'led-racing-track-a51-9a', 'price' => 9700000, 'sales' => 18, 'img' => $tka_img('p-racing'), 'cats' => array( 'ریسینگ', 'کنترلی' ), 'brand' => 'ویلی' ),

                        /* پرفروش‌ها */
                        array( 'name' => 'ست آشپزخانه دکه باربیکیو ۸۳ قطعه', 'slug' => 'teppanyaki-grill-toy-889-285', 'price' => 18800000, 'sales' => 120, 'img' => $tka_img('p-kitchen'), 'cats' => array( 'ست آشپزی', 'آشپزخانه چوبی' ), 'brand' => 'دفالوسی' ),
                        array( 'name' => 'ایرهاکی چوبی مدل 545L', 'slug' => 'wooden-air-hockey-545l', 'price' => 9880000, 'sales' => 110, 'img' => $tka_img('p-airhockey'), 'cats' => array( 'بازی فکری و گروهی' ), 'brand' => 'اینتکس' ),
                        array( 'name' => 'ریسینگ ۵۶۰ سانت مدل A51-3A', 'slug' => 'racing-track-560-a51-3a', 'price' => 11800000, 'sales' => 100, 'img' => $tka_img('p-racing'), 'cats' => array( 'ریسینگ' ), 'brand' => 'ویلی' ),
                        array( 'name' => 'ریسینگ ۵۲۰ سانت مدل A64_12A', 'slug' => 'racing-track-520-a64-12a', 'price' => 11800000, 'sales' => 90, 'img' => $tka_img('p-racing'), 'cats' => array( 'ریسینگ' ), 'brand' => 'ویلی' ),
                        array( 'name' => 'عروسک سیلیکونی سیاهپوست موزیکالی تیشرت شلوارک زرد', 'slug' => 'silicone-musical-doll-yellow', 'price' => 7700000, 'sales' => 80, 'img' => $tka_img('p-doll-yellow'), 'cats' => array( 'عروسک سیلیکونی', 'عروسک سخنگو' ) ),
                );

                /* ---------- برگه‌ها ---------- */
                $pages = array(
                        array(
                                'title'    => 'درباره ما',
                                'slug'     => 'about-us',
                                'content'  => "<h2>فروشگاه اسباب‌بازی فرشته مهربون</h2>\n<p>فروشگاه اسباب‌بازی فرشته مهربون با هزاران کالای متنوع، تمامی گروه‌های سنی را پوشش می‌دهد. انواع عروسک، پولیشی، اسباب‌بازی‌های ساختنی و آموزشی، وسایل نقلیه، آب‌بازی، ست‌های بازی، لوازم‌التحریر و ملزومات نوزاد با بهترین قیمت و ضمانت سلامت فیزیکی کالا ارائه می‌شود.</p>\n<p>تمامی کالاهای فروشگاه دارای ضمانت سلامت فیزیکی هستند و پیش از ارسال، توسط کارشناسان ما کنترل کیفیت می‌شوند.</p>",
                        ),
                        array(
                                'title'    => 'تماس با ما',
                                'slug'     => 'contact-us',
                                'content'  => "<h2>ارتباط با فرشته مهربون</h2>\n<p>هر روز از ۹ صبح تا ۱۲ بامداد پاسخگوی شما هستیم.</p>\n<ul>\n<li><strong>تلفن پشتیبانی:</strong> ۰۹۱۵۲۰۰۶۶۳۰</li>\n<li><strong>ایمیل:</strong> info@toykindangel.com</li>\n<li><strong>آدرس:</strong> مشهد، امامت ۴۲، فروشگاه فرشته مهربون</li>\n</ul>",
                        ),
                        array(
                                'title'     => 'دسته‌بندی‌ها',
                                'slug'      => 'categories',
                                'template'  => 'page-categories.php',
                                'content'   => '',
                        ),
                );

                /* ---------- منوی اصلی ---------- */
                $menu = array(
                        array( 'title' => 'خانه', 'url' => '/' ),
                        array( 'title' => 'فروشگاه', 'page' => 'shop' ),
                        array( 'title' => 'دسته‌بندی‌ها', 'page' => 'categories' ),
                        array( 'title' => 'درباره ما', 'page' => 'about-us' ),
                        array( 'title' => 'تماس با ما', 'page' => 'contact-us' ),
                );

                return compact( 'banners', 'stories', 'tree', 'brands', 'products', 'pages', 'menu' );
        }
}
