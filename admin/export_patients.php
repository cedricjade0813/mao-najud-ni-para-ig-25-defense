<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="patients_export.csv"');

// Database connection
$db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8mb4', 'root', '');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$yearLevel = $input['year_level'] ?? '';

try {
    // Build the query with year level filter
    $whereClause = '';
    $params = [];
    
    if (!empty($yearLevel)) {
        // Extract year number for flexible matching
        preg_match('/(\d+)/', $yearLevel, $matches);
        if (isset($matches[1])) {
            $yearNumber = $matches[1];
            $whereClause = 'WHERE year_level LIKE ?';
            $params[] = '%' . $yearNumber . '%';
        }
    }
    
    // Get all filtered records (no pagination for export)
    $query = 'SELECT student_id, name, address, gender, year_level, dob 
              FROM imported_patients ' . $whereClause . ' 
              ORDER BY id DESC';
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create CSV output
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, ['Student ID', 'Name', 'Address', 'Gender', 'Year Level', 'Birth Date']);
    
    // Add data rows
    foreach ($patients as $patient) {
        fputcsv($output, [
            $patient['student_id'],
            $patient['name'],
            $patient['address'],
            $patient['gender'],
            $patient['year_level'],
            $patient['dob']
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    // If there's an error, output a simple error message
    echo "Error exporting data: " . $e->getMessage();
}
?>
