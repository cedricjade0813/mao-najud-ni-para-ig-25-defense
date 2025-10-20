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

// Pagination settings
$records_per_page = 10;
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $records_per_page;

try {
    if (empty($searchTerm)) {
        // If no search term, return all users with pagination
        $countStmt = $db->query('SELECT COUNT(*) FROM users');
        $total_records = $countStmt->fetchColumn();
        
        $stmt = $db->prepare('SELECT * FROM users ORDER BY id DESC LIMIT ' . (int)$records_per_page . ' OFFSET ' . (int)$offset);
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Search across name, username, email, and role columns
        $searchPattern = '%' . $searchTerm . '%';
        
        // Count total matching records
        $countStmt = $db->prepare('SELECT COUNT(*) FROM users WHERE 
            name LIKE ? OR 
            username LIKE ? OR 
            email LIKE ? OR 
            role LIKE ?');
        $countStmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
        $total_records = $countStmt->fetchColumn();
        
        // Get matching records with pagination
        $stmt = $db->prepare('SELECT * FROM users WHERE 
            name LIKE ? OR 
            username LIKE ? OR 
            email LIKE ? OR 
            role LIKE ? 
            ORDER BY id DESC LIMIT ' . (int)$records_per_page . ' OFFSET ' . (int)$offset);
        $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    $total_pages = ceil($total_records / $records_per_page);
    
    // Calculate pagination info
    $start = $offset + 1;
    $end = min($offset + $records_per_page, $total_records);
    
    echo json_encode([
        'success' => true,
        'users' => $users,
        'total_records' => $total_records,
        'total_pages' => $total_pages,
        'current_page' => $page,
        'start' => $start,
        'end' => $end,
        'search_term' => $searchTerm
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
