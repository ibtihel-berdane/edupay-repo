<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Payment list page: filters submitted payments for agent review.
sync_all_invoice_statuses();

$filters = [
    'status' => trim($_GET['status'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
];
$where = [];
$params = [];
if (in_array($filters['status'], ['pending', 'validated', 'rejected'], true)) {
    $where[] = 'p.status = ?';
    $params[] = $filters['status'];
}
if ($filters['search'] !== '') {
    $where[] = "(p.payment_reference LIKE ? OR s.matricule LIKE ? OR i.invoice_number LIKE ?)";
    $like = '%' . $filters['search'] . '%';
    array_push($params, $like, $like, $like);
}

$sql = "SELECT p.*, s.matricule, CONCAT(s.first_name, ' ', s.last_name) AS student_name,
               i.invoice_number, ii.fee_name, r.id AS receipt_id
        FROM payments p
        JOIN students s ON s.id = p.student_id
        JOIN invoices i ON i.id = p.invoice_id
        LEFT JOIN invoice_items ii ON ii.id = p.invoice_item_id
        LEFT JOIN receipts r ON r.payment_id = p.id";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY p.submitted_at DESC';
$payments = query_all($sql, $params);

$pageTitle = 'Paiements';
$layout = 'admin';
$active = 'payments';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Paiements</h1>
        <p class="muted">Valider ou rejeter les paiements soumis par les etudiants.</p>
    </div>
</div>

<form class="card filter-card" method="get">
    <div class="form-grid">
        <div class="form-row">
            <label for="status">Statut</label>
            <select id="status" name="status">
                <option value="">Tous</option>
                <?php foreach (['pending', 'validated', 'rejected'] as $status): ?>
                    <option value="<?= h($status) ?>" <?= $filters['status'] === $status ? 'selected' : '' ?>><?= h(['pending' => 'En attente', 'validated' => 'Valide', 'rejected' => 'Rejete'][$status]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label for="search">Recherche</label><input id="search" name="search" value="<?= h($filters['search']) ?>"></div>
    </div>
    <div class="actions">
        <button class="btn btn-primary" type="submit">Filtrer</button>
        <a class="btn btn-secondary" href="<?= h(url('admin/payments.php')) ?>">Effacer</a>
    </div>
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>Reference</th><th>Matricule</th><th>Facture</th><th>Cible</th><th>Montant</th><th>Methode</th><th>Statut</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= h($payment['payment_reference']) ?></td>
                <td><?= h($payment['matricule'] . ' - ' . $payment['student_name']) ?></td>
                <td><?= h($payment['invoice_number']) ?></td>
                <td><?= h(fee_label($payment['fee_name'] ?? null)) ?></td>
                <td><?= money($payment['amount']) ?></td>
                <td><?= h(payment_method_label($payment['payment_method'])) ?></td>
                <td><?= badge($payment['status']) ?></td>
                <td class="actions receipt-cell">
                    <?php if ($payment['status'] === 'pending'): ?>
                        <a class="btn btn-primary btn-small" href="<?= h(url('admin/validate_payment.php?id=' . $payment['id'])) ?>">Examiner</a>
                    <?php endif; ?>
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
