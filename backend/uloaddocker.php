<?php
// uloaddocker.php - File upload handler for DockerManager
// Allow CORS
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed', 'success' => false]);
    exit;
}

// Check for file upload
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    $error = isset($_FILES['file']) ? $_FILES['file']['error'] : 'No file uploaded';
    echo json_encode(['error' => "File upload failed: $error", 'success' => false]);
    exit;
}

// Get destination path from POST data
$destinationPath = isset($_POST['path']) ? $_POST['path'] : '';

if (empty($destinationPath)) {
    // Fallback: save to uploads directory with original name
    $uploadDir = __DIR__ . '/uploads';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    $destinationPath = $uploadDir . '/' . basename($_FILES['file']['name']);
}

// Ensure destination directory exists
$destDir = dirname($destinationPath);
if (!is_dir($destDir)) {
    if (!mkdir($destDir, 0777, true)) {
        http_response_code(500);
        echo json_encode(['error' => "Failed to create directory: $destDir", 'success' => false]);
        exit;
    }
}

// Move the uploaded file
if (move_uploaded_file($_FILES['file']['tmp_name'], $destinationPath)) {
    echo json_encode([
        'success' => true,
        'message' => 'File uploaded successfully',
        'path' => $destinationPath
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to move uploaded file', 'success' => false]);
}
