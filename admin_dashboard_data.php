<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';
require_admin();

// JSON endpoint polled by the agent dashboard for live financial totals.
json_response(admin_dashboard_payload());
