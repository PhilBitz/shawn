<?php
require_once "config/db.php";

try {
    // Try to add the column if it doesn't exist
    $pdo->exec("ALTER TABLE properties ADD COLUMN listing_type ENUM('rent', 'sale') NOT NULL DEFAULT 'rent' AFTER property_type");
    echo "✅ Column 'listing_type' added successfully!";
    
} catch (PDOException $e) {
    // If column already exists, that's fine
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "✅ Column 'listing_type' already exists - No changes needed";
    } else {
        echo "❌ Error: " . $e->getMessage();
    }
}
?>
