<?php
require_once __DIR__ . '/includes/functions.php';
start_app_session();

$pdo = get_db();

$search        = limit_text(trim((string)($_GET['q'] ?? '')), 100);
$categoryId    = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$manufacturerId = isset($_GET['manufacturer']) ? (int)$_GET['manufacturer'] : 0;
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = ITEMS_PER_PAGE;

$where = ['1=1'];
$params = [];

if ($search !== '') {
    $where[] = product_search_sql($search, $params);
}
if ($categoryId > 0) {
    $where[] = 'p.category_id = :cat';
    $params[':cat'] = $categoryId;
}
if ($manufacturerId > 0) {
    $where[] = 'p.manufacturer_id = :man';
    $params[':man'] = $manufacturerId;
}

$whereSql = implode(' AND ', $where);

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p WHERE $whereSql");
$countStmt->execute($params);
$totalItems = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalItems / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT p.*, c.name AS category_name, m.name AS manufacturer_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    LEFT JOIN manufacturers m ON m.id = p.manufacturer_id
    WHERE $whereSql
    ORDER BY p.id DESC
    LIMIT :limit OFFSET :offset
";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY sort_order ASC")->fetchAll();
$manufacturers = $pdo->query("SELECT * FROM manufacturers ORDER BY name ASC")->fetchAll();

function build_query(array $override = []): string
{
    $current = $_GET;
    foreach ($override as $k => $v) {
        if ($v === null) {
            unset($current[$k]);
        } else {
            $current[$k] = $v;
        }
    }
    return '?' . http_build_query($current);
}

$page_title = 'قطع الغيار';
$page_description = 'تصفح جميع قطع غيار السيارات المتوفرة لدى ' . SITE_NAME . ' وابحث برقم القطعة أو الاسم أو الشركة المصنعة.';
require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>قطع الغيار</h1>
        <p>تصفح <?= $totalItems ?> قطعة غيار متوفرة، وابحث بالاسم أو رقم القطعة</p>
    </div>
</div>

<section class="section">
    <div class="container">
        <form action="<?= base_url('products.php') ?>" method="get" style="margin-bottom:26px">
            <div style="display:flex;gap:10px;max-width:560px">
                <input type="text" name="q" value="<?= clean($search) ?>" placeholder="ابحث بالاسم أو رقم القطعة...">
                <?php if ($categoryId): ?><input type="hidden" name="category" value="<?= $categoryId ?>"><?php endif; ?>
                <?php if ($manufacturerId): ?><input type="hidden" name="manufacturer" value="<?= $manufacturerId ?>"><?php endif; ?>
                <button type="submit" class="btn btn-dark"><?= icon('search','icon-sm') ?> بحث</button>
            </div>
        </form>

        <div class="filters-layout">
            <aside class="filters-box card-box">
                <div class="filter-group">
                    <h4>الأقسام</h4>
                    <div class="filter-option">
                        <a href="<?= build_query(['category' => null, 'page' => null]) ?>" style="font-weight:<?= $categoryId === 0 ? '800' : '500' ?>; color: <?= $categoryId === 0 ? 'var(--color-accent)' : 'inherit' ?>">
                            كل الأقسام
                        </a>
                    </div>
                    <?php foreach ($categories as $cat): ?>
                    <div class="filter-option">
                        <a href="<?= build_query(['category' => $cat['id'], 'page' => null]) ?>" style="font-weight:<?= $categoryId === (int)$cat['id'] ? '800' : '500' ?>; color: <?= $categoryId === (int)$cat['id'] ? 'var(--color-accent)' : 'inherit' ?>">
                            <?= clean($cat['name']) ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="filter-group">
                    <h4>الشركة المصنعة</h4>
                    <div class="filter-option">
                        <a href="<?= build_query(['manufacturer' => null, 'page' => null]) ?>" style="font-weight:<?= $manufacturerId === 0 ? '800' : '500' ?>; color: <?= $manufacturerId === 0 ? 'var(--color-accent)' : 'inherit' ?>">
                            الكل
                        </a>
                    </div>
                    <?php foreach ($manufacturers as $man): ?>
                    <div class="filter-option">
                        <a href="<?= build_query(['manufacturer' => $man['id'], 'page' => null]) ?>" style="font-weight:<?= $manufacturerId === (int)$man['id'] ? '800' : '500' ?>; color: <?= $manufacturerId === (int)$man['id'] ? 'var(--color-accent)' : 'inherit' ?>">
                            <?= clean($man['name']) ?>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($categoryId || $manufacturerId || $search): ?>
                    <a href="<?= base_url('products.php') ?>" class="btn btn-sm btn-dark btn-block">إلغاء التصفية</a>
                <?php endif; ?>
            </aside>

            <div>
                <?php if (empty($products)): ?>
                    <div class="alert alert-info">لا توجد نتائج مطابقة لبحثك. جرّب كلمات أو تصفية مختلفة.</div>
                <?php else: ?>
                    <div class="product-grid">
                        <?php foreach ($products as $p): include __DIR__ . '/includes/product_card.php'; endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?><a href="<?= build_query(['page' => $page - 1]) ?>">السابق</a><?php endif; ?>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <?php if ($i === $page): ?>
                                <span class="active"><?= $i ?></span>
                            <?php else: ?>
                                <a href="<?= build_query(['page' => $i]) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        <?php if ($page < $totalPages): ?><a href="<?= build_query(['page' => $page + 1]) ?>">التالي</a><?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
