<?php
require_once '../includes/db_connect.php';

header('Content-Type: application/json');

try {
    $searchTerm = isset($_POST['search']) ? trim($_POST['search']) : '';
    $page = isset($_POST['page']) ? max(1, intval($_POST['page'])) : 1;
    $perPage = 5; // Same as the original pagination
    
    // Build the query for counting total records (grouped by name only)
    $countSql = "SELECT COUNT(DISTINCT TRIM(LOWER(m.name))) as total
                 FROM medicines m 
                 WHERE m.quantity > 0 
                 AND (m.expiry IS NULL OR m.expiry = '' OR m.expiry = '0000-00-00' OR m.expiry > CURDATE())";
    
    $countParams = [];
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $countSql .= " AND (m.name LIKE ? OR m.dosage LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $countParams[] = $searchPattern;
        $countParams[] = $searchPattern;
    }
    
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($countParams);
    $totalRecords = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRecords / $perPage);
    
    // Build the main query with pagination (grouped by name only)
    $sql = "SELECT TRIM(m.name) as name,
                m.dosage,
                SUM(m.quantity) as quantity,
                MIN(m.expiry) as expiry
            FROM medicines m 
            WHERE m.quantity > 0 
            AND (m.expiry IS NULL OR m.expiry = '' OR m.expiry = '0000-00-00' OR m.expiry > CURDATE())";
    
    $params = [];
    
    // Add search condition if search term is provided
    if (!empty($searchTerm)) {
        $sql .= " AND (m.name LIKE ? OR m.dosage LIKE ?)";
        $searchPattern = '%' . $searchTerm . '%';
        $params[] = $searchPattern;
        $params[] = $searchPattern;
    }
    
    $sql .= " GROUP BY TRIM(LOWER(m.name)) ORDER BY m.name ASC";
    $sql .= " LIMIT " . (($page - 1) * $perPage) . ", " . $perPage;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $medicines = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format the data with proper capitalization
    $formattedMedicines = [];
    foreach ($medicines as $medicine) {
        $cleanName = trim(preg_replace('/\s+/', ' ', $medicine['name'])); // Remove extra whitespace
        $cleanName = ucfirst(strtolower($cleanName)); // Proper capitalization
        
        $formattedMedicines[] = [
            'name' => htmlspecialchars($cleanName),
            'dosage' => htmlspecialchars($medicine['dosage']),
            'quantity' => (int)$medicine['quantity'],
            'expiry' => htmlspecialchars($medicine['expiry'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'medicines' => $formattedMedicines,
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
