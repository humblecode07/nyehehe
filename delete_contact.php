<?php
include 'db.php'; // Assumes $pdo is your PDO connection

// Set headers
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Credentials: true"); // ← add this line
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, DELETE");
header("Access-Control-Allow-Headers: Content-Type");

// For internal debugging only
error_reporting(E_ALL);
ini_set('display_errors', 0); // Keep errors logged but not shown to client

// Fallback for $conn
global $pdo;
if (!isset($pdo) && isset($conn)) {
   $pdo = $conn;
}

// Logging
$debug_log = fopen("contact_delete_debug.log", "a");
fwrite($debug_log, "-------- " . date('Y-m-d H:i:s') . " --------\n");
fwrite($debug_log, "REQUEST_METHOD: " . $_SERVER["REQUEST_METHOD"] . "\n");
fwrite($debug_log, "POST data: " . print_r($_POST, true) . "\n");
fwrite($debug_log, "GET data: " . print_r($_GET, true) . "\n");

// Get contact ID from POST or GET
$id = $_POST['id'] ?? $_GET['id'] ?? null;

if (!$id) {
   echo json_encode(["success" => false, "error" => "Missing contact ID"]);
   fwrite($debug_log, "Error: Missing contact ID\n");
   fclose($debug_log);
   exit;
}

try {
   // Check if contact exists
   $stmtSelect = $pdo->prepare("SELECT id FROM contact_messages WHERE id = :id");
   $stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);
   $stmtSelect->execute();

   if ($stmtSelect->rowCount() === 0) {
      echo json_encode(["success" => false, "error" => "Contact message not found"]);
      fwrite($debug_log, "Error: Contact message not found with ID $id\n");
      fclose($debug_log);
      exit;
   }

   fwrite($debug_log, "Contact found with ID: $id\n");

   // Delete record
   $stmtDelete = $pdo->prepare("DELETE FROM contact_messages WHERE id = :id");
   $stmtDelete->bindParam(':id', $id, PDO::PARAM_INT);
   $result = $stmtDelete->execute();

   if ($result) {
      echo json_encode(["success" => true]);
      fwrite($debug_log, "Contact message deleted successfully\n");
   } else {
      $errorInfo = $stmtDelete->errorInfo();
      echo json_encode(["success" => false, "error" => "Failed to delete contact message"]);
      fwrite($debug_log, "Delete failed: " . print_r($errorInfo, true) . "\n");
   }
} catch (PDOException $e) {
   fwrite($debug_log, "PDOException: " . $e->getMessage() . "\n");
   echo json_encode(["success" => false, "error" => $e->getMessage()]);
} catch (Exception $e) {
   fwrite($debug_log, "Exception: " . $e->getMessage() . "\n");
   echo json_encode(["success" => false, "error" => "An unexpected error occurred"]);
}

fclose($debug_log);
