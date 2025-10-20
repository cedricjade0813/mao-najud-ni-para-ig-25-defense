<?php
require_once '../includes/db_connect.php';

echo "<h2>Prescription Test</h2>";

try {
    // Test 1: Simple query without join
    echo "<h3>1. Simple prescription query (no join):</h3>";
    $simpleQuery = "SELECT prescription_date, patient_name, medicines, reason, prescribed_by FROM prescriptions LIMIT 5";
    $simpleStmt = $db->prepare($simpleQuery);
    $simpleStmt->execute();
    $simpleResults = $simpleStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Count: " . count($simpleResults) . "<br>";
    if (count($simpleResults) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Date</th><th>Patient</th><th>Medicines</th><th>Reason</th><th>Prescribed By</th></tr>";
        foreach ($simpleResults as $row) {
            echo "<tr>";
            echo "<td>" . $row['prescription_date'] . "</td>";
            echo "<td>" . $row['patient_name'] . "</td>";
            echo "<td>" . htmlspecialchars($row['medicines']) . "</td>";
            echo "<td>" . $row['reason'] . "</td>";
            echo "<td>" . $row['prescribed_by'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 2: Check users table
    echo "<h3>2. Users table check:</h3>";
    $usersQuery = "SELECT username, first_name, last_name FROM users LIMIT 5";
    $usersStmt = $db->prepare($usersQuery);
    $usersStmt->execute();
    $usersResults = $usersStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Users count: " . count($usersResults) . "<br>";
    if (count($usersResults) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Username</th><th>First Name</th><th>Last Name</th></tr>";
        foreach ($usersResults as $user) {
            echo "<tr>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['first_name'] . "</td>";
            echo "<td>" . $user['last_name'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 3: Try the join query
    echo "<h3>3. Join query test:</h3>";
    $joinQuery = "SELECT p.prescription_date, p.patient_name, p.medicines, p.reason, p.prescribed_by,
                         CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')) as staff_name
                  FROM prescriptions p
                  LEFT JOIN users u ON p.prescribed_by = u.username
                  LIMIT 5";
    $joinStmt = $db->prepare($joinQuery);
    $joinStmt->execute();
    $joinResults = $joinStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Join results count: " . count($joinResults) . "<br>";
    if (count($joinResults) > 0) {
        echo "<table border='1'>";
        echo "<tr><th>Date</th><th>Patient</th><th>Medicines</th><th>Reason</th><th>Prescribed By</th><th>Staff Name</th></tr>";
        foreach ($joinResults as $row) {
            echo "<tr>";
            echo "<td>" . $row['prescription_date'] . "</td>";
            echo "<td>" . $row['patient_name'] . "</td>";
            echo "<td>" . htmlspecialchars($row['medicines']) . "</td>";
            echo "<td>" . $row['reason'] . "</td>";
            echo "<td>" . $row['prescribed_by'] . "</td>";
            echo "<td>" . $row['staff_name'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test 4: Check if prescribed_by values match usernames
    echo "<h3>4. Check prescribed_by vs usernames:</h3>";
    $prescribedByQuery = "SELECT DISTINCT prescribed_by FROM prescriptions LIMIT 10";
    $prescribedByStmt = $db->prepare($prescribedByQuery);
    $prescribedByStmt->execute();
    $prescribedByResults = $prescribedByStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Prescribed by values: " . implode(', ', $prescribedByResults) . "<br>";
    
    $usernameQuery = "SELECT DISTINCT username FROM users LIMIT 10";
    $usernameStmt = $db->prepare($usernameQuery);
    $usernameStmt->execute();
    $usernameResults = $usernameStmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Username values: " . implode(', ', $usernameResults) . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
