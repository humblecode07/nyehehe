<?php
include 'db.php'; // This should set up $pdo as your PDO instance

// Set headers first, before any output
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT");
header("Access-Control-Allow-Headers: Content-Type");

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to client, but log them

// Make sure we're using the same variable name as in db.php
global $pdo; // Try to access the PDO connection if it's named $pdo

// If your db.php uses $conn instead of $pdo, use this:
if (!isset($pdo) && isset($conn)) {
   $pdo = $conn;
}

// Open log file for debugging
$debug_log = fopen("branch_edit_debug.log", "a");
fwrite($debug_log, "-------- " . date('Y-m-d H:i:s') . " --------\n");
fwrite($debug_log, "POST data: " . print_r($_POST, true) . "\n");
fwrite($debug_log, "FILES data: " . print_r($_FILES, true) . "\n");

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$address = $_POST['address'] ?? '';
$contact = $_POST['contact'] ?? '';
$manager = $_POST['manager'] ?? '';
$hours = $_POST['hours'] ?? '';

if (!$id) {
   echo json_encode(["success" => false, "error" => "Missing branch ID"]);
   fwrite($debug_log, "Error: Missing branch ID\n");
   fclose($debug_log);
   exit;
}

try {
   // Fetch current image path so we can delete old file if replaced
   $sqlSelect = "SELECT image FROM branches WHERE id = :id";
   $stmtSelect = $pdo->prepare($sqlSelect);
   $stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);
   $stmtSelect->execute();

   if ($stmtSelect->rowCount() === 0) {
      echo json_encode(["success" => false, "error" => "Branch not found"]);
      fwrite($debug_log, "Error: Branch not found with ID $id\n");
      fclose($debug_log);
      exit;
   }

   $currentBranch = $stmtSelect->fetch(PDO::FETCH_ASSOC);
   $currentImage = $currentBranch['image'];
   fwrite($debug_log, "Current image path: $currentImage\n");

   $imagePath = $currentImage; // default to old image

   // Handle new image upload
   if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
      $targetDir = "uploads/branches/";
      if (!file_exists($targetDir)) {
         mkdir($targetDir, 0777, true);
      }

      $fileName = basename($_FILES["image"]["name"]);
      $fileName = preg_replace("/[^A-Za-z0-9._-]/", '', $fileName);
      $targetFile = $targetDir . uniqid() . "_" . $fileName;

      if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
         $imagePath = $targetFile;
         fwrite($debug_log, "New image uploaded to: $imagePath\n");

         // Delete old image file if exists and different
         if ($currentImage && file_exists($currentImage) && $currentImage !== $targetFile) {
            if (unlink($currentImage)) {
               fwrite($debug_log, "Old image deleted: $currentImage\n");
            } else {
               fwrite($debug_log, "Failed to delete old image: $currentImage\n");
            }
         }
      } else {
         echo json_encode(["success" => false, "error" => "Failed to move uploaded file."]);
         fwrite($debug_log, "Failed to move uploaded file\n");
         fclose($debug_log);
         exit;
      }
   }

   // Update the branch record
   $sql = "UPDATE branches SET name = :name, address = :address, contact = :contact, 
            manager = :manager, hours = :hours, image = :image WHERE id = :id";
   $stmt = $pdo->prepare($sql);

   if (!$stmt) {
      fwrite($debug_log, "Error preparing statement: " . print_r($pdo->errorInfo(), true) . "\n");
      echo json_encode(["success" => false, "error" => "Failed to prepare statement"]);
      fclose($debug_log);
      exit;
   }

   $result = $stmt->execute([
      ':name' => $name,
      ':address' => $address,
      ':contact' => $contact,
      ':manager' => $manager,
      ':hours' => $hours,
      ':image' => $imagePath,
      ':id' => $id
   ]);

   if ($result) {
      fwrite($debug_log, "Branch updated successfully\n");
      echo json_encode(["success" => true]);
   } else {
      fwrite($debug_log, "Failed to update branch. Error: " . print_r($stmt->errorInfo(), true) . "\n");
      echo json_encode(["success" => false, "error" => "Failed to update branch"]);
   }
} catch (PDOException $e) {
   fwrite($debug_log, "PDO Exception: " . $e->getMessage() . "\n");
   echo json_encode(["success" => false, "error" => $e->getMessage()]);
} catch (Exception $e) {
   fwrite($debug_log, "General Exception: " . $e->getMessage() . "\n");
   echo json_encode(["success" => false, "error" => "An unexpected error occurred"]);
}

fclose($debug_log);
