<?php
session_start();
require_once "config/db.php";

header("Content-Type: application/json");

/* =========================
   AUTH CHECK
========================= */

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "status" => "error",
        "message" => "User not logged in"
    ]);
    exit();
}

$sender_id = (int) $_SESSION["user_id"];

/* =========================
   RECEIVE POST DATA
========================= */

$receiver_id = isset($_POST["receiver_id"]) ? (int) $_POST["receiver_id"] : 0;
$property_id = isset($_POST["property_id"]) ? (int) $_POST["property_id"] : 0;
$message     = isset($_POST["message"]) ? trim($_POST["message"]) : "";

/* =========================
   VALIDATION
========================= */

if ($receiver_id <= 0) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Receiver ID missing"
    ]);
    exit();
}

if ($receiver_id === $sender_id) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Cannot send message to yourself"
    ]);
    exit();
}

if ($message === "") {
    echo json_encode([
        "status"=>"error",
        "message"=>"Message cannot be empty"
    ]);
    exit();
}

if (strlen($message) > 1000) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Message too long"
    ]);
    exit();
}

/* =========================
   INSERT MESSAGE
========================= */

try {

$stmt = $pdo->prepare("
INSERT INTO messages
(property_id, sender_id, receiver_id, message, sent_at)
VALUES (?, ?, ?, ?, NOW())
");

$stmt->execute([
    $property_id,
    $sender_id,
    $receiver_id,
    $message
]);

echo json_encode([
    "status" => "success",
    "sender_id" => $sender_id,
    "receiver_id" => $receiver_id,
    "property_id" => $property_id
]);

} catch (PDOException $e) {

echo json_encode([
    "status" => "error",
    "message" => $e->getMessage()
]);

}
?>