<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['student_row_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if message ID is provided
if (!isset($_POST['message_id']) || empty($_POST['message_id'])) {
    echo json_encode(['success' => false, 'message' => 'Message ID required']);
    exit;
}

$message_id = intval($_POST['message_id']);
$patient_id = $_SESSION['student_row_id'];

try {
    // First, verify that the message belongs to this patient
    $stmt = $db->prepare('SELECT id FROM messages WHERE id = ? AND recipient_id = ?');
    $stmt->execute([$message_id, $patient_id]);
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$message) {
        echo json_encode(['success' => false, 'message' => 'Message not found or access denied']);
        exit;
    }
    
    // Delete the message
    $delete_stmt = $db->prepare('DELETE FROM messages WHERE id = ? AND recipient_id = ?');
    $result = $delete_stmt->execute([$message_id, $patient_id]);
    
    if ($result && $delete_stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Message deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete message or message not found']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
