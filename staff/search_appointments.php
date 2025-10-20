<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

// Get search parameters
$searchTerm = $_POST['search'] ?? '';
$type = $_POST['type'] ?? '';

if (empty($searchTerm) || empty($type)) {
    echo json_encode(['success' => false, 'message' => 'Missing search parameters']);
    exit();
}

// Database connection
$conn = new mysqli('localhost', 'root', '', 'clinic_management_system');
if ($conn->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$appointments = [];
$searchParam = "%$searchTerm%";

try {
    // Build query based on type
    if ($type === 'pending') {
        $stmt = $conn->prepare("SELECT a.date, a.time, a.reason, a.status, a.email, ip.name FROM appointments a JOIN imported_patients ip ON a.student_id = ip.id WHERE a.status = 'pending' AND (ip.name LIKE ? OR a.date LIKE ? OR a.time LIKE ? OR a.reason LIKE ? OR a.email LIKE ?) ORDER BY a.date DESC, a.time DESC");
    } elseif ($type === 'approved') {
        $stmt = $conn->prepare("SELECT a.date, a.time, a.reason, a.status, a.email, ip.name FROM appointments a JOIN imported_patients ip ON a.student_id = ip.id WHERE a.status IN ('approved', 'confirmed') AND (ip.name LIKE ? OR a.date LIKE ? OR a.time LIKE ? OR a.reason LIKE ? OR a.email LIKE ?) ORDER BY a.date DESC, a.time DESC");
    } elseif ($type === 'declined') {
        $stmt = $conn->prepare("SELECT a.date, a.time, a.reason, a.status, a.email, ip.name FROM appointments a JOIN imported_patients ip ON a.student_id = ip.id WHERE a.status = 'declined' AND (ip.name LIKE ? OR a.date LIKE ? OR a.time LIKE ? OR a.reason LIKE ? OR a.email LIKE ?) ORDER BY a.date DESC, a.time DESC");
    } elseif ($type === 'rescheduled') {
        $stmt = $conn->prepare("SELECT a.date, a.time, a.reason, a.status, a.email, ip.name FROM appointments a JOIN imported_patients ip ON a.student_id = ip.id WHERE a.status = 'rescheduled' AND (ip.name LIKE ? OR a.date LIKE ? OR a.time LIKE ? OR a.reason LIKE ? OR a.email LIKE ?) ORDER BY a.date DESC, a.time DESC");
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid appointment type']);
        exit();
    }
    
    if ($stmt) {
        $stmt->bind_param('sssss', $searchParam, $searchParam, $searchParam, $searchParam, $searchParam);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $appointments[] = [
                'date' => $row['date'],
                'time' => $row['time'],
                'reason' => $row['reason'],
                'status' => $row['status'],
                'email' => $row['email'],
                'name' => $row['name']
            ];
        }
        
        $stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'appointments' => $appointments,
        'count' => count($appointments)
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Search failed: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>
