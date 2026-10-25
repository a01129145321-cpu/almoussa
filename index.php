<?php
require_once __DIR__ . '/includes/functions.php';
start_app_session();

$pdo = get_db();

// الأقسام مع عدد المنتجات في كل قسم
$categories = $pdo->query("
    SELECT c.*, COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id
    ORDER BY c.sort_order ASC
")->fetchAll();

// المنتجات المميزة
$featured = $pdo->query("
    SELECT p.*, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.is_featured = 1
    ORDER BY p.created_at DESC
    LIMIT 8
")->fetchAll();

// إن لم يُحدَّد أي منتج مميز من لوحة التحكم، نعرض أحدث القطع المتوفرة بعنوان محايد بدل ترك الصفحة الرئيسية بلا منتجات
$featuredIsFallback = false;
if (empty($featured)) {
    $featured = $pdo->query("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON c.id = p.category_id
        WHERE p.availability = 'available' AND p.stock_quantity > 0
        ORDER BY p.id DESC
        LIMIT 8
    ")->fetchAll();
    $featuredIsFallback = true;
}

$productCount = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

$page_title = 'الرئيسية';
$page_description = SITE_NAME . ' — ' . SITE_SLOGAN . '. تصفح آلاف قطع غيار تويوتا ولكزس الأصلية فقط بأفضل الأسعار.';

$schema_json = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'AutoPartsStore',
    'name' => SITE_NAME,
    'description' => SITE_SLOGAN,
    'telephone' => COMPANY_PHONE,
    'email' => COMPANY_EMAIL,
    'address' => COMPANY_ADDRESS,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

require __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div class="container hero-inner">
        <div>
            <span class="hero-eyebrow">قطع غيار تويوتا ولكزس — الأصلية فقط</span>
            <h1><?= clean(SITE_NAME) ?></h1>
            <p class="lead"><?= clean(SITE_SLOGAN) ?>. نوفر لك آلاف قطع الغيار بجودة مضمونة وأسعار منافسة، مع إمكانية الطلب أونلاين والتواصل المباشر عبر واتساب.</p>
            <div class="hero-actions">
                <a href="<?= base_url('products.php') ?>" class="btn btn-accent"><?= icon('search','icon-sm') ?> تصفح قطع الغيار</a>
                <a href="<?= whatsapp_link('مرحبًا، أرغب بطلب قطعة غيار') ?>" target="_blank" rel="noopener" class="btn btn-outline"><?= icon('whatsapp','icon-sm') ?> اطلب قطعة الآن</a>
            </div>
            <div class="hero-quickcontact">
                <a href="tel:<?= clean(COMPANY_PHONE) ?>">
                    <span class="qc-icon"><?= icon('phone') ?></span>
                    <?= clean(COMPANY_PHONE) ?>
                </a>
                <a href="<?= whatsapp_link() ?>" target="_blank" rel="noopener">
                    <span class="qc-icon"><?= icon('whatsapp') ?></span>
                    <?= clean(COMPANY_WHATSAPP) ?>
                </a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="hero-visual-grid">
                <div class="hero-stat"><b><?= $productCount ?>+</b><span>قطعة غيار متوفرة</span></div>
                <div class="hero-stat"><b><?= count($categories) ?></b><span>أقسام رئيسية</span></div>
                <div class="hero-stat"><b>100%</b><span>ضمان الجودة</span></div>
                <div class="hero-stat"><b>24/7</b><span>طلب عبر الموقع</span></div>
            </div>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>الأقسام الرئيسية</h2>
                <p>تسوّق حسب نوع القطعة التي تبحث عنها</p>
            </div>
            <a href="<?= base_url('products.php') ?>" class="link-more">عرض كل القطع</a>
        </div>
        <div class="category-grid">
            <?php foreach ($categories as $cat): ?>
            <a href="<?= base_url('products.php?category=' . (int)$cat['id']) ?>" class="category-card">
                <span class="cat-icon"><?= icon($cat['icon'] ?: 'box', 'icon-lg') ?></span>
                <div class="cat-name"><?= clean($cat['name']) ?></div>
                <div class="cat-count"><?= (int)$cat['product_count'] ?> منتج</div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($featured)): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <div>
                <h2><?= $featuredIsFallback ? 'قطع غيار متوفرة الآن' : 'المنتجات المميزة' ?></h2>
                <p><?= $featuredIsFallback ? 'جاهزة للطلب والتوصيل' : 'اختيارات مميزة من فريقنا' ?></p>
            </div>
            <a href="<?= base_url('products.php') ?>" class="link-more">عرض كل المنتجات</a>
        </div>
        <div class="product-grid">
            <?php foreach ($featured as $p): include __DIR__ . '/includes/product_card.php'; endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="section">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>لماذا تختارنا؟</h2>
                <p>نلتزم بتقديم أفضل تجربة شراء لقطع الغيار</p>
            </div>
        </div>
        <div class="why-grid">
            <div class="why-item">
                <span class="why-icon"><?= icon('shield') ?></span>
                <div><h3>ضمان الجودة</h3><p>جميع القطع أصلية 100% ومطابقة لمواصفات الوكالة المعتمدة.</p></div>
            </div>
            <div class="why-item">
                <span class="why-icon"><?= icon('truck') ?></span>
                <div><h3>توصيل سريع</h3><p>نجهّز طلبك بسرعة ونوفر خيارات استلام وتوصيل مناسبة.</p></div>
            </div>
            <div class="why-item">
                <span class="why-icon"><?= icon('badge') ?></span>
                <div><h3>خبرة طويلة</h3><p>سنوات من الخبرة في تجارة قطع غيار السيارات بمختلف الأنواع.</p></div>
            </div>
            <div class="why-item">
                <span class="why-icon"><?= icon('whatsapp') ?></span>
                <div><h3>دعم مباشر</h3><p>تواصل معنا في أي وقت عبر الهاتف أو واتساب للاستفسار عن أي قطعة.</p></div>
            </div>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container">
        <div class="section-head">
            <div>
                <h2>موقعنا</h2>
                <p><?= clean(COMPANY_ADDRESS) ?></p>
            </div>
            <a href="<?= clean(GOOGLE_MAPS_LINK) ?>" target="_blank" rel="noopener" class="link-more">فتح في خرائط جوجل</a>
        </div>
        <div class="map-embed"><?= google_maps_embed_html() ?></div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
