<?php
require_once __DIR__ . '/includes/functions.php';
start_app_session();

$orderNumber = $_SESSION['last_order_number'] ?? null;
unset($_SESSION['last_order_number']);

if (!$orderNumber) {
    redirect(base_url('index.php'));
}

$stmt = get_db()->prepare('SELECT subtotal_amount, shipping_amount, vat_amount, total_amount, payment_method FROM orders WHERE order_number = :n LIMIT 1');
$stmt->execute([':n' => $orderNumber]);
$order = $stmt->fetch();

$page_title = 'تم إرسال طلبك بنجاح';
require __DIR__ . '/includes/header.php';
?>

<section class="section">
    <div class="container" style="max-width:640px;text-align:center">
        <div class="card-box">
            <div style="width:70px;height:70px;background:var(--color-success-bg);color:var(--color-success);border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 18px">
                <?= icon('check', 'icon-lg') ?>
            </div>
            <h1>تم إرسال طلبك بنجاح!</h1>
            <p style="color:var(--color-text-muted)">شكرًا لثقتك بـ <?= clean(SITE_NAME) ?>. سيتواصل معك فريقنا قريبًا لتأكيد الطلب.</p>
            <div class="part-tag" style="font-size:16px;padding:10px 18px;margin:16px auto;justify-content:center">
                رقم الطلب: <?= clean($orderNumber) ?>
            </div>

            <?php if ($order): ?>
            <div class="cart-summary" style="text-align:start;max-width:360px;margin:20px auto 0">
                <div class="summary-row"><span>الإجمالي قبل الضريبة</span><span><?= format_price((float)$order['subtotal_amount']) ?></span></div>
                <div class="summary-row"><span>الشحن</span><span><?= (float)$order['shipping_amount'] > 0 ? format_price((float)$order['shipping_amount']) : 'مجاني' ?></span></div>
                <div class="summary-row"><span>ضريبة القيمة المضافة (<?= VAT_RATE * 100 ?>%)</span><span><?= format_price((float)$order['vat_amount']) ?></span></div>
                <div class="summary-row total"><span>الإجمالي شامل الضريبة</span><span><?= format_price((float)$order['total_amount']) ?></span></div>
                <?php if (VAT_NUMBER): ?><p style="font-size:12px;color:var(--color-text-muted);margin:10px 0 0">الرقم الضريبي: <?= clean(VAT_NUMBER) ?></p><?php endif; ?>
            </div>

            <?php if ($order['payment_method'] === 'bank_transfer'): ?>
                <div class="bank-details-box" style="text-align:start;max-width:360px;margin:16px auto 0">
                    <p style="margin:0 0 8px;font-weight:700">طلبك بانتظار التحويل البنكي:</p>
                    <div class="spec-table-mini">
                        <div><span>البنك</span><b><?= clean(BANK_NAME) ?></b></div>
                        <div><span>اسم الحساب</span><b><?= clean(BANK_ACCOUNT_NAME) ?></b></div>
                        <div><span>الآيبان</span><b dir="ltr"><?= clean(BANK_IBAN) ?></b></div>
                        <div><span>المبلغ</span><b><?= format_price((float)$order['total_amount']) ?></b></div>
                    </div>
                    <p style="font-size:12px;color:var(--color-text-muted);margin:8px 0 0">حوّل المبلغ واذكر رقم الطلب <?= clean($orderNumber) ?> كمرجع، ويُؤكَّد طلبك فور استلام التحويل.</p>
                </div>
            <?php else: ?>
                <p style="font-size:13px;color:var(--color-text-muted);max-width:360px;margin:12px auto 0">طريقة الدفع: الدفع عند الاستلام.</p>
            <?php endif; ?>
            <?php endif; ?>

            <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:20px">
                <a href="<?= base_url('products.php') ?>" class="btn btn-dark">متابعة التسوق</a>
                <a href="<?= whatsapp_link('مرحبًا، بخصوص طلبي رقم ' . $orderNumber) ?>" target="_blank" rel="noopener" class="btn btn-whatsapp">
                    <?= icon('whatsapp','icon-sm') ?> تواصل معنا بخصوص الطلب
                </a>
            </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
