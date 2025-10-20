<?php
session_start();
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id']) && !isset($_SESSION['faculty_id']) && !isset($_SESSION['student_row_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

try {
    // Get current profile image path
    $currentImagePath = null;
    
    if (isset($_SESSION['user_id'])) {
        // Admin user
        $stmt = $db->prepare('SELECT profile_image FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentImagePath = $user['profile_image'] ?? null;
        
        // Update database to remove profile image
        $updateStmt = $db->prepare('UPDATE users SET profile_image = NULL WHERE id = ?');
        $updateStmt->execute([$_SESSION['user_id']]);
        
    } elseif (isset($_SESSION['faculty_id'])) {
        // Faculty user
        $stmt = $db->prepare('SELECT profile_image FROM faculty WHERE faculty_id = ?');
        $stmt->execute([$_SESSION['faculty_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentImagePath = $user['profile_image'] ?? null;
        
        // Update database to remove profile image
        $updateStmt = $db->prepare('UPDATE faculty SET profile_image = NULL WHERE faculty_id = ?');
        $updateStmt->execute([$_SESSION['faculty_id']]);
        
    } elseif (isset($_SESSION['student_row_id'])) {
        // Student user
        $stmt = $db->prepare('SELECT profile_image FROM imported_patients WHERE id = ?');
        $stmt->execute([$_SESSION['student_row_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $currentImagePath = $user['profile_image'] ?? null;
        
        // Update database to remove profile image
        $updateStmt = $db->prepare('UPDATE imported_patients SET profile_image = NULL WHERE id = ?');
        $updateStmt->execute([$_SESSION['student_row_id']]);
    }
    
    // Delete the physical file if it exists
    if ($currentImagePath && file_exists('../' . $currentImagePath)) {
        unlink('../' . $currentImagePath);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Profile photo removed successfully'
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
