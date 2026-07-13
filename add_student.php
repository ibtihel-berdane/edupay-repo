<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Add a student financial dossier. Students later use this dossier to activate their login.
$values = [
    'matricule' => '',
    'first_name' => '',
    'last_name' => '',
    'program' => '',
    'level' => '',
    'academic_year' => '',
    'phone' => '',
];
$programOptions = program_options();
$levelOptions = level_options();
$errors = [];

if (is_post()) {
    verify_csrf();
    foreach ($values as $key => $_) {
        $values[$key] = trim((string)($_POST[$key] ?? ''));
    }

    foreach (['matricule', 'first_name', 'last_name', 'program', 'level', 'academic_year'] as $required) {
        if ($values[$required] === '') {
            $errors[] = field_label($required) . ' est obligatoire.';
        }
    }
    if ($values['program'] !== '' && !is_valid_program($values['program'])) {
        $errors[] = 'Selectionnez une filiere valide.';
    }
    if ($values['level'] !== '' && !is_valid_level($values['level'])) {
        $errors[] = 'Selectionnez un niveau valide.';
    }
    if ($values['matricule'] !== '' && !validate_student_matricule($values['matricule'])) {
        $errors[] = 'Le matricule doit commencer par 20xx et contenir 12 chiffres.';
    }
    if (!$errors && (int)query_value('SELECT COUNT(*) FROM students WHERE matricule = ?', [$values['matricule']]) > 0) {
        $errors[] = 'Ce matricule existe deja.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'INSERT INTO students (student_code, matricule, first_name, last_name, program, level, academic_year, phone, created_by)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $values['matricule'],
            $values['matricule'],
            $values['first_name'],
            $values['last_name'],
            $values['program'],
            $values['level'],
            $values['academic_year'],
            $values['phone'] ?: null,
            current_admin_id(),
        ]);
        flash('success', 'Dossier financier etudiant cree.');
        redirect('admin/students.php');
    }
}

$pageTitle = 'Ajouter un etudiant';
$layout = 'admin';
$active = 'students';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Ajouter un dossier financier etudiant</h1>
    <a class="btn btn-secondary" href="<?= h(url('admin/students.php')) ?>">Retour</a>
</div>
<form class="card" method="post" novalidate>
    <?= csrf_field() ?>
    <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endforeach; ?>
    <div class="form-grid">
        <?php foreach ($values as $key => $value): ?>
            <div class="form-row">
                <label for="<?= h($key) ?>"><?= h(field_label($key)) ?></label>
                <?php if ($key === 'program'): ?>
                    <select id="<?= h($key) ?>" name="<?= h($key) ?>" required>
                        <option value="">Selectionner une filiere</option>
                        <?php foreach ($programOptions as $optionValue => $label): ?>
                            <option value="<?= h($optionValue) ?>" <?= $value === $optionValue ? 'selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php elseif ($key === 'level'): ?>
                    <select id="<?= h($key) ?>" name="<?= h($key) ?>" required>
                        <option value="">Selectionner un niveau</option>
                        <?php foreach ($levelOptions as $optionValue => $label): ?>
                            <option value="<?= h($optionValue) ?>" <?= $value === $optionValue ? 'selected' : '' ?>><?= h($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php else: ?>
                    <input id="<?= h($key) ?>" name="<?= h($key) ?>" value="<?= h($value) ?>" <?= $key === 'matricule' ? 'maxlength="12"' : '' ?> <?= $key === 'phone' ? '' : 'required' ?>>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <button class="btn btn-primary" type="submit">Creer le dossier</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
