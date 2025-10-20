<?php
// profile_cancel_appointment.php
// Suppress any output before JSON
ob_start();
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($_SESSION)) session_start();
$student_id = $_SESSION['student_row_id'] ?? null;
$appointment_id = $data['appointment_id'] ?? null;
$date = $data['date'] ?? null;
$time = $data['time'] ?? null;
$reason = $data['reason'] ?? null;

// Check if we have appointment ID (new method) or date/time/reason (old method)
if (!$student_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

try {
    $success = false;
    
    if ($appointment_id) {
        // New method: Cancel by appointment ID (works for any status)
        $stmt = $db->prepare('UPDATE appointments SET status = "cancelled" WHERE id = ? AND student_id = ? AND status IN ("pending", "approved") LIMIT 1');
        $stmt->execute([$appointment_id, $student_id]);
        $success = $stmt->rowCount() > 0;
    } elseif ($date && $time && $reason) {
        // Old method: Cancel by date/time/reason (only for pending appointments)
        $stmt = $db->prepare('UPDATE appointments SET status = "cancelled" WHERE student_id = ? AND date = ? AND time = ? AND reason = ? AND status = "pending" LIMIT 1');
        $stmt->execute([$student_id, $date, $time, $reason]);
        $success = $stmt->rowCount() > 0;
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Missing appointment data.']);
        exit;
    }
    
    ob_clean();
    echo json_encode(['success' => $success, 'message' => $success ? 'Appointment cancelled successfully.' : 'No appointment found to cancel.']);
} catch (PDOException $e) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
