<?php
session_start();
header('Content-Type: application/json');

// Database connection
try {
    include '../includes/db_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get search parameters from POST data
$searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
$userFilter = isset($_POST['user_filter']) ? trim($_POST['user_filter']) : 'all';
$fromDateFilter = isset($_POST['from_date']) ? trim($_POST['from_date']) : '';
$toDateFilter = isset($_POST['to_date']) ? trim($_POST['to_date']) : '';

// Pagination settings
$perPage = 10;
$currentPage = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$offset = ($currentPage - 1) * $perPage;

try {
    // Build WHERE clause for filters
    $whereConditions = [];
    $params = [];
    
    if ($userFilter !== 'all') {
        $whereConditions[] = "user_email = ?";
        $params[] = $userFilter;
    }
    
    if ($fromDateFilter) {
        $whereConditions[] = "DATE(timestamp) >= ?";
        $params[] = $fromDateFilter;
    }
    
    if ($toDateFilter) {
        $whereConditions[] = "DATE(timestamp) <= ?";
        $params[] = $toDateFilter;
    }
    
    if ($searchTerm) {
        // Search in timestamp, user_email (User column), and user name (Name column)
        // We need to join with users table to search by both username and name
        $whereConditions[] = "(DATE_FORMAT(timestamp, '%Y-%m-%d %H:%i:%s') LIKE ? OR user_email LIKE ? OR EXISTS (SELECT 1 FROM users WHERE users.email = logs.user_email AND (users.username LIKE ? OR users.name LIKE ?)))";
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
        $params[] = '%' . $searchTerm . '%';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM logs $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalLogs = $countStmt->fetchColumn();
    $totalPages = ceil($totalLogs / $perPage);
    
    // Get logs for current page
    $logsQuery = "SELECT * FROM logs $whereClause ORDER BY timestamp DESC LIMIT $perPage OFFSET $offset";
    $logsStmt = $db->prepare($logsQuery);
    $logsStmt->execute($params);
    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get metrics for dashboard cards
    $metricsQuery = "SELECT 
        COUNT(*) as total_logs,
        COUNT(DISTINCT user_email) as active_users,
        COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as recent_activity,
        (SELECT user_email FROM logs WHERE user_email IS NOT NULL GROUP BY user_email ORDER BY COUNT(*) DESC LIMIT 1) as most_active_user
        FROM logs $whereClause";
    $metricsStmt = $db->prepare($metricsQuery);
    $metricsStmt->execute($params);
    $metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Build user map: email => full name (username)
    $userMap = [];
    $userNameMap = []; // email => actual name (name field)
    try {
        $userRows = $db->query('SELECT email, username, name FROM users')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($userRows as $u) {
            $userMap[$u['email']] = $u['username'];
            // Use name field, fallback to username if not available
            $userNameMap[$u['email']] = !empty($u['name']) ? $u['name'] : $u['username'];
        }
    } catch (Exception $e) {}
    
    // Calculate pagination info
    $start = $offset + 1;
    $end = min($offset + $perPage, $totalLogs);
    
    echo json_encode([
        'success' => true,
        'logs' => $logs,
        'total_logs' => $totalLogs,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'start' => $start,
        'end' => $end,
        'search_term' => $searchTerm,
        'user_filter' => $userFilter,
        'from_date' => $fromDateFilter,
        'to_date' => $toDateFilter,
        'metrics' => $metrics,
        'user_map' => $userMap,
        'user_name_map' => $userNameMap
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
