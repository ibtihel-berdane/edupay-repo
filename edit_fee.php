<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Edit a fee definition while preserving invoice/payment consistency rules.
$feeId = (int)($_GET['id'] ?? 0);
$fee = query_one('SELECT * FROM fees WHERE id = ?', [$feeId]);
if (!$fee) {
    flash('danger', 'Frais introuvable.');
    redirect('admin/fees.php');
}

$values = [
    'fee_name' => $fee['fee_name'],
    'program' => $fee['program'],
    'level' => $fee['level'],
    'academic_year' => $fee['academic_year'],
    'due_date' => $fee['due_date'] ?? date('Y-m-d', strtotime('+30 days')),
    'amount' => (string)$fee['amount'],
    'description' => $fee['description'] ?? '',
];
$programOptions = program_options(true);
$levelOptions = level_options(true);
$errors = [];

if (is_post()) {
    verify_csrf();
    foreach ($values as $key => $_) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }
    $feeType = fee_type_for_name($values['fee_name']);

    if (!$feeType) {
        $errors[] = 'Seuls inscription, transport et hebergement sont autorises.';
    }
    foreach (['program', 'level', 'academic_year', 'due_date', 'amount'] as $required) {
        if ($values[$required] === '') {
            $errors[] = field_label($required) . ' est obligatoire.';
        }
    }
    if ($values['program'] !== '' && !is_valid_program($values['program'], true)) {
        $errors[] = 'Selectionnez une filiere valide.';
    }
    if ($values['level'] !== '' && !is_valid_level($values['level'], true)) {
        $errors[] = 'Selectionnez un niveau valide.';
    }
    if ($values['amount'] !== '' && (float)$values['amount'] <= 0) {
        $errors[] = 'Le montant doit etre superieur a 0.';
    }
    if ($values['due_date'] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $values['due_date'])) {
        $errors[] = 'La date limite doit etre une date valide.';
    }
    if (!$errors && (int)query_value(
        'SELECT COUNT(*) FROM fees WHERE fee_name = ? AND program = ? AND level = ? AND academic_year = ? AND id <> ?',
        [$values['fee_name'], $values['program'], $values['level'], $values['academic_year'], $feeId]
    ) > 0) {
        $errors[] = 'Ce frais existe deja pour la meme filiere, le meme niveau, la meme annee et le meme nom.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'UPDATE fees SET fee_name = ?, program = ?, level = ?, academic_year = ?, due_date = ?, amount = ?, fee_type = ?, description = ? WHERE id = ?'
        );
        $stmt->execute([
            $values['fee_name'],
            $values['program'],
            $values['level'],
            $values['academic_year'],
            $values['due_date'],
            (float)$values['amount'],
            $feeType,
            $values['description'] ?: null,
            $feeId,
        ]);
        flash('success', 'Frais modifie.');
        redirect('admin/fees.php');
    }
}

$pageTitle = 'Modifier un frais';
$layout = 'admin';
$active = 'fees';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Modifier un frais</h1>
    <a class="btn btn-secondary" href="<?= h(url('admin/fees.php')) ?>">Retour</a>
</div>
<form class="card" method="post" novalidate>
    <?= csrf_field() ?>
    <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endforeach; ?>
    <div class="form-grid">
        <div class="form-row">
            <label for="fee_name">Nom du frais</label>
            <select id="fee_name" name="fee_name">
                <?php foreach (['registration', 'transport', 'housing'] as $name): ?>
                    <option value="<?= h($name) ?>" <?= $values['fee_name'] === $name ? 'selected' : '' ?>><?= h(['registration' => 'Inscription', 'transport' => 'Transport', 'housing' => 'Hebergement'][$name]) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label for="amount">Montant</label><input id="amount" name="amount" type="number" min="0.01" step="0.01" value="<?= h($values['amount']) ?>" required></div>
        <div class="form-row">
            <label for="program">Filiere</label>
            <select id="program" name="program" required>
                <option value="">Selectionner une filiere</option>
                <?php foreach ($programOptions as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= $values['program'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="level">Niveau</label>
            <select id="level" name="level" required>
                <option value="">Selectionner un niveau</option>
                <?php foreach ($levelOptions as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= $values['level'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label for="academic_year">Annee academique</label><input id="academic_year" name="academic_year" value="<?= h($values['academic_year']) ?>" required></div>
        <div class="form-row"><label for="due_date">Date limite de paiement</label><input id="due_date" name="due_date" type="date" value="<?= h($values['due_date']) ?>" required></div>
    </div>
    <div class="form-row">
        <label for="description">Description</label>
        <textarea id="description" name="description"><?= h($values['description']) ?></textarea>
    </div>
    <button class="btn btn-primary" type="submit">Enregistrer</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
