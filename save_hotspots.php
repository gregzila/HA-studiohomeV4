<?php
header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['room']) || !isset($input['hotspots'])) {
        $response['message'] = 'Invalid input: room or hotspots missing.';
        http_response_code(400);
        echo json_encode($response);
        exit;
    }

    $roomName = $input['room'];
    $hotspots = $input['hotspots'];
    $configFile = 'config_' . strtolower($roomName) . '.json';

    if (!file_exists($configFile)) {
        $response['message'] = "Config file not found for room: {$roomName}";
        http_response_code(404);
        echo json_encode($response);
        exit;
    }

    // Attempt to lock the file before writing
    $fileHandle = fopen($configFile, 'r+');
    if (flock($fileHandle, LOCK_EX)) {
        $currentData = json_decode(fread($fileHandle, filesize($configFile)), true);
        if ($currentData === null) {
             // Handle JSON error if the file is corrupt
            $currentData = []; // Or handle as an error
        }

        $currentData['hotspots'] = $hotspots;
        $newJsonData = json_encode($currentData, JSON_PRETTY_PRINT);

        // Go to the beginning of the file and truncate it before writing
        ftruncate($fileHandle, 0);
        rewind($fileHandle);
        
        if (fwrite($fileHandle, $newJsonData)) {
            $response = ['status' => 'success', 'message' => 'Hotspots saved successfully.'];
        } else {
            $response['message'] = 'Failed to write to config file.';
            http_response_code(500);
        }
        
        flock($fileHandle, LOCK_UN); // Release the lock
    } else {
        $response['message'] = 'Could not get a lock on the config file.';
        http_response_code(500);
    }
    fclose($fileHandle);

} else {
    $response['message'] = 'Invalid request method.';
    http_response_code(405);
}

echo json_encode($response);
?>