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

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Validate request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
   http_response_code(405);
   echo json_encode(['success' => false, 'error' => 'Invalid request method']);
   exit;
}

// Log the incoming data for debugging
$debug_log = fopen("food_debug.log", "a");
fwrite($debug_log, "-------- " . date('Y-m-d H:i:s') . " --------\n");
fwrite($debug_log, "POST data: " . print_r($_POST, true) . "\n");
fwrite($debug_log, "FILES data: " . print_r($_FILES, true) . "\n");

// Check for required fields
$required_fields = ['name', 'price', 'category'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'error' => ucfirst($field) . ' is required']);
        fwrite($debug_log, "Error: $field is required\n");
        fclose($debug_log);
        exit;
    }
}

// Get form data
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? '';
$category = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';

// Handle image upload
$imagePath = '';
if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
   $targetDir = "uploads/food_items/";
   if (!file_exists($targetDir)) {
      mkdir($targetDir, 0777, true);
   }

   // Validate file type
   $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
   $fileType = $_FILES['image']['type'];
   
   if (!in_array($fileType, $allowedTypes)) {
      echo json_encode(["success" => false, "error" => "Invalid file type. Only JPEG, PNG, GIF, and WebP are allowed."]);
      fwrite($debug_log, "Invalid file type: $fileType\n");
      fclose($debug_log);
      exit;
   }

   // Validate file size (5MB max)
   $maxSize = 5 * 1024 * 1024; // 5MB
   if ($_FILES['image']['size'] > $maxSize) {
      echo json_encode(["success" => false, "error" => "File too large. Maximum size is 5MB."]);
      fwrite($debug_log, "File too large: " . $_FILES['image']['size'] . " bytes\n");
      fclose($debug_log);
      exit;
   }

   $fileName = basename($_FILES["image"]["name"]);
   $fileName = preg_replace("/[^A-Za-z0-9._-]/", '', $fileName);
   $ext = pathinfo($fileName, PATHINFO_EXTENSION);
   $targetFile = $targetDir . uniqid() . "_" . time() . "." . $ext;

   if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
      $imagePath = $targetFile;
      fwrite($debug_log, "Image uploaded successfully to: $targetFile\n");
   } else {
      echo json_encode(["success" => false, "error" => "Failed to move uploaded file."]);
      fwrite($debug_log, "Failed to move uploaded file\n");
      fclose($debug_log);
      exit;
   }
} elseif (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
   // Handle upload errors
   $upload_errors = [
      UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
      UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
      UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
      UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
      UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
      UPLOAD_ERR_EXTENSION => 'File upload stopped by extension'
   ];
   
   $error = $upload_errors[$_FILES['image']['error']] ?? 'Unknown upload error';
   echo json_encode(["success" => false, "error" => $error]);
   fwrite($debug_log, "Upload error: $error\n");
   fclose($debug_log);
   exit;
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

   // Using named parameters for better security and readability
   $stmt = $pdo->prepare("INSERT INTO food_items (name, price, category, description, image) 
             VALUES (:name, :price, :category, :description, :image)");

   $result = $stmt->execute([
      ':name' => $name,
      ':price' => $price,
      ':category' => $category,
      ':description' => $description,
      ':image' => $imagePath
   ]);

   if ($result) {
      $lastId = $pdo->lastInsertId();
      fwrite($debug_log, "Database insertion successful. New ID: $lastId\n");
      echo json_encode([
         "success" => true, 
         "id" => $lastId,
         "message" => "Food item added successfully"
      ]);
   } else {
      fwrite($debug_log, "Database insertion failed. Error info: " . print_r($stmt->errorInfo(), true) . "\n");
      echo json_encode(["success" => false, "error" => "Failed to insert record"]);
   }
} catch (PDOException $e) {
   fwrite($debug_log, "PDO Exception: " . $e->getMessage() . "\n");
   echo json_encode(["success" => false, "error" => "Database error: " . $e->getMessage()]);
} catch (Exception $e) {
   fwrite($debug_log, "General Exception: " . $e->getMessage() . "\n");
   echo json_encode(["success" => false, "error" => "An unexpected error occurred"]);
}

fclose($debug_log);
?>