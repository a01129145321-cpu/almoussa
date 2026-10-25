<?php
require_once __DIR__ . '/includes/functions.php';
start_app_session();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'طريقة غير مسموحة']);
    exit;
}

if (!verify_csrf()) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'انتهت صلاحية الجلسة، الرجاء إعادة تحميل الصفحة']);
    exit;
}

set_exception_handler(function (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'تعذر تنفيذ العملية الآن، حاول مرة أخرى.']);
    exit;
});

$action = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);
$quantity = min(9999, max(1, (int)($_POST['quantity'] ?? 1)));

if ($productId <= 0) {
    echo json_encode(['success' => false, 'message' => 'منتج غير صالح']);
    exit;
}

// التأكد أن المنتج موجود فعلاً ومتوفر قبل الإضافة
$pdo = get_db();
$stmt = $pdo->prepare('SELECT id, availability, stock_quantity FROM products WHERE id = :id');
$stmt->execute([':id' => $productId]);
$product = $stmt->fetch();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'المنتج غير موجود']);
    exit;
}

switch ($action) {
    case 'add':
        if ($product['availability'] === 'out_of_stock') {
            echo json_encode(['success' => false, 'message' => 'هذا المنتج غير متوفر حاليًا']);
            exit;
        }
        $inCart = (int)(cart_items()[$productId] ?? 0);
        $stock = (int)$product['stock_quantity'];
        if ($stock > 0 && $inCart + $quantity > $stock) {
            cart_update($productId, $stock);
            echo json_encode(['success' => true, 'cart_count' => cart_count(), 'cart_total' => format_price(cart_details()['total']), 'message' => 'الكمية المتوفرة من هذه القطعة: ' . $stock]);
            exit;
        }
        cart_add($productId, $quantity);
        break;

    case 'update':
        cart_update($productId, $quantity);
        break;

    case 'remove':
        cart_remove($productId);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'إجراء غير معروف']);
        exit;
}

$details = cart_details();

echo json_encode([
    'success' => true,
    'cart_count' => cart_count(),
    'cart_total' => format_price($details['total']),
]);
