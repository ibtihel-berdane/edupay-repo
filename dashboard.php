<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/student_auth.php';

// Student dashboard: financial overview plus payable fees, invoices, and recent payments.
$payload = student_dashboard_payload(current_student_id());
$student = $payload['student'];
$stats = $payload['stats'];

$pageTitle = tr('Tableau de bord etudiant');
$layout = 'student';
$active = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>
<section data-dashboard-api="<?= h(url('api/student_dashboard_data.php')) ?>" data-dashboard-type="student" data-csrf-token="<?= h(csrf_token()) ?>">
    <div class="page-header">
        <div>
            <h1><?= h($student['first_name'] . ' ' . $student['last_name']) ?></h1>
            <p class="muted">Matricule <?= h($student['matricule']) ?> | <?= h(program_label($student['program'])) ?> | <?= h(level_label($student['level'])) ?> | <?= h($student['academic_year']) ?></p>
        </div>
<a class="btn btn-primary" href="<?= h(url('student/pay_invoice.php')) ?>"><i class="fa-solid fa-credit-card"></i> <?= h(tr('Payer une facture')) ?></a>
    </div>

    <div class="grid grid-4">
<div class="stat-card stat-purple"><i class="fa-solid fa-file-invoice stat-icon"></i><span><?= h(tr('Total facture')) ?></span><strong data-stat="total_invoiced_amount"><?= money($stats['total_invoiced_amount']) ?></strong></div>
<div class="stat-card stat-green"><i class="fa-solid fa-check-circle stat-icon"></i><span><?= h(tr('Total paye')) ?></span><strong data-stat="total_paid_amount"><?= money($stats['total_paid_amount']) ?></strong></div>
<div class="stat-card stat-orange"><i class="fa-solid fa-wallet stat-icon"></i><span><?= h(tr('Solde restant')) ?></span><strong data-stat="remaining_balance"><?= money($stats['remaining_balance']) ?></strong></div>
<div class="stat-card stat-teal"><i class="fa-solid fa-receipt stat-icon"></i><span><?= h(tr('Recus')) ?></span><strong data-stat="receipt_count"><?= h((string)$stats['receipt_count']) ?></strong></div>
    </div>

    <div class="page-header" style="margin-top:24px">
        <div>
<h2><?= h(tr('Frais disponibles')) ?></h2>
<p class="muted"><?= h(tr('Frais correspondant a votre filiere, niveau et annee academique.')) ?></p>
        </div>
    </div>
    <div class="table-wrap">
        <table>
<thead><tr><th><?= h(tr('Frais')) ?></th><th><?= h(tr('Type')) ?></th><th><?= h(tr('Montant')) ?></th><th><?= h(tr('Date limite')) ?></th><th><?= h(tr('Statut')) ?></th><th><?= h(tr('Description')) ?></th><th></th></tr></thead>
            <tbody data-student-fees>
            <?php if ($payload['available_fees']): ?>
                <?php foreach ($payload['available_fees'] as $fee): ?>
                    <?php $feeRowClass = in_array($fee['payment_status'], ['paid', 'pending', 'blocked_registration'], true) ? 'fee-row-locked fee-row-' . $fee['payment_status'] : ''; ?>
                    <tr class="<?= h($feeRowClass) ?>">
                        <td><?= h(fee_label($fee['fee_name'])) ?></td>
                        <td><?= badge($fee['fee_type']) ?></td>
                        <td><?= money($fee['amount']) ?></td>
                        <td><?= h($fee['due_date'] ?? '') ?></td>
                        <td><?= badge($fee['payment_status']) ?></td>
                        <td><?= h($fee['description'] ?? '') ?></td>
                        <td>
                            <?php if ($fee['can_pay']): ?>
<form method="post" action="<?= h(url('student/pay_fee.php')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="fee_id" value="<?= h((string)$fee['id']) ?>">
<button class="btn btn-primary btn-small" type="submit"><i class="fa-solid fa-credit-card"></i> <?= h(tr('Payer')) ?></button>
                                </form>
                            <?php elseif ($fee['payment_status'] === 'paid'): ?>
<button class="btn btn-secondary btn-small" type="button" disabled><?= h(tr('Paye')) ?></button>
                            <?php elseif ($fee['payment_status'] === 'pending'): ?>
<button class="btn btn-secondary btn-small" type="button" disabled><?= h(tr('En attente')) ?></button>
                            <?php elseif ($fee['payment_status'] === 'blocked_registration'): ?>
<span class="muted"><?= h(tr("Payez l'inscription d'abord")) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="7" class="muted">Aucun frais disponible pour votre dossier.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

<div class="page-header" style="margin-top:24px"><h2><?= h(tr('Factures')) ?></h2></div>
    <div class="table-wrap">
        <table>
<thead><tr><th><?= h(tr('Facture')) ?></th><th><?= h(tr('Total')) ?></th><th><?= h(tr('Paye')) ?></th><th><?= h(tr('Reste')) ?></th><th><?= h(tr('Statut')) ?></th><th></th></tr></thead>
            <tbody data-student-invoices>
            <?php foreach ($payload['invoices'] as $invoice): ?>
                <tr>
                    <td><?= h($invoice['invoice_number']) ?></td>
                    <td><?= money($invoice['total_amount']) ?></td>
                    <td><?= money($invoice['paid_amount']) ?></td>
                    <td><?= money($invoice['remaining_amount']) ?></td>
                    <td><?= badge($invoice['status']) ?></td>
                    <td class="actions">
<a class="btn btn-secondary btn-small" href="<?= h(url('student/view_invoice.php?id=' . $invoice['id'])) ?>"><i class="fa-solid fa-eye"></i> <?= h(tr('Voir')) ?></a>
<a class="btn btn-secondary btn-small" href="<?= h(url('student/invoice_pdf.php?id=' . $invoice['id'])) ?>"><i class="fa-solid fa-file-pdf"></i> <?= h(tr('PDF')) ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

<div class="page-header" style="margin-top:24px"><h2><?= h(tr('Paiements recents')) ?></h2></div>
    <div class="table-wrap">
        <table>
<thead><tr><th><?= h(tr('Reference')) ?></th><th><?= h(tr('Facture')) ?></th><th><?= h(tr('Montant')) ?></th><th><?= h(tr('Statut')) ?></th><th><?= h(tr('Recu')) ?></th></tr></thead>
            <tbody data-student-payments>
            <?php foreach ($payload['payments'] as $payment): ?>
                <tr>
                    <td><?= h($payment['payment_reference']) ?></td>
                    <td><?= h($payment['invoice_number']) ?></td>
                    <td><?= money($payment['amount']) ?></td>
                    <td><?= badge($payment['status']) ?></td>
                    <td class="receipt-cell">
                        <?php if ($payment['receipt_id']): ?>
                            <a class="btn btn-secondary btn-small" href="<?= h(url('student/receipt.php?id=' . $payment['receipt_id'])) ?>"><i class="fa-solid fa-receipt"></i> Recu</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
