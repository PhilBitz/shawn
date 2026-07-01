<?php
require_once "config/db.php";

try {
    // Check columns in properties table
    $result = $pdo->query("DESCRIBE properties");
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "✅ Properties Table Schema Check:<br><br>";
    
    $found_listing_type = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'listing_type') {
            $found_listing_type = true;
            echo "<strong style='color: green;'>✓ listing_type column exists</strong><br>";
            echo "Type: " . $col['Type'] . "<br>";
            echo "Null: " . ($col['Null'] === 'YES' ? 'YES' : 'NO') . "<br>";
            echo "Default: " . ($col['Default'] ?? 'None') . "<br><br>";
        }
    }
    
    if (!$found_listing_type) {
        echo "❌ listing_type column NOT found<br>";
        echo "Total columns: " . count($columns) . "<br><br>";
        echo "First 5 columns:<br>";
        for ($i = 0; $i < 5 && $i < count($columns); $i++) {
            echo "- " . $columns[$i]['Field'] . " (" . $columns[$i]['Type'] . ")<br>";
        }
    } else {
        echo "✅ System Ready!<br>";
        echo "You can now:<br>";
        echo "1. Add properties with listing purpose<br>";
        echo "2. Search by 'For Rent' or 'For Sale'<br>";
        echo "3. See correct pricing displays<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
