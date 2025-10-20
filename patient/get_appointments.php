<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$patient_id = $_SESSION['student_row_id'] ?? null;
$status = $_POST['status'] ?? 'pending';
$posted_patient_id = $_POST['patient_id'] ?? null; // Get patient ID from POST request
$page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
$perPage = 10; // Show 10 appointments per page
$searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';

// Use the posted patient ID if available, otherwise fall back to session
if ($posted_patient_id) {
    $patient_id = $posted_patient_id;
}

if (!$patient_id) {
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
    
    // Get current date
    $currentDate = date('Y-m-d');
    
    // Build the query for counting total records
    $countSql = "SELECT COUNT(*) as total FROM appointments a 
                 LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id 
                 WHERE a.student_id = ? AND $statusCondition AND a.date = ?";
    $countParams = [$patient_id, $currentDate];
    
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
    
    // Fetch appointments for the logged-in patient only with pagination
    // Join with doctor_schedules to get the real doctor name using doctor_id
    $sql = "SELECT a.id, a.date, a.time, a.reason, a.status, a.student_id, a.doctor_id, ds.doctor_name 
            FROM appointments a
            LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id
            WHERE a.student_id = ? AND $statusCondition AND a.date = ?";
    
    $params = [$patient_id, $currentDate];
    
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
    
    // Debug: Log the actual appointment data with doctor names
    error_log("=== DEBUGGING APPOINTMENT DOCTOR NAMES ===");
    error_log("Patient ID: " . $patient_id . ", Status: " . $status);
    error_log("SQL Query: " . $sql);
    error_log("Found appointments: " . count($appointments));
    error_log("Raw appointment data: " . json_encode($appointments));
    
    // Debug: Check what's in doctor_schedules table
    $debug_doctor_sql = "SELECT * FROM doctor_schedules ORDER BY schedule_date DESC LIMIT 10";
    $debug_doctor_stmt = $db->prepare($debug_doctor_sql);
    $debug_doctor_stmt->execute();
    $debug_doctors = $debug_doctor_stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("All doctor schedules: " . json_encode($debug_doctors));
    
    // Debug: Check appointments table structure and data
    $debug_appt_sql = "SELECT * FROM appointments WHERE student_id = ? ORDER BY date DESC LIMIT 3";
    $debug_appt_stmt = $db->prepare($debug_appt_sql);
    $debug_appt_stmt->execute([$patient_id]);
    $debug_appointments = $debug_appt_stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("Patient appointments: " . json_encode($debug_appointments));
    
    // Debug: Test the join manually
    if (!empty($debug_appointments)) {
        $test_date = $debug_appointments[0]['date'];
        $test_join_sql = "SELECT a.date, ds.doctor_name FROM appointments a LEFT JOIN doctor_schedules ds ON a.date = ds.schedule_date WHERE a.date = ? LIMIT 1";
        $test_join_stmt = $db->prepare($test_join_sql);
        $test_join_stmt->execute([$test_date]);
        $test_result = $test_join_stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Test join for date $test_date: " . json_encode($test_result));
    }
    
    // Format appointments
    $formattedAppointments = [];
    foreach ($appointments as $appt) {
        // Get doctor name using doctor_id
        $doctorName = 'Dr. Medical Officer'; // Default
        
        // Try to get doctor name from the join result first
        if (!empty($appt['doctor_name'])) {
            $doctorName = $appt['doctor_name'];
            error_log("Found doctor via join: " . $doctorName);
        } else if (!empty($appt['doctor_id'])) {
            // If join didn't work but we have doctor_id, try direct query
            $doctor_query = "SELECT doctor_name FROM doctor_schedules WHERE id = ?";
            $doctor_stmt = $db->prepare($doctor_query);
            $doctor_stmt->execute([$appt['doctor_id']]);
            $doctor_result = $doctor_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($doctor_result && !empty($doctor_result['doctor_name'])) {
                $doctorName = $doctor_result['doctor_name'];
                error_log("Found doctor via doctor_id {$appt['doctor_id']}: " . $doctorName);
            } else {
                error_log("No doctor found for doctor_id: " . $appt['doctor_id']);
            }
        } else {
            error_log("No doctor_id found for appointment: " . $appt['id']);
        }
        
        // Add "Dr." prefix if not already present
        if ($doctorName !== 'Dr. Medical Officer' && !empty($doctorName)) {
            if (!str_starts_with($doctorName, 'Dr.')) {
                $doctorName = 'Dr. ' . ucfirst($doctorName);
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
            'student_id' => $appt['student_id'] ?? 'N/A',
            'doctor_id' => $appt['doctor_id'] ?? 'N/A' // Add doctor_id for debugging
        ];
    }
    
    // Debug: Add patient ID to response for verification
    echo json_encode([
        'success' => true,
        'appointments' => $formattedAppointments,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $perPage
        ],
        'debug_info' => [
            'patient_id' => $patient_id,
            'status_filter' => $status,
            'search_term' => $searchTerm,
            'total_found' => count($appointments)
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
