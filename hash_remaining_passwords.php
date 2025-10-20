<?php
// Script to hash remaining plain text passwords in imported_patients table
require_once 'includes/db_connect.php';

echo "<h2>Password Hashing Script</h2>\n";
echo "<p>Checking and hashing plain text passwords in imported_patients table...</p>\n";

try {
    // Get all patients with their passwords
    $stmt = $db->prepare('SELECT id, name, password FROM imported_patients');
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $totalPatients = count($patients);
    $hashedCount = 0;
    $alreadyHashedCount = 0;
    $plainTextCount = 0;
    
    echo "<p>Found {$totalPatients} patients in the database.</p>\n";
    echo "<hr>\n";
    
    foreach ($patients as $patient) {
        $id = $patient['id'];
        $name = $patient['name'];
        $password = $patient['password'];
        
        // Check if password is already hashed
        $passwordInfo = password_get_info($password);
        
        if ($passwordInfo['algo'] === null) {
            // Password is plain text - hash it
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Update the database
            $updateStmt = $db->prepare('UPDATE imported_patients SET password = ? WHERE id = ?');
            $updateStmt->execute([$hashedPassword, $id]);
            
            echo "<p>✅ <strong>ID {$id} ({$name})</strong>: Plain text password '{$password}' → Hashed</p>\n";
            $hashedCount++;
            $plainTextCount++;
        } else {
            // Password is already hashed
            echo "<p>🔒 <strong>ID {$id} ({$name})</strong>: Password already hashed (algorithm: {$passwordInfo['algo']})</p>\n";
            $alreadyHashedCount++;
        }
    }
    
    echo "<hr>\n";
    echo "<h3>Summary:</h3>\n";
    echo "<ul>\n";
    echo "<li><strong>Total patients:</strong> {$totalPatients}</li>\n";
    echo "<li><strong>Already hashed:</strong> {$alreadyHashedCount}</li>\n";
    echo "<li><strong>Plain text found:</strong> {$plainTextCount}</li>\n";
    echo "<li><strong>Newly hashed:</strong> {$hashedCount}</li>\n";
    echo "</ul>\n";
    
    if ($hashedCount > 0) {
        echo "<p style='color: green; font-weight: bold;'>✅ Successfully hashed {$hashedCount} plain text passwords!</p>\n";
    } else {
        echo "<p style='color: blue; font-weight: bold;'>ℹ️ All passwords are already hashed. No changes needed.</p>\n";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<p><strong>Security Note:</strong> All passwords are now securely hashed using PHP's password_hash() function with PASSWORD_DEFAULT (bcrypt).</p>\n";
echo "<p><strong>Next Steps:</strong> You can now safely delete this script file as it's no longer needed.</p>\n";
?>
