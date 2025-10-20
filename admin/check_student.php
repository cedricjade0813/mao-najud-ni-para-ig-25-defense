<?php
include '../includes/db_connect.php';

echo "<h2>Check Student ID in Database</h2>";

$student_id = 'SCC-22-00015340'; // The student ID from your CSV

try {
    // Check if student exists
    $stmt = $db->prepare('SELECT COUNT(*) FROM imported_patients WHERE student_id = ?');
    $stmt->execute([$student_id]);
    $count = $stmt->fetchColumn();
    
    echo "<p><strong>Student ID:</strong> $student_id</p>";
    echo "<p><strong>Exists in database:</strong> " . ($count > 0 ? "YES" : "NO") . "</p>";
    
    if ($count > 0) {
        echo "<p><strong>This is why the import failed - the student ID already exists!</strong></p>";
        
        // Show the existing record
        $stmt = $db->prepare('SELECT * FROM imported_patients WHERE student_id = ?');
        $stmt->execute([$student_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Existing Record:</h3>";
        echo "<table border='1' cellpadding='5'>";
        foreach ($record as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p><strong>Student ID is available for import.</strong></p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='import.php'>Back to Import Page</a></p>";
?>
