<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

// Language switcher endpoint: stores preference and safely redirects back.
start_app_session();

if (is_post()) {
    verify_csrf();
}

$language = (string)($_POST['lang'] ?? ($_GET['lang'] ?? 'fr'));
set_current_language($language);

$redirect = (string)($_POST['redirect'] ?? ($_GET['redirect'] ?? url('index.php')));
$base = app_base_url();
$isSafeRelative = str_starts_with($redirect, $base . '/') || $redirect === $base || str_starts_with($redirect, '/');

if (!$isSafeRelative || str_contains($redirect, "\n") || str_contains($redirect, "\r")) {
    $redirect = url('index.php');
}

header('Location: ' . $redirect);
exit;
