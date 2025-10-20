<?php
include '../includes/db_connect.php';

echo "<h2>Test Both CSV Formats</h2>";

// Test data
$format1 = "student_id,name,dob,gender,address,civil_status,password,year_level\nSCC-22-00015340,Jangad Jaynu D.,8/22/2003,Male,SpringWoods,Single,Jangad,4th Year";
$format2 = "SCC-22-00015340,Jangad Jaynu D.,8/22/2003,Male,SpringWoods,Single,Jangad,4th Year";

echo "<h3>Testing Format 1 (With Header):</h3>";
echo "<pre>" . htmlspecialchars($format1) . "</pre>";

$lines1 = explode("\n", $format1);
foreach ($lines1 as $i => $line) {
    $data = str_getcsv($line);
    $firstColumn = isset($data[0]) ? strtolower(trim($data[0])) : '';
    $isHeader = ($firstColumn === 'student_id' || $firstColumn === 'studentid' || $firstColumn === 'id');
    echo "<p>Row " . ($i + 1) . ": " . ($isHeader ? "SKIP (Header)" : "PROCESS (Data)") . " - First column: '$firstColumn'</p>";
}

echo "<h3>Testing Format 2 (Data Only):</h3>";
echo "<pre>" . htmlspecialchars($format2) . "</pre>";

$lines2 = explode("\n", $format2);
foreach ($lines2 as $i => $line) {
    $data = str_getcsv($line);
    $firstColumn = isset($data[0]) ? strtolower(trim($data[0])) : '';
    $isHeader = ($firstColumn === 'student_id' || $firstColumn === 'studentid' || $firstColumn === 'id');
    echo "<p>Row " . ($i + 1) . ": " . ($isHeader ? "SKIP (Header)" : "PROCESS (Data)") . " - First column: '$firstColumn'</p>";
}

echo "<p><a href='import.php'>Back to Import Page</a></p>";
?>
