<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
start_app_session();

// The same page handles both student and agent registration; role selects the active branch.
$role = $_GET['role'] ?? ($_POST['role'] ?? '');
$role = in_array($role, ['student', 'admin'], true) ? $role : '';

// Already-authenticated users should not register another account from the same session.
if (current_student_id()) {
    redirect('student/dashboard.php');
}
if (current_admin_id()) {
    // Let an agent intentionally switch to student signup by clearing the admin session.
    if ($role === 'student') {
        clear_admin_session();
        flash('warning', 'Session agent fermee pour continuer l inscription etudiant.');
    } elseif ($role === 'admin') {
        redirect('admin/dashboard.php');
    }
}

$errors = [];

// Default values keep the form sticky after validation errors.
$studentValues = [
    'matricule' => '',
    'first_name' => '',
    'last_name' => '',
];
$adminValues = [
    'last_name' => '',
    'first_name' => '',
    'matricule' => '',
];

if (is_post()) {
    // Every signup POST must come from a valid form rendered by this app.
    verify_csrf();
    $password = (string)($_POST['password'] ?? '');
    $confirmPassword = (string)($_POST['confirm_password'] ?? '');

    if ($role === 'student') {
        // Student signup links a web account to an existing financial dossier.
        foreach ($studentValues as $key => $_) {
            $studentValues[$key] = trim((string)($_POST[$key] ?? ''));
        }

        // Basic required-field and password checks run before touching the database.
        foreach ($studentValues as $value) {
            if ($value === '') {
                $errors[] = 'Tous les champs sont obligatoires.';
                break;
            }
        }
        if (!validate_student_matricule($studentValues['matricule'])) {
            $errors[] = 'Le matricule etudiant doit commencer par 20xx et contenir 12 chiffres.';
        }
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caracteres.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'La confirmation du mot de passe ne correspond pas.';
        }

        if (!$errors) {
            // The matricule must already exist because agents create student dossiers first.
            $student = query_one('SELECT * FROM students WHERE matricule = ?', [$studentValues['matricule']]);
            if (!$student) {
                $errors[] = 'Aucun dossier financier ne correspond a ce matricule.';
            } elseif (!student_identity_matches($student, $studentValues['first_name'], $studentValues['last_name'])) {
                $errors[] = 'Le nom ou le prenom ne correspond pas au dossier financier.';
            } elseif ($student['password'] !== null || $student['account_status'] !== 'not_registered') {
                $errors[] = 'Ce compte etudiant est deja inscrit.';
            } else {
                // Store only the password hash and mark the account as registered.
                $stmt = db()->prepare("UPDATE students SET password = ?, account_status = 'registered' WHERE id = ? AND password IS NULL AND account_status = 'not_registered'");
                $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $student['id']]);
                flash('success', 'Inscription terminee. Vous pouvez vous connecter.');
                redirect('login.php');
            }
        }
    } elseif ($role === 'admin') {
        // Agent signup creates a new accounting-agent account directly.
        foreach ($adminValues as $key => $_) {
            $adminValues[$key] = trim((string)($_POST[$key] ?? ''));
        }

        // Agent accounts require identity fields, a unique matricule, and a strong enough password.
        foreach ($adminValues as $value) {
            if ($value === '') {
                $errors[] = 'Tous les champs sont obligatoires.';
                break;
            }
        }
        if (strlen($password) < 8) {
            $errors[] = 'Le mot de passe doit contenir au moins 8 caracteres.';
        }
        if ($password !== $confirmPassword) {
            $errors[] = 'La confirmation du mot de passe ne correspond pas.';
        }
        if (!$errors && (int)query_value('SELECT COUNT(*) FROM admins WHERE matricule = ?', [$adminValues['matricule']]) > 0) {
            $errors[] = 'Ce matricule agent existe deja.';
        }

        if (!$errors) {
            // Admin codes are generated internally; the submitted matricule stays as the login identity.
            $fullName = $adminValues['first_name'] . ' ' . $adminValues['last_name'];
            $stmt = db()->prepare(
                'INSERT INTO admins (admin_code, matricule, first_name, last_name, full_name, password, role) VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                generate_admin_code(),
                $adminValues['matricule'],
                $adminValues['first_name'],
                $adminValues['last_name'],
                $fullName,
                password_hash($password, PASSWORD_DEFAULT),
                'accounting_agent',
            ]);
            flash('success', 'Compte agent cree. Vous pouvez vous connecter.');
            redirect('login.php');
        }
    } else {
        $errors[] = 'Choisissez un role.';
    }
}

// Render the public signup layout after all redirects and POST handling are complete.
$pageTitle = 'Inscription';
$layout = 'public';
require __DIR__ . '/../includes/header.php';
?>
<div class="auth-wrap">
<div class="auth-brand"><i class="fa-solid fa-graduation-cap" style="color:var(--color-blue);"></i> <span class="brand-white">Edu</span><span class="brand-blue">Pay+</span></div>
    <?php if ($role === ''): ?>
        <!-- Step 1: choose which account type to create. -->
        <section class="auth-card auth-card-wide">
            <div class="auth-heading">
                <h1>Inscription</h1>
            </div>
            <div class="role-grid">
                <a class="role-card student-card" href="<?= h(url('student/signup.php?role=student')) ?>">
                    <i class="fa-solid fa-user-graduate role-icon"></i>
                    <strong>Etudiant</strong>
                </a>
                <a class="role-card agent-card" href="<?= h(url('student/signup.php?role=admin')) ?>">
                    <i class="fa-solid fa-user-tie role-icon"></i>
                    <strong>Agent</strong>
                </a>
            </div>
            <div class="auth-footer">
                Deja un compte ? <a href="<?= h(url('login.php')) ?>">Connectez-vous</a>
            </div>
        </section>
    <?php elseif ($role === 'student'): ?>
        <!-- Student form: verifies identity against the existing financial dossier. -->
        <form class="auth-card auth-card-wide" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="role" value="student">
            <div class="auth-heading">
                <span class="auth-kicker">Etape 2</span>
                <h1>Inscription etudiant</h1>
                <p class="muted">Votre dossier financier doit deja exister.</p>
            </div>
            <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endforeach; ?>
            <div class="form-row">
                <label for="matricule">Matricule</label>
                <input id="matricule" name="matricule" value="<?= h($studentValues['matricule']) ?>" maxlength="12" autocomplete="username" placeholder="202400000000" required>
            </div>
            <div class="form-row">
                <label for="first_name">Prenom</label>
                <input id="first_name" name="first_name" value="<?= h($studentValues['first_name']) ?>" required>
            </div>
            <div class="form-row">
                <label for="last_name">Nom</label>
                <input id="last_name" name="last_name" value="<?= h($studentValues['last_name']) ?>" required>
            </div>
            <div class="form-row">
                <label for="password">Mot de passe</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div class="form-row">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input id="confirm_password" name="confirm_password" type="password" required>
            </div>
            <div class="actions auth-actions">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-plus"></i> Creer le compte</button>
                <a class="btn btn-secondary" href="<?= h(url('student/signup.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
            <div class="auth-footer">
                Deja un compte ? <a href="<?= h(url('login.php')) ?>">Connectez-vous</a>
            </div>
        </form>
    <?php else: ?>
        <!-- Agent form: creates a new accounting-agent login. -->
        <form class="auth-card auth-card-wide" method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="role" value="admin">
            <div class="auth-heading">
                <span class="auth-kicker">Etape 2</span>
                <h1>Inscription agent</h1>
                <p class="muted">Renseignez les informations de l'agent comptable.</p>
            </div>
            <?php foreach ($errors as $error): ?><div class="alert alert-danger"><?= h($error) ?></div><?php endforeach; ?>
            <div class="form-row">
                <label for="last_name">Nom</label>
                <input id="last_name" name="last_name" value="<?= h($adminValues['last_name']) ?>" required>
            </div>
            <div class="form-row">
                <label for="first_name">Prenom</label>
                <input id="first_name" name="first_name" value="<?= h($adminValues['first_name']) ?>" required>
            </div>
            <div class="form-row">
                <label for="matricule">Matricule</label>
                <input id="matricule" name="matricule" value="<?= h($adminValues['matricule']) ?>" autocomplete="username" placeholder="ADM001 ou matricule agent" required>
            </div>
            <div class="form-row">
                <label for="password">Mot de passe</label>
                <input id="password" name="password" type="password" required>
            </div>
            <div class="form-row">
                <label for="confirm_password">Confirmer le mot de passe</label>
                <input id="confirm_password" name="confirm_password" type="password" required>
            </div>
            <div class="actions auth-actions">
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-plus"></i> Creer le compte</button>
                <a class="btn btn-secondary" href="<?= h(url('student/signup.php')) ?>"><i class="fa-solid fa-arrow-left"></i> Retour</a>
            </div>
            <div class="auth-footer">
                Deja un compte ? <a href="<?= h(url('login.php')) ?>">Connectez-vous</a>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
