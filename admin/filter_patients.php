<?php
header('Content-Type: application/json');

// Database connection
$db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8mb4', 'root', '');

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$yearLevel = $input['year_level'] ?? '';
$page = (int)($input['page'] ?? 1);
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

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
    
    // Get total count for pagination
    $countQuery = 'SELECT COUNT(*) FROM imported_patients ' . $whereClause;
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetchColumn();
    $totalPages = ceil($totalRecords / $recordsPerPage);
    
    // Get filtered records
    $query = 'SELECT id, student_id, name, dob, gender, address, civil_status, year_level 
              FROM imported_patients ' . $whereClause . ' 
              ORDER BY id DESC 
              LIMIT ' . $recordsPerPage . ' OFFSET ' . $offset;
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Return JSON response
    echo json_encode([
        'success' => true,
        'patients' => $patients,
        'total_records' => $totalRecords,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'records_per_page' => $recordsPerPage
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
