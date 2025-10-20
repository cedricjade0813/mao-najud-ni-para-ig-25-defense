<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

try {
    $patient_id = $_POST['patient_id'] ?? '';
    $patient_type = $_POST['patient_type'] ?? '';
    
    if (empty($patient_id) || empty($patient_type)) {
        echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
        exit;
    }
    
    // Validate patient type
    if (!in_array($patient_type, ['imported_patients', 'faculty', 'visitor'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid patient type']);
        exit;
    }
    
    // Prepare data for update
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $religion = trim($_POST['religion'] ?? '');
    $citizenship = trim($_POST['citizenship'] ?? '');
    $course_program = trim($_POST['course_program'] ?? '');
    $guardian_name = trim($_POST['guardian_name'] ?? '');
    $guardian_contact = trim($_POST['guardian_contact'] ?? '');
    $emergency_contact_name = trim($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_number = trim($_POST['emergency_contact_number'] ?? '');
    $parent_email = trim($_POST['parent_email'] ?? '');
    $parent_phone = trim($_POST['parent_phone'] ?? '');
    
    if (empty($name)) {
        echo json_encode(['success' => false, 'message' => 'Name is required']);
        exit;
    }
    
    // Update based on patient type
    if ($patient_type === 'imported_patients') {
        // Update student patient
        $student_id = trim($_POST['student_id'] ?? '');
        $dob = trim($_POST['dob'] ?? '');
        $year_level = trim($_POST['year_level'] ?? '');
        $civil_status = trim($_POST['civil_status'] ?? '');
        
        $sql = "UPDATE imported_patients SET 
                name = :name,
                student_id = :student_id,
                dob = :dob,
                gender = :gender,
                year_level = :year_level,
                address = :address,
                civil_status = :civil_status,
                email = :email,
                contact_number = :contact_number,
                religion = :religion,
                citizenship = :citizenship,
                course_program = :course_program,
                guardian_name = :guardian_name,
                guardian_contact = :guardian_contact,
                emergency_contact_name = :emergency_contact_name,
                emergency_contact_number = :emergency_contact_number,
                parent_email = :parent_email,
                parent_phone = :parent_phone
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':dob', $dob);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':year_level', $year_level);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':civil_status', $civil_status);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':religion', $religion);
        $stmt->bindParam(':citizenship', $citizenship);
        $stmt->bindParam(':course_program', $course_program);
        $stmt->bindParam(':guardian_name', $guardian_name);
        $stmt->bindParam(':guardian_contact', $guardian_contact);
        $stmt->bindParam(':emergency_contact_name', $emergency_contact_name);
        $stmt->bindParam(':emergency_contact_number', $emergency_contact_number);
        $stmt->bindParam(':parent_email', $parent_email);
        $stmt->bindParam(':parent_phone', $parent_phone);
        $stmt->bindParam(':id', $patient_id);
        
    } elseif ($patient_type === 'faculty') {
        // Update faculty patient
        $age = trim($_POST['age'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $college_course = trim($_POST['college_course'] ?? '');
        $civil_status = trim($_POST['civil_status'] ?? '');
        $emergency_contact = trim($_POST['emergency_contact'] ?? '');
        
        $sql = "UPDATE faculty SET 
                full_name = :name,
                email = :email,
                contact = :contact_number,
                address = :address,
                gender = :gender,
                age = :age,
                department = :department,
                college_course = :college_course,
                civil_status = :civil_status,
                citizenship = :citizenship,
                emergency_contact = :emergency_contact
                WHERE faculty_id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':college_course', $college_course);
        $stmt->bindParam(':civil_status', $civil_status);
        $stmt->bindParam(':citizenship', $citizenship);
        $stmt->bindParam(':emergency_contact', $emergency_contact);
        $stmt->bindParam(':id', $patient_id);
        
    } elseif ($patient_type === 'visitor') {
        // Update visitor patient
        $age = trim($_POST['age'] ?? '');
        $emergency_contact_visitor = trim($_POST['emergency_contact_visitor'] ?? '');
        
        $sql = "UPDATE visitor SET 
                full_name = :name,
                contact = :contact_number,
                address = :address,
                gender = :gender,
                age = :age,
                emergency_contact = :emergency_contact_visitor
                WHERE visitor_id = :id";
        
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':contact_number', $contact_number);
        $stmt->bindParam(':address', $address);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':age', $age);
        $stmt->bindParam(':emergency_contact_visitor', $emergency_contact_visitor);
        $stmt->bindParam(':id', $patient_id);
    }
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Patient updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update patient']);
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
