<?php
session_start();

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://localhost:5173");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

// Handle preflight OPTIONS request
if ($_SERVER["REQUEST_METHOD"] === "OPTIONS") {
   http_response_code(200);
   exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
   $username = $_POST['username'] ?? '';
   $password = $_POST['password'] ?? '';
   $remember = isset($_POST['remember']);

   if (!empty($username) && !empty($password)) {
      $_SESSION['username'] = $username;

      if ($remember) {
         setcookie("remembered_username", $username, time() + (30 * 24 * 60 * 60), "/");
      } else {
         setcookie("remembered_username", "", time() - 3600, "/");
      }

      echo json_encode([
         "success" => true,
         "redirect" => "http://localhost:5173/admin/contact-messages"
      ]);
      exit;
   } else {
      echo json_encode(["success" => false, "message" => "Missing username or password"]);
      exit;
   }
}
