<?php
include 'includes/db_connect.php';

echo "<h2>Checking imported_patients table passwords (clinic_management_system 9-1-latest)</h2>\n";

try {
    // Get all records from imported_patients table
    $stmt = $db->prepare('SELECT id, student_id, name, password FROM imported_patients LIMIT 10');
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>ID</th><th>Student ID</th><th>Name</th><th>Password</th><th>Is Hashed?</th></tr>\n";
    
    foreach ($patients as $patient) {
        $password = $patient['password'];
        $isHashed = password_get_info($password)['algo'] !== null;
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($patient['id']) . "</td>";
        echo "<td>" . htmlspecialchars($patient['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($patient['name']) . "</td>";
        echo "<td>" . htmlspecialchars($password) . "</td>";
        echo "<td>" . ($isHashed ? "YES (Hashed)" : "NO (Plain text)") . "</td>";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
    
    // Count hashed vs non-hashed passwords
    $allStmt = $db->prepare('SELECT password FROM imported_patients');
    $allStmt->execute();
    $allPasswords = $allStmt->fetchAll(PDO::FETCH_COLUMN);
    
    $hashedCount = 0;
    $plainCount = 0;
    
    foreach ($allPasswords as $password) {
        if (password_get_info($password)['algo'] !== null) {
            $hashedCount++;
        } else {
            $plainCount++;
        }
    }
    
    echo "<br><h3>Summary:</h3>\n";
    echo "Total records: " . count($allPasswords) . "<br>\n";
    echo "Hashed passwords: " . $hashedCount . "<br>\n";
    echo "Plain text passwords: " . $plainCount . "<br>\n";
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
