<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Add doctor_name column to appointments table if it doesn't exist
try {
    $db->exec("ALTER TABLE appointments ADD COLUMN doctor_name VARCHAR(255) DEFAULT 'Dr. Sarah Johnson'");
} catch (PDOException $e) {
    // Column might already exist, ignore error
}

try {
    $searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $perPage = 10; // 10 entries per page for Pending Appointments
    
    // First, get the total count of records that match the search criteria
    $countSql = "SELECT COUNT(*) as total
                 FROM appointments a 
                 JOIN imported_patients ip ON a.student_id = ip.id 
                 WHERE a.status = 'pending'";
    
    $countParams = [];
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $countSql .= " AND (ip.name LIKE ? OR a.email LIKE ? OR a.reason LIKE ? OR a.date LIKE ? OR a.time LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Now get the paginated results
    $sql = "SELECT a.date, a.time, a.reason, a.status, a.email, ip.name, ds.doctor_name 
            FROM appointments a 
            JOIN imported_patients ip ON a.student_id = ip.id 
            LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id 
            WHERE a.status = 'pending'";
    
    $params = [];
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $sql .= " AND (ip.name LIKE ? OR a.email LIKE ? OR a.reason LIKE ? OR a.date LIKE ? OR a.time LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
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
        // Format date and time
        $formattedDate = date('M j, Y', strtotime($appt['date']));
        $formattedTime = date('g:i A', strtotime($appt['time']));
        
        // Add "Dr." prefix if not already present
        $doctorName = $appt['doctor_name'] ?? 'Dr. Medical Officer';
        if ($doctorName !== 'Dr. Medical Officer' && !empty($doctorName)) {
            if (!str_starts_with($doctorName, 'Dr.')) {
                $doctorName = 'Dr. ' . ucfirst($doctorName);
            }
        }
        
        $formattedAppointments[] = [
            'name' => htmlspecialchars($appt['name']),
            'email' => htmlspecialchars($appt['email']),
            'date' => $appt['date'],
            'time' => $appt['time'],
            'formatted_date' => $formattedDate,
            'formatted_time' => $formattedTime,
            'reason' => htmlspecialchars($appt['reason']),
            'status' => $appt['status'],
            'doctor_name' => htmlspecialchars($doctorName)
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
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
