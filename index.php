<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/functions.php';
start_app_session();

// Public landing page: send known users to their dashboard and show role choices to guests.
if (current_admin_id()) {
    redirect('admin/dashboard.php');
}
if (current_student_id()) {
    redirect('student/dashboard.php');
}

$pageTitle = 'Accueil';
$layout = 'public';
require __DIR__ . '/includes/header.php';
?>
<div class="auth-wrap">
<div class="auth-brand"><i class="fa-solid fa-graduation-cap" style="color:var(--color-blue);"></i> <span class="brand-white">Edu</span><span class="brand-blue">Pay+</span></div>
    <section class="auth-card auth-card-wide" style="text-align:center;">
        <div class="auth-heading">
            <h1>Bienvenue</h1>
            <p class="muted">La plateforme de gestion comptable dediee aux etudiants et agents.</p>
        </div>
        <div class="role-grid" style="margin-bottom:8px;">
            <a class="role-card student-card" href="<?= h(url('login.php')) ?>">
                <i class="fa-solid fa-right-to-bracket role-icon"></i>
                <strong>Connexion</strong>
            </a>
            <a class="role-card agent-card" href="<?= h(url('student/signup.php')) ?>">
                <i class="fa-solid fa-user-plus role-icon"></i>
                <strong>Inscription</strong>
            </a>
        </div>
        <div class="auth-footer">
            EduPay+ &mdash; Gestion comptable des etudiants
        </div>
    </section>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
