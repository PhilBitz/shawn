<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) exit();

$current_user = $_SESSION["user_id"];
$property_id = isset($_GET["property_id"]) ? (int)$_GET["property_id"] : 0;
$receiver_id = isset($_GET["receiver_id"]) ? (int)$_GET["receiver_id"] : 0;

if (!$receiver_id) exit();

$stmt = $pdo->prepare("
    SELECT is_typing, updated_at
    FROM typing_status
    WHERE property_id = ?
    AND user_id = ?
");

$stmt->execute([$property_id, $receiver_id]);
$data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($data && $data["is_typing"] == 1) {
    // Check if last update within 5 seconds
    $last_update = strtotime($data["updated_at"]);
    if (time() - $last_update <= 5) {
        echo "typing";
    }
}
?>
