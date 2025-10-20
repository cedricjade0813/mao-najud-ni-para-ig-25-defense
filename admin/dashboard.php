<?php
include '../includea/header.php';
include '../includes/db_connect.php';
// Fetch dynamic stats
try {
    // Total visits today (prescriptions today)
    $visitsToday = $db->query("SELECT COUNT(*) FROM prescriptions WHERE DATE(prescription_date) = CURDATE()")->fetchColumn();
    // Appointments pending (appointments table)
    $pendingAppointments = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
    // Total patients in system (visitor + faculty + imported_patients)
    $visitorCount = $db->query("SELECT COUNT(*) FROM visitor")->fetchColumn();
    $facultyCount = $db->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    $importedPatientsCount = $db->query("SELECT COUNT(*) FROM imported_patients")->fetchColumn();
    $totalStudents = $visitorCount + $facultyCount + $importedPatientsCount;
    // Completed appointments today (using 'approved' status as completed)
    $completedToday = $db->query("SELECT COUNT(*) FROM appointments WHERE date = CURDATE() AND status = 'approved'")->fetchColumn();
    // Active appointments today (using 'confirmed' status as active)
    $activeToday = $db->query("SELECT COUNT(*) FROM appointments WHERE date = CURDATE() AND status = 'confirmed'")->fetchColumn();
    // Monthly revenue (using prescription count as proxy since no amount column exists)
    $monthlyRevenue = $db->query("SELECT COUNT(*) FROM prescriptions WHERE MONTH(prescription_date) = MONTH(CURDATE()) AND YEAR(prescription_date) = YEAR(CURDATE())")->fetchColumn();
    
    // Staff Status - Get counts by role
    $doctorsCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'doctor/nurse' AND status = 'Active'")->fetchColumn();
    $nursesCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'doctor/nurse' AND status = 'Active'")->fetchColumn(); // Same as doctors for now
    $supportStaffCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin' AND status = 'Active'")->fetchColumn();
    
    // System Alerts - Get various alerts from database
    $systemAlerts = [];
    
    // 1. Pending appointments alert (high priority)
    if ($pendingAppointments > 0) {
        $systemAlerts[] = [
            'type' => 'warning',
            'title' => 'Pending Appointments',
            'message' => $pendingAppointments . ' appointments pending approval',
            'icon' => 'ri-calendar-line',
            'color' => 'yellow'
        ];
    }
    
    // 2. Failed parent alerts
    try {
        $failedAlerts = $db->query("SELECT COUNT(*) FROM parent_alerts WHERE alert_status = 'failed' AND alert_sent_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn();
        if ($failedAlerts > 0) {
            $systemAlerts[] = [
                'type' => 'warning',
                'title' => 'Failed Parent Alerts',
                'message' => $failedAlerts . ' parent alerts failed to send',
                'icon' => 'ri-mail-line',
                'color' => 'yellow'
            ];
        }
    } catch (Exception $e) {}
    
    // 3. Expired medicines alert
    try {
        $expiredMedicines = $db->query("SELECT COUNT(*) FROM medicines WHERE expiry < CURDATE()")->fetchColumn();
        if ($expiredMedicines > 0) {
            $systemAlerts[] = [
                'type' => 'critical',
                'title' => 'Expired Medicines',
                'message' => $expiredMedicines . ' medicines have expired',
                'icon' => 'ri-error-warning-line',
                'color' => 'red'
            ];
        }
    } catch (Exception $e) {}
    
    // 4. High patient visit frequency alert
    try {
        $highVisits = $db->query("SELECT COUNT(*) FROM prescriptions WHERE prescription_date >= DATE_SUB(NOW(), INTERVAL 7 DAY) GROUP BY patient_id HAVING COUNT(*) > 5")->fetchColumn();
        if ($highVisits > 0) {
            $systemAlerts[] = [
                'type' => 'warning',
                'title' => 'High Visit Frequency',
                'message' => 'Some patients have visited more than 5 times this week',
                'icon' => 'ri-user-line',
                'color' => 'yellow'
            ];
        }
    } catch (Exception $e) {}
    
    // 5. Inactive users alert
    try {
        $inactiveUsers = $db->query("SELECT COUNT(*) FROM users WHERE status = 'Disabled'")->fetchColumn();
        if ($inactiveUsers > 0) {
            $systemAlerts[] = [
                'type' => 'warning',
                'title' => 'Inactive Users',
                'message' => $inactiveUsers . ' user accounts are disabled',
                'icon' => 'ri-user-settings-line',
                'color' => 'yellow'
            ];
        }
    } catch (Exception $e) {}
    
    // 6. Unread notifications alert
    try {
        $unreadNotifications = $db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn();
        if ($unreadNotifications > 0) {
            $systemAlerts[] = [
                'type' => 'warning',
                'title' => 'Unread Notifications',
                'message' => $unreadNotifications . ' notifications are unread',
                'icon' => 'ri-notification-line',
                'color' => 'yellow'
            ];
        }
    } catch (Exception $e) {}
    
    // 7. Recent system activity (from logs)
    try {
        $recentActivity = $db->query("SELECT action, message FROM logs WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY timestamp DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        if ($recentActivity && count($systemAlerts) < 3) {
            $systemAlerts[] = [
                'type' => 'info',
                'title' => 'System Activity',
                'message' => $recentActivity['action'] . ' - ' . $recentActivity['message'],
                'icon' => 'ri-information-line',
                'color' => 'blue'
            ];
        }
    } catch (Exception $e) {}
    
    // Limit to 3 alerts for display
    $systemAlerts = array_slice($systemAlerts, 0, 3);
    
    // Today's Appointments - Get appointments for today
    $todaysAppointments = [];
    try {
        $stmt = $db->prepare("
            SELECT a.*, p.name as student_name 
            FROM appointments a 
            LEFT JOIN imported_patients p ON a.student_id = p.id 
            WHERE DATE(a.date) = CURDATE() 
            ORDER BY a.time ASC 
            LIMIT 20
        ");
        $stmt->execute();
        $todaysAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the results
        error_log("Today's appointments query returned " . count($todaysAppointments) . " appointments");
        if (!empty($todaysAppointments)) {
            error_log("First appointment: " . print_r($todaysAppointments[0], true));
        } else {
            // Debug: Check what appointments exist
            $debug_stmt = $db->prepare("SELECT a.*, p.name as student_name FROM appointments a LEFT JOIN imported_patients p ON a.student_id = p.id ORDER BY a.date DESC LIMIT 5");
            $debug_stmt->execute();
            $debug_appointments = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log("Recent appointments in database: " . print_r($debug_appointments, true));
        }
    } catch (Exception $e) {
        error_log("Error fetching today's appointments: " . $e->getMessage());
        $todaysAppointments = [];
    }
    
    // Patient Demographics - Get age distribution from all three tables
    $ageDistribution = [
        '0-18' => 0,
        '19-35' => 0,
        '36-50' => 0,
        '51-65' => 0,
        '65+' => 0
    ];
    $totalPatients = 0;
    
    try {
        // Get age distribution from all three tables combined
        $stmt = $db->query("
            SELECT 
                CASE 
                    WHEN age <= 18 THEN '0-18'
                    WHEN age BETWEEN 19 AND 35 THEN '19-35'
                    WHEN age BETWEEN 36 AND 50 THEN '36-50'
                    WHEN age BETWEEN 51 AND 65 THEN '51-65'
                    ELSE '65+'
                END as age_group,
                COUNT(*) as count
            FROM (
                -- Calculate age from date of birth for imported_patients
                SELECT TIMESTAMPDIFF(YEAR, dob, CURDATE()) as age 
                FROM imported_patients 
                WHERE dob IS NOT NULL AND dob != '0000-00-00' AND dob != ''
                UNION ALL
                -- Use age column directly for visitor
                SELECT age 
                FROM visitor 
                WHERE age IS NOT NULL
                UNION ALL
                -- Use age column directly for faculty
                SELECT age 
                FROM faculty 
                WHERE age IS NOT NULL
            ) as combined_ages
            GROUP BY age_group
        ");
        
        $ageGroupData = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Debug: Log the age groups data
        error_log("Admin Dashboard - Age Groups Data: " . print_r($ageGroupData, true));
        
        // Populate age distribution array
        foreach ($ageGroupData as $row) {
            $ageGroup = $row['age_group'];
            $count = (int)$row['count'];
            $totalPatients += $count;
            
            if (isset($ageDistribution[$ageGroup])) {
                $ageDistribution[$ageGroup] = $count;
            }
        }
        
        // Debug: Log total patients and age distribution
        error_log("Admin Dashboard - Total Patients for Demographics: " . $totalPatients);
        error_log("Admin Dashboard - Age Distribution: " . print_r($ageDistribution, true));
        
    } catch (Exception $e) {
        error_log("Error fetching patient demographics: " . $e->getMessage());
    }
    // Build data for line chart of frequent reasons across monthly time range
    $topReasons = [];
    $topReasonsDisplay = [];
    $monthlyLabels = [];
    $monthlySeries = [];

    try {
        // Top 5 reasons over last 12 months
        $stmt = $db->prepare("SELECT norm_reason, COUNT(*) cnt FROM (
            SELECT COALESCE(NULLIF(LOWER(TRIM(reason)), ''), 'unspecified') AS norm_reason
            FROM prescriptions
            WHERE prescription_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
        ) t GROUP BY norm_reason ORDER BY cnt DESC LIMIT 5");
        $stmt->execute();
        $topReasons = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        // Build display map (capitalize first letter, rest lowercase)
        foreach ($topReasons as $r) {
            $topReasonsDisplay[$r] = ucfirst($r);
        }

        // MONTHLY: last 12 months, label YYYY-MM
        $monthlyMap = [];
        for ($i = 11; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("first day of -{$i} month"));
            $monthlyLabels[] = $ym;
            $monthlyMap[$ym] = array_fill_keys($topReasons, 0);
        }
        if (!empty($topReasons)) {
            $inReasons = implode(',', array_fill(0, count($topReasons), '?'));
            $startMonth = $monthlyLabels[0] . '-01 00:00:00';
            $sql = "SELECT DATE_FORMAT(prescription_date, '%Y-%m') ym, COALESCE(NULLIF(LOWER(TRIM(reason)), ''), 'unspecified') r, COUNT(*) c
                    FROM prescriptions
                    WHERE prescription_date >= ? AND COALESCE(NULLIF(TRIM(reason), ''), 'Unspecified') IN ($inReasons)
                    GROUP BY ym, r";
            $params = array_merge([$startMonth], $topReasons);
            $st = $db->prepare($sql);
            $st->execute($params);
            while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
                $ym = $row['ym'];
                if (isset($monthlyMap[$ym]) && isset($monthlyMap[$ym][$row['r']])) {
                    $monthlyMap[$ym][$row['r']] = (int)$row['c'];
                }
            }
        }
        foreach ($topReasons as $r) {
            $monthlySeries[$r] = array_map(function($d) use ($monthlyMap, $r) { return $monthlyMap[$d][$r] ?? 0; }, $monthlyLabels);
        }
    } catch (Exception $e) {
        error_log("Error fetching monthly visit trends: " . $e->getMessage());
    }
    // Fetch medication stock status for pie chart
    $stockStatus = [];
    // Fetch medicines for stock status display
    $medicines = [];
    $medicineGroups = [];
    try {
        $stmt = $db->query('SELECT name, quantity, dosage, expiry FROM medicines ORDER BY name');
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $stockStatus[] = [
                'value' => (int)$row['quantity'],
                'name' => $row['name']
            ];

            // Group medicines by name and sum quantities
            $medicineName = $row['name'];
            if (!isset($medicineGroups[$medicineName])) {
                $medicineGroups[$medicineName] = [
                    'name' => $medicineName,
                    'quantity' => 0,
                    'dosage' => $row['dosage'],
                    'expiry' => $row['expiry']
                ];
            }
            $medicineGroups[$medicineName]['quantity'] += (int)$row['quantity'];
        }

        // Convert grouped medicines to array
        $medicines = array_values($medicineGroups);
    } catch (Exception $e) {
    }
} catch (PDOException $e) {
    $visitsToday = 0;
    $pendingAppointments = 0;
    $totalStudents = 0;
    $completedToday = 0;
    $activeToday = 0;
    $monthlyRevenue = 0;
    $medicines = [];
}
?>

<!-- Main content -->
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <!-- Dashboard Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Clinic Management Dashboard</h1>
        <p class="text-gray-600">Real-time overview of clinic operations and performance</p>
    </div>
    <!-- Key Performance Indicators -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Visits Today -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Visits Today</p>
                    <p class="text-3xl font-bold text-blue-600"><?= $visitsToday ?></p>
                    <p class="text-gray-500 text-sm">+12% from yesterday</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-calendar-line text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Completed Today -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Appointments Today</p>
                    <p class="text-3xl font-bold text-green-600"><?= $completedToday ?></p>
                    <p class="text-gray-500 text-sm">Active: <?= $activeToday ?></p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-check-line text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Total Patients -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Total Patients</p>
                    <p class="text-3xl font-bold text-red-600"><?= number_format($totalStudents) ?></p>
                    <p class="text-gray-500 text-[11.5px]">Visitors: <?= $visitorCount ?> | Faculty: <?= $facultyCount ?> | Students: <?= $importedPatientsCount ?></p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                    <i class="ri-team-line text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <!-- Monthly Prescriptions -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-gray-600 text-sm font-medium">Monthly Prescriptions</p>
                    <p class="text-3xl font-bold text-gray-600"><?= $monthlyRevenue ?></p>
                    <p class="text-gray-500 text-sm">This month</p>
                </div>
                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                    <i class="ri-medicine-bottle-line text-2xl text-gray-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
        <!-- Left Side - 2 Cards Stacked -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Medicine Stock Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Header Section -->
                <div class="px-4 py-3 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Medicine Stock Status</h3>
                            <p class="text-gray-600 text-xs mt-1">Current inventory levels and alerts</p>
                        </div>
                        <?php if (!empty($medicines)): ?>
                        <!-- Search Bar -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ri-search-line text-gray-400"></i>
                            </div>
                            <input type="text" id="medicineSearch" class="block w-48 pl-10 pr-3 py-1.5 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-xs" placeholder="Search medicines...">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (!empty($medicines)): ?>
                    <!-- Table View -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200" id="medicineTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">MEDICINE</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">STOCK</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">STATUS</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">LEVEL</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="medicineTableBody">
                                <?php foreach ($medicines as $medicine):
                                    $quantity = (int)$medicine['quantity'];
                                    $minQuantity = 50; // Default minimum quantity

                                    // Determine status based on quantity
                                    if ($quantity == 0) {
                                        $status = 'critical';
                                        $statusColor = 'red';
                                        $progressColor = 'red';
                                        $progressWidth = 0;
                                    } elseif ($quantity < 25) {
                                        $status = 'critical';
                                        $statusColor = 'red';
                                        $progressColor = 'red';
                                        $progressWidth = min(($quantity / $minQuantity) * 100, 100);
                                    } elseif ($quantity < $minQuantity) {
                                        $status = 'low';
                                        $statusColor = 'yellow';
                                        $progressColor = 'yellow';
                                        $progressWidth = min(($quantity / $minQuantity) * 100, 100);
                                    } else {
                                        $status = 'good';
                                        $statusColor = 'green';
                                        $progressColor = 'green';
                                        $progressWidth = min(($quantity / $minQuantity) * 100, 100);
                                    }
                                ?>
                                    <tr class="hover:bg-gray-50 medicine-row" data-name="<?= strtolower(htmlspecialchars($medicine['name'])) ?>" data-dosage="<?= strtolower(htmlspecialchars($medicine['dosage'])) ?>">
                                        <td class="px-4 py-2 whitespace-nowrap w-1/3">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($medicine['name']) ?></div>
                                            <div class="text-xs text-gray-500"><?= htmlspecialchars($medicine['dosage']) ?></div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap w-1/6">
                                            <div class="text-sm text-gray-900"><?= $quantity ?></div>
                                            <div class="text-xs text-gray-500">(Min: <?= $minQuantity ?>)</div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap w-1/6">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-<?= $statusColor ?>-100 text-<?= $statusColor ?>-800">
                                                <?= $status ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap w-1/3">
                                            <div class="flex items-center">
                                                <div class="w-12 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-<?= $progressColor ?>-500 h-2 rounded-full" style="width: <?= $progressWidth ?>%"></div>
                                                </div>
                                                <span class="text-xs text-gray-500"><?= round($progressWidth) ?>%</span>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        <div class="flex justify-between items-center">
                            <!-- Records Information -->
                            <div class="text-xs text-gray-500">
                                Showing <span id="showingStart">1</span> to <span id="showingEnd"><?= min(5, count($medicines)) ?></span> of <span id="totalEntries"><?= count($medicines) ?></span> entries
                            </div>

                            <!-- Pagination Navigation -->
                            <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">
                                <!-- Previous Button -->
                                <button id="prevPage" type="button" disabled class="min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Previous">
                                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m15 18-6-6 6-6"></path>
                                    </svg>
                                    <span class="sr-only">Previous</span>
                                </button>

                                <!-- Page Numbers -->
                                <div id="pageNumbers" class="flex items-center">
                                    <!-- Page numbers will be generated by JavaScript -->
                                </div>

                                <!-- Next Button -->
                                <button id="nextPage" type="button" class="min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
                                    <span class="sr-only">Next</span>
                                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>

                    <!-- Summary Stats -->
                    <div class="px-4 py-4 border-t border-gray-200">
                        <div class="grid grid-cols-3 gap-4">
                            <?php
                            $criticalCount = 0;
                            $lowCount = 0;
                            $goodCount = 0;

                            foreach ($medicines as $medicine) {
                                $quantity = (int)$medicine['quantity'];
                                if ($quantity == 0 || $quantity < 25) {
                                    $criticalCount++;
                                } elseif ($quantity < 50) {
                                    $lowCount++;
                                } else {
                                    $goodCount++;
                                }
                            }
                            ?>
                            <div class="bg-red-50 rounded-lg p-3 border border-red-200">
                                <div class="text-center">
                                    <p class="text-xs font-medium text-red-600 mb-1">Critical</p>
                                    <p class="text-2xl font-bold text-red-600"><?= $criticalCount ?></p>
                                </div>
                            </div>

                            <div class="bg-yellow-50 rounded-lg p-3 border border-yellow-200">
                                <div class="text-center">
                                    <p class="text-xs font-medium text-yellow-600 mb-1">Low</p>
                                    <p class="text-2xl font-bold text-yellow-600"><?= $lowCount ?></p>
                                </div>
                            </div>

                            <div class="bg-green-50 rounded-lg p-3 border border-green-200">
                                <div class="text-center">
                                    <p class="text-xs font-medium text-green-600 mb-1">Good</p>
                                    <p class="text-2xl font-bold text-green-600"><?= $goodCount ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="px-6 py-8 text-center">
                        <p class="text-gray-500">No medicines found in the database.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Today's Appointments -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Header Section -->
                <div class="px-4 py-3 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900">Today's Appointments</h3>
                            <p class="text-gray-600 text-xs mt-1">Schedule overview for <?= date('n/j/Y') ?></p>
                        </div>
                        <?php if (!empty($todaysAppointments)): ?>
                        <!-- Search Bar -->
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ri-search-line text-gray-400"></i>
                            </div>
                            <input type="text" id="appointmentSearch" class="block w-48 pl-10 pr-3 py-1.5 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-xs" placeholder="Search appointments...">
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if (empty($todaysAppointments)): ?>
                    <div class="text-center py-8">
                        <div class="text-gray-400 mb-2">
                            <i class="ri-calendar-line text-4xl"></i>
                        </div>
                        <p class="text-gray-500 text-sm">No appointments scheduled for today</p>
                    </div>
                <?php else: ?>
                    <!-- Table View -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 table-fixed" id="appointmentTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TIME</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PATIENT</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">REASON</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200" id="appointmentTableBody">
                                <?php foreach ($todaysAppointments as $appointment): ?>
                                    <?php
                                    // Determine status styling
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($appointment['status']) {
                                        case 'approved':
                                            $statusClass = 'bg-blue-100 text-blue-800';
                                            $statusText = 'completed';
                                            break;
                                        case 'confirmed':
                                            $statusClass = 'bg-cyan-100 text-cyan-800';
                                            $statusText = 'in-progress';
                                            break;
                                        case 'pending':
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            $statusText = 'scheduled';
                                            break;
                                        case 'declined':
                                            $statusClass = 'bg-red-100 text-red-800';
                                            $statusText = 'declined';
                                            break;
                                        case 'rescheduled':
                                            $statusClass = 'bg-yellow-100 text-yellow-800';
                                            $statusText = 'rescheduled';
                                            break;
                                        default:
                                            $statusClass = 'bg-gray-100 text-gray-800';
                                            $statusText = $appointment['status'];
                                    }
                                    
                                    // Format time (remove any extra characters)
                                    $time = preg_replace('/[^0-9:]/', '', $appointment['time']);
                                    $time = substr($time, 0, 5); // Take only HH:MM
                                    
                                    // Get student name or fallback
                                    $studentName = $appointment['student_name'] ?: 'Unknown Student';
                                    ?>
                                    <tr class="hover:bg-gray-50 appointment-row" data-patient="<?= strtolower(htmlspecialchars($studentName)) ?>" data-reason="<?= strtolower(htmlspecialchars($appointment['reason'])) ?>" data-time="<?= strtolower(htmlspecialchars($time)) ?>">
                                        <td class="px-4 py-2">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($time) ?></div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="text-sm text-gray-900 truncate" title="<?= htmlspecialchars($studentName) ?>"><?= htmlspecialchars($studentName) ?></div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <div class="text-sm text-gray-900 truncate" title="<?= htmlspecialchars($appointment['reason']) ?>"><?= htmlspecialchars($appointment['reason']) ?></div>
                                        </td>
                                        <td class="px-4 py-2">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>">
                                                <?= htmlspecialchars($statusText) ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                        <div class="flex justify-between items-center">
                            <!-- Records Information -->
                            <div class="text-xs text-gray-500">
                                Showing <span id="appointmentShowingStart">1</span> to <span id="appointmentShowingEnd"><?= min(5, count($todaysAppointments)) ?></span> of <span id="appointmentTotalEntries"><?= count($todaysAppointments) ?></span> entries
                            </div>

                            <!-- Pagination Navigation -->
                            <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">
                                <!-- Previous Button -->
                                <button id="appointmentPrevPage" type="button" disabled class="min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Previous">
                                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m15 18-6-6 6-6"></path>
                                    </svg>
                                    <span class="sr-only">Previous</span>
                                </button>

                                <!-- Page Numbers -->
                                <div id="appointmentPageNumbers" class="flex items-center">
                                    <!-- Page numbers will be generated by JavaScript -->
                                </div>

                                <!-- Next Button -->
                                <button id="appointmentNextPage" type="button" class="min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
                                    <span class="sr-only">Next</span>
                                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </button>
                            </nav>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Right Side - 3 Cards Stacked -->
        <div class="space-y-6">
            <!-- Staff Status -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <!-- Header Section -->
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Staff Status</h3>
                </div>

                <!-- Staff List -->
                <div class="p-5 space-y-3">
                    <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-green-500 rounded-full mr-2"></div>
                            <span class="text-gray-700 text-sm font-medium">Doctors on Duty</span>
                        </div>
                        <span class="text-xs font-semibold text-green-600"><?= $doctorsCount ?> Active</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-blue-500 rounded-full mr-2"></div>
                            <span class="text-gray-700 text-sm font-medium">Nurses Available</span>
                        </div>
                        <span class="text-xs font-semibold text-blue-600"><?= $nursesCount ?> Active</span>
                    </div>
                    <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                        <div class="flex items-center">
                            <div class="w-2 h-2 bg-purple-500 rounded-full mr-2"></div>
                            <span class="text-gray-700 text-sm font-medium">Admin</span>
                        </div>
                        <span class="text-xs font-semibold text-purple-600"><?= $supportStaffCount ?> Active</span>
                    </div>
                </div>
            </div>

            <!-- System Alerts -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">System Alerts</h3>
                    <p class="text-gray-600 text-sm mt-1">Important notifications and warnings</p>
                </div>
                <div class="p-5 space-y-3">
                    <?php if (empty($systemAlerts)): ?>
                        <div class="flex items-start p-3 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex-shrink-0">
                                <i class="ri-check-line text-green-500 text-lg"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-green-800">All Systems Normal</p>
                                <p class="text-xs text-green-600 mt-1">No alerts at this time</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($systemAlerts as $alert): ?>
                            <div class="flex items-start p-3 bg-<?= $alert['color'] ?>-50 border border-<?= $alert['color'] ?>-200 rounded-lg">
                                <div class="flex-shrink-0">
                                    <i class="<?= $alert['icon'] ?> text-<?= $alert['color'] ?>-500 text-lg"></i>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-<?= $alert['color'] ?>-800"><?= htmlspecialchars($alert['title']) ?></p>
                                    <p class="text-xs text-<?= $alert['color'] ?>-600 mt-1"><?= htmlspecialchars($alert['message']) ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Patient Demographics -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">Patient Demographics</h3>
                    <p class="text-gray-600 text-sm mt-1">Age distribution of active patients</p>
                </div>
                <div class="p-6 space-y-3">
                    <?php if ($totalPatients > 0): ?>
                        <?php
                        $ageGroups = [
                            '0-18' => ['color' => 'bg-blue-500', 'label' => '0-18'],
                            '19-35' => ['color' => 'bg-green-500', 'label' => '19-35'],
                            '36-50' => ['color' => 'bg-yellow-500', 'label' => '36-50'],
                            '51-65' => ['color' => 'bg-orange-500', 'label' => '51-65'],
                            '65+' => ['color' => 'bg-red-500', 'label' => '65+']
                        ];
                        ?>
                        <?php foreach ($ageGroups as $group => $config): ?>
                            <?php
                            $count = $ageDistribution[$group];
                            $percentage = $totalPatients > 0 ? round(($count / $totalPatients) * 100) : 0;
                            ?>
                            <div class="flex items-center justify-between">
                                <span class="text-gray-700 text-sm"><?= $config['label'] ?></span>
                                <div class="flex items-center">
                                    <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                        <div class="<?= $config['color'] ?> h-2 rounded-full" style="width: <?= $percentage ?>%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-900"><?= $count ?> (<?= $percentage ?>%)</span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <div class="text-gray-400 mb-2">
                                <i class="ri-user-line text-4xl"></i>
                            </div>
                            <p class="text-gray-500 text-sm">No patient data available</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Visit Trends - Bottom Full Width -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-8">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900">Monthly Visit Trends</h3>
            <p class="text-gray-600 text-sm mt-1">Frequent illness reasons over the past 12 months</p>
        </div>
        <div class="p-6">
            <div id="monthlyVisitsChart" class="w-full h-[300px]"></div>
        </div>
    </div>



</main>
</div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Drop zone functionality
        const dropZone = document.getElementById('dropZone');
        const fileInput = document.getElementById('fileInput');
        if (dropZone && fileInput) {
            dropZone.addEventListener('click', () => {
                fileInput.click();
            });
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.classList.add('active');
            });
            dropZone.addEventListener('dragleave', () => {
                dropZone.classList.remove('active');
            });
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.classList.remove('active');
                if (e.dataTransfer.files.length) {
                    fileInput.files = e.dataTransfer.files;
                    // Handle file upload logic here
                }
            });
            fileInput.addEventListener('change', () => {
                // Handle file upload logic here
            });
        }
    });
    document.addEventListener('DOMContentLoaded', function() {
        // Custom select functionality
        const customSelects = document.querySelectorAll('.custom-select');
        customSelects.forEach(select => {
            const trigger = select.querySelector('.custom-select-trigger');
            const options = select.querySelectorAll('.custom-select-option');
            const selectedText = trigger.querySelector('span');
            trigger.addEventListener('click', () => {
                select.classList.toggle('open');
            });
            options.forEach(option => {
                option.addEventListener('click', () => {
                    selectedText.textContent = option.textContent;
                    select.classList.remove('open');
                });
            });
            document.addEventListener('click', (e) => {
                if (!select.contains(e.target)) {
                    select.classList.remove('open');
                }
            });
        });
    });
    document.addEventListener('DOMContentLoaded', function() {
        // Monthly Visit Trends Chart - Frequent Illness Reasons
        const monthlyVisitsChartElement = document.getElementById('monthlyVisitsChart');
        if (!monthlyVisitsChartElement) {
            console.error('Monthly visits chart container not found');
            return;
        }
        const monthlyVisitsChart = echarts.init(monthlyVisitsChartElement);
        
        // Map normalized keys to display labels (First letter uppercase)
        const topReasons = <?= json_encode($topReasons) ?>;
        const reasonDisplay = <?= json_encode($topReasonsDisplay) ?>;
        const monthlyLabels = <?= json_encode($monthlyLabels) ?>;
        const monthlySeries = <?= json_encode($monthlySeries) ?>;

        function buildMonthlyOption(withDots = true) {
            const palette = ['#4F46E5', '#60A5FA', '#10B981', '#F59E0B', '#EF4444'];
            return {
                tooltip: { trigger: 'axis' },
                legend: {
                    data: topReasons.map(k => reasonDisplay[k] || k),
                    top: 10,
                    right: 10,
                    orient: 'horizontal',
                    textStyle: { color: '#373d3f', fontWeight: 'bold', fontSize: 14 }
                },
                grid: { left: '3%', right: '4%', bottom: '3%', top: 60, containLabel: true, borderColor: '#D9DBF3' },
                xAxis: {
                    type: 'category',
                    data: monthlyLabels,
                    axisLine: { lineStyle: { color: '#e5e7eb' } },
                    axisLabel: { color: '#6b7280' },
                    splitLine: { show: true, lineStyle: { color: '#D9DBF3' } },
                    tooltip: { show: false }
                },
                yAxis: {
                    type: 'value',
                    axisLine: { show: false },
                    axisLabel: { color: '#6b7280' },
                    splitLine: { lineStyle: { color: '#f3f4f6' } }
                },
                series: topReasons.map((r, idx) => ({
                    name: reasonDisplay[r] || r,
                    type: 'line',
                    smooth: true,
                    symbol: withDots ? 'circle' : 'none',
                    symbolSize: 6,
                    lineStyle: { width: 2 },
                    itemStyle: { color: palette[idx % palette.length] },
                    data: (monthlySeries[r] || Array(monthlyLabels.length).fill(0)),
                    emphasis: {
                        focus: 'series',
                        itemStyle: {
                            borderWidth: 0,
                            shadowBlur: 8,
                            shadowColor: palette[idx % palette.length],
                            symbolSize: 9
                        }
                    },
                    animationDuration: 600,
                    animationEasing: 'cubicOut',
                    animationDelay: function (idx) { return 0; },
                    animationDurationUpdate: 600,
                    animationEasingUpdate: 'cubicOut'
                }))
            };
        }

        // Step 1: Draw line only
        monthlyVisitsChart.setOption(buildMonthlyOption(false));
        // Step 2: Show dots after line animation
        setTimeout(() => {
            monthlyVisitsChart.setOption(buildMonthlyOption(true));
        }, 600);

        // Resize charts when window size changes
        window.addEventListener('resize', function() {
            if (monthlyVisitsChart) {
                monthlyVisitsChart.resize();
            }
        });

        // Medicine Stock Search and Pagination
        const medicineSearch = document.getElementById('medicineSearch');
        const medicineTableBody = document.getElementById('medicineTableBody');
        const medicineRows = document.querySelectorAll('.medicine-row');
        const prevPageBtn = document.getElementById('prevPage');
        const nextPageBtn = document.getElementById('nextPage');
        const pageNumbers = document.getElementById('pageNumbers');
        const showingStart = document.getElementById('showingStart');
        const showingEnd = document.getElementById('showingEnd');
        const totalEntries = document.getElementById('totalEntries');

        let currentPage = 1;
        const itemsPerPage = 5;
        let filteredRows = Array.from(medicineRows);

        // Search functionality
        if (medicineSearch) {
            medicineSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                filteredRows = Array.from(medicineRows).filter(row => {
                    const name = row.dataset.name;
                    const dosage = row.dataset.dosage;
                    return name.includes(searchTerm) || dosage.includes(searchTerm);
                });
                currentPage = 1;
                updateTable();
                updatePagination();
            });
        }

        // Pagination functionality
        function updateTable() {
            const startIndex = (currentPage - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;
            const currentRows = filteredRows.slice(startIndex, endIndex);

            // Hide all rows
            medicineRows.forEach(row => row.style.display = 'none');

            // Show current page rows
            currentRows.forEach(row => row.style.display = '');

            // Update showing text
            showingStart.textContent = filteredRows.length > 0 ? startIndex + 1 : 0;
            showingEnd.textContent = Math.min(endIndex, filteredRows.length);
            totalEntries.textContent = filteredRows.length;
        }

        function updatePagination() {
            const totalPages = Math.ceil(filteredRows.length / itemsPerPage);

            // Clear existing page numbers
            pageNumbers.innerHTML = '';

            // Previous button
            prevPageBtn.disabled = currentPage === 1;

            // Page numbers - match admin/users.php logic exactly
            let startPage = Math.max(1, currentPage - 2);
            let endPage = Math.min(totalPages, currentPage + 2);

            // Show first page if not in range
            if (startPage > 1) {
                createPageButton(1);
                if (startPage > 2) {
                    createEllipsis();
                }
            }

            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                createPageButton(i);
            }

            // Show last page if not in range
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    createEllipsis();
                }
                createPageButton(totalPages);
            }

            // Next button
            nextPageBtn.disabled = currentPage === totalPages || totalPages === 0;
        }

        function createPageButton(pageNum) {
            const button = document.createElement('button');
            button.textContent = pageNum;
            button.type = 'button';
            button.className = `min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 py-1 px-2 text-xs focus:outline-hidden ${
                currentPage === pageNum 
                    ? 'bg-gray-200 focus:bg-gray-300' 
                    : 'hover:bg-gray-100 focus:bg-gray-100'
            }`;
            if (currentPage === pageNum) {
                button.setAttribute('aria-current', 'page');
            }
            button.addEventListener('click', () => {
                currentPage = pageNum;
                updateTable();
                updatePagination();
            });
            pageNumbers.appendChild(button);
        }

        function createEllipsis() {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 py-1 px-2 text-xs';
            pageNumbers.appendChild(ellipsis);
        }

        // Navigation buttons
        if (prevPageBtn) {
            prevPageBtn.addEventListener('click', () => {
                if (currentPage > 1) {
                    currentPage--;
                    updateTable();
                    updatePagination();
                }
            });
        }

        if (nextPageBtn) {
            nextPageBtn.addEventListener('click', () => {
                const totalPages = Math.ceil(filteredRows.length / itemsPerPage);
                if (currentPage < totalPages) {
                    currentPage++;
                    updateTable();
                    updatePagination();
                }
            });
        }

        // Initialize
        updateTable();
        updatePagination();

        // Appointment Search and Pagination
        const appointmentSearch = document.getElementById('appointmentSearch');
        const appointmentTableBody = document.getElementById('appointmentTableBody');
        const appointmentRows = document.querySelectorAll('.appointment-row');
        const appointmentPrevPageBtn = document.getElementById('appointmentPrevPage');
        const appointmentNextPageBtn = document.getElementById('appointmentNextPage');
        const appointmentPageNumbers = document.getElementById('appointmentPageNumbers');
        const appointmentShowingStart = document.getElementById('appointmentShowingStart');
        const appointmentShowingEnd = document.getElementById('appointmentShowingEnd');
        const appointmentTotalEntries = document.getElementById('appointmentTotalEntries');

        let appointmentCurrentPage = 1;
        const appointmentItemsPerPage = 5;
        let appointmentFilteredRows = Array.from(appointmentRows);

        // Search functionality
        if (appointmentSearch) {
            appointmentSearch.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                appointmentFilteredRows = Array.from(appointmentRows).filter(row => {
                    const patient = row.dataset.patient;
                    const reason = row.dataset.reason;
                    const time = row.dataset.time;
                    return patient.includes(searchTerm) || reason.includes(searchTerm) || time.includes(searchTerm);
                });
                appointmentCurrentPage = 1;
                updateAppointmentTable();
                updateAppointmentPagination();
            });
        }

        // Pagination functionality
        function updateAppointmentTable() {
            const startIndex = (appointmentCurrentPage - 1) * appointmentItemsPerPage;
            const endIndex = startIndex + appointmentItemsPerPage;
            const currentRows = appointmentFilteredRows.slice(startIndex, endIndex);

            // Hide all rows
            appointmentRows.forEach(row => row.style.display = 'none');

            // Show current page rows
            currentRows.forEach(row => row.style.display = '');

            // Update showing text
            appointmentShowingStart.textContent = appointmentFilteredRows.length > 0 ? startIndex + 1 : 0;
            appointmentShowingEnd.textContent = Math.min(endIndex, appointmentFilteredRows.length);
            appointmentTotalEntries.textContent = appointmentFilteredRows.length;
        }

        function updateAppointmentPagination() {
            const totalPages = Math.ceil(appointmentFilteredRows.length / appointmentItemsPerPage);

            // Clear existing page numbers
            appointmentPageNumbers.innerHTML = '';

            // Previous button
            appointmentPrevPageBtn.disabled = appointmentCurrentPage === 1;

            // Page numbers - match admin/users.php logic exactly
            let startPage = Math.max(1, appointmentCurrentPage - 2);
            let endPage = Math.min(totalPages, appointmentCurrentPage + 2);

            // Show first page if not in range
            if (startPage > 1) {
                createAppointmentPageButton(1);
                if (startPage > 2) {
                    createAppointmentEllipsis();
                }
            }

            // Page numbers
            for (let i = startPage; i <= endPage; i++) {
                createAppointmentPageButton(i);
            }

            // Show last page if not in range
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    createAppointmentEllipsis();
                }
                createAppointmentPageButton(totalPages);
            }

            // Next button
            appointmentNextPageBtn.disabled = appointmentCurrentPage === totalPages || totalPages === 0;
        }

        function createAppointmentPageButton(pageNum) {
            const button = document.createElement('button');
            button.textContent = pageNum;
            button.type = 'button';
            button.className = `min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 py-1 px-2 text-xs focus:outline-hidden ${
                appointmentCurrentPage === pageNum 
                    ? 'bg-gray-200 focus:bg-gray-300' 
                    : 'hover:bg-gray-100 focus:bg-gray-100'
            }`;
            if (appointmentCurrentPage === pageNum) {
                button.setAttribute('aria-current', 'page');
            }
            button.addEventListener('click', () => {
                appointmentCurrentPage = pageNum;
                updateAppointmentTable();
                updateAppointmentPagination();
            });
            appointmentPageNumbers.appendChild(button);
        }

        function createAppointmentEllipsis() {
            const ellipsis = document.createElement('span');
            ellipsis.textContent = '...';
            ellipsis.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 py-1 px-2 text-xs';
            appointmentPageNumbers.appendChild(ellipsis);
        }

        // Navigation buttons
        if (appointmentPrevPageBtn) {
            appointmentPrevPageBtn.addEventListener('click', () => {
                if (appointmentCurrentPage > 1) {
                    appointmentCurrentPage--;
                    updateAppointmentTable();
                    updateAppointmentPagination();
                }
            });
        }

        if (appointmentNextPageBtn) {
            appointmentNextPageBtn.addEventListener('click', () => {
                const totalPages = Math.ceil(appointmentFilteredRows.length / appointmentItemsPerPage);
                if (appointmentCurrentPage < totalPages) {
                    appointmentCurrentPage++;
                    updateAppointmentTable();
                    updateAppointmentPagination();
                }
            });
        }

        // Initialize appointment table
        updateAppointmentTable();
        updateAppointmentPagination();
    });
</script>

<style>
    /* Fixed column widths and ellipses for Today's Appointments table */
    #appointmentTable {
        table-layout: fixed;
        width: 100%;
    }
    
    /* Today's Appointments table column widths */
    #appointmentTable th:nth-child(1),
    #appointmentTable td:nth-child(1) {
        width: 20%; /* TIME */
    }
    
    #appointmentTable th:nth-child(2),
    #appointmentTable td:nth-child(2) {
        width: 30%; /* PATIENT */
    }
    
    #appointmentTable th:nth-child(3),
    #appointmentTable td:nth-child(3) {
        width: 35%; /* REASON */
    }
    
    #appointmentTable th:nth-child(4),
    #appointmentTable td:nth-child(4) {
        width: 15%; /* STATUS */
    }
    
    /* Text truncation with ellipses for Today's Appointments table */
    #appointmentTable td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Allow status badges to display properly */
    #appointmentTable td:nth-child(4) {
        white-space: normal;
    }
</style>

<?php
include '../includea/footer.php';
?>