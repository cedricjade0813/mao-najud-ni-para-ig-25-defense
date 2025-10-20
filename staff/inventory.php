<?php
include '../includes/db_connect.php';
include '../includes/header.php';
// Create medicines table if not exists
try {
    
    $db->exec("CREATE TABLE IF NOT EXISTS medicines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        dosage VARCHAR(255) NOT NULL,
        quantity INT NOT NULL,
        expiry DATE NOT NULL
    )");
    // Fetch all medicines for the table, grouped by name and sum quantities
    $medicines = $db->query('SELECT TRIM(name) as name, dosage, SUM(quantity) as total_quantity, MIN(expiry) as earliest_expiry FROM medicines GROUP BY TRIM(LOWER(name)) ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Fetch prescription history for Issue Medication History
$prescriptionHistory = [];
try {
    // Get year filter parameter
    $filterYear = isset($_GET['year']) ? $_GET['year'] : '';
    
    // Build query with year filtering and staff information from users table
    $query = 'SELECT p.prescription_date, p.patient_name, p.medicines, p.reason, p.prescribed_by,
                     u.name as staff_name
              FROM prescriptions p
              LEFT JOIN users u ON p.prescribed_by = u.username';
    $params = [];
    
    if ($filterYear) {
        $query .= ' WHERE YEAR(p.prescription_date) = ?';
        $params = [$filterYear];
    }
    
    $query .= ' ORDER BY p.prescription_date DESC';
    
    $prescStmt = $db->prepare($query);
    $prescStmt->execute($params);
    $prescriptionHistory = $prescStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log first record to see structure
    if (count($prescriptionHistory) > 0) {
        error_log("First prescription record: " . print_r($prescriptionHistory[0], true));
    }
    
} catch (Exception $e) {
    error_log("Prescription Query Error: " . $e->getMessage());
    $prescriptionHistory = [];
}

// Pagination for Issue Medication History
// Flatten prescription history so each medicine entry is a separate row (even for same patient)
$flatPrescriptionHistory = [];
foreach ($prescriptionHistory as $presc) {
    $date = $presc['prescription_date'];
    $patient = $presc['patient_name'];
    $reason = $presc['reason'] ?? 'N/A';
    
    // Try to decode medicines field - handle different formats
    $meds = null;
    if (isset($presc['medicines'])) {
    $meds = json_decode($presc['medicines'], true);
    }
    
    // If medicines is not JSON or empty, try to create a single entry
    if (!is_array($meds) || empty($meds)) {
        // Create a single entry with available data
        $flatPrescriptionHistory[] = [
            'prescription_date' => $date,
            'patient_name' => $patient,
            'reason' => $reason,
            'medicine' => $presc['medicine'] ?? 'Unknown Medicine',
            'quantity' => $presc['quantity'] ?? '1',
            'staff_name' => $presc['staff_name'] ?? $presc['prescribed_by'] ?? 'Staff'
        ];
    } else {
        // Process JSON medicines array
        foreach ($meds as $med) {
            $flatPrescriptionHistory[] = [
                'prescription_date' => $date,
                'patient_name' => $patient,
                'reason' => $reason,
                'medicine' => $med['medicine'] ?? '',
                'quantity' => $med['quantity'] ?? '',
                'staff_name' => $presc['staff_name'] ?? $presc['prescribed_by'] ?? 'Staff'
            ];
        }
    }
}

// Debug: Log flattened data
error_log("Flattened prescription count: " . count($flatPrescriptionHistory));
if (count($flatPrescriptionHistory) > 0) {
    error_log("First flattened record: " . print_r($flatPrescriptionHistory[0], true));
}

$historyPage = isset($_GET['history_page']) ? max(1, intval($_GET['history_page'])) : 1;
$historyPerPage = 10; // 10 entries per page for Issue Medication History
$historyTotal = count($flatPrescriptionHistory);
$historyTotalPages = ceil($historyTotal / $historyPerPage);
$historyStart = ($historyPage - 1) * $historyPerPage;
$historyPageData = array_slice($flatPrescriptionHistory, $historyStart, $historyPerPage);
?>
<!-- Dashboard Content -->
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Inventory Management</h1>
        <p class="text-gray-600">Manage medicine stock and track medication history</p>
        </div>
    <!-- Medicine Stock Available Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
        <!-- Section Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800">Medicine Stock Available</h3>
            <div class="flex items-center gap-4">
                <div class="relative">
                    <input id="medicineSearch" type="text" placeholder="Search medicines..." 
                           class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white h-10">
                    <i class="ri-search-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
    </div>
                <button id="addMedBtn" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 flex items-center gap-2 transition-colors h-10">
                    <i class="ri-add-line"></i>
                    Add Medicine
                </button>
            </div>
        </div>
        
        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table id="medicineTable" class="w-full divide-y divide-gray-200 table-fixed">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-2/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine Name</th>
                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dosage</th>
                        <th class="w-1/8 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Expiry Date</th>
                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="medicineTableBody">
                    <?php
                    // Process medicines (already grouped by SQL query)
                    $stockMap = [];
                    foreach ($medicines as $med) {
                        $cleanName = trim(preg_replace('/\s+/', ' ', $med['name'])); // Remove extra whitespace
                        $cleanName = ucfirst(strtolower($cleanName)); // Proper capitalization
                        
                        $stockMap[] = [
                            'name' => $cleanName,
                            'dosage' => $med['dosage'],
                            'quantity' => (int)$med['total_quantity'],
                            'expiry' => $med['earliest_expiry'],
                            'category' => 'General Medicine', // Default category
                            'unit' => 'units', // Default unit
                            'batch' => 'BATCH' . str_pad($med['id'] ?? 1, 3, '0', STR_PAD_LEFT),
                            'supplier' => 'Pharmacy Supplier' // Default supplier
                        ];
                    }
                    
                    // Filter out 0 stock and expired medicines
                    $filteredStockMap = [];
                    foreach ($stockMap as $medicine) {
                        // Skip medicines with 0 stock
                        if ($medicine['quantity'] <= 0) {
                            continue;
                        }
                        
                        // Skip expired medicines
                        $expiryDate = new DateTime($medicine['expiry']);
                        $today = new DateTime();
                        if ($expiryDate < $today) {
                            continue;
                        }
                        
                        $filteredStockMap[] = $medicine;
                    }
                    
                    // Pagination logic
                    $medicine_total_records = count($filteredStockMap);
                    $medicine_records_per_page = 5;
                    $medicine_page = isset($_GET['medicine_page']) ? max(1, intval($_GET['medicine_page'])) : 1;
                    $medicine_total_pages = ceil($medicine_total_records / $medicine_records_per_page);
                    $medicine_offset = ($medicine_page - 1) * $medicine_records_per_page;
                    $medicine_stock_data = array_slice($filteredStockMap, $medicine_offset, $medicine_records_per_page);
                    
                    if (!empty($medicine_stock_data)) {
                        foreach ($medicine_stock_data as $medicine) {
                            $status = $medicine['quantity'] > 100 ? 'In Stock' : ($medicine['quantity'] > 20 ? 'Medium Stock' : 'Low Stock');
                            $statusClass = $status === 'In Stock' ? 'bg-blue-600 text-white' : ($status === 'Medium Stock' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800');
                            
                            echo '<tr class="hover:bg-blue-50" data-name="' . htmlspecialchars(strtolower($medicine['name'])) . '">';
                            echo '<td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="' . htmlspecialchars($medicine['name']) . '">' . htmlspecialchars($medicine['name']) . '</td>';
                            echo '<td class="px-6 py-4 text-sm text-gray-500 truncate" title="' . htmlspecialchars($medicine['dosage']) . '">' . htmlspecialchars($medicine['dosage']) . '</td>';
                            echo '<td class="px-6 py-4 text-sm text-gray-900 text-left">' . htmlspecialchars($medicine['quantity']) . '</td>';
                            echo '<td class="px-6 py-4 text-sm text-gray-500">' . htmlspecialchars($medicine['expiry']) . '</td>';
                            echo '<td class="px-6 py-4 whitespace-nowrap">';
                            echo '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $statusClass . '">';
                            echo '<i class="ri-medicine-bottle-line mr-1"></i>' . htmlspecialchars($status);
                            echo '</span>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No available medicines</p><p class="text-gray-400 text-xs">All medicines are out of stock or expired</p></div></td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
            <!-- Pagination and Records Info for Medicine Stock Available -->
        <div class="flex justify-between items-center mt-6 px-6 py-4">
                <div class="text-sm text-gray-600">
                    <?php 
                    $medicine_start = $medicine_offset + 1;
                    $medicine_end = min($medicine_offset + $medicine_records_per_page, $medicine_total_records);
                    ?>
                    Showing <?php echo $medicine_start; ?> to <?php echo $medicine_end; ?> of <?php echo $medicine_total_records; ?> entries
                </div>
                <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">
                    <?php if ($medicine_page > 1): ?>
                    <a href="?medicine_page=<?php echo $medicine_page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"></path>
                            </svg>
                            <span class="sr-only">Previous</span>
                        </a>
                    <?php else: ?>
                    <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Previous">
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"></path>
                            </svg>
                            <span class="sr-only">Previous</span>
                        </button>
                    <?php endif; ?>
                    <?php
                    $medicine_start_page = max(1, $medicine_page - 2);
                    $medicine_end_page = min($medicine_total_pages, $medicine_page + 2);
                    if ($medicine_start_page > 1): ?>
                    <a href="?medicine_page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">1</a>
                        <?php if ($medicine_start_page > 2): ?>
                            <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $medicine_start_page; $i <= $medicine_end_page; $i++): ?>
                        <?php if ($i == $medicine_page): ?>
                        <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300" aria-current="page"><?php echo $i; ?></button>
                        <?php else: ?>
                        <a href="?medicine_page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($medicine_end_page < $medicine_total_pages): ?>
                        <?php if ($medicine_end_page < $medicine_total_pages - 1): ?>
                            <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                        <?php endif; ?>
                    <a href="?medicine_page=<?php echo $medicine_total_pages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $medicine_total_pages; ?></a>
                    <?php endif; ?>
                    <?php if ($medicine_page < $medicine_total_pages): ?>
                    <a href="?medicine_page=<?php echo $medicine_page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
                            <span class="sr-only">Next</span>
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    <?php else: ?>
                    <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Next">
                            <span class="sr-only">Next</span>
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    <!-- Issue Medication History Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
        <!-- Section Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-800">Issue Medication History</h3>
            
            <div class="flex items-center gap-4">
                <!-- Search Bar -->
                <div class="relative">
                    <input id="medicationSearchInput" type="text" 
                           class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" 
                           placeholder="Search history...">
                    <i class="ri-search-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
                
                <!-- Year Filter -->
                <div class="flex items-center gap-2">
                    <form method="GET" class="flex items-center gap-2">
                        <select name="year" id="year" class="border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                            <option value="">All Years</option>
                            <?php
                            $currentYear = date('Y');
                            for ($year = $currentYear; $year >= 2020; $year--) {
                                $selected = ($filterYear == $year) ? 'selected' : '';
                                echo "<option value='{$year}' {$selected}>{$year}</option>";
                            }
                            ?>
                        </select>
                        <?php if ($filterYear): ?>
                            <a href="inventory.php" class="text-sm text-gray-500 hover:text-gray-700 flex items-center">
                                <i class="ri-close-line"></i>
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Table Content -->
        <div class="overflow-x-auto">
            <table id="issueHistoryTable" class="w-full divide-y divide-gray-200 table-fixed">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine Name</th>
                        <th class="w-1/5 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Name</th>
                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        <th class="w-1/12 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Quantity</th>
                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issue Date</th>
                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Issued By</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="historyTableBody">
                    <?php
                    if (!empty($historyPageData)) {
                        foreach ($historyPageData as $idx => $row) {
                            $date = htmlspecialchars($row['prescription_date']);
                            $patient = htmlspecialchars($row['patient_name']);
                            $reason = htmlspecialchars($row['reason']);
                            $medName = htmlspecialchars($row['medicine']);
                            $qty = htmlspecialchars($row['quantity']);
                            
                            // Format date
                            $formattedDate = date('M j, Y', strtotime($date));
                            
                            echo '<tr class="hover:bg-blue-50" data-patient="' . htmlspecialchars(strtolower($patient)) . '" data-medicine="' . htmlspecialchars(strtolower($medName)) . '" data-date="' . htmlspecialchars($date) . '">';
                            echo '<td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="' . htmlspecialchars($medName) . '">' . $medName . '</td>';
                            echo '<td class="px-6 py-4 text-sm text-gray-500 truncate" title="' . htmlspecialchars($patient) . '">' . $patient . '</td>';
                            echo '<td class="px-6 py-4 text-sm text-gray-500 truncate" title="' . htmlspecialchars($reason) . '">' . htmlspecialchars($reason) . '</td>';
                            echo '<td class="px-6 py-4 text-sm text-gray-900 text-left">' . $qty . '</td>';
                            echo '<td class="px-6 py-4 text-sm text-gray-500">' . $formattedDate . '</td>';
                            echo '<td class="px-6 py-4 text-sm text-gray-500">' . htmlspecialchars($row['staff_name'] ?? 'Staff') . '</td>';
                            echo '</tr>';
                        }
                    } else {
                        echo '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-file-list-3-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No prescription history found</p><p class="text-gray-400 text-xs">No medications have been issued yet</p></div></td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination for Issue Medication History -->
        <div class="flex justify-between items-center mt-6 px-6 py-4">
            <div class="text-sm text-gray-600">
                <?php 
                $history_start = $historyStart + 1;
                $history_end = min($historyStart + $historyPerPage, $historyTotal);
                ?>
                Showing <?php echo $history_start; ?> to <?php echo $history_end; ?> of <?php echo $historyTotal; ?> entries
            </div>
            <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">
                <?php if ($historyPage > 1): ?>
                <a href="?history_page=<?php echo $historyPage - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
                        <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
                        <span class="sr-only">Previous</span>
                    </a>
                <?php else: ?>
                    <button type="button" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-400 cursor-not-allowed" disabled>
                        <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
                        <span class="sr-only">Previous</span>
                    </button>
                <?php endif; ?>
                <?php
                $history_start_page = max(1, $historyPage - 2);
                $history_end_page = min($historyTotalPages, $historyPage + 2);
                if ($history_start_page > 1): ?>
                <a href="?history_page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">1</a>
                    <?php if ($history_start_page > 2): ?>
                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php for ($i = $history_start_page; $i <= $history_end_page; $i++): ?>
                    <?php if ($i == $historyPage): ?>
                    <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300" aria-current="page"><?php echo $i; ?></button>
                    <?php else: ?>
                    <a href="?history_page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($history_end_page < $historyTotalPages): ?>
                    <?php if ($history_end_page < $historyTotalPages - 1): ?>
                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                    <?php endif; ?>
                <a href="?history_page=<?php echo $historyTotalPages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $historyTotalPages; ?></a>
                <?php endif; ?>
                <?php if ($historyPage < $historyTotalPages): ?>
                <a href="?history_page=<?php echo $historyPage + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
                        <span class="sr-only">Next</span>
                        <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </a>
                <?php else: ?>
                    <button type="button" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-400 cursor-not-allowed" disabled>
                        <span class="sr-only">Next</span>
                        <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </button>
                <?php endif; ?>
            </nav>
        </div>
    </div>
    <!-- Add Medicine Modal -->
    <div id="addMedModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button id="closeAddMedModal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Add Medicine</h3>
            <form id="addMedForm">
                <div class="mb-4 relative">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medicine Name</label>
                    <input type="text" name="name" id="medicineNameInput" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required autocomplete="off" />
                    <div id="medicineNameSuggestions" class="absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-b shadow-lg max-h-40 overflow-y-auto z-10 hidden"></div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                    <input type="text" name="dosage" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                    <input type="number" name="quantity" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" min="1" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                    <input type="date" name="expiry" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <button type="submit" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90">Add
                    Medicine</button>
            </form>
        </div>
    </div>
    <!-- Add Edit Medicine Modal (hidden by default) -->
    <div id="editMedModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md p-6 relative">
            <button id="closeEditMedModal" class="absolute top-2 right-2 text-gray-400 hover:text-gray-700">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Edit Medicine</h3>
            <form id="editMedForm">
                <input type="hidden" name="id" id="editMedId">
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medicine Name</label>
                    <input type="text" name="name" id="editMedName" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                    <input type="text" name="dosage" id="editMedDosage" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                    <input type="number" name="quantity" id="editMedQuantity" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" min="1" required />
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label>
                    <input type="date" name="expiry" id="editMedExpiry" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                </div>
                <button type="submit" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90">Save Changes</button>
            </form>
        </div>
    </div>
    <!-- Modal for viewing prescription details -->
    <div id="historyViewModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-md mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-neutral-700">
                <h3 id="historyViewModalTitle" class="font-bold text-gray-800 dark:text-white">
                    Prescription Details
                </h3>
                <button id="closeHistoryViewModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-4 overflow-y-auto">
                <div class="space-y-3">
                    <div class="grid grid-cols-1 gap-3">
                        <div id="historyViewModalBody" class="text-sm text-gray-700 space-y-3"></div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700">
                <button id="closeHistoryViewModalBottom" type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">
                    Close
                </button>
            </div>
        </div>
    </div>
    <!-- Reusable centered modal for alerts/info messages -->
    <div id="centeredModal" class="fixed inset-0 flex items-start justify-center z-50 bg-black bg-opacity-40 hidden">
        <div id="centeredModalBox" class="rounded-lg shadow-lg max-w-sm w-full p-6 text-center relative mt-32 transition-all duration-200">
            <div id="centeredModalMsg" class="text-lg mb-2"></div>
        </div>
    </div>
    <!-- Delete confirmation modal -->
    <div id="deleteConfirmModal" class="fixed inset-0 flex items-start justify-center z-50 bg-black bg-opacity-40 hidden">
        <div class="rounded-lg shadow-lg max-w-sm w-full p-6 text-center relative mt-32 transition-all duration-200 bg-white">
            <div class="text-lg mb-4 text-red-600 font-semibold">Are you sure you want to delete this medicine?</div>
            <div class="flex justify-center gap-4">
                <button id="deleteConfirmYes" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Delete</button>
                <button id="deleteConfirmNo" class="px-4 py-2 bg-gray-300 text-gray-800 rounded hover:bg-gray-400">Cancel</button>
            </div>
        </div>
    </div>
</main>
<style>
  html, body {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* Internet Explorer 10+ */
  }
  html::-webkit-scrollbar,
  body::-webkit-scrollbar {
    display: none; /* Safari and Chrome */
  }
</style>
<script>
    // Medicine name autocomplete functionality
    const medicineNameInput = document.getElementById('medicineNameInput');
    const medicineNameSuggestions = document.getElementById('medicineNameSuggestions');
    let selectedIndex = -1;
    let suggestionTimeout = null;

    function fetchMedicineSuggestions(query) {
        fetch(`get_medicine_suggestions.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(suggestions => {
                displaySuggestions(suggestions);
            })
            .catch(error => {
                console.error('Error fetching suggestions:', error);
                hideSuggestions();
            });
    }

    function displaySuggestions(suggestions) {
        medicineNameSuggestions.innerHTML = '';
        selectedIndex = -1;
        
        if (suggestions.length === 0) {
            hideSuggestions();
            return;
        }
        
        suggestions.forEach((suggestion, index) => {
            const div = document.createElement('div');
            div.className = 'suggestion-item';
            div.textContent = suggestion;
            div.addEventListener('click', function() {
                selectSuggestion(suggestion);
            });
            medicineNameSuggestions.appendChild(div);
        });
        
        medicineNameSuggestions.classList.remove('hidden');
    }

    function updateHighlight(suggestions) {
        suggestions.forEach((item, index) => {
            if (index === selectedIndex) {
                item.classList.add('highlighted');
            } else {
                item.classList.remove('highlighted');
            }
        });
    }

    function selectSuggestion(suggestion) {
        medicineNameInput.value = suggestion;
        hideSuggestions();
        medicineNameInput.focus();
    }

    function hideSuggestions() {
        if (medicineNameSuggestions) {
            medicineNameSuggestions.classList.add('hidden');
            selectedIndex = -1;
        }
    }

    // Set up autocomplete event listeners when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        // Load initial medicine data to show pagination
        performMedicineSearch('', 1);
        
        if (medicineNameInput) {
            medicineNameInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                // Clear previous timeout
                if (suggestionTimeout) {
                    clearTimeout(suggestionTimeout);
                }
                
                if (query.length < 1) {
                    hideSuggestions();
                    return;
                }
                
                // Debounce the API call
                suggestionTimeout = setTimeout(() => {
                    fetchMedicineSuggestions(query);
                }, 300);
            });

            medicineNameInput.addEventListener('keydown', function(e) {
                const suggestions = medicineNameSuggestions.querySelectorAll('.suggestion-item');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
                    updateHighlight(suggestions);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateHighlight(suggestions);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (selectedIndex >= 0 && suggestions[selectedIndex]) {
                        selectSuggestion(suggestions[selectedIndex].textContent);
                    }
                } else if (e.key === 'Escape') {
                    hideSuggestions();
                }
            });

            // Hide suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (!medicineNameInput.contains(e.target) && !medicineNameSuggestions.contains(e.target)) {
                    hideSuggestions();
                }
            });
        }
    });

    // Modal logic
    const addMedBtn = document.getElementById('addMedBtn');
    const addMedModal = document.getElementById('addMedModal');
    const closeAddMedModal = document.getElementById('closeAddMedModal');
    addMedBtn.addEventListener('click', () => {
        addMedModal.classList.remove('hidden');
        // Clear form and suggestions when opening modal
        document.querySelector('#addMedModal form').reset();
        hideSuggestions();
    });
    closeAddMedModal.addEventListener('click', () => {
        addMedModal.classList.add('hidden');
        hideSuggestions();
    });
    window.addEventListener('click', (e) => {
        if (e.target === addMedModal) {
            addMedModal.classList.add('hidden');
            hideSuggestions();
        }
    });
    // Prevent form submit (demo)
    document.querySelector('#addMedModal form').addEventListener('submit', function (e) {
        e.preventDefault();
        addMedModal.classList.add('hidden');
    });

    // Add Medicine Modal logic with backend integration
    document.querySelector('#addMedModal form').addEventListener('submit', function (e) {
        e.preventDefault();
        const name = this.querySelector('input[name="name"]').value;
        const dosage = this.querySelector('input[name="dosage"]').value;
        const quantity = this.querySelector('input[name="quantity"]').value;
        const expiry = this.querySelector('input[name="expiry"]').value;
        fetch('add_medicine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `name=${encodeURIComponent(name)}&dosage=${encodeURIComponent(dosage)}&quantity=${encodeURIComponent(quantity)}&expiry=${encodeURIComponent(expiry)}`
        })
        .then(res => res.json())
        .then (data => {
            if(data.success) {
                showSuccessModal('Medicine Added Successfully', 'Success', true);
                setTimeout(() => location.reload(), 1200);
            } else {
                showErrorModal('Error: ' + data.message, 'Error');
            }
        })
        .catch(() => showErrorModal('Error adding medicine.', 'Error'));
    });

    // Edit Medicine Modal logic
    const editMedModal = document.getElementById('editMedModal');
    const closeEditMedModal = document.getElementById('closeEditMedModal');
    const editMedForm = document.getElementById('editMedForm');
    let currentEditRow = null;
    document.querySelectorAll('.editMedBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            currentEditRow = row;
            const id = row.getAttribute('data-id');
            const name = row.children[0].textContent.trim();
            const dosage = row.children[1].textContent.trim();
            const quantity = row.children[2].textContent.trim();
            const expiry = row.children[4].textContent.trim();
            document.getElementById('editMedId').value = id;
            document.getElementById('editMedName').value = name;
            document.getElementById('editMedDosage').value = dosage;
            document.getElementById('editMedQuantity').value = quantity;
            document.getElementById('editMedExpiry').value = expiry;
            editMedModal.classList.remove('hidden');
        });
    });
    closeEditMedModal.addEventListener('click', () => editMedModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === editMedModal) editMedModal.classList.add('hidden');
    });
    editMedForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const id = document.getElementById('editMedId').value;
        const name = document.getElementById('editMedName').value;
        const dosage = document.getElementById('editMedDosage').value;
        const quantity = document.getElementById('editMedQuantity').value;
        const expiry = document.getElementById('editMedExpiry').value;
        fetch('edit_medicine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${encodeURIComponent(id)}&name=${encodeURIComponent(name)}&dosage=${encodeURIComponent(dosage)}&quantity=${encodeURIComponent(quantity)}&expiry=${encodeURIComponent(expiry)}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                showSuccessModal('Medicine updated!', 'Success', true);
                setTimeout(() => location.reload(), 1200);
            } else {
                showErrorModal('Error: ' + data.message, 'Error');
            }
        })
        .catch(() => showErrorModal('Error updating medicine.', 'Error'));
        editMedModal.classList.add('hidden');
    });

    // Delete Medicine logic
    const deleteMedBtns = document.querySelectorAll('.deleteMedBtn');
    let deleteMedicineId = null;
    const deleteConfirmModal = document.getElementById('deleteConfirmModal');
    const deleteConfirmYes = document.getElementById('deleteConfirmYes');
    const deleteConfirmNo = document.getElementById('deleteConfirmNo');
    deleteMedBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const row = this.closest('tr');
            deleteMedicineId = row.getAttribute('data-id');
            // Show confirmation modal
            deleteConfirmModal.classList.remove('hidden');
        });
    });
    deleteConfirmYes.addEventListener('click', function() {
        if (!deleteMedicineId) return;
        fetch('delete_medicine.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id=${encodeURIComponent(deleteMedicineId)}`
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                showSuccessModal('Medicine deleted!', 'Success', true);
                setTimeout(() => location.reload(), 1200);
            } else {
                showErrorModal('Error: ' + data.message, 'Error');
            }
        })
        .catch(() => showErrorModal('Error deleting medicine.', 'Error'));
        deleteConfirmModal.classList.add('hidden');
        deleteMedicineId = null;
    });
    deleteConfirmNo.addEventListener('click', function() {
        deleteConfirmModal.classList.add('hidden');
        deleteMedicineId = null;
    });
    window.addEventListener('click', (e) => {
        if (e.target === deleteConfirmModal) {
            deleteConfirmModal.classList.add('hidden');
            deleteMedicineId = null;
        }
    });



        // Medicine Stock Search functionality (Server-side)
    const medicineSearch = document.getElementById('medicineSearch');
        
        if (medicineSearch) {
            let searchTimeout;
            medicineSearch.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                // Set new timeout for debounced search
                searchTimeout = setTimeout(() => {
                    if (searchTerm.length >= 2 || searchTerm.length === 0) {
                        performMedicineSearch(searchTerm, 1); // Always start from page 1 for new searches
                    }
                }, 300);
            });
        }
        
        function performMedicineSearch(searchTerm, page = 1) {
            // No loading state for seamless real-time search
            
            // Store search term for pagination
            window.currentMedicineSearchTerm = searchTerm;
            
        // If search is cleared, show all data without page reload
        if (!searchTerm || searchTerm.trim() === '') {
            window.currentMedicineSearchTerm = null;
            // Make AJAX request to get all data without search filter
            fetch('search_medicines.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `search=&page=${page}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        updateMedicineTable(data.medicines, data.pagination);
                    } else {
                        console.error('Search error:', data.message);
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                }
            })
            .catch(error => {
                console.error('Search error:', error);
            });
            return;
            }
            
            // Make AJAX request to server
            fetch('search_medicines.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `search=${encodeURIComponent(searchTerm)}&page=${page}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text(); // Get as text first
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        updateMedicineTable(data.medicines, data.pagination);
                    } else {
                        console.error('Search error:', data.message);
                        // Show error or fallback
                        if (medicineTableBody) {
                            medicineTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';
                        }
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', text);
                    if (medicineTableBody) {
                        medicineTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';
                    }
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                if (medicineTableBody) {
                    medicineTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';
                }
            });
        }
        
        function updateMedicineTable(medicines, pagination = null) {
            const medicineTableBody = document.getElementById('medicineTableBody');
            if (!medicineTableBody) return;
            
            if (medicines.length === 0) {
                medicineTableBody.innerHTML = '<tr><td colspan="5" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medicines found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
                // Hide pagination when no results - target Medicine Stock Available section specifically
                const medicineSection = document.querySelector('.bg-white.rounded-lg.shadow-sm.border.border-gray-200.mb-8');
                const paginationContainer = medicineSection ? medicineSection.querySelector('.flex.justify-between.items-center.mt-6.px-6.py-4') : null;
                if (paginationContainer) {
                    paginationContainer.style.display = 'none';
                }
                return;
            }
            
            // Always show pagination - target Medicine Stock Available section specifically
            const medicineSection = document.querySelector('.bg-white.rounded-lg.shadow-sm.border.border-gray-200.mb-8');
            const paginationContainer = medicineSection ? medicineSection.querySelector('.flex.justify-between.items-center.mt-6.px-6.py-4') : null;
            if (paginationContainer) {
                paginationContainer.style.display = 'flex';
                
                // Update pagination info if provided
                if (pagination) {
                    const startRecord = ((pagination.current_page - 1) * pagination.per_page) + 1;
                    const endRecord = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);
                    const infoText = paginationContainer.querySelector('.text-sm.text-gray-600');
                    if (infoText) {
                        infoText.textContent = `Showing ${startRecord} to ${endRecord} of ${pagination.total_records} entries`;
                    }
                    
                    // Update pagination numbers based on search results
                    updateMedicinePaginationNumbers(pagination);
                } else {
                    // If no pagination data, replace the entire pagination container with simple info
                    paginationContainer.innerHTML = `
                        <div class="text-sm text-gray-600">
                            Showing 1 to ${medicines.length} of ${medicines.length} entries
                        </div>
                    `;
                }
            }
            
            let html = '';
            medicines.forEach(medicine => {
                const status = medicine.quantity > 100 ? 'In Stock' : (medicine.quantity > 20 ? 'Medium Stock' : 'Low Stock');
                const statusClass = status === 'In Stock' ? 'bg-blue-600 text-white' : (status === 'Medium Stock' ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800');
                
                html += `
                    <tr class="hover:bg-blue-50" data-name="${medicine.name.toLowerCase()}">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${medicine.name}">${medicine.name}</td>
                        <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${medicine.dosage}">${medicine.dosage}</td>
                        <td class="px-6 py-4 text-sm text-gray-900 text-left">${medicine.quantity}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">${medicine.expiry}</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                                <i class="ri-medicine-bottle-line mr-1"></i>${status}
                            </span>
                        </td>
                    </tr>
                `;
            });
            
            medicineTableBody.innerHTML = html;
        }
        
        function updateMedicinePaginationNumbers(pagination) {
            // Target the Medicine Stock Available section specifically
            const medicineSection = document.querySelector('.bg-white.rounded-lg.shadow-sm.border.border-gray-200.mb-8');
            const paginationNav = medicineSection ? medicineSection.querySelector('nav[aria-label="Pagination"]') : null;
            if (!paginationNav) return;
            
            const currentPage = pagination.current_page;
            const totalPages = pagination.total_pages;
            
            // Clear existing pagination
            paginationNav.innerHTML = '';
            
            // Previous button - always show
            if (currentPage > 1) {
                const prevBtn = document.createElement('a');
                const searchParam = window.currentMedicineSearchTerm ? `&search=${encodeURIComponent(window.currentMedicineSearchTerm)}` : '';
                prevBtn.href = `?medicine_page=${currentPage - 1}${searchParam}`;
                prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';
                prevBtn.setAttribute('aria-label', 'Previous');
                prevBtn.innerHTML = `
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                `;
                paginationNav.appendChild(prevBtn);
            } else {
                const prevBtn = document.createElement('button');
                prevBtn.type = 'button';
                prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-400 cursor-not-allowed';
                prevBtn.disabled = true;
                prevBtn.innerHTML = `
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                `;
                paginationNav.appendChild(prevBtn);
            }
            
            // Page numbers - always show current page
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, currentPage + 2);
            
            // Always show page 1 if it's not in the current range
            if (startPage > 1) {
                const firstPage = document.createElement('a');
                const searchParam = window.currentMedicineSearchTerm ? `&search=${encodeURIComponent(window.currentMedicineSearchTerm)}` : '';
                firstPage.href = `?medicine_page=1${searchParam}`;
                firstPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100';
                firstPage.textContent = '1';
                paginationNav.appendChild(firstPage);
                
                if (startPage > 2) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm';
                    ellipsis.textContent = '...';
                    paginationNav.appendChild(ellipsis);
                }
            }
            
            // Always show the current page and surrounding pages
            for (let i = startPage; i <= endPage; i++) {
                if (i === currentPage) {
                    const currentBtn = document.createElement('button');
                    currentBtn.type = 'button';
                    currentBtn.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-200 text-gray-800 border border-gray-200 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-200';
                    currentBtn.setAttribute('aria-current', 'page');
                    currentBtn.textContent = i;
                    paginationNav.appendChild(currentBtn);
                } else {
                    const pageLink = document.createElement('a');
                    const searchParam = window.currentMedicineSearchTerm ? `&search=${encodeURIComponent(window.currentMedicineSearchTerm)}` : '';
                    pageLink.href = `?medicine_page=${i}${searchParam}`;
                    pageLink.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100';
                    pageLink.textContent = i;
                    paginationNav.appendChild(pageLink);
                }
            }
            
            // Show last page if it's not in the current range
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    const ellipsis = document.createElement('span');
                    ellipsis.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm';
                    ellipsis.textContent = '...';
                    paginationNav.appendChild(ellipsis);
                }
                
                const lastPage = document.createElement('a');
                const searchParam = window.currentMedicineSearchTerm ? `&search=${encodeURIComponent(window.currentMedicineSearchTerm)}` : '';
                lastPage.href = `?medicine_page=${totalPages}${searchParam}`;
                lastPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100';
                lastPage.textContent = totalPages;
                paginationNav.appendChild(lastPage);
            }
            
            // Next button - always show
            if (currentPage < totalPages) {
                const nextBtn = document.createElement('a');
                const searchParam = window.currentMedicineSearchTerm ? `&search=${encodeURIComponent(window.currentMedicineSearchTerm)}` : '';
                nextBtn.href = `?medicine_page=${currentPage + 1}${searchParam}`;
                nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';
                nextBtn.setAttribute('aria-label', 'Next');
                nextBtn.innerHTML = `
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                `;
                paginationNav.appendChild(nextBtn);
            } else {
                const nextBtn = document.createElement('button');
                nextBtn.type = 'button';
                nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-400 cursor-not-allowed';
                nextBtn.disabled = true;
                nextBtn.innerHTML = `
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                `;
                paginationNav.appendChild(nextBtn);
            }
        }

    // Issue Medication History Search functionality (Server-side)
    const medicationSearchInput = document.getElementById('medicationSearchInput');
    
    if (medicationSearchInput) {
        let searchTimeout;
        medicationSearchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Set new timeout for debounced search
            searchTimeout = setTimeout(() => {
                if (searchTerm.length >= 2 || searchTerm.length === 0) {
                    performMedicationHistorySearch(searchTerm, 1); // Always start from page 1 for new searches
                }
            }, 300);
        });
    }
    
    function performMedicationHistorySearch(searchTerm, page = 1, year = '') {
        // No loading state for seamless real-time search
        
        // Store search term for pagination
        window.currentSearchTerm = searchTerm;
        
        // If search is cleared, show all data without page reload
        if (!searchTerm || searchTerm.trim() === '') {
            window.currentSearchTerm = null;
            // Make AJAX request to get all data without search filter
            fetch('search_medication_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `search=&page=${page}&year=${year}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        updateMedicationHistoryTable(data.history, data.pagination);
                    } else {
                        console.error('Search error:', data.message);
                    }
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                }
            })
            .catch(error => {
                console.error('Search error:', error);
            });
            return;
        }
        
        // Make AJAX request to server
        fetch('search_medication_history.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `search=${encodeURIComponent(searchTerm)}&page=${page}&year=${year}`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.text(); // Get as text first
        })
        .then(text => {
            try {
                const data = JSON.parse(text);
                if (data.success) {
                    updateMedicationHistoryTable(data.history, data.pagination);
                } else {
                    console.error('Search error:', data.message);
                    // Show error or fallback
                    if (historyTableBody) {
                        historyTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';
                    }
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text);
                if (historyTableBody) {
                    historyTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';
                }
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            if (historyTableBody) {
                historyTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';
            }
        });
    }
    
    function updateMedicationHistoryTable(history, pagination = null) {
        const historyTableBody = document.getElementById('historyTableBody');
        if (!historyTableBody) return;
        
        if (history.length === 0) {
            historyTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-file-list-3-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No prescription history found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
            // Hide pagination when no results - target Issue Medication History section specifically
            const historySections = document.querySelectorAll('.bg-white.rounded-lg.shadow-sm.border.border-gray-200.mb-8');
            const historySection = historySections[1]; // Second section is Issue Medication History
            const paginationContainer = historySection ? historySection.querySelector('.flex.justify-between.items-center.mt-6') : null;
            if (paginationContainer) {
                paginationContainer.style.display = 'none';
            }
            return;
        }
        
        // Show pagination when results are found - target Issue Medication History section specifically
        const historySections = document.querySelectorAll('.bg-white.rounded-lg.shadow-sm.border.border-gray-200.mb-8');
        const historySection = historySections[1]; // Second section is Issue Medication History
        const paginationContainer = historySection ? historySection.querySelector('.flex.justify-between.items-center.mt-6') : null;
        if (paginationContainer) {
            paginationContainer.style.display = 'flex';
            
            // Update pagination info if provided
            if (pagination) {
                // Use actual returned data for accurate pagination info
                const startRecord = pagination.start_record || ((pagination.current_page - 1) * pagination.per_page) + 1;
                const endRecord = pagination.end_record || Math.min(pagination.current_page * pagination.per_page, pagination.total_records);
                const actualCount = pagination.actual_count || history.length;
                const infoText = paginationContainer.querySelector('.text-sm.text-gray-600');
                if (infoText) {
                    infoText.textContent = `Showing ${startRecord} to ${endRecord} of ${pagination.total_records} entries`;
                }
                
                // Update pagination numbers based on search results
                updatePaginationNumbers(pagination);
            } else {
                // If no pagination data, replace the entire pagination container with simple info
                paginationContainer.innerHTML = `
                    <div class="text-sm text-gray-600">
                        Showing 1 to ${history.length} of ${history.length} entries
                    </div>
                `;
            }
        }
        
        let html = '';
        history.forEach(row => {
            const formattedDate = new Date(row.prescription_date).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
            
            html += `
                <tr class="hover:bg-blue-50" data-patient="${row.patient_name.toLowerCase()}" data-medicine="${row.medicine.toLowerCase()}" data-date="${row.prescription_date}">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${row.medicine}">${row.medicine}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${row.patient_name}">${row.patient_name}</td>
                    <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${row.reason}">${row.reason}</td>
                    <td class="px-6 py-4 text-sm text-gray-900 text-left">${row.quantity}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">${formattedDate}</td>
                    <td class="px-6 py-4 text-sm text-gray-500">${row.staff_name || 'Staff'}</td>
                </tr>
            `;
        });
        
        historyTableBody.innerHTML = html;
    }
    
    
    
    function updatePaginationNumbers(pagination) {
        // Target the Issue Medication History section specifically
        const historySections = document.querySelectorAll('.bg-white.rounded-lg.shadow-sm.border.border-gray-200.mb-8');
        const historySection = historySections[1]; // Second section is Issue Medication History
        const paginationNav = historySection ? historySection.querySelector('nav[aria-label="Pagination"]') : null;
        if (!paginationNav) return;
        
        const currentPage = pagination.current_page;
        const totalPages = pagination.total_pages;
        
        // Clear existing pagination
        paginationNav.innerHTML = '';
        
        // Previous button
        if (currentPage > 1) {
            const prevBtn = document.createElement('a');
            const searchParam = window.currentSearchTerm ? `&search=${encodeURIComponent(window.currentSearchTerm)}` : '';
            prevBtn.href = `?history_page=${currentPage - 1}${searchParam}`;
            prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';
            prevBtn.setAttribute('aria-label', 'Previous');
            prevBtn.innerHTML = `
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                <span class="sr-only">Previous</span>
            `;
            paginationNav.appendChild(prevBtn);
        } else {
            const prevBtn = document.createElement('button');
            prevBtn.type = 'button';
            prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-400 cursor-not-allowed';
            prevBtn.disabled = true;
            prevBtn.innerHTML = `
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                <span class="sr-only">Previous</span>
            `;
            paginationNav.appendChild(prevBtn);
        }
        
        // Page numbers
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            const firstPage = document.createElement('a');
            const searchParam = window.currentSearchTerm ? `&search=${encodeURIComponent(window.currentSearchTerm)}` : '';
            firstPage.href = `?history_page=1${searchParam}`;
            firstPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100';
            firstPage.textContent = '1';
            paginationNav.appendChild(firstPage);
            
            if (startPage > 2) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm';
                ellipsis.textContent = '...';
                paginationNav.appendChild(ellipsis);
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                const currentBtn = document.createElement('button');
                currentBtn.type = 'button';
                currentBtn.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300';
                currentBtn.setAttribute('aria-current', 'page');
                currentBtn.textContent = i;
                paginationNav.appendChild(currentBtn);
            } else {
                const pageLink = document.createElement('a');
                const searchParam = window.currentSearchTerm ? `&search=${encodeURIComponent(window.currentSearchTerm)}` : '';
                pageLink.href = `?history_page=${i}${searchParam}`;
                pageLink.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100';
                pageLink.textContent = i;
                paginationNav.appendChild(pageLink);
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                const ellipsis = document.createElement('span');
                ellipsis.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm';
                ellipsis.textContent = '...';
                paginationNav.appendChild(ellipsis);
            }
            
            const lastPage = document.createElement('a');
            const searchParam = window.currentSearchTerm ? `&search=${encodeURIComponent(window.currentSearchTerm)}` : '';
            lastPage.href = `?history_page=${totalPages}${searchParam}`;
            lastPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100';
            lastPage.textContent = totalPages;
            paginationNav.appendChild(lastPage);
        }
        
        // Next button
        if (currentPage < totalPages) {
            const nextBtn = document.createElement('a');
            const searchParam = window.currentSearchTerm ? `&search=${encodeURIComponent(window.currentSearchTerm)}` : '';
            nextBtn.href = `?history_page=${currentPage + 1}${searchParam}`;
            nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';
            nextBtn.setAttribute('aria-label', 'Next');
            nextBtn.innerHTML = `
                <span class="sr-only">Next</span>
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            `;
            paginationNav.appendChild(nextBtn);
        } else {
            const nextBtn = document.createElement('button');
            nextBtn.type = 'button';
            nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-400 cursor-not-allowed';
            nextBtn.disabled = true;
            nextBtn.innerHTML = `
                <span class="sr-only">Next</span>
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            `;
            paginationNav.appendChild(nextBtn);
        }
    }

    // Year Filter functionality
    const yearFilter = document.getElementById('year');
    if (yearFilter && historyTableBody) {
        yearFilter.addEventListener('change', function() {
            const selectedYear = this.value;
            const rows = historyTableBody.querySelectorAll('tr');
            
            rows.forEach(row => {
                const date = row.getAttribute('data-date') || '';
                const rowYear = date ? new Date(date).getFullYear().toString() : '';
                
                if (selectedYear === '' || rowYear === selectedYear) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    // Issue Medication History View Modal logic
    const historyData = <?php echo json_encode(array_values($historyPageData)); ?>;
    const viewBtns = document.querySelectorAll('.viewHistoryBtn');
    const viewModal = document.getElementById('historyViewModal');
    const closeViewModal = document.getElementById('closeHistoryViewModal');
    const closeViewModalBottom = document.getElementById('closeHistoryViewModalBottom');
    const viewModalTitle = document.getElementById('historyViewModalTitle');
    const viewModalBody = document.getElementById('historyViewModalBody');
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const idx = this.getAttribute('data-idx');
            const row = historyData[idx];
            viewModalTitle.textContent = row.patient_name;
            viewModalBody.innerHTML = `
                <div class="grid grid-cols-[120px_1fr] gap-3 items-center">
                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Date:</label>
                    <p class="text-sm text-gray-900 dark:text-neutral-200">${row.prescription_date}</p>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-3 items-center">
                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Patient:</label>
                    <p class="text-sm text-gray-900 dark:text-neutral-200">${row.patient_name}</p>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-3 items-center">
                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Medicine:</label>
                    <p class="text-sm text-gray-900 dark:text-neutral-200">${row.medicine}</p>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-3 items-center">
                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Quantity:</label>
                    <p class="text-sm text-gray-900 dark:text-neutral-200">${row.quantity}</p>
                </div>
                <div class="grid grid-cols-[120px_1fr] gap-3 items-start">
                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Reason:</label>
                    <p class="text-sm text-gray-900 dark:text-neutral-200">${row.reason}</p>
                </div>
            `;
            viewModal.classList.remove('hidden');
        });
    });
    closeViewModal.addEventListener('click', () => viewModal.classList.add('hidden'));
    closeViewModalBottom.addEventListener('click', () => viewModal.classList.add('hidden'));
    window.addEventListener('click', (e) => {
        if (e.target === viewModal) viewModal.classList.add('hidden');
    });

    // Medication History Search Functionality
    function filterMedicationHistory() {
        const searchTerm = document.getElementById('medicationSearchInput').value.toLowerCase().trim();
        const table = document.getElementById('issueHistoryTable');
        const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
        let visibleCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const row = rows[i];
            const cells = row.getElementsByTagName('td');
            
            if (cells.length >= 5) {
                const date = cells[0].textContent.toLowerCase();
                const patient = cells[1].textContent.toLowerCase();
                const reason = cells[2].textContent.toLowerCase();
                const medicine = cells[3].textContent.toLowerCase();
                
                const matches = !searchTerm || 
                    date.includes(searchTerm) || 
                    patient.includes(searchTerm) || 
                    reason.includes(searchTerm) || 
                    medicine.includes(searchTerm);
                
                row.style.display = matches ? '' : 'none';
                if (matches) visibleCount++;
            }
        }

        // Update search results counter
        const searchResults = document.getElementById('medicationSearchResults');
        const searchCount = document.getElementById('medicationSearchCount');
        const clearButton = document.getElementById('clearMedicationSearch');

        if (searchTerm) {
            searchResults.classList.remove('hidden');
            searchCount.textContent = visibleCount;
            clearButton.classList.remove('hidden');
        } else {
            searchResults.classList.add('hidden');
            clearButton.classList.add('hidden');
        }
    }

    // Add event listeners (only if elements exist)
    const clearMedicationSearch = document.getElementById('clearMedicationSearch');
    
    if (clearMedicationSearch) {
        clearMedicationSearch.addEventListener('click', function() {
            const medicationSearchInput = document.getElementById('medicationSearchInput');
            if (medicationSearchInput) {
                medicationSearchInput.value = '';
        filterMedicationHistory();
            }
    });
    }
    
    // Year filter functionality for Issue Medication History
    if (yearFilter) {
        yearFilter.addEventListener('change', function() {
            const selectedYear = this.value;
            performMedicationHistorySearch(window.currentSearchTerm || '', 1, selectedYear);
        });
    }
    
    // Handle pagination clicks for medicine and medication history search results
    document.addEventListener('click', function(e) {
        // Check if it's a pagination link
        if (e.target.closest('nav[aria-label="Pagination"] a')) {
            const link = e.target.closest('a');
            const href = link.getAttribute('href');
            
            // Always prevent default and use AJAX for pagination
            if (href.includes('medicine_page=')) {
                e.preventDefault();
                
                // Extract page number from href
                const pageMatch = href.match(/medicine_page=(\d+)/);
                if (pageMatch) {
                    const page = parseInt(pageMatch[1]);
                    // Use search function with current search term (or empty if no search)
                    const searchTerm = window.currentMedicineSearchTerm || '';
                    performMedicineSearch(searchTerm, page);
                }
            }
            // Check if it's medication history pagination
            else if (href.includes('history_page=')) {
                e.preventDefault();
                
                // Extract page number from href
                const pageMatch = href.match(/history_page=(\d+)/);
                if (pageMatch) {
                    const page = parseInt(pageMatch[1]);
                    // Use search function with current search term (or empty if no search)
                    const searchTerm = window.currentSearchTerm || '';
                    performMedicationHistorySearch(searchTerm, page);
                }
            }
        }
    });

    // Handle server-side pagination clicks for Issue Medication History
    document.addEventListener('click', function(e) {
        // Check if it's a server-side pagination link for Issue Medication History
        if (e.target.closest('a[href*="history_page="]')) {
            const link = e.target.closest('a');
            const href = link.getAttribute('href');
            
            // Always prevent default and use AJAX for pagination
            if (href.includes('history_page=')) {
                e.preventDefault();
                
                // Extract page number from href
                const pageMatch = href.match(/history_page=(\d+)/);
                if (pageMatch) {
                    const page = parseInt(pageMatch[1]);
                    // Use search function with current search term (or empty if no search)
                    const searchTerm = window.currentSearchTerm || '';
                    performMedicationHistorySearch(searchTerm, page);
                }
            }
        }
    });
</script>
<?php
include '../includes/footer.php';
?>