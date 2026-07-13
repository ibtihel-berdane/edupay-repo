<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_student();

// JSON endpoint polled by the student dashboard for live financial data.
json_response(student_dashboard_payload(current_student_id()));
