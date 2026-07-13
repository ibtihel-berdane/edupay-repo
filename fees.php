<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin_auth.php';

// Fee catalog page: filter, list, edit, or delete configured fees.
if (is_post() && ($_POST['action'] ?? '') === 'delete') {
    verify_csrf();
    $feeId = (int)($_POST['fee_id'] ?? 0);
    if ($feeId <= 0) {
        flash('danger', 'Frais invalide.');
    } else {
        try {
            hard_delete_fee($feeId);
            flash('success', 'Frais supprime avec les lignes de facture et paiements associes.');
        } catch (Throwable $exception) {
            flash('danger', 'Suppression impossible : ' . $exception->getMessage());
        }
    }
    redirect('admin/fees.php');
}

$filters = [
    'program' => trim($_GET['program'] ?? ''),
    'level' => trim($_GET['level'] ?? ''),
    'academic_year' => trim($_GET['academic_year'] ?? ''),
    'fee_type' => trim($_GET['fee_type'] ?? ''),
];
$programOptions = program_options(true);
$levelOptions = level_options(true);
$where = [];
$params = [];
foreach ($filters as $field => $value) {
    if ($value === '') {
        continue;
    }
    if ($field === 'program' && !is_valid_program($value, true)) {
        continue;
    }
    if ($field === 'level' && !is_valid_level($value, true)) {
        continue;
    }
    if ($field === 'fee_type' && !in_array($value, ['obligatory', 'optional'], true)) {
        continue;
    }
    $where[] = "{$field} = ?";
    $params[] = $value;
}
$sql = 'SELECT * FROM fees';
if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= ' ORDER BY academic_year DESC, program ASC, level ASC, fee_name ASC';
$fees = query_all($sql, $params);

$pageTitle = 'Frais';
$layout = 'admin';
$active = 'fees';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Frais</h1>
        <p class="muted">L inscription est obligatoire. Transport et hebergement sont optionnels.</p>
    </div>
    <a class="btn btn-primary" href="<?= h(url('admin/add_fee.php')) ?>">Ajouter un frais</a>
</div>

<form class="card filter-card" method="get">
    <div class="form-grid">
        <div class="form-row">
            <label for="program">Filiere</label>
            <select id="program" name="program">
                <option value="">Toutes</option>
                <?php foreach ($programOptions as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= $filters['program'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row">
            <label for="level">Niveau</label>
            <select id="level" name="level">
                <option value="">Tous</option>
                <?php foreach ($levelOptions as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= $filters['level'] === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-row"><label for="academic_year">Annee academique</label><input id="academic_year" name="academic_year" value="<?= h($filters['academic_year']) ?>"></div>
        <div class="form-row">
            <label for="fee_type">Type de frais</label>
            <select id="fee_type" name="fee_type">
                <option value="">Tous</option>
                <option value="obligatory" <?= $filters['fee_type'] === 'obligatory' ? 'selected' : '' ?>>Obligatoire</option>
                <option value="optional" <?= $filters['fee_type'] === 'optional' ? 'selected' : '' ?>>Optionnel</option>
            </select>
        </div>
    </div>
    <div class="actions">
        <button class="btn btn-primary" type="submit">Filtrer</button>
        <a class="btn btn-secondary" href="<?= h(url('admin/fees.php')) ?>">Effacer</a>
    </div>
</form>

<div class="table-wrap">
    <table>
        <thead><tr><th>Nom</th><th>Filiere</th><th>Niveau</th><th>Annee academique</th><th>Date limite</th><th>Montant</th><th>Type</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($fees as $fee): ?>
            <tr>
                <td><?= h(fee_label($fee['fee_name'])) ?></td>
                <td><?= h(program_label($fee['program'])) ?></td>
                <td><?= h(level_label($fee['level'])) ?></td>
                <td><?= h($fee['academic_year']) ?></td>
                <td><?= h($fee['due_date'] ?? '') ?></td>
                <td><?= money($fee['amount']) ?></td>
                <td><?= badge($fee['fee_type']) ?></td>
                <td class="actions">
                    <a class="btn btn-secondary btn-small" href="<?= h(url('admin/edit_fee.php?id=' . $fee['id'])) ?>">Modifier</a>
                    <form method="post" onsubmit="return confirm('Supprimer ce frais ?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="fee_id" value="<?= h((string)$fee['id']) ?>">
                        <button class="btn btn-danger btn-small" type="submit">Supprimer</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
