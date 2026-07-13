<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/student_auth.php';

// Shortcut endpoint: creates/fetches the invoice item for a fee, then opens the payment form.
if (!is_post()) {
    redirect('student/dashboard.php');
}

verify_csrf();

$studentId = current_student_id();
$feeId = (int)($_POST['fee_id'] ?? 0);

try {
    if ($feeId <= 0) {
        throw new RuntimeException('Frais invalide.');
    }

    $target = ensure_student_fee_invoice($studentId, $feeId);
    redirect(
        'student/pay_invoice.php?invoice_id=' . $target['invoice_id']
        . '&target_id=' . $target['invoice_item_id']
    );
} catch (Throwable $exception) {
    flash('danger', $exception->getMessage());
    redirect('student/dashboard.php');
}
