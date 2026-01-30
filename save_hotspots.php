<?php
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['room']) || !isset($input['hotspots'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid input.']);
    exit;
}

$room = $input['room'];
$hotspots = $input['hotspots'];
$configFile = 'config_' . strtolower($room) . '.json';

if (!file_exists($configFile)) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => "Config file not found for room: {$room}"]);
    exit;
}

$configData = json_decode(file_get_contents($configFile), true);
if ($configData === null) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => "Error reading or parsing config file: {$configFile}"]);
    exit;
}

$configData['hotspots'] = $hotspots;

if (file_put_contents($configFile, json_encode($configData, JSON_PRETTY_PRINT))) {
    echo json_encode(['status' => 'success', 'message' => 'Hotspots saved successfully.']);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Failed to write to config file.']);
}
?>