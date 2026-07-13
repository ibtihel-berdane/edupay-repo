<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Receipt list page: shows receipts generated from validated payments.
$receiptId = (int)($_GET['id'] ?? 0);
$where = $receiptId > 0 ? 'WHERE r.id = ?' : '';
$params = $receiptId > 0 ? [$receiptId] : [];
$receipts = query_all(
    "SELECT r.*, p.amount, p.payment_method, p.payment_reference, p.submitted_at, p.validated_at,
            s.matricule, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
            i.invoice_number
     FROM receipts r
     JOIN payments p ON p.id = r.payment_id AND p.status = 'validated'
     JOIN students s ON s.id = r.student_id
     JOIN invoices i ON i.id = r.invoice_id
     {$where}
     ORDER BY r.issued_at DESC",
    $params
);

$pageTitle = 'Recus';
$layout = 'admin';
$active = 'receipts';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Recus</h1>
        <p class="muted">Les recus existent seulement pour les paiements valides.</p>
    </div>
    <?php if ($receiptId > 0): ?><button class="btn btn-secondary" onclick="window.print()">Imprimer</button><?php endif; ?>
</div>

<?php foreach ($receipts as $receipt): ?>
    <section class="card receipt" style="margin-bottom:18px">
        <div class="receipt-header">
            <div>
                <h2><?= h($receipt['receipt_number']) ?></h2>
                <p class="muted">Emis le <?= h($receipt['issued_at']) ?></p>
            </div>
            <strong><?= money($receipt['amount']) ?></strong>
        </div>
        <div class="grid grid-2">
            <p><strong>Nom etudiant</strong><br><?= h($receipt['student_name']) ?></p>
            <p><strong>Matricule</strong><br><?= h($receipt['matricule']) ?></p>
            <p><strong>Numero de facture</strong><br><?= h($receipt['invoice_number']) ?></p>
            <p><strong>Methode de paiement</strong><br><?= h(payment_method_label($receipt['payment_method'])) ?></p>
            <p><strong>Reference de paiement</strong><br><?= h($receipt['payment_reference']) ?></p>
            <p><strong>Date de paiement</strong><br><?= h($receipt['submitted_at']) ?></p>
            <p><strong>Date de validation</strong><br><?= h($receipt['validated_at']) ?></p>
        </div>
    </section>
<?php endforeach; ?>

<?php if (!$receipts): ?>
    <div class="alert alert-warning">Aucun recu trouve.</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
