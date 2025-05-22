<?php
include 'db.php'; // Assumes $pdo is your PDO connection

// Set headers
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
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
$debug_log = fopen("branch_delete_debug.log", "a");
fwrite($debug_log, "-------- " . date('Y-m-d H:i:s') . " --------\n");
fwrite($debug_log, "POST data: " . print_r($_POST, true) . "\n");

// Get branch ID
$id = $_POST['id'] ?? null;

if (!$id) {
   echo json_encode(["success" => false, "error" => "Missing branch ID"]);
   fwrite($debug_log, "Error: Missing branch ID\n");
   fclose($debug_log);
   exit;
}

try {
   // Step 1: Get image path
   $stmtSelect = $pdo->prepare("SELECT image FROM branches WHERE id = :id");
   $stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);
   $stmtSelect->execute();

   if ($stmtSelect->rowCount() === 0) {
      echo json_encode(["success" => false, "error" => "Branch not found"]);
      fwrite($debug_log, "Error: Branch not found with ID $id\n");
      fclose($debug_log);
      exit;
   }

   $branch = $stmtSelect->fetch(PDO::FETCH_ASSOC);
   $imagePath = $branch['image'];
   fwrite($debug_log, "Image to delete: $imagePath\n");

   // Step 2: Delete record
   $stmtDelete = $pdo->prepare("DELETE FROM branches WHERE id = :id");
   $stmtDelete->bindParam(':id', $id, PDO::PARAM_INT);
   $result = $stmtDelete->execute();

   if ($result) {
      // Step 3: Delete image if exists
      if ($imagePath && file_exists($imagePath)) {
         if (unlink($imagePath)) {
            fwrite($debug_log, "Image deleted: $imagePath\n");
         } else {
            fwrite($debug_log, "Failed to delete image: $imagePath\n");
         }
      }

      echo json_encode(["success" => true]);
      fwrite($debug_log, "Branch deleted successfully\n");
   } else {
      $errorInfo = $stmtDelete->errorInfo();
      echo json_encode(["success" => false, "error" => "Failed to delete branch"]);
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
?>
