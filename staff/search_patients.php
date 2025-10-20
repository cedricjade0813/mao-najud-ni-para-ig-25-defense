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

// Get search parameters and pagination from POST data
$searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
$type = isset($_POST['type']) ? trim($_POST['type']) : 'all';
$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

try {
    $patients = [];
    $totalRecords = 0;
    
    // Build count queries for each type
    $countQueries = [];
    $dataQueries = [];
    
    if ($type === 'all' || $type === 'students') {
    if (empty($searchTerm)) {
            $countQueries[] = "SELECT COUNT(*) as count FROM imported_patients";
            $dataQueries[] = "SELECT id, student_id, name, dob, gender, address, email, contact_number, year_level, course_program, civil_status, religion, citizenship, guardian_name, guardian_contact, emergency_contact_name, emergency_contact_number, parent_email, parent_phone, 'imported_patients' as table_type FROM imported_patients ORDER BY name ASC LIMIT $perPage OFFSET $offset";
        } else {
            $searchPattern = '%' . $searchTerm . '%';
            $countQueries[] = "SELECT COUNT(*) as count FROM imported_patients WHERE name LIKE ? OR email LIKE ? OR contact_number LIKE ? OR student_id LIKE ?";
            $dataQueries[] = "SELECT id, student_id, name, dob, gender, address, email, contact_number, year_level, course_program, civil_status, religion, citizenship, guardian_name, guardian_contact, emergency_contact_name, emergency_contact_number, parent_email, parent_phone, 'imported_patients' as table_type FROM imported_patients WHERE name LIKE ? OR email LIKE ? OR contact_number LIKE ? OR student_id LIKE ? ORDER BY name ASC LIMIT $perPage OFFSET $offset";
        }
        }
        
        if ($type === 'all' || $type === 'faculty') {
        if (empty($searchTerm)) {
            $countQueries[] = "SELECT COUNT(*) as count FROM faculty";
            $dataQueries[] = "SELECT faculty_id as id, faculty_id, full_name as name, NULL as dob, age, gender, address, email, contact as contact_number, department, college_course, civil_status, citizenship, password, emergency_contact, 'faculty' as table_type FROM faculty ORDER BY full_name ASC LIMIT $perPage OFFSET $offset";
        } else {
            $searchPattern = '%' . $searchTerm . '%';
            $countQueries[] = "SELECT COUNT(*) as count FROM faculty WHERE full_name LIKE ? OR faculty_id LIKE ? OR contact LIKE ? OR email LIKE ?";
            $dataQueries[] = "SELECT faculty_id as id, faculty_id, full_name as name, NULL as dob, age, gender, address, email, contact as contact_number, department, college_course, civil_status, citizenship, password, emergency_contact, 'faculty' as table_type FROM faculty WHERE full_name LIKE ? OR faculty_id LIKE ? OR contact LIKE ? OR email LIKE ? ORDER BY full_name ASC LIMIT $perPage OFFSET $offset";
        }
        }
        
        if ($type === 'all' || $type === 'visitors') {
        if (empty($searchTerm)) {
            $countQueries[] = "SELECT COUNT(*) as count FROM visitor";
            $dataQueries[] = "SELECT visitor_id as id, visitor_id, full_name as name, NULL as dob, age, gender, address, NULL as email, contact as contact_number, NULL as purpose, emergency_contact, 'visitor' as table_type FROM visitor ORDER BY full_name ASC LIMIT $perPage OFFSET $offset";
    } else {
        $searchPattern = '%' . $searchTerm . '%';
            $countQueries[] = "SELECT COUNT(*) as count FROM visitor WHERE visitor_id LIKE ? OR full_name LIKE ? OR contact LIKE ?";
            $dataQueries[] = "SELECT visitor_id as id, visitor_id, full_name as name, NULL as dob, age, gender, address, NULL as email, contact as contact_number, NULL as purpose, emergency_contact, 'visitor' as table_type FROM visitor WHERE visitor_id LIKE ? OR full_name LIKE ? OR contact LIKE ? ORDER BY full_name ASC LIMIT $perPage OFFSET $offset";
        }
    }
        
    // Get total count for pagination
    $queryIndex = 0;
        if ($type === 'all' || $type === 'students') {
        if (empty($searchTerm)) {
            $stmt = $db->query($countQueries[$queryIndex]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalRecords += $result['count'];
        } else {
            $searchPattern = '%' . $searchTerm . '%';
            $stmt = $db->prepare($countQueries[$queryIndex]);
            $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalRecords += $result['count'];
        }
        $queryIndex++;
        }
        
        if ($type === 'all' || $type === 'faculty') {
        if (empty($searchTerm)) {
            $stmt = $db->query($countQueries[$queryIndex]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalRecords += $result['count'];
        } else {
            $searchPattern = '%' . $searchTerm . '%';
            $stmt = $db->prepare($countQueries[$queryIndex]);
            $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalRecords += $result['count'];
        }
        $queryIndex++;
        }
        
        if ($type === 'all' || $type === 'visitors') {
        if (empty($searchTerm)) {
            $stmt = $db->query($countQueries[$queryIndex]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalRecords += $result['count'];
        } else {
            $searchPattern = '%' . $searchTerm . '%';
            $stmt = $db->prepare($countQueries[$queryIndex]);
            $stmt->execute([$searchPattern, $searchPattern, $searchPattern]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalRecords += $result['count'];
        }
    }
    
    // Calculate total pages
    $totalPages = ceil($totalRecords / $perPage);
    
    // Fetch data for current page
    $queryIndex = 0;
    if ($type === 'all' || $type === 'students') {
        if (empty($searchTerm)) {
            $stmt = $db->query($dataQueries[$queryIndex]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $patients = array_merge($patients, $results);
        } else {
            $searchPattern = '%' . $searchTerm . '%';
            $stmt = $db->prepare($dataQueries[$queryIndex]);
            $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $patients = array_merge($patients, $results);
        }
        $queryIndex++;
    }
    
    if ($type === 'all' || $type === 'faculty') {
        if (empty($searchTerm)) {
            $stmt = $db->query($dataQueries[$queryIndex]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $patients = array_merge($patients, $results);
        } else {
            $searchPattern = '%' . $searchTerm . '%';
            $stmt = $db->prepare($dataQueries[$queryIndex]);
            $stmt->execute([$searchPattern, $searchPattern, $searchPattern, $searchPattern]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $patients = array_merge($patients, $results);
        }
        $queryIndex++;
    }
    
    if ($type === 'all' || $type === 'visitors') {
        if (empty($searchTerm)) {
            $stmt = $db->query($dataQueries[$queryIndex]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $patients = array_merge($patients, $results);
        } else {
            $searchPattern = '%' . $searchTerm . '%';
            $stmt = $db->prepare($dataQueries[$queryIndex]);
            $stmt->execute([$searchPattern, $searchPattern, $searchPattern]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $patients = array_merge($patients, $results);
        }
    }
    
    // Sort all patients by name
    usort($patients, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
    
    echo json_encode([
        'success' => true,
        'patients' => $patients,
        'count' => count($patients),
        'search_term' => $searchTerm,
        'type' => $type,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $perPage,
            'start_record' => $offset + 1,
            'end_record' => min($offset + $perPage, $totalRecords)
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
