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
    
    // Start transaction
    $db->beginTransaction();
    
    try {
        // Delete based on patient type
        if ($patient_type === 'imported_patients') {
            // Delete student patient and related records
            $tables_to_clean = [
                'prescriptions' => 'patient_id',
                'appointments' => 'student_id'
            ];
            
            // Delete related records first
            foreach ($tables_to_clean as $table => $column) {
                $stmt = $db->prepare("DELETE FROM {$table} WHERE {$column} = :id");
                $stmt->bindParam(':id', $patient_id);
                $stmt->execute();
            }
            
            // Delete vital signs records (using patient_id for students)
            $stmt = $db->prepare("DELETE FROM vital_signs WHERE patient_id = :id");
            $stmt->bindParam(':id', $patient_id);
            $stmt->execute();
            
            // Delete the patient record
            $stmt = $db->prepare("DELETE FROM imported_patients WHERE id = :id");
            $stmt->bindParam(':id', $patient_id);
            $stmt->execute();
            
        } elseif ($patient_type === 'faculty') {
            // Delete faculty patient and related records
            $tables_to_clean = [
                'prescriptions' => 'patient_id',
                'appointments' => 'student_id'
            ];
            
            // Delete related records first
            foreach ($tables_to_clean as $table => $column) {
                $stmt = $db->prepare("DELETE FROM {$table} WHERE {$column} = :id");
                $stmt->bindParam(':id', $patient_id);
                $stmt->execute();
            }
            
            // Delete vital signs records (using faculty_id for faculty)
            $stmt = $db->prepare("DELETE FROM vital_signs WHERE faculty_id = :id");
            $stmt->bindParam(':id', $patient_id);
            $stmt->execute();
            
            // Delete the faculty record
            $stmt = $db->prepare("DELETE FROM faculty WHERE faculty_id = :id");
            $stmt->bindParam(':id', $patient_id);
            $stmt->execute();
            
        } elseif ($patient_type === 'visitor') {
            // Delete visitor patient and related records
            $tables_to_clean = [
                'prescriptions' => 'patient_id',
                'appointments' => 'student_id'
            ];
            
            // Delete related records first
            foreach ($tables_to_clean as $table => $column) {
                $stmt = $db->prepare("DELETE FROM {$table} WHERE {$column} = :id");
                $stmt->bindParam(':id', $patient_id);
                $stmt->execute();
            }
            
            // Delete vital signs records (using visitor_id for visitors)
            $stmt = $db->prepare("DELETE FROM vital_signs WHERE visitor_id = :id");
            $stmt->bindParam(':id', $patient_id);
            $stmt->execute();
            
            // Delete the visitor record
            $stmt = $db->prepare("DELETE FROM visitor WHERE visitor_id = :id");
            $stmt->bindParam(':id', $patient_id);
            $stmt->execute();
        }
        
        // Commit transaction
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Patient deleted successfully']);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollback();
        throw $e;
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
