<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/student_auth.php';

// Student payment history with related invoice, fee, and receipt links.
$studentId = current_student_id();
$payments = query_all(
    "SELECT p.*, i.invoice_number, ii.fee_name, r.id AS receipt_id
     FROM payments p
     JOIN invoices i ON i.id = p.invoice_id
     LEFT JOIN invoice_items ii ON ii.id = p.invoice_item_id
     LEFT JOIN receipts r ON r.payment_id = p.id
     WHERE p.student_id = ?
     ORDER BY p.submitted_at DESC",
    [$studentId]
);

$pageTitle = 'Historique des paiements';
$layout = 'student';
$active = 'payments';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Historique des paiements</h1>
        <p class="muted">Paiements soumis et resultats de validation.</p>
    </div>
    <a class="btn btn-primary" href="<?= h(url('student/pay_invoice.php')) ?>">Nouveau paiement</a>
</div>

<div class="table-wrap">
    <table>
        <thead><tr><th>Reference</th><th>Facture</th><th>Cible</th><th>Montant</th><th>Methode</th><th>Statut</th><th>Soumis le</th><th>Recu</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= h($payment['payment_reference']) ?></td>
                <td><?= h($payment['invoice_number']) ?></td>
                <td><?= h(fee_label($payment['fee_name'] ?? null)) ?></td>
                <td><?= money($payment['amount']) ?></td>
                <td><?= h(payment_method_label($payment['payment_method'])) ?></td>
                <td><?= badge($payment['status']) ?></td>
                <td><?= h($payment['submitted_at']) ?></td>
                <td class="receipt-cell">
                    <?php if ($payment['receipt_id']): ?>
                        <a class="btn btn-secondary btn-small" href="<?= h(url('student/receipt.php?id=' . $payment['receipt_id'])) ?>">Recu</a>
                    <?php elseif ($payment['status'] === 'rejected'): ?>
                        <?= h($payment['rejection_reason'] ?? '') ?>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
