<?php
require_once __DIR__ . '/includes/functions.php';
start_app_session();

$page_title = 'من نحن';
$page_description = 'تعرّف على ' . SITE_NAME . '، خبرتنا في تجارة قطع غيار السيارات، وخدماتنا ونقاط قوتنا.';
require __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>من نحن</h1>
        <p><?= clean(SITE_SLOGAN) ?></p>
    </div>
</div>

<section class="section">
    <div class="container" style="max-width:900px">
        <h2>نبذة عن الشركة</h2>
        <p style="color:var(--color-text-muted)">
            <?= clean(SITE_NAME) ?> شركة متخصصة في بيع قطع غيار تويوتا ولكزس الأصلية فقط، ومقرها في
            المنطقة الصناعية بمحافظة الزلفي بالمملكة العربية السعودية. نتيح لعملائنا تصفح آلاف القطع
            وطلبها أونلاين مباشرة، مع فريق دعم جاهز عبر الهاتف والواتساب للإجابة على استفساراتكم
            وتوجيهكم لاختيار القطعة الصحيحة المتوافقة مع سيارتكم.
        </p>

        <h2 style="margin-top:36px">خبرتنا</h2>
        <p style="color:var(--color-text-muted)">
            سنوات من الخبرة المتراكمة في قطاع قطع غيار تويوتا ولكزس مكّنتنا من بناء علاقات موثوقة مع
            موردين معتمدين، وفهم عميق لاحتياجات عملائنا من مختلف موديلات هاتين الشركتين تحديدًا.
        </p>

        <h2 style="margin-top:36px">خدماتنا</h2>
        <div class="why-grid">
            <div class="why-item">
                <span class="why-icon"><?= icon('box') ?></span>
                <div><h3>تشكيلة واسعة</h3><p>قطع غيار لمختلف الأقسام: محركات، فرامل، كهرباء، تعليق وأكثر.</p></div>
            </div>
            <div class="why-item">
                <span class="why-icon"><?= icon('truck') ?></span>
                <div><h3>توصيل الطلبات</h3><p>نوفر خيارات استلام وتوصيل مناسبة بعد تأكيد الطلب.</p></div>
            </div>
            <div class="why-item">
                <span class="why-icon"><?= icon('wrench') ?></span>
                <div><h3>استشارة فنية</h3><p>نساعدك في اختيار القطعة الصحيحة المتوافقة مع سيارتك.</p></div>
            </div>
        </div>

        <h2 style="margin-top:36px">نقاط قوتنا</h2>
        <ul style="list-style:disc;padding-inline-start:22px;color:var(--color-text-muted)">
            <li>قطع غيار مضمونة الجودة من مصادر موثوقة.</li>
            <li>أسعار تنافسية مقارنة بالسوق.</li>
            <li>سهولة الطلب أونلاين ومتابعة عبر واتساب.</li>
            <li>فريق دعم متجاوب لحل استفسارات العملاء.</li>
        </ul>

        <div style="margin-top:30px">
            <a href="<?= base_url('contact.php') ?>" class="btn btn-accent">تواصل معنا</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
