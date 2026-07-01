<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"]) || $_SESSION["role"] !== "landlord") {
    echo json_encode(["success" => false, "message" => "Unauthorized"]);
    exit();
}

$property_id = (int) $_POST["property_id"] ?? 0;

if (!$property_id) {
    echo json_encode(["success" => false, "message" => "Property ID missing"]);
    exit();
}

// Check if property belongs to landlord
$stmt = $pdo->prepare("SELECT property_id, verification_status FROM properties WHERE property_id = ? AND landlord_id = ?");
$stmt->execute([$property_id, $_SESSION["user_id"]]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    echo json_encode(["success" => false, "message" => "Property not found"]);
    exit();
}

// Check if already requested or approved
if ($property["verification_status"] === "pending") {
    echo json_encode(["success" => false, "message" => "Verification already requested"]);
    exit();
}

if ($property["verification_status"] === "approved") {
    echo json_encode(["success" => false, "message" => "Property already verified"]);
    exit();
}

// Request verification
$updateStmt = $pdo->prepare("UPDATE properties SET verification_status = 'pending', verification_requested_at = NOW() WHERE property_id = ?");
$success = $updateStmt->execute([$property_id]);

echo json_encode(["success" => $success, "message" => $success ? "Verification requested successfully" : "Failed to request verification"]);
?>