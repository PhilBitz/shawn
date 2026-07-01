<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once "config/db.php";

echo "Testing database connection...<br>";
echo "PDO available: " . (class_exists('PDO') ? 'YES' : 'NO') . "<br>";

try {
    // Quick test
    $result = $pdo->query("SELECT COUNT(*) FROM properties LIMIT 1");
    echo "✅ Database connection successful<br>";
    
    // Try the migration
    echo "<br>Attempting to add listing_type column...<br>";
    
    $pdo->exec("ALTER TABLE properties ADD COLUMN listing_type ENUM('rent', 'sale') NOT NULL DEFAULT 'rent' AFTER property_type");
    echo "✅ Column added!";
    
} catch (Exception $e) {
    $msg = $e->getMessage();
    echo "Response: " . htmlspecialchars($msg) . "<br>";
    
    if (strpos($msg, 'Duplicate') !== false || strpos($msg, 'exists') !== false) {
        echo "✅ Column already exists (that's OK)";
    }
}
?>
