<?php
include '../includes/db_connect.php';

echo "<h2>Database Import Test</h2>";

try {
    // Test database connection
    echo "<p>✅ Database connection successful</p>";
    
    // Check if table exists
    $stmt = $db->query("SHOW TABLES LIKE 'imported_patients'");
    if ($stmt->rowCount() > 0) {
        echo "<p>✅ imported_patients table exists</p>";
        
        // Check table structure
        $stmt = $db->query("DESCRIBE imported_patients");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>✅ Table structure:</p><ul>";
        foreach ($columns as $column) {
            echo "<li>{$column['Field']} - {$column['Type']}</li>";
        }
        echo "</ul>";
        
        // Test insert
        $testData = [
            'student_id' => 'TEST-' . time(),
            'name' => 'Test User',
            'dob' => '1/1/2000',
            'gender' => 'Male',
            'address' => 'Test Address',
            'civil_status' => 'Single',
            'password' => password_hash('testpass', PASSWORD_DEFAULT),
            'year_level' => '1st Year'
        ];
        
        $stmt = $db->prepare('INSERT INTO imported_patients (student_id, name, dob, gender, address, civil_status, password, year_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
        $result = $stmt->execute([
            $testData['student_id'],
            $testData['name'],
            $testData['dob'],
            $testData['gender'],
            $testData['address'],
            $testData['civil_status'],
            $testData['password'],
            $testData['year_level']
        ]);
        
        if ($result) {
            echo "<p>✅ Test insert successful</p>";
            
            // Clean up test data
            $stmt = $db->prepare('DELETE FROM imported_patients WHERE student_id = ?');
            $stmt->execute([$testData['student_id']]);
            echo "<p>✅ Test data cleaned up</p>";
        } else {
            echo "<p>❌ Test insert failed</p>";
        }
        
    } else {
        echo "<p>❌ imported_patients table does not exist</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='import.php'>Back to Import Page</a></p>";
?>
