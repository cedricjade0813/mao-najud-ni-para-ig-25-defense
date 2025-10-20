<?php
// PDF Export Handler for Clinic Management System
include '../includes/db_connect.php';

// Get report parameters
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'overview';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-t');

// Check if mPDF is available, if not, use a simple HTML to PDF approach
$use_mpdf = false;
if (class_exists('Mpdf\Mpdf')) {
    $use_mpdf = true;
}

try {
    // Generate report data
    $report_data = [];
    $report_title = '';
    
    // Get overview statistics
    $total_patients = $db->query('SELECT COUNT(*) FROM imported_patients')->fetchColumn();
    $total_visits_today = $db->query("SELECT COUNT(*) FROM prescriptions WHERE DATE(prescription_date) = CURDATE()")->fetchColumn();
    $pending_appointments = $db->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
    $low_stock_medicines = $db->query("SELECT COUNT(*) FROM medicines WHERE quantity <= 20")->fetchColumn();
    $expiring_medicines = $db->query("SELECT COUNT(*) FROM medicines WHERE expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)")->fetchColumn();
    
    // Generate report data based on type
    switch ($report_type) {
        case 'overview':
            $report_title = 'System Overview Report';
            $report_data = [
                'total_patients' => $total_patients,
                'visits_today' => $total_visits_today,
                'pending_appointments' => $pending_appointments,
                'low_stock_medicines' => $low_stock_medicines,
                'expiring_medicines' => $expiring_medicines
            ];
            break;
            
        case 'visits':
            $report_title = 'Patient Visits Report';
            $stmt = $db->prepare("
                SELECT 
                    DATE(prescription_date) as visit_date,
                    COUNT(*) as visit_count,
                    COUNT(DISTINCT patient_id) as unique_patients
                FROM prescriptions 
                WHERE prescription_date BETWEEN ? AND ?
                GROUP BY DATE(prescription_date)
                ORDER BY visit_date DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'appointments':
            $report_title = 'Appointments Report';
            $stmt = $db->prepare("
                SELECT 
                    a.date,
                    a.time,
                    a.reason,
                    a.status,
                    a.email,
                    ip.name as patient_name
                FROM appointments a
                LEFT JOIN imported_patients ip ON a.student_id = ip.id
                WHERE a.date BETWEEN ? AND ?
                ORDER BY a.date DESC, a.time DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'medications':
            $report_title = 'Medication & Prescription Report';
            $stmt = $db->prepare("
                SELECT 
                    p.patient_name,
                    p.medicines,
                    p.prescription_date,
                    p.prescribed_by
                FROM prescriptions p
                WHERE p.prescription_date BETWEEN ? AND ?
                ORDER BY p.prescription_date DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'inventory':
            $report_title = 'Inventory Management Report';
            $stmt = $db->query("
                SELECT 
                    name,
                    dosage,
                    quantity,
                    expiry,
                    CASE 
                        WHEN quantity <= 20 THEN 'Low Stock'
                        WHEN expiry BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) THEN 'Expiring Soon'
                        ELSE 'Normal'
                    END as status
                FROM medicines
                ORDER BY quantity ASC, expiry ASC
            ");
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'staff_performance':
            $report_title = 'Staff Performance Report';
            $stmt = $db->prepare("
                SELECT 
                    prescribed_by as staff_member,
                    COUNT(*) as prescriptions_issued,
                    COUNT(DISTINCT patient_id) as patients_served,
                    MIN(prescription_date) as first_prescription,
                    MAX(prescription_date) as last_prescription
                FROM prescriptions
                WHERE prescription_date BETWEEN ? AND ?
                GROUP BY prescribed_by
                ORDER BY prescriptions_issued DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'patient_demographics':
            $report_title = 'Patient Demographics Report';
            $stmt = $db->query("
                SELECT 
                    gender,
                    COUNT(*) as count,
                    ROUND(COUNT(*) * 100.0 / (SELECT COUNT(*) FROM imported_patients), 2) as percentage
                FROM imported_patients
                WHERE gender IS NOT NULL AND gender != ''
                GROUP BY gender
                ORDER BY count DESC
            ");
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'communication':
            $report_title = 'Communication Report';
            $stmt = $db->prepare("
                SELECT 
                    sender_role,
                    COUNT(*) as message_count,
                    COUNT(DISTINCT sender_id) as unique_senders,
                    COUNT(DISTINCT recipient_id) as unique_recipients
                FROM messages
                WHERE created_at BETWEEN ? AND ?
                GROUP BY sender_role
                ORDER BY message_count DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            $report_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            break;
            
        case 'system_health':
            $report_title = 'System Health Report';
            $report_data = [
                'total_users' => $db->query('SELECT COUNT(*) FROM users')->fetchColumn(),
                'active_users' => $db->query("SELECT COUNT(*) FROM users WHERE status = 'Active'")->fetchColumn(),
                'total_messages' => $db->query('SELECT COUNT(*) FROM messages')->fetchColumn(),
                'unread_notifications' => $db->query("SELECT COUNT(*) FROM notifications WHERE is_read = 0")->fetchColumn(),
                'recent_logins' => $db->query("SELECT COUNT(*) FROM logs WHERE action = 'Logged in' AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")->fetchColumn()
            ];
            break;
    }
    
    // Generate HTML content for PDF
    $html = generatePDFHTML($report_title, $report_data, $report_type, $start_date, $end_date);
    
    if ($use_mpdf) {
        try {
            // Use mPDF if available
            if (class_exists('Mpdf\Mpdf')) {
                $mpdfClass = '\\Mpdf\\Mpdf';
                $mpdf = new $mpdfClass([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
                'margin_header' => 9,
                'margin_footer' => 9
            ]);
            
                $mpdf->SetTitle($report_title);
                $mpdf->SetAuthor('St. Cecilia\'s College Clinic Management System');
                $mpdf->SetCreator('CMS Reports');
                
                $mpdf->WriteHTML($html);
                $mpdf->Output(preg_replace('/[^a-zA-Z0-9_-]/', '_', $report_title) . '_' . date('Y-m-d') . '.pdf', 'D');
            }
        } catch (Exception $mpdf_error) {
            // If mPDF fails, fall back to browser print
            $use_mpdf = false;
        }
    }
    
    if (!$use_mpdf) {
        // Fallback: Use browser's print to PDF functionality
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        echo '<script>window.print();</script>';
    }
    
} catch (Exception $e) {
    // If PDF generation fails, redirect back with error message
    $export_error = "PDF generation failed: " . $e->getMessage();
    header("Location: reports.php?" . http_build_query(array_merge($_GET, ['export_error' => $export_error])));
    exit;
}

function generatePDFHTML($title, $data, $type, $start_date, $end_date) {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>' . htmlspecialchars($title) . '</title>
        <style>
            @page {
                margin: 2cm;
                @top-center {
                    content: "St. Cecilia\'s College Clinic Management System";
                    font-size: 10pt;
                    color: #666;
                }
                @bottom-center {
                    content: "Page " counter(page) " of " counter(pages);
                    font-size: 10pt;
                    color: #666;
                }
            }
            
            body {
                font-family: Arial, sans-serif;
                font-size: 12pt;
                line-height: 1.4;
                color: #333;
                margin: 0;
                padding: 0;
            }
            
            .header {
                text-align: center;
                margin-bottom: 30px;
                border-bottom: 3px solid #3B82F6;
                padding-bottom: 20px;
            }
            
            .header h1 {
                color: #3B82F6;
                font-size: 24pt;
                margin: 0 0 10px 0;
                font-weight: bold;
            }
            
            .header .subtitle {
                color: #666;
                font-size: 14pt;
                margin: 0;
            }
            
            .report-info {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 5px;
                margin-bottom: 25px;
                border-left: 4px solid #3B82F6;
            }
            
            .report-info h3 {
                margin: 0 0 10px 0;
                color: #3B82F6;
                font-size: 14pt;
            }
            
            .report-info p {
                margin: 5px 0;
                color: #666;
            }
            
            .metrics-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
                margin-bottom: 30px;
            }
            
            .metric-card {
                background: #f8f9fa;
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                border: 1px solid #e9ecef;
            }
            
            .metric-value {
                font-size: 24pt;
                font-weight: bold;
                color: #3B82F6;
                margin-bottom: 5px;
            }
            
            .metric-label {
                color: #666;
                font-size: 11pt;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
                font-size: 10pt;
            }
            
            .data-table th {
                background: #3B82F6;
                color: white;
                padding: 12px 8px;
                text-align: left;
                font-weight: bold;
                border: 1px solid #2563eb;
            }
            
            .data-table td {
                padding: 10px 8px;
                border: 1px solid #e5e7eb;
                vertical-align: top;
            }
            
            .data-table tr:nth-child(even) {
                background: #f9fafb;
            }
            
            .data-table tr:hover {
                background: #f3f4f6;
            }
            
            .status-badge {
                padding: 4px 8px;
                border-radius: 4px;
                font-size: 9pt;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            .status-pending {
                background: #fef3c7;
                color: #92400e;
            }
            
            .status-approved {
                background: #d1fae5;
                color: #065f46;
            }
            
            .status-declined {
                background: #fee2e2;
                color: #991b1b;
            }
            
            .status-low-stock {
                background: #fee2e2;
                color: #991b1b;
            }
            
            .status-expiring {
                background: #fef3c7;
                color: #92400e;
            }
            
            .status-normal {
                background: #d1fae5;
                color: #065f46;
            }
            
            .footer {
                margin-top: 40px;
                padding-top: 20px;
                border-top: 1px solid #e5e7eb;
                text-align: center;
                color: #666;
                font-size: 10pt;
            }
            
            .chart-placeholder {
                background: #f8f9fa;
                border: 2px dashed #dee2e6;
                padding: 40px;
                text-align: center;
                margin: 20px 0;
                border-radius: 8px;
            }
            
            .chart-placeholder h4 {
                color: #6c757d;
                margin: 0;
            }
            
            @media print {
                .no-print {
                    display: none !important;
                }
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h1>' . htmlspecialchars($title) . '</h1>
            <p class="subtitle">St. Cecilia\'s College Clinic Management System</p>
        </div>
        
        <div class="report-info">
            <h3>Report Information</h3>
            <p><strong>Generated:</strong> ' . date('F d, Y \a\t H:i') . '</p>
            <p><strong>Date Range:</strong> ' . date('F d, Y', strtotime($start_date)) . ' to ' . date('F d, Y', strtotime($end_date)) . '</p>
            <p><strong>Report Type:</strong> ' . ucwords(str_replace('_', ' ', $type)) . '</p>
        </div>';
    
    // Add content based on report type
    if ($type === 'overview' || $type === 'system_health') {
        $html .= generateMetricsHTML($data);
    } elseif ($type === 'patient_demographics') {
        $html .= generateDemographicsHTML($data);
    } else {
        $html .= generateTableHTML($data, $type);
    }
    
    $html .= '
        <div class="footer">
            <p>This report was generated automatically by the St. Cecilia\'s College Clinic Management System</p>
            <p>For questions or concerns, please contact the system administrator</p>
        </div>
    </body>
    </html>';
    
    return $html;
}

function generateMetricsHTML($data) {
    $html = '<div class="metrics-grid">';
    foreach ($data as $key => $value) {
        $html .= '
        <div class="metric-card">
            <div class="metric-value">' . number_format($value) . '</div>
            <div class="metric-label">' . ucwords(str_replace('_', ' ', $key)) . '</div>
        </div>';
    }
    $html .= '</div>';
    return $html;
}

function generateDemographicsHTML($data) {
    $html = '<div class="chart-placeholder">
        <h4>Patient Demographics Distribution</h4>
        <p>Gender distribution of registered patients</p>
    </div>';
    
    $html .= '<table class="data-table">
        <thead>
            <tr>
                <th>Gender</th>
                <th>Count</th>
                <th>Percentage</th>
            </tr>
        </thead>
        <tbody>';
    
    foreach ($data as $row) {
        $html .= '
        <tr>
            <td>' . htmlspecialchars($row['gender']) . '</td>
            <td>' . number_format($row['count']) . '</td>
            <td>' . $row['percentage'] . '%</td>
        </tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}

function generateTableHTML($data, $type) {
    if (empty($data)) {
        return '<div class="chart-placeholder">
            <h4>No Data Available</h4>
            <p>No records found for the selected criteria</p>
        </div>';
    }
    
    $html = '<table class="data-table">
        <thead>
            <tr>';
    
    // Generate headers
    $headers = array_keys($data[0]);
    foreach ($headers as $header) {
        $html .= '<th>' . ucwords(str_replace('_', ' ', $header)) . '</th>';
    }
    $html .= '</tr></thead><tbody>';
    
    // Generate rows
    foreach ($data as $row) {
        $html .= '<tr>';
        foreach ($row as $key => $value) {
            if ($key === 'status' && in_array($value, ['pending', 'approved', 'declined', 'Low Stock', 'Expiring Soon', 'Normal'])) {
                $status_class = '';
                switch($value) {
                    case 'pending': $status_class = 'status-pending'; break;
                    case 'approved': $status_class = 'status-approved'; break;
                    case 'declined': $status_class = 'status-declined'; break;
                    case 'Low Stock': $status_class = 'status-low-stock'; break;
                    case 'Expiring Soon': $status_class = 'status-expiring'; break;
                    case 'Normal': $status_class = 'status-normal'; break;
                }
                $html .= '<td><span class="status-badge ' . $status_class . '">' . htmlspecialchars($value) . '</span></td>';
            } elseif ($key === 'medicines' && !empty($value)) {
                $medicines = json_decode($value, true);
                if (is_array($medicines)) {
                    $med_names = [];
                    foreach ($medicines as $med) {
                        if (isset($med['name'])) {
                            $med_names[] = $med['name'];
                        }
                    }
                    $html .= '<td>' . htmlspecialchars(implode(', ', $med_names)) . '</td>';
                } else {
                    $html .= '<td>' . htmlspecialchars($value) . '</td>';
                }
            } else {
                $html .= '<td>' . htmlspecialchars($value) . '</td>';
            }
        }
        $html .= '</tr>';
    }
    
    $html .= '</tbody></table>';
    return $html;
}
?>
