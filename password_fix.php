<!DOCTYPE html>
<html>
<head>
    <title>Password Fix - Clinic Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .info { color: blue; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Fixing imported_patients table passwords (clinic_management_system 9-1-latest)</h2>

<?php
include 'includes/db_connect.php';

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
    
    echo "<h3>Results:</h3>";
    echo "Passwords updated: <span class='success'>" . $updatedCount . "</span><br>";
    echo "Passwords already hashed (skipped): <span class='info'>" . $skippedCount . "</span><br>";
    echo "Total records processed: " . ($updatedCount + $skippedCount) . "<br>";
    
    if ($updatedCount > 0) {
        echo "<br><strong class='success'>✅ Password hashing completed successfully!</strong><br>";
        echo "All plain text passwords have been converted to secure hashes.<br>";
        echo "<br><strong>What was fixed:</strong><br>";
        echo "• Your login system uses <code>password_verify()</code> which expects hashed passwords<br>";
        echo "• Your database had plain text passwords (like 'Abella', 'Abellana', etc.)<br>";
        echo "• Now all passwords are properly hashed and students can log in<br>";
    } else {
        echo "<br><strong class='info'>ℹ️ No passwords needed updating - all are already hashed.</strong><br>";
    }
    
    echo "<br><a href='password_check.php' style='background: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Check Passwords Again</a>";
    echo " <a href='index.php' style='background: #10B981; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;'>Go to Login Page</a>";
    
} catch (PDOException $e) {
    echo "<span class='error'>Database error: " . $e->getMessage() . "</span>";
}
?>

</body>
</html>
