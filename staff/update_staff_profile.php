<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) && !isset($_SESSION['faculty_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['update_staff_profile'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Determine user type and ID
    $user_id = null;
    $table = null;
    $id_field = null;
    
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        $table = 'users';
        $id_field = 'id';
    } elseif (isset($_SESSION['faculty_id'])) {
        $user_id = $_SESSION['faculty_id'];
        $table = 'faculty';
        $id_field = 'faculty_id';
    }
    
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $department = trim($_POST['department'] ?? '');

    // Validate required fields
    if (empty($name) || empty($username) || empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Name, username, and email are required']);
        exit;
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Check if username or email already exists (excluding current user)
    $stmt = $db->prepare("SELECT {$id_field} FROM {$table} WHERE (username = ? OR email = ?) AND {$id_field} != ?");
    $stmt->execute([$username, $email, $user_id]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
        exit;
    }

    // Update staff profile based on table
    if ($table === 'users') {
        $stmt = $db->prepare('
            UPDATE users 
            SET name = ?, username = ?, email = ?, phone = ?, address = ?, department = ?
            WHERE id = ?
        ');
        $stmt->execute([$name, $username, $email, $phone, $address, $department, $user_id]);
    } else { // faculty table
        $stmt = $db->prepare('
            UPDATE faculty 
            SET full_name = ?, username = ?, email = ?, phone = ?, address = ?, department = ?
            WHERE faculty_id = ?
        ');
        $stmt->execute([$name, $username, $email, $phone, $address, $department, $user_id]);
    }

    // Update session data
    $_SESSION['username'] = $username;

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);

} catch (Exception $e) {
    error_log('Staff profile update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating profile: ' . $e->getMessage()]);
}
?>
