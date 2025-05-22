<?php
include 'db.php'; // make sure this defines $pdo

// Set headers first before output
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Error reporting but don't show errors to user
error_reporting(E_ALL);
ini_set('display_errors', 0);

global $pdo;
if (!isset($pdo) && isset($conn)) {
   $pdo = $conn;
}

// Open debug log file
$debug_log = fopen("food_delete_debug.log", "a");
fwrite($debug_log, "-------- " . date('Y-m-d H:i:s') . " --------\n");
fwrite($debug_log, "POST data: " . print_r($_POST, true) . "\n");

$id = $_POST['id'] ?? null;

if (!$id || !is_numeric($id)) {
   $error = "Invalid or missing ID";
   fwrite($debug_log, "Error: $error\n");
   echo json_encode(["success" => false, "error" => $error]);
   fclose($debug_log);
   exit;
}

try {
   // Fetch current image path before deletion
   $stmtSelect = $pdo->prepare("SELECT image FROM food_items WHERE id = :id");
   $stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);
   $stmtSelect->execute();

   if ($stmtSelect->rowCount() === 0) {
      $error = "No item found with that ID";
      fwrite($debug_log, "Error: $error\n");
      echo json_encode(["success" => false, "error" => $error]);
      fclose($debug_log);
      exit;
   }

   $item = $stmtSelect->fetch(PDO::FETCH_ASSOC);
   $imagePath = $item['image'];

   // Delete DB record
   $stmtDelete = $pdo->prepare("DELETE FROM food_items WHERE id = :id");
   $stmtDelete->bindParam(':id', $id, PDO::PARAM_INT);
   $stmtDelete->execute();

   if ($stmtDelete->rowCount() > 0) {
      fwrite($debug_log, "Deleted DB record with ID $id\n");

      // Delete image file if exists
      if ($imagePath && file_exists($imagePath)) {
         if (unlink($imagePath)) {
            fwrite($debug_log, "Deleted image file: $imagePath\n");
         } else {
            fwrite($debug_log, "Failed to delete image file: $imagePath\n");
         }
      } else {
         fwrite($debug_log, "No image file to delete or file does not exist\n");
      }

      echo json_encode(["success" => true]);
   } else {
      $error = "Failed to delete the item";
      fwrite($debug_log, "Error: $error\n");
      echo json_encode(["success" => false, "error" => $error]);
   }
} catch (PDOException $e) {
   fwrite($debug_log, "PDO Exception: " . $e->getMessage() . "\n");
   echo json_encode(["success" => false, "error" => "Database error occurred"]);
} catch (Exception $e) {
   fwrite($debug_log, "General Exception: " . $e->getMessage() . "\n");
   echo json_encode(["success" => false, "error" => "An unexpected error occurred"]);
}

fclose($debug_log);
