<?php
// AJAX endpoint for pagination - must be at the very beginning
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'patients_pagination') {
    // Disable error reporting to prevent HTML output
    error_reporting(0);
    ini_set('display_errors', 0);
    
    try {
        include '../includes/db_connect.php';
        
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $records_per_page = 10;
        $offset = ($page - 1) * $records_per_page;
        
        // Get total count
        $total_count = $db->query('SELECT COUNT(*) FROM imported_patients')->fetchColumn();
        $total_pages = ceil($total_count / $records_per_page);
        
        // Get paginated data
        $stmt = $db->prepare('SELECT id, student_id, name, dob, gender, address, civil_status, year_level FROM imported_patients ORDER BY id DESC LIMIT ' . (int)$records_per_page . ' OFFSET ' . (int)$offset);
        $stmt->execute();
        $patients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate pagination info
        $start_record = $offset + 1;
        $end_record = min($offset + $records_per_page, $total_count);
        
        // Set proper content type
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => true,
            'patients' => $patients,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $total_pages,
                'total_records' => $total_count,
                'per_page' => $records_per_page,
                'start_record' => $start_record,
                'end_record' => $end_record
            ]
        ]);
        exit;
    } catch (Exception $e) {
        // Set proper content type for errors too
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
        exit;
    }
}

include '../includea/header.php';
include '../includes/db_connect.php';
?>

<style>
/* Custom styles for the modern dashboard design */
.main-content {
    background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
    min-height: calc(100vh - 73px);
}

.summary-card {
    transition: all 0.3s ease;
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    backdrop-filter: blur(10px);
}


.upload-section {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    backdrop-filter: blur(10px);
}

.table-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid #E5E7EB;
    border-radius: 8px;
    backdrop-filter: blur(10px);
}

.table-row {
    transition: all 0.2s ease;
}


.status-badge {
    position: relative;
    overflow: hidden;
}

.status-badge::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
    transition: left 0.5s;
}

.status-badge:hover::before {
    left: 100%;
}


.upload-btn {
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.upload-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 6px 20px rgba(59, 130, 246, 0.4);
}

.upload-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
}

.upload-btn:hover::before {
    left: 100%;
}

/* Animation for loading states */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.loading {
    animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

/* Custom scrollbar */
.table-container::-webkit-scrollbar {
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .summary-card {
        margin-bottom: 1rem;
    }
    
    .upload-section .flex {
        flex-direction: column;
        gap: 1rem;
    }
    
}

/* Focus states for accessibility */
.focus-visible:focus {
    outline: 2px solid #3b82f6;
    outline-offset: 2px;
}

/* Smooth transitions for all interactive elements */
button, a, input, select {
    transition: all 0.2s ease;
}

/* Enhanced shadow system */
.shadow-soft {
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
}

.shadow-medium {
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
}

.shadow-strong {
    box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
}
</style>

<?php

// Database connection (using MySQL for clinic_management_system)
$db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8mb4', 'root', '');

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Ensure page is at least 1
$offset = ($page - 1) * $records_per_page;

// Get total count for pagination (from all patient tables)
$visitor_count_stmt = $db->query('SELECT COUNT(*) FROM visitor');
$visitor_count = $visitor_count_stmt->fetchColumn();

$faculty_count_stmt = $db->query('SELECT COUNT(*) FROM faculty');
$faculty_count = $faculty_count_stmt->fetchColumn();

$imported_patients_count_stmt = $db->query('SELECT COUNT(*) FROM imported_patients');
$imported_patients_count = $imported_patients_count_stmt->fetchColumn();

$imported_faculty_count_stmt = $db->query('SELECT COUNT(*) FROM faculty');
$imported_faculty_count = $imported_faculty_count_stmt->fetchColumn();

$total_records = $visitor_count + $imported_patients_count + $imported_faculty_count;
$total_pages = ceil($imported_patients_count / $records_per_page);

// Create imported_patients table if not exists (matching your actual database structure)
$db->exec('CREATE TABLE IF NOT EXISTS imported_patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(255) DEFAULT NULL,
    name VARCHAR(255) DEFAULT NULL,
    dob VARCHAR(255) DEFAULT NULL,
    gender VARCHAR(255) DEFAULT NULL,
    address VARCHAR(255) DEFAULT NULL,
    email VARCHAR(255) DEFAULT NULL,
    parent_email VARCHAR(255) DEFAULT NULL,
    parent_phone VARCHAR(20) DEFAULT NULL,
    contact_number VARCHAR(20) DEFAULT NULL,
    religion VARCHAR(100) DEFAULT NULL,
    citizenship VARCHAR(100) DEFAULT NULL,
    course_program VARCHAR(255) DEFAULT NULL,
    civil_status VARCHAR(255) DEFAULT NULL,
    password VARCHAR(255) DEFAULT NULL,
    year_level VARCHAR(255) DEFAULT NULL,
    guardian_name VARCHAR(255) DEFAULT NULL,
    guardian_contact VARCHAR(255) DEFAULT NULL,
    emergency_contact_name VARCHAR(255) DEFAULT NULL,
    emergency_contact_number VARCHAR(20) DEFAULT NULL,
    upload_year VARCHAR(9) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci');

// Note: Using existing 'faculty' table instead of creating imported_faculty

// Handle CSV upload and import
$uploadStatus = '';
$previewRows = [];
$duplicateCount = 0;
$importType = 'students'; // Default to students

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csvFile'])) {
    $file = $_FILES['csvFile']['tmp_name'];
    $importType = isset($_POST['import_type']) ? $_POST['import_type'] : 'students';
    
    // Check if file was uploaded successfully
    if (!is_uploaded_file($file)) {
        $uploadStatus = "<span class='text-red-700'>File upload failed. Please try again.</span>";
    } elseif (($handle = fopen($file, 'r')) !== false) {
        $header = fgetcsv($handle); // Assume first row is header
        
        if ($importType === 'faculty') {
            // Faculty import logic
            $inserted = 0;
            $rowCount = 0;
            
            while (($data = fgetcsv($handle)) !== false) {
                $rowCount++;
                error_log("Faculty CSV Import: Processing row $rowCount with " . count($data) . " columns");
                
                // Skip rows that don't have enough columns (faculty needs 8 columns)
                if (count($data) < 8) {
                    error_log("Faculty CSV Import: Skipping row $rowCount - insufficient columns (" . count($data) . "/8)");
                    continue;
                }
                
                // Auto-detect and skip header rows
                $firstColumn = isset($data[0]) ? strtolower(trim($data[0])) : '';
                if ($firstColumn === 'full_name' || $firstColumn === 'name' || $firstColumn === 'id' || 
                    stripos($firstColumn, 'faculty') !== false) {
                    error_log("Faculty CSV Import: Skipping row $rowCount - detected header row");
                    continue;
                }
                
                // Map faculty columns: [full_name, contact, department, college_course, gender, email, password, civil_status]
                $full_name = isset($data[0]) ? trim($data[0]) : '';
                $contact = isset($data[1]) ? trim($data[1]) : '';
                $department = isset($data[2]) ? trim($data[2]) : '';
                $college_course = isset($data[3]) ? trim($data[3]) : '';
                $gender = isset($data[4]) ? trim($data[4]) : '';
                $email = isset($data[5]) ? trim($data[5]) : '';
                $password = isset($data[6]) ? trim($data[6]) : '';
                $civil_status = isset($data[7]) ? trim($data[7]) : '';
                
                // Skip empty rows
                if (empty($full_name) || empty($email)) {
                    error_log("Faculty CSV Import: Skipping row $rowCount - empty full_name or email. full_name='$full_name', email='$email'");
                    continue;
                }
                
                $isDuplicate = false;
                // Check for duplicate email with proper charset handling
                $stmtCheck = $db->prepare('SELECT COUNT(*) FROM faculty WHERE email = ? COLLATE utf8mb4_general_ci');
                $stmtCheck->execute([$email]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $isDuplicate = true;
                    error_log("Faculty CSV Import: Duplicate found for email: $email");
                }
                
                if (!$isDuplicate) {
                    try {
                        // Get next faculty_id
                        $id_stmt = $db->query('SELECT MAX(faculty_id) FROM faculty');
                        $max_id = $id_stmt->fetchColumn();
                        $next_id = ($max_id ? $max_id + 1 : 1);
                        
                        // Hash the password before storing it
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Map department to enum values
                        $department_enum = 'College'; // Default to College
                        if (stripos($department, 'JHS') !== false || stripos($department, 'Junior') !== false) {
                            $department_enum = 'JHS';
                        } elseif (stripos($department, 'SHS') !== false || stripos($department, 'Senior') !== false) {
                            $department_enum = 'SHS';
                        }
                        
                        // Map college_course to enum values
                        $college_course_enum = 'BSIT'; // Default
                        if (stripos($college_course, 'BSBA') !== false) $college_course_enum = 'BSBA';
                        elseif (stripos($college_course, 'BEED') !== false) $college_course_enum = 'BEED';
                        elseif (stripos($college_course, 'BSED') !== false) $college_course_enum = 'BSED';
                        elseif (stripos($college_course, 'BSHTM') !== false) $college_course_enum = 'BSHTM';
                        elseif (stripos($college_course, 'BSCRIM') !== false) $college_course_enum = 'BSCRIM';
                        elseif (stripos($college_course, 'BSN') !== false) $college_course_enum = 'BSN';
                        
                        // Map gender to enum values
                        $gender_enum = 'Other'; // Default
                        if (stripos($gender, 'Male') !== false || stripos($gender, 'M') !== false) $gender_enum = 'Male';
                        elseif (stripos($gender, 'Female') !== false || stripos($gender, 'F') !== false) $gender_enum = 'Female';
                        
                        // Map civil_status to enum values
                        $civil_status_enum = 'Single'; // Default
                        if (stripos($civil_status, 'Married') !== false) $civil_status_enum = 'Married';
                        elseif (stripos($civil_status, 'Widowed') !== false) $civil_status_enum = 'Widowed';
                        elseif (stripos($civil_status, 'Divorced') !== false) $civil_status_enum = 'Divorced';
                        
                        $stmt2 = $db->prepare('INSERT INTO faculty (faculty_id, full_name, address, contact, emergency_contact, age, department, college_course, gender, email, password, civil_status, citizenship) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                        $result = $stmt2->execute([
                            $next_id,
                            $full_name,
                            'Not specified', // address (required field)
                            $contact,
                            $contact, // emergency_contact (use same as contact)
                            25, // age (default value)
                            $department_enum,
                            $college_course_enum,
                            $gender_enum,
                            $email,
                            $hashedPassword,
                            $civil_status_enum,
                            'Filipino' // citizenship (default value)
                        ]);
                        
                        if ($result) {
                            $inserted++;
                            error_log("Faculty CSV Import: Successfully inserted email: $email");
                        } else {
                            error_log("Faculty CSV Import: Failed to insert record for email: $email");
                        }
                    } catch (PDOException $e) {
                        error_log("Faculty CSV Import Error: " . $e->getMessage() . " for email: $email");
                    }
                } else {
                    $duplicateCount++;
                }
            }
            fclose($handle);
            
            // Enhanced status message with more details
            if ($inserted > 0) {
                $uploadStatus = "<span class='text-green-700'>Faculty upload and import successful! $inserted new record(s) added.";
                if ($duplicateCount > 0) {
                    $uploadStatus .= " $duplicateCount duplicate(s) skipped.";
                }
                $uploadStatus .= "</span>";
            } else {
                $uploadStatus = "<span class='text-yellow-700'>No new faculty records added.";
                if ($duplicateCount > 0) {
                    $uploadStatus .= " $duplicateCount duplicate(s) found.";
                }
                $uploadStatus .= " Check your CSV format and try again.</span>";
            }
        } else {
            // Student import logic (existing code)
            $existingIds = [];
            $stmt = $db->query('SELECT id FROM imported_patients');
            foreach ($stmt as $row) {
                $existingIds[] = $row['id'];
            }
            $inserted = 0;
            $rowCount = 0;
            $hasHeader = false;
            
            while (($data = fgetcsv($handle)) !== false) {
                $rowCount++;
                error_log("CSV Import: Processing row $rowCount with " . count($data) . " columns");
                
                // Skip rows that don't have enough columns
                if (count($data) < 8) {
                    error_log("CSV Import: Skipping row $rowCount - insufficient columns (" . count($data) . "/8)");
                    continue;
                }
                
                // Auto-detect and skip header rows
                $firstColumn = isset($data[0]) ? strtolower(trim($data[0])) : '';
                if ($firstColumn === 'student_id' || $firstColumn === 'studentid' || $firstColumn === 'id' || 
                    stripos($firstColumn, 'student') !== false) {
                    error_log("CSV Import: Skipping row $rowCount - detected header row");
                    continue;
                }
                
                // Map columns: [student_id, name, dob, gender, address, civil_status, password, year_level]
                $student_id = isset($data[0]) ? trim($data[0]) : '';
                $name = isset($data[1]) ? trim($data[1]) : '';
                $dob = isset($data[2]) ? trim($data[2]) : '';
                $gender = isset($data[3]) ? trim($data[3]) : '';
                $address = isset($data[4]) ? trim($data[4]) : '';
                $civil_status = isset($data[5]) ? trim($data[5]) : '';
                $password = isset($data[6]) ? trim($data[6]) : '';
                $year_level = isset($data[7]) ? trim($data[7]) : '';
                
                // Skip empty rows
                if (empty($student_id) || empty($name)) {
                    error_log("CSV Import: Skipping row $rowCount - empty student_id or name. student_id='$student_id', name='$name'");
                    continue;
                }
                
                $isDuplicate = false;
                // Check for duplicate student_id with proper charset handling
                $stmtCheck = $db->prepare('SELECT COUNT(*) FROM imported_patients WHERE student_id = ? COLLATE utf8mb4_general_ci');
                $stmtCheck->execute([$student_id]);
                if ($stmtCheck->fetchColumn() > 0) {
                    $isDuplicate = true;
                    error_log("CSV Import: Duplicate found for student_id: $student_id");
                }
                $previewRows[] = [
                    'student_id' => $student_id,
                    'name' => $name,
                    'dob' => $dob,
                    'gender' => $gender,
                    'address' => $address,
                    'civil_status' => $civil_status,
                    'password' => $password,
                    'year_level' => $year_level,
                    'duplicate' => $isDuplicate
                ];
                if (!$isDuplicate) {
                    try {
                        // Hash the password before storing it
                        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                        $stmt2 = $db->prepare('INSERT INTO imported_patients (student_id, name, dob, gender, address, civil_status, password, year_level) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
                        $result = $stmt2->execute([$student_id, $name, $dob, $gender, $address, $civil_status, $hashedPassword, $year_level]);
                        
                        if ($result) {
                            $inserted++;
                            error_log("CSV Import: Successfully inserted student_id: $student_id");
                        } else {
                            error_log("CSV Import: Failed to insert record for student_id: $student_id");
                        }
                    } catch (PDOException $e) {
                        error_log("CSV Import Error: " . $e->getMessage() . " for student_id: $student_id");
                    }
                } else {
                    $duplicateCount++;
                }
            }
            fclose($handle);
            
            // Enhanced status message with more details
            if ($inserted > 0) {
                $uploadStatus = "<span class='text-green-700'>Student upload and import successful! $inserted new record(s) added.";
                if ($duplicateCount > 0) {
                    $uploadStatus .= " $duplicateCount duplicate(s) skipped.";
                }
                $uploadStatus .= "</span>";
            } else {
                $uploadStatus = "<span class='text-yellow-700'>No new student records added.";
                if ($duplicateCount > 0) {
                    $uploadStatus .= " $duplicateCount duplicate(s) found.";
                }
                $uploadStatus .= " Check your CSV format and try again.</span>";
            }
        }
    } else {
        $uploadStatus = "<span class='text-red-700'>Failed to open uploaded file.</span>";
    }
}
?>

<main class="flex-1 overflow-y-auto main-content p-6 ml-16 md:ml-64 mt-[56px]">
        <!-- Application Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Patient Records</h1>
            <p class="text-gray-600">Manage your patients and their permissions</p>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-white rounded-lg p-6 summary-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Total Patients</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $total_records; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-blue-50 rounded-lg flex items-center justify-center">
                        <i class="ri-user-line text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg p-6 summary-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Students Records</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo max(0, $imported_patients_count - $duplicateCount); ?></p>
                    </div>
                    <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                        <i class="ri-user-check-line text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg p-6 summary-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Faculty Records</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $imported_faculty_count; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                        <i class="ri-user-star-line text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg p-6 summary-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Duplicates</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $duplicateCount; ?></p>
                    </div>
                    <div class="w-12 h-12 bg-red-50 rounded-lg flex items-center justify-center">
                        <i class="ri-user-unfollow-line text-2xl text-red-600"></i>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg p-6 summary-card">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 mb-1">Import Status</p>
                        <p class="text-lg font-semibold text-gray-800">Ready</p>
                    </div>
                    <div class="w-12 h-12 bg-gray-50 rounded-lg flex items-center justify-center">
                        <i class="ri-database-2-line text-2xl text-gray-600"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search & Filter Section -->
        <div class="bg-white rounded-lg p-6 mb-8 upload-section">
            <h3 class="text-lg font-semibold text-gray-800 mb-2">Import & Filter</h3>
            <p class="text-gray-600 text-sm mb-6">Upload CSV files or use filters to manage patient data</p>
            
            <div class="flex flex-col lg:flex-row gap-4 items-start lg:items-end">
                <!-- File Upload Form -->
                <div class="flex-1">
                    <form id="csvUploadForm" enctype="multipart/form-data" method="post" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Import Type</label>
                                <select name="import_type" id="importType" class="block w-full px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" style="padding-top: 1.0625rem; padding-bottom: 1.0625rem;">
                                    <option value="students">Students</option>
                                    <option value="faculty">Faculty</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                                <div class="flex items-center space-x-4">
                                    <input type="file" name="csvFile" id="csvFile" accept=".csv"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700 border border-gray-300 rounded-lg px-3 py-2"
                                        required />
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- Action and Filter Controls -->
                <div class="flex items-center space-x-3">
                    <button type="submit" form="csvUploadForm"
                        class="px-6 py-2 upload-btn text-white font-medium text-sm rounded-lg flex items-center space-x-2">
                        <i class="ri-upload-line"></i>
                        <span>Upload</span>
                    </button>
                    <select id="yearFilter" class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">All Year</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                    </select>
                    <button id="exportBtn" class="px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm hover:bg-gray-50 transition-colors duration-200 flex items-center space-x-2">
                        <i class="ri-download-line"></i>
                        <span>Export</span>
                    </button>
                </div>
            </div>
            
            <!-- Upload Status Notification -->
            <?php if ($uploadStatus): ?>
                <div id="uploadStatus" class="mt-4 p-4 rounded-lg border-l-4 <?php echo strpos($uploadStatus, 'successful') !== false ? 'bg-green-50 border-green-400 text-green-800' : (strpos($uploadStatus, 'failed') !== false ? 'bg-red-50 border-red-400 text-red-800' : 'bg-yellow-50 border-yellow-400 text-yellow-800'); ?>">
                    <?php echo $uploadStatus; ?>
                </div>
            <?php else: ?>
                <div id="uploadStatus" class="hidden mt-4 p-4 rounded-lg"></div>
            <?php endif; ?>
        </div>
        <!-- Records Directory Table -->
        <div class="bg-white rounded-lg table-container">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex justify-between items-end">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-800">Records Directory</h3>
                        <p class="text-gray-600 text-sm mt-1">Complete list of imported records with pagination</p>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="relative">
                            <input type="text" id="tableSearch" placeholder="Search records..." 
                                class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="ri-search-line text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Navigation Tabs -->
            <div class="px-6 py-3 border-b border-gray-200">
                <nav class="flex space-x-8">
                    <button class="record-tab active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-tab="students">
                        Students (<span id="studentsTabCount"><?php echo $imported_patients_count; ?></span>)
                    </button>
                    <button class="record-tab px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-tab="faculty">
                        Faculty (<span id="facultyTabCount"><?php echo $imported_faculty_count; ?></span>)
                    </button>
                </nav>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full table-fixed divide-y divide-gray-200" id="importedPatientsTable">
                    <thead class="bg-gray-50" id="tableHeaders">
                        <tr id="studentHeaders">
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="w-48 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="w-48 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Address
                            </th>
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Gender
                            </th>
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Year Level
                            </th>
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Birth Date
                            </th>
                        </tr>
                        <tr id="facultyHeaders" style="display: none;">
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                ID
                            </th>
                            <th class="w-48 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Full Name
                            </th>
                            <th class="w-48 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Department
                            </th>
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Contact
                            </th>
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Gender
                            </th>
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="patientsTableBody">
                    <?php
                    $stmt = $db->prepare('SELECT id, student_id, name, dob, gender, address, civil_status, year_level FROM imported_patients ORDER BY id DESC LIMIT ' . (int)$records_per_page . ' OFFSET ' . (int)$offset);
                    $stmt->execute();
                    $patients = $stmt->fetchAll();
                    
                    if (count($patients) > 0):
                        foreach ($patients as $row): ?>
                        <tr class="table-row">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($row['student_id']); ?>">
                                <?php echo htmlspecialchars($row['student_id']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($row['name']); ?>">
                                <?php echo htmlspecialchars($row['name']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 truncate" title="<?php echo htmlspecialchars($row['address']); ?>">
                                <?php echo htmlspecialchars($row['address']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 truncate" title="<?php echo htmlspecialchars($row['gender']); ?>">
                                <?php echo htmlspecialchars($row['gender']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 truncate" title="<?php echo htmlspecialchars($row['year_level']); ?>">
                                <?php echo htmlspecialchars($row['year_level']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 truncate" title="<?php echo htmlspecialchars($row['dob']); ?>">
                                <?php echo htmlspecialchars($row['dob']); ?>
                            </td>
                        </tr>
                        <?php endforeach;
                    else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="ri-user-line text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg font-medium">No patients found</p>
                                    <p class="text-gray-400 text-sm">Upload a CSV file to get started</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination and Records Info -->
            <?php if ($total_records > 0): ?>
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex justify-between items-center">
                    <!-- Records Information -->
                    <div class="text-sm text-gray-500" id="patientsEntriesInfo">
                        Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $records_per_page, $imported_patients_count); ?> of <?php echo $imported_patients_count; ?> entries
                    </div>

                    <!-- Pagination Navigation -->
                    <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination" id="patientsPagination">
                        <!-- Pagination will be generated here by JavaScript -->
                    </nav>
                </div>
            </div>
            <?php endif; ?>
        </div>
</main>

<script>
// Table search and filter functionality with server-side search
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('tableSearch');
    const yearFilter = document.getElementById('yearFilter');
    const exportBtn = document.getElementById('exportBtn');
    const table = document.getElementById('importedPatientsTable');
    const importType = document.getElementById('importType');
    const csvFormatInfo = document.getElementById('csvFormatInfo');
    const studentHeaders = document.getElementById('studentHeaders');
    const facultyHeaders = document.getElementById('facultyHeaders');
    
    // Handle import type change
    if (importType && csvFormatInfo) {
        importType.addEventListener('change', function() {
            const selectedType = this.value;
            if (selectedType === 'faculty') {
                csvFormatInfo.innerHTML = '<strong>Faculty CSV Format:</strong> full_name, contact, department, college_course, gender, email, password, civil_status<br><em>Note: Department should be JHS/SHS/College, College Course should be BSIT/BSBA/BEED/BSED/BSHTM/BSCRIM/BSN</em>';
            } else {
                csvFormatInfo.innerHTML = '<strong>Students CSV Format:</strong> student_id, name, dob, gender, address, civil_status, password, year_level';
            }
        });
    }
    
    // Tab switching functionality (following staff/records.php logic)
    const tabs = document.querySelectorAll('.record-tab');
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Remove active class from all tabs
            tabs.forEach(t => {
                t.classList.remove('active', 'text-blue-600', 'border-blue-600');
                t.classList.add('text-gray-500', 'border-transparent');
            });
            
            // Add active class to clicked tab
            this.classList.add('active', 'text-blue-600', 'border-blue-600');
            this.classList.remove('text-gray-500', 'border-transparent');
            
            // Get the tab type and filter data
            const tabType = this.getAttribute('data-tab');
            filterRecordsByType(tabType);
        });
    });
    
    // Function to filter records by type
    function filterRecordsByType(type) {
        if (type === 'faculty') {
            studentHeaders.style.display = 'none';
            facultyHeaders.style.display = 'table-row';
            loadFacultyData();
            // Refresh faculty count when switching to faculty tab
            refreshFacultyCount();
        } else {
            studentHeaders.style.display = 'table-row';
            facultyHeaders.style.display = 'none';
            loadStudentData();
        }
    }
    
    // Load faculty data with pagination
    function loadFacultyData(page = 1) {
        const tbody = document.getElementById('patientsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
                        <p class="text-gray-500 text-lg font-medium">Loading faculty data...</p>
                    </div>
                </td>
            </tr>
        `;
        
        fetch('get_faculty_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'page=' + page + '&per_page=10'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateFacultyTable(data.faculty);
                updateFacultyPagination(data.pagination);
            } else {
                console.error('Error loading faculty:', data.error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="ri-user-star-line text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 text-lg font-medium">No faculty found</p>
                                <p class="text-gray-400 text-sm">Import faculty CSV to get started</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading faculty:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="ri-user-star-line text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg font-medium">Error loading faculty</p>
                            <p class="text-gray-400 text-sm">Please try again</p>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    // Load student data
    function loadStudentData() {
        const tbody = document.getElementById('patientsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
                        <p class="text-gray-500 text-lg font-medium">Loading student data...</p>
                    </div>
                </td>
            </tr>
        `;
        
        // Load student data via AJAX
        fetch('?ajax=patients_pagination&page=1')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateStudentsTable(data.patients);
                } else {
                    console.error('Error loading students:', data.error);
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="ri-user-line text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg font-medium">No students found</p>
                                    <p class="text-gray-400 text-sm">Import student CSV to get started</p>
                                </div>
                            </td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error('Error loading students:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="ri-user-line text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 text-lg font-medium">Error loading students</p>
                                <p class="text-gray-400 text-sm">Please try again</p>
                            </div>
                        </td>
                    </tr>
                `;
            });
    }
    
    // Update students table
    function updateStudentsTable(students) {
        const tbody = document.getElementById('patientsTableBody');
        
        if (students.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="ri-user-line text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg font-medium">No students found</p>
                            <p class="text-gray-400 text-sm">Import student CSV to get started</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        let html = '';
        students.forEach(student => {
            const birthDate = student.dob ? new Date(student.dob).toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            }) : 'N/A';
            
            html += `
                <tr class="table-row">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${student.student_id}">
                        ${student.student_id}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${student.name}">
                        ${student.name}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${student.address}">
                        ${student.address}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${student.gender}">
                        ${student.gender}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${student.year_level}">
                        ${student.year_level}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${birthDate}">
                        ${birthDate}
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }
    
    // Update faculty pagination
    function updateFacultyPagination(pagination) {
        const paginationContainer = document.querySelector('.px-6.py-4.border-t.border-gray-200.bg-gray-50');
        if (paginationContainer) {
            const recordsInfo = paginationContainer.querySelector('.text-sm.text-gray-500');
            const paginationNav = paginationContainer.querySelector('nav');
            
            if (recordsInfo) {
                recordsInfo.innerHTML = `Showing ${pagination.start_record} to ${pagination.end_record} of ${pagination.total_records} entries`;
            }
            
            if (paginationNav && pagination.total_pages > 1) {
                updateFacultyPaginationButtons(paginationNav, pagination.current_page, pagination.total_pages);
            } else if (paginationNav) {
                paginationNav.innerHTML = '';
            }
        }
    }
    
    // Update faculty pagination buttons
    function updateFacultyPaginationButtons(nav, currentPage, totalPages) {
        let paginationHTML = '';
        
        // Previous Button
        if (currentPage > 1) {
            paginationHTML += `
                <button onclick="loadFacultyPage(${currentPage - 1})" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>
            `;
        } else {
            paginationHTML += `
                <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Previous">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>
            `;
        }

        // Page Numbers
        const start_page = Math.max(1, currentPage - 2);
        const end_page = Math.min(totalPages, currentPage + 2);

        // Show first page if not in range
        if (start_page > 1) {
            paginationHTML += `
                <button onclick="loadFacultyPage(1)" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">1</button>
            `;
            if (start_page > 2) {
                paginationHTML += `
                    <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                `;
            }
        }

        // Page numbers
        for (let i = start_page; i <= end_page; i++) {
            if (i === currentPage) {
                paginationHTML += `
                    <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-200 text-gray-800 border border-gray-200 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-300" aria-current="page">${i}</button>
                `;
            } else {
                paginationHTML += `
                    <button onclick="loadFacultyPage(${i})" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">${i}</button>
                `;
            }
        }

        // Show last page if not in range
        if (end_page < totalPages) {
            if (end_page < totalPages - 1) {
                paginationHTML += `
                    <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                `;
            }
            paginationHTML += `
                <button onclick="loadFacultyPage(${totalPages})" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">${totalPages}</button>
            `;
        }

        // Next Button
        if (currentPage < totalPages) {
            paginationHTML += `
                <button onclick="loadFacultyPage(${currentPage + 1})" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
        } else {
            paginationHTML += `
                <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Next">
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
        }

        nav.innerHTML = paginationHTML;
    }
    
    // Global function for faculty pagination
    window.loadFacultyPage = function(page) {
        loadFacultyData(page);
    };
    
    // Update faculty table
    function updateFacultyTable(faculty) {
        const tbody = document.getElementById('patientsTableBody');
        
        if (faculty.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="ri-user-star-line text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg font-medium">No faculty found</p>
                            <p class="text-gray-400 text-sm">Import faculty CSV to get started</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        let html = '';
        faculty.forEach(facultyMember => {
            html += `
                <tr class="table-row">
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${facultyMember.faculty_id}">
                        ${facultyMember.faculty_id}
                    </td>
                    <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${facultyMember.full_name}">
                        ${facultyMember.full_name}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${facultyMember.department}">
                        ${facultyMember.department}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${facultyMember.contact}">
                        ${facultyMember.contact}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${facultyMember.gender}">
                        ${facultyMember.gender}
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${facultyMember.email}">
                        ${facultyMember.email}
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
    }
    
    // Initialize with students tab by default
    filterRecordsByType('students');
    
    // Function to update tab counts
    function updateTabCounts() {
        // Update students count
        fetch('?ajax=patients_pagination&page=1')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('studentsTabCount').textContent = data.pagination.total_records;
                }
            })
            .catch(error => console.error('Error updating students count:', error));
        
        // Update faculty count
        fetch('get_faculty_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'page=1&per_page=1' // Just get the count, not the actual data
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('facultyTabCount').textContent = data.pagination.total_records;
            }
        })
        .catch(error => console.error('Error updating faculty count:', error));
    }
    
    // Update tab counts on page load
    updateTabCounts();
    
    // Function to refresh faculty count specifically
    function refreshFacultyCount() {
        fetch('get_faculty_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'page=1&per_page=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.getElementById('facultyTabCount').textContent = data.pagination.total_records;
                console.log('Faculty count updated to:', data.pagination.total_records);
            }
        })
        .catch(error => console.error('Error updating faculty count:', error));
    }
    
    // Add form submission handler to refresh current tab after successful upload
    const csvUploadForm = document.getElementById('csvUploadForm');
    if (csvUploadForm) {
        csvUploadForm.addEventListener('submit', function(e) {
            // Get the import type before submission
            const importType = document.getElementById('importType');
            const selectedImportType = importType ? importType.value : 'students';
            
            // Add a small delay to allow the server to process the upload
            setTimeout(() => {
                // Refresh tab counts
                updateTabCounts();
                
                // If faculty was imported, specifically refresh faculty count
                if (selectedImportType === 'faculty') {
                    refreshFacultyCount();
                }
                
                // Refresh current tab content
                const activeTab = document.querySelector('.record-tab.active');
                if (activeTab) {
                    const currentType = activeTab.getAttribute('data-tab');
                    filterRecordsByType(currentType);
                }
            }, 1000); // 1 second delay to allow server processing
        });
    }
    
    // Search functionality with debouncing
    let searchTimeout;
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                const searchTerm = this.value.trim();
                const activeTab = document.querySelector('.record-tab.active');
                const currentType = activeTab ? activeTab.getAttribute('data-tab') : 'students';
                
                if (searchTerm) {
                    searchRecords(searchTerm, currentType);
                } else {
                    filterRecordsByType(currentType);
                }
            }, 300); // Wait 300ms after user stops typing
        });
    }
    
    // Function to search records
    function searchRecords(searchTerm, type) {
        if (type === 'faculty') {
            searchFacultyData(searchTerm);
        } else {
            searchStudentData(searchTerm);
        }
    }
    
    // Function to search faculty data
    function searchFacultyData(searchTerm, page = 1) {
        const tbody = document.getElementById('patientsTableBody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
                        <p class="text-gray-500 text-lg font-medium">Searching faculty...</p>
                    </div>
                </td>
            </tr>
        `;
        
        fetch('get_faculty_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'search=' + encodeURIComponent(searchTerm) + '&page=' + page + '&per_page=10'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateFacultyTable(data.faculty);
                updateFacultyPagination(data.pagination);
            } else {
                console.error('Error searching faculty:', data.error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="ri-user-star-line text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 text-lg font-medium">No faculty found</p>
                                <p class="text-gray-400 text-sm">Try adjusting your search terms</p>
                            </div>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(error => {
            console.error('Error searching faculty:', error);
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="ri-user-star-line text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg font-medium">Error searching faculty</p>
                            <p class="text-gray-400 text-sm">Please try again</p>
                        </div>
                    </td>
                </tr>
            `;
        });
    }
    
    // Function to search student data
    function searchStudentData(searchTerm) {
        // Use existing search functionality for students
        searchPatients();
    }
    
    // Year filter functionality
    if (yearFilter) {
        yearFilter.addEventListener('change', function() {
            searchPatients();
        });
    }
    
    // Export functionality
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            exportToCSV();
        });
    }
    
    function searchPatients(page = 1) {
        const searchTerm = searchInput ? searchInput.value.trim() : '';
        const selectedYear = yearFilter ? yearFilter.value : '';
        
        // Show loading state
        const tbody = table.querySelector('tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
                        <p class="text-gray-500 text-lg font-medium">Searching patients...</p>
                    </div>
                </td>
            </tr>
        `;

        // Make AJAX request
        const formData = new FormData();
        formData.append('search', searchTerm);
        formData.append('year_filter', selectedYear);
        formData.append('page', page);

        fetch('search_imported_patients.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTableWithData(data.patients, data.total_records, data.current_page, data.total_pages, data.start, data.end);
            } else {
                alert('Search failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            alert('Search failed. Please try again.');
        });
    }

    function updateTableWithData(patients, totalRecords, currentPage, totalPages, start, end) {
        const tbody = table.querySelector('tbody');
        
        if (patients.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="ri-user-line text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg font-medium">No patients found</p>
                            <p class="text-gray-400 text-sm">Try adjusting your search terms or filters</p>
                        </div>
                    </td>
                </tr>
            `;
        } else {
            let tableHTML = '';
            patients.forEach(function(patient) {
                tableHTML += `
                    <tr class="table-row">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${patient.student_id}">
                            ${patient.student_id}
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${patient.name}">
                            ${patient.name}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${patient.address}">
                            ${patient.address}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${patient.gender}">
                            ${patient.gender}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${patient.year_level}">
                            ${patient.year_level}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${patient.dob}">
                            ${patient.dob}
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = tableHTML;
        }
        
        // Update pagination
        updatePagination(totalRecords, currentPage, totalPages, start, end);
    }

    function updatePagination(totalRecords, currentPage, totalPages, start, end) {
        const paginationContainer = document.querySelector('.px-6.py-4.border-t.border-gray-200.bg-gray-50');
        if (paginationContainer) {
            const recordsInfo = paginationContainer.querySelector('.text-sm.text-gray-500');
            const paginationNav = paginationContainer.querySelector('nav');
            
            if (recordsInfo) {
                recordsInfo.innerHTML = `Showing ${start} to ${end} of ${totalRecords} entries`;
            }
            
            if (paginationNav && totalPages > 1) {
                updatePaginationButtons(paginationNav, currentPage, totalPages);
            } else if (paginationNav) {
                paginationNav.innerHTML = '';
            }
        }
    }

    function updatePaginationButtons(nav, currentPage, totalPages) {
        let paginationHTML = '';
        
        // Previous Button
        if (currentPage > 1) {
            paginationHTML += `
                <button onclick="searchPatients(${currentPage - 1})" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>
            `;
        } else {
            paginationHTML += `
                <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Previous">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>
            `;
        }

        // Page Numbers
        const start_page = Math.max(1, currentPage - 2);
        const end_page = Math.min(totalPages, currentPage + 2);

        // Show first page if not in range
        if (start_page > 1) {
            paginationHTML += `
                <button onclick="searchPatients(1)" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">1</button>
            `;
            if (start_page > 2) {
                paginationHTML += `
                    <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                `;
            }
        }

        // Page numbers
        for (let i = start_page; i <= end_page; i++) {
            if (i === currentPage) {
                paginationHTML += `
                    <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-200 text-gray-800 border border-gray-200 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-300" aria-current="page">${i}</button>
                `;
            } else {
                paginationHTML += `
                    <button onclick="searchPatients(${i})" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">${i}</button>
                `;
            }
        }

        // Show last page if not in range
        if (end_page < totalPages) {
            if (end_page < totalPages - 1) {
                paginationHTML += `
                    <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                `;
            }
            paginationHTML += `
                <button onclick="searchPatients(${totalPages})" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">${totalPages}</button>
            `;
        }

        // Next Button
        if (currentPage < totalPages) {
            paginationHTML += `
                <button onclick="searchPatients(${currentPage + 1})" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
        } else {
            paginationHTML += `
                <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Next">
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
        }

        nav.innerHTML = paginationHTML;
    }
    
    function exportToCSV() {
        const searchTerm = searchInput ? searchInput.value.trim() : '';
        const selectedYear = yearFilter ? yearFilter.value : '';
        
        // Export all filtered records, not just current page
        fetch('export_patients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                search: searchTerm,
                year_level: selectedYear
            })
        })
        .then(response => response.blob())
        .then(blob => {
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `patients_export_${selectedYear || 'all'}_${new Date().toISOString().split('T')[0]}.csv`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            window.URL.revokeObjectURL(url);
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error exporting data. Please try again.');
        });
    }
});

// AJAX Pagination for Patient Directory
let currentPatientsPage = <?php echo $page; ?>;
const patientsPerPage = 10;

// Load patients page via AJAX
function loadPatientsPage(page) {
    currentPatientsPage = page;
    
    fetch(`?ajax=patients_pagination&page=${page}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                updatePatientsTable(data.patients);
                updatePatientsPagination(data.pagination);
            } else {
                console.error('Server error:', data.error || 'Unknown error');
                alert('Error loading patients: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error loading patients:', error);
            alert('Error loading patients. Please refresh the page and try again.');
        });
}

// Update patients table
function updatePatientsTable(patients) {
    const tbody = document.getElementById('patientsTableBody');
    if (!tbody) return;
    
    if (patients.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <i class="ri-user-line text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg font-medium">No patients found</p>
                        <p class="text-gray-400 text-sm">Upload a CSV file to get started</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    let html = '';
    patients.forEach(patient => {
        const birthDate = patient.dob ? new Date(patient.dob).toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        }) : 'N/A';
        
        html += `
            <tr class="table-row">
                <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${patient.student_id}">
                    ${patient.student_id}
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${patient.name}">
                    ${patient.name}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${patient.address}">
                    ${patient.address}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${patient.gender}">
                    ${patient.gender}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${patient.year_level}">
                    ${patient.year_level}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${birthDate}">
                    ${birthDate}
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;
}

// Update pagination
function updatePatientsPagination(pagination) {
    const paginationContainer = document.getElementById('patientsPagination');
    const entriesInfo = document.getElementById('patientsEntriesInfo');
    
    if (!paginationContainer || !entriesInfo) return;
    
    // Update entries info
    entriesInfo.textContent = `Showing ${pagination.start_record} to ${pagination.end_record} of ${pagination.total_records} entries`;
    
    // Generate pagination HTML
    let paginationHtml = '';
    
    if (pagination.total_pages > 1) {
        // Previous button
        paginationHtml += `
            <button type="button" ${pagination.current_page === 1 ? 'disabled' : ''} 
                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" 
                    onclick="loadPatientsPage(${pagination.current_page - 1})" aria-label="Previous">
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                <span class="sr-only">Previous</span>
            </button>
        `;
        
        // Page numbers with ellipses
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        if (startPage > 1) {
            paginationHtml += `<button onclick="loadPatientsPage(1)" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === pagination.current_page) {
                paginationHtml += `<button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-200 text-gray-800 border border-gray-200 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-300" aria-current="page">${i}</button>`;
            } else {
                paginationHtml += `<button onclick="loadPatientsPage(${i})" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">${i}</button>`;
            }
        }
        
        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                paginationHtml += `<span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>`;
            }
            paginationHtml += `<button onclick="loadPatientsPage(${pagination.total_pages})" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">${pagination.total_pages}</button>`;
        }
        
        // Next button
        paginationHtml += `
            <button type="button" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''} 
                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" 
                    onclick="loadPatientsPage(${pagination.current_page + 1})" aria-label="Next">
                <span class="sr-only">Next</span>
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </button>
        `;
    }
    
    paginationContainer.innerHTML = paginationHtml;
}

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize with current page
    loadPatientsPage(currentPatientsPage);
});
</script>

<?php
include '../includea/footer.php';
?>