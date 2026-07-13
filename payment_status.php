<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
start_app_session();

// Payment status API: returns scoped payment rows for the current student or agent.
if (current_student_id()) {
    $studentId = current_student_id();
    $invoiceId = isset($_GET['invoice_id']) ? (int)$_GET['invoice_id'] : 0;
    $params = [$studentId];
    $where = 'WHERE p.student_id = ?';
    if ($invoiceId > 0) {
        $where .= ' AND p.invoice_id = ?';
        $params[] = $invoiceId;
    }
    $payments = query_all(
        "SELECT p.id, p.payment_reference, p.amount, p.payment_method, p.status, p.submitted_at, p.validated_at,
                p.rejection_reason, i.invoice_number, ii.fee_name, r.id AS receipt_id
         FROM payments p
         JOIN invoices i ON i.id = p.invoice_id
         LEFT JOIN invoice_items ii ON ii.id = p.invoice_item_id
         LEFT JOIN receipts r ON r.payment_id = p.id
         {$where}
         ORDER BY p.submitted_at DESC",
        $params
    );
    json_response(['payments' => $payments]);
}

if (current_admin_id()) {
    $status = trim($_GET['status'] ?? '');
    $params = [];
    $where = '';
    if (in_array($status, ['pending', 'validated', 'rejected'], true)) {
        $where = 'WHERE p.status = ?';
        $params[] = $status;
    }
    $payments = query_all(
        "SELECT p.id, p.payment_reference, p.amount, p.payment_method, p.status, p.submitted_at,
                s.matricule, CONCAT(s.first_name, ' ', s.last_name) AS student_name, i.invoice_number
         FROM payments p
         JOIN students s ON s.id = p.student_id
         JOIN invoices i ON i.id = p.invoice_id
         {$where}
         ORDER BY p.submitted_at DESC",
        $params
    );
    json_response(['payments' => $payments]);
}

json_response(['error' => 'Unauthorized'], 401);
