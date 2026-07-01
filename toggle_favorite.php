<?php
session_start();
require_once "config/db.php";

header("Content-Type: application/json");

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "client") {
    echo json_encode([
        "status" => "error",
        "message" => "Unauthorized"
    ]);
    exit();
}

$user_id = (int) $_SESSION["user_id"];
$property_id = isset($_POST["property_id"]) ? (int) $_POST["property_id"] : 0;

if ($property_id <= 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid property"
    ]);
    exit();
}

/* Check if already favorited */
$stmt = $pdo->prepare("
    SELECT favorite_id
    FROM favorites
    WHERE user_id = ? AND property_id = ?
    LIMIT 1
");
$stmt->execute([$user_id, $property_id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);

if ($existing) {
    $stmt = $pdo->prepare("
        DELETE FROM favorites
        WHERE user_id = ? AND property_id = ?
    ");
    $stmt->execute([$user_id, $property_id]);

    echo json_encode([
        "status" => "removed"
    ]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO favorites (user_id, property_id)
        VALUES (?, ?)
    ");
    $stmt->execute([$user_id, $property_id]);

    echo json_encode([
        "status" => "added"
    ]);
}
?>