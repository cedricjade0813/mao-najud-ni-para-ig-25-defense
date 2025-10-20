<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) && !isset($_SESSION['faculty_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['remove_staff_photo'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Determine user type and ID
    $user_id = null;
    $table = null;
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $table = 'users';
    } elseif (isset($_SESSION['faculty_id'])) {
        $user_id = $_SESSION['faculty_id'];
        $table = 'faculty';
    }

    // Get current profile image
    $stmt = $db->prepare("SELECT profile_image FROM {$table} WHERE " . ($table === 'users' ? 'id' : 'faculty_id') . " = ?");
    $stmt->execute([$user_id]);
    $current_image = $stmt->fetchColumn();

    // Delete the image file if it exists
    if ($current_image && file_exists($current_image)) {
        unlink($current_image);
    }

    // Update database to remove image path
    $stmt = $db->prepare("UPDATE {$table} SET profile_image = NULL WHERE " . ($table === 'users' ? 'id' : 'faculty_id') . " = ?");
    $stmt->execute([$user_id]);

    echo json_encode([
        'success' => true,
        'message' => 'Profile photo removed successfully'
    ]);

} catch (Exception $e) {
    error_log('Staff photo removal error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while removing photo']);
}
?>
