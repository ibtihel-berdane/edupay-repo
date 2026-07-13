<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Student management page: search, list, view/edit, or hard-delete student dossiers.
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $studentId = (int)($_POST['student_id'] ?? 0);

    if ($studentId <= 0) {
        flash('danger', 'Etudiant invalide.');
    } else {
        try {
            hard_delete_student($studentId);
            flash('success', 'Dossier financier supprime avec ses factures, paiements et recus.');
        } catch (Throwable $exception) {
            flash('danger', 'Suppression impossible : ' . $exception->getMessage());
        }
    }
    redirect('admin/students.php');
}

$search = trim($_GET['search'] ?? '');
$where = [];
$params = [];
if ($search !== '') {
    $where[] = "(matricule LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR program LIKE ? OR level LIKE ?)";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}

$sql = 'SELECT * FROM students';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY created_at DESC';
$students = query_all($sql, $params);

$pageTitle = 'Etudiants';
$layout = 'admin';
$active = 'students';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Etudiants</h1>
        <p class="muted">Dossiers financiers crees par les agents comptables.</p>
    </div>
    <a class="btn btn-primary" href="<?= h(url('admin/add_student.php')) ?>">Ajouter un etudiant</a>
</div>

<form class="card filter-card" method="get">
    <div class="form-grid">
        <div class="form-row">
            <label for="search">Recherche</label>
            <input id="search" name="search" value="<?= h($search) ?>" placeholder="Matricule, nom, filiere, niveau">
        </div>
        <div class="form-row actions" style="align-items:end">
            <button class="btn btn-primary" type="submit">Rechercher</button>
            <a class="btn btn-secondary" href="<?= h(url('admin/students.php')) ?>">Effacer</a>
        </div>
    </div>
</form>

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Matricule</th>
            <th>Nom</th>
            <th>Filiere</th>
            <th>Niveau</th>
            <th>Annee academique</th>
            <th>Statut</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $student): ?>
            <tr>
                <td><?= h($student['matricule']) ?></td>
                <td><?= h($student['first_name'] . ' ' . $student['last_name']) ?></td>
                <td><?= h(program_label($student['program'])) ?></td>
                <td><?= h(level_label($student['level'])) ?></td>
                <td><?= h($student['academic_year']) ?></td>
                <td><?= badge($student['account_status']) ?></td>
                <td class="actions">
                    <a class="btn btn-secondary btn-small" href="<?= h(url('admin/view_student.php?id=' . $student['id'])) ?>">Voir</a>
                    <a class="btn btn-secondary btn-small" href="<?= h(url('admin/edit_student.php?id=' . $student['id'])) ?>">Modifier</a>
                    <form method="post" onsubmit="return confirm('Supprimer ce dossier financier ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="student_id" value="<?= h((string)$student['id']) ?>">
                        <button class="btn btn-danger btn-small" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
