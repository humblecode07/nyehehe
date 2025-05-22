<?php
include 'db.php'; // Assumes $pdo is defined here

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
   $sql = "SELECT * FROM contact_messages ORDER BY created_at DESC";
   $stmt = $pdo->prepare($sql);
   $stmt->execute();

   $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

   echo json_encode(['contacts' => $results]);
} catch (PDOException $e) {
   http_response_code(500);
   echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
