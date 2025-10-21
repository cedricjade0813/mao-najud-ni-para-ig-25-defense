<?php
// profile_cancel_appointment.php
// Suppress any output before JSON
ob_start();
header('Content-Type: application/json');
require_once '../includes/db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!isset($_SESSION)) session_start();
$faculty_id = $_SESSION['faculty_id'] ?? null;
$appointment_id = $data['appointment_id'] ?? null;
$date = $data['date'] ?? null;
$time = $data['time'] ?? null;
$reason = $data['reason'] ?? null;

// Check if we have appointment ID (new method) or date/time/reason (old method)
if (!$faculty_id) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

try {
    $success = false;
    
    if ($appointment_id) {
        // New method: Delete appointment by ID (works for any status)
        $stmt = $db->prepare('DELETE FROM appointments WHERE id = ? AND faculty_id = ? AND status IN ("pending", "approved") LIMIT 1');
        $stmt->execute([$appointment_id, $faculty_id]);
        $success = $stmt->rowCount() > 0;
    } elseif ($date && $time && $reason) {
        // Old method: Delete appointment by date/time/reason (only for pending appointments)
        $stmt = $db->prepare('DELETE FROM appointments WHERE faculty_id = ? AND date = ? AND time = ? AND reason = ? AND status = "pending" LIMIT 1');
        $stmt->execute([$faculty_id, $date, $time, $reason]);
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
