<?php
session_start();

header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
   http_response_code(200);
   exit;
}

$_SESSION = [];
session_destroy();

if (isset($_COOKIE['remembered_username'])) {
   setcookie('remembered_username', '', time() - 3600, '/');
}

echo json_encode([
   "success" => true,
   "message" => "Logged out successfully"
]);
exit();
