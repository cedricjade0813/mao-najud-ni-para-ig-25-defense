<?php
// AJAX endpoint for fetching faculty data
header('Content-Type: application/json');

try {
    include '../includes/db_connect.php';
    
    // Get pagination parameters
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $per_page = isset($_POST['per_page']) ? (int)$_POST['per_page'] : 10;
    $page = max($page, 1); // Ensure page is at least 1
    $offset = ($page - 1) * $per_page;
    
    // Check if search term is provided
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    
    if (!empty($search)) {
        // Search faculty data with pagination
        $count_stmt = $db->prepare('SELECT COUNT(*) FROM faculty WHERE full_name LIKE ? OR email LIKE ? OR department LIKE ? OR college_course LIKE ?');
        $searchTerm = '%' . $search . '%';
        $count_stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $total_records = $count_stmt->fetchColumn();
        
        $stmt = $db->prepare('SELECT faculty_id, full_name, contact, department, college_course, gender, email, civil_status FROM faculty WHERE full_name LIKE ? OR email LIKE ? OR department LIKE ? OR college_course LIKE ? ORDER BY faculty_id DESC LIMIT ' . (int)$per_page . ' OFFSET ' . (int)$offset);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
    } else {
        // Get all faculty data with pagination
        $count_stmt = $db->query('SELECT COUNT(*) FROM faculty');
        $total_records = $count_stmt->fetchColumn();
        
        $stmt = $db->prepare('SELECT faculty_id, full_name, contact, department, college_course, gender, email, civil_status FROM faculty ORDER BY faculty_id DESC LIMIT ' . (int)$per_page . ' OFFSET ' . (int)$offset);
        $stmt->execute();
    }
    
    $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $total_pages = ceil($total_records / $per_page);
    $start_record = $offset + 1;
    $end_record = min($offset + $per_page, $total_records);
    
    echo json_encode([
        'success' => true,
        'faculty' => $faculty,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'per_page' => $per_page,
            'start_record' => $start_record,
            'end_record' => $end_record
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
