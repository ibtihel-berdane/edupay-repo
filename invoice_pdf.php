<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/student_auth.php';
require_once __DIR__ . '/../includes/pdf.php';

// Student PDF endpoint for downloading an owned invoice.
$studentId = current_student_id();
$invoiceId = (int)($_GET['id'] ?? 0);
if (!student_owns_invoice($studentId, $invoiceId)) {
    flash('danger', 'Facture introuvable.');
    redirect('student/invoices.php');
}

sync_invoice_status($invoiceId);
$invoice = query_one(
    "SELECT i.*, s.matricule, s.first_name, s.last_name, s.program, s.level, s.academic_year
     FROM invoices i
     JOIN students s ON s.id = i.student_id
     WHERE i.id = ? AND i.student_id = ?",
    [$invoiceId, $studentId]
);
if (!$invoice) {
    flash('danger', 'Facture introuvable.');
    redirect('student/invoices.php');
}

$items = query_all('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY fee_type ASC, id ASC', [$invoiceId]);
$payments = query_all(
    "SELECT p.*, ii.fee_name
     FROM payments p
     LEFT JOIN invoice_items ii ON ii.id = p.invoice_item_id
     WHERE p.invoice_id = ? AND p.student_id = ?
     ORDER BY p.submitted_at DESC",
    [$invoiceId, $studentId]
);

send_invoice_pdf($invoice, $items, $payments);
