<?php
require_once "config/db.php";

$user_id = $_GET["user_id"];

$stmt = $pdo->prepare("
    SELECT last_seen 
    FROM users 
    WHERE user_id = ?
");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user["last_seen"]) {
    $last_seen = strtotime($user["last_seen"]);
    if (time() - $last_seen <= 10) {
        echo "online";
    } else {
        echo "offline";
    }
}
?>