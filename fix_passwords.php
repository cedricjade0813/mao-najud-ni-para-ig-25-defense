<?php
include 'includes/db_connect.php';

echo "<h2>Fixing imported_patients table passwords (clinic_management_system 9-1-latest)</h2>\n";

try {
    // Get all records with plain text passwords
    $stmt = $db->prepare('SELECT id, password FROM imported_patients');
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updatedCount = 0;
    $skippedCount = 0;
    
    foreach ($patients as $patient) {
        $password = $patient['password'];
        $id = $patient['id'];
        
        // Check if password is already hashed
        if (password_get_info($password)['algo'] !== null) {
            $skippedCount++;
            continue;
        }
        
        // Hash the plain text password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Update the record
        $updateStmt = $db->prepare('UPDATE imported_patients SET password = ? WHERE id = ?');
        $updateStmt->execute([$hashedPassword, $id]);
        
        $updatedCount++;
    }
    
    echo "<h3>Results:</h3>\n";
    echo "Passwords updated: " . $updatedCount . "<br>\n";
    echo "Passwords already hashed (skipped): " . $skippedCount . "<br>\n";
    echo "Total records processed: " . ($updatedCount + $skippedCount) . "<br>\n";
    
    if ($updatedCount > 0) {
        echo "<br><strong>✅ Password hashing completed successfully!</strong><br>\n";
        echo "All plain text passwords have been converted to secure hashes.<br>\n";
    } else {
        echo "<br><strong>ℹ️ No passwords needed updating - all are already hashed.</strong><br>\n";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>
