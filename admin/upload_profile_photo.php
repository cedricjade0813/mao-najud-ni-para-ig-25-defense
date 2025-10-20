<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Debug: Log the request
error_log("Upload request received: " . print_r($_FILES, true));

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['faculty_id']) && !isset($_SESSION['student_row_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

// Check if file was uploaded
if (!isset($_FILES['profile_photo']) || $_FILES['profile_photo']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error']);
    exit;
}

$file = $_FILES['profile_photo'];

// Validate file type
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
    exit;
}

// Validate file size (5MB max)
$maxSize = 5 * 1024 * 1024; // 5MB in bytes
if ($file['size'] > $maxSize) {
    echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 5MB.']);
    exit;
}

// Generate unique filename
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = uniqid('profile_', true) . '.' . $extension;
$uploadPath = '../uploads/profiles/' . $filename;

// Create uploads directory if it doesn't exist
if (!is_dir('../uploads/profiles/')) {
    mkdir('../uploads/profiles/', 0755, true);
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode(['success' => false, 'message' => 'Failed to save file']);
    exit;
}

// Update database with new profile image path
try {
    $imagePath = 'uploads/profiles/' . $filename;
    
    // Determine which table to update based on session
    if (isset($_SESSION['user_id'])) {
        // Admin user
        $stmt = $db->prepare('UPDATE users SET profile_image = ? WHERE id = ?');
        $stmt->execute([$imagePath, $_SESSION['user_id']]);
        $table = 'users';
        $id = $_SESSION['user_id'];
    } elseif (isset($_SESSION['faculty_id'])) {
        // Faculty user
        $stmt = $db->prepare('UPDATE faculty SET profile_image = ? WHERE faculty_id = ?');
        $stmt->execute([$imagePath, $_SESSION['faculty_id']]);
        $table = 'faculty';
        $id = $_SESSION['faculty_id'];
    } elseif (isset($_SESSION['student_row_id'])) {
        // Student user
        $stmt = $db->prepare('UPDATE imported_patients SET profile_image = ? WHERE id = ?');
        $stmt->execute([$imagePath, $_SESSION['student_row_id']]);
        $table = 'imported_patients';
        $id = $_SESSION['student_row_id'];
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile photo updated successfully',
        'image_path' => $imagePath,
        'table' => $table,
        'id' => $id
    ]);
    
} catch (PDOException $e) {
    // Delete the uploaded file if database update fails
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    error_log("Database error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    // Delete the uploaded file if any error occurs
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }
    error_log("General error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
