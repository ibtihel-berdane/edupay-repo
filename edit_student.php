<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Edit the identity and academic scope of an existing student dossier.
$studentId = (int)($_GET['id'] ?? 0);
$student = query_one('SELECT * FROM students WHERE id = ?', [$studentId]);
if (!$student) {
    flash('danger', 'Etudiant introuvable.');
    redirect('admin/students.php');
}

$values = [
    'matricule' => $student['matricule'],
    'first_name' => $student['first_name'],
    'last_name' => $student['last_name'],
    'program' => $student['program'],
    'level' => $student['level'],
    'academic_year' => $student['academic_year'],
    'phone' => $student['phone'] ?? '',
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
    if (!$errors && (int)query_value('SELECT COUNT(*) FROM students WHERE matricule = ? AND id <> ?', [$values['matricule'], $studentId]) > 0) {
        $errors[] = 'Ce matricule existe deja.';
    }

    if (!$errors) {
        $stmt = db()->prepare(
            'UPDATE students SET student_code = ?, matricule = ?, first_name = ?, last_name = ?, program = ?, level = ?, academic_year = ?, phone = ? WHERE id = ?'
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
            $studentId,
        ]);
        flash('success', 'Dossier financier etudiant modifie.');
        redirect('admin/view_student.php?id=' . $studentId);
    }
}

$pageTitle = 'Modifier un etudiant';
$layout = 'admin';
$active = 'students';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Modifier l'etudiant</h1>
    <a class="btn btn-secondary" href="<?= h(url('admin/view_student.php?id=' . $studentId)) ?>">Retour</a>
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
    <button class="btn btn-primary" type="submit">Enregistrer</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
