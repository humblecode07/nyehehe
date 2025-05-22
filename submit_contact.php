<?php
require_once 'db.php'; // db.php should provide either $conn or $pdo

// Set CORS and response headers
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Log file setup
$log = fopen("contact_debug.log", "a");
fwrite($log, "-------- " . date('Y-m-d H:i:s') . " --------\n");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    fwrite($log, "OPTIONS preflight request handled\n");
    fclose($log);
    exit;
}

// Restrict to POST method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    fwrite($log, "Error: Invalid request method\n");
    fclose($log);
    exit;
}

// Attempt to read JSON input
$input = file_get_contents("php://input");
$data = json_decode($input, true);

// Fallback if JSON is not provided (could be form POST)
if (!$data && !empty($_POST)) {
    $data = $_POST;
    fwrite($log, "Fallback to form POST data\n");
} else {
    fwrite($log, "Received JSON data: " . $input . "\n");
}

$name = $data['name'] ?? '';
$email = $data['email'] ?? '';
$message = $data['message'] ?? '';

// Log values
fwrite($log, "Name: $name\nEmail: $email\nMessage: $message\n");

// Simple validation
if (empty($name) || empty($email) || empty($message)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing fields']);
    fwrite($log, "Validation failed: Missing fields\n");
    fclose($log);
    exit;
}

try {
    // Use either $conn or $pdo depending on your db.php
    global $pdo;
    if (!isset($pdo) && isset($conn)) {
        $pdo = $conn;
    }

    $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message, status) VALUES (?, ?, ?, 'New')");
    $result = $stmt->execute([$name, $email, $message]);

    if ($result) {
        echo json_encode(['success' => true]);
        fwrite($log, "Database insert successful\n");
    } else {
        echo json_encode(['success' => false, 'error' => 'Insert failed']);
        fwrite($log, "Insert failed: " . print_r($stmt->errorInfo(), true) . "\n");
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    fwrite($log, "PDOException: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Unexpected error']);
    fwrite($log, "Exception: " . $e->getMessage() . "\n");
}

fclose($log);
