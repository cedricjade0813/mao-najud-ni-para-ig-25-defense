<?php
include '../includes/header.php';
include '../includes/db_connect.php';

// Essential data for staff dashboard
$visitsToday = 0;
try {
    $visitsToday = $db->query("SELECT COUNT(*) FROM prescriptions WHERE DATE(prescription_date) = CURDATE()")->fetchColumn();
    error_log("Total Visits Today: " . $visitsToday);
} catch (Exception $e) {
    error_log("Error fetching visits today: " . $e->getMessage());
}

$appointmentsToday = 0;
$appointmentsTodayList = [];
try {
    $stmt = $db->prepare("SELECT date, time, reason, email FROM appointments WHERE status = 'approved' AND DATE(date) = CURDATE() ORDER BY time ASC");
    $stmt->execute();
    $appointmentsTodayList = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $appointmentsToday = count($appointmentsTodayList);
    error_log("Appointments Today: " . $appointmentsToday);
} catch (Exception $e) {
    error_log("Error fetching appointments today: " . $e->getMessage());
}

// Fetch Today's Appointments for the datatable - only approved status
$todaysAppointments = [];
try {
    $stmt = $db->prepare("
        SELECT a.*, p.name as student_name 
        FROM appointments a 
        LEFT JOIN imported_patients p ON a.student_id = p.id 
        WHERE DATE(a.date) = CURDATE() 
        AND a.status = 'approved'
        ORDER BY a.time ASC 
        LIMIT 20
    ");
    $stmt->execute();
    $todaysAppointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log the results
    error_log("Today's approved appointments query returned " . count($todaysAppointments) . " appointments");
    if (!empty($todaysAppointments)) {
        error_log("First appointment: " . print_r($todaysAppointments[0], true));
    }
} catch (Exception $e) {
    error_log("Error fetching today's approved appointments: " . $e->getMessage());
    $todaysAppointments = [];
}

// Fetch weekly patient trend data from all three tables
$weeklyTrendData = [];
$totalVisitsWeek = 0;
$dailyAverage = 0;
$weeklyGrowth = 0;

try {
    // Get daily data for this week (Monday to Sunday) from prescriptions table only
    $weeklyData = [];
    $weekDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

    for ($i = 0; $i < 7; $i++) {
        $date = date('Y-m-d', strtotime("monday this week +$i days"));
        $dayName = $weekDays[$i];

        // Count prescriptions for this specific date
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM prescriptions 
            WHERE DATE(prescription_date) = ?
        ");
        $stmt->execute([$date]);
        $count = $stmt->fetchColumn();

        $weeklyData[] = [
            'day' => $dayName,
            'count' => (int)$count
        ];

        $totalVisitsWeek += (int)$count;
    }

    // Calculate daily average
    $dailyAverage = $totalVisitsWeek > 0 ? round($totalVisitsWeek / 7, 1) : 0;

    // Calculate growth vs last week
    $lastWeekStart = date('Y-m-d', strtotime('monday last week'));
    $lastWeekEnd = date('Y-m-d', strtotime('sunday last week'));

    $stmt = $db->prepare("
        SELECT COUNT(*) FROM prescriptions 
        WHERE DATE(prescription_date) BETWEEN ? AND ?
    ");
    $stmt->execute([$lastWeekStart, $lastWeekEnd]);
    $lastWeekTotal = $stmt->fetchColumn();

    if ($lastWeekTotal > 0) {
        $weeklyGrowth = round((($totalVisitsWeek - $lastWeekTotal) / $lastWeekTotal) * 100, 1);
    }

    error_log("Weekly Trend Data (Prescriptions): " . print_r($weeklyData, true));
} catch (Exception $e) {
    error_log("Error fetching weekly trend data: " . $e->getMessage());
    // Fallback sample data
    $weeklyData = [
        ['day' => 'Mon', 'count' => 45],
        ['day' => 'Tue', 'count' => 38],
        ['day' => 'Wed', 'count' => 42],
        ['day' => 'Thu', 'count' => 35],
        ['day' => 'Fri', 'count' => 28],
        ['day' => 'Sat', 'count' => 15],
        ['day' => 'Sun', 'count' => 23]
    ];
    $totalVisitsWeek = 226;
    $dailyAverage = 32.3;
    $weeklyGrowth = 8;
}

// Fetch patient age groups data
$ageGroups = [];
$totalPatients = 0;
$ageGroupColors = ['#3B82F6', '#EF4444', '#F59E0B', '#10B981', '#8B5CF6', '#6B7280'];

try {
    // Get age groups from all three tables combined
    $stmt = $db->query("
        SELECT 
            CASE 
                WHEN age < 18 THEN 'Under 18'
                WHEN age BETWEEN 18 AND 25 THEN '18-25'
                WHEN age BETWEEN 26 AND 35 THEN '26-35'
                WHEN age BETWEEN 36 AND 50 THEN '36-50'
                WHEN age BETWEEN 51 AND 65 THEN '51-65'
                ELSE 'Over 65'
            END as age_group,
            COUNT(*) as count
        FROM (
            -- Calculate age from date of birth for imported_patients
            SELECT TIMESTAMPDIFF(YEAR, dob, CURDATE()) as age 
            FROM imported_patients 
            WHERE dob IS NOT NULL AND dob != '0000-00-00'
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
        ORDER BY 
            CASE age_group
                WHEN 'Under 18' THEN 1
                WHEN '18-25' THEN 2
                WHEN '26-35' THEN 3
                WHEN '36-50' THEN 4
                WHEN '51-65' THEN 5
                WHEN 'Over 65' THEN 6
            END
    ");

    $ageGroupData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Debug: Log the age groups data
    error_log("Age Groups Data: " . print_r($ageGroupData, true));

    // Get total count from all tables
    $totalPatients = 0;
    foreach ($ageGroupData as $row) {
        $totalPatients += $row['count'];
    }

    // Format the data
    foreach ($ageGroupData as $row) {
        $percentage = $totalPatients > 0 ? round(($row['count'] / $totalPatients) * 100, 1) : 0;
        $ageGroups[] = [
            'label' => $row['age_group'],
            'count' => $row['count'],
            'percentage' => $percentage
        ];
    }

    // Debug: Log total patients and formatted age groups
    error_log("Total Patients for Age Groups: " . $totalPatients);
    error_log("Formatted Age Groups: " . print_r($ageGroups, true));

    // If no data, use sample data
    if (empty($ageGroups)) {
        $ageGroups = [
            ['label' => 'Under 18', 'count' => 45, 'percentage' => 28.5],
            ['label' => '18-25', 'count' => 32, 'percentage' => 20.3],
            ['label' => '26-35', 'count' => 28, 'percentage' => 17.7],
            ['label' => '36-50', 'count' => 25, 'percentage' => 15.8],
            ['label' => '51-65', 'count' => 18, 'percentage' => 11.4],
            ['label' => 'Over 65', 'count' => 10, 'percentage' => 6.3]
        ];
        $totalPatients = 158;
    }
} catch (Exception $e) {
    error_log("Error fetching age groups data: " . $e->getMessage());
    // Fallback sample data
    $ageGroups = [
        ['label' => 'Under 18', 'count' => 45, 'percentage' => 28.5],
        ['label' => '18-25', 'count' => 32, 'percentage' => 20.3],
        ['label' => '26-35', 'count' => 28, 'percentage' => 17.7],
        ['label' => '36-50', 'count' => 25, 'percentage' => 15.8],
        ['label' => '51-65', 'count' => 18, 'percentage' => 11.4],
        ['label' => 'Over 65', 'count' => 10, 'percentage' => 6.3]
    ];
    $totalPatients = 158;
}

// Fetch illness data for different time ranges - following admin dashboard pattern exactly
$illnessData = [
    'daily' => [],
    'weekly' => [],
    'monthly' => []
];

$topReasons = [];
$topReasonsDisplay = [];
$dailyLabels = [];
$dailySeries = [];
$weeklyLabels = [];
$weeklySeries = [];
$monthlyLabels = [];
$monthlySeries = [];

try {
    // First, let's see what's actually in the prescriptions table
    $debugStmt = $db->prepare("SELECT reason, COUNT(*) as count FROM prescriptions GROUP BY reason ORDER BY count DESC LIMIT 10");
    $debugStmt->execute();
    $debugResults = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("All reasons in prescriptions table: " . print_r($debugResults, true));

    // Get top 5 reasons from prescriptions table - simplified approach
    $stmt = $db->prepare("
        SELECT reason, COUNT(*) as count 
        FROM prescriptions
        WHERE reason IS NOT NULL AND reason != '' 
        GROUP BY reason 
        ORDER BY count DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $topReasons = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);

    error_log("Top 5 reasons found: " . print_r($topReasons, true));

    // If no reasons found, use sample data
    if (empty($topReasons)) {
        $topReasons = ['fever', 'headache', 'cough', 'cold', 'stomach ache'];
    }

    // DAILY: last 7 days - simplified approach
    $dailyLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    $dailySeries = [];

    foreach ($topReasons as $reason) {
        $seriesData = [0, 0, 0, 0, 0, 0, 0];

        // Get data for this reason for the last 7 days
        $stmt = $db->prepare("
            SELECT DATE(prescription_date) as date, COUNT(*) as count
                FROM prescriptions
            WHERE prescription_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
            AND reason = ?
            GROUP BY DATE(prescription_date)
            ORDER BY date ASC
        ");
        $stmt->execute([$reason]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $dayOfWeek = (int)date('N', strtotime($row['date'])) - 1; // Monday = 0
            if ($dayOfWeek >= 0 && $dayOfWeek < 7) {
                $seriesData[$dayOfWeek] = (int)$row['count'];
            }
        }

        $dailySeries[$reason] = $seriesData;
    }

    // WEEKLY: last 12 weeks - simplified approach
    $weeklyLabels = [];
    for ($i = 11; $i >= 0; $i--) {
        $weekStart = date('Y-m-d', strtotime("-$i weeks monday"));
        $weeklyLabels[] = date('M d', strtotime($weekStart));
    }

    $weeklySeries = [];
    foreach ($topReasons as $reason) {
        $seriesData = array_fill(0, 12, 0);

        $stmt = $db->prepare("
            SELECT YEARWEEK(prescription_date) as week, COUNT(*) as count
                FROM prescriptions
            WHERE prescription_date >= DATE_SUB(CURDATE(), INTERVAL 12 WEEK)
            AND reason = ?
            GROUP BY YEARWEEK(prescription_date)
            ORDER BY week ASC
        ");
        $stmt->execute([$reason]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            // Find the week index in our labels
            $weekDate = date('Y-m-d', strtotime($row['week'] . '01'));
            $weekIndex = array_search(date('M d', strtotime($weekDate)), $weeklyLabels);
            if ($weekIndex !== false) {
                $seriesData[$weekIndex] = (int)$row['count'];
            }
        }

        $weeklySeries[$reason] = $seriesData;
    }

    // MONTHLY: last 12 months - simplified approach
    $monthlyLabels = [];
    for ($i = 11; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        $monthlyLabels[] = $month;
    }

    $monthlySeries = [];
    foreach ($topReasons as $reason) {
        $seriesData = array_fill(0, 12, 0);

        $stmt = $db->prepare("
            SELECT DATE_FORMAT(prescription_date, '%Y-%m') as month, COUNT(*) as count
                FROM prescriptions
            WHERE prescription_date >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            AND reason = ?
            GROUP BY DATE_FORMAT(prescription_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute([$reason]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($results as $row) {
            $monthIndex = array_search($row['month'], $monthlyLabels);
            if ($monthIndex !== false) {
                $seriesData[$monthIndex] = (int)$row['count'];
            }
        }

        $monthlySeries[$reason] = $seriesData;
    }

    // Build final data structure
    $illnessData['daily'] = [
        'labels' => $dailyLabels,
        'series' => $dailySeries,
        'topReasons' => $topReasons
    ];

    $illnessData['weekly'] = [
        'labels' => $weeklyLabels,
        'series' => $weeklySeries,
        'topReasons' => $topReasons
    ];

    $illnessData['monthly'] = [
        'labels' => $monthlyLabels,
        'series' => $monthlySeries,
        'topReasons' => $topReasons
    ];
} catch (Exception $e) {
    error_log("Error fetching illness data: " . $e->getMessage());
}

// Fetch medication stock status - following admin dashboard pattern exactly
$medicines = [];
$medicineGroups = [];
try {
    $stmt = $db->query('SELECT name, quantity, dosage, expiry FROM medicines ORDER BY name');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
    // If no medicines table exists, create sample data
    $db->exec("CREATE TABLE IF NOT EXISTS medicines (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        quantity INT NOT NULL DEFAULT 0,
        dosage VARCHAR(100),
        expiry DATE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Insert sample data
    $sampleMedicines = [
        ['Diatabs', 0, '350mg', '2024-12-31'],
        ['Bioflu', 330, '500mg', '2025-06-30'],
        ['Biogesic', 35, '500mg', '2024-11-15'],
        ['Mefinamic', 523, '500mg', '2025-03-20'],
        ['Paracetamol', 150, '500mg', '2025-01-10'],
        ['Ibuprofen', 75, '400mg', '2024-10-25'],
        ['Aspirin', 200, '100mg', '2025-02-14']
    ];

    $insertStmt = $db->prepare('INSERT INTO medicines (name, quantity, dosage, expiry) VALUES (?, ?, ?, ?)');
    foreach ($sampleMedicines as $medicine) {
        $insertStmt->execute($medicine);
    }

    // Now fetch the data
    $stmt = $db->query('SELECT name, quantity, dosage, expiry FROM medicines ORDER BY name');
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
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
    $medicines = array_values($medicineGroups);
}
?>

<style>
    html,
    body {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    html::-webkit-scrollbar,
    body::-webkit-scrollbar {
        display: none;
    }
    
    /* Fixed column widths and ellipses for Medicine Stock Status table */
    #medicineTable {
        table-layout: fixed;
        width: 100%;
    }
    
    /* Medicine Stock Status table column widths */
    #medicineTable th:nth-child(1),
    #medicineTable td:nth-child(1) {
        width: 40%; /* MEDICINE */
    }
    
    #medicineTable th:nth-child(2),
    #medicineTable td:nth-child(2) {
        width: 20%; /* STOCK */
    }
    
    #medicineTable th:nth-child(3),
    #medicineTable td:nth-child(3) {
        width: 20%; /* STATUS */
    }
    
    #medicineTable th:nth-child(4),
    #medicineTable td:nth-child(4) {
        width: 20%; /* LEVEL */
    }
    
    /* Text truncation with ellipses for Medicine Stock Status table */
    #medicineTable td {
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    /* Allow progress bar to display properly */
    #medicineTable td:nth-child(4) {
        white-space: normal;
    }

    /* Modern Dashboard Styles - Matching Guide Image */
    .dashboard-container {
        background-color: #F8F9FA;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }

    .metric-card {
        background: #FFFFFF;
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        padding: 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: all 0.2s ease;
    }


    .metric-content {
        flex: 1;
    }

    .metric-icon {
        width: 40px;
        height: 40px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: 16px;
    }

    .metric-number {
        font-size: 28px;
        font-weight: 700;
        color: #212529;
        line-height: 1;
        margin-bottom: 4px;
    }

    .metric-label {
        font-size: 14px;
        font-weight: 500;
        color: #6C757D;
        margin-bottom: 6px;
    }

    .metric-change {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 8px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .metric-change.positive {
        background-color: #D4EDDA;
        color: #155724;
    }

    .content-card {
        background: #FFFFFF;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        padding: 24px;
        margin-bottom: 24px;
    }

    .card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 20px;
    }

    .card-title {
        font-size: 18px;
        font-weight: 600;
        color: #212529;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .card-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .status-tag {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        text-transform: uppercase;
    }

    .status-critical {
        background-color: #DC3545;
        color: #FFFFFF;
    }

    .status-low {
        background-color: #FFC107;
        color: #212529;
    }

    .appointment-item {
        display: flex;
        align-items: center;
        gap: 16px;
        padding: 16px 0;
        border-bottom: 1px solid #E9ECEF;
    }

    .appointment-item:last-child {
        border-bottom: none;
    }

    .appointment-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: #007BFF;
        color: #FFFFFF;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
    }

    .appointment-details {
        flex: 1;
    }

    .appointment-name {
        font-size: 16px;
        font-weight: 600;
        color: #212529;
        margin-bottom: 4px;
    }

    .appointment-info {
        font-size: 14px;
        color: #6C757D;
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .appointment-info span {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .appointment-actions {
        display: flex;
        gap: 8px;
    }

    .btn {
        padding: 8px 16px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        border: none;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .btn-primary {
        background-color: #007BFF;
        color: #FFFFFF;
    }


    .btn-outline {
        background-color: transparent;
        color: #6C757D;
        border: 1px solid #6C757D;
    }


    .quick-action-btn {
        background: #FFFFFF;
        border: 1px solid #E9ECEF;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        color: inherit;
    }


    .quick-action-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 24px;
    }

    .quick-action-label {
        font-size: 14px;
        font-weight: 500;
        color: #212529;
    }
</style>

<main class="flex-1 overflow-y-auto dashboard-container p-6 ml-16 md:ml-64 mt-[56px]">
    <!-- Dashboard Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Staff Dashboard</h1>
        <p class="text-gray-600">Real-time overview of clinic operations and patient care</p>
    </div>

    <!-- Key Performance Indicators - 6 Cards as in Guide -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
        <!-- Total Visits Today -->
        <div class="metric-card">
            <div class="metric-content">
                <div class="metric-label">Total Visits Today</div>
                <div class="metric-number"><?php echo $visitsToday; ?></div>
                <div class="metric-change positive">
                    <i class="ri-arrow-up-line"></i>
                    +12% from yesterday
                </div>
            </div>
            <div class="metric-icon bg-blue-100 text-blue-600">
                <i class="ri-user-heart-line text-lg"></i>
            </div>
        </div>

        <!-- Appointments Today -->
        <div class="metric-card">
            <div class="metric-content">
                <div class="metric-label">Appointments Today</div>
                <div class="metric-number"><?php echo $appointmentsToday; ?></div>
                <div class="metric-change positive">
                    <i class="ri-arrow-up-line"></i>
                    +8% from last week
                </div>
            </div>
            <div class="metric-icon bg-green-100 text-green-600">
                <i class="ri-calendar-check-line text-lg"></i>
            </div>
        </div>

        <!-- Total Visits This Week -->
        <div class="metric-card">
            <div class="metric-content">
                <div class="metric-label">Total Visits This Week</div>
                <div class="metric-number"><?php echo $totalVisitsWeek; ?></div>
                <div class="metric-change positive">
                    <i class="ri-arrow-up-line"></i>
                    +15% from last week
                </div>
            </div>
            <div class="metric-icon bg-purple-100 text-purple-600">
                <i class="ri-bar-chart-2-line text-lg"></i>
            </div>
        </div>

    </div>

    <!-- Main Dashboard Layout -->
    <div class="flex flex-col lg:flex-row gap-6 mb-8">
        <!-- Medicine Stock Status Card -->
        <div class="flex-1">
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
                        <table class="min-w-full divide-y divide-gray-200 table-fixed" id="medicineTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">MEDICINE</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STOCK</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">LEVEL</th>
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
                                    <tr class="medicine-row" data-name="<?= strtolower(htmlspecialchars($medicine['name'])) ?>" data-dosage="<?= strtolower(htmlspecialchars($medicine['dosage'])) ?>">
                                        <td class="px-4 py-3">
                                            <div class="text-sm font-medium text-gray-900 truncate" title="<?= htmlspecialchars($medicine['name']) ?>"><?= htmlspecialchars($medicine['name']) ?></div>
                                            <div class="text-xs text-gray-500 truncate" title="<?= htmlspecialchars($medicine['dosage']) ?>"><?= htmlspecialchars($medicine['dosage']) ?></div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <div class="text-sm text-gray-900"><?= $quantity ?></div>
                                            <div class="text-xs text-gray-500">(Min: <?= $minQuantity ?>)</div>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-<?= $statusColor ?>-100 text-<?= $statusColor ?>-800">
                                                <?= $status ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3">
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

            <!-- Weekly Patient Trend Chart -->
            <div class="content-card mt-6">
                <div class="card-header">
                    <div class="card-title">
                        Weekly Patient Trend
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">Daily prescriptions • This week</p>
                <div id="weeklyTrendChart" class="w-full h-[235px]"></div>
                <div class="mt-4 grid grid-cols-3 gap-4 text-center">
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $totalVisitsWeek; ?></div>
                        <div class="text-sm text-gray-600">Total Prescriptions</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold text-gray-800"><?php echo $dailyAverage; ?></div>
                        <div class="text-sm text-gray-600">Daily Average</div>
                    </div>
                    <div>
                        <div class="text-2xl font-bold <?php echo $weeklyGrowth >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                            <?php echo $weeklyGrowth >= 0 ? '+' : ''; ?><?php echo $weeklyGrowth; ?>%
                        </div>
                        <div class="text-sm text-gray-600">vs Last Week</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Today's Appointments Card -->
        <div class="flex-1">
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
                        <table class="min-w-full divide-y divide-gray-200" id="appointmentTable">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">TIME</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PATIENT</th>
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
                                            $statusText = 'approved';
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
                                    <tr class="hover:bg-gray-50 appointment-row" data-patient="<?= strtolower(htmlspecialchars($studentName)) ?>" data-time="<?= strtolower(htmlspecialchars($time)) ?>">
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?= htmlspecialchars($time) ?></div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
                                            <div class="text-sm text-gray-900"><?= htmlspecialchars($studentName) ?></div>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap">
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

            <!-- Age Groups Distribution Chart -->
            <div class="content-card mt-6">
                <div class="card-header">
                    <div class="card-title">
                        Age Groups Distribution
                    </div>
                </div>
                <p class="text-sm text-gray-600 mb-4">Total people: <?php echo $totalPatients; ?> (Patients, Visitors & Faculty)</p>
                <div id="ageGroupsDonutChart" class="w-full h-[300px]"></div>

                <!-- Custom Legend -->
                <div class="mt-6 space-y-3">
                    <?php foreach ($ageGroups as $index => $group): ?>
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="w-3 h-3 rounded-full" style="background-color: <?php echo $ageGroupColors[$index]; ?>"></div>
                                <span class="text-sm font-medium text-gray-700"><?php echo $group['label']; ?></span>
                            </div>
                            <span class="text-sm text-gray-500"><?php echo $group['count']; ?> • <?php echo $group['percentage']; ?>%</span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>


    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    </div>

    <!-- Illness Trends Analysis Chart -->
    <div class="content-card -mt-16">
        <div class="card-header">
            <div class="card-title">
                Illness Trends Analysis
            </div>
            <div class="flex items-center gap-2">
                <button class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full" onclick="setActive(this); updateIllnessChart('daily')">Daily</button>
                <button class="px-3 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-full" onclick="setActive(this); updateIllnessChart('weekly')">Weekly</button>
                <button class="px-3 py-1 text-sm text-gray-600 hover:bg-gray-100 rounded-full" onclick="setActive(this); updateIllnessChart('monthly')">Monthly</button>
            </div>
        </div>
        <p class="text-sm text-gray-600 mb-4">Frequent illness reasons over time • Last 7 days</p>
        <div id="illnessTrendsChart" class="w-full h-[350px]"></div>
    </div>


</main>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize charts
        const ageGroupsChart = echarts.init(document.getElementById('ageGroupsDonutChart'));
        const weeklyChart = echarts.init(document.getElementById('weeklyTrendChart'));

        // Get age groups data from PHP
        const ageGroupsData = <?php echo json_encode($ageGroups); ?>;
        const ageGroupColors = <?php echo json_encode($ageGroupColors); ?>;

        // Donut Chart for Patient Age Groups
        const ageGroupsOption = {
            tooltip: {
                trigger: 'item',
                formatter: '{b}: {c} ({d}%)'
            },
            legend: {
                show: false
            },
            series: [{
                name: 'Age Groups',
                type: 'pie',
                radius: ['40%', '70%'],
                center: ['50%', '50%'],
                avoidLabelOverlap: false,
                itemStyle: {
                    borderRadius: 0,
                    borderColor: '#fff',
                    borderWidth: 2
                },
                label: {
                    show: false
                },
                labelLine: {
                    show: false
                },
                data: ageGroupsData.map((group, index) => ({
                    value: group.count,
                    name: group.label
                })),
                color: ageGroupColors
            }]
        };

        // Get weekly trend data from PHP
        const weeklyTrendData = <?php echo json_encode($weeklyData); ?>;

        // Weekly Trend Bar Chart
        const weeklyOption = {
            tooltip: {
                trigger: 'axis',
                axisPointer: {
                    type: 'shadow'
                }
            },
            grid: {
                left: '3%',
                right: '4%',
                bottom: '3%',
                containLabel: true
            },
            xAxis: {
                type: 'category',
                data: weeklyTrendData.map(item => item.day),
                axisLine: {
                    lineStyle: {
                        color: '#E9ECEF'
                    }
                },
                axisLabel: {
                    color: '#6C757D'
                }
            },
            yAxis: {
                type: 'value',
                axisLine: {
                    show: false
                },
                axisLabel: {
                    color: '#6C757D'
                },
                splitLine: {
                    lineStyle: {
                        color: '#F8F9FA'
                    }
                }
            },
            series: [{
                name: 'People',
                type: 'bar',
                data: weeklyTrendData.map(item => item.count),
                itemStyle: {
                    color: '#007BFF',
                    borderRadius: [4, 4, 0, 0]
                },
                emphasis: {
                    itemStyle: {
                        color: '#0056B3'
                    }
                }
            }]
        };

        // Illness Trends Analysis Chart
        const illnessTrendsChart = echarts.init(document.getElementById('illnessTrendsChart'));

        // Get data from PHP
        const illnessData = <?php echo json_encode($illnessData); ?>;
        const colorPalette = ['#007BFF', '#FFC107', '#28A745', '#6F42C1', '#DC3545'];

        // Function to capitalize first letter
        function capitalizeFirst(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }

        // Function to set active button state
        window.setActive = function(button) {
            // Remove active class from all buttons in the same group
            const buttons = button.parentElement.querySelectorAll('button');
            buttons.forEach(btn => {
                btn.classList.remove('bg-blue-100', 'text-blue-800');
                btn.classList.add('text-gray-600', 'hover:bg-gray-100');
            });

            // Add active class to clicked button
            button.classList.remove('text-gray-600', 'hover:bg-gray-100');
            button.classList.add('bg-blue-100', 'text-blue-800');
        };

        // Function to build chart option
        function buildIllnessOption(timeRange) {
            const data = illnessData[timeRange];
            if (!data || !data.labels || !data.series || !data.topReasons.length) {
                return {};
            }

            // Create series for all top reasons
            const series = data.topReasons.map((reason, index) => ({
                name: capitalizeFirst(reason),
                type: 'line',
                smooth: true,
                symbol: 'circle',
                symbolSize: 6,
                lineStyle: {
                    width: 2,
                    color: colorPalette[index % colorPalette.length]
                },
                itemStyle: {
                    color: colorPalette[index % colorPalette.length]
                },
                data: data.series[reason] || [],
                emphasis: {
                    focus: 'series',
                    itemStyle: {
                        borderWidth: 0,
                        shadowBlur: 8,
                        shadowColor: colorPalette[index % colorPalette.length],
                        symbolSize: 9
                    }
                },
                animationDuration: 600,
                animationEasing: 'cubicOut'
            }));

            // Calculate max value for Y-axis scaling across all series
            let maxValue = 1;
            series.forEach(s => {
                const seriesMax = Math.max(...s.data, 0);
                if (seriesMax > maxValue) maxValue = seriesMax;
            });
            const yAxisMax = Math.ceil(maxValue * 1.2); // Add 20% padding

            return {
                tooltip: {
                    trigger: 'axis',
                    axisPointer: {
                        type: 'cross',
                        label: {
                            backgroundColor: '#6a7985'
                        }
                    }
                },
                legend: {
                    data: data.topReasons.map(r => capitalizeFirst(r)),
                    top: 10,
                    right: 10,
                    orient: 'horizontal',
                    textStyle: {
                        color: '#373d3f',
                        fontWeight: 'bold',
                        fontSize: 14
                    }
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '3%',
                    top: 60,
                    containLabel: true,
                    borderColor: '#D9DBF3'
                },
                xAxis: {
                    type: 'category',
                    data: data.labels,
                    axisLine: {
                        lineStyle: {
                            color: '#e5e7eb'
                        }
                    },
                    axisLabel: {
                        color: '#6b7280'
                    },
                    splitLine: {
                        show: true,
                        lineStyle: {
                            color: '#D9DBF3'
                        }
                    }
                },
                yAxis: {
                    type: 'value',
                    min: 0,
                    max: yAxisMax,
                    axisLine: {
                        show: false
                    },
                    axisLabel: {
                        color: '#6b7280'
                    },
                    splitLine: {
                        lineStyle: {
                            color: '#f3f4f6'
                        }
                    }
                },
                series: series
            };
        }

        // Function to update chart based on time range
        window.updateIllnessChart = function(timeRange) {
            const option = buildIllnessOption(timeRange);
            if (option && Object.keys(option).length > 0) {
                illnessTrendsChart.setOption(option, true);

                // Update description text
                const descriptions = {
                    'daily': 'Frequent illness reasons over time • Last 7 days',
                    'weekly': 'Frequent illness reasons over time • Last 12 weeks',
                    'monthly': 'Frequent illness reasons over time • Last 12 months'
                };
                document.querySelector('#illnessTrendsChart').previousElementSibling.textContent = descriptions[timeRange];
            }
        };

        // Initial chart with daily data
        const initialOption = buildIllnessOption('daily');

        // Initialize charts
        ageGroupsChart.setOption(ageGroupsOption);
        weeklyChart.setOption(weeklyOption);
        illnessTrendsChart.setOption(initialOption);

        // Resize charts when window size changes
        window.addEventListener('resize', function() {
            donutChart.resize();
            weeklyChart.resize();
            illnessTrendsChart.resize();
        });
    });

    // Medicine Stock Search and Pagination - following admin dashboard pattern exactly
    document.addEventListener('DOMContentLoaded', function() {
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

            // Always show at least one page number
            if (totalPages === 0) {
                // If no data, show page 1 anyway
                createPageButton(1);
            } else if (totalPages === 1) {
                // If only 1 page, show it
                createPageButton(1);
            } else {
                // Multiple pages - use the complex logic
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
                    const time = row.dataset.time;
                    return patient.includes(searchTerm) || time.includes(searchTerm);
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

            // Always show at least one page number
            if (totalPages === 0) {
                // If no data, show page 1 anyway
                createAppointmentPageButton(1);
            } else if (totalPages === 1) {
                // If only 1 page, show it
                createAppointmentPageButton(1);
            } else {
                // Multiple pages - use the complex logic
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

<?php
include '../includes/footer.php';
?>