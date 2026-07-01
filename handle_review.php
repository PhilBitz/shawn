<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "client") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$review_id = $_POST["review_id"] ?? "";
$rating = (int) ($_POST["rating"] ?? 0);
$review = trim($_POST["review"] ?? "");
$property_id = $_POST["property_id"] ?? 0; // Need to add this to form

if ($rating < 1 || $rating > 5 || empty($review)) {
    echo json_encode(["success" => false, "message" => "Invalid data"]);
    exit();
}

if (!empty($review_id)) {
    // Update existing review
    $stmt = $pdo->prepare("SELECT client_id FROM property_reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $reviewData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reviewData || $reviewData["client_id"] != $_SESSION["user_id"]) {
        echo json_encode(["success" => false, "message" => "Unauthorized"]);
        exit();
    }

    $updateStmt = $pdo->prepare("UPDATE property_reviews SET rating = ?, review = ?, updated_at = NOW() WHERE review_id = ?");
    $success = $updateStmt->execute([$rating, $review, $review_id]);
} else {
    // Add new review - need property_id
    if (!$property_id) {
        echo json_encode(["success" => false, "message" => "Property ID missing"]);
        exit();
    }

    // Check if already reviewed
    $checkStmt = $pdo->prepare("SELECT review_id FROM property_reviews WHERE property_id = ? AND client_id = ?");
    $checkStmt->execute([$property_id, $_SESSION["user_id"]]);
    if ($checkStmt->fetch()) {
        echo json_encode(["success" => false, "message" => "You have already reviewed this property"]);
        exit();
    }

    $insertStmt = $pdo->prepare("INSERT INTO property_reviews (property_id, client_id, rating, review, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    $success = $insertStmt->execute([$property_id, $_SESSION["user_id"], $rating, $review]);
}

echo json_encode(["success" => $success]);
?>