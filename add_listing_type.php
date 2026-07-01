<?php
require_once "config/db.php";

try {
    // Check if column already exists
    $result = $pdo->query("SHOW COLUMNS FROM properties WHERE Field = 'listing_type'");
    if ($result->rowCount() > 0) {
        echo "✅ Column 'listing_type' already exists in properties table.";
        exit;
    }

    // Add listing_type column
    $pdo->exec("ALTER TABLE properties ADD COLUMN listing_type ENUM('rent', 'sale') NOT NULL DEFAULT 'rent' AFTER property_type");
    
    echo "✅ Migration completed successfully!<br>";
    echo "Added: listing_type ENUM('rent', 'sale') DEFAULT 'rent'<br>";
    echo "<br>Database updated! You can now use the rent/sale feature.";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
