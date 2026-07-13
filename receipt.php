<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/student_auth.php';

// Student receipt page: display one receipt owned by the authenticated student.
$studentId = current_student_id();
$receiptId = (int)($_GET['id'] ?? 0);
$receipt = query_one(
    "SELECT r.*, p.amount, p.payment_method, p.payment_reference, p.submitted_at, p.validated_at,
            s.matricule, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            i.invoice_number
     FROM receipts r
     JOIN payments p ON p.id = r.payment_id AND p.status = 'validated'
     JOIN students s ON s.id = r.student_id
     JOIN invoices i ON i.id = r.invoice_id
     WHERE r.id = ? AND r.student_id = ?",
    [$receiptId, $studentId]
);

if (!$receipt) {
    flash('danger', 'Recu introuvable.');
    redirect('student/payment_history.php');
}

$pageTitle = 'Recu';
$layout = 'student';
$active = 'payments';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Recu</h1>
    <div class="actions">
        <button class="btn btn-secondary" onclick="window.print()">Imprimer</button>
        <a class="btn btn-secondary" href="<?= h(url('student/payment_history.php')) ?>">Retour</a>
    </div>
</div>

<section class="card receipt">
    <div class="receipt-header">
        <div>
            <h2><?= h($receipt['receipt_number']) ?></h2>
            <p class="muted">Emis le <?= h($receipt['issued_at']) ?></p>
        </div>
        <strong><?= money($receipt['amount']) ?></strong>
    </div>
    <div class="grid grid-2">
        <p><strong>Nom complet</strong><br><?= h($receipt['student_name']) ?></p>
        <p><strong>Matricule</strong><br><?= h($receipt['matricule']) ?></p>
        <p><strong>Numero de facture</strong><br><?= h($receipt['invoice_number']) ?></p>
        <p><strong>Montant paye</strong><br><?= money($receipt['amount']) ?></p>
        <p><strong>Methode de paiement</strong><br><?= h(payment_method_label($receipt['payment_method'])) ?></p>
        <p><strong>Reference de paiement</strong><br><?= h($receipt['payment_reference']) ?></p>
        <p><strong>Date de paiement</strong><br><?= h($receipt['submitted_at']) ?></p>
        <p><strong>Date de validation</strong><br><?= h($receipt['validated_at']) ?></p>
    </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
