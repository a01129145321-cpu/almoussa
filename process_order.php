<?php
require_once __DIR__ . '/includes/functions.php';
start_app_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('cart.php'));
}

require_valid_csrf();

// روبوت: نتجاهل الطلب بصمت
if (honeypot_triggered()) {
    redirect(base_url('index.php'));
}

$cart = cart_details();
if (empty($cart['items'])) {
    flash_set('error', 'سلتك فارغة، الرجاء إضافة قطع غيار أولاً.');
    redirect(base_url('products.php'));
}

$data = [
    'customer_name' => limit_text(sanitize_text($_POST['customer_name'] ?? ''), 150),
    'phone'         => limit_text(sanitize_text($_POST['phone'] ?? ''), 30),
    'whatsapp'      => limit_text(sanitize_text($_POST['whatsapp'] ?? ''), 30),
    'email'         => limit_text(sanitize_text($_POST['email'] ?? ''), 150),
    'city'          => limit_text(sanitize_text($_POST['city'] ?? ''), 100),
    'address'       => limit_text(sanitize_text($_POST['address'] ?? ''), 255),
    'notes'         => limit_text(sanitize_text($_POST['notes'] ?? ''), 2000),
    'payment_method' => ($_POST['payment_method'] ?? '') === 'bank_transfer' && bank_transfer_configured() ? 'bank_transfer' : 'cod',
];

// الأرقام العربية (٠١٢...) تُحوَّل للاتينية قبل التحقق
$data['phone'] = normalize_number_string($data['phone']);
$data['whatsapp'] = normalize_number_string($data['whatsapp']);

$errors = [];
if ($data['customer_name'] === '') $errors[] = 'الرجاء إدخال الاسم الكامل.';
if ($data['phone'] === '') $errors[] = 'الرجاء إدخال رقم الهاتف.';
if ($data['phone'] !== '' && !preg_match('/^[0-9+\s\-]{7,20}$/', $data['phone'])) $errors[] = 'رقم الهاتف غير صالح.';
if ($data['city'] === '') $errors[] = 'الرجاء إدخال المحافظة أو المدينة.';
if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'صيغة البريد الإلكتروني غير صحيحة.';

if (!empty($errors)) {
    $_SESSION['order_form_errors'] = $errors;
    $_SESSION['order_form_data'] = $data;
    redirect(base_url('cart.php'));
}

$pdo = get_db();

try {
    $pdo->beginTransaction();

    // نقفل صفوف المنتجات ونعيد فحص المخزون داخل المعاملة، فلا يُباع نفس المخزون لطلبين متزامنين
    $lockStmt = $pdo->prepare('SELECT stock_quantity, availability, price, name FROM products WHERE id = :id FOR UPDATE');
    $stockErrors = [];
    foreach ($cart['items'] as $item) {
        $lockStmt->execute([':id' => $item['product']['id']]);
        $fresh = $lockStmt->fetch();
        if (!$fresh) {
            $stockErrors[] = 'المنتج "' . $item['product']['name'] . '" لم يعد متوفرًا في الكتالوج.';
            continue;
        }
        $stock = (int)$fresh['stock_quantity'];
        if ($fresh['availability'] === 'out_of_stock') {
            $stockErrors[] = 'المنتج "' . $fresh['name'] . '" غير متوفر حاليًا، الرجاء إزالته من السلة.';
        } elseif ($fresh['availability'] === 'available' && $item['quantity'] > $stock) {
            $stockErrors[] = 'الكمية المطلوبة من "' . $fresh['name'] . '" أكبر من المتوفر (' . $stock . ').';
        } elseif (abs((float)$fresh['price'] - (float)$item['product']['price']) > 0.001) {
            $stockErrors[] = 'تغيّر سعر "' . $fresh['name'] . '" أثناء تصفحك، الرجاء مراجعة السلة ثم إعادة الإرسال.';
        }
    }
    if (!empty($stockErrors)) {
        $pdo->rollBack();
        $_SESSION['order_form_errors'] = $stockErrors;
        $_SESSION['order_form_data'] = $data;
        redirect(base_url('cart.php'));
    }

    $orderNumber = generate_order_number();

    $stmt = $pdo->prepare("
        INSERT INTO orders (order_number, customer_name, phone, whatsapp, email, city, address, notes, subtotal_amount, shipping_amount, vat_amount, total_amount, payment_method, status)
        VALUES (:order_number, :customer_name, :phone, :whatsapp, :email, :city, :address, :notes, :subtotal_amount, :shipping_amount, :vat_amount, :total_amount, :payment_method, 'new')
    ");
    $stmt->execute([
        ':order_number'  => $orderNumber,
        ':customer_name' => $data['customer_name'],
        ':phone'         => $data['phone'],
        ':whatsapp'      => $data['whatsapp'],
        ':email'         => $data['email'],
        ':city'          => $data['city'],
        ':address'       => $data['address'],
        ':notes'         => $data['notes'],
        ':subtotal_amount' => $cart['subtotal'],
        ':shipping_amount' => $cart['shipping'],
        ':vat_amount'    => $cart['vat'],
        ':total_amount'  => $cart['total'],
        ':payment_method' => $data['payment_method'],
    ]);

    $orderId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, part_number, price, quantity, subtotal)
        VALUES (:order_id, :product_id, :product_name, :part_number, :price, :quantity, :subtotal)
    ");

    $itemsForEmail = [];
    foreach ($cart['items'] as $item) {
        $p = $item['product'];
        $itemStmt->execute([
            ':order_id'     => $orderId,
            ':product_id'   => $p['id'],
            ':product_name' => $p['name'],
            ':part_number'  => $p['part_number'],
            ':price'        => $p['price'],
            ':quantity'     => $item['quantity'],
            ':subtotal'     => $item['subtotal'],
        ]);
        apply_stock_delta($pdo, (int)$p['id'], -$item['quantity']);
        $itemsForEmail[] = [
            'product_name' => $p['name'],
            'part_number'  => $p['part_number'],
            'quantity'     => $item['quantity'],
            'subtotal'     => $item['subtotal'],
        ];
    }

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('process_order failed: ' . $e->getMessage());
    flash_set('error', DEBUG_MODE ? 'خطأ: ' . $e->getMessage() : 'حدث خطأ أثناء إرسال الطلب، الرجاء المحاولة مرة أخرى.');
    redirect(base_url('cart.php'));
}

// إشعار بريدي (لا يوقف العملية إذا فشل الإرسال)
try {
    send_order_notification_email(
        ['order_number' => $orderNumber] + $data + ['subtotal_amount' => $cart['subtotal'], 'shipping_amount' => $cart['shipping'], 'vat_amount' => $cart['vat'], 'total_amount' => $cart['total']],
        $itemsForEmail
    );
} catch (Throwable $e) {
    // تجاهل أخطاء البريد حتى لا تمنع نجاح الطلب
}

cart_clear();
$_SESSION['last_order_number'] = $orderNumber;

redirect(base_url('order_success.php'));
