<?php
session_start();
header('Content-Type: application/json');

// Database connection (MySQL)
try {
    $db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get search term from POST data
$searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
$yearFilter = isset($_POST['year_filter']) ? trim($_POST['year_filter']) : '';

// Pagination settings
$records_per_page = 10;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $records_per_page;

try {
    if (empty($searchTerm) && empty($yearFilter)) {
        // If no search term or year filter, return all imported patients with pagination
        $countStmt = $db->query('SELECT COUNT(*) FROM imported_patients');
        $total_records = $countStmt->fetchColumn();
        
        $stmt = $db->prepare('SELECT id, student_id, name, dob, gender, address, civil_status, year_level FROM imported_patients ORDER BY id DESC LIMIT ' . (int)$records_per_page . ' OFFSET ' . (int)$offset);
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Build search conditions
        $whereConditions = [];
        $params = [];
        
        // Search across multiple columns
        if (!empty($searchTerm)) {
            $searchPattern = '%' . $searchTerm . '%';
            $whereConditions[] = '(name LIKE ? OR student_id LIKE ? OR address LIKE ? OR gender LIKE ? OR year_level LIKE ? OR dob LIKE ?)';
            $params = array_merge($params, [$searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern, $searchPattern]);
        }
        
        // Year level filter
        if (!empty($yearFilter)) {
            $whereConditions[] = 'year_level LIKE ?';
            $params[] = '%' . $yearFilter . '%';
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // Count total matching records
        $countStmt = $db->prepare('SELECT COUNT(*) FROM imported_patients WHERE ' . $whereClause);
        $countStmt->execute($params);
        $total_records = $countStmt->fetchColumn();
        
        // Get matching records with pagination
        $stmt = $db->prepare('SELECT id, student_id, name, dob, gender, address, civil_status, year_level FROM imported_patients WHERE ' . $whereClause . ' ORDER BY id DESC LIMIT ' . (int)$records_per_page . ' OFFSET ' . (int)$offset);
        $stmt->execute($params);
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $total_pages = ceil($total_records / $records_per_page);
    
    // Calculate pagination info
    $start = $offset + 1;
    $end = min($offset + $records_per_page, $total_records);
    
    echo json_encode([
        'success' => true,
        'patients' => $patients,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'start' => $start,
        'end' => $end,
        'search_term' => $searchTerm,
        'year_filter' => $yearFilter
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
