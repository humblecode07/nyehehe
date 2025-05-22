<?php
require_once 'db.php';

// Handle CORS if needed (for dev)
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);

$id = $data['id'] ?? null;
$status = $data['status'] ?? '';

if (!$id || !in_array($status, ['New', 'Read'])) {
   http_response_code(400);
   echo json_encode(['success' => false, 'error' => 'Invalid input']);
   exit;
}

try {
   $sql = "UPDATE contact_messages SET status = ? WHERE id = ?";
   $stmt = $conn->prepare($sql);
   $stmt->execute([$status, $id]);

   echo json_encode(['success' => true]);
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
