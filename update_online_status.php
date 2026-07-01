<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) exit();

$stmt = $pdo->prepare("
    UPDATE users 
    SET last_seen = NOW() 
    WHERE user_id = ?
");

$stmt->execute([$_SESSION["user_id"]]);
?>