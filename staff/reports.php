<?php
// AJAX endpoint for real-time report filtering - MUST be first
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_filter_reports'])) {
    include '../includes/db_connect.php';
    // Clear any previous output
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    
    // Debug: Log the request
    error_log("AJAX request received: " . print_r($_POST, true));
    
    // Get filter parameters
    $report_type = $_POST['report_type'] ?? 'all';
    $from_date = $_POST['from_date'] ?? '';
    $to_date = $_POST['to_date'] ?? '';
    
    // Set default date range if no filters are applied
    if (empty($from_date) && empty($to_date)) {
        $from_date = date('Y-m-d', strtotime('-30 days'));
        $to_date = date('Y-m-d');
    }
    
    // Build date filter conditions for SQL queries
    $date_condition = '';
    if (!empty($from_date) && !empty($to_date)) {
        $date_condition = "AND DATE(prescription_date) BETWEEN '$from_date' AND '$to_date'";
    } elseif (!empty($from_date)) {
        $date_condition = "AND DATE(prescription_date) >= '$from_date'";
    } elseif (!empty($to_date)) {
        $date_condition = "AND DATE(prescription_date) <= '$to_date'";
    }
    
    try {
        // Get system overview data
        $visitor_count = $db->query("SELECT COUNT(*) FROM visitor")->fetchColumn();
        $faculty_count = $db->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
        $imported_count = $db->query("SELECT COUNT(*) FROM imported_patients")->fetchColumn();
        $total_patients = $visitor_count + $faculty_count + $imported_count;
        
        // Build appointment date condition
        $appointment_date_condition = '';
        if (!empty($from_date) && !empty($to_date)) {
            $appointment_date_condition = "AND DATE(date) BETWEEN '$from_date' AND '$to_date'";
        } elseif (!empty($from_date)) {
            $appointment_date_condition = "AND DATE(date) >= '$from_date'";
        } elseif (!empty($to_date)) {
            $appointment_date_condition = "AND DATE(date) <= '$to_date'";
        }
        
        // Get appointment data
        $appointments_data = $db->query("
            SELECT 
                COUNT(*) as total_appointments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as scheduled,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'declined' THEN 1 END) as cancelled,
                COUNT(CASE WHEN status = 'rescheduled' THEN 1 END) as no_show
            FROM appointments 
            WHERE 1=1 $appointment_date_condition
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Debug: Log appointments data
        error_log("Appointments data: " . print_r($appointments_data, true));
        error_log("Appointment date condition: " . $appointment_date_condition);
        
        // Get medication data
        $medication_data = $db->query("
            SELECT 
                COUNT(*) as prescriptions_issued,
                COUNT(DISTINCT patient_id) as patients_served,
                COUNT(DISTINCT prescribed_by) as prescribers
            FROM prescriptions
            WHERE 1=1 $date_condition
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Get most prescribed medicine
        $most_prescribed = $db->query("
            SELECT 
                JSON_UNQUOTE(JSON_EXTRACT(medicines, '$[0].medicine')) as medicine_name,
                COUNT(*) as prescription_count
            FROM prescriptions 
            WHERE 1=1 $date_condition
            AND medicines IS NOT NULL 
            AND medicines != ''
            GROUP BY medicine_name
            ORDER BY prescription_count DESC
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Get patient visits data
        $patient_visits_data = $db->query("
            SELECT 
                COUNT(*) as total_visits,
                COUNT(DISTINCT patient_id) as unique_patients,
                COUNT(*) as recent_visits
            FROM prescriptions 
            WHERE 1=1 $date_condition
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Get inventory data
        $inventory_data = $db->query("
            SELECT 
                COUNT(*) as total_items,
                COUNT(CASE WHEN quantity <= 20 THEN 1 END) as low_stock,
                COUNT(CASE WHEN quantity = 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN expiry < CURDATE() THEN 1 END) as expired_medicines
            FROM medicines
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Generate reports based on filter
        $generated_reports = [];
        
        // Generate dynamic date based on filters
        if (!empty($from_date) && !empty($to_date)) {
            $current_date = date('M d, Y', strtotime($to_date));
            $date_range = date('M d', strtotime($from_date)) . ' - ' . date('M d, Y', strtotime($to_date));
        } elseif (!empty($from_date)) {
            $current_date = date('M d, Y', strtotime($from_date));
            $date_range = 'From ' . date('M d, Y', strtotime($from_date));
        } elseif (!empty($to_date)) {
            $current_date = date('M d, Y', strtotime($to_date));
            $date_range = 'Until ' . date('M d, Y', strtotime($to_date));
        } else {
            $current_date = date('M d, Y');
            $date_range = 'Last 30 Days';
        }
        
        // Patient Visits Report
        if ($report_type === 'all' || $report_type === 'patient_visits') {
            $generated_reports[] = [
                'id' => 1,
                'title' => 'Patient Visits Report - ' . $date_range,
                'date' => $current_date,
                'type' => 'Patient Visits',
                'type_color' => 'bg-blue-100 text-blue-800',
                'metrics' => [
                    'Total Visits' => number_format($patient_visits_data['total_visits'] ?? 0),
                    'Unique Patients' => number_format($patient_visits_data['unique_patients'] ?? 0),
                    'Recent Visits' => number_format($patient_visits_data['recent_visits'] ?? 0),
                    'Avg Per Patient' => ($patient_visits_data['unique_patients'] ?? 0) > 0 ?
                        round(($patient_visits_data['total_visits'] ?? 0) / ($patient_visits_data['unique_patients'] ?? 1), 1) : '0'
                ]
            ];
        }
        
        // Appointments Report
        if ($report_type === 'all' || $report_type === 'appointments') {
            $generated_reports[] = [
                'id' => 2,
                'title' => 'Appointments Summary - ' . $date_range,
                'date' => $current_date,
                'type' => 'Appointments',
                'type_color' => 'bg-green-100 text-green-800',
                'metrics' => [
                    'Pending' => number_format($appointments_data['scheduled'] ?? 0),
                    'Approved' => number_format($appointments_data['completed'] ?? 0),
                    'Declined' => number_format($appointments_data['cancelled'] ?? 0),
                    'Rescheduled' => number_format($appointments_data['no_show'] ?? 0)
                ]
            ];
        }
        
        // Medication Report
        if ($report_type === 'all' || $report_type === 'medications') {
            $most_prescribed_name = $most_prescribed ? $most_prescribed['medicine_name'] : 'N/A';
            $avg_per_patient = ($medication_data['patients_served'] ?? 0) > 0 ?
                round(($medication_data['prescriptions_issued'] ?? 0) / ($medication_data['patients_served'] ?? 1), 1) : '0';
            
            $generated_reports[] = [
                'id' => 3,
                'title' => 'Medication & Prescription Report - ' . $date_range,
                'date' => $current_date,
                'type' => 'Medication',
                'type_color' => 'bg-purple-100 text-purple-800',
                'metrics' => [
                    'Prescriptions Issued' => number_format($medication_data['prescriptions_issued'] ?? 0),
                    'Most Prescribed' => $most_prescribed_name,
                    'Average Per Patient' => $avg_per_patient,
                    'Active Prescribers' => number_format($medication_data['prescribers'] ?? 0)
                ]
            ];
        }
        
        // Inventory Report
        if ($report_type === 'all' || $report_type === 'inventory') {
            $generated_reports[] = [
                'id' => 4,
                'title' => 'Inventory Management Report - ' . $date_range,
                'date' => $current_date,
                'type' => 'Inventory',
                'type_color' => 'bg-orange-100 text-orange-800',
                'metrics' => [
                    'Total Items' => number_format($inventory_data['total_items'] ?? 0),
                    'Low Stock' => number_format($inventory_data['low_stock'] ?? 0),
                    'Out Of Stock' => number_format($inventory_data['out_of_stock'] ?? 0),
                    'Expired Medicines' => number_format($inventory_data['expired_medicines'] ?? 0)
                ]
            ];
        }
        
        echo json_encode([
            'success' => true,
            'reports' => $generated_reports,
            'total_reports' => count($generated_reports)
        ]);
        
    } catch (PDOException $e) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
    } catch (Exception $e) {
        ob_clean();
        echo json_encode([
            'success' => false,
            'error' => 'General error: ' . $e->getMessage()
        ]);
    }
    exit;
}

// Simple test endpoint - MUST be before any HTML output
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_ajax'])) {
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'AJAX endpoint is working']);
    exit;
}

// Debug endpoint to check what's being sent
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['debug_ajax'])) {
    if (ob_get_level()) {
        ob_clean();
    }
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true, 
        'method' => $_SERVER['REQUEST_METHOD'],
        'post_data' => $_POST,
        'get_data' => $_GET,
        'request_uri' => $_SERVER['REQUEST_URI']
    ]);
    exit;
}

// Include files only after AJAX endpoints
include '../includes/db_connect.php';
include '../includes/header.php';
?>
<style>
/* Hide scrollbar for main content - More comprehensive approach */
.scrollbar-hide {
    /* Firefox */
    scrollbar-width: none !important;
    /* Internet Explorer and Edge */
    -ms-overflow-style: none !important;
    /* Webkit browsers (Chrome, Safari, newer Edge) */
    overflow: -moz-scrollbars-none !important;
}

/* Webkit scrollbar hiding */
.scrollbar-hide::-webkit-scrollbar {
    width: 0 !important;
    height: 0 !important;
    display: none !important;
}

.scrollbar-hide::-webkit-scrollbar-track {
    display: none !important;
}

.scrollbar-hide::-webkit-scrollbar-thumb {
    display: none !important;
}

.scrollbar-hide::-webkit-scrollbar-corner {
    display: none !important;
}

/* Ensure smooth scrolling */
.scrollbar-hide {
    scroll-behavior: smooth;
}

/* Additional scrollbar hiding for table containers */
.overflow-x-auto.scrollbar-hide {
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
    overflow: -moz-scrollbars-none !important;
}

.overflow-x-auto.scrollbar-hide::-webkit-scrollbar {
    width: 0 !important;
    height: 0 !important;
    display: none !important;
}

.overflow-x-auto.scrollbar-hide::-webkit-scrollbar-track {
    display: none !important;
}

.overflow-x-auto.scrollbar-hide::-webkit-scrollbar-thumb {
    display: none !important;
}

/* Force hide scrollbars on all elements */
* {
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
}

*::-webkit-scrollbar {
    width: 0 !important;
    height: 0 !important;
    display: none !important;
}

*::-webkit-scrollbar-track {
    display: none !important;
}

*::-webkit-scrollbar-thumb {
    display: none !important;
}

*::-webkit-scrollbar-corner {
    display: none !important;
}

/* Specific targeting for body and html */
html, body {
    scrollbar-width: none !important;
    -ms-overflow-style: none !important;
    overflow: -moz-scrollbars-none !important;
}

html::-webkit-scrollbar, body::-webkit-scrollbar {
    width: 0 !important;
    height: 0 !important;
    display: none !important;
}

html::-webkit-scrollbar-track, body::-webkit-scrollbar-track {
    display: none !important;
}

html::-webkit-scrollbar-thumb, body::-webkit-scrollbar-thumb {
    display: none !important;
}

html::-webkit-scrollbar-corner, body::-webkit-scrollbar-corner {
    display: none !important;
}
</style>

<?php
// Get filter parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'all';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';

// Set default date range if no filters are applied
if (empty($from_date) && empty($to_date)) {
    $from_date = date('Y-m-d', strtotime('-30 days'));
    $to_date = date('Y-m-d');
}

// Build date filter conditions for SQL queries
$date_condition = '';
if (!empty($from_date) && !empty($to_date)) {
    $date_condition = "AND DATE(prescription_date) BETWEEN '$from_date' AND '$to_date'";
} elseif (!empty($from_date)) {
    $date_condition = "AND DATE(prescription_date) >= '$from_date'";
} elseif (!empty($to_date)) {
    $date_condition = "AND DATE(prescription_date) <= '$to_date'";
}

$appointment_date_condition = '';
if (!empty($from_date) && !empty($to_date)) {
    $appointment_date_condition = "AND DATE(date) BETWEEN '$from_date' AND '$to_date'";
} elseif (!empty($from_date)) {
    $appointment_date_condition = "AND DATE(date) >= '$from_date'";
} elseif (!empty($to_date)) {
    $appointment_date_condition = "AND DATE(date) <= '$to_date'";
}

// Get system overview data
try {
    // Total patients in system (visitor + faculty + imported_patients)
    $visitor_count = $db->query("SELECT COUNT(*) FROM visitor")->fetchColumn();
    $faculty_count = $db->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
    $imported_patients_count = $db->query("SELECT COUNT(*) FROM imported_patients")->fetchColumn();
    $total_patients = $visitor_count + $faculty_count + $imported_patients_count;
    
    $todays_visits = $db->query("SELECT COUNT(*) FROM prescriptions WHERE DATE(prescription_date) = CURDATE()")->fetchColumn();
    $upcoming_appointments = $db->query("SELECT COUNT(*) FROM appointments WHERE date >= CURDATE() AND status = 'pending'")->fetchColumn();
    $low_stock_items = $db->query("SELECT COUNT(*) FROM medicines WHERE quantity <= 20")->fetchColumn();
    $todays_prescriptions = $db->query("SELECT COUNT(*) FROM prescriptions WHERE DATE(prescription_date) = CURDATE()")->fetchColumn();

    // Calculate revenue (mock data for now)
    $revenue = $db->query("SELECT COUNT(*) * 150 FROM prescriptions WHERE DATE(prescription_date) = CURDATE()")->fetchColumn();
} catch (PDOException $e) {
    $total_patients = 0;
    $visitor_count = 0;
    $faculty_count = 0;
    $imported_patients_count = 0;
    $todays_visits = 0;
    $upcoming_appointments = 0;
    $low_stock_items = 0;
    $todays_prescriptions = 0;
    $revenue = 0;
}

// Generate real-time reports data from system
try {
    // Patient Visits Report
    $patient_visits_data = $db->query("
        SELECT 
            COUNT(*) as total_visits,
            COUNT(DISTINCT patient_id) as unique_patients,
            COUNT(*) as recent_visits
        FROM prescriptions 
        WHERE 1=1 $date_condition
    ")->fetch(PDO::FETCH_ASSOC);

    // Appointments Report
    $appointments_data = $db->query("
        SELECT 
            COUNT(*) as total_appointments,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as scheduled,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as completed,
            COUNT(CASE WHEN status = 'declined' THEN 1 END) as cancelled,
            COUNT(CASE WHEN status = 'rescheduled' THEN 1 END) as no_show
        FROM appointments 
        WHERE 1=1 $appointment_date_condition
    ")->fetch(PDO::FETCH_ASSOC);

    // Medication Report
    $medication_data = $db->query("
        SELECT 
            COUNT(*) as prescriptions_issued,
            COUNT(DISTINCT patient_id) as patients_served,
            COUNT(DISTINCT prescribed_by) as prescribers
        FROM prescriptions 
        WHERE 1=1 $date_condition
    ")->fetch(PDO::FETCH_ASSOC);

    // Get most prescribed medication
    $most_prescribed = $db->query("
        SELECT 
            JSON_UNQUOTE(JSON_EXTRACT(medicines, '$[0].medicine')) as medicine_name,
            COUNT(*) as prescription_count
        FROM prescriptions 
        WHERE 1=1 $date_condition
        AND medicines IS NOT NULL 
        AND medicines != ''
        GROUP BY medicine_name
        ORDER BY prescription_count DESC
        LIMIT 1
    ")->fetch(PDO::FETCH_ASSOC);

    // Inventory Report
    $inventory_data = $db->query("
        SELECT 
            COUNT(*) as total_items,
            COUNT(CASE WHEN quantity <= 20 THEN 1 END) as low_stock,
            COUNT(CASE WHEN quantity = 0 THEN 1 END) as out_of_stock,
            COUNT(CASE WHEN expiry < CURDATE() THEN 1 END) as expired_medicines
        FROM medicines
    ")->fetch(PDO::FETCH_ASSOC);

    // Generate real-time reports - Filter by report type
    $generated_reports = [];
    error_log("Generating reports for report_type: " . $report_type);
    
    // Generate dynamic date based on filters
    if (!empty($from_date) && !empty($to_date)) {
        $current_date = date('M d, Y', strtotime($to_date));
        $date_range = date('M d', strtotime($from_date)) . ' - ' . date('M d, Y', strtotime($to_date));
    } elseif (!empty($from_date)) {
        $current_date = date('M d, Y', strtotime($from_date));
        $date_range = 'From ' . date('M d, Y', strtotime($from_date));
    } elseif (!empty($to_date)) {
        $current_date = date('M d, Y', strtotime($to_date));
        $date_range = 'Until ' . date('M d, Y', strtotime($to_date));
    } else {
        $current_date = date('M d, Y');
        $date_range = 'Last 30 Days';
    }

    // Patient Visits Report - Show if 'all' or 'patient_visits'
    if ($report_type === 'all' || $report_type === 'patient_visits') {
    $generated_reports[] = [
        'id' => 1,
        'title' => 'Patient Visits Report - ' . $date_range,
        'date' => $current_date,
        'type' => 'Patient Visits',
        'type_color' => 'bg-blue-100 text-blue-800',
        'metrics' => [
            'Total Visits' => number_format($patient_visits_data['total_visits'] ?? 0),
            'Unique Patients' => number_format($patient_visits_data['unique_patients'] ?? 0),
            'Recent Visits' => number_format($patient_visits_data['recent_visits'] ?? 0),
            'Avg Per Patient' => ($patient_visits_data['unique_patients'] ?? 0) > 0 ?
                round(($patient_visits_data['total_visits'] ?? 0) / ($patient_visits_data['unique_patients'] ?? 1), 1) : '0'
        ]
    ];
    }

    // Appointments Report - Show if 'all' or 'appointments'
    if ($report_type === 'all' || $report_type === 'appointments') {
    $generated_reports[] = [
        'id' => 2,
        'title' => 'Appointments Summary - ' . $date_range,
        'date' => $current_date,
        'type' => 'Appointments',
        'type_color' => 'bg-green-100 text-green-800',
        'metrics' => [
            'Pending' => number_format($appointments_data['scheduled'] ?? 0),
            'Approved' => number_format($appointments_data['completed'] ?? 0),
            'Declined' => number_format($appointments_data['cancelled'] ?? 0),
            'Rescheduled' => number_format($appointments_data['no_show'] ?? 0)
        ]
    ];
    }

    // Medication Report - Show if 'all' or 'medications'
    if ($report_type === 'all' || $report_type === 'medications') {
    $most_prescribed_name = $most_prescribed ? $most_prescribed['medicine_name'] : 'N/A';
    $avg_per_patient = ($medication_data['patients_served'] ?? 0) > 0 ?
        round(($medication_data['prescriptions_issued'] ?? 0) / ($medication_data['patients_served'] ?? 1), 1) : '0';

    $generated_reports[] = [
        'id' => 3,
        'title' => 'Medication & Prescription Report - ' . $date_range,
        'date' => $current_date,
        'type' => 'Medication',
        'type_color' => 'bg-purple-100 text-purple-800',
        'metrics' => [
            'Prescriptions Issued' => number_format($medication_data['prescriptions_issued'] ?? 0),
            'Most Prescribed' => $most_prescribed_name,
            'Average Per Patient' => $avg_per_patient,
            'Active Prescribers' => number_format($medication_data['prescribers'] ?? 0)
        ]
    ];
    }

    // Inventory Report - Show if 'all' or 'inventory'
    if ($report_type === 'all' || $report_type === 'inventory') {
    $generated_reports[] = [
        'id' => 4,
            'title' => 'Inventory Management Report - ' . $date_range,
        'date' => $current_date,
        'type' => 'Inventory',
        'type_color' => 'bg-orange-100 text-orange-800',
        'metrics' => [
            'Total Items' => number_format($inventory_data['total_items'] ?? 0),
            'Low Stock' => number_format($inventory_data['low_stock'] ?? 0),
            'Out Of Stock' => number_format($inventory_data['out_of_stock'] ?? 0),
            'Expired Medicines' => number_format($inventory_data['expired_medicines'] ?? 0)
        ]
    ];
    }
    error_log("Generated " . count($generated_reports) . " reports in try block");
} catch (PDOException $e) {
    // Fallback to default reports if database error
    $current_date = date('M d, Y');
    // Only generate fallback reports if no reports were generated in the try block
    if (empty($generated_reports)) {
        $generated_reports = [];
        error_log("Database error occurred, generating fallback reports for report_type: " . $report_type);
    
    // Patient Visits Report - Show if 'all' or 'patient_visits'
    if ($report_type === 'all' || $report_type === 'patient_visits') {
        $generated_reports[] = [
            'id' => 1,
            'title' => 'Patient Visits Report - ' . date('F Y'),
            'date' => $current_date,
            'type' => 'Patient Visits',
            'type_color' => 'bg-blue-100 text-blue-800',
            'metrics' => [
                'Total Visits' => '0',
                'Unique Patients' => '0',
                'Recent Visits' => '0',
                'Avg Per Patient' => '0'
            ]
        ];
    }
    }
    
    // Appointments Report - Show if 'all' or 'appointments'
    if ($report_type === 'all' || $report_type === 'appointments') {
        $generated_reports[] = [
            'id' => 2,
            'title' => 'Appointments Summary - Last 30 Days',
            'date' => $current_date,
            'type' => 'Appointments',
            'type_color' => 'bg-green-100 text-green-800',
            'metrics' => [
                'Pending' => '0',
                'Approved' => '0',
                'Declined' => '0',
                'Rescheduled' => '0'
            ]
        ];
    }
    
    // Medication Report - Show if 'all' or 'medications'
    if ($report_type === 'all' || $report_type === 'medications') {
        $generated_reports[] = [
            'id' => 3,
            'title' => 'Medication & Prescription Report',
            'date' => $current_date,
            'type' => 'Medication',
            'type_color' => 'bg-purple-100 text-purple-800',
            'metrics' => [
                'Prescriptions Issued' => '0',
                'Most Prescribed' => 'N/A',
                'Average Per Patient' => '0',
                'Active Prescribers' => '0'
            ]
        ];
    }
    
    // Inventory Report - Show if 'all' or 'inventory'
    if ($report_type === 'all' || $report_type === 'inventory') {
        $generated_reports[] = [
            'id' => 4,
            'title' => 'Inventory Management Report - ' . $date_range,
            'date' => $current_date,
            'type' => 'Inventory',
            'type_color' => 'bg-orange-100 text-orange-800',
            'metrics' => [
                'Total Items' => '0',
                'Low Stock' => '0',
                'Out Of Stock' => '0',
                'Expired Medicines' => '0'
        ]
    ];
    }
}
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px] scrollbar-hide">
    <!-- Header Section -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Staff Reports</h1>
        <p class="text-gray-600">Generate and manage system reports</p>
    </div>


    <!-- System Overview Section -->
    <div class="mb-8">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">System Overview</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
            <!-- Total Patients Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-600">Total Patients</p>
                    <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="ri-user-2-line text-2xl text-blue-600"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-blue-600 mb-2"><?= number_format($total_patients) ?></p><p class="text-xs text-gray-500 flex items-center">
                    <i class="ri-arrow-up-line text-green-500 mr-1"></i>
                    Updated just now
                </p>
            </div>

            <!-- Today's Visits Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-600">Today's Visits</p>
                    <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="ri-calendar-check-line text-2xl text-green-600"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-green-600 mb-2"><?= number_format($todays_visits) ?></p>
                <p class="text-xs text-gray-500 flex items-center">
                    <i class="ri-arrow-up-line text-green-500 mr-1"></i>
                    Updated just now
                </p>
            </div>

            <!-- Upcoming Appointments Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-600">Upcoming Appointments</p>
                    <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                        <i class="ri-calendar-line text-2xl text-orange-600"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-orange-600 mb-2"><?= number_format($upcoming_appointments) ?></p>
                <p class="text-xs text-gray-500 flex items-center">
                    <i class="ri-arrow-up-line text-green-500 mr-1"></i>
                    Updated just now
                </p>
            </div>

            <!-- Low Stock Items Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-600">Low Stock Items</p>
                    <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="ri-box-line text-2xl text-red-600"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-red-600 mb-2"><?= number_format($low_stock_items) ?></p>
                <p class="text-xs text-gray-500 flex items-center">
                    <i class="ri-arrow-up-line text-green-500 mr-1"></i>
                    Updated just now
                </p>
            </div>

            <!-- Today's Prescriptions Card -->
            <div class="bg-white rounded-lg border border-gray-200 p-6 flex flex-col">
                <div class="flex items-center justify-between mb-4">
                    <p class="text-sm font-medium text-gray-600">Today's Prescriptions</p>
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="ri-links-line text-2xl text-purple-600"></i>
                    </div>
                </div>
                <p class="text-3xl font-bold text-purple-600 mb-2"><?= number_format($todays_prescriptions) ?></p>
                <p class="text-xs text-gray-500 flex items-center">
                    <i class="ri-arrow-up-line text-green-500 mr-1"></i>
                    Updated just now
                </p>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-8">
        <div class="flex items-center mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Filters</h3>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
             <!-- Report Type Filter -->
             <div>
                 <label class="block text-sm font-medium text-gray-700 mb-2">Report Type</label>
                 <select id="reportTypeFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                     <option value="all" <?= $report_type === 'all' ? 'selected' : '' ?>>All Reports</option>
                     <option value="patient_visits" <?= $report_type === 'patient_visits' ? 'selected' : '' ?>>Patient Visits</option>
                     <option value="appointments" <?= $report_type === 'appointments' ? 'selected' : '' ?>>Appointments</option>
                     <option value="medications" <?= $report_type === 'medications' ? 'selected' : '' ?>>Medications</option>
                     <option value="inventory" <?= $report_type === 'inventory' ? 'selected' : '' ?>>Inventory</option>
                 </select>
             </div>

             <!-- From Date Filter -->
             <div>
                 <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                 <input type="date" id="fromDateFilter" value="<?= htmlspecialchars($from_date) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
             </div>

             <!-- To Date Filter -->
             <div>
                 <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                 <input type="date" id="toDateFilter" value="<?= htmlspecialchars($to_date) ?>" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
             </div>
        </div>
    </div>

    

    <!-- Generated Reports Section -->
    <div id="generatedReports">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-gray-900">Generated Reports</h3>
            <div class="flex items-center space-x-3">
                <button id="exportAllBtn" class="px-3 py-1.5 bg-white text-gray-700 font-medium text-xs rounded-lg border border-gray-300 hover:bg-gray-50 transition-colors flex items-center space-x-1">
                    <i class="ri-download-2-line text-sm"></i>
                    <span>Export All</span>
                </button>
                <span class="text-sm text-gray-500"><?= count($generated_reports) ?> reports</span>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <?php foreach ($generated_reports as $report): ?>
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow flex flex-col h-full">
                    <!-- Header Section with Fixed Height -->
                    <div class="flex items-start justify-between mb-4 min-h-[60px]">
                        <div class="flex-1 pr-4">
                            <h4 class="text-lg font-semibold text-gray-900 mb-1 leading-tight"><?= htmlspecialchars($report['title']) ?></h4>
                            <p class="text-sm text-gray-500">Generated on <?= $report['date'] ?></p>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <button class="exportReportBtn px-3 py-1.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors text-sm font-medium" 
                                    data-report-type="<?= strtolower(str_replace([' ', '&'], ['_', ''], $report['type'])) ?>">
                                Export
                            </button>
                        </div>
                    </div>

                    <!-- Report Type Tag -->
                    <div class="mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $report['type_color'] ?>">
                            <?= $report['type'] ?>
                        </span>
                    </div>

                    <!-- Report Metrics - Always at bottom -->
                    <div class="grid grid-cols-2 gap-4 mt-auto">
                        <?php foreach ($report['metrics'] as $metric => $value): ?>
                            <div class="text-center">
                                <?php if ($metric === 'Most Prescribed'): ?>
                                    <p class="text-sm font-semibold text-gray-900 truncate" title="<?= htmlspecialchars($value) ?>"><?= htmlspecialchars($value) ?></p>
                                <?php else: ?>
                                    <p class="text-2xl font-bold text-gray-900"><?= $value ?></p>
                                <?php endif; ?>
                                <p class="text-xs text-gray-500"><?= $metric ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>

<style>
/* Custom styles for the reports page */
.report-card {
    transition: none;
}

.report-card:hover {
    transform: none;
    box-shadow: none;
}

    /* Smooth transitions for interactive elements */
    button,
    select,
    input {
        transition: all 0.2s ease-in-out;
    }

    /* Focus states */
    input:focus,
    select:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }
</style>

<script>
    // Add interactivity to the reports page
    document.addEventListener('DOMContentLoaded', function() {
        // Add hover effects to report cards
        const reportCards = document.querySelectorAll('.bg-white.rounded-lg.border');
        reportCards.forEach(card => {
            card.classList.add('report-card');
        });

        // Real-time data refresh functionality
        function updateTimestamps() {
            const timestampElements = document.querySelectorAll('.text-xs.text-gray-500');
            timestampElements.forEach(element => {
                if (element.textContent.includes('Updated just now')) {
                    const now = new Date();
                    const timeString = now.toLocaleTimeString('en-US', {
                        hour: '2-digit',
                        minute: '2-digit',
                        hour12: true
                    });
                    element.innerHTML = `<i class="ri-arrow-up-line text-green-500 mr-1"></i>Updated at ${timeString}`;
                }
            });
        }

        // Update timestamps every minute
        setInterval(updateTimestamps, 60000);

        // Auto-refresh data every 5 minutes
        function refreshData() {
            // Add loading indicator to system overview cards
            const overviewCards = document.querySelectorAll('.bg-white.rounded-lg.border.p-6');
            overviewCards.forEach(card => {
                const timestamp = card.querySelector('.text-xs.text-gray-500');
                if (timestamp) {
                    timestamp.innerHTML = '<i class="ri-loader-4-line animate-spin text-blue-500 mr-1"></i>Updating...';
                }
            });

            // Refresh the page to get latest data
            setTimeout(() => {
                window.location.reload();
            }, 2000);
        }

        // Auto-refresh every 5 minutes (300000ms)
        setInterval(refreshData, 300000);


    // Real-time filtering functionality
    function filterReports() {
        const reportType = document.getElementById('reportTypeFilter').value;
        const fromDate = document.getElementById('fromDateFilter').value;
        const toDate = document.getElementById('toDateFilter').value;
        
        // Use more specific selector for Generated Reports cards only
        const reportCards = document.querySelectorAll('#generatedReports .bg-white.rounded-lg.border.p-6');
        
        reportCards.forEach(card => {
            let shouldShow = true;
            
            // Filter by report type
            if (reportType !== 'all') {
                const reportTitleElement = card.querySelector('h4');
                if (reportTitleElement) {
                    const reportTitle = reportTitleElement.textContent.toLowerCase();
                    const typeMapping = {
                        'patient_visits': 'patient visits',
                        'appointments': 'appointments',
                        'medications': 'medication & prescription',
                        'inventory': 'inventory management'
                    };
                    
                    if (typeMapping[reportType] && !reportTitle.includes(typeMapping[reportType])) {
                        shouldShow = false;
                    }
                }
            }
            
            // Filter by date range
            if (fromDate || toDate) {
                const reportDateElement = card.querySelector('p.text-sm.text-gray-500');
                if (reportDateElement) {
                    const reportDateText = reportDateElement.textContent;
                    if (reportDateText.includes('Generated on ')) {
                        // Extract date from "Generated on Dec 15, 2024" format
                        const dateString = reportDateText.replace('Generated on ', '');
                        const reportDate = new Date(dateString);
                        
                        // Check if date is valid
                        if (!isNaN(reportDate.getTime())) {
                            // Convert filter dates to Date objects for comparison
                            const fromDateObj = fromDate ? new Date(fromDate) : null;
                            const toDateObj = toDate ? new Date(toDate) : null;
                            
                            // Set time to start/end of day for proper comparison
                            if (fromDateObj) {
                                fromDateObj.setHours(0, 0, 0, 0);
                            }
                            if (toDateObj) {
                                toDateObj.setHours(23, 59, 59, 999);
                            }
                            
                            // Compare dates
                            if (fromDateObj && reportDate < fromDateObj) {
                                shouldShow = false;
                            }
                            
                            if (toDateObj && reportDate > toDateObj) {
                                shouldShow = false;
                            }
                        }
                    }
                }
            }
            
            // Show/hide card
            if (shouldShow) {
                card.style.display = 'block';
            } else {
                card.style.display = 'none';
            }
        });
        
        // Update report count
        const visibleReports = document.querySelectorAll('#generatedReports .bg-white.rounded-lg.border.p-6[style*="block"], #generatedReports .bg-white.rounded-lg.border.p-6:not([style])');
        const reportCount = document.querySelector('.text-sm.text-gray-500');
        if (reportCount && reportCount.textContent.includes('reports')) {
            reportCount.textContent = `${visibleReports.length} reports`;
        }
    }
    
    // Real-time filtering function
    function filterReportsRealTime() {
        const reportType = document.getElementById('reportTypeFilter').value;
        const fromDate = document.getElementById('fromDateFilter').value;
        const toDate = document.getElementById('toDateFilter').value;
        
        // Store current scroll position and prevent scrolling
        const currentScrollY = window.scrollY;
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = `-${currentScrollY}px`;
        document.body.style.width = '100%';
        
        // Show loading state
        const reportsContainer = document.getElementById('generatedReports');
        if (reportsContainer) {
            reportsContainer.innerHTML = '<div class="flex items-center justify-center p-8"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div><span class="ml-2 text-gray-600">Loading reports...</span></div>';
        }
        
        // Send AJAX request
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `ajax_filter_reports=1&report_type=${reportType}&from_date=${fromDate}&to_date=${toDate}`
        })
        .then(response => {
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                return response.text().then(text => {
                    console.error('Non-JSON response received:', text);
                    throw new Error('Server returned non-JSON response');
                });
            }
            
            return response.json();
        })
        .then(data => {
            console.log('Received data:', data);
            if (data.success) {
                updateReportsDisplay(data.reports);
                updateReportCount(data.total_reports);
                
                // Restore normal scrolling behavior
                setTimeout(() => {
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.top = '';
                    document.body.style.width = '';
                    window.scrollTo(0, currentScrollY);
                }, 10);
            } else {
                console.error('Error filtering reports:', data.error);
                if (reportsContainer) {
                    reportsContainer.innerHTML = '<div class="text-center p-8 text-red-600">Error loading reports: ' + (data.error || 'Unknown error') + '</div>';
                }
                
                // Restore normal scrolling behavior even on error
                setTimeout(() => {
                    document.body.style.overflow = '';
                    document.body.style.position = '';
                    document.body.style.top = '';
                    document.body.style.width = '';
                    window.scrollTo(0, currentScrollY);
                }, 10);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            if (reportsContainer) {
                reportsContainer.innerHTML = '<div class="text-center p-8 text-red-600">Error loading reports: ' + error.message + '</div>';
            }
            
            // Restore normal scrolling behavior on error
            setTimeout(() => {
                document.body.style.overflow = '';
                document.body.style.position = '';
                document.body.style.top = '';
                document.body.style.width = '';
                window.scrollTo(0, currentScrollY);
            }, 10);
        });
    }
    
    // Update reports display
    function updateReportsDisplay(reports) {
        const reportsContainer = document.getElementById('generatedReports');
        if (!reportsContainer) return;
        
        if (reports.length === 0) {
            reportsContainer.innerHTML = '<div class="text-center p-8 text-gray-500">No reports found for the selected filters.</div>';
            return;
        }
        
        // Determine grid layout based on number of reports
        const gridClass = reports.length === 1 ? 'grid grid-cols-1 gap-6' : 'grid grid-cols-1 lg:grid-cols-2 gap-6';
        let reportsHTML = `<div class="${gridClass}">`;
        reports.forEach(report => {
            const metricsHTML = Object.entries(report.metrics).map(([key, value]) => 
                `<div class="flex justify-between items-center py-2 border-b border-gray-100 last:border-b-0">
                    <span class="text-sm text-gray-600">${key}</span>
                    <span class="text-sm font-semibold text-gray-900">${value}</span>
                </div>`
            ).join('');
            
            reportsHTML += `
                <div class="bg-white rounded-lg border border-gray-200 p-6 hover:shadow-md transition-shadow flex flex-col h-full">
                    <div class="flex items-start justify-between mb-4">
                        <div class="flex-1">
                            <h4 class="text-lg font-semibold text-gray-900 mb-1">${report.title}</h4>
                            <p class="text-sm text-gray-500">Generated on ${report.date}</p>
                        </div>
                        <div class="flex items-center space-x-2 flex-shrink-0">
                            <button class="exportReportBtn px-3 py-1.5 text-gray-600 hover:bg-gray-50 rounded-lg transition-colors text-sm font-medium" 
                                    data-report-type="${report.type.toLowerCase().replace(' ', '_')}">
                                Export
                            </button>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${report.type_color}">
                            ${report.type}
                        </span>
                    </div>
                    
                    <div class="space-y-1 mt-auto">
                        ${metricsHTML}
                    </div>
                </div>
            `;
        });
        reportsHTML += '</div>';
        
        reportsContainer.innerHTML = reportsHTML;
        
        // Re-attach export button event listeners
        attachExportListeners();
    }
    
    // Update report count
    function updateReportCount(count) {
        const reportCount = document.querySelector('.text-sm.text-gray-500');
        if (reportCount) {
            reportCount.textContent = `${count} report${count !== 1 ? 's' : ''}`;
        }
    }
    
    // Attach export button event listeners
    function attachExportListeners() {
        const exportReportBtns = document.querySelectorAll('.exportReportBtn');
        exportReportBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const reportType = this.getAttribute('data-report-type');
                const fromDate = document.getElementById('fromDateFilter').value;
        const toDate = document.getElementById('toDateFilter').value;
        
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="ri-loader-4-line animate-spin mr-1"></i>Exporting...';
                this.disabled = true;
                
                // Build URL with filter parameters
        const params = new URLSearchParams();
        if (reportType !== 'all') {
            params.append('report_type', reportType);
        }
        if (fromDate) {
            params.append('from_date', fromDate);
        }
        if (toDate) {
            params.append('to_date', toDate);
        }
        
                // Redirect to export page
                window.location.href = `export_reports.php?${params.toString()}`;
            });
        });
    }
    
    // Test AJAX endpoint
    function testAjaxEndpoint() {
        console.log('Testing AJAX endpoint...');
        
        // Test 1: Simple test
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'test_ajax=1'
        })
        .then(response => {
            console.log('Test 1 - Response status:', response.status);
            console.log('Test 1 - Content-Type:', response.headers.get('content-type'));
            return response.text();
        })
        .then(text => {
            console.log('Test 1 - Raw response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Test 1 - Parsed JSON:', data);
            } catch (e) {
                console.error('Test 1 - JSON parse error:', e);
            }
        })
        .catch(error => {
            console.error('Test 1 - Fetch error:', error);
        });
        
        // Test 2: Debug endpoint
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'debug_ajax=1'
        })
        .then(response => {
            console.log('Test 2 - Response status:', response.status);
            console.log('Test 2 - Content-Type:', response.headers.get('content-type'));
            return response.text();
        })
        .then(text => {
            console.log('Test 2 - Raw response:', text);
            try {
                const data = JSON.parse(text);
                console.log('Test 2 - Parsed JSON:', data);
            } catch (e) {
                console.error('Test 2 - JSON parse error:', e);
            }
        })
        .catch(error => {
            console.error('Test 2 - Fetch error:', error);
        });
    }
    
    // Test on page load
    testAjaxEndpoint();
    
    // Add event listeners for real-time filtering
    document.getElementById('reportTypeFilter').addEventListener('change', filterReportsRealTime);
    
    document.getElementById('fromDateFilter').addEventListener('change', filterReportsRealTime);
    document.getElementById('toDateFilter').addEventListener('change', filterReportsRealTime);


        // Handle export all functionality
        const exportAllBtn = document.getElementById('exportAllBtn');
        if (exportAllBtn) {
            exportAllBtn.addEventListener('click', function() {
                // Get current filter values
                const reportType = document.getElementById('reportTypeFilter').value;
                const fromDate = document.getElementById('fromDateFilter').value;
                const toDate = document.getElementById('toDateFilter').value;
                
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="ri-loader-4-line animate-spin mr-1"></i>Exporting...';
                this.disabled = true;

                // Build URL with filter parameters
                const params = new URLSearchParams();
                if (reportType !== 'all') {
                    params.append('report_type', reportType);
                }
                if (fromDate) {
                    params.append('from_date', fromDate);
                }
                if (toDate) {
                    params.append('to_date', toDate);
                }
                
                // Open export in new window
                const downloadUrl = 'export_reports_pdf_simple.php?' + params.toString();
                window.open(downloadUrl, '_blank');
                
                // Reset button state
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 1000);
            });
        }

        // Handle individual report export functionality
        const exportReportBtns = document.querySelectorAll('.exportReportBtn');
        exportReportBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const reportType = this.getAttribute('data-report-type');
                const fromDate = document.getElementById('fromDateFilter').value;
                const toDate = document.getElementById('toDateFilter').value;
                
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="ri-loader-4-line animate-spin mr-1"></i>Exporting...';
                this.disabled = true;

                // Build URL with filter parameters
                const params = new URLSearchParams();
                params.append('individual_report', reportType);
                if (fromDate) {
                    params.append('from_date', fromDate);
                }
                if (toDate) {
                    params.append('to_date', toDate);
                }
                
                // Open export in new window
                const downloadUrl = 'export_reports_pdf_simple.php?' + params.toString();
                window.open(downloadUrl, '_blank');
                
                // Reset button state
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 1000);
            });
        });

    });

    // Add spin animation for loading states
    const style = document.createElement('style');
    style.textContent = `
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    .animate-spin {
        animation: spin 1s linear infinite;
    }
`;
    document.head.appendChild(style);
</script>

<?php include '../includes/footer.php'; ?>
