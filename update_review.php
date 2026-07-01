<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "client") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$review_id = $_POST["review_id"] ?? 0;
$property_id = (int) ($_POST["property_id"] ?? 0);
$rating = (int) ($_POST["rating"] ?? 0);
$review = trim($_POST["review"] ?? "");

if (!$review_id || $rating < 1 || $rating > 5 || empty($review)) {
    if (!empty($property_id)) {
        header("Location: property_details.php?id=" . $property_id);
        exit();
    }
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit();
}

// Check if the review belongs to the user
$stmt = $pdo->prepare("SELECT client_id FROM property_reviews WHERE review_id = ?");
$stmt->execute([$review_id]);
$reviewData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reviewData || $reviewData["client_id"] != $_SESSION["user_id"]) {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

// Update the review
$updateStmt = $pdo->prepare("UPDATE property_reviews SET rating = ?, review = ?, updated_at = NOW() WHERE review_id = ?");
$success = $updateStmt->execute([$rating, $review, $review_id]);

if (isset($_POST["redirect"]) && !empty($property_id)) {
    header("Location: property_details.php?id=" . $property_id);
    exit();
}

echo json_encode(["success" => $success]);
?>