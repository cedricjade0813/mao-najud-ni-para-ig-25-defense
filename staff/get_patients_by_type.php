<?php
session_start();
header('Content-Type: application/json');

// Database connection
try {
    include '../includes/db_connect.php';
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get the patient type and pagination from POST data
$type = isset($_POST['type']) ? trim($_POST['type']) : 'all';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$records_per_page = 10;
$offset = ($page - 1) * $records_per_page;

try {
    $patients = [];
    $total_count = 0;
    
    if ($type === 'all' || $type === 'students') {
        // Get total count for students
        $countStmt = $db->query('SELECT COUNT(*) FROM imported_patients');
        $students_count = $countStmt->fetchColumn();
        $total_count += $students_count;
        
        // Get students from imported_patients table with pagination
        if ($type === 'students') {
            $stmt = $db->prepare('SELECT id, student_id, name, dob, gender, address, email, contact_number, year_level, course_program, civil_status, religion, citizenship, guardian_name, guardian_contact, emergency_contact_name, emergency_contact_number, parent_email, parent_phone, "imported_patients" as table_type FROM imported_patients ORDER BY name ASC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // For 'all' type, get all students (will be paginated after combining)
            $stmt = $db->query('SELECT id, student_id, name, dob, gender, address, email, contact_number, year_level, course_program, civil_status, religion, citizenship, guardian_name, guardian_contact, emergency_contact_name, emergency_contact_number, parent_email, parent_phone, "imported_patients" as table_type FROM imported_patients ORDER BY name ASC');
            $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $patients = array_merge($patients, $students);
    }
    
    if ($type === 'all' || $type === 'faculty') {
        // Get total count for faculty
        $countStmt = $db->query('SELECT COUNT(*) FROM faculty');
        $faculty_count = $countStmt->fetchColumn();
        $total_count += $faculty_count;
        
        // Get faculty from faculty table with pagination
        if ($type === 'faculty') {
            $stmt = $db->prepare('SELECT faculty_id as id, faculty_id, full_name as name, NULL as dob, age, gender, address, email, contact as contact_number, department, college_course, civil_status, citizenship, password, emergency_contact, "faculty" as table_type FROM faculty ORDER BY full_name ASC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // For 'all' type, get all faculty (will be paginated after combining)
            $stmt = $db->query('SELECT faculty_id as id, faculty_id, full_name as name, NULL as dob, age, gender, address, email, contact as contact_number, department, college_course, civil_status, citizenship, password, emergency_contact, "faculty" as table_type FROM faculty ORDER BY full_name ASC');
            $faculty = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $patients = array_merge($patients, $faculty);
    }
    
    if ($type === 'all' || $type === 'visitors') {
        // Get total count for visitors
        $countStmt = $db->query('SELECT COUNT(*) FROM visitor');
        $visitors_count = $countStmt->fetchColumn();
        $total_count += $visitors_count;
        
        // Get visitors from visitor table with pagination
        if ($type === 'visitors') {
            $stmt = $db->prepare('SELECT visitor_id as id, visitor_id, full_name as name, NULL as dob, age, gender, address, NULL as email, contact as contact_number, NULL as purpose, emergency_contact, "visitor" as table_type FROM visitor ORDER BY full_name ASC LIMIT :limit OFFSET :offset');
            $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // For 'all' type, get all visitors (will be paginated after combining)
            $stmt = $db->query('SELECT visitor_id as id, visitor_id, full_name as name, NULL as dob, age, gender, address, NULL as email, contact as contact_number, NULL as purpose, emergency_contact, "visitor" as table_type FROM visitor ORDER BY full_name ASC');
            $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        $patients = array_merge($patients, $visitors);
    }
    
    // Sort all patients by name
    usort($patients, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    // For 'all' type, apply pagination after combining all data
    if ($type === 'all') {
        $total_pages = ceil($total_count / $records_per_page);
        $patients = array_slice($patients, $offset, $records_per_page);
    } else {
        $total_pages = ceil($total_count / $records_per_page);
    }
    
    echo json_encode([
        'success' => true,
        'patients' => $patients,
        'count' => count($patients),
        'type' => $type,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_count,
            'records_per_page' => $records_per_page,
            'start_record' => $offset + 1,
            'end_record' => min($offset + $records_per_page, $total_count)
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
