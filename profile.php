<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/student_auth.php';

// Student profile page: display the authenticated student's dossier data.
$student = current_student();

$pageTitle = 'Profil';
$layout = 'student';
$active = 'profile';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <h1>Profil</h1>
</div>
<div class="card">
    <div class="grid grid-3">
        <p><strong>Matricule</strong><br><?= h($student['matricule']) ?></p>
        <p><strong>Statut</strong><br><?= badge($student['account_status']) ?></p>
        <p><strong>Prenom</strong><br><?= h($student['first_name']) ?></p>
        <p><strong>Nom</strong><br><?= h($student['last_name']) ?></p>
        <p><strong>Telephone</strong><br><?= h($student['phone'] ?? '') ?></p>
        <p><strong>Filiere</strong><br><?= h(program_label($student['program'])) ?></p>
        <p><strong>Niveau</strong><br><?= h(level_label($student['level'])) ?></p>
        <p><strong>Annee academique</strong><br><?= h($student['academic_year']) ?></p>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
