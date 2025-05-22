<?php
include 'db.php'; // This should set up $pdo as your PDO instance

// Set headers first, before any output
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// For debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to client, but log them

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405);
   echo json_encode(['success' => false, 'error' => 'Invalid request method']);
   exit;
}

// Log the incoming data for debugging
$debug_log = fopen("branch_debug.log", "a");
fwrite($debug_log, "-------- " . date('Y-m-d H:i:s') . " --------\n");
fwrite($debug_log, "POST data: " . print_r($_POST, true) . "\n");
fwrite($debug_log, "FILES data: " . print_r($_FILES, true) . "\n");

// Check for required field 'name'
if (!isset($_POST['name']) || empty($_POST['name'])) {
   echo json_encode(['success' => false, 'error' => 'Branch name is required']);
   fwrite($debug_log, "Error: Branch name is required\n");
   fclose($debug_log);
   exit;
}

// Get form data
$name = $_POST['name'] ?? '';
$address = $_POST['address'] ?? '';
$contact = $_POST['contact'] ?? '';
$manager = $_POST['manager'] ?? '';
$hours = $_POST['hours'] ?? '';

// Handle image upload
$imagePath = '';
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
      fwrite($debug_log, "Image uploaded successfully to: $targetFile\n");
   } else {
      echo json_encode(["success" => false, "error" => "Failed to move uploaded file."]);
      fwrite($debug_log, "Failed to move uploaded file\n");
      fclose($debug_log);
      exit;
   }
}

try {
   // Make sure we're using the same variable name as in db.php
   // Your db.php might be using $pdo instead of $conn
   global $pdo; // Try to access the PDO connection if it's named $pdo

   // If your db.php uses $conn instead of $pdo, use this:
   if (!isset($pdo) && isset($conn)) {
      $pdo = $conn;
   }

   // Log which connection we're using
   fwrite($debug_log, "PDO connection variable: " . (isset($pdo) ? "pdo exists" : "pdo missing") .
      (isset($conn) ? ", conn exists" : ", conn missing") . "\n");

   // Using the approach from your working SELECT query
   $stmt = $pdo->prepare("INSERT INTO branches (name, address, contact, manager, hours, image) 
             VALUES (:name, :address, :contact, :manager, :hours, :image)");

   $result = $stmt->execute([
      ':name' => $name,
      ':address' => $address,
      ':contact' => $contact,
      ':manager' => $manager,
      ':hours' => $hours,
      ':image' => $imagePath
   ]);

   if ($result) {
      fwrite($debug_log, "Database insertion successful\n");
      echo json_encode(["success" => true]);
   } else {
      fwrite($debug_log, "Database insertion failed. Error info: " . print_r($stmt->errorInfo(), true) . "\n");
      echo json_encode(["success" => false, "error" => "Failed to insert record"]);
   }
} catch (PDOException $e) {
   fwrite($debug_log, "PDO Exception: " . $e->getMessage() . "\n");
   echo json_encode(["success" => false, "error" => $e->getMessage()]);
} catch (Exception $e) {
   fwrite($debug_log, "General Exception: " . $e->getMessage() . "\n");
   echo json_encode(["success" => false, "error" => "An unexpected error occurred"]);
}

fclose($debug_log);
