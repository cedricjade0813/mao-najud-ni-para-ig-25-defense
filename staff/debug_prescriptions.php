<?php
require_once '../includes/db_connect.php';

echo "<h2>Prescription Database Debug</h2>";

try {
    // Check if prescriptions table exists
    echo "<h3>1. Checking if prescriptions table exists:</h3>";
    $tablesQuery = "SHOW TABLES LIKE 'prescriptions'";
    $tablesStmt = $db->prepare($tablesQuery);
    $tablesStmt->execute();
    $tables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($tables) > 0) {
        echo "✅ Prescriptions table exists<br>";
        
        // Check table structure
        echo "<h3>2. Table structure:</h3>";
        $structureQuery = "DESCRIBE prescriptions";
        $structureStmt = $db->prepare($structureQuery);
        $structureStmt->execute();
        $structure = $structureStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($structure as $field) {
            echo "<tr>";
            echo "<td>" . $field['Field'] . "</td>";
            echo "<td>" . $field['Type'] . "</td>";
            echo "<td>" . $field['Null'] . "</td>";
            echo "<td>" . $field['Key'] . "</td>";
            echo "<td>" . $field['Default'] . "</td>";
            echo "<td>" . $field['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check total count
        echo "<h3>3. Total records in prescriptions table:</h3>";
        $countQuery = "SELECT COUNT(*) as total FROM prescriptions";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute();
        $count = $countStmt->fetch(PDO::FETCH_ASSOC);
        echo "Total prescriptions: " . $count['total'] . "<br>";
        
        // Show sample data
        if ($count['total'] > 0) {
            echo "<h3>4. Sample data (first 5 records):</h3>";
            $sampleQuery = "SELECT * FROM prescriptions LIMIT 5";
            $sampleStmt = $db->prepare($sampleQuery);
            $sampleStmt->execute();
            $samples = $sampleStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1'>";
            if (count($samples) > 0) {
                // Header row
                echo "<tr>";
                foreach (array_keys($samples[0]) as $column) {
                    echo "<th>" . $column . "</th>";
                }
                echo "</tr>";
                
                // Data rows
                foreach ($samples as $row) {
                    echo "<tr>";
                    foreach ($row as $value) {
                        echo "<td>" . htmlspecialchars($value) . "</td>";
                    }
                    echo "</tr>";
                }
            }
            echo "</table>";
        }
        
        // Check for staff table (try different names)
        echo "<h3>5. Checking for staff table:</h3>";
        
        // Try different possible table names
        $possibleStaffTables = ['users', 'staff', 'admin', 'administrators', 'employees'];
        $staffTableFound = false;
        
        foreach ($possibleStaffTables as $tableName) {
            try {
                $staffQuery = "SELECT COUNT(*) as total FROM $tableName";
                $staffStmt = $db->prepare($staffQuery);
                $staffStmt->execute();
                $staffCount = $staffStmt->fetch(PDO::FETCH_ASSOC);
                echo "✅ Found table '$tableName' with " . $staffCount['total'] . " records<br>";
                $staffTableFound = true;
                
                // Show sample data
                if ($staffCount['total'] > 0) {
                    $staffSampleQuery = "SELECT * FROM $tableName LIMIT 3";
                    $staffSampleStmt = $db->prepare($staffSampleQuery);
                    $staffSampleStmt->execute();
                    $staffSamples = $staffSampleStmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    echo "<h4>Sample data from $tableName:</h4>";
                    echo "<table border='1'>";
                    if (count($staffSamples) > 0) {
                        // Header row
                        echo "<tr>";
                        foreach (array_keys($staffSamples[0]) as $column) {
                            echo "<th>" . $column . "</th>";
                        }
                        echo "</tr>";
                        
                        // Data rows
                        foreach ($staffSamples as $staff) {
                            echo "<tr>";
                            foreach ($staff as $value) {
                                echo "<td>" . htmlspecialchars($value) . "</td>";
                            }
                            echo "</tr>";
                        }
                    }
                    echo "</table>";
                }
                break;
            } catch (Exception $e) {
                echo "❌ Table '$tableName' not found<br>";
            }
        }
        
        if (!$staffTableFound) {
            echo "❌ No staff table found with common names<br>";
        }
        
    } else {
        echo "❌ Prescriptions table does not exist<br>";
        
        // Show all tables
        echo "<h3>Available tables:</h3>";
        $allTablesQuery = "SHOW TABLES";
        $allTablesStmt = $db->prepare($allTablesQuery);
        $allTablesStmt->execute();
        $allTables = $allTablesStmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo "<ul>";
        foreach ($allTables as $table) {
            echo "<li>" . $table . "</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
