<?php
session_start();
require_once "config/db.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$property_id = (int)$_POST["property_id"];
$rating = (int)$_POST["rating"];
$review = trim($_POST["review"]);
$client_id = $_SESSION["user_id"];

/* Prevent duplicate reviews */

$check = $pdo->prepare("
SELECT COUNT(*) 
FROM property_reviews
WHERE property_id=? AND client_id=?
");

$check->execute([$property_id,$client_id]);

if ($check->fetchColumn() > 0) {
    header("Location: property_details.php?id=".$property_id);
    exit();
}

/* Insert review */

$stmt = $pdo->prepare("
INSERT INTO property_reviews
(property_id,client_id,rating,review)
VALUES (?,?,?,?)
");

$stmt->execute([$property_id,$client_id,$rating,$review]);

header("Location: property_details.php?id=".$property_id);
exit();