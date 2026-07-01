<?php
require_once 'config/db.php';

$stmt = $pdo->query("DESCRIBE properties");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\n=== CURRENT PROPERTIES TABLE SCHEMA ===\n\n";
foreach($columns as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ")\n";
}
echo "\nTotal columns: " . count($columns) . "\n";
?>
