<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
start_app_session();

// Invoice status API: syncs and returns one invoice visible to the current user.
$invoiceId = (int)($_GET['invoice_id'] ?? $_GET['id'] ?? 0);
if ($invoiceId <= 0) {
    json_response(['error' => 'Identifiant de facture obligatoire.'], 422);
}

if (current_student_id()) {
    $studentId = current_student_id();
    if (!student_owns_invoice($studentId, $invoiceId)) {
        json_response(['error' => 'Not found'], 404);
    }
    sync_invoice_status($invoiceId);
    $invoice = query_one('SELECT * FROM invoices WHERE id = ? AND student_id = ?', [$invoiceId, $studentId]);
    $items = query_all('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY fee_type ASC, id ASC', [$invoiceId]);
    json_response(['invoice' => $invoice, 'items' => $items]);
}

if (current_admin_id()) {
    sync_invoice_status($invoiceId);
    $invoice = query_one('SELECT * FROM invoices WHERE id = ?', [$invoiceId]);
    if (!$invoice) {
        json_response(['error' => 'Not found'], 404);
    }
    $items = query_all('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY fee_type ASC, id ASC', [$invoiceId]);
    json_response(['invoice' => $invoice, 'items' => $items]);
}

json_response(['error' => 'Unauthorized'], 401);
