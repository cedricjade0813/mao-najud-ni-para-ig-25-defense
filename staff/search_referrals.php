<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    $searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $perPage = 10; // 10 entries per page for Referral Records
    
    // First, get the total count of records that match the search criteria
    $countSql = "SELECT COUNT(*) as total
                 FROM medication_referrals mr 
                 LEFT JOIN users u ON mr.recorded_by = u.username
                 WHERE 1=1";
    
    $countParams = [];
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $countSql .= " AND (mr.patient_name LIKE ? OR mr.faculty_name LIKE ? OR mr.visitor_name LIKE ? OR u.name LIKE ? OR mr.referral_to LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Now get the paginated results
    $sql = "SELECT mr.*, ip.year_level,
               ip.student_id as matched_student_id,
               mr.patient_id as referral_patient_id,
               COALESCE(u.name, mr.recorded_by) as recorded_by_name,
               COALESCE(vs_patient.body_temp, vs_faculty.body_temp, vs_visitor.body_temp) AS body_temp,
               COALESCE(vs_patient.resp_rate, vs_faculty.resp_rate, vs_visitor.resp_rate) AS resp_rate,
               COALESCE(vs_patient.pulse, vs_faculty.pulse, vs_visitor.pulse) AS pulse,
               COALESCE(vs_patient.blood_pressure, vs_faculty.blood_pressure, vs_visitor.blood_pressure) AS blood_pressure,
               COALESCE(vs_patient.weight, vs_faculty.weight, vs_visitor.weight) AS weight,
               COALESCE(vs_patient.height, vs_faculty.height, vs_visitor.height) AS height,
               COALESCE(vs_patient.oxygen_sat, vs_faculty.oxygen_sat, vs_visitor.oxygen_sat) AS oxygen_sat
        FROM medication_referrals mr 
        LEFT JOIN users u ON mr.recorded_by = u.username
        LEFT JOIN imported_patients ip ON 
            CAST(TRIM(mr.patient_id) AS CHAR) = CAST(TRIM(ip.student_id) AS CHAR)
            OR TRIM(mr.patient_name) = TRIM(ip.name)
        LEFT JOIN (
            SELECT patient_id, body_temp, resp_rate, pulse, blood_pressure, weight, height, oxygen_sat,
                   ROW_NUMBER() OVER (PARTITION BY patient_id ORDER BY created_at DESC) as rn
            FROM vital_signs
        ) vs_patient ON CAST(TRIM(mr.patient_id) AS CHAR) = CAST(TRIM(vs_patient.patient_id) AS CHAR) AND vs_patient.rn = 1
        LEFT JOIN (
            SELECT faculty_id, body_temp, resp_rate, pulse, blood_pressure, weight, height, oxygen_sat,
                   ROW_NUMBER() OVER (PARTITION BY faculty_id ORDER BY created_at DESC) as rn
            FROM vital_signs
        ) vs_faculty ON CAST(TRIM(mr.faculty_id) AS CHAR) = CAST(TRIM(vs_faculty.faculty_id) AS CHAR) AND vs_faculty.rn = 1
        LEFT JOIN (
            SELECT visitor_id, body_temp, resp_rate, pulse, blood_pressure, weight, height, oxygen_sat,
                   ROW_NUMBER() OVER (PARTITION BY visitor_id ORDER BY created_at DESC) as rn
            FROM vital_signs
        ) vs_visitor ON CAST(TRIM(mr.visitor_id) AS CHAR) = CAST(TRIM(vs_visitor.visitor_id) AS CHAR) AND vs_visitor.rn = 1
        WHERE 1=1";
    
    $params = [];
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $sql .= " AND (mr.patient_name LIKE ? OR mr.faculty_name LIKE ? OR mr.visitor_name LIKE ? OR u.name LIKE ? OR mr.referral_to LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    
    $sql .= " ORDER BY mr.created_at DESC";
    $sql .= " LIMIT " . (($page - 1) * $perPage) . ", " . $perPage;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data
    $formattedReferrals = [];
    foreach ($referrals as $referral) {
        // Determine entity type and display name/id/level
        $entityType = 'Student';
        $displayName = $referral['patient_name'] ?? '';
        $displayId = $referral['patient_id'] ?? '';
        $yearLevel = $referral['year_level'] ?? '';
        
        if (!empty($referral['faculty_id'])) {
            $entityType = 'Teacher';
            $displayName = $referral['faculty_name'] ?? '';
            $displayId = $referral['faculty_id'] ?? '';
            $yearLevel = 'Teacher';
        } elseif (!empty($referral['visitor_id'])) {
            $entityType = 'Visitor';
            $displayName = $referral['visitor_name'] ?? '';
            $displayId = $referral['visitor_id'] ?? '';
            $yearLevel = 'Visitor';
        }
        
        // Generate referrer name (staff member who recorded)
        $referrerName = $referral['recorded_by_name'] ?? 'Staff Member';
        
        // Determine status based on referral_to field
        $status = !empty($referral['referral_to']) ? 'Referred' : 'Pending';
        
        // Show referral destination or "Not specified"
        $referralDestination = !empty($referral['referral_to']) ? $referral['referral_to'] : 'Not specified';
        
        $formattedReferrals[] = [
            'id' => $referral['id'],
            'display_name' => htmlspecialchars($displayName),
            'display_id' => htmlspecialchars($displayId),
            'entity_type' => $entityType,
            'year_level' => htmlspecialchars($yearLevel ?: $entityType),
            'recorded_by' => htmlspecialchars($referral['recorded_by'] ?? 'Staff'),
            'recorded_by_name' => htmlspecialchars($referrerName),
            'status' => $status,
            'created_at' => $referral['created_at'],
            'referral_to' => htmlspecialchars($referralDestination),
            'subjective' => htmlspecialchars($referral['subjective'] ?? ''),
            'objective' => htmlspecialchars($referral['objective'] ?? ''),
            'assessment' => htmlspecialchars($referral['assessment'] ?? ''),
            'plan' => htmlspecialchars($referral['plan'] ?? ''),
            'intervention' => htmlspecialchars($referral['intervention'] ?? ''),
            'evaluation' => htmlspecialchars($referral['evaluation'] ?? ''),
            'body_temp' => htmlspecialchars($referral['body_temp'] ?? ''),
            'resp_rate' => htmlspecialchars($referral['resp_rate'] ?? ''),
            'pulse' => htmlspecialchars($referral['pulse'] ?? ''),
            'blood_pressure' => htmlspecialchars($referral['blood_pressure'] ?? ''),
            'weight' => htmlspecialchars($referral['weight'] ?? ''),
            'height' => htmlspecialchars($referral['height'] ?? ''),
            'oxygen_sat' => htmlspecialchars($referral['oxygen_sat'] ?? '')
        ];
    }
    
    echo json_encode([
        'success' => true,
        'referrals' => $formattedReferrals,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $perPage
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
