<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

// Backward-compatible student login entry; the shared login page handles the real form.
redirect('login.php');
