<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Student detail page: shows dossier information, invoice totals, and payment history.
$studentId = (int)($_GET['id'] ?? 0);
$student = query_one('SELECT * FROM students WHERE id = ?', [$studentId]);
if (!$student) {
    flash('danger', 'Etudiant introuvable.');
    redirect('admin/students.php');
}

sync_all_invoice_statuses();
$invoices = query_all('SELECT * FROM invoices WHERE student_id = ? ORDER BY created_at DESC', [$studentId]);
$payments = query_all(
    "SELECT p.*, i.invoice_number, r.id AS receipt_id
     FROM payments p
     JOIN invoices i ON i.id = p.invoice_id
     LEFT JOIN receipts r ON r.payment_id = p.id
     WHERE p.student_id = ?
     ORDER BY p.submitted_at DESC",
    [$studentId]
);
$totals = [
    'invoiced' => query_value('SELECT COALESCE(SUM(total_amount), 0) FROM invoices WHERE student_id = ?', [$studentId]),
    'paid' => query_value('SELECT COALESCE(SUM(paid_amount), 0) FROM invoices WHERE student_id = ?', [$studentId]),
    'remaining' => query_value('SELECT COALESCE(SUM(remaining_amount), 0) FROM invoices WHERE student_id = ?', [$studentId]),
];

$pageTitle = 'Voir etudiant';
$layout = 'admin';
$active = 'students';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1><?= h($student['first_name'] . ' ' . $student['last_name']) ?></h1>
        <p class="muted">Matricule <?= h($student['matricule']) ?></p>
    </div>
    <div class="actions">
        <a class="btn btn-primary" href="<?= h(url('admin/generate_invoice.php?student_id=' . $studentId)) ?>">Generer une facture</a>
        <a class="btn btn-secondary" href="<?= h(url('admin/edit_student.php?id=' . $studentId)) ?>">Modifier</a>
    </div>
</div>

<div class="grid grid-4">
    <div class="stat-card"><span>Statut</span><strong><?= badge($student['account_status']) ?></strong></div>
    <div class="stat-card"><span>Total facture</span><strong><?= money($totals['invoiced']) ?></strong></div>
    <div class="stat-card"><span>Total paye</span><strong><?= money($totals['paid']) ?></strong></div>
    <div class="stat-card"><span>Reste</span><strong><?= money($totals['remaining']) ?></strong></div>
</div>

<div class="card" style="margin-top:18px">
    <h2>Informations etudiant</h2>
    <div class="grid grid-3">
        <p><strong>Filiere</strong><br><?= h(program_label($student['program'])) ?></p>
        <p><strong>Niveau</strong><br><?= h(level_label($student['level'])) ?></p>
        <p><strong>Annee academique</strong><br><?= h($student['academic_year']) ?></p>
        <p><strong>Telephone</strong><br><?= h($student['phone'] ?? '') ?></p>
        <p><strong>Cree le</strong><br><?= h($student['created_at']) ?></p>
        <p><strong>Modifie le</strong><br><?= h($student['updated_at']) ?></p>
    </div>
</div>

<div class="page-header" style="margin-top:24px"><h2>Factures</h2></div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Facture</th><th>Total</th><th>Paye</th><th>Reste</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $invoice): ?>
            <tr>
                <td><?= h($invoice['invoice_number']) ?></td>
                <td><?= money($invoice['total_amount']) ?></td>
                <td><?= money($invoice['paid_amount']) ?></td>
                <td><?= money($invoice['remaining_amount']) ?></td>
                <td><?= badge($invoice['status']) ?></td>
                <td><a class="btn btn-secondary btn-small" href="<?= h(url('admin/view_invoice.php?id=' . $invoice['id'])) ?>">Voir</a></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="page-header" style="margin-top:24px"><h2>Paiements</h2></div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Reference</th><th>Facture</th><th>Montant</th><th>Methode</th><th>Statut</th><th>Recu</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= h($payment['payment_reference']) ?></td>
                <td><?= h($payment['invoice_number']) ?></td>
                <td><?= money($payment['amount']) ?></td>
                <td><?= h(payment_method_label($payment['payment_method'])) ?></td>
                <td><?= badge($payment['status']) ?></td>
                <td class="receipt-cell">
                    <?php if ($payment['receipt_id']): ?>
                        <a class="btn btn-secondary btn-small" href="<?= h(url('admin/receipts.php?id=' . $payment['receipt_id'])) ?>">Recu</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
