<?php
/**
 * Property Types Migration Verification Script
 * Verify that all database changes were applied correctly
 */

session_start();
require_once "config/db.php";

// Color codes for terminal output
$green = "\033[92m";
$red = "\033[91m";
$yellow = "\033[93m";
$blue = "\033[94m";
$reset = "\033[0m";

echo $blue . "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║     StaySphere Property Types - Migration Verification             ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝" . $reset . "\n\n";

$checks_passed = 0;
$checks_failed = 0;

// Check 1: property_type column exists
echo "Checking database columns...\n";
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM properties LIKE 'property_type'");
    if ($stmt->fetch()) {
        echo $green . "✓ property_type column exists" . $reset . "\n";
        $checks_passed++;
    } else {
        echo $red . "✗ property_type column NOT found" . $reset . "\n";
        $checks_failed++;
    }
} catch (Exception $e) {
    echo $red . "✗ Error checking property_type: " . $e->getMessage() . $reset . "\n";
    $checks_failed++;
}

// Check 2: Land-specific fields
$land_fields = ['land_size', 'land_unit', 'land_purpose', 'land_road_access'];
foreach ($land_fields as $field) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM properties LIKE '$field'");
        if ($stmt->fetch()) {
            echo $green . "✓ $field column exists" . $reset . "\n";
            $checks_passed++;
        } else {
            echo $red . "✗ $field column NOT found" . $reset . "\n";
            $checks_failed++;
        }
    } catch (Exception $e) {
        echo $red . "✗ Error checking $field: " . $e->getMessage() . $reset . "\n";
        $checks_failed++;
    }
}

// Check 3: Commercial-specific fields
$commercial_fields = ['commercial_type', 'shop_size', 'shop_unit', 'office_size', 'office_unit'];
foreach ($commercial_fields as $field) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM properties LIKE '$field'");
        if ($stmt->fetch()) {
            echo $green . "✓ $field column exists" . $reset . "\n";
            $checks_passed++;
        } else {
            echo $red . "✗ $field column NOT found" . $reset . "\n";
            $checks_failed++;
        }
    } catch (Exception $e) {
        echo $red . "✗ Error checking $field: " . $e->getMessage() . $reset . "\n";
        $checks_failed++;
    }
}

echo "\n";

// Check 4: New categories added
echo "Checking new categories...\n";
$new_categories = [
    'Apartment', 'House', 'Studio', 'Commercial Shop', 'Commercial Office', 
    'Warehouse', 'Residential Land', 'Commercial Land', 'Farmland'
];

foreach ($new_categories as $category) {
    try {
        $stmt = $pdo->prepare("SELECT category_id FROM categories WHERE category_name = ?");
        $stmt->execute([$category]);
        if ($stmt->fetch()) {
            echo $green . "✓ Category '$category' exists" . $reset . "\n";
            $checks_passed++;
        } else {
            echo $yellow . "⚠ Category '$category' NOT found (may need manual insertion)" . $reset . "\n";
            $checks_failed++;
        }
    } catch (Exception $e) {
        echo $red . "✗ Error checking category: " . $e->getMessage() . $reset . "\n";
        $checks_failed++;
    }
}

echo "\n";

// Check 5: Indexes created
echo "Checking indexes...\n";
try {
    $stmt = $pdo->query("SHOW INDEX FROM properties WHERE Key_name = 'idx_property_type'");
    if ($stmt->fetch()) {
        echo $green . "✓ idx_property_type index exists" . $reset . "\n";
        $checks_passed++;
    } else {
        echo $yellow . "⚠ idx_property_type index NOT found" . $reset . "\n";
        $checks_failed++;
    }
} catch (Exception $e) {
    echo $red . "✗ Error checking idx_property_type: " . $e->getMessage() . $reset . "\n";
    $checks_failed++;
}

try {
    $stmt = $pdo->query("SHOW INDEX FROM properties WHERE Key_name = 'idx_land_purpose'");
    if ($stmt->fetch()) {
        echo $green . "✓ idx_land_purpose index exists" . $reset . "\n";
        $checks_passed++;
    } else {
        echo $yellow . "⚠ idx_land_purpose index NOT found" . $reset . "\n";
        $checks_failed++;
    }
} catch (Exception $e) {
    echo $red . "✗ Error checking idx_land_purpose: " . $e->getMessage() . $reset . "\n";
    $checks_failed++;
}

try {
    $stmt = $pdo->query("SHOW INDEX FROM properties WHERE Key_name = 'idx_commercial_type'");
    if ($stmt->fetch()) {
        echo $green . "✓ idx_commercial_type index exists" . $reset . "\n";
        $checks_passed++;
    } else {
        echo $yellow . "⚠ idx_commercial_type index NOT found" . $reset . "\n";
        $checks_failed++;
    }
} catch (Exception $e) {
    echo $red . "✗ Error checking idx_commercial_type: " . $e->getMessage() . $reset . "\n";
    $checks_failed++;
}

echo "\n";

// Check 6: Property type values in existing properties
echo "Checking existing properties...\n";
try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM properties");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $total = $result['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as typed FROM properties WHERE property_type IS NOT NULL");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $typed = $result['typed'];
    
    if ($typed > 0) {
        echo $green . "✓ $typed/$total properties have property_type set" . $reset . "\n";
        $checks_passed++;
    } else {
        echo $yellow . "⚠ No properties have property_type set (expected if newly migrated)" . $reset . "\n";
    }
} catch (Exception $e) {
    echo $red . "✗ Error checking properties: " . $e->getMessage() . $reset . "\n";
    $checks_failed++;
}

echo "\n";

// Check 7: Helper functions file exists
echo "Checking helper files...\n";
if (file_exists(__DIR__ . "/includes/property_helpers.php")) {
    echo $green . "✓ property_helpers.php exists" . $reset . "\n";
    $checks_passed++;
} else {
    echo $red . "✗ property_helpers.php NOT found" . $reset . "\n";
    $checks_failed++;
}

// Check 8: Test helper function
try {
    require_once "includes/property_helpers.php";
    
    if (function_exists('getPropertyTypeLabel')) {
        $label = getPropertyTypeLabel('residential');
        if (strpos($label, 'Residential') !== false) {
            echo $green . "✓ getPropertyTypeLabel() function works" . $reset . "\n";
            $checks_passed++;
        } else {
            echo $red . "✗ getPropertyTypeLabel() returned unexpected value" . $reset . "\n";
            $checks_failed++;
        }
    } else {
        echo $red . "✗ getPropertyTypeLabel() function not found" . $reset . "\n";
        $checks_failed++;
    }
} catch (Exception $e) {
    echo $yellow . "⚠ Could not test helper functions: " . $e->getMessage() . $reset . "\n";
}

echo "\n";

// Summary
echo $blue . "╔════════════════════════════════════════════════════════════════════╗" . $reset . "\n";
echo "║                         VERIFICATION SUMMARY                        ║\n";
echo $blue . "╚════════════════════════════════════════════════════════════════════╝" . $reset . "\n\n";

$total_checks = $checks_passed + $checks_failed;
$percentage = round(($checks_passed / $total_checks) * 100, 1);

echo "Checks Passed: " . $green . $checks_passed . $reset . "\n";
echo "Checks Failed: " . $red . $checks_failed . $reset . "\n";
echo "Success Rate: " . ($percentage >= 90 ? $green : ($percentage >= 70 ? $yellow : $red)) . $percentage . "%" . $reset . "\n\n";

if ($checks_failed === 0) {
    echo $green . "✓ All checks passed! Migration appears to be successful." . $reset . "\n";
    echo $green . "The property types system is ready for use!" . $reset . "\n";
} else {
    echo $red . "✗ Some checks failed. Please review the issues above." . $reset . "\n";
    echo $red . "Migration may not be complete or may need manual intervention." . $reset . "\n";
}

echo "\n" . $blue . "Next Steps:" . $reset . "\n";
echo "1. Test creating a property with each type (residential, commercial, land)\n";
echo "2. Verify filters appear on the discovery page\n";
echo "3. Check property cards display correct information\n";
echo "4. Review property details page for each type\n";
echo "5. See PROPERTY_TYPES_IMPLEMENTATION.md for detailed testing guide\n";
echo "\n";

// Cleanup function
unset($pdo);
?>
