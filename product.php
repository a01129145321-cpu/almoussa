<?php
require_once __DIR__ . '/includes/functions.php';
start_app_session();

$pdo = get_db();
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name, m.name AS manufacturer_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN manufacturers m ON m.id = p.manufacturer_id
    WHERE p.id = :id
    LIMIT 1
");
$stmt->execute([':id' => $id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    $page_title = 'المنتج غير موجود';
    $page_description = 'عذرًا، هذا المنتج غير موجود أو تم حذفه.';
    require __DIR__ . '/includes/header.php';
    echo '<div class="container section"><div class="alert alert-danger">عذرًا، هذا المنتج غير موجود أو تم حذفه.</div>
          <a href="' . base_url('products.php') . '" class="btn btn-dark">العودة لقطع الغيار</a></div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

// تحديث عدد المشاهدات (غير حرج، بدون قفل)
$pdo->prepare("UPDATE products SET views = views + 1 WHERE id = :id")->execute([':id' => $id]);

// صور إضافية
$imgStmt = $pdo->prepare("SELECT * FROM product_images WHERE product_id = :id ORDER BY sort_order ASC");
$imgStmt->execute([':id' => $id]);
$extraImages = $imgStmt->fetchAll();

$allImages = array_merge(
    [!empty($product['main_image']) ? $product['main_image'] : find_part_image($product['part_number'])],
    array_column($extraImages, 'image_path')
);
$allImages = array_values(array_filter($allImages));
if (empty($allImages)) {
    $allImages = [null]; // سيستخدم placeholder
}

// منتجات ذات صلة (نفس القسم)
$relStmt = $pdo->prepare("
    SELECT p.*, c.name AS category_name FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE p.category_id = :cat AND p.id != :id
    ORDER BY RAND() LIMIT 4
");
$relStmt->execute([':cat' => $product['category_id'], ':id' => $id]);
$related = $relStmt->fetchAll();

$page_title = $product['name'];
$descPlain = trim(strip_tags((string)$product['description']));
$page_description = $descPlain !== ''
    ? mb_substr($descPlain, 0, 155)
    : $product['name'] . ' — رقم القطعة ' . $product['part_number'] . ' من ' . SITE_NAME . '. اطلبها الآن أونلاين.';

$schema_json = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $product['name'],
    'sku' => $product['part_number'],
    'description' => $descPlain !== '' ? $descPlain : $product['name'],
    'brand' => ['@type' => 'Brand', 'name' => $product['manufacturer_name'] ?: SITE_NAME],
    'offers' => [
        '@type' => 'Offer',
        'priceCurrency' => 'SAR',
        'price' => number_format((float)$product['price'], 2, '.', ''),
        'availability' => $product['availability'] === 'out_of_stock' ? 'https://schema.org/OutOfStock' : ($product['availability'] === 'on_order' ? 'https://schema.org/PreOrder' : 'https://schema.org/InStock'),
        'url' => abs_url('product.php?id=' . (int)$product['id']),
    ],
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG);

require __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding-top:24px">
    <nav style="font-size:13px;color:var(--color-text-muted);margin-bottom:18px">
        <a href="<?= base_url('index.php') ?>">الرئيسية</a> /
        <a href="<?= base_url('products.php') ?>">قطع الغيار</a>
        <?php if ($product['category_name']): ?>
            / <a href="<?= base_url('products.php?category=' . (int)$product['category_id']) ?>"><?= clean($product['category_name']) ?></a>
        <?php endif; ?>
        / <span><?= clean($product['name']) ?></span>
    </nav>
</div>

<section class="container" style="padding-bottom:56px">
    <div class="product-detail">
        <div>
            <div class="gallery-main">
                <img src="<?= clean(product_image_url($allImages[0])) ?>" alt="<?= clean($product['name']) ?>" data-gallery-main>
            </div>
            <?php if (count($allImages) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach ($allImages as $i => $img): ?>
                    <img src="<?= clean(product_image_url($img)) ?>" data-gallery-thumb data-full="<?= clean(product_image_url($img)) ?>" class="<?= $i === 0 ? 'active' : '' ?>" alt="صورة <?= $i + 1 ?>">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div>
            <span class="badge <?= availability_class($product['availability']) ?>" style="position:static;display:inline-block;margin-bottom:10px"><?= availability_label($product['availability']) ?></span>
            <h1><?= clean($product['name']) ?></h1>
            <span class="part-tag" style="font-size:14px;padding:6px 12px"><?= icon('badge','icon-sm') ?> رقم القطعة: <?= clean($product['part_number']) ?></span>

            <div style="margin:18px 0">
                <?php if (!empty($product['old_price']) && $product['old_price'] > $product['price']): ?>
                    <span class="price-old" style="font-size:16px"><?= format_price((float)$product['old_price']) ?></span>
                <?php endif; ?>
                <span class="price" style="font-size:28px"><?= format_price((float)$product['price']) ?></span>
                <div style="font-size:13px;color:var(--color-text-muted);margin-top:4px">السعر غير شامل ضريبة القيمة المضافة (<?= rtrim(rtrim(number_format(VAT_RATE * 100, 2), '0'), '.') ?>%)، وتُضاف على إجمالي الطلب.</div>
                <?php if ($stock = stock_display((int)$product['stock_quantity'], $product['availability'])): ?>
                    <div class="stock-note <?= $stock['low'] ? 'stock-note-low' : '' ?>" style="margin-top:8px;font-size:14px"><?= clean($stock['text']) ?></div>
                <?php endif; ?>
            </div>

            <table class="spec-table">
                <tr><td>الشركة المصنعة</td><td><?= clean($product['manufacturer_name'] ?: 'غير محدد') ?></td></tr>
                <tr><td>القسم</td><td><?= clean($product['category_name'] ?: 'غير محدد') ?></td></tr>
                <tr><td>حالة التوفر</td><td><?= availability_label($product['availability']) ?></td></tr>
                <?php if ((int)$product['stock_quantity'] > 0 && $product['availability'] === 'available'): ?>
                <tr><td>الكمية المتوفرة</td><td><?= (int)$product['stock_quantity'] ?> قطعة</td></tr>
                <?php endif; ?>
                <?php if (!empty($product['compatible_cars'])): ?>
                <tr><td>السيارات المتوافقة</td><td><?= clean($product['compatible_cars']) ?></td></tr>
                <?php endif; ?>
            </table>

            <?php if (!empty($product['description'])): ?>
                <h3 style="font-size:16px">الوصف</h3>
                <p style="color:var(--color-text-muted)"><?= nl2br(clean($product['description'])) ?></p>
            <?php endif; ?>

            <form id="addToCartForm" style="display:flex;gap:14px;align-items:center;flex-wrap:wrap;margin-top:20px">
                <div class="qty-control">
                    <button type="button" data-qty-minus>−</button>
                    <input type="number" min="1" value="1" data-qty-for="<?= (int)$product['id'] ?>">
                    <button type="button" data-qty-plus>+</button>
                </div>
                <button type="button" class="btn btn-accent" data-add-cart data-product-id="<?= (int)$product['id'] ?>"
                        <?= $product['availability'] === 'out_of_stock' ? 'disabled' : '' ?>>
                    <?= icon('cart','icon-sm') ?> أضف إلى الطلب
                </button>
                <a href="<?= whatsapp_link('مرحبًا، أرغب بالاستفسار عن: ' . $product['name'] . ' (رقم القطعة: ' . $product['part_number'] . ')') ?>"
                   target="_blank" rel="noopener" class="btn btn-whatsapp">
                    <?= icon('whatsapp','icon-sm') ?> استفسار عبر واتساب
                </a>
            </form>
        </div>
    </div>
</section>

<?php if (!empty($related)): ?>
<section class="section section-alt">
    <div class="container">
        <div class="section-head"><div><h2>منتجات ذات صلة</h2></div></div>
        <div class="product-grid">
            <?php foreach ($related as $p): include __DIR__ . '/includes/product_card.php'; endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
