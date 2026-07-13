<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/student_auth.php';

// Student bourse page: show assignment status, next stipend date, and stipend history.
$studentId = current_student_id();
$payload = student_dashboard_payload($studentId);
$student = $payload['student'];
$stipend = $payload['stipend'];

$pageTitle = 'Bourse';
$layout = 'student';
$active = 'stipend';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-header">
    <div>
        <h1>Bourse</h1>
        <p class="muted"><?= h($student['first_name'] . ' ' . $student['last_name']) ?> | <?= h(level_label($student['level'])) ?></p>
    </div>
</div>

<div class="grid grid-4 stipend-stats">
    <div class="stat-card stat-purple">
        <i class="fa-solid fa-user-check stat-icon"></i>
        <span>Attribution</span>
        <strong><?= $stipend['is_enabled'] ? badge('assigned') : badge('not_assigned') ?></strong>
    </div>
    <div class="stat-card stat-green">
        <i class="fa-solid fa-hand-holding-dollar stat-icon"></i>
        <span>Ce mois</span>
        <strong><?= $stipend['current_month_received'] ? badge('disbursed') : badge('pending') ?></strong>
    </div>
    <div class="stat-card stat-teal">
        <i class="fa-solid fa-coins stat-icon"></i>
        <span>Montant du mois</span>
        <strong><?= money($stipend['current_month_amount']) ?> DA</strong>
    </div>
    <div class="stat-card stat-orange">
        <i class="fa-solid fa-sack-dollar stat-icon"></i>
        <span>Total recu</span>
        <strong><?= money($stipend['total_received']) ?> DA</strong>
    </div>
</div>

<div class="card stipend-card">
    <div class="grid grid-3">
        <p><strong>Montant eligible</strong><br><?= money($stipend['eligible_amount']) ?> DA</p>
        <p><strong>Mois courant</strong><br><?= h($stipend['current_month']) ?></p>
        <p><strong>Prochaine bourse</strong><br><?= $stipend['is_enabled'] ? h($stipend['next_stipend_date']) : h('En attente d attribution') ?></p>
    </div>
</div>

<div class="page-header" style="margin-top:24px"><h2>Historique des bourses</h2></div>
<div class="table-wrap">
    <table>
        <thead><tr><th>Mois</th><th>Montant</th><th>Niveau</th><th>Statut</th><th>Date de versement</th></tr></thead>
        <tbody>
        <?php if ($stipend['history']): ?>
            <?php foreach ($stipend['history'] as $row): ?>
                <tr>
                    <td><?= h($row['stipend_month']) ?></td>
                    <td><?= money($row['amount']) ?> DA</td>
                    <td><?= h($row['level_group']) ?></td>
                    <td><?= badge($row['status']) ?></td>
                    <td><?= h($row['disbursed_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="5" class="muted">Aucune bourse versee.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
