<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/student_auth.php';

// Student invoice list: ensure invoices are current before showing them.
$studentId = current_student_id();
ensure_automatic_student_invoices($studentId);
sync_all_invoice_statuses();
$invoices = query_all('SELECT * FROM invoices WHERE student_id = ? ORDER BY created_at DESC', [$studentId]);

$pageTitle = 'Mes factures';
$layout = 'student';
$active = 'invoices';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Mes factures</h1>
        <p class="muted">Factures generees automatiquement par le systeme.</p>
    </div>
    <a class="btn btn-primary" href="<?= h(url('student/pay_invoice.php')) ?>">Payer une facture</a>
</div>

<div class="table-wrap">
    <table>
        <thead><tr><th>Facture</th><th>Total</th><th>Paye</th><th>Reste</th><th>Echeance</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $invoice): ?>
            <tr>
                <td><?= h($invoice['invoice_number']) ?></td>
                <td><?= money($invoice['total_amount']) ?></td>
                <td><?= money($invoice['paid_amount']) ?></td>
                <td><?= money($invoice['remaining_amount']) ?></td>
                <td><?= h($invoice['due_date'] ?? '') ?></td>
                <td><?= badge($invoice['status']) ?></td>
                <td class="actions">
                    <a class="btn btn-secondary btn-small" href="<?= h(url('student/view_invoice.php?id=' . $invoice['id'])) ?>">Voir</a>
                    <a class="btn btn-secondary btn-small" href="<?= h(url('student/invoice_pdf.php?id=' . $invoice['id'])) ?>">PDF</a>
                    <?php if ((float)$invoice['remaining_amount'] > 0): ?>
                        <a class="btn btn-primary btn-small" href="<?= h(url('student/pay_invoice.php?invoice_id=' . $invoice['id'])) ?>">Payer</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
