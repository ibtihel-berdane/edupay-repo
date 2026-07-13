<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/student_auth.php';

// Student invoice detail page guarded by ownership checks.
$studentId = current_student_id();
$invoiceId = (int)($_GET['id'] ?? 0);
if (!student_owns_invoice($studentId, $invoiceId)) {
    flash('danger', 'Facture introuvable.');
    redirect('student/invoices.php');
}

sync_invoice_status($invoiceId);
$invoice = query_one('SELECT * FROM invoices WHERE id = ? AND student_id = ?', [$invoiceId, $studentId]);
$items = query_all('SELECT * FROM invoice_items WHERE invoice_id = ? ORDER BY fee_type ASC, id ASC', [$invoiceId]);
$payments = query_all(
    "SELECT p.*, ii.fee_name, r.id AS receipt_id
     FROM payments p
     LEFT JOIN invoice_items ii ON ii.id = p.invoice_item_id
     LEFT JOIN receipts r ON r.payment_id = p.id
     WHERE p.invoice_id = ? AND p.student_id = ?
     ORDER BY p.submitted_at DESC",
    [$invoiceId, $studentId]
);

$pageTitle = 'Voir facture';
$layout = 'student';
$active = 'invoices';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1><?= h($invoice['invoice_number']) ?></h1>
        <p class="muted">Echeance <?= h($invoice['due_date'] ?? '') ?></p>
    </div>
    <div class="actions">
        <?php if ((float)$invoice['remaining_amount'] > 0): ?>
            <a class="btn btn-primary" href="<?= h(url('student/pay_invoice.php?invoice_id=' . $invoiceId)) ?>">Payer</a>
        <?php endif; ?>
        <button class="btn btn-secondary" type="button" onclick="window.print()">Imprimer</button>
        <a class="btn btn-secondary" href="<?= h(url('student/invoice_pdf.php?id=' . $invoiceId)) ?>">Telecharger PDF</a>
        <a class="btn btn-secondary" href="<?= h(url('student/invoices.php')) ?>">Retour</a>
    </div>
</div>

<div class="grid grid-4">
    <div class="stat-card"><span>Total</span><strong><?= money($invoice['total_amount']) ?></strong></div>
    <div class="stat-card"><span>Paye</span><strong><?= money($invoice['paid_amount']) ?></strong></div>
    <div class="stat-card"><span>Reste</span><strong><?= money($invoice['remaining_amount']) ?></strong></div>
    <div class="stat-card"><span>Statut</span><strong><?= badge($invoice['status']) ?></strong></div>
</div>

<div class="page-header" style="margin-top:24px"><h2>Elements</h2></div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Frais</th><th>Type</th><th>Montant</th><th>Paye valide</th><th>Reste disponible</th></tr></thead>
        <tbody>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?= h(fee_label($item['fee_name'])) ?></td>
                <td><?= badge($item['fee_type']) ?></td>
                <td><?= money($item['amount']) ?></td>
                <td><?= money(invoice_item_paid((int)$item['id'])) ?></td>
                <td><?= money(invoice_item_remaining((int)$item['id'])) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="page-header" style="margin-top:24px"><h2>Paiements</h2></div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Reference</th><th>Cible</th><th>Montant</th><th>Methode</th><th>Statut</th><th>Recu</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= h($payment['payment_reference']) ?></td>
                <td><?= h(fee_label($payment['fee_name'] ?? null)) ?></td>
                <td><?= money($payment['amount']) ?></td>
                <td><?= h(payment_method_label($payment['payment_method'])) ?></td>
                <td><?= badge($payment['status']) ?></td>
                <td class="receipt-cell">
                    <?php if ($payment['receipt_id']): ?>
                        <a class="btn btn-secondary btn-small" href="<?= h(url('student/receipt.php?id=' . $payment['receipt_id'])) ?>">Recu</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
