<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    $searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $year = isset($_POST['year']) ? trim($_POST['year']) : '';
    $perPage = 10; // 10 entries per page for Issue Medication History
    
    // First, get the total count of records that match the search criteria
    $countSql = "SELECT COUNT(*) as total
                 FROM prescriptions p
                 LEFT JOIN users u ON p.prescribed_by = u.username
                 WHERE 1=1";
    
    $countParams = [];
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $countSql .= " AND (p.patient_name LIKE ? OR p.prescription_date LIKE ? OR p.reason LIKE ? OR u.name LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
    }
    
    // Add year filter condition if year is provided
    if (!empty($year)) {
        $countSql .= " AND YEAR(p.prescription_date) = ?";
        $countParams[] = $year;
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Now get the paginated results
    $sql = "SELECT DISTINCT 
                p.prescription_date,
                p.patient_name,
                p.medicines,
                p.reason,
                u.name as staff_name
            FROM prescriptions p
            LEFT JOIN users u ON p.prescribed_by = u.username
            WHERE 1=1";
    
    $params = [];
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $sql .= " AND (p.patient_name LIKE ? OR p.prescription_date LIKE ? OR p.reason LIKE ? OR u.name LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    
    // Add year filter condition if year is provided
    if (!empty($year)) {
        $sql .= " AND YEAR(p.prescription_date) = ?";
        $params[] = $year;
    }
    
    $sql .= " ORDER BY p.prescription_date DESC";
    $sql .= " LIMIT " . (($page - 1) * $perPage) . ", " . $perPage;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $allHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data - handle JSON medicines field
    $formattedHistory = [];
    foreach ($allHistory as $row) {
        $meds = json_decode($row['medicines'], true);
        
        if (is_array($meds) && !empty($meds)) {
            // Process each medicine in the JSON array
            foreach ($meds as $med) {
                $formattedHistory[] = [
                    'prescription_date' => $row['prescription_date'],
                    'patient_name' => htmlspecialchars(trim($row['patient_name'])),
                    'medicine' => htmlspecialchars($med['medicine'] ?? ''),
                    'quantity' => (int)($med['quantity'] ?? 0),
                    'reason' => htmlspecialchars($row['reason']),
                    'staff_name' => htmlspecialchars(trim($row['staff_name'] ?? $row['prescribed_by'] ?? 'Staff'))
                ];
                
                // Ensure we don't exceed the per_page limit
                if (count($formattedHistory) >= $perPage) {
                    break 2; // Break out of both loops
                }
            }
        } else {
            // Fallback if no medicines data
            $formattedHistory[] = [
                'prescription_date' => $row['prescription_date'],
                'patient_name' => htmlspecialchars(trim($row['patient_name'])),
                'medicine' => 'Unknown Medicine',
                'quantity' => 1,
                'reason' => htmlspecialchars($row['reason']),
                'staff_name' => htmlspecialchars(trim($row['staff_name'] ?? $row['prescribed_by'] ?? 'Staff'))
            ];
            
            // Ensure we don't exceed the per_page limit
            if (count($formattedHistory) >= $perPage) {
                break;
            }
        }
    }
    
    // Final safety check - limit to exactly per_page entries
    $formattedHistory = array_slice($formattedHistory, 0, $perPage);
    
    // Calculate actual pagination info based on returned entries
    $actualCount = count($formattedHistory);
    $startRecord = (($page - 1) * $perPage) + 1;
    $endRecord = $startRecord + $actualCount - 1;
    
    echo json_encode([
        'success' => true,
        'history' => $formattedHistory,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $perPage,
            'start_record' => $startRecord,
            'end_record' => $endRecord,
            'actual_count' => $actualCount
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
