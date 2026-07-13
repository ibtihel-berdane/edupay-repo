<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/functions.php';

start_app_session();

// Notification API: supports polling plus mark-one/mark-all read actions.
$recipient = current_notification_recipient();
if (!$recipient) {
    json_response(['error' => 'Non authentifie.'], 401);
}

if (is_post()) {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        json_response(['error' => 'Jeton de formulaire invalide.'], 419);
    }

    $action = (string)($_POST['action'] ?? '');
    if ($action === 'mark_all') {
        mark_notifications_read($recipient['type'], (int)$recipient['id']);
    } elseif ($action === 'mark_one') {
        mark_notifications_read($recipient['type'], (int)$recipient['id'], (int)($_POST['notification_id'] ?? 0));
    } else {
        json_response(['error' => 'Action invalide.'], 422);
    }

    json_response([
        'ok' => true,
        'unread_count' => unread_notification_count($recipient['type'], (int)$recipient['id']),
        'notifications' => notifications_for_recipient($recipient['type'], (int)$recipient['id']),
    ]);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    json_response(['error' => 'Methode non autorisee.'], 405);
}

$notifications = notifications_for_recipient($recipient['type'], (int)$recipient['id']);
$latestId = 0;
foreach ($notifications as $notification) {
    $latestId = max($latestId, (int)$notification['id']);
}

json_response([
    'notifications' => $notifications,
    'unread_count' => unread_notification_count($recipient['type'], (int)$recipient['id']),
    'latest_id' => $latestId,
]);
