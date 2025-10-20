<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

$patient_id = $_SESSION['student_row_id'] ?? null;
$appointment_id = $_POST['appointment_id'] ?? null;

if (!$patient_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in.']);
    exit;
}

if (!$appointment_id) {
    echo json_encode(['success' => false, 'message' => 'Missing appointment ID.']);
    exit;
}

try {
    include '../includes/db_connect.php';
    
    // Update appointment status to cancelled
    $stmt = $db->prepare('UPDATE appointments SET status = ? WHERE id = ? AND student_id = ?');
    $success = $stmt->execute(['cancelled', $appointment_id, $patient_id]);
    
    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'No appointment found to cancel or unauthorized.']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
