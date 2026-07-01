<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) exit();

$user_id = $_SESSION["user_id"];
$property_id = $_POST["property_id"];
$is_typing = $_POST["is_typing"] ? 1 : 0;

// Insert or update typing status
$stmt = $pdo->prepare("
    INSERT INTO typing_status (property_id, user_id, is_typing)
    VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE 
        is_typing = VALUES(is_typing),
        updated_at = CURRENT_TIMESTAMP
");

$stmt->execute([$property_id, $user_id, $is_typing]);
?>