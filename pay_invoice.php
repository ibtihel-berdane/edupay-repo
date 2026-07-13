<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/student_auth.php';

// Student payment form for a selected invoice or invoice item.
$studentId = current_student_id();
$invoiceId = (int)($_GET['invoice_id'] ?? ($_POST['invoice_id'] ?? 0));
$preselectedTargetId = (int)($_GET['target_id'] ?? 0);
$errors = [];

ensure_automatic_student_invoices($studentId);

if ($invoiceId <= 0) {
    sync_all_invoice_statuses();
    $openInvoices = query_all(
        "SELECT * FROM invoices WHERE student_id = ? AND remaining_amount > 0 ORDER BY created_at DESC",
        [$studentId]
    );

    $pageTitle = 'Payer une facture';
    $layout = 'student';
    $active = 'payments';
    require __DIR__ . '/../includes/header.php';
    ?>
    <div class="page-header"><h1>Payer une facture</h1></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Facture</th><th>Reste</th><th>Statut</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($openInvoices as $invoice): ?>
                <tr>
                    <td><?= h($invoice['invoice_number']) ?></td>
                    <td><?= money($invoice['remaining_amount']) ?></td>
                    <td><?= badge($invoice['status']) ?></td>
                    <td><a class="btn btn-primary btn-small" href="<?= h(url('student/pay_invoice.php?invoice_id=' . $invoice['id'])) ?>">Choisir</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php require __DIR__ . '/../includes/footer.php'; exit; ?>
<?php
}

if (!student_owns_invoice($studentId, $invoiceId)) {
    flash('danger', 'Facture introuvable.');
    redirect('student/invoices.php');
}

sync_invoice_status($invoiceId);
$invoice = query_one('SELECT * FROM invoices WHERE id = ? AND student_id = ?', [$invoiceId, $studentId]);
$targets = available_payment_targets($studentId, $invoiceId);

$values = [
    'invoice_item_id' => '',
    'amount' => '',
    'payment_method' => 'carte_edahabia',
    'payment_reference' => generate_payment_reference(),
];

if (!is_post() && $preselectedTargetId > 0) {
    foreach ($targets as $target) {
        if ((int)$target['id'] === $preselectedTargetId) {
            $values['invoice_item_id'] = (string)$preselectedTargetId;
            $values['amount'] = (string)$target['remaining_amount'];
            break;
        }
    }
}

if (is_post()) {
    verify_csrf();
    foreach ($values as $key => $_) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }
    if ($values['payment_reference'] === '') {
        $values['payment_reference'] = generate_payment_reference();
    }

    $targetId = (int)$values['invoice_item_id'];
    $target = null;
    foreach ($targets as $candidate) {
        if ((int)$candidate['id'] === $targetId) {
            $target = $candidate;
            break;
        }
    }

    $amount = (float)$values['amount'];
    if (!$target) {
        $errors[] = 'Selectionnez une cible de paiement disponible.';
    }
    if ($amount <= 0) {
        $errors[] = 'Le montant doit etre superieur a 0.';
    }
    if ($target && $amount > (float)$target['remaining_amount'] + 0.009) {
        $errors[] = 'Le montant ne peut pas depasser le reste de la cible selectionnee.';
    }
    if ($target && invoice_item_has_pending_payment($targetId)) {
        $errors[] = 'Un paiement est deja en attente pour ce frais.';
    }
    if (!in_array($values['payment_method'], ['carte_edahabia', 'eccp'], true)) {
        $errors[] = 'Selectionnez une methode de paiement valide.';
    }
    if ($values['payment_reference'] === '') {
        $errors[] = 'La reference de paiement est obligatoire.';
    }
    if (!$errors && (int)query_value('SELECT COUNT(*) FROM payments WHERE payment_reference = ?', [$values['payment_reference']]) > 0) {
        $errors[] = 'Cette reference de paiement existe deja.';
    }
    if ($target && $target['fee_name'] !== 'registration') {
        $feeScope = query_one('SELECT program, level, academic_year FROM fees WHERE id = ?', [$target['fee_id']]);
        if (
            $feeScope
            && !student_registration_paid_for_scope($studentId, $feeScope['program'], $feeScope['level'], $feeScope['academic_year'])
        ) {
            $errors[] = 'L inscription doit etre totalement payee avant les frais optionnels.';
        }
    }

    if (!$errors && $target) {
        $pdo = db();
        $stmt = $pdo->prepare(
            "INSERT INTO payments (payment_reference, student_id, invoice_id, invoice_item_id, amount, payment_method, status)
             VALUES (?, ?, ?, ?, ?, ?, 'pending')"
        );
        $stmt->execute([
            $values['payment_reference'],
            $studentId,
            $invoiceId,
            $targetId,
            $amount,
            $values['payment_method'],
        ]);
        notify_admins_payment_submitted((int)$pdo->lastInsertId());
        flash('success', 'Paiement soumis et en attente de validation.');
        redirect('student/payment_history.php');
    }
}

$pageTitle = 'Payer une facture';
$layout = 'student';
$active = 'payments';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Payer <?= h($invoice['invoice_number']) ?></h1>
        <p class="muted">Choisissez votre moyen de paiement. La facture sera mise a jour apres validation comptable.</p>
    </div>
    <a class="btn btn-secondary" href="<?= h(url('student/view_invoice.php?id=' . $invoiceId)) ?>">Retour</a>
</div>

<div class="grid grid-3">
    <div class="stat-card"><span>Total</span><strong><?= money($invoice['total_amount']) ?></strong></div>
    <div class="stat-card"><span>Paye</span><strong><?= money($invoice['paid_amount']) ?></strong></div>
    <div class="stat-card"><span>Reste</span><strong><?= money($invoice['remaining_amount']) ?></strong></div>
</div>

<form class="card" method="post" style="margin-top:18px" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="invoice_id" value="<?= h((string)$invoiceId) ?>">
    <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endforeach; ?>
    <?php if (!$targets): ?>
        <div class="alert alert-warning">Aucun element payable n est disponible pour cette facture.</div>
    <?php endif; ?>
    <div class="form-grid">
        <div class="form-row">
            <label for="invoice_item_id">Cible du paiement</label>
            <select id="invoice_item_id" name="invoice_item_id" required>
                <option value="">Selectionner une cible</option>
                <?php foreach ($targets as $target): ?>
                    <option value="<?= h((string)$target['id']) ?>" <?= (int)$values['invoice_item_id'] === (int)$target['id'] ? 'selected' : '' ?>>
                        <?= h(fee_label($target['fee_name'])) ?> - Reste <?= money($target['remaining_amount']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label for="amount">Montant</label><input id="amount" name="amount" type="number" min="0.01" step="0.01" value="<?= h($values['amount']) ?>" required></div>
        <div class="form-row">
            <label for="payment_method">Methode de paiement</label>
            <select id="payment_method" name="payment_method">
                <?php foreach (['carte_edahabia' => 'Carte Edahabia', 'eccp' => 'ECCP'] as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= $values['payment_method'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label for="payment_reference">Reference de paiement</label><input id="payment_reference" name="payment_reference" value="<?= h($values['payment_reference']) ?>"></div>
    </div>
    <button class="btn btn-primary" type="submit" <?= !$targets ? 'disabled' : '' ?>>Payer</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
