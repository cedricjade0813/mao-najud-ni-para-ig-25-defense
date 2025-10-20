<?php
include '../includes/db_connect.php';
session_start();

header('Content-Type: application/json');

try {
    $faculty_id = $_SESSION['faculty_id'];
    $search = isset($_POST['search']) ? trim($_POST['search']) : '';
    $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    $page = max($page, 1);
    
    // Fetch prescription data with doctor information
    $medicalHistory = [];
    $stmt = $db->prepare('
        SELECT 
            p.id,
            p.prescription_date,
            p.reason,
            p.medicines,
            p.prescribed_by,
            p.notes,
            ds.doctor_name,
            u.name as prescribed_by_name
        FROM prescriptions p
        LEFT JOIN doctor_schedules ds ON DATE(p.prescription_date) = ds.schedule_date
        LEFT JOIN users u ON p.prescribed_by = u.username
        WHERE p.patient_id = ? 
        ORDER BY p.prescription_date DESC
    ');
    $stmt->execute([$faculty_id]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process prescriptions and create medical history records (one row per prescription)
    foreach ($prescriptions as $prescription) {
        $date = date('M j, Y', strtotime($prescription['prescription_date']));
        $time = date('g:i A', strtotime($prescription['prescription_date']));
        $doctor = $prescription['doctor_name'] ?? 'Dr. Medical Officer';
        $reason = $prescription['reason'] ?? 'N/A';
        
        // Get medicines list for display
        $medicine_list = 'No medicine prescribed';
        if (!empty($prescription['medicines'])) {
            $medicines = json_decode($prescription['medicines'], true);
            if (is_array($medicines) && !empty($medicines)) {
                $medicine_names = array_map(function($med) {
                    return $med['medicine'] ?? 'Unknown';
                }, $medicines);
                $medicine_list = implode(', ', $medicine_names);
            }
        }
        
        // Create one record per prescription
        $medicalHistory[] = [
            'id' => $prescription['id'],
            'date' => $date,
            'time' => $time,
            'doctor' => $doctor,
            'reason' => $reason,
            'medicine' => $medicine_list,
            'prescribed_by' => $prescription['prescribed_by_name'] ?? $prescription['prescribed_by'] ?? 'Unknown',
            'quantity' => 'See details',
            'dosage' => 'See details',
            'frequency' => 'See details',
            'instructions' => 'See details',
            'prescription_data' => $prescription
        ];
    }
    
    // Apply search filter if provided
    if (!empty($search)) {
        $medicalHistory = array_filter($medicalHistory, function($record) use ($search) {
            return stripos($record['reason'], $search) !== false || 
                   stripos($record['medicine'], $search) !== false ||
                   stripos($record['doctor'], $search) !== false ||
                   stripos($record['prescribed_by'], $search) !== false ||
                   stripos($record['date'], $search) !== false;
        });
    }
    
    // Pagination
    $records_per_page = 10;
    $offset = ($page - 1) * $records_per_page;
    $total_records = count($medicalHistory);
    $total_pages = ceil($total_records / $records_per_page);
    $medicalHistory_paginated = array_slice($medicalHistory, $offset, $records_per_page);
    
    // Format the response
    $response = [
        'success' => true,
        'records' => $medicalHistory_paginated,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'per_page' => $records_per_page
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
