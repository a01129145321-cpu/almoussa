<?php
require_once __DIR__ . '/includes/functions.php';
start_app_session();

$formErrors = $_SESSION['contact_form_errors'] ?? [];
$formData = $_SESSION['contact_form_data'] ?? [];
unset($_SESSION['contact_form_errors'], $_SESSION['contact_form_data']);

$page_title = 'تواصل معنا';
$page_description = 'تواصل مع ' . SITE_NAME . ' عبر الهاتف أو واتساب أو البريد الإلكتروني، أو أرسل لنا رسالة مباشرة.';
require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>تواصل معنا</h1>
        <p>نحن هنا لمساعدتك، تواصل معنا بالطريقة الأنسب لك</p>
    </div>
</div>

<section class="section">
    <div class="container contact-grid">
        <div>
            <div class="contact-info-item">
                <span class="ci-icon"><?= icon('phone') ?></span>
                <div><h4>الهاتف</h4><p><a href="tel:<?= clean(COMPANY_PHONE) ?>"><?= clean(COMPANY_PHONE) ?></a></p></div>
            </div>
            <div class="contact-info-item">
                <span class="ci-icon"><?= icon('whatsapp') ?></span>
                <div><h4>واتساب</h4><p><a href="<?= whatsapp_link() ?>" target="_blank" rel="noopener"><?= clean(COMPANY_WHATSAPP) ?></a></p></div>
            </div>
            <div class="contact-info-item">
                <span class="ci-icon"><?= icon('email') ?></span>
                <div><h4>البريد الإلكتروني</h4><p><a href="mailto:<?= clean(COMPANY_EMAIL) ?>"><?= clean(COMPANY_EMAIL) ?></a></p></div>
            </div>
            <div class="contact-info-item">
                <span class="ci-icon"><?= icon('location') ?></span>
                <div><h4>العنوان</h4><p><?= clean(COMPANY_ADDRESS) ?></p></div>
            </div>
            <div class="contact-info-item">
                <span class="ci-icon"><?= icon('clock') ?></span>
                <div><h4>ساعات العمل</h4><p><?= clean(WORKING_HOURS) ?></p></div>
            </div>
            <?php if (VAT_NUMBER): ?>
            <div class="contact-info-item">
                <span class="ci-icon"><?= icon('badge') ?></span>
                <div><h4>الرقم الضريبي</h4><p><?= clean(VAT_NUMBER) ?></p></div>
            </div>
            <?php endif; ?>
            <?php if (SOCIAL_INSTAGRAM): ?>
            <div class="contact-info-item">
                <span class="ci-icon"><?= icon('instagram') ?></span>
                <div><h4>إنستقرام</h4><p><a href="<?= clean(SOCIAL_INSTAGRAM) ?>" target="_blank" rel="noopener">@almousa.t.e</a></p></div>
            </div>
            <?php endif; ?>

            <div class="map-embed"><?= google_maps_embed_html() ?></div>
            <a href="<?= clean(GOOGLE_MAPS_LINK) ?>" target="_blank" rel="noopener" class="btn btn-dark" style="margin-top:16px">
                <?= icon('location','icon-sm') ?> فتح الموقع في خرائط جوجل
            </a>
        </div>

        <div class="card-box">
            <h2 style="font-size:20px">أرسل لنا رسالة</h2>
            <?php if (!empty($formErrors)): ?>
                <div class="alert alert-danger"><?php foreach ($formErrors as $e): ?><div><?= clean($e) ?></div><?php endforeach; ?></div>
            <?php endif; ?>
            <form method="post" action="<?= base_url('contact_submit.php') ?>">
                <?= csrf_field() ?>
                <?= honeypot_field() ?>
                <div class="form-group">
                    <label>الاسم <span class="req">*</span></label>
                    <input type="text" name="name" required value="<?= clean($formData['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>رقم الهاتف</label>
                    <input type="tel" name="phone" value="<?= clean($formData['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>البريد الإلكتروني</label>
                    <input type="email" name="email" value="<?= clean($formData['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>الرسالة <span class="req">*</span></label>
                    <textarea name="message" required rows="5"><?= clean($formData['message'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-accent btn-block">إرسال الرسالة</button>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
