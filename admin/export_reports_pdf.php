<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    die('Access denied');
}

// Include database connection
include '../includes/db_connect.php';

// Use the correct variable name from db_connect.php
$pdo = $db;

try {
    // Get filter parameters
    $from_date = $_GET['from_date'] ?? date('Y-m-01'); // Default to first day of current month
    $to_date = $_GET['to_date'] ?? date('Y-m-t'); // Default to last day of current month
    $report_type = $_GET['report_type'] ?? 'all';
    $individual_report = $_GET['individual_report'] ?? null;

    // Build date conditions for queries
    $date_condition = "DATE(prescription_date) BETWEEN '$from_date' AND '$to_date'";
    $appointment_date_condition = "DATE(date) BETWEEN '$from_date' AND '$to_date'";

    // Get report data
    $reports_data = [];

    if ($individual_report) {
        // Export individual report
        switch ($individual_report) {
            case 'patient_visits':
                $query = "SELECT COUNT(*) as total_visits FROM imported_patients WHERE $date_condition";
                $stmt = $pdo->query($query);
                $patient_visits_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $query = "SELECT COUNT(DISTINCT student_id) as unique_patients FROM imported_patients WHERE $date_condition";
                $stmt = $pdo->query($query);
                $unique_patients = $stmt->fetch(PDO::FETCH_ASSOC);
                
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
                $stmt = $pdo->query($query);
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
                $stmt = $pdo->query($query);
                $medication_data = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $query = "SELECT COUNT(DISTINCT student_id) as patients_served FROM prescriptions WHERE $date_condition";
                $stmt = $pdo->query($query);
                $patients_served = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $query = "SELECT COUNT(DISTINCT prescribed_by) as prescribers FROM prescriptions WHERE $date_condition";
                $stmt = $pdo->query($query);
                $prescribers = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $query = "SELECT JSON_UNQUOTE(JSON_EXTRACT(medicines, '$[0].medicine')) as medicine_name, 
                         COUNT(*) as count 
                         FROM prescriptions 
                         WHERE $date_condition AND medicines IS NOT NULL AND medicines != '[]'
                         GROUP BY medicine_name 
                         ORDER BY count DESC 
                         LIMIT 1";
                $stmt = $pdo->query($query);
                $most_prescribed = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $reports_data = [
                    'title' => 'Medication & Prescription Report',
                    'type' => 'Medication',
                    'data' => [
                        'Prescriptions Issued' => $medication_data['prescriptions_issued'] ?? 0,
                        'Most Prescribed' => $most_prescribed['medicine_name'] ?? 'N/A',
                        'Average Per Patient' => ($patients_served['patients_served'] ?? 0) > 0 ? 
                            round(($medication_data['prescriptions_issued'] ?? 0) / ($patients_served['patients_served'] ?? 1), 1) : '0',
                        'Active Prescribers' => $prescribers['prescribers'] ?? 0
                    ]
                ];
                break;
                
            case 'inventory':
                $query = "SELECT COUNT(*) as total_items FROM medicines";
                $stmt = $pdo->query($query);
                $total_items = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $query = "SELECT COUNT(*) as low_stock FROM medicines WHERE stock_quantity <= 10 AND stock_quantity > 0";
                $stmt = $pdo->query($query);
                $low_stock = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $query = "SELECT COUNT(*) as out_of_stock FROM medicines WHERE stock_quantity = 0";
                $stmt = $pdo->query($query);
                $out_of_stock = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $query = "SELECT COUNT(*) as reorder_needed FROM medicines WHERE stock_quantity <= 5";
                $stmt = $pdo->query($query);
                $reorder_needed = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $reports_data = [
                    'title' => 'Inventory Management Report',
                    'type' => 'Inventory',
                    'data' => [
                        'Total Items' => $total_items['total_items'] ?? 0,
                        'Low Stock' => $low_stock['low_stock'] ?? 0,
                        'Out Of Stock' => $out_of_stock['out_of_stock'] ?? 0,
                        'Reorder Needed' => $reorder_needed['reorder_needed'] ?? 0
                    ]
                ];
                break;
        }
    } else {
        // Export all reports
        // Patient Visits
        $query = "SELECT COUNT(*) as total_visits FROM imported_patients WHERE $date_condition";
        $stmt = $pdo->query($query);
        $patient_visits_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "SELECT COUNT(DISTINCT student_id) as unique_patients FROM imported_patients WHERE $date_condition";
        $stmt = $pdo->query($query);
        $unique_patients = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Appointments
        $query = "SELECT 
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as scheduled,
            SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as completed,
            SUM(CASE WHEN status = 'declined' THEN 1 ELSE 0 END) as cancelled,
            SUM(CASE WHEN status = 'rescheduled' THEN 1 ELSE 0 END) as no_show
            FROM appointments WHERE $appointment_date_condition";
        $stmt = $pdo->query($query);
        $appointments_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Medication
        $query = "SELECT COUNT(*) as prescriptions_issued FROM prescriptions WHERE $date_condition";
        $stmt = $pdo->query($query);
        $medication_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "SELECT COUNT(DISTINCT student_id) as patients_served FROM prescriptions WHERE $date_condition";
        $stmt = $pdo->query($query);
        $patients_served = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "SELECT COUNT(DISTINCT prescribed_by) as prescribers FROM prescriptions WHERE $date_condition";
        $stmt = $pdo->query($query);
        $prescribers = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "SELECT JSON_UNQUOTE(JSON_EXTRACT(medicines, '$[0].medicine')) as medicine_name, 
                 COUNT(*) as count 
                 FROM prescriptions 
                 WHERE $date_condition AND medicines IS NOT NULL AND medicines != '[]'
                 GROUP BY medicine_name 
                 ORDER BY count DESC 
                 LIMIT 1";
        $stmt = $pdo->query($query);
        $most_prescribed = $most_prescribed = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Inventory
        $query = "SELECT COUNT(*) as total_items FROM medicines";
        $stmt = $pdo->query($query);
        $total_items = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "SELECT COUNT(*) as low_stock FROM medicines WHERE quantity <= 10 AND quantity > 0";
        $stmt = $pdo->query($query);
        $low_stock = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "SELECT COUNT(*) as out_of_stock FROM medicines WHERE quantity = 0";
        $stmt = $pdo->query($query);
        $out_of_stock = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $query = "SELECT COUNT(*) as reorder_needed FROM medicines WHERE quantity <= 5";
        $stmt = $pdo->query($query);
        $reorder_needed = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $reports_data = [
            [
                'title' => 'Patient Visits Report',
                'type' => 'Patient Visits',
                'data' => [
                    'Total Visits' => $patient_visits_data['total_visits'] ?? 0,
                    'Unique Patients' => $unique_patients['unique_patients'] ?? 0,
                    'Recent Visits' => $patient_visits_data['total_visits'] ?? 0,
                    'Avg Per Patient' => ($unique_patients['unique_patients'] ?? 0) > 0 ? 
                        round(($patient_visits_data['total_visits'] ?? 0) / ($unique_patients['unique_patients'] ?? 1), 1) : '0'
                ]
            ],
            [
                'title' => 'Appointments Summary',
                'type' => 'Appointments',
                'data' => [
                    'Pending' => $appointments_data['scheduled'] ?? 0,
                    'Approved' => $appointments_data['completed'] ?? 0,
                    'Declined' => $appointments_data['cancelled'] ?? 0,
                    'Rescheduled' => $appointments_data['no_show'] ?? 0
                ]
            ],
            [
                'title' => 'Medication & Prescription Report',
                'type' => 'Medication',
                'data' => [
                    'Prescriptions Issued' => $medication_data['prescriptions_issued'] ?? 0,
                    'Most Prescribed' => $most_prescribed['medicine_name'] ?? 'N/A',
                    'Average Per Patient' => ($patients_served['patients_served'] ?? 0) > 0 ? 
                        round(($medication_data['prescriptions_issued'] ?? 0) / ($patients_served['patients_served'] ?? 1), 1) : '0',
                    'Active Prescribers' => $prescribers['prescribers'] ?? 0
                ]
            ],
            [
                'title' => 'Inventory Management Report',
                'type' => 'Inventory',
                'data' => [
                    'Total Items' => $total_items['total_items'] ?? 0,
                    'Low Stock' => $low_stock['low_stock'] ?? 0,
                    'Out Of Stock' => $out_of_stock['out_of_stock'] ?? 0,
                    'Reorder Needed' => $reorder_needed['reorder_needed'] ?? 0
                ]
            ]
        ];
    }

    // Generate PDF content
    $current_date = date('M d, Y');
    $current_time = date('H:i');
    $date_range = date('M d, Y', strtotime($from_date)) . ' to ' . date('M d, Y', strtotime($to_date));
    
    // Create HTML content for PDF
    $html = generatePDFHTML($reports_data, $current_date, $current_time, $date_range, $individual_report);
    
    // Set headers for PDF download
    $filename = $individual_report ? 
        strtolower(str_replace(' ', '_', $reports_data['title'])) . '_' . date('Y-m-d') . '.pdf' : 
        'system_overview_report_' . date('Y-m-d') . '.pdf';
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: private, max-age=0, must-revalidate');
    header('Pragma: public');
    
    // For now, we'll create a simple HTML-to-PDF solution
    // In a production environment, you'd use a proper PDF library like TCPDF or mPDF
    echo generateSimplePDF($html);

} catch (PDOException $e) {
    http_response_code(500);
    die('Database error: ' . $e->getMessage());
}

function generatePDFHTML($reports_data, $current_date, $current_time, $date_range, $individual_report) {
    $is_individual = $individual_report !== null;
    $title = $is_individual ? $reports_data['title'] : 'System Overview Report';
    
    $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . $title . '</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #2563eb;
            font-size: 28px;
            margin: 0 0 10px 0;
        }
        .header h2 {
            color: #6b7280;
            font-size: 16px;
            margin: 0;
            font-weight: normal;
        }
        .report-info {
            background-color: white;
            border-left: 4px solid #2563eb;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .report-info h3 {
            color: #1f2937;
            margin: 0 0 15px 0;
            font-size: 18px;
        }
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: bold;
            color: #374151;
            width: 120px;
        }
        .info-value {
            color: #6b7280;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 20px;
        }
        .metric-card {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .metric-value {
            font-size: 32px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 8px;
        }
        .metric-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            color: #6b7280;
            font-size: 12px;
        }
        @media print {
            body { background-color: white; }
            .metric-card { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>' . $title . '</h1>
        <h2>St. Cecilia\'s College Clinic Management System</h2>
    </div>
    
    <div class="report-info">
        <h3>Report Information</h3>
        <div class="info-row">
            <span class="info-label">Generated:</span>
            <span class="info-value">' . $current_date . ' at ' . $current_time . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Date Range:</span>
            <span class="info-value">' . $date_range . '</span>
        </div>
        <div class="info-row">
            <span class="info-label">Report Type:</span>
            <span class="info-value">' . ($is_individual ? $reports_data['type'] : 'Overview') . '</span>
        </div>
    </div>';
    
    if ($is_individual) {
        // Individual report
        $html .= '<div class="metrics-grid">';
        foreach ($reports_data['data'] as $label => $value) {
            $html .= '<div class="metric-card">
                <div class="metric-value">' . $value . '</div>
                <div class="metric-label">' . $label . '</div>
            </div>';
        }
        $html .= '</div>';
    } else {
        // All reports - create system overview
        $all_metrics = [];
        foreach ($reports_data as $report) {
            foreach ($report['data'] as $label => $value) {
                if (is_numeric($value)) {
                    $all_metrics[] = ['label' => $label, 'value' => $value];
                }
            }
        }
        
        // Take the most important metrics for system overview
        $system_metrics = [
            ['label' => 'TOTAL PATIENTS', 'value' => $all_metrics[1]['value'] ?? 0], // Unique Patients
            ['label' => 'VISITS TODAY', 'value' => 0], // This would need to be calculated separately
            ['label' => 'PENDING APPOINTMENTS', 'value' => $all_metrics[4]['value'] ?? 0], // Pending
            ['label' => 'LOW STOCK MEDICINES', 'value' => $all_metrics[10]['value'] ?? 0], // Low Stock
            ['label' => 'EXPIRING MEDICINES', 'value' => 0] // This would need to be calculated separately
        ];
        
        $html .= '<div class="metrics-grid" style="grid-template-columns: repeat(3, 1fr); grid-template-rows: auto auto;">';
        foreach ($system_metrics as $index => $metric) {
            $style = '';
            if ($index === 4) { // Last metric - center it
                $style = 'grid-column: 2 / 3; grid-row: 2 / 3;';
            }
            $html .= '<div class="metric-card" style="' . $style . '">
                <div class="metric-value">' . $metric['value'] . '</div>
                <div class="metric-label">' . $metric['label'] . '</div>
            </div>';
        }
        $html .= '</div>';
    }
    
    $html .= '<div class="footer">
        This report was generated automatically by the St. Cecilia\'s College Clinic Management System<br>
        For questions or concerns, please contact the system administrator
    </div>
</body>
</html>';
    
    return $html;
}

function generateSimplePDF($html) {
    // This is a simple solution that returns HTML
    // In production, you should use a proper PDF library like TCPDF, mPDF, or DomPDF
    // For now, we'll return HTML that can be printed as PDF by the browser
    
    return $html;
}
?>
