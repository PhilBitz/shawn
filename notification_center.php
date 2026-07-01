<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    exit();
}

$user_id = $_SESSION["user_id"];
$role = $_SESSION["role"];

$response = [
    "messages" => 0,
    "bookings" => 0,
    "support" => 0,
    "appeals" => 0
];

/* =========================
   UNREAD MESSAGES
========================= */

$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM messages
WHERE receiver_id = ?
AND is_read = 0
");

$stmt->execute([$user_id]);
$response["messages"] = $stmt->fetchColumn();

/* =========================
   LANDLORD BOOKING REQUESTS
========================= */

if ($role == "landlord") {

$stmt = $pdo->prepare("
SELECT COUNT(*)
FROM bookings b
JOIN properties p ON b.property_id = p.property_id
WHERE p.landlord_id = ?
AND b.booking_status = 'pending'
");

$stmt->execute([$user_id]);
$response["bookings"] = $stmt->fetchColumn();

}

/* =========================
   SUPPORT MESSAGES FOR ADMIN
========================= */

if ($role == "admin") {

$stmt = $pdo->query("
SELECT COUNT(*)
FROM messages
WHERE property_id = 0
AND is_read = 0
");

$response["support"] = $stmt->fetchColumn();

/* =========================
   USER APPEALS
========================= */

$stmt = $pdo->query("
SELECT COUNT(*)
FROM appeals
WHERE status = 'pending'
");

$response["appeals"] = $stmt->fetchColumn();

}

echo json_encode($response);