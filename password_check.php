<!DOCTYPE html>
<html>
<head>
    <title>Password Check - Clinic Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .hashed { color: green; font-weight: bold; }
        .plain { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Checking imported_patients table passwords (clinic_management_system 9-1-latest)</h2>

<?php
include 'includes/db_connect.php';

try {
    // Get all records from imported_patients table
    $stmt = $db->prepare('SELECT id, student_id, name, password FROM imported_patients LIMIT 10');
    $stmt->execute();
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table>";
    echo "<tr><th>ID</th><th>Student ID</th><th>Name</th><th>Password</th><th>Is Hashed?</th></tr>";
    
    foreach ($patients as $patient) {
        $password = $patient['password'];
        $isHashed = password_get_info($password)['algo'] !== null;
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($patient['id']) . "</td>";
        echo "<td>" . htmlspecialchars($patient['student_id']) . "</td>";
        echo "<td>" . htmlspecialchars($patient['name']) . "</td>";
        echo "<td>" . htmlspecialchars($password) . "</td>";
        echo "<td class='" . ($isHashed ? "hashed" : "plain") . "'>" . ($isHashed ? "YES (Hashed)" : "NO (Plain text)") . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
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
    
    echo "<br><h3>Summary:</h3>";
    echo "Total records: " . count($allPasswords) . "<br>";
    echo "Hashed passwords: <span class='hashed'>" . $hashedCount . "</span><br>";
    echo "Plain text passwords: <span class='plain'>" . $plainCount . "</span><br>";
    
    if ($plainCount > 0) {
        echo "<br><strong style='color: red;'>⚠️ ISSUE FOUND:</strong> You have " . $plainCount . " plain text passwords, but your login code expects hashed passwords!<br>";
        echo "<a href='password_fix.php' style='background: #4F46E5; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Fix Passwords Now</a>";
    } else {
        echo "<br><strong style='color: green;'>✅ All passwords are properly hashed!</strong>";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>

</body>
</html>
