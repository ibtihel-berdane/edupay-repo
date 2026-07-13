<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Reporting page: combines invoice, payment, and student totals under common filters.
ensure_automatic_invoices_for_all_students();
sync_all_invoice_statuses();

$filters = [
    'program' => trim($_GET['program'] ?? ''),
    'level' => trim($_GET['level'] ?? ''),
    'academic_year' => trim($_GET['academic_year'] ?? ''),
    'matricule' => trim($_GET['matricule'] ?? ''),
    'invoice_status' => trim($_GET['invoice_status'] ?? ''),
    'payment_status' => trim($_GET['payment_status'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
];
$programOptions = program_options();
$levelOptions = level_options();
if ($filters['program'] !== '' && !is_valid_program($filters['program'])) {
    $filters['program'] = '';
}
if ($filters['level'] !== '' && !is_valid_level($filters['level'])) {
    $filters['level'] = '';
}

$invoiceWhere = [];
$invoiceParams = [];
foreach (['program', 'level', 'academic_year', 'matricule'] as $field) {
    if ($filters[$field] !== '') {
        $invoiceWhere[] = "s.{$field} = ?";
        $invoiceParams[] = $filters[$field];
    }
}
if (in_array($filters['invoice_status'], ['paid', 'unpaid', 'partially_paid', 'late'], true)) {
    $invoiceWhere[] = 'i.status = ?';
    $invoiceParams[] = $filters['invoice_status'];
}
if ($filters['date_from'] !== '') {
    $invoiceWhere[] = 'DATE(i.created_at) >= ?';
    $invoiceParams[] = $filters['date_from'];
}
if ($filters['date_to'] !== '') {
    $invoiceWhere[] = 'DATE(i.created_at) <= ?';
    $invoiceParams[] = $filters['date_to'];
}
$invoiceSql = "SELECT i.*, s.matricule, s.program, s.level, s.academic_year, CONCAT(s.first_name, ' ', s.last_name) AS student_name
               FROM invoices i JOIN students s ON s.id = i.student_id";
if ($invoiceWhere) {
    $invoiceSql .= ' WHERE ' . implode(' AND ', $invoiceWhere);
}
$invoiceSql .= ' ORDER BY i.created_at DESC';
$invoices = query_all($invoiceSql, $invoiceParams);

$paymentWhere = [];
$paymentParams = [];
foreach (['program', 'level', 'academic_year', 'matricule'] as $field) {
    if ($filters[$field] !== '') {
        $paymentWhere[] = "s.{$field} = ?";
        $paymentParams[] = $filters[$field];
    }
}
if (in_array($filters['payment_status'], ['pending', 'validated', 'rejected'], true)) {
    $paymentWhere[] = 'p.status = ?';
    $paymentParams[] = $filters['payment_status'];
}
if ($filters['date_from'] !== '') {
    $paymentWhere[] = 'DATE(p.submitted_at) >= ?';
    $paymentParams[] = $filters['date_from'];
}
if ($filters['date_to'] !== '') {
    $paymentWhere[] = 'DATE(p.submitted_at) <= ?';
    $paymentParams[] = $filters['date_to'];
}
$paymentSql = "SELECT p.*, s.matricule, CONCAT(s.first_name, ' ', s.last_name) AS student_name, i.invoice_number
               FROM payments p
               JOIN students s ON s.id = p.student_id
               JOIN invoices i ON i.id = p.invoice_id";
if ($paymentWhere) {
    $paymentSql .= ' WHERE ' . implode(' AND ', $paymentWhere);
}
$paymentSql .= ' ORDER BY p.submitted_at DESC';
$payments = query_all($paymentSql, $paymentParams);

$studentWhere = [];
$studentParams = [];
foreach (['program', 'level', 'academic_year', 'matricule'] as $field) {
    if ($filters[$field] !== '') {
        $studentWhere[] = "{$field} = ?";
        $studentParams[] = $filters[$field];
    }
}
$studentSql = 'SELECT * FROM students';
if ($studentWhere) {
    $studentSql .= ' WHERE ' . implode(' AND ', $studentWhere);
}
$studentSql .= ' ORDER BY created_at DESC';
$students = query_all($studentSql, $studentParams);

$invoiceTotal = array_sum(array_map(fn(array $row): float => (float)$row['total_amount'], $invoices));
$invoicePaid = array_sum(array_map(fn(array $row): float => (float)$row['paid_amount'], $invoices));
$paymentTotal = array_sum(array_map(fn(array $row): float => (float)$row['amount'], $payments));

$pageTitle = 'Rapports';
$layout = 'admin';
$active = 'reports';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Rapports</h1>
        <p class="muted">Filtrer les factures, paiements et statuts d inscription.</p>
    </div>
</div>

<form class="card filter-card" method="get">
    <div class="form-grid">
        <?php foreach (['program', 'level', 'academic_year', 'matricule'] as $field): ?>
            <div class="form-row"><label for="<?= h($field) ?>"><?= h([
                'program' => 'Filiere',
                'level' => 'Niveau',
                'academic_year' => 'Annee academique',
                'matricule' => 'Matricule',
            ][$field]) ?></label>
                <?php if ($field === 'program'): ?>
                    <select id="<?= h($field) ?>" name="<?= h($field) ?>">
                        <option value="">Toutes</option>
                        <?php foreach ($programOptions as $value => $label): ?>
                            <option value="<?= h($value) ?>" <?= $filters[$field] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($field === 'level'): ?>
                    <select id="<?= h($field) ?>" name="<?= h($field) ?>">
                        <option value="">Tous</option>
                        <?php foreach ($levelOptions as $value => $label): ?>
                            <option value="<?= h($value) ?>" <?= $filters[$field] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input id="<?= h($field) ?>" name="<?= h($field) ?>" value="<?= h($filters[$field]) ?>">
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
        <div class="form-row">
            <label for="invoice_status">Statut facture</label>
            <select id="invoice_status" name="invoice_status">
                <option value="">Tous</option>
                <?php foreach (['paid', 'unpaid', 'partially_paid', 'late'] as $status): ?>
                    <option value="<?= h($status) ?>" <?= $filters['invoice_status'] === $status ? 'selected' : '' ?>><?= h([
                        'paid' => 'Payee',
                        'unpaid' => 'Non payee',
                        'partially_paid' => 'Partiellement payee',
                        'late' => 'En retard',
                    ][$status]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="payment_status">Statut paiement</label>
            <select id="payment_status" name="payment_status">
                <option value="">Tous</option>
                <?php foreach (['pending', 'validated', 'rejected'] as $status): ?>
                    <option value="<?= h($status) ?>" <?= $filters['payment_status'] === $status ? 'selected' : '' ?>><?= h([
                        'pending' => 'En attente',
                        'validated' => 'Valide',
                        'rejected' => 'Rejete',
                    ][$status]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label for="date_from">Date debut</label><input id="date_from" name="date_from" type="date" value="<?= h($filters['date_from']) ?>"></div>
        <div class="form-row"><label for="date_to">Date fin</label><input id="date_to" name="date_to" type="date" value="<?= h($filters['date_to']) ?>"></div>
    </div>
    <div class="actions">
        <button class="btn btn-primary" type="submit">Generer le rapport</button>
        <a class="btn btn-secondary" href="<?= h(url('admin/reports.php')) ?>">Effacer</a>
    </div>
</form>

<div class="grid grid-4">
    <div class="stat-card"><span>Factures</span><strong><?= h((string)count($invoices)) ?></strong></div>
    <div class="stat-card"><span>Total facture</span><strong><?= money($invoiceTotal) ?></strong></div>
    <div class="stat-card"><span>Total paye</span><strong><?= money($invoicePaid) ?></strong></div>
    <div class="stat-card"><span>Total paiements</span><strong><?= money($paymentTotal) ?></strong></div>
</div>

<div class="page-header" style="margin-top:24px"><h2>Factures</h2></div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Facture</th><th>Matricule</th><th>Filiere</th><th>Total</th><th>Paye</th><th>Reste</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($invoices as $invoice): ?>
            <tr>
                <td><?= h($invoice['invoice_number']) ?></td>
                <td><?= h($invoice['matricule'] . ' - ' . $invoice['student_name']) ?></td>
                <td><?= h(program_label($invoice['program']) . ' / ' . level_label($invoice['level'])) ?></td>
                <td><?= money($invoice['total_amount']) ?></td>
                <td><?= money($invoice['paid_amount']) ?></td>
                <td><?= money($invoice['remaining_amount']) ?></td>
                <td><?= badge($invoice['status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="page-header" style="margin-top:24px"><h2>Paiements</h2></div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Reference</th><th>Matricule</th><th>Facture</th><th>Montant</th><th>Statut</th><th>Date</th></tr></thead>
        <tbody>
        <?php foreach ($payments as $payment): ?>
            <tr>
                <td><?= h($payment['payment_reference']) ?></td>
                <td><?= h($payment['matricule'] . ' - ' . $payment['student_name']) ?></td>
                <td><?= h($payment['invoice_number']) ?></td>
                <td><?= money($payment['amount']) ?></td>
                <td><?= badge($payment['status']) ?></td>
                <td><?= h($payment['submitted_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="page-header" style="margin-top:24px"><h2>Etudiants</h2></div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Matricule</th><th>Nom</th><th>Filiere</th><th>Annee academique</th><th>Statut</th></tr></thead>
        <tbody>
        <?php foreach ($students as $student): ?>
            <tr>
                <td><?= h($student['matricule']) ?></td>
                <td><?= h($student['first_name'] . ' ' . $student['last_name']) ?></td>
                <td><?= h(program_label($student['program']) . ' / ' . level_label($student['level'])) ?></td>
                <td><?= h($student['academic_year']) ?></td>
                <td><?= badge($student['account_status']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
