<?php
// Execute migration
require_once 'config/db.php';

$migrationFile = 'migrations/001_add_property_types.sql';
$sql = file_get_contents($migrationFile);

try {
    // Split multiple statements and execute them
    $statements = array_filter(array_map('trim', preg_split('/;(?=\s*(--|\/\/|#|\/\*)|\s*$)/m', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $pdo->exec($statement);
            echo "✓ Executed: " . substr($statement, 0, 60) . "...\n";
        }
    }
    
    echo "\n✅ Migration completed successfully!\n";
} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
    exit(1);
}
?>
