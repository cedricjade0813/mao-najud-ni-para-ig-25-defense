<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    $searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $perPage = 10; // 10 entries per page for Doctor Schedules
    
    // First, get the total count of records that match the search criteria
    $countSql = "SELECT COUNT(*) as total
                 FROM doctor_schedules 
                 WHERE schedule_date >= CURDATE()";
    
    $countParams = [];
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $countSql .= " AND (doctor_name LIKE ? OR profession LIKE ? OR schedule_date LIKE ? OR schedule_time LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
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
    $sql = "SELECT id, doctor_name, profession, schedule_date, schedule_time 
            FROM doctor_schedules 
            WHERE schedule_date >= CURDATE()";
    
    $params = [];
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $sql .= " AND (doctor_name LIKE ? OR profession LIKE ? OR schedule_date LIKE ? OR schedule_time LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    
    $sql .= " ORDER BY schedule_date ASC, schedule_time ASC";
    $sql .= " LIMIT " . (($page - 1) * $perPage) . ", " . $perPage;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedSchedules = [];
    foreach ($schedules as $schedule) {
        // Parse time range to get start and end times
        $timeParts = explode('-', $schedule['schedule_time']);
        $startTime = isset($timeParts[0]) ? trim($timeParts[0]) : '';
        $endTime = isset($timeParts[1]) ? trim($timeParts[1]) : '';
        
        $formattedSchedules[] = [
            'id' => $schedule['id'],
            'doctor_name' => htmlspecialchars($schedule['doctor_name']),
            'profession' => htmlspecialchars($schedule['profession'] ?? 'Physician'),
            'schedule_date' => $schedule['schedule_date'],
            'schedule_time' => htmlspecialchars($schedule['schedule_time']),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'formatted_date' => date('D, M j, Y', strtotime($schedule['schedule_date'])),
            'formatted_start_time' => date('g:i A', strtotime($startTime)),
            'formatted_end_time' => date('g:i A', strtotime($endTime))
        ];
    }
    
    echo json_encode([
        'success' => true,
        'schedules' => $formattedSchedules,
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
