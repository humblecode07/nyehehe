<?php
include 'db.php'; // Make sure this defines $pdo (or adapt if $conn)

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

error_reporting(E_ALL);
ini_set('display_errors', 0); // Do not expose errors to client, but log them

// Handle OPTIONS preflight (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Setup debug log
$debug_log = fopen("edit_food_debug.log", "a");
fwrite($debug_log, "-------- " . date('Y-m-d H:i:s') . " --------\n");
fwrite($debug_log, "POST data: " . print_r($_POST, true) . "\n");
fwrite($debug_log, "FILES data: " . print_r($_FILES, true) . "\n");

// Check DB connection variable
global $pdo;
if (!isset($pdo) && isset($conn)) {
    $pdo = $conn;
}

$id = $_POST['id'] ?? null;
$name = $_POST['name'] ?? '';
$price = $_POST['price'] ?? '';
$category = $_POST['category'] ?? '';
$description = $_POST['description'] ?? '';

if (!$id) {
    echo json_encode(["success" => false, "error" => "Missing food item ID"]);
    fwrite($debug_log, "Error: Missing food item ID\n");
    fclose($debug_log);
    exit;
}

try {
    // Fetch current image path
    $stmtSelect = $pdo->prepare("SELECT image FROM food_items WHERE id = :id");
    $stmtSelect->bindParam(':id', $id, PDO::PARAM_INT);
    $stmtSelect->execute();

    if ($stmtSelect->rowCount() === 0) {
        echo json_encode(["success" => false, "error" => "Food item not found"]);
        fwrite($debug_log, "Error: Food item not found with ID $id\n");
        fclose($debug_log);
        exit;
    }

    $currentItem = $stmtSelect->fetch(PDO::FETCH_ASSOC);
    $currentImage = $currentItem['image'];
    fwrite($debug_log, "Current image path: $currentImage\n");

    $imagePath = $currentImage; // default to old image

    // Handle new image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = "uploads/food_items/";
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($_FILES["image"]["name"]);
        $fileName = preg_replace("/[^A-Za-z0-9._-]/", '', $fileName);
        $targetFile = $uploadDir . uniqid() . "_" . $fileName;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $targetFile)) {
            $imagePath = $targetFile;
            fwrite($debug_log, "New image uploaded to: $imagePath\n");

            // Delete old image if different
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

    // Prepare update statement
    $sql = "UPDATE food_items SET name = :name, price = :price, category = :category, description = :description, image = :image WHERE id = :id";
    $stmt = $pdo->prepare($sql);

    if (!$stmt) {
        fwrite($debug_log, "Error preparing statement: " . print_r($pdo->errorInfo(), true) . "\n");
        echo json_encode(["success" => false, "error" => "Failed to prepare statement"]);
        fclose($debug_log);
        exit;
    }

    $result = $stmt->execute([
        ':name' => $name,
        ':price' => $price,
        ':category' => $category,
        ':description' => $description,
        ':image' => $imagePath,
        ':id' => $id,
    ]);

    if ($result) {
        fwrite($debug_log, "Food item updated successfully\n");
        echo json_encode(["success" => true]);
    } else {
        fwrite($debug_log, "Failed to update food item: " . print_r($stmt->errorInfo(), true) . "\n");
        echo json_encode(["success" => false, "error" => "Failed to update food item"]);
    }
} catch (PDOException $e) {
    fwrite($debug_log, "PDO Exception: " . $e->getMessage() . "\n");
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
} catch (Exception $e) {
    fwrite($debug_log, "General Exception: " . $e->getMessage() . "\n");
    echo json_encode(["success" => false, "error" => "An unexpected error occurred"]);
}

fclose($debug_log);
