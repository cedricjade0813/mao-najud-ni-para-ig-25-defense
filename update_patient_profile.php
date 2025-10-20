<?php
session_start();
require_once 'includes/db_connect.php';

header('Content-Type: application/json');

// Check if user is logged in as patient or faculty
if (!isset($_SESSION['student_row_id']) && !isset($_SESSION['faculty_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['update_patient_profile'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

try {
    // Determine user type and ID
    if (isset($_SESSION['student_row_id'])) {
        $user_id = $_SESSION['student_row_id'];
        $user_type = 'student';
        $table = 'imported_patients';
        $id_column = 'id';
    } elseif (isset($_SESSION['faculty_id'])) {
        $user_id = $_SESSION['faculty_id'];
        $user_type = 'faculty';
        $table = 'faculty';
        $id_column = 'faculty_id';
    }
    
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $civil_status = trim($_POST['civil_status'] ?? '');

    // Validate required fields
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        exit;
    }

    // Validate email format if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email format']);
        exit;
    }

    // Update profile based on user type
    if ($user_type === 'student') {
        $stmt = $db->prepare('
            UPDATE imported_patients 
            SET name = ?, dob = ?, gender = ?, year_level = ?, address = ?, civil_status = ?
            WHERE id = ?
        ');
        
        $stmt->execute([
            $name,
            $dob ?: null,
            $gender ?: null,
            $year_level ?: null,
            $address ?: null,
            $civil_status ?: null,
            $user_id
        ]);
    } else { // faculty
        $stmt = $db->prepare('
            UPDATE faculty 
            SET full_name = ?, address = ?, civil_status = ?
            WHERE faculty_id = ?
        ');
        
        $stmt->execute([
            $name,
            $address ?: null,
            $civil_status ?: null,
            $user_id
        ]);
    }

    // Update session data
    if ($user_type === 'student') {
        $_SESSION['patient_data']['name'] = $name;
        $_SESSION['patient_data']['dob'] = $dob;
        $_SESSION['patient_data']['gender'] = $gender;
        $_SESSION['patient_data']['year_level'] = $year_level;
        $_SESSION['patient_data']['address'] = $address;
        $_SESSION['patient_data']['civil_status'] = $civil_status;
    } else { // faculty
        $_SESSION['faculty_name'] = $name;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Profile updated successfully'
    ]);

} catch (Exception $e) {
    error_log('Patient profile update error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while updating profile']);
}
?>
