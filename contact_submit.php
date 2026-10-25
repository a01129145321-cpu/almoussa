<?php
require_once __DIR__ . '/includes/functions.php';
start_app_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(base_url('contact.php'));
}
require_valid_csrf();

// روبوت: نتظاهر بالنجاح دون حفظ شيء
if (honeypot_triggered()) {
    flash_set('success', 'تم إرسال رسالتك بنجاح، سنتواصل معك قريبًا.');
    redirect(base_url('contact.php'));
}

$data = [
    'name'    => limit_text(sanitize_text($_POST['name'] ?? ''), 150),
    'phone'   => limit_text(sanitize_text($_POST['phone'] ?? ''), 30),
    'email'   => limit_text(sanitize_text($_POST['email'] ?? ''), 150),
    'message' => limit_text(sanitize_text($_POST['message'] ?? ''), 5000),
];

$errors = [];
if ($data['name'] === '') $errors[] = 'الرجاء إدخال الاسم.';
if ($data['message'] === '') $errors[] = 'الرجاء كتابة نص الرسالة.';
if ($data['email'] !== '' && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'صيغة البريد الإلكتروني غير صحيحة.';

if (!empty($errors)) {
    $_SESSION['contact_form_errors'] = $errors;
    $_SESSION['contact_form_data'] = $data;
    redirect(base_url('contact.php'));
}

try {
    $pdo = get_db();
    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, phone, email, message) VALUES (:name, :phone, :email, :message)");
    $stmt->execute([
        ':name'    => $data['name'],
        ':phone'   => $data['phone'],
        ':email'   => $data['email'],
        ':message' => $data['message'],
    ]);

    if (ADMIN_NOTIFICATION_EMAIL) {
        $subject = '=?UTF-8?B?' . base64_encode('رسالة تواصل جديدة - ' . SITE_NAME) . '?=';
        $body = "الاسم: {$data['name']}\nالهاتف: {$data['phone']}\nالبريد: {$data['email']}\n\nالرسالة:\n{$data['message']}";
        $headers = 'Content-Type: text/plain; charset=UTF-8' . "\r\n";
        $headers .= 'From: no-reply@' . preg_replace('#^https?://#', '', rtrim(SITE_URL, '/')) . "\r\n";
        if ($data['email'] !== '' && !preg_match('/[\r\n]/', $data['email'])) {
            $headers .= 'Reply-To: ' . $data['email'] . "\r\n";
        }
        @mail(ADMIN_NOTIFICATION_EMAIL, $subject, $body, $headers);
    }

    flash_set('success', 'تم إرسال رسالتك بنجاح، سنتواصل معك قريبًا.');
} catch (Throwable $e) {
    flash_set('error', 'حدث خطأ أثناء إرسال الرسالة، الرجاء المحاولة لاحقًا.');
}

redirect(base_url('contact.php'));
