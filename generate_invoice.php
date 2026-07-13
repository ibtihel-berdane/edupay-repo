<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Manual invoice generation for a selected student and one or more matching fees.
$selectedStudentId = (int)($_GET['student_id'] ?? ($_POST['student_id'] ?? 0));
$selectedStudent = $selectedStudentId > 0 ? query_one('SELECT * FROM students WHERE id = ?', [$selectedStudentId]) : null;
$students = query_all('SELECT id, matricule, first_name, last_name, program, level, academic_year FROM students ORDER BY created_at DESC');
$matchingFees = [];
$registrationAlreadyInvoiced = false;
$errors = [];
$dueDate = trim($_POST['due_date'] ?? date('Y-m-d', strtotime('+30 days')));

if ($selectedStudent) {
    $allMatchingFees = query_all(
        "SELECT * FROM fees
         WHERE (program = ? OR program = 'ALL')
           AND (level = ? OR level = 'ALL')
           AND academic_year = ?
         ORDER BY fee_type ASC, fee_name ASC, program ASC, level ASC",
        [$selectedStudent['program'], $selectedStudent['level'], $selectedStudent['academic_year']]
    );
    foreach ($allMatchingFees as $fee) {
        if ($fee['fee_name'] === 'registration' && existing_student_fee_invoice_target($selectedStudentId, (int)$fee['id'])) {
            $registrationAlreadyInvoiced = true;
        }
        if (!existing_student_fee_invoice_target($selectedStudentId, (int)$fee['id'])) {
            $matchingFees[] = $fee;
        }
    }
}

if (is_post()) {
    verify_csrf();
    if (!$selectedStudent) {
        $errors[] = 'Selectionnez un etudiant valide.';
    }
    if ($dueDate === '') {
        $errors[] = 'La date d echeance est obligatoire.';
    }

    $selectedOptionalIds = array_map('intval', $_POST['optional_fee_ids'] ?? []);
    $invoiceFees = [];
    $registrationFeeFound = $registrationAlreadyInvoiced;

    foreach ($matchingFees as $fee) {
        if ($fee['fee_name'] === 'registration') {
            $invoiceFees[] = $fee;
            $registrationFeeFound = true;
            continue;
        }
        if ($fee['fee_type'] === 'optional' && in_array((int)$fee['id'], $selectedOptionalIds, true)) {
            $invoiceFees[] = $fee;
        }
    }

    if (!$registrationFeeFound) {
        $errors[] = 'Un frais d inscription doit exister pour cette filiere, ce niveau et cette annee.';
    }
    if (!$invoiceFees) {
        $errors[] = 'Aucun frais de facture selectionne.';
    }

    if (!$errors && $selectedStudent) {
        $total = array_sum(array_map(fn(array $fee): float => (float)$fee['amount'], $invoiceFees));
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $invoiceNumber = generate_invoice_number();
            $stmt = $pdo->prepare(
                'INSERT INTO invoices (invoice_number, student_id, total_amount, paid_amount, remaining_amount, status, due_date)
                 VALUES (?, ?, ?, 0, ?, ?, ?)'
            );
            $stmt->execute([
                $invoiceNumber,
                $selectedStudentId,
                $total,
                $total,
                invoice_status(0, $total, $dueDate),
                $dueDate,
            ]);
            $invoiceId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare(
                'INSERT INTO invoice_items (invoice_id, fee_id, fee_name, amount, fee_type) VALUES (?, ?, ?, ?, ?)'
            );
            foreach ($invoiceFees as $fee) {
                $itemStmt->execute([$invoiceId, $fee['id'], $fee['fee_name'], $fee['amount'], $fee['fee_type']]);
            }
            $pdo->commit();
            flash('success', 'Facture generee.');
            redirect('admin/view_invoice.php?id=' . $invoiceId);
        } catch (Throwable $exception) {
            $pdo->rollBack();
            $errors[] = 'La facture n a pas pu etre generee : ' . $exception->getMessage();
        }
    }
}

$pageTitle = 'Generer une facture';
$layout = 'admin';
$active = 'invoices';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Generer une facture</h1>
    <a class="btn btn-secondary" href="<?= h(url('admin/invoices.php')) ?>">Retour</a>
</div>
<form class="card filter-card" method="get">
    <div class="form-grid">
        <div class="form-row">
            <label for="student_id">Etudiant</label>
            <select id="student_id" name="student_id" required>
                <option value="">Selectionner un etudiant</option>
                <?php foreach ($students as $student): ?>
                    <option value="<?= h((string)$student['id']) ?>" <?= $selectedStudentId === (int)$student['id'] ? 'selected' : '' ?>>
                        <?= h($student['matricule'] . ' - ' . $student['first_name'] . ' ' . $student['last_name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row actions" style="align-items:end">
            <button class="btn btn-primary" type="submit">Charger les frais</button>
        </div>
    </div>
</form>

<?php if ($selectedStudent): ?>
<form class="card" method="post" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="student_id" value="<?= h((string)$selectedStudentId) ?>">
    <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endforeach; ?>
    <div class="grid grid-3">
        <p><strong>Filiere</strong><br><?= h(program_label($selectedStudent['program'])) ?></p>
        <p><strong>Niveau</strong><br><?= h(level_label($selectedStudent['level'])) ?></p>
        <p><strong>Annee academique</strong><br><?= h($selectedStudent['academic_year']) ?></p>
    </div>
    <div class="form-row">
        <label for="due_date">Date d echeance</label>
        <input id="due_date" name="due_date" type="date" value="<?= h($dueDate) ?>" required>
    </div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Inclure</th><th>Frais</th><th>Type</th><th>Montant</th></tr></thead>
            <tbody>
            <?php foreach ($matchingFees as $fee): ?>
                <tr>
                    <td>
                        <?php if ($fee['fee_name'] === 'registration'): ?>
                            <input type="checkbox" checked disabled>
                        <?php else: ?>
                            <input type="checkbox" name="optional_fee_ids[]" value="<?= h((string)$fee['id']) ?>">
                        <?php endif; ?>
                    </td>
                    <td><?= h(fee_label($fee['fee_name'])) ?></td>
                    <td><?= badge($fee['fee_type']) ?></td>
                    <td><?= money($fee['amount']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="actions" style="margin-top:16px">
        <button class="btn btn-primary" type="submit">Generer la facture</button>
    </div>
</form>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
