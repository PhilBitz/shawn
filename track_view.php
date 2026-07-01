<?php
session_start();
require_once "config/db.php";

$property_id = (int) ($_POST["property_id"] ?? $_GET["property_id"] ?? 0);

if (!$property_id) {
    echo json_encode(["success" => false]);
    exit();
}

$user_id = isset($_SESSION["user_id"]) ? (int) $_SESSION["user_id"] : null;

// Only track if user is logged in (to ensure unique views)
if ($user_id) {
    $stmt = $pdo->prepare("
        INSERT INTO property_views (property_id, user_id) 
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE viewed_at = NOW()
    ");
    $stmt->execute([$property_id, $user_id]);
}

echo json_encode(["success" => true]);
?>