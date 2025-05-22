<?php
include 'db.php'; // This defines $pdo

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
    $stmt = $pdo->prepare("SELECT id, name, address, contact, manager, hours, image FROM branches ORDER BY id DESC");
    $stmt->execute();
    $branches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($branches);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
