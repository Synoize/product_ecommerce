<?php

/**
 * Shipment status webhook endpoint for Shiprocket.
 *
 * Configure this URL in Shiprocket webhook settings:
 * BASE_URL/api/shipment_status_webhook.php
 */

require_once __DIR__ . '/../includes/db_connect.php';
require_once __DIR__ . '/../includes/shiprocket.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$configuredToken = defined('SHIPROCKET_WEBHOOK_TOKEN') ? SHIPROCKET_WEBHOOK_TOKEN : '';
if ($configuredToken !== '') {
    $incomingToken = $_SERVER['HTTP_X_API_KEY'] ?? $_SERVER['HTTP_X_SHIPROCKET_TOKEN'] ?? '';
    if (!hash_equals($configuredToken, $incomingToken)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Invalid webhook token']);
        exit;
    }
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON payload']);
    exit;
}

$events = isset($payload[0]) && is_array($payload[0]) ? $payload : [$payload];
$updated = 0;
$errors = [];

foreach ($events as $event) {
    try {
        shiprocketApplyTrackingUpdate($pdo, $event);
        $updated++;
    } catch (Exception $e) {
        $errors[] = $e->getMessage();
        error_log('Shipment Webhook Error: ' . $e->getMessage());
    }
}

if ($updated === 0 && $errors) {
    http_response_code(422);
}

echo json_encode([
    'success' => $updated > 0,
    'updated' => $updated,
    'errors' => $errors,
]);
