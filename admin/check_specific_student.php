<?php
include '../includes/db_connect.php';

echo "<h2>Check Specific Student ID</h2>";

// The student ID from your CSV
$student_id = 'SCC-22-00015340';

try {
    // Check if student exists
    $stmt = $db->prepare('SELECT COUNT(*) FROM imported_patients WHERE student_id = ?');
    $stmt->execute([$student_id]);
    $count = $stmt->fetchColumn();
    
    echo "<p><strong>Checking Student ID:</strong> $student_id</p>";
    echo "<p><strong>Exists in database:</strong> " . ($count > 0 ? "YES (DUPLICATE!)" : "NO") . "</p>";
    
    if ($count > 0) {
        echo "<p style='color: red;'><strong>❌ This is why your import failed!</strong></p>";
        echo "<p>The student ID '$student_id' already exists in the database, so it's being skipped as a duplicate.</p>";
        
        // Show the existing record
        $stmt = $db->prepare('SELECT * FROM imported_patients WHERE student_id = ?');
        $stmt->execute([$student_id]);
        $record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Existing Record in Database:</h3>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>Field</th><th>Value</th></tr>";
        foreach ($record as $key => $value) {
            echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
        }
        echo "</table>";
        
        echo "<h3>Solutions:</h3>";
        echo "<ol>";
        echo "<li><strong>Use a different Student ID:</strong> Change 'SCC-22-00015340' to something like 'SCC-22-00015341'</li>";
        echo "<li><strong>Delete the existing record:</strong> <a href='delete_student.php?id=$student_id' onclick='return confirm(\"Are you sure you want to delete this student?\")'>Delete this student</a></li>";
        echo "<li><strong>Update the existing record:</strong> Modify the existing student's information</li>";
        echo "</ol>";
        
    } else {
        echo "<p style='color: green;'><strong>✅ Student ID is available for import!</strong></p>";
        echo "<p>If the import is still failing, there might be another issue. Check the error logs.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>Database error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='import.php'>Back to Import Page</a></p>";
?>
