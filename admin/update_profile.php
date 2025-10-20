<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['faculty_id']) && !isset($_SESSION['student_row_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Get POST data
$name = trim($_POST['name'] ?? '');
$username = trim($_POST['username'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validate required fields
if (empty($name) || empty($username) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit;
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Determine which table to update based on session
    if (isset($_SESSION['user_id'])) {
        // Admin user - update users table
        $userId = $_SESSION['user_id'];
        
        // Check if username or email already exists (excluding current user)
        $checkStmt = $db->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?');
        $checkStmt->execute([$username, $email, $userId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Username or email already exists']);
            exit;
        }
        
        // Update user profile
        $updateStmt = $db->prepare('UPDATE users SET name = ?, username = ?, email = ? WHERE id = ?');
        $updateStmt->execute([$name, $username, $email, $userId]);
        
        // Update session data
        $_SESSION['username'] = $username;
        
    } elseif (isset($_SESSION['faculty_id'])) {
        // Faculty user - update faculty table
        $facultyId = $_SESSION['faculty_id'];
        
        // Check if email already exists (excluding current faculty)
        $checkStmt = $db->prepare('SELECT faculty_id FROM faculty WHERE email = ? AND faculty_id != ?');
        $checkStmt->execute([$email, $facultyId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }
        
        // Update faculty profile
        $updateStmt = $db->prepare('UPDATE faculty SET full_name = ?, email = ? WHERE faculty_id = ?');
        $updateStmt->execute([$name, $email, $facultyId]);
        
    } elseif (isset($_SESSION['student_row_id'])) {
        // Student user - update imported_patients table
        $studentId = $_SESSION['student_row_id'];
        
        // Check if email already exists (excluding current student)
        $checkStmt = $db->prepare('SELECT id FROM imported_patients WHERE email = ? AND id != ?');
        $checkStmt->execute([$email, $studentId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email already exists']);
            exit;
        }
        
        // Update student profile
        $updateStmt = $db->prepare('UPDATE imported_patients SET name = ?, email = ? WHERE id = ?');
        $updateStmt->execute([$name, $email, $studentId]);
        
        // Update session data
        $_SESSION['student_name'] = $name;
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile updated successfully',
        'data' => [
            'name' => $name,
            'username' => $username,
            'email' => $email
        ]
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error occurred']);
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating profile: ' . $e->getMessage()]);
}
?>
