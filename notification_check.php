<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) exit();

$user_id = $_SESSION["user_id"];

// Get latest unread message
$stmt = $pdo->prepare("
    SELECT m.message, m.property_id, u.full_name, p.title
    FROM messages m
    JOIN users u ON m.sender_id = u.user_id
    JOIN properties p ON m.property_id = p.property_id
    WHERE m.receiver_id = ?
    AND m.is_read = 0
    ORDER BY m.sent_at DESC
    LIMIT 1
");

$stmt->execute([$user_id]);
$message = $stmt->fetch(PDO::FETCH_ASSOC);

if ($message) {
    echo json_encode([
        "sender" => $message["full_name"],
        "preview" => substr($message["message"], 0, 50),
        "property_id" => $message["property_id"],
        "property_title" => $message["title"]
    ]);
} else {
    echo json_encode(null);
}
?>