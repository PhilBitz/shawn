<?php
/**
 * StaySphere Property Listing System - Comprehensive Expansion
 * Adds all amenity, feature, and specification fields for residential, commercial, and land properties
 * Migration: 002_comprehensive_property_fields.sql executed via PHP
 */

session_start();
require_once "config/db.php";

echo "╔════════════════════════════════════════════════════════════════════╗\n";
echo "║  StaySphere Property Listing Expansion - Comprehensive Fields      ║\n";
echo "║  Adding Amenities, Features, and Specifications                    ║\n";
echo "╚════════════════════════════════════════════════════════════════════╝\n\n";

$errors = [];
$success_count = 0;

try {
    // === RESIDENTIAL AMENITY FIELDS ===
    $residential_fields = [
        // Utilities & Infrastructure
        ['water_supply', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Water supply available"'],
        ['electricity_availability', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Electricity available"'],
        ['water_quality', 'VARCHAR(50)', 'COMMENT "Water quality: bore, municipal, mixed"'],
        ['electricity_type', 'VARCHAR(50)', 'COMMENT "Power type: standard, 3-phase, generator"'],
        
        // Property Features
        ['property_size', 'DECIMAL(10,2)', 'COMMENT "Total property size in sqm"'],
        ['property_size_unit', 'VARCHAR(20)', 'DEFAULT "sqm" COMMENT "Size measurement unit"'],
        ['balcony', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Has balcony"'],
        ['garden', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Has garden/yard"'],
        ['fence', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Property fenced"'],
        ['kitchen_equipped', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Kitchen equipped"'],
        ['kitchen_type', 'VARCHAR(50)', 'COMMENT "Kitchen type: basic, equipped, shared"'],
        
        // Amenities
        ['wifi_available', 'TINYINT(1)', 'DEFAULT 0 COMMENT "WiFi available"'],
        ['air_conditioning', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Air conditioning"'],
        ['parking_available', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Parking available"'],
        ['generator', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Generator backup"'],
        ['swimming_pool', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Swimming pool"'],
        ['solar_power', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Solar power system"'],
        
        // Security & Access
        ['security_features', 'VARCHAR(255)', 'COMMENT "Security features: gate, guards, alarm, cctv"'],
        ['security_24_7', 'TINYINT(1)', 'DEFAULT 0 COMMENT "24/7 security available"'],
    ];
    
    // === COMMERCIAL AMENITY FIELDS ===
    $commercial_fields = [
        // Infrastructure & Utilities
        ['water_supply_commercial', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Water supply for commercial"'],
        ['electricity_commercial', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Electricity supply"'],
        ['electricity_phase', 'VARCHAR(50)', 'COMMENT "Electricity phase: single, 3-phase"'],
        ['internet_availability', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Internet available"'],
        ['internet_ready', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Ready for internet setup"'],
        
        // Commercial Features
        ['storage_area', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Storage area available"'],
        ['storage_size', 'DECIMAL(10,2)', 'COMMENT "Storage area size in sqm"'],
        ['floor_number', 'INT(11)', 'COMMENT "Floor number (0=ground)"'],
        ['loading_area', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Loading/unloading area"'],
        ['business_suitability', 'VARCHAR(255)', 'COMMENT "Suitable for: retail, office, warehouse, restaurant, etc"'],
        ['road_accessibility', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Accessible by road"'],
        
        // Security & Access
        ['cctv_installed', 'TINYINT(1)', 'DEFAULT 0 COMMENT "CCTV system installed"'],
        ['fire_safety', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Fire safety equipment"'],
        ['security_system', 'VARCHAR(255)', 'COMMENT "Security system type"'],
        
        // Amenities
        ['air_conditioning_commercial', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Air conditioning"'],
        ['generator_commercial', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Generator backup"'],
        ['elevator_access', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Elevator/lift available"'],
    ];
    
    // === LAND SPECIFIC FIELDS ===
    $land_fields = [
        // Land Characteristics
        ['topography', 'VARCHAR(100)', 'COMMENT "Topography: flat, sloped, mixed"'],
        ['water_access', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Water source accessible"'],
        ['water_type', 'VARCHAR(100)', 'COMMENT "Water type: river, bore, spring, municipal"'],
        ['electricity_access', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Electricity accessible"'],
        ['fenced_status', 'VARCHAR(50)', 'COMMENT "Fenced: yes, no, partial"'],
        
        // Documentation
        ['land_title', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Land title deed available"'],
        ['survey_plan', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Survey plan available"'],
        ['purchase_receipt', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Purchase receipt available"'],
        ['site_plan', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Site plan available"'],
        ['certificate_occupancy', 'TINYINT(1)', 'DEFAULT 0 COMMENT "Certificate of occupancy"'],
    ];
    
    // Combine all field definitions
    $all_fields = array_merge($residential_fields, $commercial_fields, $land_fields);
    
    echo "Starting migration - adding " . count($all_fields) . " new columns...\n\n";
    
    // Check which columns already exist
    $existing_stmt = $pdo->query("DESCRIBE properties");
    $existing_columns = [];
    while ($row = $existing_stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    // Add each field if it doesn't already exist
    foreach ($all_fields as $field) {
        $field_name = $field[0];
        $field_type = $field[1];
        $field_attrs = $field[2];
        
        if (in_array($field_name, $existing_columns)) {
            echo "⊘ Column '$field_name' already exists - skipping\n";
            continue;
        }
        
        try {
            $sql = "ALTER TABLE properties ADD COLUMN `$field_name` $field_type $field_attrs";
            $pdo->exec($sql);
            echo "✓ Added column: $field_name\n";
            $success_count++;
        } catch (Exception $e) {
            echo "✗ Failed to add $field_name: " . $e->getMessage() . "\n";
            $errors[] = "Failed to add $field_name: " . $e->getMessage();
        }
    }
    
    echo "\n";
    echo "╔════════════════════════════════════════════════════════════════════╗\n";
    echo "║                      MIGRATION COMPLETE                            ║\n";
    echo "╚════════════════════════════════════════════════════════════════════╝\n\n";
    
    echo "✅ Successfully added: $success_count new columns\n";
    
    if (count($errors) > 0) {
        echo "⚠️  Errors encountered: " . count($errors) . "\n";
        foreach ($errors as $error) {
            echo "  - $error\n";
        }
    }
    
    // Verify new schema
    echo "\n=== NEW SCHEMA SUMMARY ===\n\n";
    $verify_stmt = $pdo->query("DESCRIBE properties");
    $total_columns = 0;
    $residential_count = 0;
    $commercial_count = 0;
    $land_count = 0;
    
    while ($row = $verify_stmt->fetch(PDO::FETCH_ASSOC)) {
        $total_columns++;
        $field = $row['Field'];
        if (in_array($field, array_column($residential_fields, 0))) $residential_count++;
        if (in_array($field, array_column($commercial_fields, 0))) $commercial_count++;
        if (in_array($field, array_column($land_fields, 0))) $land_count++;
    }
    
    echo "Total properties table columns: $total_columns\n";
    echo "Residential-specific fields: $residential_count\n";
    echo "Commercial-specific fields: $commercial_count\n";
    echo "Land-specific fields: $land_count\n";
    echo "\n✅ Property listing expansion complete!\n";
    echo "The system now supports comprehensive fields for all property types.\n";
    
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}

unset($pdo);
?>
