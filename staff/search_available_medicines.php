<?php
include '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    $searchTerm = $_POST['search'] ?? '';
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $perPage = 10;
    
    // Build the base query for available medicines
    $today = date('Y-m-d');
    $baseQuery = "SELECT * FROM medicines WHERE expiry >= ? AND quantity > 0";
    $params = [$today];
    
    // Add search conditions
    if (!empty($searchTerm)) {
        $baseQuery .= " AND (name LIKE ? OR dosage LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params = array_merge($params, [$searchParam, $searchParam]);
    }
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM ($baseQuery) as filtered";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Calculate pagination
    $totalPages = ceil($totalRecords / $perPage);
    $offset = ($page - 1) * $perPage;
    
    // Get paginated data
    $dataQuery = $baseQuery . " ORDER BY name ASC LIMIT $perPage OFFSET $offset";
    $dataStmt = $db->prepare($dataQuery);
    $dataStmt->execute($params);
    $medicines = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format medicines data
    $formattedMedicines = [];
    foreach ($medicines as $med) {
        $formattedMedicines[] = [
            'id' => $med['id'],
            'name' => htmlspecialchars($med['name']),
            'dosage' => htmlspecialchars($med['dosage']),
            'quantity' => $med['quantity'],
            'expiry' => $med['expiry'],
            'formatted_expiry' => date('M d, Y', strtotime($med['expiry'])),
            'status' => 'Available',
            'status_class' => 'bg-green-100 text-green-800'
        ];
    }
    
    echo json_encode([
        'success' => true,
        'medicines' => $formattedMedicines,
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_records' => $totalRecords,
            'per_page' => $perPage,
            'start_record' => $offset + 1,
            'end_record' => min($offset + $perPage, $totalRecords)
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Search failed: ' . $e->getMessage()
    ]);
}
?>
