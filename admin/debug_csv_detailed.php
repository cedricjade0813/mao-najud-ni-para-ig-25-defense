<!DOCTYPE html>
<html>
<head>
    <title>Detailed CSV Debug - Clinic Management System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .info { color: blue; font-weight: bold; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .row-number { background-color: #f9f9f9; font-weight: bold; }
        .valid-row { background-color: #e8f5e8; }
        .invalid-row { background-color: #ffe8e8; }
        .skipped-row { background-color: #fff3cd; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Detailed CSV Import Debug</h1>

<?php
include '../includes/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    $file = $_FILES['csvFile']['tmp_name'];
    $originalName = $_FILES['csvFile']['name'];
    
    echo "<h2>File Analysis: " . htmlspecialchars($originalName) . "</h2>";
    echo "<p><strong>File size:</strong> " . $_FILES['csvFile']['size'] . " bytes</p>";
    echo "<p><strong>File type:</strong> " . $_FILES['csvFile']['type'] . "</p>";
    
    if (($handle = fopen($file, 'r')) !== false) {
        echo "<h3>CSV Content Analysis:</h3>";
        
        $rowNumber = 0;
        $validRows = 0;
        $skippedRows = 0;
        $duplicateRows = 0;
        $errorRows = 0;
        
        echo "<table>";
        echo "<tr><th>Row #</th><th>Status</th><th>Columns</th><th>Student ID</th><th>Name</th><th>DOB</th><th>Gender</th><th>Address</th><th>Civil Status</th><th>Password</th><th>Year Level</th><th>Issues</th></tr>";
        
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            $status = '';
            $issues = [];
            $rowClass = '';
            
            // No header row to skip - process all rows as data
            
            // Check column count
            if (count($data) < 8) {
                $status = 'Invalid';
                $issues[] = 'Insufficient columns (' . count($data) . '/8)';
                $rowClass = 'invalid-row';
                $skippedRows++;
            } else {
                // Extract data
                $student_id = isset($data[0]) ? trim($data[0]) : '';
                $name = isset($data[1]) ? trim($data[1]) : '';
                $dob = isset($data[2]) ? trim($data[2]) : '';
                $gender = isset($data[3]) ? trim($data[3]) : '';
                $address = isset($data[4]) ? trim($data[4]) : '';
                $civil_status = isset($data[5]) ? trim($data[5]) : '';
                $password = isset($data[6]) ? trim($data[6]) : '';
                $year_level = isset($data[7]) ? trim($data[7]) : '';
                
                // Check for empty required fields
                if (empty($student_id)) {
                    $issues[] = 'Empty student_id';
                }
                if (empty($name)) {
                    $issues[] = 'Empty name';
                }
                
                if (!empty($issues)) {
                    $status = 'Invalid';
                    $rowClass = 'invalid-row';
                    $skippedRows++;
                } else {
                    // Check for duplicates
                    try {
                        $stmtCheck = $db->prepare('SELECT COUNT(*) FROM imported_patients WHERE student_id = ?');
                        $stmtCheck->execute([$student_id]);
                        $duplicateCount = $stmtCheck->fetchColumn();
                        
                        if ($duplicateCount > 0) {
                            $status = 'Duplicate';
                            $issues[] = 'Student ID already exists';
                            $rowClass = 'skipped-row';
                            $duplicateRows++;
                        } else {
                            // Try to insert
                            try {
                                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                                $stmt2 = $db->prepare('INSERT INTO imported_patients (student_id, name, dob, gender, address, civil_status, password, year_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                                $result = $stmt2->execute([$student_id, $name, $dob, $gender, $address, $civil_status, $hashedPassword, $year_level]);
                                
                                if ($result) {
                                    $status = 'Success';
                                    $rowClass = 'valid-row';
                                    $validRows++;
                                } else {
                                    $status = 'Error';
                                    $issues[] = 'Database insert failed';
                                    $rowClass = 'invalid-row';
                                    $errorRows++;
                                }
                            } catch (PDOException $e) {
                                $status = 'Error';
                                $issues[] = 'Database error: ' . $e->getMessage();
                                $rowClass = 'invalid-row';
                                $errorRows++;
                            }
                        }
                    } catch (PDOException $e) {
                        $status = 'Error';
                        $issues[] = 'Duplicate check failed: ' . $e->getMessage();
                        $rowClass = 'invalid-row';
                        $errorRows++;
                    }
                }
            }
            
            echo "<tr class='$rowClass'>";
            echo "<td class='row-number'>$rowNumber</td>";
            echo "<td><strong>$status</strong></td>";
            echo "<td>" . count($data) . "</td>";
            for ($i = 0; $i < 8; $i++) {
                $value = isset($data[$i]) ? htmlspecialchars($data[$i]) : '';
                if (strlen($value) > 20) {
                    $value = substr($value, 0, 17) . '...';
                }
                echo "<td>$value</td>";
            }
            echo "<td>" . implode(', ', $issues) . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        fclose($handle);
        
        echo "<h3>Summary:</h3>";
        echo "<p><span class='success'>✅ Valid rows inserted: $validRows</span></p>";
        echo "<p><span class='warning'>⚠️ Skipped rows: $skippedRows</span></p>";
        echo "<p><span class='info'>ℹ️ Duplicate rows: $duplicateRows</span></p>";
        echo "<p><span class='error'>❌ Error rows: $errorRows</span></p>";
        echo "<p><strong>Total rows processed: $rowNumber</strong></p>";
        
        if ($validRows == 0) {
            echo "<h3>Why No Records Were Added:</h3>";
            if ($skippedRows > 0) {
                echo "<p>• $skippedRows rows were skipped due to format issues</p>";
            }
            if ($duplicateRows > 0) {
                echo "<p>• $duplicateRows rows were duplicates</p>";
            }
            if ($errorRows > 0) {
                echo "<p>• $errorRows rows had database errors</p>";
            }
        }
        
    } else {
        echo "<p class='error'>❌ Failed to open CSV file</p>";
    }
} else {
    echo "<h3>Upload a CSV file to debug:</h3>";
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="csvFile" accept=".csv" required>';
    echo '<button type="submit">Debug Upload</button>';
    echo '</form>';
}
?>

        <p><a href="import.php">Back to Import Page</a></p>
    </div>
</body>
</html>
