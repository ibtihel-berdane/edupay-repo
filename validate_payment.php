<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Payment validation page: agents approve or reject a submitted payment.
$paymentId = (int)($_GET['id'] ?? ($_POST['payment_id'] ?? 0));
$payment = query_one(
    "SELECT p.*, s.matricule, s.first_name, s.last_name, i.invoice_number, ii.fee_name
     FROM payments p
     JOIN students s ON s.id = p.student_id
     JOIN invoices i ON i.id = p.invoice_id
     LEFT JOIN invoice_items ii ON ii.id = p.invoice_item_id
     WHERE p.id = ?",
    [$paymentId]
);
if (!$payment) {
    flash('danger', 'Paiement introuvable.');
    redirect('admin/payments.php');
}

$errors = [];
if (is_post()) {
    verify_csrf();
    $action = $_POST['action'] ?? '';
    $pdo = db();

    if ($payment['status'] !== 'pending') {
        flash('warning', 'Seuls les paiements en attente peuvent etre examines.');
        redirect('admin/payments.php');
    }

    if ($action === 'validate') {
        $pdo->beginTransaction();
        try {
            $fresh = query_one('SELECT * FROM payments WHERE id = ? FOR UPDATE', [$paymentId]);
            if (!$fresh || $fresh['status'] !== 'pending') {
                throw new RuntimeException('Le paiement n est plus en attente.');
            }
            if (!empty($fresh['invoice_item_id'])) {
                $remaining = invoice_item_remaining((int)$fresh['invoice_item_id'], false);
                if ((float)$fresh['amount'] > $remaining + 0.009) {
                    throw new RuntimeException('Le montant depasse le reste de la cible.');
                }
            }
            $stmt = $pdo->prepare("UPDATE payments SET status = 'validated', validated_by = ?, validated_at = NOW(), rejection_reason = NULL WHERE id = ?");
            $stmt->execute([current_admin_id(), $paymentId]);

            sync_invoice_status((int)$fresh['invoice_id']);
            if (!receipt_for_payment($paymentId)) {
                $receiptStmt = $pdo->prepare('INSERT INTO receipts (receipt_number, payment_id, student_id, invoice_id) VALUES (?, ?, ?, ?)');
                $receiptStmt->execute([generate_receipt_number(), $paymentId, $fresh['student_id'], $fresh['invoice_id']]);
            }
            $pdo->commit();
            notify_student_payment_treated($paymentId);
            flash('success', 'Paiement valide et recu genere.');
            redirect('admin/payments.php');
        } catch (Throwable $exception) {
            $pdo->rollBack();
            $errors[] = $exception->getMessage();
        }
    } elseif ($action === 'reject') {
        $reason = trim((string)($_POST['rejection_reason'] ?? ''));
        if ($reason === '') {
            $errors[] = 'Le motif de rejet est obligatoire.';
        } else {
            $stmt = db()->prepare("UPDATE payments SET status = 'rejected', rejection_reason = ?, validated_by = ?, validated_at = NOW() WHERE id = ?");
            $stmt->execute([$reason, current_admin_id(), $paymentId]);
            sync_invoice_status((int)$payment['invoice_id']);
            notify_student_payment_treated($paymentId);
            flash('success', 'Paiement rejete.');
            redirect('admin/payments.php');
        }
    } else {
        $errors[] = 'Action invalide.';
    }
}

$pageTitle = 'Valider le paiement';
$layout = 'admin';
$active = 'payments';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Examiner le paiement</h1>
    <a class="btn btn-secondary" href="<?= h(url('admin/payments.php')) ?>">Retour</a>
</div>

<div class="card">
    <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endforeach; ?>
    <div class="grid grid-3">
        <p><strong>Reference</strong><br><?= h($payment['payment_reference']) ?></p>
        <p><strong>Matricule</strong><br><?= h($payment['matricule'] . ' - ' . $payment['first_name'] . ' ' . $payment['last_name']) ?></p>
        <p><strong>Facture</strong><br><?= h($payment['invoice_number']) ?></p>
        <p><strong>Cible</strong><br><?= h(fee_label($payment['fee_name'] ?? null)) ?></p>
        <p><strong>Montant</strong><br><?= money($payment['amount']) ?></p>
        <p><strong>Statut</strong><br><?= badge($payment['status']) ?></p>
        <p><strong>Methode</strong><br><?= h(payment_method_label($payment['payment_method'])) ?></p>
        <p><strong>Soumis le</strong><br><?= h($payment['submitted_at']) ?></p>
    </div>
    <?php if ($payment['status'] === 'pending'): ?>
        <div class="actions" style="margin-top:16px">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="payment_id" value="<?= h((string)$paymentId) ?>">
                <input type="hidden" name="action" value="validate">
                <button class="btn btn-primary" type="submit">Valider</button>
            </form>
        </div>
        <form method="post" style="margin-top:16px">
            <?= csrf_field() ?>
            <input type="hidden" name="payment_id" value="<?= h((string)$paymentId) ?>">
            <input type="hidden" name="action" value="reject">
            <div class="form-row">
                <label for="rejection_reason">Motif de rejet</label>
                <textarea id="rejection_reason" name="rejection_reason"></textarea>
            </div>
            <button class="btn btn-danger" type="submit">Rejeter</button>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
