<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
start_app_session();

// Clear the student session and return to the shared login page.
clear_student_session();
session_regenerate_id(true);
redirect('login.php');
