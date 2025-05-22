<?php
include 'db.php'; // This should define $pdo

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

try {
   $sql = "SELECT id, name, description, category, price, image FROM food_items ORDER BY id DESC";
   $stmt = $pdo->prepare($sql);
   $stmt->execute();

   $foods = $stmt->fetchAll(PDO::FETCH_ASSOC);
   echo json_encode($foods);
} catch (PDOException $e) {
   http_response_code(500); // Good practice to let frontend know it's server error
   echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
