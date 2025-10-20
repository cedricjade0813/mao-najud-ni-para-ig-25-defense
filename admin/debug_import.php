<?php
include '../includes/db_connect.php';

echo "<h2>CSV Import Debug</h2>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    $file = $_FILES['csvFile']['tmp_name'];
    
    echo "<h3>File Upload Info:</h3>";
    echo "<p>File name: " . $_FILES['csvFile']['name'] . "</p>";
    echo "<p>File size: " . $_FILES['csvFile']['size'] . " bytes</p>";
    echo "<p>File type: " . $_FILES['csvFile']['type'] . "</p>";
    echo "<p>Temp file: " . $file . "</p>";
    echo "<p>File exists: " . (file_exists($file) ? 'YES' : 'NO') . "</p>";
    
    if (($handle = fopen($file, 'r')) !== false) {
        echo "<h3>CSV Content Analysis:</h3>";
        
        $rowNumber = 0;
        $validRows = 0;
        $skippedRows = 0;
        $duplicateRows = 0;
        
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            echo "<h4>Row $rowNumber:</h4>";
            echo "<p>Columns found: " . count($data) . "</p>";
            echo "<p>Data: " . htmlspecialchars(implode(' | ', $data)) . "</p>";
            
            // Skip header row
            if ($rowNumber == 1) {
                echo "<p>→ Skipped (header row)</p>";
                continue;
            }
            
            // Check column count
            if (count($data) < 8) {
                echo "<p>→ Skipped (insufficient columns)</p>";
                $skippedRows++;
                continue;
            }
            
            // Extract data
            $student_id = isset($data[0]) ? trim($data[0]) : '';
            $name = isset($data[1]) ? trim($data[1]) : '';
            $dob = isset($data[2]) ? trim($data[2]) : '';
            $gender = isset($data[3]) ? trim($data[3]) : '';
            $address = isset($data[4]) ? trim($data[4]) : '';
            $civil_status = isset($data[5]) ? trim($data[5]) : '';
            $password = isset($data[6]) ? trim($data[6]) : '';
            $year_level = isset($data[7]) ? trim($data[7]) : '';
            
            echo "<p>Extracted data:</p>";
            echo "<ul>";
            echo "<li>Student ID: '$student_id'</li>";
            echo "<li>Name: '$name'</li>";
            echo "<li>DOB: '$dob'</li>";
            echo "<li>Gender: '$gender'</li>";
            echo "<li>Address: '$address'</li>";
            echo "<li>Civil Status: '$civil_status'</li>";
            echo "<li>Password: '$password'</li>";
            echo "<li>Year Level: '$year_level'</li>";
            echo "</ul>";
            
            // Check for empty required fields
            if (empty($student_id) || empty($name)) {
                echo "<p>→ Skipped (empty required fields)</p>";
                $skippedRows++;
                continue;
            }
            
            // Check for duplicates
            try {
                $stmtCheck = $db->prepare('SELECT COUNT(*) FROM imported_patients WHERE student_id = ?');
                $stmtCheck->execute([$student_id]);
                $duplicateCount = $stmtCheck->fetchColumn();
                
                if ($duplicateCount > 0) {
                    echo "<p>→ Skipped (duplicate student_id)</p>";
                    $duplicateRows++;
                    continue;
                }
                
                // Try to insert
                echo "<p>→ Attempting to insert...</p>";
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt2 = $db->prepare('INSERT INTO imported_patients (student_id, name, dob, gender, address, civil_status, password, year_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                $result = $stmt2->execute([$student_id, $name, $dob, $gender, $address, $civil_status, $hashedPassword, $year_level]);
                
                if ($result) {
                    echo "<p>✅ Successfully inserted!</p>";
                    $validRows++;
                } else {
                    echo "<p>❌ Insert failed</p>";
                }
                
            } catch (PDOException $e) {
                echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
            }
            
            echo "<hr>";
        }
        
        fclose($handle);
        
        echo "<h3>Summary:</h3>";
        echo "<p>Total rows processed: $rowNumber</p>";
        echo "<p>Valid rows inserted: $validRows</p>";
        echo "<p>Skipped rows: $skippedRows</p>";
        echo "<p>Duplicate rows: $duplicateRows</p>";
        
    } else {
        echo "<p>❌ Failed to open CSV file</p>";
    }
} else {
    echo "<h3>Upload a CSV file to debug:</h3>";
    echo '<form method="post" enctype="multipart/form-data">';
    echo '<input type="file" name="csvFile" accept=".csv" required>';
    echo '<button type="submit">Debug Upload</button>';
    echo '</form>';
}

echo "<p><a href='import.php'>Back to Import Page</a></p>";
?>
