<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(403);
    die('Access denied');
}

// Include database connection
include '../includes/db_connect.php';

try {
    // Get filter parameters
    $from_date = $_GET['from_date'] ?? date('Y-m-01');
    $to_date = $_GET['to_date'] ?? date('Y-m-t');
    $report_type = $_GET['report_type'] ?? 'all';
    $individual_report = $_GET['individual_report'] ?? null;

    // Build date conditions for queries - with better error handling
    $date_condition = "DATE(prescription_date) BETWEEN '$from_date' AND '$to_date'";
    $appointment_date_condition = "DATE(date) BETWEEN '$from_date' AND '$to_date'";
    
    // Debug: Check what tables exist and what data is available
    $debug_info = [];
    try {
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $debug_info['tables'] = $tables;
        
        // Check if prescriptions table exists and has data
        if (in_array('prescriptions', $tables)) {
            $prescription_count = $db->query("SELECT COUNT(*) FROM prescriptions")->fetchColumn();
            $debug_info['prescriptions_count'] = $prescription_count;
        }
        
        // Check if appointments table exists and has data
        if (in_array('appointments', $tables)) {
            $appointment_count = $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
            $debug_info['appointments_count'] = $appointment_count;
        }
        
    } catch (Exception $e) {
        $debug_info['error'] = $e->getMessage();
    }

    // Get real data from database
    $reports_data = [];
    
    if ($individual_report) {
        // Individual report logic - handle different report types
        error_log("Individual report requested: " . $individual_report);
        error_log("All GET parameters: " . print_r($_GET, true));
        
        // Normalize the report type to handle different formats
        $normalized_report = strtolower(trim($individual_report));
        error_log("Normalized report type: " . $normalized_report);
        
        switch ($normalized_report) {
            case 'patient_visits':
                // Get real patient visits data - try multiple approaches
                try {
                    $query = "SELECT COUNT(*) as total_visits FROM prescriptions WHERE $date_condition";
                    $stmt = $db->query($query);
                    $patient_visits_data = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    // Fallback: get all prescriptions if date condition fails
                    $query = "SELECT COUNT(*) as total_visits FROM prescriptions";
                    $stmt = $db->query($query);
                    $patient_visits_data = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
                try {
                    $query = "SELECT COUNT(DISTINCT patient_id) as unique_patients FROM prescriptions WHERE $date_condition";
                    $stmt = $db->query($query);
                    $unique_patients = $stmt->fetch(PDO::FETCH_ASSOC);
                } catch (Exception $e) {
                    // Fallback: get all unique patients if date condition fails
                    $query = "SELECT COUNT(DISTINCT patient_id) as unique_patients FROM prescriptions";
                    $stmt = $db->query($query);
                    $unique_patients = $stmt->fetch(PDO::FETCH_ASSOC);
                }
                
                $reports_data = [
                    'title' => 'Patient Visits Report',
                    'type' => 'Patient Visits',
                    'data' => [
                        'Total Visits' => $patient_visits_data['total_visits'] ?? 0,
                        'Unique Patients' => $unique_patients['unique_patients'] ?? 0,
                        'Recent Visits' => $patient_visits_data['total_visits'] ?? 0,
                        'Avg Per Patient' => ($unique_patients['unique_patients'] ?? 0) > 0 ? 
                            round(($patient_visits_data['total_visits'] ?? 0) / ($unique_patients['unique_patients'] ?? 1), 1) : '0'
                    ]
                ];
                break;
                
            case 'appointments':
                $query = "SELECT 
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as scheduled,
                    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as completed,
                    SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as cancelled,
                    SUM(CASE WHEN status = 'rescheduled' THEN 1 ELSE 0 END) as no_show
                    FROM appointments WHERE $appointment_date_condition";
                $stmt = $db->query($query);
                $appointments_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $reports_data = [
                    'title' => 'Appointments Summary',
                    'type' => 'Appointments',
                    'data' => [
                        'Pending' => $appointments_data['scheduled'] ?? 0,
                        'Approved' => $appointments_data['completed'] ?? 0,
                        'Declined' => $appointments_data['cancelled'] ?? 0,
                        'Rescheduled' => $appointments_data['no_show'] ?? 0
                    ]
                ];
                break;
                
            case 'medication':
                $query = "SELECT COUNT(*) as prescriptions_issued FROM prescriptions WHERE $date_condition";
                $stmt = $db->query($query);
                $medication_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $query = "SELECT COUNT(DISTINCT patient_id) as patients_served FROM prescriptions WHERE $date_condition";
                $stmt = $db->query($query);
                $patients_served = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $query = "SELECT COUNT(DISTINCT prescribed_by) as prescribers FROM prescriptions WHERE $date_condition";
                $stmt = $db->query($query);
                $prescribers = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // Get most prescribed medicine
                $query = "SELECT JSON_UNQUOTE(JSON_EXTRACT(medicines, '$[0].medicine')) as medicine_name, 
                         COUNT(*) as count 
                         FROM prescriptions 
                         WHERE $date_condition AND medicines IS NOT NULL AND medicines != '[]'
                         GROUP BY medicine_name 
                         ORDER BY count DESC 
                         LIMIT 1";
                $stmt = $db->query($query);
                $most_prescribed = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $reports_data = [
                    'title' => 'Medication Report',
                    'type' => 'Medication',
                    'data' => [
                        'Prescriptions Issued' => $medication_data['prescriptions_issued'] ?? 0,
                        'Average Per Patient' => ($patients_served['patients_served'] ?? 0) > 0 ? 
                            round(($medication_data['prescriptions_issued'] ?? 0) / ($patients_served['patients_served'] ?? 1), 1) : '0',
                        'Prescribers' => $prescribers['prescribers'] ?? 0,
                        'Most Prescribed' => $most_prescribed['medicine_name'] ?? 'N/A'
                    ]
                ];
                break;
                
            case 'inventory_management':
                // Get medicine inventory data
                try {
                    $query = "SELECT COUNT(*) as total_medicines FROM medicines";
                    $stmt = $db->query($query);
                    $total_medicines = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $query = "SELECT COUNT(*) as low_stock FROM medicines WHERE quantity <= 20";
                    $stmt = $db->query($query);
                    $low_stock = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $query = "SELECT COUNT(*) as out_of_stock FROM medicines WHERE quantity = 0";
                    $stmt = $db->query($query);
                    $out_of_stock = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $query = "SELECT COUNT(*) as expired FROM medicines WHERE expiry < CURDATE()";
                    $stmt = $db->query($query);
                    $expired = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $reports_data = [
                        'title' => 'Inventory Management Report',
                        'type' => 'Inventory Management',
                        'data' => [
                            'Total Medicines' => $total_medicines['total_medicines'] ?? 0,
                            'Low Stock' => $low_stock['low_stock'] ?? 0,
                            'Out of Stock' => $out_of_stock['out_of_stock'] ?? 0,
                            'Expired Medicines' => $expired['expired'] ?? 0
                        ]
                    ];
                } catch (Exception $e) {
                    error_log("Inventory query error: " . $e->getMessage());
                    $reports_data = [
                        'title' => 'Inventory Management Report',
                        'type' => 'Inventory Management',
                        'data' => [
                            'Error' => 'Unable to fetch inventory data: ' . $e->getMessage()
                        ]
                    ];
                }
                break;
                
            case 'inventory':
            case 'inventory_management':
            case 'medicine_inventory':
            case 'stock_management':
                // Get medicine inventory data
                try {
                    $query = "SELECT COUNT(*) as total_medicines FROM medicines";
                    $stmt = $db->query($query);
                    $total_medicines = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $query = "SELECT COUNT(*) as low_stock FROM medicines WHERE quantity <= 20";
                    $stmt = $db->query($query);
                    $low_stock = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $query = "SELECT COUNT(*) as out_of_stock FROM medicines WHERE quantity = 0";
                    $stmt = $db->query($query);
                    $out_of_stock = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $query = "SELECT COUNT(*) as expired FROM medicines WHERE expiry < CURDATE()";
                    $stmt = $db->query($query);
                    $expired = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $reports_data = [
                        'title' => 'Inventory Management Report',
                        'type' => 'Inventory Management',
                        'data' => [
                            'Total Medicines' => $total_medicines['total_medicines'] ?? 0,
                            'Low Stock' => $low_stock['low_stock'] ?? 0,
                            'Out of Stock' => $out_of_stock['out_of_stock'] ?? 0,
                            'Expired Medicines' => $expired['expired'] ?? 0
                        ]
                    ];
                } catch (Exception $e) {
                    error_log("Inventory query error: " . $e->getMessage());
                    $reports_data = [
                        'title' => 'Inventory Management Report',
                        'type' => 'Inventory Management',
                        'data' => [
                            'Error' => 'Unable to fetch inventory data: ' . $e->getMessage()
                        ]
                    ];
                }
                break;
                
            case 'system_overview':
                // Get real system data
                $visitor_count = $db->query("SELECT COUNT(*) FROM visitor")->fetchColumn();
                $faculty_count = $db->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
                $imported_count = $db->query("SELECT COUNT(*) FROM imported_patients")->fetchColumn();
                $total_patients = $visitor_count + $faculty_count + $imported_count;
                
                $reports_data = [
                    'title' => 'System Overview',
                    'type' => 'System Overview',
                    'data' => [
                        'Total Patients' => $total_patients,
                        'Students' => $imported_count,
                        'Faculty' => $faculty_count,
                        'Visitors' => $visitor_count
                    ]
                ];
                break;
                
            default:
                // Handle any unrecognized report type - show general data
                error_log("Unrecognized report type: " . $normalized_report);
                
                // Try to get some basic data regardless of report type
                try {
                    $visitor_count = $db->query("SELECT COUNT(*) FROM visitor")->fetchColumn();
                    $faculty_count = $db->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
                    $imported_count = $db->query("SELECT COUNT(*) FROM imported_patients")->fetchColumn();
                    $total_patients = $visitor_count + $faculty_count + $imported_count;
                    
                    $prescription_count = $db->query("SELECT COUNT(*) FROM prescriptions")->fetchColumn();
                    $appointment_count = $db->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
                    $medicine_count = $db->query("SELECT COUNT(*) FROM medicines")->fetchColumn();
                    
                    $reports_data = [
                        'title' => 'General System Report',
                        'type' => 'System Overview',
                        'data' => [
                            'Total Patients' => $total_patients,
                            'Students' => $imported_count,
                            'Faculty' => $faculty_count,
                            'Visitors' => $visitor_count,
                            'Total Prescriptions' => $prescription_count,
                            'Total Appointments' => $appointment_count,
                            'Total Medicines' => $medicine_count,
                            'Requested Report Type' => $individual_report
                        ]
                    ];
                } catch (Exception $e) {
                    error_log("Default report error: " . $e->getMessage());
                    $reports_data = [
                        'title' => 'Report Error',
                        'type' => 'Error',
                        'data' => [
                            'Error' => 'Unable to generate report: ' . $e->getMessage(),
                            'Requested Type' => $individual_report,
                            'Available Tables' => implode(', ', $debug_info['tables'] ?? [])
                        ]
                    ];
                }
                break;
        }
    } else {
        // Export all reports
        // System Overview
        $visitor_count = $db->query("SELECT COUNT(*) FROM visitor")->fetchColumn();
        $faculty_count = $db->query("SELECT COUNT(*) FROM faculty")->fetchColumn();
        $imported_count = $db->query("SELECT COUNT(*) FROM imported_patients")->fetchColumn();
        $total_patients = $visitor_count + $faculty_count + $imported_count;
        
        // Appointments
        $appointments_data = $db->query("
            SELECT 
                COUNT(*) as total_appointments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as scheduled,
                COUNT(CASE WHEN status = 'approved' THEN 1 END) as completed,
                COUNT(CASE WHEN status = 'declined' THEN 1 END) as cancelled,
                COUNT(CASE WHEN status = 'rescheduled' THEN 1 END) as no_show
            FROM appointments 
            WHERE $appointment_date_condition
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Medication
        $medication_data = $db->query("
            SELECT 
                COUNT(*) as prescriptions_issued,
                COUNT(DISTINCT patient_id) as patients_served,
                COUNT(DISTINCT prescribed_by) as prescribers
            FROM prescriptions
            WHERE $date_condition
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Patient Visits
        $patient_visits_data = $db->query("
            SELECT 
                COUNT(*) as total_visits,
                COUNT(DISTINCT patient_id) as unique_patients
            FROM prescriptions 
            WHERE $date_condition
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Get most prescribed medicine
        $most_prescribed = $db->query("
            SELECT JSON_UNQUOTE(JSON_EXTRACT(medicines, '$[0].medicine')) as medicine_name, 
                   COUNT(*) as count 
            FROM prescriptions 
            WHERE $date_condition AND medicines IS NOT NULL AND medicines != '[]'
            GROUP BY medicine_name 
            ORDER BY count DESC 
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);
        
        // Inventory Management
        $inventory_data = $db->query("
            SELECT 
                COUNT(*) as total_medicines,
                COUNT(CASE WHEN quantity <= 20 THEN 1 END) as low_stock,
                COUNT(CASE WHEN quantity = 0 THEN 1 END) as out_of_stock,
                COUNT(CASE WHEN expiry < CURDATE() THEN 1 END) as expired
            FROM medicines
        ")->fetch(PDO::FETCH_ASSOC);
        
        $reports_data = [
            'system_overview' => [
                'title' => 'System Overview',
                'data' => [
                    'Total Patients' => $total_patients,
                    'Students' => $imported_count,
                    'Faculty' => $faculty_count,
                    'Visitors' => $visitor_count
                ]
            ],
            'appointments' => [
                'title' => 'Appointments Summary',
                'data' => [
                    'Pending' => $appointments_data['scheduled'] ?? 0,
                    'Declined' => $appointments_data['cancelled'] ?? 0,
                    'Approved' => $appointments_data['completed'] ?? 0,
                    'Rescheduled' => $appointments_data['no_show'] ?? 0
                ]
            ],
            'medication' => [
                'title' => 'Medication & Prescription Report',
                'data' => [
                    'Prescriptions Issued' => $medication_data['prescriptions_issued'] ?? 0,
                    'Average Per Patient' => ($medication_data['patients_served'] ?? 0) > 0 ? 
                        round(($medication_data['prescriptions_issued'] ?? 0) / ($medication_data['patients_served'] ?? 1), 1) : '0',
                    'Most Prescribed' => $most_prescribed['medicine_name'] ?? 'N/A',
                    'Active Prescribers' => $medication_data['prescribers'] ?? 0
                ]
            ],
            'patient_visits' => [
                'title' => 'Patient Visits Report',
                'data' => [
                    'Total Visits' => $patient_visits_data['total_visits'] ?? 0,
                    'Recent Visits' => $patient_visits_data['total_visits'] ?? 0,
                    'Unique Patients' => $patient_visits_data['unique_patients'] ?? 0,
                    'Avg Per Patient' => ($patient_visits_data['unique_patients'] ?? 0) > 0 ? 
                        round(($patient_visits_data['total_visits'] ?? 0) / ($patient_visits_data['unique_patients'] ?? 1), 1) : '0'
                ]
            ],
            'inventory_management' => [
                'title' => 'Inventory Management Report',
                'data' => [
                    'Total Items' => $inventory_data['total_medicines'] ?? 0,
                    'Out Of Stock' => $inventory_data['out_of_stock'] ?? 0,
                    'Low Stock' => $inventory_data['low_stock'] ?? 0,
                    'Expired Medicines' => $inventory_data['expired'] ?? 0
                ]
            ]
        ];
    }

    // Generate HTML report
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Clinic Management System - Reports</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif; 
                background: #f5f7fa;
                margin: 0; 
                padding: 20px;
                min-height: 100vh;
                line-height: 1.6;
            }
            .container {
                max-width: 1000px;
                margin: 0 auto;
                background: white;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
                overflow: hidden;
                border: 1px solid #e1e8ed;
            }
            .header { 
                background: #2563eb;
                color: white;
                padding: 40px 30px;
                text-align: center;
                border-bottom: 3px solid #1d4ed8;
            }
            .header h1 { 
                font-size: 2.2em; 
                font-weight: 700; 
                margin-bottom: 8px;
                letter-spacing: -0.5px;
            }
            .header h2 { 
                font-size: 1.3em; 
                font-weight: 400; 
                margin-bottom: 15px;
                opacity: 0.95;
            }
            .date-range { 
                background: rgba(255,255,255,0.15);
                padding: 8px 16px;
                border-radius: 20px;
                display: inline-block;
                font-size: 0.9em;
                font-weight: 500;
                border: 1px solid rgba(255,255,255,0.2);
            }
            .content {
                padding: 30px;
            }
            .report-section { 
                margin-bottom: 30px; 
                page-break-inside: avoid;
                background: white;
                border: 1px solid #e1e8ed;
                border-radius: 6px;
                padding: 25px;
                border-left: 4px solid #2563eb;
                box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            }
            .report-title { 
                font-size: 1.3em; 
                font-weight: 600; 
                margin-bottom: 20px; 
                color: #1e293b;
                display: flex;
                align-items: center;
                border-bottom: 2px solid #e1e8ed;
                padding-bottom: 10px;
            }
            .report-title::before {
                content: "📊";
                margin-right: 8px;
                font-size: 1.1em;
            }
            .data-table { 
                width: 100%; 
                border-collapse: collapse; 
                margin-bottom: 0;
                background: white;
                border: 1px solid #e1e8ed;
                border-radius: 4px;
                overflow: hidden;
            }
            .data-table th { 
                background: #2563eb;
                color: white;
                padding: 12px 16px;
                text-align: left;
                font-weight: 600;
                font-size: 0.9em;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-bottom: 2px solid #1d4ed8;
            }
            .data-table td { 
                padding: 12px 16px; 
                border-bottom: 1px solid #f1f5f9;
                font-size: 0.95em;
                color: #374151;
            }
            .data-table tr:nth-child(even) { 
                background-color: #f8fafc; 
            }
            .data-table tr:hover {
                background-color: #f1f5f9;
                transition: background-color 0.2s ease;
            }
            .data-table tr:last-child td {
                border-bottom: none;
            }
            .metric-name {
                font-weight: 600;
                color: #1e293b;
            }
            .metric-value {
                font-weight: 700;
                color: #2563eb;
                font-size: 1.05em;
            }
            .footer { 
                background: #1e293b;
                color: white;
                padding: 20px 30px;
                text-align: center;
                font-size: 0.9em;
                border-top: 3px solid #2563eb;
            }
            .footer p {
                margin: 4px 0;
            }
            .footer p:first-child {
                font-weight: 600;
                color: #f8fafc;
            }
            .footer p:last-child {
                opacity: 0.8;
                font-size: 0.85em;
            }
            .no-data {
                text-align: center;
                padding: 40px;
                color: #64748b;
            }
            .no-data i {
                font-size: 2.5em;
                margin-bottom: 15px;
                color: #cbd5e1;
            }
            .debug-info {
                background: #fef3c7;
                border: 1px solid #f59e0b;
                border-radius: 6px;
                padding: 20px;
                margin-top: 20px;
            }
            .debug-info h4 {
                color: #92400e;
                margin-bottom: 10px;
            }
            .debug-info ul {
                list-style: none;
                padding: 0;
            }
            .debug-info li {
                padding: 5px 0;
                border-bottom: 1px solid #f59e0b;
            }
            .debug-info li:last-child {
                border-bottom: none;
            }
            @media print {
                body { 
                    background: white !important; 
                    -webkit-print-color-adjust: exact !important;
                    color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .container { 
                    box-shadow: none !important; 
                    border: 1px solid #ccc !important;
                }
                .header {
                    background: #2563eb !important;
                    color: white !important;
                    -webkit-print-color-adjust: exact !important;
                    color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .date-range {
                    background: rgba(255,255,255,0.15) !important;
                    border: 1px solid rgba(255,255,255,0.2) !important;
                    -webkit-print-color-adjust: exact !important;
                    color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .data-table th {
                    background: #2563eb !important;
                    color: white !important;
                    -webkit-print-color-adjust: exact !important;
                    color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .data-table tr:nth-child(even) {
                    background-color: #f8fafc !important;
                    -webkit-print-color-adjust: exact !important;
                    color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .footer {
                    background: #1e293b !important;
                    color: white !important;
                    -webkit-print-color-adjust: exact !important;
                    color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .report-section {
                    border-left: 4px solid #2563eb !important;
                    -webkit-print-color-adjust: exact !important;
                    color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
                .metric-value {
                    color: #2563eb !important;
                    -webkit-print-color-adjust: exact !important;
                    color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🏥 Clinic Management System</h1>
                <h2>Generated Reports</h2>
                <div class="date-range">📅 Date Range: ' . $from_date . ' to ' . $to_date . '</div>
            </div>
            <div class="content">';

    if ($individual_report) {
        // Single report
        if (isset($reports_data['title']) && isset($reports_data['data'])) {
            $html .= '<div class="report-section">
                <div class="report-title">' . $reports_data['title'] . '</div>
                <table class="data-table">
                    <thead>
                        <tr><th>Metric</th><th>Value</th></tr>
                    </thead>
                    <tbody>';
            
            foreach ($reports_data['data'] as $key => $value) {
                $html .= '<tr><td class="metric-name">' . $key . '</td><td class="metric-value">' . $value . '</td></tr>';
            }
            
            $html .= '</tbody></table></div>';
        } else {
            $html .= '<div class="report-section">
                <div class="report-title">No Data Available</div>
                <p>No data found for the selected report type and date range.</p>
                <p><strong>Debug Information:</strong></p>
                <ul>';
            
            if (isset($debug_info['tables'])) {
                $html .= '<li>Available tables: ' . implode(', ', $debug_info['tables']) . '</li>';
            }
            if (isset($debug_info['prescriptions_count'])) {
                $html .= '<li>Prescriptions in database: ' . $debug_info['prescriptions_count'] . '</li>';
            }
            if (isset($debug_info['appointments_count'])) {
                $html .= '<li>Appointments in database: ' . $debug_info['appointments_count'] . '</li>';
            }
            if (isset($debug_info['error'])) {
                $html .= '<li>Error: ' . $debug_info['error'] . '</li>';
            }
            
            $html .= '</ul></div>';
        }
    } else {
        // All reports
        if (is_array($reports_data) && !empty($reports_data)) {
            foreach ($reports_data as $report) {
                if (isset($report['title']) && isset($report['data'])) {
                    $html .= '<div class="report-section">
                        <div class="report-title">' . $report['title'] . '</div>
                        <table class="data-table">
                            <thead>
                                <tr><th>Metric</th><th>Value</th></tr>
                            </thead>
                            <tbody>';
                    
                    foreach ($report['data'] as $key => $value) {
                        $html .= '<tr><td class="metric-name">' . $key . '</td><td class="metric-value">' . $value . '</td></tr>';
                    }
                    
                    $html .= '</tbody></table></div>';
                }
            }
        } else {
            $html .= '<div class="report-section">
                <div class="report-title">No Data Available</div>
                <p>No data found for the selected date range.</p>
                <p><strong>Debug Information:</strong></p>
                <ul>';
            
            if (isset($debug_info['tables'])) {
                $html .= '<li>Available tables: ' . implode(', ', $debug_info['tables']) . '</li>';
            }
            if (isset($debug_info['prescriptions_count'])) {
                $html .= '<li>Prescriptions in database: ' . $debug_info['prescriptions_count'] . '</li>';
            }
            if (isset($debug_info['appointments_count'])) {
                $html .= '<li>Appointments in database: ' . $debug_info['appointments_count'] . '</li>';
            }
            if (isset($debug_info['error'])) {
                $html .= '<li>Error: ' . $debug_info['error'] . '</li>';
            }
            
            $html .= '</ul></div>';
        }
    }

    $html .= '</div>
            <div class="footer">
                <p>Generated on: ' . date('Y-m-d H:i:s') . '</p>
                <p>Clinic Management System - Staff Reports</p>
            </div>
        </div>
    </body>
    </html>';

    // Set headers for download
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="clinic_reports_' . date('Y-m-d_H-i-s') . '.html"');
    
    echo $html;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Error generating report: ' . $e->getMessage();
}
?>
