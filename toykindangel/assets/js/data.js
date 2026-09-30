/* =========================================================
   داده‌های نمونه (نسخهٔ کاملاً لوکال)
   • همهٔ تصاویر از خود قالب خوانده می‌شوند:
       assets/img/…        بنرها، دسته‌ها، آیکون‌ها
       assets/demo/…       تصاویر محصولات (مسیر «assets/» توسط
                           TKA_BASE در main.js به URI قالب ترمیم می‌شود)
   • همهٔ لینک‌های محصول داخلی‌اند:  /product/<slug>/
     (اسلاگ‌ها با درون‌ریز محتوای نمونهٔ قالب یکی است —
      نمایش → درج محتوای نمونه)
   • هیچ درخواستی به هیچ دامنهٔ خارجی ارسال نمی‌شود.
   ========================================================= */
window.TKA = (function () {
  var P = "assets/demo/products/";

  /* ---------- بنرها (از اسنپ‌شاپ) ---------- */
  var banners = {
    /* اسلایدر بالای صفحه اصلی */
    hero: [
      { img: "assets/img/tiles/tile-0.jpg", href: "#" },
      { img: "assets/img/tiles/tile-1.jpg", href: "#" },
      { img: "assets/img/tiles/tile-2.jpg", href: "#" },
      { img: "assets/img/tiles/tile-3.jpg", href: "#" }
    ],
    /* گرید ۲×۲ */
    grid: [
      { img: "assets/img/tiles/tile-4.jpg", href: "#" },
      { img: "assets/img/tiles/tile-5.jpg", href: "#" },
      { img: "assets/img/tiles/tile-6.jpg", href: "#" },
      { img: "assets/img/tiles/tile-7.jpg", href: "#" }
    ],
    /* نوار باریک بالای صفحه (بنر موبایل اسنپ‌شاپ) */
    strip: { img: "assets/img/banners/banner-0.jpg", href: "#" }
  };

  /* ---------- ردیف دسته‌بندی‌ها (استوری‌مانند) ---------- */
  /* تصاویر ۱۰۴×۱۰۴ از اسنپ‌شاپ — در صورت نیاز جابه‌جا کنید */
  var stories = [
    { name: "عروسک و پولیشی",     img: "assets/img/cats/cat-11.png", href: "#" },
    { name: "ساختنی و پازل",      img: "assets/img/cats/cat-12.png", href: "#" },
    { name: "وسایل نقلیه",        img: "assets/img/cats/cat-06.png", href: "#" },
    { name: "فضای باز و ورزش",    img: "assets/img/cats/cat-13.png", href: "#" },
    { name: "نوزاد و خردسال",     img: "assets/img/cats/cat-14.png", href: "#" },
    { name: "کتاب و لوازم‌تحریر",  img: "assets/img/cats/cat-05.png", href: "#" },
    { name: "رباتیک و هوشمند",    img: "assets/img/cats/cat-02.png", href: "#" },
    { name: "خانه‌سازی و آشپزی",   img: "assets/img/cats/cat-03.png", href: "#" },
    { name: "تن‌پوش کارتونی",      img: "assets/img/cats/cat-04.png", href: "#" },
    { name: "گریم و زیورآلات",    img: "assets/img/cats/cat-01.png", href: "#" }
  ];

  /* ---------- برندها (بدون تصویر خارجی؛ ریل با نام رندر می‌شود) ---------- */
  var brands = [
    { name: "اینتکس",  img: "" },
    { name: "باربی",   img: "" },
    { name: "دفالوسی", img: "" },
    { name: "لگو",     img: "" },
    { name: "هانگر",   img: "" },
    { name: "ویلی",    img: "" }
  ];

  /* ---------- محصولات (لینک‌ها به فروشگاه محلی) ---------- */
  var products = {
    amazing: [
      { name: "پولیشی پک سه عددی میوه پیانویی جعبه دار", price: 3720000, old: 4320000, off: 14, bnpl: true,
        img: P + "p-fruit-trio.jpg", url: "/product/plush-fruit-piano-trio-box/" },
      { name: "پولیشی میوه پیانویی طرح هویج جعبه دار", price: 1440000, old: 1800000, off: 20, bnpl: true,
        img: P + "p-carrot.jpg", url: "/product/plush-fruit-piano-carrot-box/" },
      { name: "پولیشی میوه پیانویی طرح توت فرنگی جعبه دار", price: 1440000, old: 1800000, off: 20, bnpl: true,
        img: P + "p-strawberry.jpg", url: "/product/plush-fruit-piano-strawberry-box/" },
      { name: "پولیشی میوه پیانویی طرح موز جعبه دار", price: 1440000, old: 1800000, off: 20, bnpl: true,
        img: P + "p-banana.jpg", url: "/product/plush-fruit-piano-banana-box/" },
      { name: "پولیشی صندلی تخت‌شو طرح مینیون", price: 3880000, old: 4850000, off: 20, bnpl: true,
        img: P + "p-sofa-minion.jpg", url: "/product/plush-sofa-bed-minion/" },
      { name: "پولیشی صندلی تخت‌شو طرح سگ هاسکی", price: 3880000, old: 4850000, off: 20, bnpl: true,
        img: P + "p-sofa-husky.jpg", url: "/product/plush-sofa-bed-husky/" },
      { name: "عروسک سیلیکونی سیاهپوست موزیکالی تیشرت شلوارک طوسی", price: 7700000, old: 9600000, off: 20, bnpl: true,
        img: P + "p-doll-boy.jpg", url: "/product/silicone-musical-doll-grey/" },
      { name: "ایرهاکی چوبی Joy Toys", price: 9800000, old: 12250000, off: 20, bnpl: true,
        img: P + "p-airhockey.jpg", url: "/product/joy-toys-air-hockey/" }
    ],
    newest: [
      { name: "پولیشی صندلی تخت‌شو طرح کاپی‌بارا", price: 3880000, img: P + "p-sofa-capybara.jpg", url: "/product/plush-sofa-bed-capybara/" },
      { name: "پولیشی صندلی تخت‌شو طرح دلقک سبز", price: 3880000, img: P + "p-sofa-clown.jpg", url: "/product/plush-sofa-bed-green-clown/" },
      { name: "پولیشی صندلی تخت‌شو طرح هیولا قرمز", price: 3880000, img: P + "p-sofa-monster.jpg", url: "/product/plush-sofa-bed-red-monster/" },
      { name: "پولیشی صندلی تخت‌شو طرح دایناسور زرد سبز", price: 3880000, img: P + "p-sofa-dino.jpg", url: "/product/plush-sofa-bed-green-yellow-dinosaur/" },
      { name: "پولیشی صندلی تخت‌شو طرح خرگوش بنفش", price: 3880000, img: P + "p-sofa-rabbit.jpg", url: "/product/plush-sofa-bed-purple-rabbit/" },
      { name: "پولیشی صندلی تخت‌شو طرح هیولا سبز پاستیلی", price: 3880000, img: P + "p-sofa-green-monster.jpg", url: "/product/plush-sofa-bed-green-monster/" },
      { name: "بوکس دیواری شش‌تایی مدل 7792_A", price: 8800000, img: P + "p-boxing.jpg", url: "/product/wall-boxing-set-7792a/" },
      { name: "ریسینگ ال‌ای‌دی‌دار ۶ متری مدل A51/9A", price: 9700000, img: P + "p-racing.jpg", url: "/product/led-racing-track-a51-9a/" }
    ],
    best: [
      { name: "ست آشپزخانه دکه باربیکیو ۸۳ قطعه", price: 18800000, img: P + "p-kitchen.jpg", url: "/product/teppanyaki-grill-toy-889-285/" },
      { name: "ایرهاکی چوبی مدل 545L", price: 9880000, img: P + "p-airhockey.jpg", url: "/product/wooden-air-hockey-545l/" },
      { name: "ریسینگ ۵۶۰ سانت مدل A51-3A", price: 11800000, img: P + "p-racing.jpg", url: "/product/racing-track-560-a51-3a/" },
      { name: "ریسینگ ۵۲۰ سانت مدل A64_12A", price: 11800000, img: P + "p-racing.jpg", url: "/product/racing-track-520-a64-12a/" },
      { name: "عروسک سیلیکونی سیاهپوست موزیکالی تیشرت شلوارک زرد", price: 7700000, img: P + "p-doll-yellow.jpg", url: "/product/silicone-musical-doll-yellow/" },
      { name: "پولیشی پک سه عددی میوه پیانویی جعبه دار", price: 3720000, old: 4320000, off: 14,
        img: P + "p-fruit-trio.jpg", url: "/product/plush-fruit-piano-trio-box/" }
    ]
  };

  /* ---------- درخت دسته‌بندی‌ها (صفحه دسته‌بندی) ----------
     آیکن‌ها از مجموعه آیکن‌های واقعی اسنپ‌شاپ (icons.js)
     ساختار دقیقاً مانند AllCategories اسنپ‌شاپ:
     ستون راست = دسته‌های اصلی، پنل چپ = گروه‌های زیردسته به صورت متنی */
  var categories = [
    { name: "کریسمس و هدیه", icon: "i-cat-stars", groups: [
      { title: "کریسمس", items: ["درخت کریسمس", "تزئینات کریسمس", "هدایای کریسمس", "لباس بابانوئل", "ست هدیه", "کارت تبریک", "جوراب هدیه", "ماگ و یادگاری"] }
    ]},
    { name: "عروسک، ربات و شخصیت", icon: "i-cat-baby", groups: [
      { title: "عروسک", items: ["عروسک", "عروسک پارچه‌ای", "عروسک سیلیکونی", "عروسک پیانویی", "عروسک نمایشی", "لوازم جانبی عروسک", "رباتیک", "عروسک سخنگو"] },
      { title: "پولیشی و شخصیت", items: ["پولیشی‌ها", "پتو پولیشی", "مبل‌های پولیشی", "شخصیت کارتونی", "حیوانات مینیاتوری", "سورپرایزی‌ها", "جاکلیدی", "بالشتک و کوسن"] }
    ]},
    { name: "ساختنی، آموزشی و بازی", icon: "i-cat-classroom", groups: [
      { title: "ساختنی", items: ["بلاک ساختنی", "لگو و سازه", "پازل", "مدل‌سازی", "مهره و منجوق", "مغناطیسی", "خمیر بازی", "مکانیکی"] },
      { title: "آموزشی", items: ["بازی فکری و گروهی", "اسباب‌بازی آموزشی", "ریاضی و حروف", "ست آزمایش", "زبان و کلمات", "حافظه و تمرکز", "لوح و تخته", "چرتکه"] }
    ]},
    { name: "وسایل نقلیه", icon: "i-cat-car", groups: [
      { title: "انواع وسیله نقلیه", items: ["زمینی", "هوایی", "ریلی", "دریایی", "جنگی", "امدادی", "ماشین سنگین", "مدل", "کنترلی", "ریسینگ"] }
    ]},
    { name: "فضای باز و سوارشدنی", icon: "i-cat-scooter", groups: [
      { title: "فضای باز", items: ["سوارشدنی‌ها", "اسکوتر و اسکیت", "دوچرخه و سه‌چرخه", "پارک بازی", "خانه بازی", "وسایل بادی", "ورزشی", "لوازم جانبی و ایمنی", "اسکیت‌برد"] }
    ]},
    { name: "تفنگ و مبارزه", icon: "i-cat-tools", groups: [
      { title: "تفنگ و مبارزه", items: ["تفنگ", "تفنگ آبپاش", "لوازم مبارزه", "جاسوسی", "تیر و کمان", "شمشیر و سپر", "دارت و نشانه‌روی", "ست نظامی"] }
    ]},
    { name: "ماسک و تن‌پوش کارتونی", icon: "i-cat-fashion", groups: [
      { title: "ماسک و تن‌پوش", items: ["تن‌پوش کارتونی", "ماسک", "کلاه‌گیس", "لوازم جانبی", "بال و شنل", "دستکش و جوراب"] }
    ]},
    { name: "کیف، کوله و چمدان", icon: "i-cat-shop", groups: [
      { title: "کیف و کوله", items: ["کیف", "کوله‌پشتی", "چمدان", "ساک", "جامدادی", "کیف پول"] }
    ]},
    { name: "گریم و زیورآلات", icon: "i-cat-beauty", groups: [
      { title: "لوازم گریم", items: ["لوازم گریم", "زیورآلات", "اکسسوری مو", "ست آرایش", "ناخن و تتو موقت"] }
    ]},
    { name: "اتاق کودک", icon: "i-cat-home", groups: [
      { title: "اتاق کودک", items: ["چراغ خواب", "تخت و روتختی", "کمد و جعبه اسباب‌بازی", "تزئینات اتاق", "نیمکت و میز", "گهواره", "فرش و موکت"] }
    ]},
    { name: "آب بازی", icon: "i-cat-speaker", groups: [
      { title: "آب بازی", items: ["تفنگ آبپاش", "وسایل شنا", "وسایل حمام", "استخر و وسایل بادی", "وان و تشت", "سرسره آبی", "اسباب‌بازی حمام"] }
    ]},
    { name: "نوزاد و خردسال", icon: "i-cat-baby", groups: [
      { title: "نوزاد و خردسال", items: ["اسباب‌بازی نوزاد", "واکر و کالسکه", "آب بازی نوزاد", "لوازم نوزاد", "جغجغه و دندان‌گیر", "تشویقی آموزشی", "کتاب پارچه‌ای"] }
    ]},
    { name: "ست‌های بازی", icon: "i-gift", groups: [
      { title: "ست‌های بازی", items: ["ست عروسکی", "ست ماشینی", "ست جنگی", "ست ابزار", "ست دکتری", "ست مکانیکی", "ست فروشگاهی"] }
    ]},
    { name: "اسباب‌بازی چوبی", icon: "i-cat-tools", groups: [
      { title: "اسباب‌بازی چوبی", items: ["بلاک چوبی", "پازل چوبی", "قطار چوبی", "ماشین چوبی", "بازی چوبی", "آشپزخانه چوبی"] }
    ]},
    { name: "کتاب", icon: "i-cat-art", groups: [
      { title: "کتاب", items: ["کتاب کودک", "قصه و داستان", "کتاب آموزشی", "رنگ‌آمیزی", "کتاب لمسی و صوتی", "دفتر و نقاشی"] }
    ]},
    { name: "لوازم‌التحریر", icon: "i-cat-sign", groups: [
      { title: "لوازم‌التحریر", items: ["کیف و کوله پشتی", "نوشت‌افزار", "ماگ و فلاسک", "ظرف غذا", "میز تحریر و تخته", "ست آموزشی"] }
    ]},
    { name: "رباتیک و هوشمند", icon: "i-cat-digital", groups: [
      { title: "هوشمند", items: ["ربات آموزشی", "اسباب‌بازی هوشمند", "باتری و شارژر", "کنترل از راه دور", "ویدئو و پروژکتور"] }
    ]},
    { name: "حراجی‌ها", icon: "i-cat-ticket", groups: [
      { title: "تخفیف‌ها", items: ["حراج ویژه", "زیر قیمت", "پیشنهاد لحظه‌ای", "آخرین فرصت", "پک اقتصادی"] }
    ]},
    { name: "جدیدترین‌ها", icon: "i-cat-new", groups: [
      { title: "تازه‌ها", items: ["جدیدترین محصولات", "پرفروش‌ها", "پیشنهاد ما", "ویدئوها", "به‌زودی"] }
    ]}
  ];

  /* ---------- آیکون دسته‌های اصلی ----------
     فایل‌های assets/img/cc/*.jpg تصاویر واقعیِ دسته‌بندی‌های اسنپ‌شاپ هستند که
     از اسکرین‌شات زندهٔ سایت (شبکهٔ ۳ ستونهٔ صفحه اصلی) استخراج شده‌اند. */
  var catIcons = [
    "assets/img/cc/s01.jpg", "assets/img/cc/s02.jpg", "assets/img/cc/s03.jpg",
    "assets/img/cc/s04.jpg", "assets/img/cc/s05.jpg", "assets/img/cc/s06.jpg",
    "assets/img/cc/s07.jpg", "assets/img/cc/s08.jpg", "assets/img/cc/s09.jpg",
    "assets/img/cc/s10.jpg", "assets/img/cc/s11.jpg"
  ];
  categories.forEach(function (c, i) { c.img = catIcons[i % catIcons.length]; });

  /* استخر تصاویر محصولات (برای کاشی‌های زیردسته‌ها) */
  var productImgs = [].concat(products.amazing, products.newest, products.best)
    .map(function (p) { return p.img; })
    .filter(function (v, i, a) { return v && a.indexOf(v) === i; });

  return {
    banners: banners, stories: stories, brands: brands,
    products: products, categories: categories,
    catIcons: catIcons, productImgs: productImgs
  };
})();
