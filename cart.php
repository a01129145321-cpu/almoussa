<?php
require_once __DIR__ . '/includes/functions.php';
start_app_session();

// معالجة تحديث/حذف عناصر السلة (تعمل دون الحاجة لجافاسكربت) - نمط PRG لمنع إعادة الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_form_action'])) {
    require_valid_csrf();

    if ($_POST['cart_form_action'] === 'update' && isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $productId => $qty) {
            cart_update((int)$productId, (int)$qty);
        }
    } elseif ($_POST['cart_form_action'] === 'remove') {
        cart_remove((int)($_POST['product_id'] ?? 0));
    } elseif ($_POST['cart_form_action'] === 'clear') {
        cart_clear();
    }

    redirect(base_url('cart.php'));
}

$cart = cart_details();
$formErrors = $_SESSION['order_form_errors'] ?? [];
$formData = $_SESSION['order_form_data'] ?? [];
unset($_SESSION['order_form_errors'], $_SESSION['order_form_data']);

$page_title = 'سلة الطلب';
$page_description = 'راجع منتجاتك وأكمل بيانات الطلب لإرساله إلى ' . SITE_NAME;
require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>سلة الطلب</h1>
        <p>راجع القطع التي اخترتها ثم أكمل بيانات التوصيل لإرسال طلبك</p>
    </div>
</div>

<section class="section">
    <div class="container">
    <?php if (empty($cart['items'])): ?>
        <div class="alert alert-info">سلتك فارغة حاليًا.</div>
        <a href="<?= base_url('products.php') ?>" class="btn btn-dark"><?= icon('search','icon-sm') ?> تصفح قطع الغيار</a>
    <?php else: ?>
        <div class="filters-layout layout-main-side">
            <div>
                <form method="post" action="<?= base_url('cart.php') ?>">
                    <?= csrf_field() ?>
                    <input type="hidden" name="cart_form_action" value="update">
                    <div style="overflow-x:auto">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>المنتج</th>
                                <th>السعر</th>
                                <th>الكمية</th>
                                <th>الإجمالي (قبل الضريبة)</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cart['items'] as $item): $p = $item['product']; ?>
                            <tr>
                                <td data-label="المنتج">
                                    <div class="cart-product">
                                        <img src="<?= clean(product_thumb_url($p)) ?>" alt="<?= clean($p['name']) ?>">
                                        <div>
                                            <a href="<?= base_url('product.php?id=' . (int)$p['id']) ?>" style="font-weight:700"><?= clean($p['name']) ?></a>
                                            <div class="part-tag" style="margin-top:4px"><?= clean($p['part_number']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="السعر"><?= format_price((float)$p['price']) ?></td>
                                <td data-label="الكمية">
                                    <input type="number" name="qty[<?= (int)$p['id'] ?>]" value="<?= (int)$item['quantity'] ?>" min="0" style="width:80px">
                                </td>
                                <td data-label="الإجمالي"><?= format_price($item['subtotal']) ?></td>
                                <td>
                                    <button type="submit" form="removeForm<?= (int)$p['id'] ?>" class="remove-link" style="background:none;border:none;padding:0"><?= icon('trash','icon-sm') ?></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </div>
                    <div style="margin-top:16px;display:flex;gap:10px">
                        <button type="submit" class="btn btn-dark btn-sm">تحديث الكميات</button>
                    </div>
                </form>

                <?php foreach ($cart['items'] as $item): $p = $item['product']; ?>
                <form id="removeForm<?= (int)$p['id'] ?>" method="post" action="<?= base_url('cart.php') ?>" style="display:none">
                    <?= csrf_field() ?>
                    <input type="hidden" name="cart_form_action" value="remove">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                </form>
                <?php endforeach; ?>
            </div>

            <div>
                <div class="cart-summary">
                    <h3 style="margin-bottom:14px">ملخص الطلب</h3>
                    <div class="summary-row"><span>عدد القطع</span><span><?= array_sum(array_column($cart['items'], 'quantity')) ?></span></div>
                    <div class="summary-row"><span>الإجمالي قبل الضريبة</span><span><?= format_price($cart['subtotal']) ?></span></div>
                    <div class="summary-row"><span>الشحن</span><span><?= $cart['shipping'] > 0 ? format_price($cart['shipping']) : '<b style="color:var(--color-success)">مجاني</b>' ?></span></div>
                    <div class="summary-row"><span>ضريبة القيمة المضافة (<?= rtrim(rtrim(number_format(VAT_RATE * 100, 2), '0'), '.') ?>%)</span><span><?= format_price($cart['vat']) ?></span></div>
                    <div class="summary-row total"><span>الإجمالي شامل الضريبة</span><span><?= format_price($cart['total']) ?></span></div>
                    <p style="font-size:12px;color:var(--color-text-muted);margin:10px 0 0">أسعار القطع غير شاملة الضريبة، وتُضاف الضريبة على الإجمالي شاملًا الشحن.</p>
                    <?php if ($cart['shipping'] > 0): ?>
                        <p style="font-size:13px;margin:10px 0 0;color:var(--color-accent-dark);font-weight:700">أضف قطعًا بقيمة <?= format_price(FREE_SHIPPING_THRESHOLD - $cart['subtotal'] + 0.01) ?> أخرى (قبل الضريبة) لتحصل على شحن مجاني.</p>
                    <?php else: ?>
                        <p style="font-size:13px;margin:10px 0 0;color:var(--color-success);font-weight:700">شحن مجاني لطلبك لأنه تجاوز <?= format_price(FREE_SHIPPING_THRESHOLD) ?>.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card-box" style="margin-top:30px;max-width:760px">
            <h2 style="font-size:20px">بيانات التوصيل</h2>
            <p style="color:var(--color-text-muted);margin-bottom:20px">أدخل بياناتك لإتمام إرسال الطلب، سيتواصل معك فريقنا للتأكيد.</p>

            <form method="post" action="<?= base_url('process_order.php') ?>">
                <?= csrf_field() ?>
                <?= honeypot_field() ?>
                <div class="form-grid">
                    <div class="form-group">
                        <label>الاسم الكامل <span class="req">*</span></label>
                        <input type="text" name="customer_name" required maxlength="150" value="<?= clean($formData['customer_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>رقم الهاتف <span class="req">*</span></label>
                        <input type="tel" name="phone" required maxlength="20" value="<?= clean($formData['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>رقم واتساب</label>
                        <input type="tel" name="whatsapp" value="<?= clean($formData['whatsapp'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>البريد الإلكتروني</label>
                        <input type="email" name="email" value="<?= clean($formData['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>المحافظة / المدينة <span class="req">*</span></label>
                        <input type="text" name="city" required value="<?= clean($formData['city'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>العنوان</label>
                        <input type="text" name="address" value="<?= clean($formData['address'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>ملاحظات</label>
                    <textarea name="notes"><?= clean($formData['notes'] ?? '') ?></textarea>
                </div>

                <div class="form-group">
                    <label>طريقة الدفع <span class="req">*</span></label>
                    <div class="payment-options">
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="cod"
                                <?= ($formData['payment_method'] ?? 'cod') === 'cod' ? 'checked' : '' ?>>
                            <span><b>الدفع عند الاستلام</b><br><small>ادفع نقدًا أو بالشبكة عند استلام طلبك</small></span>
                        </label>
                        <?php if (bank_transfer_configured()): ?>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="bank_transfer" id="payBankTransfer"
                                <?= ($formData['payment_method'] ?? '') === 'bank_transfer' ? 'checked' : '' ?>>
                            <span><b>تحويل بنكي</b><br><small>حوّل المبلغ ثم أرسل طلبك، ويُؤكَّد الطلب بعد استلام التحويل</small></span>
                        </label>
                        <?php endif; ?>
                    </div>
                    <?php if (bank_transfer_configured()): ?>
                    <div class="bank-details-box" id="bankDetailsBox">
                        <p style="margin:0 0 8px;font-weight:700">بيانات الحساب البنكي:</p>
                        <div class="spec-table-mini">
                            <div><span>البنك</span><b><?= clean(BANK_NAME) ?></b></div>
                            <div><span>اسم الحساب</span><b><?= clean(BANK_ACCOUNT_NAME) ?></b></div>
                            <div><span>الآيبان</span><b dir="ltr"><?= clean(BANK_IBAN) ?></b></div>
                        </div>
                        <p style="font-size:12px;color:var(--color-text-muted);margin:8px 0 0">بعد التحويل، أرسل الطلب وسيتواصل معك فريقنا لتأكيد استلام المبلغ.</p>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($formErrors)): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($formErrors as $err): ?><div><?= clean($err) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <button type="submit" class="btn btn-accent btn-block"><?= icon('check','icon-sm') ?> إرسال الطلب</button>
            </form>
        </div>
    <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
