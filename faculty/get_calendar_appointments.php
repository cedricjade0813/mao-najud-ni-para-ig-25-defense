<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$faculty_id = $_SESSION['faculty_id'] ?? null;

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
    
    // Fetch all appointments for the faculty
    $sql = "SELECT id, date, time, reason, status, doctor_name FROM appointments 
            WHERE student_id = ? 
            ORDER BY date ASC, time ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$faculty_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format appointments for calendar
    $formattedAppointments = [];
    foreach ($appointments as $appt) {
        $formattedAppointments[] = [
            'id' => $appt['id'],
            'date' => $appt['date'],
            'time' => convertTimeRange($appt['time']),
            'reason' => htmlspecialchars($appt['reason']),
            'status' => $appt['status'],
            'doctor_name' => htmlspecialchars($appt['doctor_name'] ?? 'Dr. Sarah Johnson')
        ];
    }
    
    echo json_encode([
        'success' => true,
        'appointments' => $formattedAppointments
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
