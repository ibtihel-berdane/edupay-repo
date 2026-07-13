<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Add a fee definition used to automatically create matching student invoices.
$values = [
    'fee_name' => 'registration',
    'program' => '',
    'level' => '',
    'academic_year' => '',
    'due_date' => date('Y-m-d', strtotime('+30 days')),
    'amount' => '',
    'description' => '',
    'custom_fee_name' => '',
];
$programOptions = program_options(true);
$levelOptions = level_options(true);
$errors = [];

if (is_post()) {
    verify_csrf();

    foreach ($values as $key => $_) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }

    $isCustomFee = $values['fee_name'] === 'custom';
    $feeNameToSave = $isCustomFee ? trim((string)$values['custom_fee_name']) : $values['fee_name'];

    $feeType = fee_type_for_name($feeNameToSave);

    if (!$feeType && !$isCustomFee) {
        $errors[] = 'Seuls inscription, transport et hebergement sont autorises.';
    }

    if ($isCustomFee) {
        if ($feeNameToSave === '') {
            $errors[] = 'Nom du frais personnel est obligatoire.';
        }
        // Misc fee type is treated as optional by default.
        $feeType = 'optional';
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
        'SELECT COUNT(*) FROM fees WHERE fee_name = ? AND program = ? AND level = ? AND academic_year = ?',
        [$feeNameToSave, $values['program'], $values['level'], $values['academic_year']]
    ) > 0) {
        $errors[] = 'Ce frais existe deja pour la meme filiere, le meme niveau, la meme annee et le meme nom.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO fees (fee_name, program, level, academic_year, due_date, amount, fee_type, description)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
        );

        $stmt->execute([
            $feeNameToSave,
            $values['program'],
            $values['level'],
            $values['academic_year'],
            $values['due_date'],
            (float)$values['amount'],
            $feeType,
            $values['description'] ?: null,
        ]);

        flash('success', 'Frais enregistre.');
        redirect('admin/fees.php');
    }
}

$pageTitle = 'Ajouter un frais';
$layout = 'admin';
$active = 'fees';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Ajouter un frais</h1>
    <a class="btn btn-secondary" href="<?= h(url('admin/fees.php')) ?>">Retour</a>
</div>
<form class="card" method="post" novalidate>
    <?= csrf_field() ?>

    <?php foreach ($errors as $error): ?>
        <div class="alert alert-danger"><?= h($error) ?></div>
    <?php endforeach; ?>

    <div class="form-grid">
        <div class="form-row">
            <label for="fee_name">Nom du frais</label>
            <select id="fee_name" name="fee_name" onchange="toggleCustomFeeName()">
                <?php foreach (['registration', 'transport', 'housing', 'custom'] as $name): ?>
                    <option value="<?= h($name) ?>" <?= $values['fee_name'] === $name ? 'selected' : '' ?>>
                        <?= h(['registration' => 'Inscription', 'transport' => 'Transport', 'housing' => 'Hebergement', 'custom' => 'Autre (personnaliser)'][$name]) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-row" id="custom_fee_name_row" style="<?= $values['fee_name'] === 'custom' ? '' : 'display:none;' ?>">
            <label for="custom_fee_name">Nom du frais (personnel)</label>
            <input id="custom_fee_name" name="custom_fee_name" type="text" value="<?= h($values['custom_fee_name']) ?>" placeholder="Ex: Frais de documentation" />
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

<script>
    function toggleCustomFeeName() {
        var feeName = document.getElementById('fee_name').value;
        var row = document.getElementById('custom_fee_name_row');
        if (!row) return;
        row.style.display = feeName === 'custom' ? '' : 'none';
    }
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>

