<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$search = $_POST['search'] ?? '';
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$perPage = 5; // Same as the original pagination
$faculty_id = isset($_POST['faculty_id']) ? intval($_POST['faculty_id']) : 0;

if (!$faculty_id) {
    echo json_encode(['success' => false, 'message' => 'Faculty ID is required']);
    exit;
}

try {
    include '../includes/db_connect.php';
    
    // Function to convert 24-hour time range to 12-hour format
    function convertTimeRange($timeRange) {
        if (empty($timeRange) || !strpos($timeRange, '-')) {
            return $timeRange; // Return as is if not a time range
        }
        
        $times = explode('-', $timeRange);
        if (count($times) !== 2) {
            return $timeRange; // Return as is if not a valid range
        }
        
        $startTime = trim($times[0]);
        $endTime = trim($times[1]);
        
        // Convert start time
        $startFormatted = date('g:i A', strtotime($startTime));
        
        // Convert end time
        $endFormatted = date('g:i A', strtotime($endTime));
        
        return $startFormatted . '-' . $endFormatted;
    }
    
    // Calculate date range for last 10 days
    $ten_days_ago = date('Y-m-d', strtotime('-10 days'));
    $today = date('Y-m-d');
    
    // Build the query for counting total records (only active statuses)
    $countSql = "SELECT COUNT(*) as total
                 FROM appointments a 
                 LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id 
                 WHERE a.faculty_id = ? 
                 AND a.date >= ? 
                 AND a.date <= ?
                 AND a.status IN ('pending', 'approved', 'confirmed')";
    
    $countParams = [$faculty_id, $ten_days_ago, $today];
    
    // Add search condition if search term is provided
    if (!empty($search)) {
        $countSql .= " AND (a.reason LIKE ? OR a.status LIKE ? OR ds.doctor_name LIKE ?)";
        $searchPattern = '%' . $search . '%';
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Build the main query with pagination (only active statuses)
    $sql = "SELECT a.id, a.date, a.time, a.reason, a.status, a.faculty_id, a.doctor_id, ds.doctor_name 
            FROM appointments a 
            LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id 
            WHERE a.faculty_id = ? 
            AND a.date >= ? 
            AND a.date <= ?
            AND a.status IN ('pending', 'approved', 'confirmed')";
    
    $params = [$faculty_id, $ten_days_ago, $today];
    
    // Add search condition if search term is provided
    if (!empty($search)) {
        $sql .= " AND (a.reason LIKE ? OR a.status LIKE ? OR ds.doctor_name LIKE ?)";
        $searchPattern = '%' . $search . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    
    $sql .= " ORDER BY a.date DESC, a.time DESC";
    $sql .= " LIMIT " . (($page - 1) * $perPage) . ", " . $perPage;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedAppointments = [];
    foreach ($appointments as $appt) {
        // Add "Dr." prefix if not already present
        $doctorName = $appt['doctor_name'] ?? 'Dr. Medical Officer';
        if ($doctorName !== 'Dr. Medical Officer' && !empty($doctorName)) {
            if (!str_starts_with($doctorName, 'Dr.')) {
                $doctorName = 'Dr. ' . ucfirst($doctorName);
            }
        }
        
        $formattedAppointments[] = [
            'id' => $appt['id'],
            'date' => htmlspecialchars($appt['date']),
            'time' => convertTimeRange($appt['time']),
            'reason' => htmlspecialchars($appt['reason']),
            'status' => htmlspecialchars($appt['status']),
            'doctor_name' => htmlspecialchars($doctorName),
            'formatted_date' => date('M j, Y', strtotime($appt['date']))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'appointments' => $formattedAppointments,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $perPage
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
