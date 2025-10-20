<?php
session_start();
require_once 'includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in as patient or faculty
if (!isset($_SESSION['student_row_id']) && !isset($_SESSION['faculty_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['remove_patient_photo'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Determine user type and ID
    if (isset($_SESSION['student_row_id'])) {
        $user_id = $_SESSION['student_row_id'];
        $table = 'imported_patients';
        $id_column = 'id';
    } elseif (isset($_SESSION['faculty_id'])) {
        $user_id = $_SESSION['faculty_id'];
        $table = 'faculty';
        $id_column = 'faculty_id';
    }

    // Get current profile image
    $stmt = $db->prepare("SELECT profile_image FROM {$table} WHERE {$id_column} = ?");
    $stmt->execute([$user_id]);
    $current_image = $stmt->fetchColumn();

    // Delete the image file if it exists
    if ($current_image && file_exists($current_image)) {
        unlink($current_image);
    }

    // Update database to remove image path
    $stmt = $db->prepare("UPDATE {$table} SET profile_image = NULL WHERE {$id_column} = ?");
    $stmt->execute([$user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Profile photo removed successfully'
    ]);

} catch (Exception $e) {
    error_log('Patient photo removal error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while removing photo']);
}
?>
