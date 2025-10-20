<?php
session_start();

header('Content-Type: application/json');

// Check if user is logged in as patient
if (!isset($_SESSION['student_row_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['clear_cache']) || $input['clear_cache'] !== '1') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Clear the patient data cache from session
    if (isset($_SESSION['patient_data'])) {
        unset($_SESSION['patient_data']);
    }
    
    // Also clear any unread cache
    if (isset($_SESSION['unread_cache_time'])) {
        unset($_SESSION['unread_cache_time']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Cache cleared successfully'
    ]);
    
} catch (Exception $e) {
    error_log('Cache clear error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while clearing cache']);
}
?>
