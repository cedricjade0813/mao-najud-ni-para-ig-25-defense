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
    $perPage = 10; // 10 entries per page for Approved Appointments
    
    // First, get the total count of records that match the search criteria
    $countSql = "SELECT COUNT(*) as total
                 FROM appointments a 
                 JOIN imported_patients ip ON a.student_id = ip.id 
                 WHERE a.status IN ('approved', 'confirmed')";
    
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
    $sql = "SELECT a.date, a.time, a.reason, a.status, a.email, ip.name, 
            COALESCE(a.doctor_name, 'Dr. Sarah Johnson') as doctor_name 
            FROM appointments a 
            JOIN imported_patients ip ON a.student_id = ip.id 
            WHERE a.status IN ('approved', 'confirmed')";
    
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
        
        // Determine status display
        $statusDisplay = ucfirst($appt['status']);
        $statusClass = '';
        switch ($appt['status']) {
            case 'approved':
                $statusClass = 'bg-green-100 text-green-800';
                break;
            case 'confirmed':
                $statusClass = 'bg-blue-100 text-blue-800';
                break;
            default:
                $statusClass = 'bg-gray-100 text-gray-800';
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
            'status_display' => $statusDisplay,
            'status_class' => $statusClass
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
