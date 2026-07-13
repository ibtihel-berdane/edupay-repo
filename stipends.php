<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Handle agent actions before rendering so the list always reloads from fresh database state.
if (is_post()) {
    verify_csrf();
    $studentId = (int)($_POST['student_id'] ?? 0);
    $action = (string)($_POST['action'] ?? '');

    // Only existing students can be assigned or revoked.
    $student = $studentId > 0 ? query_one('SELECT id, first_name, last_name FROM students WHERE id = ?', [$studentId]) : null;
    if (!$student || !in_array($action, ['assign', 'revoke'], true)) {
        flash('danger', 'Demande invalide.');
        redirect('admin/stipends.php');
    }

    if ($action === 'assign') {
        // Grant eligibility and immediately create the current month stipend if it does not exist.
        $stmt = db()->prepare('UPDATE students SET stipend_enabled = 1 WHERE id = ?');
        $stmt->execute([$studentId]);
        ensure_monthly_stipend_for_student($studentId);
        flash('success', 'Bourse attribuee.');
    } else {
        // Revocation stops future automatic stipends; existing history remains visible.
        $stmt = db()->prepare('UPDATE students SET stipend_enabled = 0 WHERE id = ?');
        $stmt->execute([$studentId]);
        flash('success', 'Bourse supprimee pour les prochains versements.');
    }

    redirect('admin/stipends.php');
}

// Read filter values from the query string and build the student search safely with bound parameters.
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$where = [];
$params = [];

if ($search !== '') {
    $where[] = "(s.matricule LIKE ? OR s.first_name LIKE ? OR s.last_name LIKE ? OR s.program LIKE ? OR s.level LIKE ?)";
    $like = '%' . $search . '%';
    array_push($params, $like, $like, $like, $like, $like);
}
if ($status === 'assigned') {
    $where[] = 's.stipend_enabled = 1';
}
if ($status === 'not_assigned') {
    $where[] = 's.stipend_enabled = 0';
}

// List each student with aggregate stipend history for the table.
$sql = "SELECT s.*,
               COALESCE((SELECT SUM(ss.amount) FROM student_stipends ss WHERE ss.student_id = s.id), 0) AS stipend_total,
               (SELECT COUNT(*) FROM student_stipends ss WHERE ss.student_id = s.id) AS stipend_count,
               (SELECT MAX(ss.disbursed_at) FROM student_stipends ss WHERE ss.student_id = s.id) AS last_stipend_at
        FROM students s";
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY s.stipend_enabled DESC, s.created_at DESC';
$students = query_all($sql, $params);

// Summary cards: current eligibility counts, projected monthly payout, and total already disbursed.
$assignedLevels = query_all('SELECT level FROM students WHERE stipend_enabled = 1');
$stats = [
    'assigned' => (int)query_value('SELECT COUNT(*) FROM students WHERE stipend_enabled = 1'),
    'not_assigned' => (int)query_value('SELECT COUNT(*) FROM students WHERE stipend_enabled = 0'),
    'monthly_total' => array_sum(array_map(static fn(array $row): float => stipend_amount_for_level($row['level']), $assignedLevels)),
    'paid_total' => (float)query_value('SELECT COALESCE(SUM(amount), 0) FROM student_stipends'),
];

$pageTitle = 'Bourses';
$layout = 'admin';
$active = 'stipends';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Bourses</h1>
        <p class="muted">Attribution et suivi des bourses mensuelles.</p>
    </div>
</div>

<div class="grid grid-4">
    <div class="stat-card stat-green"><i class="fa-solid fa-user-check stat-icon"></i><span>Attribuees</span><strong><?= h((string)$stats['assigned']) ?></strong></div>
    <div class="stat-card stat-orange"><i class="fa-solid fa-user-xmark stat-icon"></i><span>Non attribuees</span><strong><?= h((string)$stats['not_assigned']) ?></strong></div>
    <div class="stat-card stat-teal"><i class="fa-solid fa-calendar-check stat-icon"></i><span>A verser ce mois</span><strong><?= money($stats['monthly_total']) ?> DA</strong></div>
    <div class="stat-card stat-purple"><i class="fa-solid fa-sack-dollar stat-icon"></i><span>Total deja verse</span><strong><?= money($stats['paid_total']) ?> DA</strong></div>
</div>

<form class="card filter-card" method="get" style="margin-top:18px">
    <div class="form-grid">
        <div class="form-row">
            <label for="search">Recherche</label>
            <input id="search" name="search" value="<?= h($search) ?>" placeholder="Matricule, nom, filiere, niveau">
        </div>
        <div class="form-row">
            <label for="status">Attribution</label>
            <select id="status" name="status">
                <option value="">Tous</option>
                <option value="assigned" <?= $status === 'assigned' ? 'selected' : '' ?>>Attribuee</option>
                <option value="not_assigned" <?= $status === 'not_assigned' ? 'selected' : '' ?>>Non attribuee</option>
            </select>
        </div>
    </div>
    <div class="actions">
        <button class="btn btn-primary" type="submit">Rechercher</button>
        <a class="btn btn-secondary" href="<?= h(url('admin/stipends.php')) ?>">Effacer</a>
    </div>
</form>

<div class="table-wrap">
    <table>
        <thead>
        <tr>
            <th>Matricule</th>
            <th>Etudiant</th>
            <th>Filiere</th>
            <th>Niveau</th>
            <th>Attribution</th>
            <th>Montant mensuel</th>
            <th>Deja verse</th>
            <th></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($students as $student): ?>
            <?php $enabled = (int)$student['stipend_enabled'] === 1; ?>
            <tr>
                <td><?= h($student['matricule']) ?></td>
                <td><?= h($student['first_name'] . ' ' . $student['last_name']) ?></td>
                <td><?= h(program_label($student['program'])) ?></td>
                <td><?= h(level_label($student['level'])) ?></td>
                <td><?= $enabled ? badge('assigned') : badge('not_assigned') ?></td>
                <td><?= money(stipend_amount_for_level($student['level'])) ?> DA</td>
                <td><?= money($student['stipend_total']) ?> DA</td>
                <td class="table-actions">
                    <?php if ($enabled): ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="student_id" value="<?= h((string)$student['id']) ?>">
                            <input type="hidden" name="action" value="revoke">
                            <button class="btn btn-danger btn-small" type="submit">Supprimer la bourse</button>
                        </form>
                    <?php else: ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="student_id" value="<?= h((string)$student['id']) ?>">
                            <input type="hidden" name="action" value="assign">
                            <button class="btn btn-primary btn-small" type="submit">Attribuer la bourse</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$students): ?>
            <tr><td colspan="8" class="muted">Aucun etudiant trouve.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
