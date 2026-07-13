<?php
declare(strict_types=1);

require_once __DIR__ . '/functions.php';
start_app_session();

// Shared layout header: establishes navigation, theme/language controls, and page shell.
$pageTitle = $pageTitle ?? 'EduPay+';
$active = $active ?? '';
$layout = $layout ?? 'public';

$currentLanguage = current_language();
$languageOptions = supported_languages();
$languageRedirect = $_SERVER['REQUEST_URI'] ?? url('index.php');

$adminNav = [
    // Admin sidebar routes.
    'dashboard' => ['Tableau de bord', 'admin/dashboard.php'],
    'students' => ['Etudiants', 'admin/students.php'],
    'stipends' => ['Bourses', 'admin/stipends.php'],
    'fees' => ['Frais', 'admin/fees.php'],
    'invoices' => ['Factures', 'admin/invoices.php'],
    'payments' => ['Paiements', 'admin/payments.php'],
    'receipts' => ['Recus', 'admin/receipts.php'],
    'reports' => ['Rapports', 'admin/reports.php'],
];

$studentNav = [
    // Student sidebar routes.
    'dashboard' => ['Tableau de bord', 'student/dashboard.php'],
    'profile' => ['Profil', 'student/profile.php'],
    'stipend' => ['Bourse', 'student/stipend.php'],
    'invoices' => ['Factures', 'student/invoices.php'],
    'payments' => ['Paiements', 'student/payment_history.php'],
];

$isAuthenticated = !empty($_SESSION['admin_id']) || !empty($_SESSION['student_id']);
$cssVersion = (string)@filemtime(__DIR__ . '/../assets/css/style.css');
?>
<!doctype html>
<html lang="<?= h($currentLanguage) ?>" dir="<?= h(language_direction($currentLanguage)) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= h(tr($pageTitle)) ?> | EduPay+</title>
    <script>
        (function () {
            try {
                var storedTheme = localStorage.getItem('edupay-theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
                    document.documentElement.classList.add('theme-dark');
                }
            } catch (error) {}
        })();
    </script>
    <script>
        window.EDUPAY_LANGUAGE = <?= json_encode($currentLanguage, JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <link rel="stylesheet" href="<?= h(url('assets/css/style.css') . '?v=' . $cssVersion) ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="<?= ($layout ?? 'public') === 'public' ? 'public-gradient' : '' ?>">
<header class="topbar <?= empty($_SESSION['admin_id']) && empty($_SESSION['student_id']) ? 'topbar-public' : '' ?>">
    <a class="brand" href="<?= h(url('index.php')) ?>"><i class="fa-solid fa-graduation-cap"></i> EduPay+</a>
    <nav class="topnav">
        <?php if ($isAuthenticated): ?>
            <form class="language-form" method="post" action="<?= h(url('api/language.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="redirect" value="<?= h($languageRedirect) ?>">
                <label class="sr-only" for="language-select"><?= h(tr('Langue')) ?></label>
                <select id="language-select" name="lang" data-language-select aria-label="<?= h(tr('Langue')) ?>">
                    <?php foreach ($languageOptions as $languageCode => $languageLabel): ?>
                        <option value="<?= h($languageCode) ?>" <?= $currentLanguage === $languageCode ? 'selected' : '' ?>><?= h(tr($languageLabel)) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <button class="utility-button utility-button-icon theme-toggle" type="button" data-theme-toggle aria-label="<?= h(tr('Activer le mode nuit')) ?>" title="<?= h(tr('Mode nuit')) ?>">
                <i class="fa-solid fa-moon" data-theme-icon></i>
            </button>
            <div class="notification-center" data-notification-center data-notifications-api="<?= h(url('api/notifications.php')) ?>" data-csrf-token="<?= h(csrf_token()) ?>">
                <button class="utility-button utility-button-icon notification-button" type="button" data-notification-toggle aria-label="<?= h(tr('Notifications')) ?>" aria-expanded="false" title="<?= h(tr('Notifications')) ?>">
                    <i class="fa-solid fa-bell"></i>
                    <span class="notification-count" data-notification-count hidden>0</span>
                </button>
                <div class="notification-menu" data-notification-menu hidden>
                    <div class="notification-menu-header">
                        <strong><?= h(tr('Notifications')) ?></strong>
                        <button type="button" data-notification-mark-all><?= h(tr('Tout lu')) ?></button>
                    </div>
                    <div class="notification-list" data-notification-list>
                        <p class="notification-empty"><?= h(tr('Chargement...')) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php if (!empty($_SESSION['admin_id'])): ?>
            <span><i class="fa-solid fa-user-tie"></i> <?= h($_SESSION['admin_name'] ?? tr('Agent')) ?></span>
        <?php elseif (!empty($_SESSION['student_id'])): ?>
            <span><i class="fa-solid fa-user-graduate"></i> <?= h($_SESSION['student_name'] ?? tr('Etudiant')) ?></span>
        <?php endif; ?>
    </nav>
</header>
<?php if (!$isAuthenticated): ?>
    <div class="public-toolbar">
        <form class="language-form" method="post" action="<?= h(url('api/language.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="redirect" value="<?= h($languageRedirect) ?>">
            <label class="sr-only" for="public-language-select"><?= h(tr('Langue')) ?></label>
            <select id="public-language-select" name="lang" data-language-select aria-label="<?= h(tr('Langue')) ?>">
                <?php foreach ($languageOptions as $languageCode => $languageLabel): ?>
                    <option value="<?= h($languageCode) ?>" <?= $currentLanguage === $languageCode ? 'selected' : '' ?>><?= h(tr($languageLabel)) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <button class="utility-button utility-button-icon theme-toggle" type="button" data-theme-toggle aria-label="<?= h(tr('Activer le mode nuit')) ?>" title="<?= h(tr('Mode nuit')) ?>">
            <i class="fa-solid fa-moon" data-theme-icon></i>
        </button>
    </div>
<?php endif; ?>

<?php if ($layout === 'admin'): ?>
<div class="app-shell">
    <aside class="sidebar">
        <?php
        $adminIcons = [
            'dashboard' => 'fa-chart-line',
            'students' => 'fa-users',
            'stipends' => 'fa-hand-holding-dollar',
            'fees' => 'fa-file-invoice-dollar',
            'invoices' => 'fa-file-invoice',
            'payments' => 'fa-money-bill-wave',
            'receipts' => 'fa-receipt',
            'reports' => 'fa-chart-pie',
        ];
        foreach ($adminNav as $key => [$label, $path]): ?>
            <a class="<?= $active === $key ? 'active' : '' ?>" href="<?= h(url($path)) ?>"><i class="fa-solid <?= h($adminIcons[$key] ?? 'fa-circle') ?>"></i> <?= h(tr($label)) ?></a>
        <?php endforeach; ?>
        <div class="sidebar-footer">
            <a href="<?= h(url('admin/logout.php')) ?>"><i class="fa-solid fa-right-from-bracket"></i> <?= h(tr('Deconnexion')) ?></a>
        </div>
    </aside>
    <main class="content">
<?php elseif ($layout === 'student'): ?>
<div class="app-shell">
    <aside class="sidebar">
        <?php
        $studentIcons = [
            'dashboard' => 'fa-chart-line',
            'profile' => 'fa-user',
            'stipend' => 'fa-hand-holding-dollar',
            'invoices' => 'fa-file-invoice',
            'payments' => 'fa-money-bill-wave',
        ];
        foreach ($studentNav as $key => [$label, $path]): ?>
            <a class="<?= $active === $key ? 'active' : '' ?>" href="<?= h(url($path)) ?>"><i class="fa-solid <?= h($studentIcons[$key] ?? 'fa-circle') ?>"></i> <?= h(tr($label)) ?></a>
        <?php endforeach; ?>
        <div class="sidebar-footer">
            <a href="<?= h(url('student/logout.php')) ?>"><i class="fa-solid fa-right-from-bracket"></i> <?= h(tr('Deconnexion')) ?></a>
        </div>
    </aside>
    <main class="content">
<?php else: ?>
<main class="public-content">
<?php endif; ?>

<?php foreach (consume_flash() as $message): ?>
    <div class="alert alert-<?= h($message['type']) ?>"><?= h(tr($message['message'])) ?></div>
<?php endforeach; ?>
<?php
if ($currentLanguage !== 'fr') {
    $GLOBALS['edupay_translate_body'] = true;
    ob_start();
}
?>
