<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$faculty_id = $_SESSION['faculty_id'] ?? null;
$status = $_POST['status'] ?? 'pending';
$posted_faculty_id = $_POST['faculty_id'] ?? null; // Get faculty ID from POST request
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$perPage = 10; // Show 10 appointments per page
$searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';

// Use the posted faculty ID if available, otherwise fall back to session
if ($posted_faculty_id) {
    $faculty_id = $posted_faculty_id;
}

if (!$faculty_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
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
    
    // Build status condition
    $statusCondition = '';
    switch($status) {
        case 'pending':
            $statusCondition = "status = 'pending'";
            break;
        case 'approved':
            $statusCondition = "status IN ('approved', 'confirmed')";
            break;
        case 'declined':
            $statusCondition = "status = 'declined'";
            break;
        case 'rescheduled':
            $statusCondition = "status = 'rescheduled'";
            break;
        default:
            $statusCondition = "status = 'pending'";
    }
    
    // Set timezone to Philippines and get current date
    date_default_timezone_set('Asia/Manila');
    $currentDate = date('Y-m-d');
    
    // Build date condition - filter by current date for pending, by time for rescheduled
    if ($status === 'pending') {
        $dateCondition = "AND a.date = ?";
        $dateParams = [$currentDate];
    } elseif ($status === 'rescheduled') {
        $currentTime = date('H:i:s');
        // Hide rescheduled appointments 1 hour after their scheduled time
        $dateCondition = "AND (a.date > ? OR (a.date = ? AND (
            (a.time NOT LIKE '%-%' AND ADDTIME(a.time, '01:00:00') >= ?) OR 
            (a.time LIKE '%-%' AND (
                ADDTIME(SUBSTRING_INDEX(a.time, '-', 1), '01:00:00') >= ? OR 
                (SUBSTRING_INDEX(a.time, '-', 1) <= ? AND ADDTIME(SUBSTRING_INDEX(a.time, '-', -1), '01:00:00') >= ?)
            ))
        )))";
        $dateParams = [$currentDate, $currentDate, $currentTime, $currentTime, $currentTime, $currentTime];
    } else {
        $dateCondition = "";
        $dateParams = [];
    }
    
    // Build the query for counting total records
    $countSql = "SELECT COUNT(*) as total FROM appointments a 
                 LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id 
                 WHERE a.faculty_id = ? AND $statusCondition $dateCondition";
    $countParams = array_merge([$faculty_id], $dateParams);
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $countSql .= " AND (a.reason LIKE ? OR ds.doctor_name LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Fetch appointments for the logged-in faculty only with pagination
    // Join with doctor_schedules to get the real doctor name using doctor_id
    $sql = "SELECT a.id, a.date, a.time, a.reason, a.status, a.faculty_id, a.doctor_id, ds.doctor_name 
            FROM appointments a
            LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id
            WHERE a.faculty_id = ? AND $statusCondition $dateCondition";
    
    $params = array_merge([$faculty_id], $dateParams);
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $sql .= " AND (a.reason LIKE ? OR ds.doctor_name LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    
    $sql .= " ORDER BY date DESC, time DESC";
    $sql .= " LIMIT " . (($page - 1) * $perPage) . ", " . $perPage;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug logging removed for cleaner code
    
    // Format appointments
    $formattedAppointments = [];
    foreach ($appointments as $appt) {
        // Get doctor name using doctor_id
        $doctorName = 'Dr. Medical Officer'; // Default fallback
        
        // First try to get doctor name from the join result
        if (!empty($appt['doctor_name'])) {
            $doctorName = $appt['doctor_name'];
        } 
        // If join didn't work, try direct query using doctor_id
        else if (!empty($appt['doctor_id'])) {
            $doctor_query = "SELECT doctor_name FROM doctor_schedules WHERE id = ?";
            $doctor_stmt = $db->prepare($doctor_query);
            $doctor_stmt->execute([$appt['doctor_id']]);
            $doctor_result = $doctor_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($doctor_result && !empty($doctor_result['doctor_name'])) {
                $doctorName = $doctor_result['doctor_name'];
            }
        }
        
        // Add "Dr." prefix if not already present
        if (!empty($doctorName) && $doctorName !== 'Dr. Medical Officer') {
            if (!str_starts_with($doctorName, 'Dr.')) {
                $doctorName = 'Dr. ' . $doctorName;
            }
        }
        
        $formattedAppointments[] = [
            'id' => $appt['id'],
            'date' => $appt['date'],
            'time' => convertTimeRange($appt['time']),
            'reason' => htmlspecialchars($appt['reason']),
            'status' => $appt['status'],
            'doctor_name' => $doctorName,
            'formatted_date' => date('D, M j, Y', strtotime($appt['date'])),
            'faculty_id' => $appt['faculty_id'] ?? 'N/A',
        ];
    }
    
    // Debug: Add faculty ID to response for verification
    echo json_encode([
        'success' => true,
        'appointments' => $formattedAppointments,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $perPage
        ],
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
