<?php
include '../includes/db_connect.php';
session_start();
include '../includes/header.php';
try {
    
    // Create prescriptions table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        patient_name VARCHAR(255),
        prescribed_by VARCHAR(255),
        prescription_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        medicines TEXT,
        notes TEXT
    )");
    // Create pending_prescriptions table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS pending_prescriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        patient_name VARCHAR(255),
        prescribed_by VARCHAR(255),
        prescription_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        medicines TEXT,
        notes TEXT
    )");
    
    // Create vital_signs table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS vital_signs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        patient_name VARCHAR(255),
        vital_date DATE,
        weight DECIMAL(5,2),
        height DECIMAL(5,2),
        body_temp DECIMAL(4,2),
        resp_rate INT,
        pulse INT,
        blood_pressure VARCHAR(20),
        oxygen_sat DECIMAL(5,2),
        remarks TEXT,
        recorded_by VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_patient_date (patient_id, vital_date)
    )");
    
    // Add blood_pressure column if it doesn't exist
    try {
        $db->exec("ALTER TABLE vital_signs ADD COLUMN blood_pressure VARCHAR(20) AFTER pulse");
    } catch (Exception $e) { 
        // Ignore if column already exists
    }
    
    // Option A: Add visitor support columns for vital_signs
    try {
        $db->exec("ALTER TABLE vital_signs ADD COLUMN IF NOT EXISTS visitor_id INT NULL AFTER patient_id");
    } catch (Exception $e) { /* ignore */ }
    try {
        $db->exec("ALTER TABLE vital_signs ADD COLUMN IF NOT EXISTS visitor_name VARCHAR(255) NULL AFTER patient_name");
    } catch (Exception $e) { /* ignore */ }
    try {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vital_signs_visitor_id ON vital_signs (visitor_id)");
    } catch (Exception $e) { /* ignore */ }

    // Add faculty support columns for vital_signs
    try {
        $db->exec("ALTER TABLE vital_signs ADD COLUMN IF NOT EXISTS faculty_id INT NULL AFTER visitor_name");
    } catch (Exception $e) { /* ignore */ }
    try {
        $db->exec("ALTER TABLE vital_signs ADD COLUMN IF NOT EXISTS faculty_name VARCHAR(255) NULL AFTER faculty_id");
    } catch (Exception $e) { /* ignore */ }
    try {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_vital_signs_faculty_id ON vital_signs (faculty_id)");
    } catch (Exception $e) { /* ignore */ }
    
    // Create medication_referrals table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS medication_referrals (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT,
        patient_name VARCHAR(255),
        subjective TEXT,
        objective TEXT,
        assessment TEXT,
        plan TEXT,
        intervention TEXT,
        evaluation TEXT,
        recorded_by VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Option A: Add visitor support columns for medication_referrals
    try {
        $db->exec("ALTER TABLE medication_referrals ADD COLUMN IF NOT EXISTS visitor_id INT NULL AFTER patient_id");
    } catch (Exception $e) { /* ignore */ }
    try {
        $db->exec("ALTER TABLE medication_referrals ADD COLUMN IF NOT EXISTS visitor_name VARCHAR(255) NULL AFTER patient_name");
    } catch (Exception $e) { /* ignore */ }
    try {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_med_ref_visitor_id ON medication_referrals (visitor_id)");
    } catch (Exception $e) { /* ignore */ }
    
    // Add patient_email and parent_email columns to prescriptions and pending_prescriptions if not exist
    $db->exec("ALTER TABLE prescriptions ADD COLUMN IF NOT EXISTS patient_email VARCHAR(255) AFTER patient_name");
    $db->exec("ALTER TABLE prescriptions ADD COLUMN IF NOT EXISTS parent_email VARCHAR(255) AFTER patient_email");
    $db->exec("ALTER TABLE pending_prescriptions ADD COLUMN IF NOT EXISTS patient_email VARCHAR(255) AFTER patient_name");
    $db->exec("ALTER TABLE pending_prescriptions ADD COLUMN IF NOT EXISTS parent_email VARCHAR(255) AFTER patient_email");
    
    // Add reason column to prescriptions and pending_prescriptions if not exist
    $db->exec("ALTER TABLE prescriptions ADD COLUMN IF NOT EXISTS reason VARCHAR(255) AFTER medicines");
    $db->exec("ALTER TABLE pending_prescriptions ADD COLUMN IF NOT EXISTS reason VARCHAR(255) AFTER medicines");
    
    // Add unique constraint to vital_signs table if not exists
    try {
        $db->exec("ALTER TABLE vital_signs ADD UNIQUE KEY unique_patient_date (patient_id, vital_date)");
    } catch (Exception $e) { 
        // Ignore if constraint already exists
    }
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
} catch (Exception $e) { /* Ignore if columns already exist */
}



// Fetch medicines from DB for dropdown

$medicines = [];
try {
    $medStmt = $db->query('SELECT TRIM(name) as name, SUM(quantity) as total_quantity FROM medicines WHERE quantity > 0 AND (expiry IS NULL OR expiry = "" OR expiry = "0000-00-00" OR expiry > CURDATE()) GROUP BY TRIM(LOWER(REPLACE(REPLACE(name, CHAR(13), ""), CHAR(10), ""))) ORDER BY TRIM(name) ASC');
    $medicines = $medStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Capitalize first letter of each medicine name and remove duplicates
    $processedMedicines = [];
    foreach ($medicines as $med) {
        $cleanName = trim(preg_replace('/\s+/', ' ', $med['name'])); // Remove extra whitespace
        $cleanName = ucfirst(strtolower($cleanName));
        
        if (isset($processedMedicines[$cleanName])) {
            // If medicine already exists, add quantities
            $processedMedicines[$cleanName]['quantity'] += (int)$med['total_quantity'];
        } else {
            // Add new medicine
            $processedMedicines[$cleanName] = [
                'name' => $cleanName,
                'quantity' => (int)$med['total_quantity']
            ];
        }
    }
    
    // Convert back to indexed array
    $medicines = array_values($processedMedicines);
} catch (Exception $e) {
    $medicines = [];
}

// Dynamic suggestions for prescribe modal fields
function getDistinctValues($db, $table, $column)
{
    try {
        $stmt = $db->query("SELECT DISTINCT $column FROM $table WHERE $column IS NOT NULL AND $column != '' LIMIT 50");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return [];
    }
}

$reasonSuggestions = getDistinctValues($db, 'prescriptions', 'reason');
$dosageSuggestions = [];
$qtySuggestions = [];
$frequencySuggestions = [];
$instructionsSuggestions = [];

// Parse medicines field for dosage, qty, frequency, instructions
try {
    $medRows = $db->query('SELECT medicines FROM prescriptions WHERE medicines IS NOT NULL AND medicines != "" LIMIT 100')->fetchAll(PDO::FETCH_COLUMN);
    foreach ($medRows as $row) {
        $meds = json_decode($row, true);
        if (is_array($meds)) {
            foreach ($meds as $med) {
                if (!empty($med['dosage'])) $dosageSuggestions[] = $med['dosage'];
                if (!empty($med['quantity'])) $qtySuggestions[] = $med['quantity'];
                if (!empty($med['frequency'])) $frequencySuggestions[] = $med['frequency'];
                if (!empty($med['instructions'])) $instructionsSuggestions[] = $med['instructions'];
            }
        }
    }
} catch (Exception $e) {
}

$dosageSuggestions = array_unique(array_filter($dosageSuggestions));
$qtySuggestions = array_unique(array_filter($qtySuggestions));
$frequencySuggestions = array_unique(array_filter($frequencySuggestions));
$instructionsSuggestions = array_unique(array_filter($instructionsSuggestions));
?>

<!-- DataTables CSS removed - using custom AJAX-based table -->
<style id="tableFilterBaseStyles">
  .filter-student #visitorSection{display:none !important;}
  .filter-student #facultySection{display:none !important;}
  .filter-visitor #studentSection{display:none !important;}
  .filter-visitor #facultySection{display:none !important;}
  .filter-faculty #studentSection{display:none !important;}
  .filter-faculty #visitorSection{display:none !important;}
  /* .filter-all shows all by default */
  .filter-all #studentSection{display:block;}
  .filter-all #facultySection{display:block;}
  .filter-all #visitorSection{display:block;}
  @media (prefers-color-scheme: dark){
    .filter-all #studentSection{display:block;}
    .filter-all #facultySection{display:block;}
    .filter-all #visitorSection{display:block;}
  }
  
  /* Ensure blocks even if a utility class tries to override */
  #studentSection[hidden], #facultySection[hidden], #visitorSection[hidden]{display:none !important;}
</style>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- DataTables JS removed - using custom AJAX-based table -->

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <!-- Header Section -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Patient Records</h1>
        <p class="text-gray-600">Manage clinic patient information and records.</p>
    </div>

    <!-- Summary Cards Section -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Patients Card -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Patients</p>
                    <p class="text-3xl font-bold text-blue-600" id="totalPatientsCount">0</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-team-line text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Students Card -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Students</p>
                    <p class="text-3xl font-bold text-green-600" id="studentsCount">0</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-add-line text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Faculty Card -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Faculty</p>
                    <p class="text-3xl font-bold text-purple-600" id="facultyCount">0</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-add-line text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Visitors Card -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Visitors</p>
                    <p class="text-3xl font-bold text-orange-600" id="visitorsCount">0</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-add-line text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Patient Records Table Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200">
        <!-- Table Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">Patient Records (<span id="totalRecordsCount">0</span>)</h3>
        <div class="flex items-center gap-2">
                <!-- Search Bar -->
            <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-search-line text-gray-400"></i>
                    </div>
                    <input id="searchInput" type="text" 
                           class="block w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" 
                           placeholder="Search by name, email, or phone...">
                    <button id="clearSearch" type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden">
                    <i class="ri-close-line"></i>
                </button>
            </div>
                <button id="addPatientBtn" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700 flex items-center gap-2">
                    <i class="ri-add-line"></i>
                    Add New Patient
                </button>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="px-6 py-3 border-b border-gray-200">
            <nav class="flex space-x-8">
                <button class="patient-tab active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-tab="all">
                    All Patients (<span id="allCount">0</span>)
        </button>
                <button class="patient-tab px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-tab="students">
                    Students (<span id="studentsTabCount">0</span>)
                </button>
                <button class="patient-tab px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-tab="faculty">
                    Faculty (<span id="facultyTabCount">0</span>)
                </button>
                <button class="patient-tab px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-tab="visitors">
                    Visitors (<span id="visitorsTabCount">0</span>)
                </button>
            </nav>
        </div>

        <!-- Table Content -->
            <div class="overflow-x-auto">
            <table id="importedPatientsTable" class="w-full divide-y divide-gray-200 table-fixed">
                    <thead class="bg-gray-50">
                        <tr>
                        <th class="w-1/3 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                        <th class="w-20 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="w-16 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                        <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="w-24 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                        <?php
                    // Get counts for all patient types
                    $visitor_count = $db->query('SELECT COUNT(*) FROM visitor')->fetchColumn();
                    $faculty_count = $db->query('SELECT COUNT(*) FROM faculty')->fetchColumn();
                    $imported_patients_count = $db->query('SELECT COUNT(*) FROM imported_patients')->fetchColumn();
                    $total_patients = $visitor_count + $faculty_count + $imported_patients_count;
                    
                            // Pagination settings
                            $records_per_page = 10;
                            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                            $page = max($page, 1);
                            $offset = ($page - 1) * $records_per_page;

                            // Get total count for pagination
                            $total_count_stmt = $db->query('SELECT COUNT(*) FROM imported_patients');
                            $total_records = $total_count_stmt->fetchColumn();
                            $total_pages = ceil($total_records / $records_per_page);

                            $stmt = $db->prepare('SELECT id, student_id, name, dob, gender, address, civil_status, year_level, email, contact_number, religion, citizenship, course_program, guardian_name, guardian_contact, emergency_contact_name, emergency_contact_number, parent_email, parent_phone, MAX(dob) as last_visit FROM imported_patients GROUP BY id, student_id, name ORDER BY id DESC LIMIT :limit OFFSET :offset');
                            $stmt->bindValue(':limit', $records_per_page, PDO::PARAM_INT);
                            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
                            $stmt->execute();
                    
                    if ($stmt->rowCount() > 0):
                        foreach ($stmt as $row): 
                            // Calculate age from DOB
                            $age = $row['dob'] ? date_diff(date_create($row['dob']), date_create('today'))->y : 'N/A';
                            $age_text = $age !== 'N/A' ? $age . ' years' : 'N/A';
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                        <span class="text-sm font-medium text-blue-600">
                                            <?php echo strtoupper(substr($row['name'], 0, 2)); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($row['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($row['email'] ?? ''); ?></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Student
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $age_text; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="flex items-center">
                                <i class="ri-phone-line text-gray-400 mr-2"></i>
                                <?php echo htmlspecialchars($row['contact_number'] ?? 'N/A'); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <?php echo $row['last_visit'] ? date('M j, Y', strtotime($row['last_visit'])) : 'No visits'; ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            <div class="flex items-center">
                                <i class="ri-calendar-line text-gray-400 mr-2"></i>
                                Feb 15, 2024
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex items-center space-x-2">
                                <button class="viewBtn text-blue-600 hover:text-blue-900" 
                                    data-name="<?php echo htmlspecialchars($row['name']); ?>" 
                                    data-id="<?php echo htmlspecialchars($row['id']); ?>" 
                                    data-student-id="<?php echo htmlspecialchars($row['student_id']); ?>" 
                                    data-dob="<?php echo htmlspecialchars($row['dob'] ?? ''); ?>"
                                    data-gender="<?php echo htmlspecialchars($row['gender']); ?>" 
                                    data-year="<?php echo htmlspecialchars($row['year_level']); ?>" 
                                    data-address="<?php echo htmlspecialchars($row['address']); ?>" 
                                    data-civil="<?php echo htmlspecialchars($row['civil_status']); ?>"
                                    data-email="<?php echo htmlspecialchars($row['email'] ?? ''); ?>"
                                    data-contact="<?php echo htmlspecialchars($row['contact_number'] ?? ''); ?>"
                                    data-religion="<?php echo htmlspecialchars($row['religion'] ?? ''); ?>"
                                    data-citizenship="<?php echo htmlspecialchars($row['citizenship'] ?? ''); ?>"
                                    data-course="<?php echo htmlspecialchars($row['course_program'] ?? ''); ?>"
                                    data-guardian-name="<?php echo htmlspecialchars($row['guardian_name'] ?? ''); ?>"
                                    data-guardian-contact="<?php echo htmlspecialchars($row['guardian_contact'] ?? ''); ?>"
                                    data-emergency-name="<?php echo htmlspecialchars($row['emergency_contact_name'] ?? ''); ?>"
                                    data-emergency-contact="<?php echo htmlspecialchars($row['emergency_contact_number'] ?? ''); ?>"
                                    data-parent-email="<?php echo htmlspecialchars($row['parent_email'] ?? ''); ?>"
                                    data-parent-phone="<?php echo htmlspecialchars($row['parent_phone'] ?? ''); ?>"
                                    data-type="<?php echo htmlspecialchars($row['table_type'] ?? 'imported_patients'); ?>"
                                    data-age="<?php echo htmlspecialchars($row['age'] ?? ''); ?>"
                                    data-department="<?php echo htmlspecialchars($row['department'] ?? ''); ?>"
                                    data-college-course="<?php echo htmlspecialchars($row['college_course'] ?? ''); ?>"
                                    data-password="<?php echo htmlspecialchars($row['password'] ?? ''); ?>"
                                    data-emergency-contact-faculty="<?php echo htmlspecialchars($row['emergency_contact'] ?? ''); ?>"
                                    data-emergency-contact-visitor="<?php echo htmlspecialchars($row['emergency_contact'] ?? ''); ?>">
                                    <i class="ri-eye-line"></i>
                                </button>
                                <button class="editBtn text-gray-600 hover:text-gray-900" 
                                    data-id="<?php echo htmlspecialchars($row['id']); ?>" 
                                    data-student-id="<?php echo htmlspecialchars($row['student_id']); ?>" 
                                    data-name="<?php echo htmlspecialchars($row['name']); ?>" 
                                    data-dob="<?php echo htmlspecialchars($row['dob'] ?? ''); ?>"
                                    data-gender="<?php echo htmlspecialchars($row['gender']); ?>" 
                                    data-year="<?php echo htmlspecialchars($row['year_level']); ?>" 
                                    data-address="<?php echo htmlspecialchars($row['address']); ?>" 
                                    data-civil="<?php echo htmlspecialchars($row['civil_status']); ?>"
                                    data-email="<?php echo htmlspecialchars($row['email'] ?? ''); ?>"
                                    data-contact="<?php echo htmlspecialchars($row['contact_number'] ?? ''); ?>"
                                    data-religion="<?php echo htmlspecialchars($row['religion'] ?? ''); ?>"
                                    data-citizenship="<?php echo htmlspecialchars($row['citizenship'] ?? ''); ?>"
                                    data-course="<?php echo htmlspecialchars($row['course_program'] ?? ''); ?>"
                                    data-guardian-name="<?php echo htmlspecialchars($row['guardian_name'] ?? ''); ?>"
                                    data-guardian-contact="<?php echo htmlspecialchars($row['guardian_contact'] ?? ''); ?>"
                                    data-emergency-name="<?php echo htmlspecialchars($row['emergency_contact_name'] ?? ''); ?>"
                                    data-emergency-contact="<?php echo htmlspecialchars($row['emergency_contact_number'] ?? ''); ?>"
                                    data-parent-email="<?php echo htmlspecialchars($row['parent_email'] ?? ''); ?>"
                                    data-parent-phone="<?php echo htmlspecialchars($row['parent_phone'] ?? ''); ?>"
                                    data-type="<?php echo htmlspecialchars($row['table_type'] ?? 'imported_patients'); ?>"
                                    data-age="<?php echo htmlspecialchars($row['age'] ?? ''); ?>"
                                    data-department="<?php echo htmlspecialchars($row['department'] ?? ''); ?>"
                                    data-college-course="<?php echo htmlspecialchars($row['college_course'] ?? ''); ?>"
                                    data-password="<?php echo htmlspecialchars($row['password'] ?? ''); ?>"
                                    data-emergency-contact-faculty="<?php echo htmlspecialchars($row['emergency_contact'] ?? ''); ?>"
                                    data-emergency-contact-visitor="<?php echo htmlspecialchars($row['emergency_contact'] ?? ''); ?>"
                                    title="Edit Patient">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button class="deleteBtn text-red-600 hover:text-red-900" 
                                    data-id="<?php echo htmlspecialchars($row['id']); ?>" 
                                    data-name="<?php echo htmlspecialchars($row['name']); ?>" 
                                    data-student-id="<?php echo htmlspecialchars($row['student_id']); ?>"
                                    title="Delete Patient">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                            </td>
                        </tr>
                    <?php 
                        endforeach; 
                    else: ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="ri-user-line text-4xl text-gray-300 mb-4"></i>
                                <p class="text-gray-500 text-lg font-medium">No patients found</p>
                                <p class="text-gray-400 text-sm">Try adjusting your search terms or filters</p>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        
        <!-- Pagination Controls -->
        <div id="paginationControls" class="px-6 py-4 border-t border-gray-200 bg-gray-50" style="display: none;">
            <div class="flex justify-between items-center">
                    <!-- Records Information -->
                <div id="recordsInfo" class="text-sm text-gray-600">
                    <!-- Will be populated by JavaScript -->
                    </div>

                    <!-- Pagination Navigation -->
                <nav id="paginationNav" class="flex justify-end items-center -space-x-px" aria-label="Pagination">
                    <!-- Will be populated by JavaScript -->
                    </nav>
                </div>
        </div>

        <!-- Pagination and Records Info -->
        

    <!-- Faculty Records Table - Hidden, using unified table with tabs -->
    <div id="facultySection" class="bg-white rounded shadow p-6 mt-6" style="display: none;">
        <h3 class="text-lg font-semibold mb-4">Faculty Records</h3>
            <div class="overflow-x-auto">
                <table id="facultyTable" class="w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-1/5 px-4 py-2 text-left font-semibold text-gray-600">Full Name</th>
                            <th class="w-1/5 px-4 py-2 text-left font-semibold text-gray-600">Department</th>
                            <th class="w-1/5 px-4 py-2 text-left font-semibold text-gray-600">Gender</th>
                            <th class="w-1/5 px-4 py-2 text-left font-semibold text-gray-600">Email</th>
                            <th class="w-1/5 px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Pagination settings for faculty
                        $faculty_records_per_page = 10;
                        $faculty_page = isset($_GET['faculty_page']) ? (int)$_GET['faculty_page'] : 1;
                        $faculty_page = max($faculty_page, 1);
                        $faculty_offset = ($faculty_page - 1) * $faculty_records_per_page;

                        // Get total count for faculty pagination
                        try {
                            $faculty_total_count_stmt = $db->query('SELECT COUNT(*) FROM faculty');
                            $faculty_total_records = $faculty_total_count_stmt->fetchColumn();
                        } catch (Exception $e) {
                            $faculty_total_records = 0;
                        }
                        $faculty_total_pages = ceil($faculty_total_records / $faculty_records_per_page);

                        try {
                            $faculty_stmt = $db->prepare('SELECT * FROM faculty ORDER BY faculty_id DESC LIMIT :limit OFFSET :offset');
                            $faculty_stmt->bindValue(':limit', $faculty_records_per_page, PDO::PARAM_INT);
                            $faculty_stmt->bindValue(':offset', $faculty_offset, PDO::PARAM_INT);
                            $faculty_stmt->execute();
                            foreach ($faculty_stmt as $faculty): ?>
                        <tr>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($faculty['full_name']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($faculty['department']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($faculty['gender'] ?? ''); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($faculty['email'] ?? ''); ?></td>
                            <td class="px-4 py-2 text-center">
                                <button class="viewFacultyBtn px-3 py-1 text-xs bg-primary text-white rounded hover:bg-primary/90" 
                                    data-faculty-id="<?php echo htmlspecialchars($faculty['faculty_id']); ?>" 
                                    data-full-name="<?php echo htmlspecialchars($faculty['full_name']); ?>" 
                                    data-address="<?php echo htmlspecialchars($faculty['address']); ?>"
                                    data-contact="<?php echo htmlspecialchars($faculty['contact']); ?>"
                                    data-emergency-contact="<?php echo htmlspecialchars($faculty['emergency_contact']); ?>"
                                    data-age="<?php echo htmlspecialchars($faculty['age']); ?>"
                                    data-department="<?php echo htmlspecialchars($faculty['department']); ?>"
                                    data-college-course="<?php echo htmlspecialchars($faculty['college_course'] ?? ''); ?>"
                                    data-gender="<?php echo htmlspecialchars($faculty['gender']); ?>"
                                    data-email="<?php echo htmlspecialchars($faculty['email']); ?>"
                                    data-civil-status="<?php echo htmlspecialchars($faculty['civil_status']); ?>"
                                    data-citizenship="<?php echo htmlspecialchars($faculty['citizenship']); ?>">View</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php } catch (Exception $e) { ?>
                            <tr>
                                <td colspan="4" class="px-4 py-2 text-center text-gray-500">
                                    No faculty records found. Add your first faculty member using the form above.
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
                <?php if ($faculty_total_records == 0): ?>
                    <div class="text-center py-8 text-gray-500">
                        <p>No faculty records found.</p>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Faculty Pagination and Records Info -->
            <?php if ($faculty_total_records > 0): ?>
            <div class="flex justify-between items-center mt-6">
                <!-- Records Information -->
                <div class="text-sm text-gray-600">
                    <?php 
                    $faculty_start = $faculty_offset + 1;
                    $faculty_end = min($faculty_offset + $faculty_records_per_page, $faculty_total_records);
                    ?>
                    Showing <?php echo $faculty_start; ?> to <?php echo $faculty_end; ?> of <?php echo $faculty_total_records; ?> entries
                </div>

                <!-- Pagination Navigation -->
                <?php if ($faculty_total_pages > 1): ?>
                <nav class="flex justify-end items-center -space-x-px" aria-label="Faculty Pagination">
                    <!-- Previous Button -->
                    <?php if ($faculty_page > 1): ?>
                        <a href="?page=<?php echo isset($page) ? $page : 1; ?>&visitor_page=<?php echo isset($visitor_page) ? $visitor_page : 1; ?>&faculty_page=<?php echo $faculty_page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"></path>
                            </svg>
                            <span class="sr-only">Previous</span>
                        </a>
                    <?php else: ?>
                        <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Previous">
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"></path>
                            </svg>
                            <span class="sr-only">Previous</span>
                        </button>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php
                    $faculty_start_page = max(1, $faculty_page - 2);
                    $faculty_end_page = min($faculty_total_pages, $faculty_page + 2);
                    
                    for ($i = $faculty_start_page; $i <= $faculty_end_page; $i++): ?>
                        <?php if ($i == $faculty_page): ?>
                            <button type="button" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-primary text-white" aria-current="page"><?php echo $i; ?></button>
                        <?php else: ?>
                            <a href="?page=<?php echo isset($page) ? $page : 1; ?>&visitor_page=<?php echo isset($visitor_page) ? $visitor_page : 1; ?>&faculty_page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <!-- Next Button -->
                    <?php if ($faculty_page < $faculty_total_pages): ?>
                        <a href="?page=<?php echo isset($page) ? $page : 1; ?>&visitor_page=<?php echo isset($visitor_page) ? $visitor_page : 1; ?>&faculty_page=<?php echo $faculty_page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
                            <span class="sr-only">Next</span>
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </a>
                    <?php else: ?>
                        <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Next">
                            <span class="sr-only">Next</span>
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                    <?php endif; ?>
                </nav>
                <?php endif; ?>
            </div>
            <?php endif; ?>
    </div>

    <!-- Visitor Records Table - Hidden, using unified table with tabs -->
    <div id="visitorSection" class="bg-white rounded shadow p-6 mt-6 relative z-10" style="display: none;">
        <h3 class="text-xl font-bold text-gray-800 mb-4">Visitor Records</h3>
        <div class="overflow-x-auto">
            <table id="visitorTable" class="w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1/5 px-4 py-2 text-left font-semibold text-gray-600">Full Name</th>
                        <th class="w-1/5 px-4 py-2 text-left font-semibold text-gray-600">Gender</th>
                        <th class="w-1/5 px-4 py-2 text-left font-semibold text-gray-600">Contact</th>
                        <th class="w-1/5 px-4 py-2 text-left font-semibold text-gray-600">Emergency Contact</th>
                        <th class="w-1/5 px-4 py-2 text-center font-semibold text-gray-600">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // Pagination settings for visitors
                    $visitor_records_per_page = 10;
                    $visitor_page = isset($_GET['visitor_page']) ? (int)$_GET['visitor_page'] : 1;
                    $visitor_page = max($visitor_page, 1);
                    $visitor_offset = ($visitor_page - 1) * $visitor_records_per_page;

                    // Get total count for visitor pagination
                    $visitor_total_count_stmt = $db->query('SELECT COUNT(*) FROM visitor');
                    $visitor_total_records = $visitor_total_count_stmt->fetchColumn();
                    $visitor_total_pages = ceil($visitor_total_records / $visitor_records_per_page);

                    $visitor_stmt = $db->prepare('SELECT * FROM visitor ORDER BY visitor_id DESC LIMIT :limit OFFSET :offset');
                    $visitor_stmt->bindValue(':limit', $visitor_records_per_page, PDO::PARAM_INT);
                    $visitor_stmt->bindValue(':offset', $visitor_offset, PDO::PARAM_INT);
                    $visitor_stmt->execute();
                    foreach ($visitor_stmt as $visitor): ?>
                        <tr>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($visitor['full_name']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($visitor['gender']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($visitor['contact']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($visitor['emergency_contact'] ?? ''); ?></td>
                            <td class="px-4 py-2 text-center">
                                <button class="viewVisitorBtn px-3 py-1 text-xs bg-primary text-white rounded hover:bg-primary/90"
                                    data-id="<?php echo htmlspecialchars($visitor['visitor_id']); ?>"
                                    data-name="<?php echo htmlspecialchars($visitor['full_name']); ?>"
                                    data-age="<?php echo htmlspecialchars($visitor['age']); ?>"
                                    data-gender="<?php echo htmlspecialchars($visitor['gender']); ?>"
                                    data-address="<?php echo htmlspecialchars($visitor['address']); ?>"
                                    data-contact="<?php echo htmlspecialchars($visitor['contact']); ?>"
                                    data-emergency-contact="<?php echo htmlspecialchars($visitor['emergency_contact'] ?? ''); ?>">View</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php if ($visitor_total_records == 0): ?>
                <div class="text-center py-8 text-gray-500">
                    <p>No visitor records found.</p>
    </div>
            <?php endif; ?>
        </div>

        <!-- Visitor Pagination and Records Info -->
        <?php if ($visitor_total_records > 0): ?>
            <div class="flex justify-between items-center mt-6">
                <!-- Records Information -->
                <div class="text-sm text-gray-600">
                    <?php
                    $visitor_start = $visitor_offset + 1;
                    $visitor_end = min($visitor_offset + $visitor_records_per_page, $visitor_total_records);
                    ?>
                    Showing <?php echo $visitor_start; ?> to <?php echo $visitor_end; ?> of <?php echo $visitor_total_records; ?> entries
                </div>

                <!-- Pagination Navigation -->
                <?php if ($visitor_total_pages > 1): ?>
                    <nav class="flex justify-end items-center -space-x-px" aria-label="Visitor Pagination">
                        <!-- Previous Button -->
                        <?php if ($visitor_page > 1): ?>
                            <a href="?page=<?php echo isset($page) ? $page : 1; ?>&visitor_page=<?php echo $visitor_page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m15 18-6-6 6-6"></path>
                                </svg>
                                <span class="sr-only">Previous</span>
                            </a>
                        <?php else: ?>
                            <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Previous">
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m15 18-6-6 6-6"></path>
                                </svg>
                                <span class="sr-only">Previous</span>
                            </button>
                        <?php endif; ?>

                        <!-- Page Numbers -->
                        <?php
                        $visitor_start_page = max(1, $visitor_page - 2);
                        $visitor_end_page = min($visitor_total_pages, $visitor_page + 2);
                        // Show first page if not in range
                        if ($visitor_start_page > 1): ?>
                            <a href="?page=<?php echo isset($page) ? $page : 1; ?>&visitor_page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">1</a>
                            <?php if ($visitor_start_page > 2): ?>
                                <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $visitor_start_page; $i <= $visitor_end_page; $i++): ?>
                            <?php if ($i == $visitor_page): ?>
                                <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-200 text-gray-800 border border-gray-200 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-300" aria-current="page"><?php echo $i; ?></button>
                            <?php else: ?>
                                <a href="?page=<?php echo isset($page) ? $page : 1; ?>&visitor_page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Show last page if not in range -->
                        <?php if ($visitor_end_page < $visitor_total_pages): ?>
                            <?php if ($visitor_end_page < $visitor_total_pages - 1): ?>
                                <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                            <?php endif; ?>
                            <a href="?page=<?php echo isset($page) ? $page : 1; ?>&visitor_page=<?php echo $visitor_total_pages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100"><?php echo $visitor_total_pages; ?></a>
                        <?php endif; ?>

                        <!-- Next Button -->
                        <?php if ($visitor_page < $visitor_total_pages): ?>
                            <a href="?page=<?php echo isset($page) ? $page : 1; ?>&visitor_page=<?php echo $visitor_page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
                                <span class="sr-only">Next</span>
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6"></path>
                                </svg>
                            </a>
                        <?php else: ?>
                            <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Next">
                                <span class="sr-only">Next</span>
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6"></path>
                                </svg>
                            </button>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Visitor Prescribe Medicine Modal (mirrors patient prescribe modal) -->
    <div id="visitorPrescribeMedModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-[60] hidden">
        <div class="w-full max-w-5xl h-[85vh] mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="font-bold text-lg text-gray-800 dark:text-white">Prescribe Medicine</h3>
                <button id="closeVisitorPrescribeMedModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <form id="visitorPrescribeMedForm">
                    <div id="visitorMedsList">
                        <div class="medRow mb-4 border-b pb-4">
                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                                <select class="medicineSelect w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    <option value="">Select medicine</option>
                                    <?php foreach ($medicines as $med): ?>
                                        <option value="<?php echo htmlspecialchars($med['name']); ?>" data-stock="<?php echo htmlspecialchars($med['quantity']); ?>">
                                            <?php echo htmlspecialchars($med['name']); ?> (<?php echo htmlspecialchars($med['quantity']); ?> in stock)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="flex gap-2 mb-2">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                                    <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 500mg" list="dosageSuggestions" />
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                    <input type="number" class="w-full border border-gray-300 rounded px-3 py-2 text-sm qtyInput" min="1" list="qtySuggestions" />
                                    <span class="text-xs text-gray-500 stockMsg"></span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                                <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 3x a day" list="frequencySuggestions" />
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                                <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. After meals" list="instructionsSuggestions" />
                            </div>
                            <button type="button" class="removeMedBtn text-xs text-red-500 hover:underline mt-1">Remove</button>
                        </div>
                    </div>
                    <button type="button" id="visitorAddMedRowBtn" class="mb-4 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">+ Add Another Medicine</button>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                        <textarea class="w-full border border-gray-300 rounded px-3 py-2 text-sm" rows="2" placeholder="Additional info..."></textarea>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Prescription</label>
                        <input type="text" name="reason" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. Fever, Headache, Cough, etc." required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Recipient Email Address</label>
                        <input type="email" name="recipient_email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter recipient's email address" />
                    </div>
                    <div class="flex justify-center">
                        <button type="submit" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 focus:outline-hidden focus:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none min-w-[200px]">Submit Prescription</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- Profile Modal -->
    <div id="studentModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-5xl h-[85vh] mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200 dark:border-neutral-700">
                <h3 id="modalPatientName" class="font-bold text-lg text-gray-800 dark:text-white">Patient Profile</h3>
                <button id="closeStudentModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <!-- Navigation Tabs -->
                <div class="flex justify-start space-x-3 mb-6" id="studentModalTabs">
                    <button class="tabBtn px-4 py-2 text-sm rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-primary/10 dark:bg-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-500" data-tab="infoTab">Information</button>
                    <button class="tabBtn px-4 py-2 text-sm rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-primary/10 dark:bg-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-500" data-tab="vitalsTab">Vital Signs</button>
                    <button class="tabBtn px-4 py-2 text-sm rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-primary/10 dark:bg-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-500" data-tab="medReferralTab">Medication Referral</button>
                </div>
                
                <!-- Tab Contents -->
                <div id="infoTab" class="tabContent">
                    <div id="modalPatientDetails" class="text-base text-gray-700 dark:text-neutral-300 mb-6">
                        <!-- Patient details will be shown here -->
                    </div>
                </div>
                
                <div id="vitalsTab" class="tabContent hidden modal-scroll-area">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Patient Vital Signs</h4>
                    <form id="vitalsForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Date</label>
                            <input type="date" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="vital_date" required />
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Weight (kg)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="weight" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Height (cm)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="height" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Body Temp (°C)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="body_temp" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Respiratory Rate</label>
                                <input type="number" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="resp_rate" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Pulse</label>
                                <input type="number" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="pulse" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Blood Pressure</label>
                                <input type="text" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="blood_pressure" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Oxygen Saturation (%)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="oxygen_sat" />
                            </div>
                            <div class="flex-1"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Remarks</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="remarks" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                
                <div id="medReferralTab" class="tabContent hidden modal-scroll-area">
                    <!-- Patient's Medication Referral History -->
                    <div id="medReferralHistory" class="mb-4">
                        
                        <div id="medReferralHistoryContent" class="text-xs text-gray-600 dark:text-neutral-400 mb-4">
                            <p class="text-center text-gray-400 dark:text-neutral-500">Loading medication referral history...</p>
                        </div>
                    </div>
                    
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Record New Medication Referral</h4>
                    <form id="medReferralForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Subjective</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="subjective" rows="2" placeholder="Patient's complaints and symptoms"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Objective</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="objective" rows="2" placeholder="Observable signs and measurements"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Assessment</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="assessment" rows="2" placeholder="Clinical judgment and diagnosis"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Plan</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="plan" rows="2" placeholder="Treatment plan and recommendations"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Intervention</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="intervention" rows="2" placeholder="Actions taken during the visit"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Evaluation</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="evaluation" rows="2" placeholder="Outcome assessment and follow-up needed"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Referral To</label>
                            <input type="text" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="referral_to" placeholder="Referral destination (e.g., Dental Clinic, ER, Specialist)" />
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700">
                <button id="prescribeMedBtn" type="button" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none min-w-[200px]">
                    <i class="ri-capsule-line"></i>
                    Prescribe Medicine
                </button>
                <button id="saveVitalsBtn" type="button" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none min-w-[200px] hidden">
                    Save Vital Signs
                </button>
                <button id="saveMedReferralBtn" type="button" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none min-w-[200px] hidden">
                    Save Medication Referral
                </button>
            </div>
        </div>
    </div>
    <!-- Prescribe Medicine Modal -->
    <div id="prescribeMedModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-[60] hidden">
        <div class="w-full max-w-5xl h-[85vh] mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="font-bold text-lg text-gray-800 dark:text-white">Prescribe Medicine</h3>
                <button id="closePrescribeMedModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
            <form id="prescribeMedForm">
                <div id="medsList">
                    <div class="medRow mb-4 border-b pb-4">
                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                            <select class="medicineSelect w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">Select medicine</option>
                                <?php foreach ($medicines as $med): ?>
                                    <option value="<?php echo htmlspecialchars($med['name']); ?>" data-stock="<?php echo htmlspecialchars($med['quantity']); ?>">
                                        <?php echo htmlspecialchars($med['name']); ?> (<?php echo htmlspecialchars($med['quantity']); ?> in stock)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="flex gap-2 mb-2">
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                                <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 500mg" list="dosageSuggestions" />
                                <datalist id="dosageSuggestions" style="max-height:120px;overflow-y:auto;">
                                    <?php $limitedDosage = array_slice($dosageSuggestions, 0, 5); ?>
                                    <?php foreach ($limitedDosage as $dosage): ?>
                                        <option value="<?php echo htmlspecialchars($dosage); ?>" />
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                <input type="number" class="w-full border border-gray-300 rounded px-3 py-2 text-sm qtyInput" min="1" list="qtySuggestions" />
                                <datalist id="qtySuggestions" style="max-height:120px;overflow-y:auto;">
                                    <?php $limitedQty = array_slice($qtySuggestions, 0, 5); ?>
                                    <?php foreach ($limitedQty as $qty): ?>
                                        <option value="<?php echo htmlspecialchars($qty); ?>" />
                                    <?php endforeach; ?>
                                </datalist>
                                <span class="text-xs text-gray-500 stockMsg"></span>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                            <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 3x a day" list="frequencySuggestions" />
                            <datalist id="frequencySuggestions" style="max-height:120px;overflow-y:auto;">
                                <?php $limitedFreq = array_slice($frequencySuggestions, 0, 5); ?>
                                <?php foreach ($limitedFreq as $freq): ?>
                                    <option value="<?php echo htmlspecialchars($freq); ?>" />
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="mb-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                            <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. After meals" list="instructionsSuggestions" />
                            <datalist id="instructionsSuggestions" style="max-height:120px;overflow-y:auto;">
                                <?php $limitedInst = array_slice($instructionsSuggestions, 0, 5); ?>
                                <?php foreach ($limitedInst as $inst): ?>
                                    <option value="<?php echo htmlspecialchars($inst); ?>" />
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <button type="button" class="removeMedBtn text-xs text-red-500 hover:underline mt-1">Remove</button>
                    </div>
                </div>
                <button type="button" id="addMedRowBtn" class="mb-4 px-3 py-1 bg-blue-500 text-white rounded hover:bg-blue-600">+ Add Another Medicine</button>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                    <textarea class="w-full border border-gray-300 rounded px-3 py-2 text-sm" rows="2" placeholder="Additional info..."></textarea>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason for Prescription</label>
                    <input type="text" name="reason" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. Fever, Headache, Cough, etc." required list="reasonSuggestions" />
                    <datalist id="reasonSuggestions" style="max-height:120px;overflow-y:auto;">
                        <?php $limitedReason = array_slice($reasonSuggestions, 0, 5); ?>
                        <?php foreach ($limitedReason as $reason): ?>
                            <option value="<?php echo htmlspecialchars($reason); ?>" />
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Patient Email Address</label>
                    <input type="email" name="patient_email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter patient's email address" required list="patientEmailSuggestions" />
                    <datalist id="patientEmailSuggestions" style="max-height:120px;overflow-y:auto;">
                        <?php 
                        $allPatientEmails = getDistinctValues($db, 'imported_patients', 'email');
                        $limitedPatientEmails = array_slice($allPatientEmails, 0, 5);
                        foreach ($limitedPatientEmails as $email): ?>
                            <option value="<?php echo htmlspecialchars($email); ?>" />
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parent's Email Address</label>
                    <input type="email" name="parent_email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter parent's email address" required list="parentEmailSuggestions" />
                    <datalist id="parentEmailSuggestions" style="max-height:120px;overflow-y:auto;">
                        <?php 
                        $allParentEmails = getDistinctValues($db, 'imported_patients', 'parent_email');
                        $limitedParentEmails = array_slice($allParentEmails, 0, 5);
                        foreach ($limitedParentEmails as $email): ?>
                            <option value="<?php echo htmlspecialchars($email); ?>" />
                        <?php endforeach; ?>
                    </datalist>
                </div>
                <div class="flex justify-center">
                    <button type="submit" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 focus:outline-hidden focus:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none min-w-[200px]">Submit Prescription</button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <!-- Add New Entry Type Modal (Student or Visitor) -->
    <div id="addEntityTypeModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-md mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="font-bold text-lg text-gray-800 dark:text-white">Add New</h3>
                <button id="closeAddEntityTypeModalBtn" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6">
                <p class="text-sm text-gray-600 dark:text-neutral-300 mb-4">Choose the type of record to add:</p>
                <div class="grid grid-cols-1 gap-3">
                    <button id="chooseStudentBtn" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Student</button>
                    <button id="chooseVisitorBtn" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Visitor</button>
                    <button id="chooseFacultyBtn" class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Faculty</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add New Faculty Modal -->
    <div id="addFacultyModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-5xl h-[85vh] mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="font-bold text-lg text-gray-800 dark:text-white">Add New Faculty Patient</h3>
                <button id="closeAddFacultyModalBtn" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"></path><path d="m6 6 12 12"></path></svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <form id="addFacultyForm" class="space-y-4" autocomplete="off" novalidate>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="full_name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter full name" required />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                            <input type="email" name="email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter email address" required />
                        </div>
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                            <input type="password" name="password" id="add_faculty_password" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary pr-10" placeholder="Enter password" required />
                            <span class="absolute right-3 top-9 cursor-pointer" onclick="togglePatientPassword('add_faculty_password', this)">
                                <i class="ri-eye-off-line" id="add_faculty_password_icon"></i>
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address *</label>
                        <input type="text" name="address" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter complete address" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact *</label>
                        <input type="text" name="contact" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter contact number" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact *</label>
                        <input type="text" name="emergency_contact" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter emergency contact number" required />
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Age *</label>
                            <input type="number" name="age" min="1" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter age" required />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                            <select name="gender" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                            <select name="department" id="facultyDepartmentSelect" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                                <option value="">Select Department</option>
                                <option value="JHS">JHS</option>
                                <option value="SHS">SHS</option>
                                <option value="College">College</option>
                            </select>
                        </div>
                        <div id="facultyCourseDiv" style="display:none;">
                            <label class="block text-sm font-medium text-gray-700 mb-1">College Course *</label>
                            <select name="college_course" id="facultyCourseSelect" class="w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                <option value="">Select Course</option>
                                <option value="BSIT">BSIT</option>
                                <option value="BSBA">BSBA</option>
                                <option value="BEED">BEED</option>
                                <option value="BSED">BSED</option>
                                <option value="BSHTM">BSHTM</option>
                                <option value="BSCRIM">BSCRIM</option>
                                <option value="BSN">BSN</option>
                            </select>
                        </div>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Civil Status *</label>
                            <select name="civil_status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                                <option value="">Select Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Divorced">Divorced</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Citizenship *</label>
                            <input type="text" name="citizenship" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter citizenship" required />
                        </div>
                    </div>
                </form>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700">
                <button type="submit" form="addFacultyForm" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 focus:outline-hidden focus:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none min-w-[200px]">Add Faculty</button>
            </div>
            </div>
        </div>
    </div>

    <!-- Add New Student Patient Modal -->
    <div id="addPatientModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-5xl h-[85vh] mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="font-bold text-lg text-gray-800 dark:text-white">Add New Student Patient</h3>
                <button id="closeAddPatientModalBtn" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
            <form id="addPatientForm" class="space-y-4" autocomplete="off" novalidate>
                <input type="hidden" name="add_patient" value="1">

                <!-- Personal Information Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Student ID *</label>
                        <input type="text" name="student_id" id="student_id_input" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="SCC-00-0000000" required />
                        <div id="student_id_validation" class="mt-1 text-xs"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter full name" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                        <input type="date" name="dob" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                        <select name="gender" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" name="email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter email address" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                        <input type="tel" name="contact_number" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter contact number" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parent's Email</label>
                        <input type="email" name="parent_email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter parent's email" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parent's Phone</label>
                        <input type="tel" name="parent_phone" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter parent's phone number" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                        <input type="text" name="religion" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter religion" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Citizenship</label>
                        <input type="text" name="citizenship" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter citizenship" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Course/Program</label>
                        <input type="text" name="course_program" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter course or program" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Civil Status</label>
                        <select name="civil_status" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Select Civil Status</option>
                            <option value="Single">Single</option>
                            <option value="Married">Married</option>
                            <option value="Widowed">Widowed</option>
                            <option value="Divorced">Divorced</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                        <select name="year_level" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                            <option value="">Select Year Level</option>
                            <option value="1st Year">1st Year</option>
                            <option value="2nd Year">2nd Year</option>
                            <option value="3rd Year">3rd Year</option>
                            <option value="4th Year">4th Year</option>
                            <option value="5th Year">5th Year</option>
                        </select>
                    </div>
                    <div class="relative">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                        <input type="password" name="password" id="add_patient_password" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary pr-10" required />
                        <span class="absolute right-3 top-9 cursor-pointer" onclick="togglePatientPassword('add_patient_password', this)">
                            <i class="ri-eye-off-line" id="add_patient_password_icon"></i>
                        </span>
                    </div>
                </div>

                <!-- Address Section -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" rows="2" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter complete address"></textarea>
                </div>

                <!-- Guardian Information Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Name</label>
                        <input type="text" name="guardian_name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter guardian's name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Contact</label>
                        <input type="tel" name="guardian_contact" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter guardian's contact number" />
                    </div>
                </div>

                <!-- Emergency Contact Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Name</label>
                        <input type="text" name="emergency_contact_name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter emergency contact name" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Number</label>
                        <input type="tel" name="emergency_contact_number" class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary" placeholder="Enter emergency contact number" />
                    </div>
                </div>

            </form>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700">
                <button type="submit" form="addPatientForm" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 focus:outline-hidden focus:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none min-w-[200px]">Add Patient</button>
            </div>
        </div>
    </div>

    <!-- Add New Visitor Patient Modal -->
    <div id="addVisitorModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-5xl h-[85vh] mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200 dark:border-neutral-700">
                <h3 class="font-bold text-lg text-gray-800 dark:text-white">Add New Visitor Patient</h3>
                <button id="closeAddVisitorModalBtn" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
            <form id="addVisitorForm" class="space-y-4" autocomplete="off" novalidate>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" name="full_name" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter full name" required />
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Age *</label>
                        <input type="number" name="age" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter age" required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                        <select name="gender" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                            <option value="">Select Gender</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                    <textarea name="address" rows="2" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter complete address"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Contact *</label>
                    <input type="text" name="contact" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter contact number" required />
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact</label>
                    <input type="text" name="emergency_contact" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Emergency contact number" />
                </div>
            </form>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700">
                <button type="submit" form="addVisitorForm" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-primary text-white hover:bg-primary/90 focus:outline-hidden focus:bg-primary/90 disabled:opacity-50 disabled:pointer-events-none min-w-[200px]">Add Patient</button>
            </div>
        </div>
    </div>

    <!-- Visitor Details Modal -->
    <div id="visitorProfileModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-5xl h-[85vh] mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200 dark:border-neutral-700">
                <h3 id="visitorModalTitle" class="font-bold text-lg text-gray-800 dark:text-white">Visitor Profile</h3>
                <button id="closeVisitorProfileModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            <div class="p-6 overflow-y-auto flex-1">
                <!-- Navigation Tabs (matching student modal) -->
                <div class="flex justify-start space-x-3 mb-6" id="visitorProfileModalTabs">
                    <button class="tabBtnVisitor px-4 py-2 text-sm rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-primary/10 dark:bg-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-500" data-tab="visitorInfoTab">Information</button>
                    <button class="tabBtnVisitor px-4 py-2 text-sm rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-primary/10 dark:bg-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-500" data-tab="visitorVitalsTab">Vital Signs</button>
                    <button class="tabBtnVisitor px-4 py-2 text-sm rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-primary/10 dark:bg-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-500" data-tab="visitorMedReferralTab">Medication Referral</button>
                </div>
                <!-- Tab Contents -->
                <div id="visitorInfoTab" class="tabContent">
                    <div id="visitorModalDetails" class="text-base text-gray-700 dark:text-neutral-300 mb-6">
                        <!-- Populated by JS -->
                    </div>
                </div>
                <div id="visitorVitalsTab" class="tabContent hidden modal-scroll-area">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Visitor Vital Signs</h4>
                    <form id="visitorVitalsForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Date</label>
                            <input type="date" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="vital_date" required />
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Weight (kg)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="weight" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Height (cm)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="height" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Body Temp (°C)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="body_temp" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Respiratory Rate</label>
                                <input type="number" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="resp_rate" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Pulse</label>
                                <input type="number" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="pulse" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Blood Pressure</label>
                                <input type="text" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="blood_pressure" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Oxygen Saturation (%)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="oxygen_sat" />
                            </div>
                            <div class="flex-1"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Remarks</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="remarks" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div id="visitorMedReferralTab" class="tabContent hidden modal-scroll-area">
                    <div id="visitorMedReferralHistory" class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Previous Medication Referrals</h4>
                        <div id="visitorMedReferralHistoryContent" class="text-xs text-gray-600 dark:text-neutral-400 mb-4">
                            <p class="text-center text-gray-400 dark:text-neutral-500">Loading medication referral history...</p>
                        </div>
                    </div>
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Record New Medication Referral</h4>
                    <form id="visitorMedReferralForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Subjective</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="subjective" rows="2" placeholder="Patient's complaints and symptoms"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Objective</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="objective" rows="2" placeholder="Observable signs and measurements"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Assessment</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="assessment" rows="2" placeholder="Clinical judgment and diagnosis"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Plan</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="plan" rows="2" placeholder="Treatment plan and recommendations"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Intervention</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="intervention" rows="2" placeholder="Actions taken during the visit"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Evaluation</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="evaluation" rows="2" placeholder="Outcome assessment and follow-up needed"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Referral To</label>
                            <input type="text" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="referral_to" placeholder="Referral destination (e.g., Dental Clinic, ER, Specialist)" />
                        </div>
                    </form>

                </div>
            </div>
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700">
                <button id="visitorPrescribeMedBtn" type="button" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none min-w-[200px]">
                    <i class="ri-capsule-line"></i>
                    Prescribe Medicine
                </button>
                <button id="saveVisitorVitalsBtn" type="button" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none min-w-[200px] hidden">
                    Save Vital Signs
                </button>
                <button id="saveVisitorMedReferralBtn" type="button" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none min-w-[200px] hidden">
                    Save Medication Referral
                </button>
            </div>
        </div>
    </div>

    <!-- Faculty Profile Modal -->
    <div id="facultyProfileModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-5xl h-[85vh] mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200 dark:border-neutral-700">
                <h3 id="facultyModalTitle" class="font-bold text-lg text-gray-800 dark:text-white">Faculty Profile</h3>
                <button id="closeFacultyProfileModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <!-- Navigation Tabs -->
                <div class="flex justify-start space-x-3 mb-6" id="facultyProfileModalTabs">
                    <button class="tabBtnFaculty px-4 py-2 text-sm rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-primary/10 dark:bg-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-500" data-tab="facultyInfoTab">Information</button>
                    <button class="tabBtnFaculty px-4 py-2 text-sm rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-primary/10 dark:bg-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-500" data-tab="facultyVitalsTab">Vital Signs</button>
                    <button class="tabBtnFaculty px-4 py-2 text-sm rounded-lg font-semibold text-gray-700 bg-gray-200 hover:bg-primary/10 dark:bg-neutral-600 dark:text-neutral-300 dark:hover:bg-neutral-500" data-tab="facultyMedReferralTab">Medication Referral</button>
                </div>
                
                <!-- Tab Contents -->
                <div id="facultyInfoTab" class="tabContent">
                    <div id="facultyModalDetails" class="text-base text-gray-700 dark:text-neutral-300 mb-6">
                        <!-- Faculty details will be shown here -->
                    </div>
                </div>
                
                <div id="facultyVitalsTab" class="tabContent hidden modal-scroll-area">
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Faculty Vital Signs</h4>
                    <form id="facultyVitalsForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Date</label>
                            <input type="date" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="vital_date" required />
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Weight (kg)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="weight" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Height (cm)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="height" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Body Temp (°C)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="body_temp" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Respiratory Rate</label>
                                <input type="number" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="resp_rate" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Pulse</label>
                                <input type="number" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="pulse" />
                            </div>
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Blood Pressure</label>
                                <input type="text" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="blood_pressure" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <div class="flex-1">
                                <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Oxygen Saturation (%)</label>
                                <input type="number" step="0.01" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="oxygen_sat" />
                            </div>
                            <div class="flex-1"></div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Remarks</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="remarks" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                
                <div id="facultyMedReferralTab" class="tabContent hidden modal-scroll-area">
                    <!-- Faculty's Medication Referral History -->
                    <div id="facultyMedReferralHistory" class="mb-4">
                        <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Previous Medication Referrals</h4>
                        <div id="facultyMedReferralHistoryContent" class="text-xs text-gray-600 dark:text-neutral-400 mb-4">
                            <p class="text-center text-gray-400 dark:text-neutral-500">Loading medication referral history...</p>
                        </div>
                    </div>
                    
                    <h4 class="text-sm font-semibold text-gray-800 dark:text-white mb-2">Record New Medication Referral</h4>
                    <form id="facultyMedReferralForm" class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Subjective</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="subjective" rows="2" placeholder="Faculty's complaints and symptoms"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Objective</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="objective" rows="2" placeholder="Observable signs and measurements"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Assessment</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="assessment" rows="2" placeholder="Clinical judgment and diagnosis"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Plan</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="plan" rows="2" placeholder="Treatment plan and recommendations"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Intervention</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="intervention" rows="2" placeholder="Actions taken during the visit"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Evaluation</label>
                            <textarea class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="evaluation" rows="2" placeholder="Outcome assessment and follow-up needed"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-neutral-300">Referral To</label>
                            <input type="text" class="w-full border border-gray-300 dark:border-neutral-600 rounded px-3 py-2 text-sm dark:bg-neutral-700 dark:text-white" name="referral_to" placeholder="Referral destination (e.g., Dental Clinic, ER, Specialist)" />
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700">
                <button id="facultyPrescribeMedBtn" type="button" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none min-w-[200px]">
                    <i class="ri-capsule-line"></i>
                    Prescribe Medicine
                </button>
                <button id="saveFacultyVitalsBtn" type="button" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none min-w-[200px] hidden">
                    Save Vital Signs
                </button>
                <button id="saveFacultyMedReferralBtn" type="button" class="py-2 px-3 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700 disabled:opacity-50 disabled:pointer-events-none min-w-[200px] hidden">
                    Save Medication Referral
                </button>
            </div>
        </div>
    </div>

    <!-- Edit Patient Modal -->
    <div id="editPatientModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-4xl h-[90vh] mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200">
                <h3 class="font-bold text-lg text-gray-800">Edit Patient Information</h3>
                <button id="closeEditPatientModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto flex-1">
                <form id="editPatientForm" class="space-y-6">
                    <input type="hidden" id="editPatientId" name="patient_id">
                    <input type="hidden" id="editPatientType" name="patient_type">
                    
                    <!-- Dynamic form content will be inserted here -->
                    <div id="editFormContent">
                        <!-- Form content will be generated based on patient type -->
                    </div>
                </form>
            </div>
            
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                <button id="cancelEditPatient" type="button" class="py-2 px-4 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50">
                    Cancel
                </button>
                <button id="saveEditPatient" type="button" class="py-2 px-4 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-blue-600 text-white hover:bg-blue-700 focus:outline-hidden focus:bg-blue-700">
                    <i class="ri-save-line"></i>
                    Save Changes
                </button>
            </div>
        </div>
    </div>

    <!-- Delete Patient Modal -->
    <div id="deletePatientModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">
        <div class="w-full max-w-md mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto">
            <div class="flex justify-between items-center py-4 px-6 border-b border-gray-200">
                <h3 class="font-bold text-lg text-gray-800">Delete Patient</h3>
                <button id="closeDeletePatientModal" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200" aria-label="Close">
                    <span class="sr-only">Close</span>
                    <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18"></path>
                        <path d="m6 6 12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0 w-12 h-12 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="ri-delete-bin-line text-2xl text-red-600"></i>
                    </div>
                    <div class="ml-4">
                        <h4 class="text-lg font-medium text-gray-900">Confirm Deletion</h4>
                        <p class="text-sm text-gray-500">This action cannot be undone.</p>
                    </div>
                </div>
                
                <div class="bg-gray-50 p-4 rounded-lg mb-4">
                    <p class="text-sm text-gray-700">
                        Are you sure you want to delete <strong id="deletePatientName"></strong>?
                    </p>
                    <p class="text-xs text-gray-500 mt-2">
                        Patient ID: <span id="deletePatientId"></span>
                    </p>
                </div>
                
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="ri-alert-line text-yellow-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-800">
                                <strong>Warning:</strong> This will permanently delete all patient records, including medical history and prescriptions.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200">
                <button id="cancelDeletePatient" type="button" class="py-2 px-4 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50">
                    Cancel
                </button>
                <button id="confirmDeletePatient" type="button" class="py-2 px-4 inline-flex items-center justify-center gap-x-2 text-sm font-medium rounded-lg border border-transparent bg-red-600 text-white hover:bg-red-700 focus:outline-hidden focus:bg-red-700">
                    <i class="ri-delete-bin-line"></i>
                    Delete Patient
                </button>
            </div>
        </div>
    </div>

</main>

<script>
// Update summary card counts
document.addEventListener('DOMContentLoaded', function() {
    // Update summary card counts
    document.getElementById('totalPatientsCount').textContent = '<?php echo $total_patients; ?>';
    document.getElementById('studentsCount').textContent = '<?php echo $imported_patients_count; ?>';
    document.getElementById('facultyCount').textContent = '<?php echo $faculty_count; ?>';
    document.getElementById('visitorsCount').textContent = '<?php echo $visitor_count; ?>';
    
    // Update tab counts
    document.getElementById('allCount').textContent = '<?php echo $total_patients; ?>';
    document.getElementById('studentsTabCount').textContent = '<?php echo $imported_patients_count; ?>';
    document.getElementById('facultyTabCount').textContent = '<?php echo $faculty_count; ?>';
    document.getElementById('visitorsTabCount').textContent = '<?php echo $visitor_count; ?>';
    document.getElementById('totalRecordsCount').textContent = '<?php echo $total_patients; ?>';
    
    // Edit and Delete Modal Functionality
    const editPatientModal = document.getElementById('editPatientModal');
    const deletePatientModal = document.getElementById('deletePatientModal');
    const closeEditPatientModal = document.getElementById('closeEditPatientModal');
    const closeDeletePatientModal = document.getElementById('closeDeletePatientModal');
    const cancelEditPatient = document.getElementById('cancelEditPatient');
    const cancelDeletePatient = document.getElementById('cancelDeletePatient');
    const saveEditPatient = document.getElementById('saveEditPatient');
    const confirmDeletePatient = document.getElementById('confirmDeletePatient');

    // Edit Patient functionality
    function openEditModal(patientData) {
        // Set hidden fields
        document.getElementById('editPatientId').value = patientData.id || '';
        document.getElementById('editPatientType').value = patientData.type || '';
        
        // Generate form content based on patient type
        let formContent = '';
        
        if (patientData.type === 'visitor') {
            // Visitor-specific form
            formContent = `
                <!-- Personal Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" id="editName" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                            <input type="number" id="editAge" name="age" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select id="editGender" name="gender" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea id="editAddress" name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Contact Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                            <input type="tel" id="editContact" name="contact_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Emergency Contact</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact</label>
                            <input type="text" id="editEmergencyContactVisitor" name="emergency_contact_visitor" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>
            `;
        } else if (patientData.type === 'faculty') {
            // Faculty-specific form
            formContent = `
                <!-- Personal Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" id="editName" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Age</label>
                            <input type="number" id="editAge" name="age" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select id="editGender" name="gender" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Civil Status</label>
                            <select id="editCivilStatus" name="civil_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Civil Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Divorced">Divorced</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Citizenship</label>
                            <input type="text" id="editCitizenship" name="citizenship" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea id="editAddress" name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>

                <!-- Professional Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Professional Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                            <input type="text" id="editDepartment" name="department" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">College/Course</label>
                            <input type="text" id="editCollegeCourse" name="college_course" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Contact Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="editEmail" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact</label>
                            <input type="tel" id="editContact" name="contact_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Emergency Contact</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact</label>
                            <input type="text" id="editEmergencyContactFaculty" name="emergency_contact" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Default student form (if needed)
            formContent = `
                <!-- Personal Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                            <input type="text" id="editName" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Student ID</label>
                            <input type="text" id="editStudentId" name="student_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                            <input type="date" id="editDob" name="dob" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Gender</label>
                            <select id="editGender" name="gender" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Year Level</label>
                            <select id="editYearLevel" name="year_level" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Year Level</option>
                                <option value="1st Year">1st Year</option>
                                <option value="2nd Year">2nd Year</option>
                                <option value="3rd Year">3rd Year</option>
                                <option value="4th Year">4th Year</option>
                                <option value="5th Year">5th Year</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Civil Status</label>
                            <select id="editCivilStatus" name="civil_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">Select Civil Status</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Widowed">Widowed</option>
                                <option value="Divorced">Divorced</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea id="editAddress" name="address" rows="2" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Contact Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" id="editEmail" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Contact Number</label>
                            <input type="tel" id="editContact" name="contact_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Additional Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Additional Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Religion</label>
                            <input type="text" id="editReligion" name="religion" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Citizenship</label>
                            <input type="text" id="editCitizenship" name="citizenship" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Course/Program</label>
                            <input type="text" id="editCourse" name="course_program" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Guardian Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Guardian Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Name</label>
                            <input type="text" id="editGuardianName" name="guardian_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Guardian Contact</label>
                            <input type="tel" id="editGuardianContact" name="guardian_contact" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Emergency Contact -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Emergency Contact</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Name</label>
                            <input type="text" id="editEmergencyName" name="emergency_contact_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact Number</label>
                            <input type="tel" id="editEmergencyContact" name="emergency_contact_number" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>

                <!-- Parent Information -->
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h4 class="text-lg font-semibold text-gray-800 mb-4">Parent Information</h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Parent Email</label>
                            <input type="email" id="editParentEmail" name="parent_email" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Parent Phone</label>
                            <input type="tel" id="editParentPhone" name="parent_phone" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Insert the form content
        document.getElementById('editFormContent').innerHTML = formContent;
        
        // Populate form fields with patient data
        document.getElementById('editName').value = patientData.name || '';
        document.getElementById('editGender').value = patientData.gender || '';
        document.getElementById('editAddress').value = patientData.address || '';
        document.getElementById('editContact').value = patientData.contact || '';
        
        // Common fields for all types
        const ageField = document.getElementById('editAge');
        if (ageField) ageField.value = patientData.age || '';
        
        // Type-specific field population
        if (patientData.type === 'faculty') {
            document.getElementById('editCivilStatus').value = patientData.civil || '';
            document.getElementById('editEmail').value = patientData.email || '';
            document.getElementById('editCitizenship').value = patientData.citizenship || '';
            document.getElementById('editDepartment').value = patientData.department || '';
            document.getElementById('editCollegeCourse').value = patientData['college-course'] || '';
            document.getElementById('editEmergencyContactFaculty').value = patientData['emergency-contact-faculty'] || '';
        } else if (patientData.type === 'visitor') {
            document.getElementById('editEmergencyContactVisitor').value = patientData['emergency-contact-visitor'] || '';
        } else {
            // Student fields
            document.getElementById('editStudentId').value = patientData.student_id || '';
            document.getElementById('editDob').value = patientData.dob || '';
            document.getElementById('editYearLevel').value = patientData.year || '';
            document.getElementById('editCivilStatus').value = patientData.civil || '';
            document.getElementById('editEmail').value = patientData.email || '';
            document.getElementById('editReligion').value = patientData.religion || '';
            document.getElementById('editCitizenship').value = patientData.citizenship || '';
            document.getElementById('editCourse').value = patientData.course || '';
            document.getElementById('editGuardianName').value = patientData['guardian-name'] || '';
            document.getElementById('editGuardianContact').value = patientData['guardian-contact'] || '';
            document.getElementById('editEmergencyName').value = patientData['emergency-name'] || '';
            document.getElementById('editEmergencyContact').value = patientData['emergency-contact'] || '';
            document.getElementById('editParentEmail').value = patientData['parent-email'] || '';
            document.getElementById('editParentPhone').value = patientData['parent-phone'] || '';
        }
        
        editPatientModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeEditModal() {
        editPatientModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Delete Patient functionality
    function openDeleteModal(patientData) {
        document.getElementById('deletePatientName').textContent = patientData.name || 'Unknown';
        document.getElementById('deletePatientId').textContent = patientData.id || 'N/A';
        
        // Store patient data for deletion
        deletePatientModal.dataset.patientId = patientData.id || '';
        deletePatientModal.dataset.patientType = patientData.type || '';
        
        deletePatientModal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function closeDeleteModal() {
        deletePatientModal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    // Event listeners for edit modal
    closeEditPatientModal.addEventListener('click', closeEditModal);
    cancelEditPatient.addEventListener('click', closeEditModal);
    window.addEventListener('click', (e) => {
        if (e.target === editPatientModal) closeEditModal();
    });

    // Event listeners for delete modal
    closeDeletePatientModal.addEventListener('click', closeDeleteModal);
    cancelDeletePatient.addEventListener('click', closeDeleteModal);
    window.addEventListener('click', (e) => {
        if (e.target === deletePatientModal) closeDeleteModal();
    });

    // Edit button click handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.editBtn')) {
            const btn = e.target.closest('.editBtn');
            const patientData = {
                id: btn.dataset.id,
                type: btn.dataset.type,
                name: btn.dataset.name,
                student_id: btn.dataset.studentId,
                dob: btn.dataset.dob,
                gender: btn.dataset.gender,
                year: btn.dataset.year,
                address: btn.dataset.address,
                civil: btn.dataset.civil,
                email: btn.dataset.email,
                contact: btn.dataset.contact,
                religion: btn.dataset.religion,
                citizenship: btn.dataset.citizenship,
                course: btn.dataset.course,
                'guardian-name': btn.dataset.guardianName,
                'guardian-contact': btn.dataset.guardianContact,
                'emergency-name': btn.dataset.emergencyName,
                'emergency-contact': btn.dataset.emergencyContact,
                'parent-email': btn.dataset.parentEmail,
                'parent-phone': btn.dataset.parentPhone,
                age: btn.dataset.age,
                department: btn.dataset.department,
                'college-course': btn.dataset.collegeCourse,
                password: btn.dataset.password,
                'emergency-contact-faculty': btn.dataset.emergencyContactFaculty,
                'emergency-contact-visitor': btn.dataset.emergencyContactVisitor
            };
            openEditModal(patientData);
        }
    });

    // Delete button click handler
    document.addEventListener('click', function(e) {
        if (e.target.closest('.deleteBtn')) {
            const btn = e.target.closest('.deleteBtn');
            const patientData = {
                id: btn.dataset.id,
                type: btn.dataset.type,
                name: btn.dataset.name,
                student_id: btn.dataset.studentId
            };
            openDeleteModal(patientData);
        }
    });

    // Save edit functionality
    saveEditPatient.addEventListener('click', function() {
        const form = document.getElementById('editPatientForm');
        const formData = new FormData(form);
        const patientId = formData.get('patient_id');
        const patientType = formData.get('patient_type');
        
        // Show loading state
        saveEditPatient.disabled = true;
        saveEditPatient.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Saving...';
        
        // Send AJAX request to update patient
        fetch('update_patient.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                if ($('#updateToast').length) $('#updateToast').remove();
                $('body').append(`
                  <div id="updateToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                    <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                      <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                      <span>Patient updated successfully!</span>
                    </div>
                  </div>
                `);
                
                // Auto-remove toast after 3 seconds
                setTimeout(() => {
                    $('#updateToast').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
                
                closeEditModal();
                // Refresh the current view
                const activeTab = document.querySelector('.patient-tab.active');
                const currentType = activeTab ? activeTab.getAttribute('data-tab') : 'all';
                filterPatientsByType(currentType);
            } else {
                alert('Error updating patient: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error updating patient. Please try again.');
        })
        .finally(() => {
            // Reset button state
            saveEditPatient.disabled = false;
            saveEditPatient.innerHTML = '<i class="ri-save-line"></i> Save Changes';
        });
    });

    // Confirm delete functionality
    confirmDeletePatient.addEventListener('click', function() {
        const patientId = deletePatientModal.dataset.patientId;
        const patientType = deletePatientModal.dataset.patientType;
        
        // Show loading state
        confirmDeletePatient.disabled = true;
        confirmDeletePatient.innerHTML = '<i class="ri-loader-4-line animate-spin"></i> Deleting...';
        
        // Send AJAX request to delete patient
        const formData = new FormData();
        formData.append('patient_id', patientId);
        formData.append('patient_type', patientType);
        
        fetch('delete_patient.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                if ($('#deleteToast').length) $('#deleteToast').remove();
                $('body').append(`
                  <div id="deleteToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                    <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                      <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                      <span>Patient deleted successfully!</span>
                    </div>
                  </div>
                `);
                
                // Auto-remove toast after 3 seconds
                setTimeout(() => {
                    $('#deleteToast').fadeOut(300, function() {
                        $(this).remove();
                    });
                }, 3000);
                
                closeDeleteModal();
                // Auto-refresh the page after a short delay for immediate update
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            } else {
                alert('Error deleting patient: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error deleting patient. Please try again.');
        })
        .finally(() => {
            // Reset button state
            confirmDeletePatient.disabled = false;
            confirmDeletePatient.innerHTML = '<i class="ri-delete-bin-line"></i> Delete Patient';
        });
    });
    
    // Load all patients by default
    filterPatientsByType('all');
    
    // Test the tab functionality
    console.log('Tab system initialized. Total patients:', <?php echo $total_patients; ?>);
    console.log('Students:', <?php echo $imported_patients_count; ?>);
    console.log('Faculty:', <?php echo $faculty_count; ?>);
    console.log('Visitors:', <?php echo $visitor_count; ?>);
    
    // Tab switching functionality
    const tabs = document.querySelectorAll('.patient-tab');
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
            // Store current scroll position for tab switching
            const currentScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            filterPatientsByType(tabType, 1, currentScrollPosition);
        });
    });
    
    // Function to filter patients by type
    function filterPatientsByType(type, page = 1, scrollPosition = null) {
        const tbody = document.querySelector('#importedPatientsTable tbody');
        
        // Make AJAX request to get filtered data (no loading state for pagination)
        fetch('get_patients_by_type.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `type=${type}&page=${page}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('Received data for type:', type, data);
            if (data.success) {
                updateTableWithPatients(data.patients, type);
                updatePagination(data.pagination);
                
                // Restore scroll position if provided
                if (scrollPosition !== null) {
                    setTimeout(() => {
                        window.scrollTo(0, scrollPosition);
                    }, 100);
                }
            } else {
                showError('Failed to load patients: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Failed to load patients. Please try again.');
        });
    }
    
    // Function to update table with filtered patients
    function updateTableWithPatients(patients, type) {
        const tbody = document.querySelector('#importedPatientsTable tbody');
        
        if (patients.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="ri-user-line text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg font-medium">No ${type} found</p>
                            <p class="text-gray-400 text-sm">Try adjusting your search terms or filters</p>
                        </div>
                    </td>
                </tr>
            `;
            return;
        }
        
        let tableHTML = '';
        patients.forEach(function(patient) {
            // Calculate age - use age column for faculty/visitors, calculate from dob for students
            let age;
            if (patient.table_type === 'imported_patients') {
                // Students: calculate from dob
                age = patient.dob ? calculateAge(patient.dob) : 'N/A';
            } else {
                // Faculty/Visitors: use age column directly
                age = patient.age || 'N/A';
            }
            const ageText = age !== 'N/A' ? age + ' years' : 'N/A';
            
            // Determine patient type and badge color
            let typeBadge = '';
            let typeColor = '';
            if (type === 'students' || (type === 'all' && patient.table_type === 'imported_patients')) {
                typeBadge = 'Student';
                typeColor = 'bg-green-100 text-green-800';
            } else if (type === 'faculty' || (type === 'all' && patient.table_type === 'faculty')) {
                typeBadge = 'Faculty';
                typeColor = 'bg-purple-100 text-purple-800';
            } else if (type === 'visitors' || (type === 'all' && patient.table_type === 'visitor')) {
                typeBadge = 'Visitor';
                typeColor = 'bg-orange-100 text-orange-800';
            }
            
            // Get initials for avatar
            const initials = patient.name ? patient.name.split(' ').map(n => n[0]).join('').toUpperCase().substring(0, 2) : '??';
            
            tableHTML += `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                    <span class="text-sm font-medium text-blue-600">${initials}</span>
                                </div>
                            </div>
                            <div class="ml-4 min-w-0 flex-1">
                                <div class="text-sm font-medium text-gray-900 truncate" title="${patient.name || 'N/A'}">${patient.name || 'N/A'}</div>
                                <div class="text-sm text-gray-500 truncate" title="${patient.email || 'N/A'}">${patient.email || 'N/A'}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${typeColor}">
                            ${typeBadge}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div class="truncate" title="${ageText}">${ageText}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        <div class="flex items-center">
                            <i class="ri-phone-line text-gray-400 mr-2 flex-shrink-0"></i>
                            <span class="truncate" title="${patient.contact_number || patient.phone || 'N/A'}">${patient.contact_number || patient.phone || 'N/A'}</span>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center space-x-2">
                            <button class="viewBtn text-blue-600 hover:text-blue-900" 
                                data-name="${patient.name || ''}" 
                                data-id="${patient.id || ''}" 
                                data-student-id="${patient.student_id || ''}"
                                data-type="${patient.table_type || ''}"
                                data-email="${patient.email || ''}"
                                data-contact="${patient.contact_number || patient.phone || ''}"
                                data-dob="${patient.dob || ''}"
                                data-gender="${patient.gender || ''}"
                                data-address="${patient.address || ''}"
                                data-civil="${patient.civil_status || ''}"
                                data-religion="${patient.religion || ''}"
                                data-citizenship="${patient.citizenship || ''}"
                                data-year="${patient.year_level || ''}"
                                data-course="${patient.course_program || ''}"
                                data-parent-email="${patient.parent_email || ''}"
                                data-parent-phone="${patient.parent_phone || ''}"
                                data-guardian-name="${patient.guardian_name || ''}"
                                data-guardian-contact="${patient.guardian_contact || ''}"
                                data-emergency-name="${patient.emergency_contact_name || ''}"
                                data-emergency-contact="${patient.emergency_contact_number || ''}"
                                data-age="${patient.age || ''}"
                                data-department="${patient.department || ''}"
                                data-college-course="${patient.college_course || ''}"
                                data-password="${patient.password || ''}"
                                data-emergency-contact-faculty="${patient.emergency_contact || ''}"
                                data-emergency-contact-visitor="${patient.emergency_contact || ''}">
                                <i class="ri-eye-line"></i>
                            </button>
                            <button class="editBtn text-gray-600 hover:text-gray-900" 
                                data-id="${patient.id || ''}" 
                                data-student-id="${patient.student_id || ''}" 
                                data-name="${patient.name || ''}" 
                                data-dob="${patient.dob || ''}"
                                data-gender="${patient.gender || ''}" 
                                data-year="${patient.year_level || ''}" 
                                data-address="${patient.address || ''}" 
                                data-civil="${patient.civil_status || ''}"
                                data-email="${patient.email || ''}"
                                data-contact="${patient.contact_number || patient.phone || ''}"
                                data-religion="${patient.religion || ''}"
                                data-citizenship="${patient.citizenship || ''}"
                                data-course="${patient.course_program || ''}"
                                data-guardian-name="${patient.guardian_name || ''}"
                                data-guardian-contact="${patient.guardian_contact || ''}"
                                data-emergency-name="${patient.emergency_contact_name || ''}"
                                data-emergency-contact="${patient.emergency_contact_number || ''}"
                                data-parent-email="${patient.parent_email || ''}"
                                data-parent-phone="${patient.parent_phone || ''}"
                                data-type="${patient.table_type || ''}"
                                data-age="${patient.age || ''}"
                                data-department="${patient.department || ''}"
                                data-college-course="${patient.college_course || ''}"
                                data-password="${patient.password || ''}"
                                data-emergency-contact-faculty="${patient.emergency_contact || ''}"
                                data-emergency-contact-visitor="${patient.emergency_contact || ''}"
                                title="Edit Patient">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button class="deleteBtn text-red-600 hover:text-red-900" 
                                data-id="${patient.id || ''}" 
                                data-name="${patient.name || ''}" 
                                data-student-id="${patient.student_id || ''}"
                                data-type="${patient.table_type || ''}"
                                title="Delete Patient">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        });
        
        tbody.innerHTML = tableHTML;
        
        // Re-attach event listeners for view buttons
        attachViewButtonListeners();
    }
    
    // Helper function to calculate age
    function calculateAge(dateString) {
        const today = new Date();
        const birthDate = new Date(dateString);
        let age = today.getFullYear() - birthDate.getFullYear();
        const monthDiff = today.getMonth() - birthDate.getMonth();
        
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
            age--;
        }
        
        return age;
    }
    
    // Helper function to format date
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }
    
    // Helper function to show error
    function showError(message) {
        const tbody = document.querySelector('#importedPatientsTable tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <i class="ri-error-warning-line text-4xl text-red-300 mb-4"></i>
                        <p class="text-red-500 text-lg font-medium">Error</p>
                        <p class="text-gray-400 text-sm">${message}</p>
                    </div>
                </td>
            </tr>
        `;
    }
    
    // Function to attach view button listeners
    function attachViewButtonListeners() {
        document.querySelectorAll('.viewBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Your existing view button logic here
                console.log('View button clicked for:', this.getAttribute('data-name'));
            });
        });
    }
    
    // Search functionality
    let searchTimeout;
    document.getElementById('searchInput').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            // Store current scroll position for search
            const currentScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
            
            const searchTerm = this.value.trim();
            const activeTab = document.querySelector('.patient-tab.active');
            const currentType = activeTab ? activeTab.getAttribute('data-tab') : 'all';
            
            if (searchTerm) {
                searchPatients(searchTerm, currentType, 1, currentScrollPosition);
            } else {
                filterPatientsByType(currentType, 1, currentScrollPosition);
            }
        }, 300);
    });
    
    // Function to search patients
    function searchPatients(searchTerm, type, page = 1, scrollPosition = null) {
        const tbody = document.querySelector('#importedPatientsTable tbody');
        
        // Make AJAX request to search (no loading state for pagination)
        fetch('search_patients.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `search=${encodeURIComponent(searchTerm)}&type=${type}&page=${page}`
        })
        .then(response => response.json())
        .then(data => {
            console.log('Search results for type:', type, 'search term:', searchTerm, data);
            if (data.success) {
                updateTableWithPatients(data.patients, type);
                updatePagination(data.pagination);
                
                // Restore scroll position if provided
                if (scrollPosition !== null) {
                    setTimeout(() => {
                        window.scrollTo(0, scrollPosition);
                    }, 100);
                }
            } else {
                showError('Search failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showError('Search failed. Please try again.');
        });
    }
    
    // Function to update pagination controls
    function updatePagination(pagination) {
        const paginationControls = document.getElementById('paginationControls');
        const recordsInfo = document.getElementById('recordsInfo');
        const paginationNav = document.getElementById('paginationNav');
        
        if (!pagination) {
            paginationControls.style.display = 'none';
            return;
        }
        
        paginationControls.style.display = 'block';
        
        // Update records info
        recordsInfo.textContent = `Showing ${pagination.start_record} to ${pagination.end_record} of ${pagination.total_records} entries`;
        
        // Generate pagination navigation
        let navHTML = '';
        
        // Previous button
        if (pagination.current_page > 1) {
            navHTML += `
                <button onclick="goToPage(${pagination.current_page - 1})" 
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>
            `;
        } else {
            navHTML += `
                <button type="button" disabled 
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>
            `;
        }
        
        // Page numbers with ellipses
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.total_pages, pagination.current_page + 2);
        
        // Add ellipsis at the beginning if needed
        if (startPage > 1) {
            navHTML += `
                <button onclick="goToPage(1)" 
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                    1
                </button>
            `;
            if (startPage > 2) {
                navHTML += `
                    <span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">
                        ...
                    </span>
                `;
            }
        }
        
        // Add page numbers
        for (let i = startPage; i <= endPage; i++) {
            if (i === pagination.current_page) {
                navHTML += `
                    <button type="button" 
                            class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-gray-200 text-gray-800 focus:outline-hidden focus:bg-gray-300">
                        ${i}
                    </button>
                `;
            } else {
                navHTML += `
                    <button onclick="goToPage(${i})" 
                            class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                        ${i}
                    </button>
                `;
            }
        }
        
        // Add ellipsis at the end if needed
        if (endPage < pagination.total_pages) {
            if (endPage < pagination.total_pages - 1) {
                navHTML += `
                    <span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">
                        ...
                    </span>
                `;
            }
            navHTML += `
                <button onclick="goToPage(${pagination.total_pages})" 
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                    ${pagination.total_pages}
                </button>
            `;
        }
        
        // Next button
        if (pagination.current_page < pagination.total_pages) {
            navHTML += `
                <button onclick="goToPage(${pagination.current_page + 1})" 
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
        } else {
            navHTML += `
                <button type="button" disabled 
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none">
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
        }
        
        paginationNav.innerHTML = navHTML;
    }
    
    // Function to go to a specific page
    function goToPage(page) {
        // Store current scroll position
        const currentScrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        
        const activeTab = document.querySelector('.patient-tab.active');
        const currentType = activeTab ? activeTab.getAttribute('data-tab') : 'all';
        const searchInput = document.getElementById('searchInput');
        const searchTerm = searchInput ? searchInput.value.trim() : '';
        
        if (searchTerm) {
            searchPatients(searchTerm, currentType, page, currentScrollPosition);
        } else {
            filterPatientsByType(currentType, page, currentScrollPosition);
        }
    }
    
    // Make goToPage globally accessible
    window.goToPage = goToPage;
});
</script>

<script>
$(document).ready(function() {
        // Add New Patient Modal logic
        const addPatientBtn = document.getElementById('addPatientBtn');
        const addEntityTypeModal = document.getElementById('addEntityTypeModal');
        const closeAddEntityTypeModalBtn = document.getElementById('closeAddEntityTypeModalBtn');
        const chooseStudentBtn = document.getElementById('chooseStudentBtn');
        const chooseVisitorBtn = document.getElementById('chooseVisitorBtn');
        const addPatientModal = document.getElementById('addPatientModal');
        const closeAddPatientModalBtn = document.getElementById('closeAddPatientModalBtn');
        const addVisitorModal = document.getElementById('addVisitorModal');
        const closeAddVisitorModalBtn = document.getElementById('closeAddVisitorModalBtn');
        const chooseFacultyBtn = document.getElementById('chooseFacultyBtn');
        const addFacultyModal = document.getElementById('addFacultyModal');
        const closeAddFacultyModalBtn = document.getElementById('closeAddFacultyModalBtn');
        const facultyDepartmentSelect = document.getElementById('facultyDepartmentSelect');
        const facultyCourseDiv = document.getElementById('facultyCourseDiv');
        const facultyCourseSelect = document.getElementById('facultyCourseSelect');

        // Choose Faculty
        chooseFacultyBtn.addEventListener('click', () => {
            addEntityTypeModal.classList.add('hidden');
            addFacultyModal.classList.remove('hidden');
            $('body, html').addClass('overflow-hidden');
        });
        closeAddFacultyModalBtn.addEventListener('click', () => {
            addFacultyModal.classList.add('hidden');
            addEntityTypeModal.classList.remove('hidden');
            $('body, html').removeClass('overflow-hidden');
        });
        window.addEventListener('click', (e) => {
            if (e.target === addFacultyModal) {
                addFacultyModal.classList.add('hidden');
                addEntityTypeModal.classList.remove('hidden');
                $('body, html').removeClass('overflow-hidden');
            }
        });
        facultyDepartmentSelect.addEventListener('change', function() {
            if (this.value === 'College') {
                facultyCourseDiv.style.display = '';
                facultyCourseSelect.required = true;
            } else {
                facultyCourseDiv.style.display = 'none';
                facultyCourseSelect.required = false;
                facultyCourseSelect.value = '';
            }
        });
        document.getElementById('addFacultyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Clear previous messages
            $('#addFacultyForm .error-msg, #addFacultyForm .success-msg').remove();
            
            // Get form data
            const formData = new FormData(this);
            
            // Validate required fields
            const requiredFields = ['full_name', 'email', 'password', 'address', 'contact', 'emergency_contact', 'age', 'gender', 'department', 'civil_status', 'citizenship'];
            for (let field of requiredFields) {
                if (!formData.get(field) || formData.get(field).trim() === '') {
                    showErrorModal(`Please fill in the ${field.replace('_', ' ')} field.`, 'Validation Error');
                    return;
                }
            }
            
            // Validate college course if department is College
            const department = formData.get('department');
            if (department === 'College') {
                const collegeCourse = formData.get('college_course');
                if (!collegeCourse || collegeCourse.trim() === '') {
                    showErrorModal('Please select a college course when department is College.', 'Validation Error');
                    return;
                }
            }
            
            // Validate age
            const age = parseInt(formData.get('age'));
            if (age <= 0 || age > 120) {
                showErrorModal('Please enter a valid age between 1 and 120.', 'Validation Error');
                return;
            }
            
            // Validate email format
            const email = formData.get('email');
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                showErrorModal('Please enter a valid email address.', 'Validation Error');
                return;
            }

            // Validate password length
            const password = formData.get('password');
            if (password.length < 6) {
                showErrorModal('Password must be at least 6 characters long.', 'Validation Error');
                return;
            }
            
            // Show loading state
            const submitBtn = document.querySelector('button[form="addFacultyForm"]');
            const originalText = submitBtn ? submitBtn.textContent : 'Add Faculty';
            if (submitBtn) {
                submitBtn.textContent = 'Adding...';
                submitBtn.disabled = true;
            }
            
            // Submit form via AJAX
            fetch('save_faculty.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    if ($('#facultyToast').length) $('#facultyToast').remove();
                    $('body').append(`
                      <div id="facultyToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                        <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                          <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                          <span>Faculty added successfully!</span>
                        </div>
                      </div>
                    `);
                    
                    // Close modal and reset form
                    document.getElementById('addFacultyModal').classList.add('hidden');
            $('body, html').removeClass('overflow-hidden');
            this.reset();
                    
                    // Reload the page after a short delay
                    setTimeout(function() {
                        $('#facultyToast').fadeOut(300, function() {
                            $(this).remove();
                        });
                        window.location.reload();
                    }, 1200);
                } else {
                    showErrorModal('Error: ' + data.message, 'Error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showErrorModal('An error occurred while adding the faculty. Please try again.', 'Error');
            })
            .finally(() => {
                // Restore button state
                if (submitBtn) {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            });
        });
        // Open chooser instead of student modal directly
        addPatientBtn.addEventListener('click', () => { addEntityTypeModal.classList.remove('hidden'); $('body, html').addClass('overflow-hidden'); });
        closeAddEntityTypeModalBtn.addEventListener('click', () => { addEntityTypeModal.classList.add('hidden'); $('body, html').removeClass('overflow-hidden'); });
        window.addEventListener('click', (e) => {
            if (e.target === addEntityTypeModal) { addEntityTypeModal.classList.add('hidden'); $('body, html').removeClass('overflow-hidden'); }
        });

        // Choose Student
        chooseStudentBtn.addEventListener('click', () => {
            addEntityTypeModal.classList.add('hidden');
            addPatientModal.classList.remove('hidden');
        });

        // Choose Visitor
        chooseVisitorBtn.addEventListener('click', () => {
            addEntityTypeModal.classList.add('hidden');
            addVisitorModal.classList.remove('hidden');
        });

        // Close student modal → return to chooser
        closeAddPatientModalBtn.addEventListener('click', () => {
            addPatientModal.classList.add('hidden');
            addEntityTypeModal.classList.remove('hidden');
            $('body, html').addClass('overflow-hidden');
        });
        window.addEventListener('click', (e) => {
            if (e.target === addPatientModal) {
                addPatientModal.classList.add('hidden');
                addEntityTypeModal.classList.remove('hidden');
                $('body, html').addClass('overflow-hidden');
            }
        });

        // Close visitor modal → return to chooser
        closeAddVisitorModalBtn.addEventListener('click', () => {
            addVisitorModal.classList.add('hidden');
            addEntityTypeModal.classList.remove('hidden');
            $('body, html').addClass('overflow-hidden');
        });
        window.addEventListener('click', (e) => {
            if (e.target === addVisitorModal) {
                addVisitorModal.classList.add('hidden');
                addEntityTypeModal.classList.remove('hidden');
                $('body, html').addClass('overflow-hidden');
            }
        });

        // Password toggle function for patient modal
        window.togglePatientPassword = function(inputId, element) {
            const input = document.getElementById(inputId);
            const icon = element.querySelector('i');

            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'ri-eye-line';
            } else {
                input.type = 'password';
                icon.className = 'ri-eye-off-line';
            }
        };

        // Student ID validation
        let studentIdTimeout;
        $('#student_id_input').on('input', function() {
            const studentId = $(this).val().trim();
            const validationDiv = $('#student_id_validation');

            // Clear previous timeout
            clearTimeout(studentIdTimeout);

            // Clear validation message if empty
            if (studentId === '') {
                validationDiv.html('').removeClass('text-red-600 text-green-600');
                return;
            }

            // Set timeout to avoid too many requests
            studentIdTimeout = setTimeout(function() {
                $.ajax({
                    url: 'check_student_id.php',
                    type: 'POST',
                    data: {
                        student_id: studentId
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (!response.valid_format) {
                            validationDiv.html(response.message).removeClass('text-green-600').addClass('text-red-600');
                        } else if (response.exists) {
                            validationDiv.html(response.message).removeClass('text-green-600').addClass('text-red-600');
                        } else {
                            validationDiv.html(response.message).removeClass('text-red-600').addClass('text-green-600');
                        }
                    },
                    error: function() {
                        validationDiv.html('Error checking Student ID. Please try again.').removeClass('text-green-600').addClass('text-red-600');
                    }
                });
            }, 500); // 500ms delay
        });

        // Add Patient Form Submission
        $('#addPatientForm').on('submit', function(e) {
            e.preventDefault();

            // Clear previous messages
            $('#addPatientForm .error-msg, #addPatientForm .success-msg').remove();

            // Check if student ID validation shows an error
            const validationDiv = $('#student_id_validation');
            if (validationDiv.hasClass('text-red-600')) {
                showErrorModal('Please fix the Student ID validation error before submitting.', 'Validation Error');
                return;
            }

            // If validation is still pending, wait a moment
            if (validationDiv.text() === '') {
                showErrorModal('Please wait for Student ID validation to complete.', 'Validation Pending');
                return;
            }

            // Check if Student ID is in correct format
            const studentId = $('#student_id_input').val().trim();
            // Accept SCC-00-0000000 or SCC-00-00000000
            const formatPattern = /^SCC-\d{2}-\d{7,8}$/;
            if (!formatPattern.test(studentId)) {
                showErrorModal('Please enter a valid Student ID in format: SCC-00-0000000 or SCC-00-00000000', 'Invalid Format');
                return;
            }

            $.ajax({
                url: 'add_patient.php',
                type: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        if ($('#patientToast').length) $('#patientToast').remove();
                        $('body').append(`
                      <div id="patientToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                        <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                          <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                          <span>Patient added successfully</span>
                        </div>
                      </div>
                    `);

                        // Close modal and reset form
                        $('#addPatientModal').addClass('hidden');
                        $('#addPatientForm')[0].reset();

                        // Reload the page after a short delay
                        setTimeout(function() {
                            $('#patientToast').fadeOut(300, function() {
                                $(this).remove();
                            });
                            window.location.reload();
                        }, 1200);
                    } else {
                        showErrorModal('Error: ' + response.message, 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    showErrorModal('An error occurred while adding the patient. Please try again.', 'Error');
                }
            });
        });

    // DataTables initialization removed - using AJAX-based filtering instead

    // Add Visitor AJAX submission
    $('#addVisitorForm').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize();
        $.ajax({
            url: 'save_visitor.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(resp){
                if(resp.success){
                    if ($('#visitorAddToast').length) $('#visitorAddToast').remove();
                    $('body').append(`
                      <div id="visitorAddToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                        <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                          <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                          <span>Patient added successfully</span>
                        </div>
                      </div>`);
                    setTimeout(function(){
                        $('#visitorAddToast').fadeOut(300, function(){ $(this).remove(); });
                        $('#addVisitorModal').addClass('hidden');
                        $('body, html').removeClass('overflow-hidden');
                        window.location.href = 'records.php';
                    }, 1000);
                } else {
                    showErrorModal('Error: ' + (resp.message || 'Failed to add visitor'), 'Error');
                }
            },
            error: function(){ showErrorModal('An error occurred while saving visitor.', 'Error'); }
        });

        // Table filter: show All, Student, Faculty, Visitor
        function applyTableFilter(val){
            const studentEl = document.getElementById('studentSection');
            const facultyEl = document.getElementById('facultySection');
            const visitorEl = document.getElementById('visitorSection');
            if (!studentEl || !facultyEl || !visitorEl) return;
            if (val === 'all') {
                studentEl.style.removeProperty('display');
                facultyEl.style.removeProperty('display');
                visitorEl.style.removeProperty('display');
                studentEl.classList.remove('hidden');
                facultyEl.classList.remove('hidden');
                visitorEl.classList.remove('hidden');
            } else if (val === 'student') {
                studentEl.style.setProperty('display','', 'important');
                facultyEl.style.setProperty('display','none', 'important');
                visitorEl.style.setProperty('display','none', 'important');
                studentEl.classList.remove('hidden');
                facultyEl.classList.add('hidden');
                visitorEl.classList.add('hidden');
            } else if (val === 'faculty') {
                studentEl.style.setProperty('display','none', 'important');
                facultyEl.style.setProperty('display','', 'important');
                visitorEl.style.setProperty('display','none', 'important');
                studentEl.classList.add('hidden');
                facultyEl.classList.remove('hidden');
                visitorEl.classList.add('hidden');
            } else if (val === 'visitor') {
                studentEl.style.setProperty('display','none', 'important');
                facultyEl.style.setProperty('display','none', 'important');
                visitorEl.style.setProperty('display','', 'important');
                studentEl.classList.add('hidden');
                facultyEl.classList.add('hidden');
                visitorEl.classList.remove('hidden');
            }
        }
        // Delegate change to ensure it binds regardless of load order
        function setBodyFilterClass(val){
            document.body.classList.remove('filter-all','filter-student','filter-faculty','filter-visitor');
            document.body.classList.add('filter-' + val);
        }
        $(document).on('change', '#tableFilter', function(){ setBodyFilterClass(this.value); applyTableFilter(this.value); });
        // Apply default on load
        setBodyFilterClass($('#tableFilter').val());
        applyTableFilter($('#tableFilter').val());
    });
    // DataTables search functionality removed - using AJAX-based search instead
    
    // DataTables filtering functionality removed - using AJAX-based filtering instead
    // View button logic
    $(document).on('click', '.viewBtn', function() {
        const name = $(this).data('name');
        const id = $(this).data('id');
        const studentId = $(this).data('student-id');
        const dob = $(this).data('dob');
        const gender = $(this).data('gender');
        const year = $(this).data('year');
        const address = $(this).data('address');
        const civil = $(this).data('civil');
        const email = $(this).data('email');
        const contact = $(this).data('contact');
        const religion = $(this).data('religion');
        const citizenship = $(this).data('citizenship');
        const course = $(this).data('course');
        const guardianName = $(this).data('guardian-name');
        const guardianContact = $(this).data('guardian-contact');
        const emergencyName = $(this).data('emergency-name');
        const emergencyContact = $(this).data('emergency-contact');
        const parentEmail = $(this).data('parent-email');
        const parentPhone = $(this).data('parent-phone');
        const type = $(this).data('type');
        const age = $(this).data('age');
        const department = $(this).data('department');
        const collegeCourse = $(this).data('college-course');
        const password = $(this).data('password');
        const emergencyContactFaculty = $(this).data('emergency-contact-faculty');
        const emergencyContactVisitor = $(this).data('emergency-contact-visitor');
        
        $('#modalPatientName').text(name + ' (' + id + ')');
        
        // Generate modal content based on patient type
        let modalContent = '';
        
        if (type === 'visitor') {
            // Visitor-specific fields
            modalContent = `
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Personal Information Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Personal Information</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Full Name:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${name || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Age:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${age || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Gender:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${gender || 'N/A'}</p>
                            </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Contact Information Section -->
                        <div>
                            <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Contact Information</h4>
                            <div class="space-y-3">
                                <div class="grid grid-cols-[140px_1fr] gap-3 items-start">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Address:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${address || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Contact:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${contact || 'N/A'}</p>
                            </div>
                            </div>
                        </div>

                        <!-- Emergency Contact Section -->
                        <div>
                            <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Emergency Contact</h4>
                            <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Emergency Contact:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${emergencyContactVisitor || 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else if (type === 'faculty') {
            // Faculty-specific fields
            modalContent = `
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Personal Information Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Personal Information</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Full Name:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${name || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Age:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${age || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Gender:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${gender || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Civil Status:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${civil || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Citizenship:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${citizenship || 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                        <!-- Professional Information Section -->
                    <div>
                            <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Professional Information</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Department:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${department || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">College/Course:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${collegeCourse || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Contact Information Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Contact Information</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Email:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${email || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Contact:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${contact || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-start">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Address:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${address || 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                        <!-- Emergency Contact Section -->
                    <div>
                            <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Emergency Contact</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Emergency Contact:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${emergencyContactFaculty || 'N/A'}</p>
                            </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        } else {
            // Default student/visitor fields
            modalContent = `
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Personal Information Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Personal Information</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Student ID:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${studentId || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Date of Birth:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${dob || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Gender:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${gender || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Civil Status:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${civil || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Religion:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${religion || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Citizenship:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${citizenship || 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Information Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Academic Information</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Year Level:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${year || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Course/Program:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${course || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Contact Information Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Contact Information</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Email:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${email || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Contact Number:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${contact || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Parent's Email:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${parentEmail || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Parent's Phone:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${parentPhone || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-start">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Address:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${address || 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contacts Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Emergency Contacts</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Guardian Name:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${guardianName || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Guardian Contact:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${guardianContact || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Emergency Contact:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${emergencyName || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Emergency Number:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${emergencyContact || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>
                </div>
            `;
        }
        
        $('#modalPatientDetails').html(modalContent);
        $('#studentModal').removeClass('hidden');
        $('body, html').addClass('overflow-hidden');
        
        // Set current patient name for other modals
        currentPatientName = name;
        
        // Default to Information tab when modal opens
        showProfileTab('infoTab');
        
        // Clear vital signs form when new patient is selected
        $('#vitalsForm input[name="weight"]').val('');
        $('#vitalsForm input[name="height"]').val('');
        $('#vitalsForm input[name="body_temp"]').val('');
        $('#vitalsForm input[name="resp_rate"]').val('');
        $('#vitalsForm input[name="pulse"]').val('');
        $('#vitalsForm input[name="blood_pressure"]').val('');
        $('#vitalsForm input[name="oxygen_sat"]').val('');
        $('#vitalsForm textarea[name="remarks"]').val('');
        $('#vitalsForm input[name="vital_date"]').val(new Date().toISOString().split('T')[0]);
        // Clear medication referral form
        $('#medReferralForm')[0].reset();
        $('#medReferralHistoryContent').html('<p class="text-center text-gray-400">Click on Medication Referral tab to load history...</p>');
    });
    $('#closeStudentModal').on('click', function() {
        $('#studentModal').addClass('hidden');
        $('body, html').removeClass('overflow-hidden');
    });
    $(window).on('click', function(e) {
        if (e.target === document.getElementById('studentModal')) {
            $('#studentModal').addClass('hidden');
            $('body, html').removeClass('overflow-hidden');
        }
    });
    // Prescribe Medicine Modal logic
    let currentPatientName = '';
    
    // Function to reset prescribe modal to initial state
    function resetPrescribeModal() {
        // Clear any existing medication history
        $('#prescribeMedForm .med-history').remove();
        
        // Reset form fields
        $('#prescribeMedForm')[0].reset();
        
        // Hide medicine fields initially
        $('#medsList, #addMedRowBtn').hide();
        
        // Ensure Prescribe Meds button is always present
        if ($('#prescribeMedForm #nextToMedsBtn').length === 0) {
            // Find the notes field and add the button after it
            const notesDiv = $('#prescribeMedForm textarea').closest('.mb-4');
            if (notesDiv.length > 0) {
                notesDiv.after('<button type="button" id="nextToMedsBtn" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90 mb-4">Prescribe Meds</button>');
            }
        }
        
        // Show the Prescribe Meds button
        $('#prescribeMedForm #nextToMedsBtn').show();
    }
    
    // Function to reset visitor prescribe modal to initial state
    function resetVisitorPrescribeModal() {
        // Clear any existing medication history
        $('#visitorPrescribeMedForm .med-history').remove();
        
        // Reset form fields
        $('#visitorPrescribeMedForm')[0].reset();
        
        // Hide medicine fields initially
        $('#visitorMedsList, #visitorAddMedRowBtn').hide();
        
        // Ensure Prescribe Meds button is always present
        if ($('#visitorPrescribeMedForm #visitorNextToMedsBtn').length === 0) {
            // Find the notes field and add the button after it
            const notesDiv = $('#visitorPrescribeMedForm textarea').closest('.mb-4');
            if (notesDiv.length > 0) {
                notesDiv.after('<button type="button" id="visitorNextToMedsBtn" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90 mb-4">Prescribe Meds</button>');
            }
        }
        
        // Show the Prescribe Meds button
        $('#visitorPrescribeMedForm #visitorNextToMedsBtn').show();
    }
    // --- PRESCRIBE MODAL: Show med history, then Notes, then a 'Next' button, then medicine fields ---
    $('#prescribeMedBtn').on('click', function() {
        $('#prescribeMedModal').removeClass('hidden');
        $('#prescribeMedModal h3').text('Prescribe Medicine for ' + currentPatientName);
        $('body, html').addClass('overflow-hidden');
        
        // Reset modal to initial state
        resetPrescribeModal();
        // Fetch and display medication history for this patient
        const patientName = currentPatientName;
        $.ajax({
            url: 'get_patient_med_history.php',
            type: 'POST',
                data: {
                    patient_name: patientName
                },
            success: function(response) {
                let historyHtml = '<div class="mb-4 flex justify-between items-center"><strong>Medication History</strong><div class="relative w-64"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="ri-search-line text-gray-400"></i></div><input type="text" id="medHistorySearch" class="block w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Search medication history..."><button id="clearMedHistorySearch" type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden"><i class="ri-close-line"></i></button></div></div>';
                historyHtml += '<div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dosage</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th></tr></thead><tbody class="bg-white divide-y divide-gray-200" id="medHistoryTableBody">';
                if (response && response.length > 0) {
                    // Show first 5 entries
                    const entriesToShow = response.slice(0, 5);
                    entriesToShow.forEach(function(item) {
                        historyHtml += `<tr><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.prescription_date}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.medicine}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.dosage}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.quantity}</td></tr>`;
                    });
                } else {
                    historyHtml += '<tr><td colspan="4" class="px-4 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medication history found</p><p class="text-gray-400 text-xs">No previous prescriptions recorded</p></div></td></tr>';
                }
                historyHtml += '</tbody></table></div>';
                
                // Add pagination (always show, even for 1 page)
                if (response && response.length > 0) {
                    const totalPages = Math.ceil(response.length / 5);
                    historyHtml += '<div class="mt-3 flex justify-between items-center"><div class="text-xs text-gray-600">Showing 1 to 5 of ' + response.length + ' entries</div><nav class="flex justify-end items-center -space-x-px" id="medHistoryPagination">';
                    
                    // Previous button
                    historyHtml += `
                        <button type="button" disabled 
                                class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                aria-label="Previous">
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"></path>
                            </svg>
                            <span class="sr-only">Previous</span>
                        </button>
                    `;
                    
                    // Page numbers with ellipses
                    const startPage = Math.max(1, 1 - 2);
                    const endPage = Math.min(totalPages, 1 + 2);
                    
                    // Add ellipsis at the beginning if needed
                    if (startPage > 1) {
                        historyHtml += `
                            <button onclick="goToMedHistoryPage(1)" 
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                1
                            </button>
                        `;
                        if (startPage > 2) {
                            historyHtml += `
                                <span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">
                                    ...
                                </span>
                            `;
                        }
                    }
                    
                    // Add page numbers
                    for (let i = startPage; i <= endPage; i++) {
                        if (i === 1) {
                            historyHtml += `
                                <button type="button" 
                                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-gray-200 text-gray-800 focus:outline-hidden focus:bg-gray-300">
                                    ${i}
                                </button>
                            `;
                } else {
                            historyHtml += `
                                <button onclick="goToMedHistoryPage(${i})" 
                                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                    ${i}
                                </button>
                            `;
                        }
                    }
                    
                    // Add ellipsis at the end if needed
                    if (endPage < totalPages) {
                        if (endPage < totalPages - 1) {
                            historyHtml += `
                                <span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">
                                    ...
                                </span>
                            `;
                        }
                        historyHtml += `
                            <button onclick="goToMedHistoryPage(${totalPages})" 
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                ${totalPages}
                            </button>
                        `;
                    }
                    
                    // Next button - always present
                    historyHtml += `
                        <button type="button" disabled 
                                class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                aria-label="Next">
                            <span class="sr-only">Next</span>
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                    `;
                    
                    historyHtml += '</nav></div>';
                }
                historyHtml += '</div>';
                // Store original data for pagination
                $('#prescribeMedForm .med-history').remove();
                // Store the original data for pagination
                const medHistoryDiv = $(`<div class='med-history'>${historyHtml}</div>`);
                medHistoryDiv.data('originalData', response);
                $('#prescribeMedForm').prepend(medHistoryDiv);
                // Move Notes field just below med history
                const notesDiv = $('#prescribeMedForm textarea').closest('.mb-4');
                notesDiv.insertAfter($('#prescribeMedForm .med-history'));
                // Insert Next button after Notes if not present
                if ($('#prescribeMedForm #nextToMedsBtn').length === 0) {
                    notesDiv.after('<button type="button" id="nextToMedsBtn" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90 mb-4">Prescribe Meds</button>');
                }
                // Hide medsList and addMedRowBtn initially
                $('#medsList, #addMedRowBtn').hide();
            },
            error: function() {
                $('#prescribeMedForm .med-history').remove();
                $('#prescribeMedForm').prepend('<div class="med-history mb-4 text-red-500">Unable to load medication history.</div>');
                const notesDiv = $('#prescribeMedForm textarea').closest('.mb-4');
                notesDiv.insertAfter($('#prescribeMedForm .med-history'));
                if ($('#prescribeMedForm #nextToMedsBtn').length === 0) {
                    notesDiv.after('<button type="button" id="nextToMedsBtn" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90 mb-4">Prescribe Meds</button>');
                }
                $('#medsList, #addMedRowBtn').hide();
            }
        });
    });

    // Faculty prescribe button: reuse the same prescribe modal flow
    $('#facultyPrescribeMedBtn').on('click', function() {
        // Mirror student flow
        $('#prescribeMedModal').removeClass('hidden');
        // Lock body scroll
        $('body, html').addClass('overflow-hidden');
        
        // Reset modal to initial state
        resetPrescribeModal();
        // Load history for current faculty (currentPatientName is set on modal open)
        const patientName = currentPatientName;
        $.ajax({
            url: 'get_patient_med_history.php',
            type: 'POST',
            data: { patient_name: patientName },
            success: function(response) {
                $('#prescribeMedForm .med-history').remove();
                let tableHtml = '<div class="mb-4 flex justify-between items-center"><strong>Medication History</strong><div class="relative w-64"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="ri-search-line text-gray-400"></i></div><input type="text" id="medHistorySearch3" class="block w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Search medication history..."><button id="clearMedHistorySearch3" type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden"><i class="ri-close-line"></i></button></div></div>';
                tableHtml += '<div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dosage</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th></tr></thead><tbody class="bg-white divide-y divide-gray-200" id="medHistoryTableBody3">';
                if (response && response.length > 0) {
                    // Show first 5 entries
                    const entriesToShow = response.slice(0, 5);
                    entriesToShow.forEach(function(item) {
                        tableHtml += '<tr><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">' + (item.prescription_date || '') + '</td>' +
                                     '<td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">' + (item.medicine || '') + '</td>' +
                                     '<td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">' + (item.dosage || '') + '</td>' +
                                     '<td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">' + (item.quantity || '') + '</td></tr>';
                    });
                } else {
                    tableHtml += '<tr><td colspan="4" class="px-4 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medication history found</p><p class="text-gray-400 text-xs">No previous prescriptions recorded</p></div></td></tr>';
                }
                tableHtml += '</tbody></table></div>';
                
                // Add pagination (always show, even for 1 page)
                if (response && response.length > 0) {
                    const totalPages = Math.ceil(response.length / 5);
                    tableHtml += '<div class="mt-3 flex justify-between items-center"><div class="text-xs text-gray-600">Showing 1 to 5 of ' + response.length + ' entries</div><nav class="flex justify-end items-center -space-x-px" id="medHistoryPagination3">';
                    
                    // Previous button
                    tableHtml += `
                        <button type="button" disabled 
                                class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                aria-label="Previous">
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m15 18-6-6 6-6"></path>
                            </svg>
                            <span class="sr-only">Previous</span>
                        </button>
                    `;
                    
                    // Page numbers with ellipses
                    const startPage = Math.max(1, 1 - 2);
                    const endPage = Math.min(totalPages, 1 + 2);
                    
                    // Add ellipsis at the beginning if needed
                    if (startPage > 1) {
                        tableHtml += `
                            <button onclick="goToMedHistoryPage(1)" 
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                1
                            </button>
                        `;
                        if (startPage > 2) {
                            tableHtml += `
                                <span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">
                                    ...
                                </span>
                            `;
                        }
                    }
                    
                    // Add page numbers
                    for (let i = startPage; i <= endPage; i++) {
                        if (i === 1) {
                            tableHtml += `
                                <button type="button" 
                                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-gray-200 text-gray-800 focus:outline-hidden focus:bg-gray-300">
                                    ${i}
                                </button>
                            `;
                        } else {
                            tableHtml += `
                                <button onclick="goToMedHistoryPage(${i})" 
                                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                    ${i}
                                </button>
                            `;
                        }
                    }
                    
                    // Add ellipsis at the end if needed
                    if (endPage < totalPages) {
                        if (endPage < totalPages - 1) {
                            tableHtml += `
                                <span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">
                                    ...
                                </span>
                            `;
                        }
                        tableHtml += `
                            <button onclick="goToMedHistoryPage(${totalPages})" 
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                ${totalPages}
                            </button>
                        `;
                    }
                    
                    // Next button - always present
                    tableHtml += `
                        <button type="button" disabled 
                                class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                aria-label="Next">
                            <span class="sr-only">Next</span>
                            <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m9 18 6-6-6-6"></path>
                            </svg>
                        </button>
                    `;
                    
                    tableHtml += '</nav></div>';
                }
                tableHtml += '</div>';
                // Store the original data for pagination
                const medHistoryDiv = $('<div class="med-history">' + tableHtml + '</div>');
                medHistoryDiv.data('originalData', response);
                $('#prescribeMedForm').prepend(medHistoryDiv);
                const notesDiv = $('#prescribeMedForm textarea').closest('.mb-4');
                notesDiv.insertAfter($('#prescribeMedForm .med-history'));
                if ($('#prescribeMedForm #nextToMedsBtn').length === 0) {
                    notesDiv.after('<button type="button" id="nextToMedsBtn" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90 mb-4">Prescribe Meds</button>');
                }
            },
            error: function() {
                $('#prescribeMedForm .med-history').remove();
                $('#prescribeMedForm').prepend('<div class="med-history mb-4 text-red-500">Unable to load medication history.</div>');
                const notesDiv = $('#prescribeMedForm textarea').closest('.mb-4');
                notesDiv.insertAfter($('#prescribeMedForm .med-history'));
                if ($('#prescribeMedForm #nextToMedsBtn').length === 0) {
                    notesDiv.after('<button type="button" id="nextToMedsBtn" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90 mb-4">Prescribe Meds</button>');
                }
            }
        });
    });
    // Next button logic: show medicine fields
    $(document).on('click', '#nextToMedsBtn', function() {
        // Remove 'required' from all hidden fields before showing
        $('#medsList .medicineSelect, #medsList input, #medsList textarea').removeAttr('required');
        $('#medsList, #addMedRowBtn').show();
        // Restore 'required' attributes only after showing
        setTimeout(function() {
            $('#medsList .medicineSelect, #medsList input[placeholder], #medsList input.qtyInput').each(function() {
                // Only add required to visible fields
                if ($(this).is(':visible')) {
                    if ($(this).attr('placeholder') === 'e.g. 500mg' || $(this).hasClass('qtyInput') || $(this).is('select')) {
                        $(this).attr('required', 'required');
                    }
                }
            });
        }, 10);
        $(this).hide();
    });
    $('#closePrescribeMedModal').on('click', function() {
        $('#prescribeMedModal').addClass('hidden');
        // Reset modal title
        $('#prescribeMedModal h3').text('Prescribe Medicine');
        $('body, html').removeClass('overflow-hidden');
        // Reset modal content and ensure Prescribe Meds button is always present
        resetPrescribeModal();
    });
    $(window).on('click', function(e) {
        if (e.target === document.getElementById('prescribeMedModal')) {
            $('#prescribeMedModal').addClass('hidden');
            $('body, html').removeClass('overflow-hidden');
            // Reset modal content and ensure Prescribe Meds button is always present
            resetPrescribeModal();
        }
    });
    // Add Medicine Row (no required attributes)
    $('#addMedRowBtn').on('click', function() {
        var newRow = `<div class="medRow mb-4 border-b pb-4">
                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                                <select class="medicineSelect w-full border border-gray-300 rounded px-3 py-2 text-sm">
                                    <option value="">Select medicine</option>
                                </select>
                            </div>
                            <div class="flex gap-2 mb-2">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                                    <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 500mg" list="dosageSuggestions" />
                                    <datalist id="dosageSuggestions">
                                        <?php foreach ($dosageSuggestions as $dosage): ?>
                                            <option value="<?php echo htmlspecialchars($dosage); ?>" />
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                                    <input type="number" class="w-full border border-gray-300 rounded px-3 py-2 text-sm qtyInput" min="1" list="qtySuggestions" />
                                    <datalist id="qtySuggestions">
                                        <?php foreach ($qtySuggestions as $qty): ?>
                                            <option value="<?php echo htmlspecialchars($qty); ?>" />
                                        <?php endforeach; ?>
                                    </datalist>
                                    <span class="text-xs text-gray-500 stockMsg"></span>
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                                <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 3x a day" list="frequencySuggestions" />
                                <datalist id="frequencySuggestions">
                                    <?php $limitedFreq = array_slice($frequencySuggestions, 0, 5); ?>
                                    <?php foreach ($limitedFreq as $freq): ?>
                                        <option value="<?php echo htmlspecialchars($freq); ?>" />
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <div class="mb-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                                <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. After meals" list="instructionsSuggestions" />
                                <datalist id="instructionsSuggestions">
                                    <?php $limitedInst = array_slice($instructionsSuggestions, 0, 5); ?>
                                    <?php foreach ($limitedInst as $inst): ?>
                                        <option value="<?php echo htmlspecialchars($inst); ?>" />
                                    <?php endforeach; ?>
                                </datalist>
                            </div>
                            <button type="button" class="removeMedBtn text-xs text-red-500 hover:underline mt-1">Remove</button>
                        </div>`;
        $('#medsList').append($(newRow));
        
        // Populate medicine options for the new row
        var medicinesData = <?php echo json_encode($medicines); ?>;
        console.log("Medicines array:", medicinesData);
        var newSelect = $('#medsList .medRow:last .medicineSelect');
        medicinesData.forEach(function(med) {
            newSelect.append('<option value="' + med.name + '" data-stock="' + med.quantity + '">' + med.name + ' (' + med.quantity + ' in stock)</option>');
        });
    });
    // Remove Medicine Row
    $(document).on('click', '.removeMedBtn', function() {
        $(this).closest('.medRow').remove();
    });
    
    // Medicine selection change handler - update quantity validation
    $(document).on('change', '.medicineSelect', function() {
        var selectedOption = $(this).find('option:selected');
        var stock = parseInt(selectedOption.data('stock')) || 0;
        var qtyInput = $(this).closest('.medRow').find('.qtyInput');
        var stockMsg = $(this).closest('.medRow').find('.stockMsg');
        
        // Update max attribute
        qtyInput.attr('max', stock);
        if (stock <= 0) {
            qtyInput.val('');
        }
        
        // Clear any existing validation messages
        qtyInput.removeClass('border-red-500').addClass('border-gray-300');
        qtyInput.siblings('.qty-error').remove();
    });
    
    // Quantity input validation - check against stock
    $(document).on('input change', '.qtyInput', function() {
        var qtyInput = $(this);
        var medicineSelect = qtyInput.closest('.medRow').find('.medicineSelect');
        var selectedOption = medicineSelect.find('option:selected');
        var stock = parseInt(selectedOption.data('stock')) || 0;
        var quantity = parseInt(qtyInput.val()) || 0;
        
        // Remove previous error styling
        qtyInput.removeClass('border-red-500').addClass('border-gray-300');
        qtyInput.siblings('.qty-error').remove();
        
        if (quantity > stock) {
            qtyInput.addClass('border-red-500').removeClass('border-gray-300');
            qtyInput.after('<div class="qty-error text-red-500 text-xs mt-1">Quantity cannot exceed stock (' + stock + ')</div>');
            qtyInput.val(stock); // Set to max available
        }
    });
    // Submit Prescription Form (supports Student and Faculty flows)
    $('#prescribeMedForm').on('submit', function(e) {
        e.preventDefault();
        // Clear previous errors and success
        $('#prescribeMedForm .error-msg, #prescribeMedForm .success-msg').remove();
        
        // Validate quantities against stock before submission
        var hasInvalidQuantity = false;
        $('.medRow').each(function() {
            var row = $(this);
            var medicineSelect = row.find('.medicineSelect');
            var qtyInput = row.find('.qtyInput');
            var selectedOption = medicineSelect.find('option:selected');
            var stock = parseInt(selectedOption.data('stock')) || 0;
            var quantity = parseInt(qtyInput.val()) || 0;
            
            if (quantity > stock) {
                hasInvalidQuantity = true;
                qtyInput.addClass('border-red-500').removeClass('border-gray-300');
                qtyInput.siblings('.qty-error').remove();
                qtyInput.after('<div class="qty-error text-red-500 text-xs mt-1">Quantity cannot exceed stock (' + stock + ')</div>');
            }
        });
        
        if (hasInvalidQuantity) {
            alert('Please correct the quantities that exceed available stock before submitting.');
            return;
        }
        
        // Do NOT require any fields in .medRow (all optional)
        var medsData = [];
        $('.medRow').each(function() {
            var row = $(this);
            var med = {
                medicine: row.find('.medicineSelect').val(),
                dosage: row.find('input[placeholder="e.g. 500mg"]').val(),
                quantity: row.find('input.qtyInput').val(),
                frequency: row.find('input[placeholder="e.g. 3x a day"]').val(),
                instructions: row.find('input[placeholder="e.g. After meals"]').val()
            };
            // Add row even if all fields are blank (or skip if you want only non-empty rows)
            if (med.medicine || med.dosage || med.quantity || med.frequency || med.instructions) {
                medsData.push(med);
            }
        });
        var notes = $('#prescribeMedForm textarea').val();
        var reason = $('#prescribeMedForm input[name="reason"]').val();
        // Detect context: Student profile modal or Faculty profile modal
        var patientIdMatch = null;
        var patientName = '';
        if ($('#studentModal').length && !$('#studentModal').hasClass('hidden')) {
            // Student modal
            patientIdMatch = $('#studentModal').find('#modalPatientName').text().match(/\(([^)]+)\)$/);
            patientName = $('#studentModal').find('#modalPatientName').text().replace(/\s*\([^)]*\)$/, '');
        } else if ($('#facultyProfileModal').length && !$('#facultyProfileModal').hasClass('hidden')) {
            // Faculty modal
            var titleTextFac = $('#facultyModalTitle').text();
            patientIdMatch = titleTextFac.match(/\(([^)]+)\)$/);
            patientName = titleTextFac.replace(/\s*\([^)]*\)$/, '');
        } else {
            // Fallback to currentPatientName if set by opener
            if (typeof currentPatientName === 'string' && currentPatientName.trim().length > 0) {
                patientName = currentPatientName.trim();
            }
        }
        var patientId = patientIdMatch ? patientIdMatch[1] : '';
        var patientEmail = $('#prescribeMedForm input[name="patient_email"]').val();
        var parentEmail = $('#prescribeMedForm input[name="parent_email"]').val();
        $.ajax({
            url: 'submit_prescription.php',
            type: 'POST',
            data: {
                patient_id: patientId,
                patient_name: patientName,
                medicines: JSON.stringify(medsData),
                reason: reason,
                notes: notes,
                patient_email: patientEmail,
                parent_email: parentEmail
            },
            success: function(response) {
                // Popup with main page blue color, no blur
                if ($('#prescriptionToast').length) $('#prescriptionToast').remove();
                $('body').append(`
                  <div id="prescriptionToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                    <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                      <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                      <span>Prescription submitted</span>
                    </div>
                  </div>
                `);
                setTimeout(function() {
                        $('#prescriptionToast').fadeOut(300, function() {
                            $(this).remove();
                        });
                    $('#prescribeMedModal').addClass('hidden');
                    $('#studentModal').addClass('hidden');
                    window.location.href = 'records.php';
                }, 1200);
            },
            error: function(xhr, status, error) {
                console.error(error);
                showErrorModal('An error occurred while submitting the prescription. Please try again.', 'Error');
            }
        });
    });

    // Tab switching logic for profile modal
    function showProfileTab(tabId) {
        $('#studentModal .tabContent').addClass('hidden');
        $('#' + tabId).removeClass('hidden');
        $('#studentModalTabs .tabBtn').removeClass('bg-primary text-white').addClass('bg-gray-200 text-gray-700 dark:bg-neutral-600 dark:text-neutral-300');
        $('#studentModalTabs .tabBtn[data-tab="' + tabId + '"]').addClass('bg-primary text-white').removeClass('bg-gray-200 text-gray-700 dark:bg-neutral-600 dark:text-neutral-300');
        
        // Show/hide appropriate action buttons based on active tab
        $('#prescribeMedBtn, #saveVitalsBtn, #saveMedReferralBtn').addClass('hidden');
        if (tabId === 'infoTab') {
            $('#prescribeMedBtn').removeClass('hidden');
        } else if (tabId === 'vitalsTab') {
            $('#saveVitalsBtn').removeClass('hidden');
        } else if (tabId === 'medReferralTab') {
            $('#saveMedReferralBtn').removeClass('hidden');
        }
    }
    
    // Save button handlers
    $('#saveVitalsBtn').on('click', function() {
        $('#vitalsForm').submit();
    });
    
    $('#saveMedReferralBtn').on('click', function() {
        $('#medReferralForm').submit();
    });
    // Tab button click
    $('#studentModalTabs .tabBtn').on('click', function() {
        const tabId = $(this).data('tab');
        showProfileTab(tabId);
        
        // Load patient history when switching to vitals or med referral tabs
        if (tabId === 'vitalsTab') {
            loadPatientHistory(); // This will auto-populate vital signs form
        } else if (tabId === 'medReferralTab') {
            loadPatientHistory(); // This will show medication referral history
        }
    });

    // Function to load patient history and populate forms
    function loadPatientHistory() {
        var patientInfo = $('#modalPatientName').text();
        var patientId = patientInfo.match(/\(([^)]+)\)$/);
        var patientName = patientInfo.replace(/\s*\([^)]*\)$/, '');
        
        // Clear all vital signs form fields first
        $('#vitalsForm input[name="weight"]').val('');
        $('#vitalsForm input[name="height"]').val('');
        $('#vitalsForm input[name="body_temp"]').val('');
        $('#vitalsForm input[name="resp_rate"]').val('');
        $('#vitalsForm input[name="pulse"]').val('');
        $('#vitalsForm input[name="blood_pressure"]').val('');
        $('#vitalsForm input[name="oxygen_sat"]').val('');
        $('#vitalsForm textarea[name="remarks"]').val('');
        $('#vitalsForm input[name="vital_date"]').val(new Date().toISOString().split('T')[0]);
        
        $.ajax({
            url: 'get_patient_records.php',
            type: 'POST',
            data: {
                patient_id: patientId ? patientId[1] : '',
                patient_name: patientName
            },
            dataType: 'json',
            success: function(response) {
                // Only populate vital signs form if this specific patient has records
                if (response.vital_signs && response.vital_signs.length > 0) {
                    const latestVitals = response.vital_signs[0]; // First item is the most recent
                    
                    // Populate form fields with latest vital signs for THIS patient only
                    if (latestVitals.weight) $('#vitalsForm input[name="weight"]').val(latestVitals.weight);
                    if (latestVitals.height) $('#vitalsForm input[name="height"]').val(latestVitals.height);
                    if (latestVitals.body_temp) $('#vitalsForm input[name="body_temp"]').val(latestVitals.body_temp);
                    if (latestVitals.resp_rate) $('#vitalsForm input[name="resp_rate"]').val(latestVitals.resp_rate);
                    if (latestVitals.pulse) $('#vitalsForm input[name="pulse"]').val(latestVitals.pulse);
                    if (latestVitals.blood_pressure) $('#vitalsForm input[name="blood_pressure"]').val(latestVitals.blood_pressure);
                    if (latestVitals.oxygen_sat) $('#vitalsForm input[name="oxygen_sat"]').val(latestVitals.oxygen_sat);
                    if (latestVitals.remarks) $('#vitalsForm textarea[name="remarks"]').val(latestVitals.remarks);
                    if (latestVitals.vital_date) $('#vitalsForm input[name="vital_date"]').val(latestVitals.vital_date);
                }
                // If no previous data for this patient, fields remain blank (already cleared above)
                
                // Display medication referral history for this specific patient
                if (response.medication_referrals && response.medication_referrals.length > 0) {
                    let referralHtml = '<div class="mb-4 flex justify-between items-center"><strong>Previous Medication Referrals</strong><div class="relative w-64"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="ri-search-line text-gray-400"></i></div><input type="text" id="medReferralHistorySearch" class="block w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Search referral history..."><button id="clearMedReferralHistorySearch" type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden"><i class="ri-close-line"></i></button></div></div>';
                    referralHtml += '<div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assessment</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th></tr></thead><tbody class="bg-white divide-y divide-gray-200" id="medReferralHistoryTableBody">';
                    
                    // Show first 5 entries
                    const entriesToShow = response.medication_referrals.slice(0, 5);
                    entriesToShow.forEach(function(referral) {
                        referralHtml += `<tr>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${referral.created_at ? new Date(referral.created_at).toLocaleDateString() : 'No date'}</td>
                            <td class="px-4 py-2 text-xs text-gray-900">${referral.assessment || 'N/A'}</td>
                            <td class="px-4 py-2 text-xs text-gray-900">${referral.plan || 'N/A'}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${referral.recorded_by || 'Staff'}</td>
                        </tr>`;
                    });
                    referralHtml += '</tbody></table></div>';
                    
                    // Add pagination (always show, even for 1 page)
                    if (response.medication_referrals.length > 0) {
                        const totalPages = Math.ceil(response.medication_referrals.length / 5);
                        referralHtml += '<div class="mt-3 flex justify-between items-center px-6 py-4"><div class="text-xs text-gray-600">Showing 1 to 5 of ' + response.medication_referrals.length + ' entries</div><nav class="flex justify-end items-center -space-x-px" id="medReferralHistoryPagination">';
                        
                        // Previous button
                        referralHtml += `
                            <button type="button" disabled 
                                class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                aria-label="Previous">
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m15 18-6-6 6-6"></path>
                                </svg>
                                <span class="sr-only">Previous</span>
                            </button>`;
                        
                        // Page numbers
                        for (let i = 1; i <= totalPages; i++) {
                            if (i === 1) {
                                referralHtml += `<button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300" aria-current="page">${i}</button>`;
                            } else {
                                referralHtml += `<button type="button" data-page="${i}" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">${i}</button>`;
                            }
                        }
                        
                        // Next button
                        if (totalPages > 1) {
                            referralHtml += `
                                <button type="button" data-page="2"
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" 
                                    aria-label="Next">
                                    <span class="sr-only">Next</span>
                                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </button>`;
                        } else {
                            referralHtml += `
                                <button type="button" disabled 
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                    aria-label="Next">
                                    <span class="sr-only">Next</span>
                                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </button>`;
                        }
                        
                        referralHtml += '</nav></div>';
                    }
                    
                    $('#medReferralHistoryContent').html(referralHtml);
                    // Store original data for search functionality
                    $('#medReferralHistoryContent').data('originalData', response.medication_referrals);
                } else {
                    $('#medReferralHistoryContent').html('<div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assessment</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><tr><td colspan="4" class="px-4 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-file-list-3-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medication referrals found</p><p class="text-gray-400 text-xs">No previous referrals recorded</p></div></td></tr></tbody></table></div>');
                }
            },
            error: function(xhr, status, error) {
                // If error, keep fields blank and just set today's date
                $('#vitalsForm input[name="vital_date"]').val(new Date().toISOString().split('T')[0]);
                $('#medReferralHistoryContent').html('<p class="text-center text-red-400 text-xs">Error loading medication referral history.</p>');
            }
        });
    }

    // Vital Signs Form Submission
    $('#vitalsForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('#vitalsForm .error-msg, #vitalsForm .success-msg').remove();
        
        // Get patient info from modal
        var patientInfo = $('#modalPatientName').text();
        var patientId = patientInfo.match(/\(([^)]+)\)$/);
        var patientName = patientInfo.replace(/\s*\([^)]*\)$/, '');
        
        // Get form data
        var formData = {
            patient_id: patientId ? patientId[1] : '',
            patient_name: patientName,
            vital_date: $(this).find('input[name="vital_date"]').val(),
            weight: $(this).find('input[name="weight"]').val(),
            height: $(this).find('input[name="height"]').val(),
            body_temp: $(this).find('input[name="body_temp"]').val(),
            resp_rate: $(this).find('input[name="resp_rate"]').val(),
            pulse: $(this).find('input[name="pulse"]').val(),
            blood_pressure: $(this).find('input[name="blood_pressure"]').val(),
            oxygen_sat: $(this).find('input[name="oxygen_sat"]').val(),
            remarks: $(this).find('textarea[name="remarks"]').val()
        };
        
        $.ajax({
            url: 'save_vital_signs.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove any previous popup
                    if ($('#vitalsToast').length) $('#vitalsToast').remove();
                    $('body').append(`
                      <div id="vitalsToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                        <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                          <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                          <span>Vital signs saved</span>
                        </div>
                      </div>
                    `);
                    // Don't reset form - keep the data in the fields
                    setTimeout(function() {
                            $('#vitalsToast').fadeOut(300, function() {
                                $(this).remove();
                                // Refresh the page after toast disappears
                                window.location.reload();
                            });
                    }, 1200);
                } else {
                    showErrorModal('Error: ' + response.message, 'Error');
                }
            },
            error: function(xhr, status, error) {
                showErrorModal('An error occurred while saving vital signs. Please try again.', 'Error');
            }
        });
    });

    // Medication Referral Form Submission
    $('#medReferralForm').on('submit', function(e) {
        e.preventDefault();
        
        // Clear previous messages
        $('#medReferralForm .error-msg, #medReferralForm .success-msg').remove();
        
        // Get patient info from modal
        var patientInfo = $('#modalPatientName').text();
        var patientId = patientInfo.match(/\(([^)]+)\)$/);
        var patientName = patientInfo.replace(/\s*\([^)]*\)$/, '');
        
        // Get form data
            var formData = {
                patient_id: patientId ? patientId[1] : '',
                patient_name: patientName,
                subjective: $(this).find('textarea[name="subjective"]').val(),
                objective: $(this).find('textarea[name="objective"]').val(),
                assessment: $(this).find('textarea[name="assessment"]').val(),
                plan: $(this).find('textarea[name="plan"]').val(),
                intervention: $(this).find('textarea[name="intervention"]').val(),
                evaluation: $(this).find('textarea[name="evaluation"]').val(),
                referral_to: $(this).find('input[name="referral_to"]').val(),
                entity_type: 'patient'
            };
        
        $.ajax({
            url: 'save_medication_referral.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove any previous popup
                    if ($('#medReferralToast').length) $('#medReferralToast').remove();
                    $('body').append(`
                      <div id="medReferralToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                        <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                          <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                          <span>Medication referral saved</span>
                        </div>
                      </div>
                    `);
                    // Reset form
                    $('#medReferralForm')[0].reset();
                    setTimeout(function() {
                            $('#medReferralToast').fadeOut(300, function() {
                                $(this).remove();
                            });
                    }, 1200);
                } else {
                    showErrorModal('Error: ' + response.message, 'Error');
                }
            },
            error: function(xhr, status, error) {
                showErrorModal('An error occurred while saving medication referral. Please try again.', 'Error');
            }
        });
    });

        // Visitor Modal functionality
        const visitorProfileModal = document.getElementById('visitorProfileModal');
        const closeVisitorProfileModal = document.getElementById('closeVisitorProfileModal');

        function openVisitorModal() { visitorProfileModal.classList.remove('hidden'); $('body, html').addClass('overflow-hidden'); }
        function closeVisitorModal() { visitorProfileModal.classList.add('hidden'); $('body, html').removeClass('overflow-hidden'); }

        closeVisitorProfileModal.addEventListener('click', closeVisitorModal);
        window.addEventListener('click', (e) => { if (e.target === visitorProfileModal) closeVisitorModal(); });

        // View visitor button logic - match student modal layout
        $(document).on('click', '.viewVisitorBtn', function() {
            const id = $(this).data('id') || 'N/A';
            const name = $(this).data('name') || 'N/A';
            const age = $(this).data('age') || 'N/A';
            const gender = $(this).data('gender') || 'N/A';
            const address = $(this).data('address') || 'N/A';
            const contact = $(this).data('contact') || 'N/A';
        const emergencyContact = $(this).data('emergency-contact') || 'N/A';

            $('#visitorModalTitle').text(name + ' (' + id + ')');
            $('#visitorModalDetails').html(`
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Personal Information Section -->
                        <div>
                            <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Personal Information</h4>
                            <div class="space-y-3">
                        <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Visitor ID:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${id || 'N/A'}</p>
                        </div>
                        <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Age:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${age || 'N/A'}</p>
                        </div>
                        <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                    <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Gender:</label>
                                    <p class="text-sm text-gray-900 dark:text-neutral-200">${gender || 'N/A'}</p>
                        </div>
                        </div>
                    </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Contact Information Section -->
                        <div>
                            <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Contact Information</h4>
                            <div class="space-y-3">
                        <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Contact Number:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${contact || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Emergency Contact:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${emergencyContact || 'N/A'}</p>
                        </div>
                        <div class="grid grid-cols-[140px_1fr] gap-3 items-start">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Address:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${address || 'N/A'}</p>
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            `);

            // Default to Information tab
            showVisitorTab('visitorInfoTab');
            openVisitorModal();
        });

        // Visitor tab switching
        function showVisitorTab(tabId) {
            $('#visitorProfileModal .tabContent').addClass('hidden');
            $('#' + tabId).removeClass('hidden');
            $('#visitorProfileModalTabs .tabBtnVisitor').removeClass('bg-primary text-white').addClass('bg-gray-200 text-gray-700 dark:bg-neutral-600 dark:text-neutral-300');
            $('#visitorProfileModalTabs .tabBtnVisitor[data-tab="' + tabId + '"]').addClass('bg-primary text-white').removeClass('bg-gray-200 text-gray-700 dark:bg-neutral-600 dark:text-neutral-300');
            // Show/hide visitor footer buttons
            $('#visitorPrescribeMedBtn, #saveVisitorVitalsBtn, #saveVisitorMedReferralBtn').addClass('hidden');
            if (tabId === 'visitorInfoTab') {
                $('#visitorPrescribeMedBtn').removeClass('hidden');
            } else if (tabId === 'visitorVitalsTab') {
                $('#saveVisitorVitalsBtn').removeClass('hidden');
            } else if (tabId === 'visitorMedReferralTab') {
                $('#saveVisitorMedReferralBtn').removeClass('hidden');
            }
        }

        $('#visitorProfileModalTabs .tabBtnVisitor').on('click', function() {
            const tabId = $(this).data('tab');
            showVisitorTab(tabId);
            if (tabId === 'visitorVitalsTab' || tabId === 'visitorMedReferralTab') {
                loadVisitorHistory();
            }
        });

        // Visitor Prescribe Medicine modal logic
        let currentVisitorName = '';
        $(document).on('click', '.viewVisitorBtn', function() {
            currentVisitorName = $(this).data('name') || '';
        });
        $('#visitorPrescribeMedBtn').on('click', function() {
            $('#visitorPrescribeMedModal').removeClass('hidden');
            $('#visitorPrescribeMedModal h3').text('Prescribe Medicine for ' + currentVisitorName);
            $('body, html').addClass('overflow-hidden');
            
            // Reset modal to initial state
            resetVisitorPrescribeModal();
            // Fetch and display medication history for this visitor (reuse patient endpoint by name)
            const visitorName = currentVisitorName;
            $.ajax({
                url: 'get_patient_med_history.php',
                type: 'POST',
                data: { patient_name: visitorName },
                success: function(response) {
                    // Build table HTML same as student flow
                    $('#visitorPrescribeMedForm .med-history').remove();
                    let tableHtml = '<div class="mb-4 flex justify-between items-center"><strong>Medication History</strong><div class="relative w-64"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="ri-search-line text-gray-400"></i></div><input type="text" id="medHistorySearch4" class="block w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Search medication history..."><button id="clearMedHistorySearch4" type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden"><i class="ri-close-line"></i></button></div></div>';
                    tableHtml += '<div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dosage</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th></tr></thead><tbody class="bg-white divide-y divide-gray-200" id="medHistoryTableBody4">';
                    if (response && response.length > 0) {
                        // Show first 5 entries
                        const entriesToShow = response.slice(0, 5);
                        entriesToShow.forEach(function(item) {
                            tableHtml += `<tr><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.prescription_date}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.medicine}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.dosage}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.quantity}</td></tr>`;
                        });
                    } else {
                        tableHtml += '<tr><td colspan="4" class="px-4 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-medicine-bottle-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medication history found</p><p class="text-gray-400 text-xs">No previous prescriptions recorded</p></div></td></tr>';
                    }
                    tableHtml += '</tbody></table></div>';
                    
                    // Add pagination (always show, even for 1 page)
                    if (response && response.length > 0) {
                        const totalPages = Math.ceil(response.length / 5);
                        tableHtml += '<div class="mt-3 flex justify-between items-center"><div class="text-xs text-gray-600">Showing 1 to 5 of ' + response.length + ' entries</div><nav class="flex justify-end items-center -space-x-px" id="medHistoryPagination4">';
                        
                        // Previous button
                        tableHtml += `
                            <button type="button" disabled 
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                    aria-label="Previous">
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m15 18-6-6 6-6"></path>
                                </svg>
                                <span class="sr-only">Previous</span>
                            </button>
                        `;
                        
                        // Page numbers with ellipses
                        const startPage = Math.max(1, 1 - 2);
                        const endPage = Math.min(totalPages, 1 + 2);
                        
                        // Add ellipsis at the beginning if needed
                        if (startPage > 1) {
                            tableHtml += `
                                <button onclick="goToMedHistoryPage(1)" 
                                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                    1
                                </button>
                            `;
                            if (startPage > 2) {
                                tableHtml += `
                                    <span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">
                                        ...
                                    </span>
                                `;
                            }
                        }
                        
                        // Add page numbers
                        for (let i = startPage; i <= endPage; i++) {
                            if (i === 1) {
                                tableHtml += `
                                    <button type="button" 
                                            class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-gray-200 text-gray-800 focus:outline-hidden focus:bg-gray-300">
                                        ${i}
                                    </button>
                                `;
                            } else {
                                tableHtml += `
                                    <button onclick="goToMedHistoryPage(${i})" 
                                            class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                        ${i}
                                    </button>
                                `;
                            }
                        }
                        
                        // Add ellipsis at the end if needed
                        if (endPage < totalPages) {
                            if (endPage < totalPages - 1) {
                                tableHtml += `
                                    <span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">
                                        ...
                                    </span>
                                `;
                            }
                            tableHtml += `
                                <button onclick="goToMedHistoryPage(${totalPages})" 
                                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                                    ${totalPages}
                                </button>
                            `;
                        }
                        
                        // Next button - always present
                        tableHtml += `
                            <button type="button" disabled 
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                    aria-label="Next">
                                <span class="sr-only">Next</span>
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6"></path>
                                </svg>
                            </button>
                        `;
                        
                        tableHtml += '</nav></div>';
                    }
                    tableHtml += '</div>';
                    // Store the original data for pagination
                    const medHistoryDiv = $(`<div class='med-history'>${tableHtml}</div>`);
                    medHistoryDiv.data('originalData', response);
                    $('#visitorPrescribeMedForm').prepend(medHistoryDiv);
                    // Move Notes under history
                    const notesDiv = $('#visitorPrescribeMedForm textarea').closest('.mb-4');
                    notesDiv.insertAfter($('#visitorPrescribeMedForm .med-history'));
                    // Add Next button if missing
                    if ($('#visitorPrescribeMedForm #visitorNextToMedsBtn').length === 0) {
                        notesDiv.after('<button type="button" id="visitorNextToMedsBtn" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90 mb-4">Prescribe Meds</button>');
                    }
                    // Hide meds initially
                    $('#visitorMedsList, #visitorAddMedRowBtn').hide();
                },
                error: function() {
                    $('#visitorPrescribeMedForm .med-history').remove();
                    $('#visitorPrescribeMedForm').prepend('<div class="med-history mb-4 text-red-500">Unable to load medication history.</div>');
                    const notesDiv = $('#visitorPrescribeMedForm textarea').closest('.mb-4');
                    notesDiv.insertAfter($('#visitorPrescribeMedForm .med-history'));
                    if ($('#visitorPrescribeMedForm #visitorNextToMedsBtn').length === 0) {
                        notesDiv.after('<button type="button" id="visitorNextToMedsBtn" class="w-full bg-primary text-white py-2 rounded hover:bg-primary/90 mb-4">Prescribe Meds</button>');
                    }
                    $('#visitorMedsList, #visitorAddMedRowBtn').hide();
                }
            });
        });
        $('#closeVisitorPrescribeMedModal').on('click', function() {
            $('#visitorPrescribeMedModal').addClass('hidden');
            $('body, html').removeClass('overflow-hidden');
            // Reset modal content and ensure Prescribe Meds button is always present
            resetVisitorPrescribeModal();
        });
        $(window).on('click', function(e) {
            if (e.target === document.getElementById('visitorPrescribeMedModal')) {
                $('#visitorPrescribeMedModal').addClass('hidden');
                $('body, html').removeClass('overflow-hidden');
                // Reset modal content and ensure Prescribe Meds button is always present
                resetVisitorPrescribeModal();
            }
        });
        // Add medicine row for visitor
        $('#visitorAddMedRowBtn').on('click', function() {
            const newRow = `
                <div class="medRow mb-4 border-b pb-4">
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Medicine</label>
                        <select class="medicineSelect w-full border border-gray-300 rounded px-3 py-2 text-sm">
                            <option value="">Select medicine</option>
                            <?php foreach ($medicines as $med): ?>
                                <option value="<?php echo htmlspecialchars($med['name']); ?>" data-stock="<?php echo htmlspecialchars($med['quantity']); ?>">
                                    <?php echo htmlspecialchars($med['name']); ?> (<?php echo htmlspecialchars($med['quantity']); ?> in stock)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="flex gap-2 mb-2">
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Dosage</label>
                            <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 500mg" list="dosageSuggestions" />
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quantity</label>
                            <input type="number" class="w-full border border-gray-300 rounded px-3 py-2 text-sm qtyInput" min="1" list="qtySuggestions" />
                            <span class="text-xs text-gray-500 stockMsg"></span>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                        <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. 3x a day" list="frequencySuggestions" />
                    </div>
                    <div class="mb-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Instructions</label>
                        <input type="text" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="e.g. After meals" list="instructionsSuggestions" />
                    </div>
                    <button type="button" class="removeMedBtn text-xs text-red-500 hover:underline mt-1">Remove</button>
                </div>`;
            $('#visitorMedsList').append($(newRow));
        });
        // Visitor medicine selection change handler - update quantity validation
        $(document).on('change', '#visitorPrescribeMedForm .medicineSelect', function() {
            var selectedOption = $(this).find('option:selected');
            var stock = parseInt(selectedOption.data('stock')) || 0;
            var qtyInput = $(this).closest('.medRow').find('.qtyInput');
            var stockMsg = $(this).closest('.medRow').find('.stockMsg');
            
            // Update max attribute
            qtyInput.attr('max', stock);
            if (stock <= 0) {
                qtyInput.val('');
            }
            
            // Clear any existing validation messages
            qtyInput.removeClass('border-red-500').addClass('border-gray-300');
            qtyInput.siblings('.qty-error').remove();
        });
        
        // Visitor quantity input validation - check against stock
        $(document).on('input change', '#visitorPrescribeMedForm .qtyInput', function() {
            var qtyInput = $(this);
            var medicineSelect = qtyInput.closest('.medRow').find('.medicineSelect');
            var selectedOption = medicineSelect.find('option:selected');
            var stock = parseInt(selectedOption.data('stock')) || 0;
            var quantity = parseInt(qtyInput.val()) || 0;
            
            // Remove previous error styling
            qtyInput.removeClass('border-red-500').addClass('border-gray-300');
            qtyInput.siblings('.qty-error').remove();
            
            if (quantity > stock) {
                qtyInput.addClass('border-red-500').removeClass('border-gray-300');
                qtyInput.after('<div class="qty-error text-red-500 text-xs mt-1">Quantity cannot exceed stock (' + stock + ')</div>');
                qtyInput.val(stock); // Set to max available
            }
        });
        
        // Submit visitor prescription form
        $('#visitorPrescribeMedForm').on('submit', function(e) {
            e.preventDefault();
            
            // Validate quantities against stock before submission
            var hasInvalidQuantity = false;
            $('#visitorMedsList .medRow').each(function() {
                var row = $(this);
                var medicineSelect = row.find('.medicineSelect');
                var qtyInput = row.find('.qtyInput');
                var selectedOption = medicineSelect.find('option:selected');
                var stock = parseInt(selectedOption.data('stock')) || 0;
                var quantity = parseInt(qtyInput.val()) || 0;
                
                if (quantity > stock) {
                    hasInvalidQuantity = true;
                    qtyInput.addClass('border-red-500').removeClass('border-gray-300');
                    qtyInput.siblings('.qty-error').remove();
                    qtyInput.after('<div class="qty-error text-red-500 text-xs mt-1">Quantity cannot exceed stock (' + stock + ')</div>');
                }
            });
            
            if (hasInvalidQuantity) {
                alert('Please correct the quantities that exceed available stock before submitting.');
                return;
            }
            
            var medsData = [];
            $('#visitorMedsList .medRow').each(function() {
                var row = $(this);
                var med = {
                    medicine: row.find('.medicineSelect').val(),
                    dosage: row.find('input[placeholder="e.g. 500mg"]').val(),
                    quantity: row.find('input.qtyInput').val(),
                    frequency: row.find('input[placeholder="e.g. 3x a day"]').val(),
                    instructions: row.find('input[placeholder="e.g. After meals"]').val()
                };
                if (med.medicine || med.dosage || med.quantity || med.frequency || med.instructions) {
                    medsData.push(med);
                }
            });
            var notes = $('#visitorPrescribeMedForm textarea').val();
            var reason = $('#visitorPrescribeMedForm input[name="reason"]').val();
            var titleText = $('#visitorModalTitle').text();
            var idMatch = titleText.match(/\(([^)]+)\)$/);
            var visitorId = idMatch ? idMatch[1] : '';
            var visitorName = titleText.replace(/\s*\([^)]*\)$/,'');
            var recipientEmail = $('#visitorPrescribeMedForm input[name="recipient_email"]').val();

            $.ajax({
                url: 'submit_prescription.php',
                type: 'POST',
                data: {
                    patient_id: visitorId,
                    patient_name: visitorName,
                    medicines: JSON.stringify(medsData),
                    reason: reason,
                    notes: notes,
                    patient_email: recipientEmail
                },
                success: function(response) {
                    if ($('#prescriptionToast').length) $('#prescriptionToast').remove();
                    $('body').append(`
                      <div id="prescriptionToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                        <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                          <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                          <span>Prescription submitted</span>
                        </div>
                      </div>
                    `);
                    setTimeout(function() {
                        $('#prescriptionToast').fadeOut(300, function() { $(this).remove(); });
                        $('#visitorPrescribeMedModal').addClass('hidden');
                        $('#visitorProfileModal').addClass('hidden');
                        window.location.href = 'records.php';
                    }, 1200);
                },
                error: function() {
                    showErrorModal('An error occurred while submitting the prescription. Please try again.', 'Error');
                }
            });
        });

        // Reveal visitor meds section after Next button
        $(document).on('click', '#visitorNextToMedsBtn', function() {
            $('#visitorMedsList, #visitorAddMedRowBtn').show();
            $(this).remove();
        });

        // Load visitor history and populate vitals form
        function loadVisitorHistory() {
            var titleText = $('#visitorModalTitle').text();
            var idMatch = titleText.match(/\(([^)]+)\)$/);
            var visitorId = idMatch ? idMatch[1] : '';
            var visitorName = titleText.replace(/\s*\([^)]*\)$/, '');

            // Clear form first
            $('#visitorVitalsForm input[name="weight"]').val('');
            $('#visitorVitalsForm input[name="height"]').val('');
            $('#visitorVitalsForm input[name="body_temp"]').val('');
            $('#visitorVitalsForm input[name="resp_rate"]').val('');
            $('#visitorVitalsForm input[name="pulse"]').val('');
            $('#visitorVitalsForm input[name="blood_pressure"]').val('');
            $('#visitorVitalsForm input[name="oxygen_sat"]').val('');
            $('#visitorVitalsForm textarea[name="remarks"]').val('');
            $('#visitorVitalsForm input[name="vital_date"]').val(new Date().toISOString().split('T')[0]);

            $.ajax({
                url: 'get_visitor_records.php',
                type: 'POST',
                data: {
                    visitor_id: visitorId,
                    visitor_name: visitorName
                },
                dataType: 'json',
                success: function(response) {
                    if (response.vital_signs && response.vital_signs.length > 0) {
                        const latestVitals = response.vital_signs[0];
                        if (latestVitals.weight) $('#visitorVitalsForm input[name="weight"]').val(latestVitals.weight);
                        if (latestVitals.height) $('#visitorVitalsForm input[name="height"]').val(latestVitals.height);
                        if (latestVitals.body_temp) $('#visitorVitalsForm input[name="body_temp"]').val(latestVitals.body_temp);
                        if (latestVitals.resp_rate) $('#visitorVitalsForm input[name="resp_rate"]').val(latestVitals.resp_rate);
                        if (latestVitals.pulse) $('#visitorVitalsForm input[name="pulse"]').val(latestVitals.pulse);
                        if (latestVitals.blood_pressure) $('#visitorVitalsForm input[name="blood_pressure"]').val(latestVitals.blood_pressure);
                        if (latestVitals.oxygen_sat) $('#visitorVitalsForm input[name="oxygen_sat"]').val(latestVitals.oxygen_sat);
                        if (latestVitals.remarks) $('#visitorVitalsForm textarea[name="remarks"]').val(latestVitals.remarks);
                        if (latestVitals.vital_date) $('#visitorVitalsForm input[name="vital_date"]').val(latestVitals.vital_date);
                    }
                    // Render visitor referral history
                    if (response.medication_referrals && response.medication_referrals.length > 0) {
                        let referralHtml = '<div class="mb-4 flex justify-between items-center"><strong>Previous Medication Referrals</strong><div class="relative w-64"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="ri-search-line text-gray-400"></i></div><input type="text" id="visitorMedReferralHistorySearch" class="block w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Search referral history..."><button id="clearVisitorMedReferralHistorySearch" type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden"><i class="ri-close-line"></i></button></div></div>';
                        referralHtml += '<div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assessment</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th></tr></thead><tbody class="bg-white divide-y divide-gray-200" id="visitorMedReferralHistoryTableBody">';
                        
                        // Show first 5 entries
                        const entriesToShow = response.medication_referrals.slice(0, 5);
                        entriesToShow.forEach(function(referral) {
                            referralHtml += `<tr>
                                <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${referral.created_at ? new Date(referral.created_at).toLocaleDateString() : 'No date'}</td>
                                <td class="px-4 py-2 text-xs text-gray-900">${referral.assessment || 'N/A'}</td>
                                <td class="px-4 py-2 text-xs text-gray-900">${referral.plan || 'N/A'}</td>
                                <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${referral.recorded_by || 'Staff'}</td>
                            </tr>`;
                        });
                        referralHtml += '</tbody></table></div>';
                        
                        // Add pagination (always show, even for 1 page)
                        if (response.medication_referrals.length > 0) {
                            const totalPages = Math.ceil(response.medication_referrals.length / 5);
                            referralHtml += '<div class="mt-3 flex justify-between items-center px-6 py-4"><div class="text-xs text-gray-600">Showing 1 to 5 of ' + response.medication_referrals.length + ' entries</div><nav class="flex justify-end items-center -space-x-px" id="visitorMedReferralHistoryPagination">';
                            
                            // Previous button
                            referralHtml += `
                                <button type="button" disabled 
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                    aria-label="Previous">
                                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m15 18-6-6 6-6"></path>
                                    </svg>
                                    <span class="sr-only">Previous</span>
                                </button>`;
                            
                            // Page numbers
                            for (let i = 1; i <= totalPages; i++) {
                                if (i === 1) {
                                    referralHtml += `<button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300" aria-current="page">${i}</button>`;
                                } else {
                                    referralHtml += `<button type="button" data-page="${i}" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">${i}</button>`;
                                }
                            }
                            
                            // Next button
                            if (totalPages > 1) {
                                referralHtml += `
                                    <button type="button" 
                                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" 
                                        aria-label="Next">
                                        <span class="sr-only">Next</span>
                                        <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m9 18 6-6-6-6"></path>
                                        </svg>
                                    </button>`;
                            } else {
                                referralHtml += `
                                    <button type="button" disabled 
                                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                        aria-label="Next">
                                        <span class="sr-only">Next</span>
                                        <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="m9 18 6-6-6-6"></path>
                                        </svg>
                                    </button>`;
                            }
                            
                            referralHtml += '</nav></div>';
                        }
                        
                        $('#visitorMedReferralHistoryContent').html(referralHtml);
                        // Store original data for search functionality
                        $('#visitorMedReferralHistoryContent').data('originalData', response.medication_referrals);
                    } else {
                        $('#visitorMedReferralHistoryContent').html('<div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assessment</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><tr><td colspan="4" class="px-4 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-file-list-3-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medication referrals found</p><p class="text-gray-400 text-xs">No previous referrals recorded</p></div></td></tr></tbody></table></div>');
                    }
                },
                error: function() {
                    $('#visitorVitalsForm input[name="vital_date"]').val(new Date().toISOString().split('T')[0]);
                    $('#visitorMedReferralHistoryContent').html('<p class="text-center text-red-400 text-xs">Error loading medication referral history.</p>');
                }
            });
        }

        // Visitor Vital Signs Form Submission
        // Ensure Save Vital Signs button triggers form submit
        $('#saveVisitorVitalsBtn').off('click').on('click', function() {
            $('#visitorVitalsForm').submit();
        });
        $('#visitorVitalsForm').on('submit', function(e) {
            e.preventDefault();

            // Clear previous messages
            $('#visitorVitalsForm .error-msg, #visitorVitalsForm .success-msg').remove();

            // Get form data
            var vt = $('#visitorModalTitle').text();
            var vidMatch = vt.match(/\(([^)]+)\)$/);
            var visitorId = vidMatch ? vidMatch[1] : '';
            var visitorName = vt.replace(/\s*\([^)]*\)$/, '');
               // Enforce modal title format: "Name (ID)" and check for N/A
               if (!visitorId || !visitorName || visitorId === 'N/A' || visitorName === 'N/A') {
                   $('#visitorVitalsForm').prepend('<div class="error-msg" style="color:red;margin-bottom:8px;">Visitor ID or name missing or invalid. Please make sure you select a valid visitor. Modal title must be: Name (ID).</div>');
                   return;
               }
            var formData = {
                visitor_id: visitorId,
                visitor_name: visitorName,
                vital_date: $(this).find('input[name="vital_date"]').val(),
                weight: $(this).find('input[name="weight"]').val(),
                height: $(this).find('input[name="height"]').val(),
                body_temp: $(this).find('input[name="body_temp"]').val(),
                resp_rate: $(this).find('input[name="resp_rate"]').val(),
                pulse: $(this).find('input[name="pulse"]').val(),
                blood_pressure: $(this).find('input[name="blood_pressure"]').val(),
                oxygen_sat: $(this).find('input[name="oxygen_sat"]').val(),
                remarks: $(this).find('textarea[name="remarks"]').val(),
                entity_type: 'visitor'
            };

            $.ajax({
                url: 'save_vital_signs.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove any previous popup
                        if ($('#vitalsToast').length) $('#vitalsToast').remove();
                        $('body').append(`
                      <div id="vitalsToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                        <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                          <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                          <span>Vital signs saved</span>
                        </div>
                      </div>
                    `);
                        // Don't reset form - keep the data in the fields
                        setTimeout(function() {
                            $('#vitalsToast').fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 1200);
                    } else {
                        showErrorModal('Error: ' + response.message, 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    showErrorModal('An error occurred while saving vital signs. Please try again.', 'Error');
                }
            });
        });

        // Visitor Medication Referral Form Submission
        $('#visitorMedReferralForm').on('submit', function(e) {
            e.preventDefault();

            // Clear previous messages
            $('#visitorMedReferralForm .error-msg, #visitorMedReferralForm .success-msg').remove();

            // Get form data
            var vt = $('#visitorModalTitle').text();
            var vidMatch = vt.match(/\(([^)]+)\)$/);
            var visitorId = vidMatch ? vidMatch[1] : '';
            var visitorName = vt.replace(/\s*\([^)]*\)$/, '');
            var formData = {
                visitor_id: visitorId,
                visitor_name: visitorName,
                subjective: $(this).find('textarea[name="subjective"]').val(),
                objective: $(this).find('textarea[name="objective"]').val(),
                assessment: $(this).find('textarea[name="assessment"]').val(),
                plan: $(this).find('textarea[name="plan"]').val(),
                intervention: $(this).find('textarea[name="intervention"]').val(),
                evaluation: $(this).find('textarea[name="evaluation"]').val(),
                referral_to: $(this).find('input[name="referral_to"]').val(),
                entity_type: 'visitor'
            };

            $.ajax({
                url: 'save_medication_referral.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Remove any previous popup
                        if ($('#medReferralToast').length) $('#medReferralToast').remove();
                        $('body').append(`
                      <div id="medReferralToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                        <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                          <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                          <span>Medication referral saved</span>
                        </div>
                      </div>
                    `);
                        // Reset form
                        $('#visitorMedReferralForm')[0].reset();
                        setTimeout(function() {
                            $('#medReferralToast').fadeOut(300, function() {
                                $(this).remove();
                            });
                        }, 1200);
                    } else {
                        showErrorModal('Error: ' + response.message, 'Error');
                    }
                },
                error: function(xhr, status, error) {
                    showErrorModal('An error occurred while saving medication referral. Please try again.', 'Error');
                }
            });
        });

        // Submit Visitor Vital Signs
        $('#visitorVitalsForm').on('submit', function(e) {
            e.preventDefault();

            // Extract visitor info from title
            var titleText = $('#visitorModalTitle').text();
            var idMatch = titleText.match(/\(([^)]+)\)$/);
            var visitorId = idMatch ? idMatch[1] : '';
            var visitorName = titleText.replace(/\s*\([^)]*\)$/, '');

            var formData = {
                visitor_id: visitorId,
                visitor_name: visitorName,
                vital_date: $(this).find('input[name="vital_date"]').val(),
                weight: $(this).find('input[name="weight"]').val(),
                height: $(this).find('input[name="height"]').val(),
                body_temp: $(this).find('input[name="body_temp"]').val(),
                resp_rate: $(this).find('input[name="resp_rate"]').val(),
                pulse: $(this).find('input[name="pulse"]').val(),
                blood_pressure: $(this).find('input[name="blood_pressure"]').val(),
                oxygen_sat: $(this).find('input[name="oxygen_sat"]').val(),
                remarks: $(this).find('textarea[name="remarks"]').val(),
                entity_type: 'visitor'
            };

            $.ajax({
                url: 'save_vital_signs.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if ($('#vitalsToast').length) $('#vitalsToast').remove();
                        $('body').append(`
                        <div id="vitalsToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                          <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                            <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                            <span>Visitor vital signs saved!</span>
                          </div>
                        </div>`);
                        setTimeout(function(){ $('#vitalsToast').fadeOut(300, function(){ $(this).remove(); }); }, 1200);
                    } else {
                        showErrorModal('Error: ' + response.message, 'Error');
                    }
                },
                error: function(){ showErrorModal('An error occurred while saving visitor vital signs.', 'Error'); }
            });
        });

        // Submit Visitor Medication Referral
        $('#visitorMedReferralForm').on('submit', function(e) {
            e.preventDefault();

            var titleText = $('#visitorModalTitle').text();
            var idMatch = titleText.match(/\(([^)]+)\)$/);
            var visitorId = idMatch ? idMatch[1] : '';
            var visitorName = titleText.replace(/\s*\([^)]*\)$/, '');

            var formData = {
                visitor_id: visitorId,
                visitor_name: visitorName,
                subjective: $(this).find('textarea[name="subjective"]').val(),
                objective: $(this).find('textarea[name="objective"]').val(),
                assessment: $(this).find('textarea[name="assessment"]').val(),
                plan: $(this).find('textarea[name="plan"]').val(),
                intervention: $(this).find('textarea[name="intervention"]').val(),
                evaluation: $(this).find('textarea[name="evaluation"]').val(),
                entity_type: 'visitor'
            };

            $.ajax({
                url: 'save_medication_referral.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        if ($('#medReferralToast').length) $('#medReferralToast').remove();
                        $('body').append(`
                        <div id="medReferralToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                          <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                            <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                            <span>Visitor referral saved!</span>
                          </div>
                        </div>`);
                        $('#visitorMedReferralForm')[0].reset();
                        setTimeout(function(){ $('#medReferralToast').fadeOut(300, function(){ $(this).remove(); }); }, 1200);
                    } else {
                        showErrorModal('Error: ' + response.message, 'Error');
                    }
                },
                error: function(){ showErrorModal('An error occurred while saving visitor medication referral.', 'Error'); }
        });
    });

    // Faculty Modal functionality
    const facultyProfileModal = document.getElementById('facultyProfileModal');
    const closeFacultyProfileModal = document.getElementById('closeFacultyProfileModal');

    function openFacultyModal() { facultyProfileModal.classList.remove('hidden'); $('body, html').addClass('overflow-hidden'); }
    function closeFacultyModal() { facultyProfileModal.classList.add('hidden'); $('body, html').removeClass('overflow-hidden'); }

    closeFacultyProfileModal.addEventListener('click', closeFacultyModal);
    window.addEventListener('click', (e) => { if (e.target === facultyProfileModal) closeFacultyModal(); });

    // View faculty button logic - match student modal layout
    $(document).on('click', '.viewFacultyBtn', function() {
        const facultyId = $(this).data('faculty-id') || 'N/A';
        const fullName = $(this).data('full-name') || 'N/A';
        const address = $(this).data('address') || 'N/A';
        const contact = $(this).data('contact') || 'N/A';
        const emergencyContact = $(this).data('emergency-contact') || 'N/A';
        const age = $(this).data('age') || 'N/A';
        const department = $(this).data('department') || 'N/A';
        const collegeCourse = $(this).data('college-course') || 'N/A';
        const gender = $(this).data('gender') || 'N/A';
        const email = $(this).data('email') || 'N/A';
        const civilStatus = $(this).data('civil-status') || 'N/A';
        const citizenship = $(this).data('citizenship') || 'N/A';

        $('#facultyModalTitle').text(fullName + ' (' + facultyId + ')');
        // Ensure prescribe flow uses faculty name
        try { currentPatientName = fullName; } catch(e) {}
        $('#facultyModalDetails').html(`
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Personal Information Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Personal Information</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Faculty ID:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${facultyId || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Age:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${age || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Gender:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${gender || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Civil Status:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${civilStatus || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Citizenship:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${citizenship || 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Academic Information Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Academic Information</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Department:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${department || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">College Course:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${collegeCourse || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Contact Information Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Contact Information</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Email:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${email || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Contact Number:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${contact || 'N/A'}</p>
                            </div>
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-start">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Address:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${address || 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contacts Section -->
                    <div>
                        <h4 class="text-base font-semibold text-gray-800 dark:text-white mb-3 pb-2 border-b border-gray-200 dark:border-neutral-600">Emergency Contacts</h4>
                        <div class="space-y-3">
                            <div class="grid grid-cols-[140px_1fr] gap-3 items-center">
                                <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Emergency Contact:</label>
                                <p class="text-sm text-gray-900 dark:text-neutral-200">${emergencyContact || 'N/A'}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);

        // Default to Information tab
        showFacultyTab('facultyInfoTab');
        openFacultyModal();
    });

    // Faculty tab switching
    function showFacultyTab(tabId) {
        $('#facultyProfileModal .tabContent').addClass('hidden');
        $('#' + tabId).removeClass('hidden');
        $('#facultyProfileModalTabs .tabBtnFaculty').removeClass('bg-primary text-white').addClass('bg-gray-200 text-gray-700 dark:bg-neutral-600 dark:text-neutral-300');
        $('#facultyProfileModalTabs .tabBtnFaculty[data-tab="' + tabId + '"]').addClass('bg-primary text-white').removeClass('bg-gray-200 text-gray-700 dark:bg-neutral-600 dark:text-neutral-300');
        // Show/hide faculty footer buttons
        $('#facultyPrescribeMedBtn, #saveFacultyVitalsBtn, #saveFacultyMedReferralBtn').addClass('hidden');
        if (tabId === 'facultyInfoTab') {
            $('#facultyPrescribeMedBtn').removeClass('hidden');
        } else if (tabId === 'facultyVitalsTab') {
            $('#saveFacultyVitalsBtn').removeClass('hidden');
        } else if (tabId === 'facultyMedReferralTab') {
            $('#saveFacultyMedReferralBtn').removeClass('hidden');
        }
    }

    $('#facultyProfileModalTabs .tabBtnFaculty').on('click', function() {
        const tabId = $(this).data('tab');
        showFacultyTab(tabId);
        if (tabId === 'facultyVitalsTab' || tabId === 'facultyMedReferralTab') {
            loadFacultyHistory();
        }
    });

    // Load faculty history and populate vitals form
    function loadFacultyHistory() {
        var titleText = $('#facultyModalTitle').text();
        var idMatch = titleText.match(/\(([^)]+)\)$/);
        var facultyId = idMatch ? idMatch[1] : '';
        var facultyName = titleText.replace(/\s*\([^)]*\)$/, '');

        // Clear form first
        $('#facultyVitalsForm input[name="weight"]').val('');
        $('#facultyVitalsForm input[name="height"]').val('');
        $('#facultyVitalsForm input[name="body_temp"]').val('');
        $('#facultyVitalsForm input[name="resp_rate"]').val('');
        $('#facultyVitalsForm input[name="pulse"]').val('');
        $('#facultyVitalsForm input[name="blood_pressure"]').val('');
        $('#facultyVitalsForm input[name="oxygen_sat"]').val('');
        $('#facultyVitalsForm textarea[name="remarks"]').val('');
        $('#facultyVitalsForm input[name="vital_date"]').val(new Date().toISOString().split('T')[0]);

        // Fetch latest faculty vital signs and medication referral history
        $.ajax({
            url: 'get_faculty_vital_signs.php',
            type: 'POST',
            data: {
                faculty_id: facultyId,
                faculty_name: facultyName
            },
            dataType: 'json',
            success: function(response) {
                if (response.vital_signs && response.vital_signs.length > 0) {
                    var vitals = response.vital_signs[0];
                    $('#facultyVitalsForm input[name="weight"]').val(vitals.weight || '');
                    $('#facultyVitalsForm input[name="height"]').val(vitals.height || '');
                    $('#facultyVitalsForm input[name="body_temp"]').val(vitals.body_temp || '');
                    $('#facultyVitalsForm input[name="resp_rate"]').val(vitals.resp_rate || '');
                    $('#facultyVitalsForm input[name="pulse"]').val(vitals.pulse || '');
                    $('#facultyVitalsForm input[name="blood_pressure"]').val(vitals.blood_pressure || '');
                    $('#facultyVitalsForm input[name="oxygen_sat"]').val(vitals.oxygen_sat || '');
                    $('#facultyVitalsForm textarea[name="remarks"]').val(vitals.remarks || '');
                    $('#facultyVitalsForm input[name="vital_date"]').val(vitals.vital_date || new Date().toISOString().split('T')[0]);
                }
                
                // Display faculty medication referral history
                if (response.medication_referrals && response.medication_referrals.length > 0) {
                    let referralHtml = '<div class="mb-4 flex justify-between items-center"><strong>Previous Medication Referrals</strong><div class="relative w-64"><div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"><i class="ri-search-line text-gray-400"></i></div><input type="text" id="facultyMedReferralHistorySearch" class="block w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white" placeholder="Search referral history..."><button id="clearFacultyMedReferralHistorySearch" type="button" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600 hidden"><i class="ri-close-line"></i></button></div></div>';
                    referralHtml += '<div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assessment</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th></tr></thead><tbody class="bg-white divide-y divide-gray-200" id="facultyMedReferralHistoryTableBody">';
                    
                    // Show first 5 entries
                    const entriesToShow = response.medication_referrals.slice(0, 5);
                    entriesToShow.forEach(function(referral) {
                        referralHtml += `<tr>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${referral.created_at ? new Date(referral.created_at).toLocaleDateString() : 'No date'}</td>
                            <td class="px-4 py-2 text-xs text-gray-900">${referral.assessment || 'N/A'}</td>
                            <td class="px-4 py-2 text-xs text-gray-900">${referral.plan || 'N/A'}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${referral.recorded_by || 'Staff'}</td>
                        </tr>`;
                    });
                    referralHtml += '</tbody></table></div>';
                    
                    // Add pagination (always show, even for 1 page)
                    if (response.medication_referrals.length > 0) {
                        const totalPages = Math.ceil(response.medication_referrals.length / 5);
                        referralHtml += '<div class="mt-3 flex justify-between items-center px-6 py-4"><div class="text-xs text-gray-600">Showing 1 to 5 of ' + response.medication_referrals.length + ' entries</div><nav class="flex justify-end items-center -space-x-px" id="facultyMedReferralHistoryPagination">';
                        
                        // Previous button
                        referralHtml += `
                            <button type="button" disabled 
                                class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                aria-label="Previous">
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m15 18-6-6 6-6"></path>
                                </svg>
                                <span class="sr-only">Previous</span>
                            </button>`;
                        
                        // Page numbers
                        for (let i = 1; i <= totalPages; i++) {
                            if (i === 1) {
                                referralHtml += `<button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300" aria-current="page">${i}</button>`;
                            } else {
                                referralHtml += `<button type="button" data-page="${i}" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">${i}</button>`;
                            }
                        }
                        
                        // Next button
                        if (totalPages > 1) {
                            referralHtml += `
                                <button type="button" data-page="2"
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" 
                                    aria-label="Next">
                                    <span class="sr-only">Next</span>
                                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </button>`;
                        } else {
                            referralHtml += `
                                <button type="button" disabled 
                                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                                    aria-label="Next">
                                    <span class="sr-only">Next</span>
                                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m9 18 6-6-6-6"></path>
                                    </svg>
                                </button>`;
                        }
                        
                        referralHtml += '</nav></div>';
                    }
                    
                    $('#facultyMedReferralHistoryContent').html(referralHtml);
                    // Store original data for search functionality
                    $('#facultyMedReferralHistoryContent').data('originalData', response.medication_referrals);
                } else {
                    $('#facultyMedReferralHistoryContent').html('<div class="overflow-x-auto"><table class="w-full divide-y divide-gray-200"><thead class="bg-gray-50"><tr><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assessment</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th><th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th></tr></thead><tbody class="bg-white divide-y divide-gray-200"><tr><td colspan="4" class="px-4 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-file-list-3-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medication referrals found</p><p class="text-gray-400 text-xs">No previous referrals recorded</p></div></td></tr></tbody></table></div>');
                }
            },
            error: function() {
                $('#facultyVitalsForm input[name="vital_date"]').val(new Date().toISOString().split('T')[0]);
                $('#facultyMedReferralHistoryContent').html('<p class="text-center text-red-400 text-xs">Error loading medication referral history.</p>');
            }
        });
    }

    // Faculty vital signs save functionality
    $('#saveFacultyVitalsBtn').on('click', function() {
        var titleText = $('#facultyModalTitle').text();
        var idMatch = titleText.match(/\(([^)]+)\)$/);
        var facultyId = idMatch ? idMatch[1] : '';
        var facultyName = titleText.replace(/\s*\([^)]*\)$/, '');
        
        var formData = {
            faculty_id: facultyId,
            faculty_name: facultyName,
            vital_date: $('#facultyVitalsForm input[name="vital_date"]').val(),
            weight: $('#facultyVitalsForm input[name="weight"]').val(),
            height: $('#facultyVitalsForm input[name="height"]').val(),
            body_temp: $('#facultyVitalsForm input[name="body_temp"]').val(),
            resp_rate: $('#facultyVitalsForm input[name="resp_rate"]').val(),
            pulse: $('#facultyVitalsForm input[name="pulse"]').val(),
            blood_pressure: $('#facultyVitalsForm input[name="blood_pressure"]').val(),
            oxygen_sat: $('#facultyVitalsForm input[name="oxygen_sat"]').val(),
            remarks: $('#facultyVitalsForm textarea[name="remarks"]').val(),
            entity_type: 'faculty'
        };

        $.ajax({
            url: 'save_vital_signs.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove any previous popup
                    if ($('#vitalsToast').length) $('#vitalsToast').remove();
                    $('body').append(`
                      <div id="vitalsToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                        <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                          <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                          <span>Vital signs saved</span>
                        </div>
                      </div>
                    `);
                    setTimeout(function() {
                        $('#vitalsToast').fadeOut(300, function() {
                            $(this).remove();
                        });
                    }, 1200);
                } else {
                    showErrorModal('Error: ' + response.message, 'Error');
                }
            },
            error: function(xhr, status, error) {
                showErrorModal('An error occurred while saving vital signs. Please try again.', 'Error');
            }
        });
    });

    // Faculty medication referral save functionality
    $('#saveFacultyMedReferralBtn').on('click', function() {
        var titleText = $('#facultyModalTitle').text();
        var idMatch = titleText.match(/\(([^)]+)\)$/);
        var facultyId = idMatch ? idMatch[1] : '';
        var facultyName = titleText.replace(/\s*\([^)]*\)$/, '');
        
        var formData = {
            faculty_id: facultyId,
            faculty_name: facultyName,
            subjective: $('#facultyMedReferralForm textarea[name="subjective"]').val(),
            objective: $('#facultyMedReferralForm textarea[name="objective"]').val(),
            assessment: $('#facultyMedReferralForm textarea[name="assessment"]').val(),
            plan: $('#facultyMedReferralForm textarea[name="plan"]').val(),
            intervention: $('#facultyMedReferralForm textarea[name="intervention"]').val(),
            evaluation: $('#facultyMedReferralForm textarea[name="evaluation"]').val(),
            referral_to: $('#facultyMedReferralForm input[name="referral_to"]').val(),
            entity_type: 'faculty'
        };

        $.ajax({
            url: 'save_medication_referral.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Remove any previous popup
                    if ($('#medReferralToast').length) $('#medReferralToast').remove();
                    $('body').append(`
                      <div id="medReferralToast" style="position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);">
                        <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                          <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                          <span>Medication referral saved</span>
                        </div>
                      </div>
                    `);
                    $('#facultyMedReferralForm')[0].reset();
                    setTimeout(function(){ $('#medReferralToast').fadeOut(300, function(){ $(this).remove(); }); }, 1200);
                } else {
                    showErrorModal('Error: ' + response.message, 'Error');
                }
            },
            error: function(){ showErrorModal('An error occurred while saving faculty medication referral.', 'Error'); }
        });
    });
    
    // Global function for medication history pagination
    window.goToMedHistoryPage = function(page) {
        const activeModal = $('.med-history').closest('.modal, [id*="Modal"]');
        const paginationContainer = activeModal.find('[id^="medHistoryPagination"]');
        const paginationId = paginationContainer.attr('id');
        const tableBodyId = paginationId.replace('Pagination', 'TableBody');
        const tableBody = $('#' + tableBodyId);
        
        // Get the original data from the modal
        const modal = activeModal.find('.med-history');
        const originalData = modal.data('originalData');
        
        if (originalData && originalData.length > 0) {
            const startIndex = (page - 1) * 5;
            const endIndex = startIndex + 5;
            const pageData = originalData.slice(startIndex, endIndex);
            
            let rowsHtml = '';
            pageData.forEach(function(item) {
                rowsHtml += `<tr><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.prescription_date}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.medicine}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.dosage}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.quantity}</td></tr>`;
            });
            
            tableBody.html(rowsHtml);
            
            // Update pagination with dynamic page numbers
            const totalPages = Math.ceil(originalData.length / 5);
            const startPage = Math.max(1, page - 2);
            const endPage = Math.min(totalPages, page + 2);
            
            // Clear existing page numbers (keep prev/next buttons)
            const prevButton = paginationContainer.find('button[aria-label="Previous"]');
            const nextButton = paginationContainer.find('button[aria-label="Next"]');
            paginationContainer.find('button:not([aria-label="Previous"]):not([aria-label="Next"])').remove();
            paginationContainer.find('span').remove();
            
            // Insert new page numbers between prev and next buttons
            let newPageNumbers = '';
            
            // Add ellipsis at the beginning if needed
            if (startPage > 1) {
                newPageNumbers += `
                    <button onclick="goToMedHistoryPage(1)" 
                            class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                        1
                    </button>
                `;
                if (startPage > 2) {
                    newPageNumbers += `
                        <span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">
                            ...
                        </span>
                    `;
                }
            }
            
            // Add page numbers
            for (let i = startPage; i <= endPage; i++) {
                if (i === page) {
                    newPageNumbers += `
                        <button type="button" 
                                class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-gray-200 text-gray-800 focus:outline-hidden focus:bg-gray-300">
                            ${i}
                        </button>
                    `;
                } else {
                    newPageNumbers += `
                        <button onclick="goToMedHistoryPage(${i})" 
                                class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                            ${i}
                        </button>
                    `;
                }
            }
            
            // Add ellipsis at the end if needed
            if (endPage < totalPages) {
                if (endPage < totalPages - 1) {
                    newPageNumbers += `
                        <span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">
                            ...
                        </span>
                    `;
                }
                newPageNumbers += `
                    <button onclick="goToMedHistoryPage(${totalPages})" 
                            class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                        ${totalPages}
                    </button>
                `;
            }
            
            // Insert new page numbers after prev button
            prevButton.after(newPageNumbers);
            
            // Update previous/next buttons - always keep them visible
            if (page > 1) {
                prevButton.prop('disabled', false).removeClass('disabled:opacity-50 disabled:pointer-events-none');
                prevButton.attr('onclick', `goToMedHistoryPage(${page - 1})`);
            } else {
                prevButton.prop('disabled', true).addClass('disabled:opacity-50 disabled:pointer-events-none');
                prevButton.removeAttr('onclick');
            }
            
            if (page < totalPages) {
                nextButton.prop('disabled', false).removeClass('disabled:opacity-50 disabled:pointer-events-none');
                nextButton.attr('onclick', `goToMedHistoryPage(${page + 1})`);
            } else {
                nextButton.prop('disabled', true).addClass('disabled:opacity-50 disabled:pointer-events-none');
                nextButton.removeAttr('onclick');
            }
            
            // Update entries info
            const startEntry = startIndex + 1;
            const endEntry = Math.min(endIndex, originalData.length);
            const entriesInfo = modal.closest('.med-history').find('.flex.justify-between.items-center .text-xs.text-gray-600').first();
            entriesInfo.text(`Showing ${startEntry} to ${endEntry} of ${originalData.length} entries`);
        }
    };
    
    // Medication History Search Functionality
    function filterMedHistoryData(searchTerm, originalData, tableBodyId, paginationId) {
        if (!originalData || originalData.length === 0) return;
        
        let filteredData = originalData;
        
        if (searchTerm && searchTerm.trim() !== '') {
            const searchLower = searchTerm.toLowerCase();
            filteredData = originalData.filter(function(item) {
                return (item.prescription_date && item.prescription_date.toLowerCase().includes(searchLower)) ||
                       (item.medicine && item.medicine.toLowerCase().includes(searchLower)) ||
                       (item.dosage && item.dosage.toLowerCase().includes(searchLower)) ||
                       (item.quantity && item.quantity.toString().toLowerCase().includes(searchLower));
            });
        }
        
        // Update table with filtered data
        const tableBody = $('#' + tableBodyId);
        const totalPages = Math.ceil(filteredData.length / 5);
        const currentPage = 1; // Reset to first page when searching
        
        // Show first 5 entries of filtered data
        const entriesToShow = filteredData.slice(0, 5);
        let rowsHtml = '';
        
        if (entriesToShow.length > 0) {
            entriesToShow.forEach(function(item) {
                rowsHtml += `<tr><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.prescription_date}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.medicine}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.dosage}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.quantity}</td></tr>`;
            });
        } else {
            rowsHtml += '<tr><td colspan="4" class="px-4 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-search-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No results found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
        }
        
        tableBody.html(rowsHtml);
        
        // Update pagination (always show, even for no results)
        const paginationContainer = $('#' + paginationId);
        if (filteredData.length > 0) {
            // Use the same pagination function to maintain functionality
            goToMedHistoryPage(1, originalData, tableBodyId, paginationId, searchTerm);
        } else {
            // Show pagination even when no results, but with disabled state
            paginationContainer.closest('.mt-3').show();
            goToMedHistoryPage(1, originalData, tableBodyId, paginationId, searchTerm);
        }
        
        // Update entries info
        const startEntry = 1;
        const endEntry = Math.min(5, filteredData.length);
        const entriesInfo = $('.med-history').find('.flex.justify-between.items-center .text-xs.text-gray-600').first();
        entriesInfo.text(`Showing ${startEntry} to ${endEntry} of ${filteredData.length} entries`);
    }
    
    // Function to handle pagination for medication history
    function goToMedHistoryPage(page, originalData, tableBodyId, paginationId, searchTerm = '') {
        if (!originalData || originalData.length === 0) return;
        
        let filteredData = originalData;
        
        if (searchTerm && searchTerm.trim() !== '') {
            const searchLower = searchTerm.toLowerCase();
            filteredData = originalData.filter(function(item) {
                return (item.prescription_date && item.prescription_date.toLowerCase().includes(searchLower)) ||
                       (item.medicine && item.medicine.toLowerCase().includes(searchLower)) ||
                       (item.dosage && item.dosage.toLowerCase().includes(searchLower)) ||
                       (item.quantity && item.quantity.toString().toLowerCase().includes(searchLower));
            });
        }
        
        const totalPages = Math.ceil(filteredData.length / 5);
        const startIndex = (page - 1) * 5;
        const endIndex = startIndex + 5;
        const entriesToShow = filteredData.slice(startIndex, endIndex);
        
        // Update table with current page data
        const tableBody = $('#' + tableBodyId);
        let rowsHtml = '';
        
        if (entriesToShow.length > 0) {
            entriesToShow.forEach(function(item) {
                rowsHtml += `<tr><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.prescription_date}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.medicine}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.dosage}</td><td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.quantity}</td></tr>`;
            });
        } else {
            rowsHtml += '<tr><td colspan="4" class="px-4 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-search-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No results found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
        }
        
        tableBody.html(rowsHtml);
        
        // Update pagination
        const paginationContainer = $('#' + paginationId);
        let paginationHtml = '';
        
        // Previous button
        if (page > 1) {
            paginationHtml += `
                <button type="button" data-page="${page - 1}"
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" 
                        aria-label="Previous">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>
            `;
        } else {
            paginationHtml += `
                <button type="button" disabled 
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                        aria-label="Previous">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>
            `;
        }
        
        // Page numbers
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, page + 2);
        
        if (startPage > 1) {
            paginationHtml += `<button type="button" data-page="1" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === page) {
                paginationHtml += `<button type="button" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-gray-200 text-gray-800 focus:outline-hidden focus:bg-gray-300" aria-current="page">${i}</button>`;
            } else {
                paginationHtml += `<button type="button" data-page="${i}" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">${i}</button>`;
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">...</span>`;
            }
            paginationHtml += `<button type="button" data-page="${totalPages}" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">${totalPages}</button>`;
        }
        
        // Next button
        if (page < totalPages) {
            paginationHtml += `
                <button type="button" data-page="${page + 1}"
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" 
                        aria-label="Next">
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
        } else {
            paginationHtml += `
                <button type="button" disabled 
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                        aria-label="Next">
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
        }
        
        paginationContainer.html(paginationHtml);
        
        // Update entries info
        const startEntry = startIndex + 1;
        const endEntry = Math.min(endIndex, filteredData.length);
        const entriesInfo = $('.med-history').find('.flex.justify-between.items-center .text-xs.text-gray-600').first();
        entriesInfo.text(`Showing ${startEntry} to ${endEntry} of ${filteredData.length} entries`);
    }
    
    // Medication Referral History Search Functionality
    function filterMedReferralHistoryData(searchTerm, originalData, tableBodyId, paginationId) {
        if (!originalData || originalData.length === 0) return;
        
        let filteredData = originalData;
        
        if (searchTerm && searchTerm.trim() !== '') {
            const searchLower = searchTerm.toLowerCase();
            filteredData = originalData.filter(function(item) {
                return (item.created_at && new Date(item.created_at).toLocaleDateString().toLowerCase().includes(searchLower)) ||
                       (item.assessment && item.assessment.toLowerCase().includes(searchLower)) ||
                       (item.plan && item.plan.toLowerCase().includes(searchLower)) ||
                       (item.recorded_by && item.recorded_by.toLowerCase().includes(searchLower));
            });
        }
        
        // Update table with filtered data
        const tableBody = $('#' + tableBodyId);
        const totalPages = Math.ceil(filteredData.length / 5);
        const currentPage = 1; // Reset to first page when searching
        
        // Show first 5 entries of filtered data
        const entriesToShow = filteredData.slice(0, 5);
        let rowsHtml = '';
        
        if (entriesToShow.length > 0) {
            entriesToShow.forEach(function(item) {
                rowsHtml += `<tr>
                    <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'No date'}</td>
                    <td class="px-4 py-2 text-xs text-gray-900">${item.assessment || 'N/A'}</td>
                    <td class="px-4 py-2 text-xs text-gray-900">${item.plan || 'N/A'}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.recorded_by || 'Staff'}</td>
                </tr>`;
            });
        } else {
            rowsHtml += '<tr><td colspan="4" class="px-4 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-search-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No results found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
        }
        
        tableBody.html(rowsHtml);
        
        // Update pagination (always show, even for no results)
        const paginationContainer = $('#' + paginationId);
        if (filteredData.length > 0) {
            // Use the same pagination function to maintain functionality
            goToMedReferralHistoryPage(1, originalData, tableBodyId, paginationId, searchTerm);
        } else {
            // Show pagination even when no results, but with disabled state
            paginationContainer.closest('.mt-3').show();
            goToMedReferralHistoryPage(1, originalData, tableBodyId, paginationId, searchTerm);
        }
        
        // Update entries info
        const startEntry = 1;
        const endEntry = Math.min(5, filteredData.length);
        const entriesInfo = $('#medReferralHistoryContent, #visitorMedReferralHistoryContent, #facultyMedReferralHistoryContent').find('.flex.justify-between.items-center .text-xs.text-gray-600').first();
        entriesInfo.text(`Showing ${startEntry} to ${endEntry} of ${filteredData.length} entries`);
    }
    
    // Search event handlers
    $(document).on('input', '[id^="medHistorySearch"]', function() {
        const searchTerm = $(this).val();
        const searchId = $(this).attr('id');
        const tableBodyId = searchId.replace('Search', 'TableBody');
        const paginationId = searchId.replace('Search', 'Pagination');
        const clearButtonId = searchId.replace('Search', 'clearMedHistorySearch');
        
        // Show/hide clear button
        if (searchTerm.trim() !== '') {
            $('#' + clearButtonId).removeClass('hidden');
        } else {
            $('#' + clearButtonId).addClass('hidden');
        }
        
        // Get original data and filter
        const modal = $(this).closest('.med-history');
        const originalData = modal.data('originalData');
        filterMedHistoryData(searchTerm, originalData, tableBodyId, paginationId);
    });
    
    // Medication Referral History Search event handlers
    $(document).on('input', '[id^="medReferralHistorySearch"]', function() {
        const searchTerm = $(this).val();
        const searchId = $(this).attr('id');
        const tableBodyId = searchId.replace('Search', 'TableBody');
        const paginationId = searchId.replace('Search', 'Pagination');
        const clearButtonId = searchId.replace('Search', 'clearMedReferralHistorySearch');
        
        // Show/hide clear button
        if (searchTerm.trim() !== '') {
            $('#' + clearButtonId).removeClass('hidden');
        } else {
            $('#' + clearButtonId).addClass('hidden');
        }
        
        // Get original data and filter
        const modal = $(this).closest('#medReferralHistoryContent, #visitorMedReferralHistoryContent, #facultyMedReferralHistoryContent');
        const originalData = modal.data('originalData');
        if (originalData) {
            filterMedReferralHistoryData(searchTerm, originalData, tableBodyId, paginationId);
        }
    });
    
    // Clear search event handlers
    $(document).on('click', '[id^="clearMedHistorySearch"]', function() {
        const clearId = $(this).attr('id');
        const searchId = clearId.replace('clearMedHistorySearch', 'medHistorySearch');
        const tableBodyId = searchId.replace('Search', 'TableBody');
        const paginationId = searchId.replace('Search', 'Pagination');
        
        $('#' + searchId).val('');
        $(this).addClass('hidden');
        
        // Get original data and show all
        const modal = $(this).closest('.med-history');
        const originalData = modal.data('originalData');
        filterMedHistoryData('', originalData, tableBodyId, paginationId);
    });
    
    // Clear Medication Referral History Search event handlers
    $(document).on('click', '[id^="clearMedReferralHistorySearch"]', function() {
        const clearId = $(this).attr('id');
        const searchId = clearId.replace('clearMedReferralHistorySearch', 'medReferralHistorySearch');
        const tableBodyId = searchId.replace('Search', 'TableBody');
        const paginationId = searchId.replace('Search', 'Pagination');
        
        $('#' + searchId).val('');
        $(this).addClass('hidden');
        
        // Get original data and show all
        const modal = $(this).closest('#medReferralHistoryContent, #visitorMedReferralHistoryContent, #facultyMedReferralHistoryContent');
        const originalData = modal.data('originalData');
        filterMedReferralHistoryData('', originalData, tableBodyId, paginationId);
    });
    
    // Medication Referral History Pagination
    $(document).on('click', '[id^="medReferralHistoryPagination"] button[data-page]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const page = parseInt($(this).data('page'));
        const paginationId = $(this).closest('nav').attr('id');
        const tableBodyId = paginationId.replace('Pagination', 'TableBody');
        const searchId = paginationId.replace('Pagination', 'Search');
        const modal = $(this).closest('#medReferralHistoryContent, #visitorMedReferralHistoryContent, #facultyMedReferralHistoryContent');
        const originalData = modal.data('originalData');
        const searchTerm = $('#' + searchId).val() || '';
        
        goToMedReferralHistoryPage(page, originalData, tableBodyId, paginationId, searchTerm);
    });
    
    // Function to handle pagination for medication referral history
    function goToMedReferralHistoryPage(page, originalData, tableBodyId, paginationId, searchTerm = '') {
        if (!originalData || originalData.length === 0) return;
        
        let filteredData = originalData;
        
        if (searchTerm && searchTerm.trim() !== '') {
            const searchLower = searchTerm.toLowerCase();
            filteredData = originalData.filter(function(item) {
                return (item.created_at && new Date(item.created_at).toLocaleDateString().toLowerCase().includes(searchLower)) ||
                       (item.assessment && item.assessment.toLowerCase().includes(searchLower)) ||
                       (item.plan && item.plan.toLowerCase().includes(searchLower)) ||
                       (item.recorded_by && item.recorded_by.toLowerCase().includes(searchLower));
            });
        }
        
        const totalPages = Math.ceil(filteredData.length / 5);
        const startIndex = (page - 1) * 5;
        const endIndex = startIndex + 5;
        const entriesToShow = filteredData.slice(startIndex, endIndex);
        
        // Update table with current page data
        const tableBody = $('#' + tableBodyId);
        let rowsHtml = '';
        
        if (entriesToShow.length > 0) {
            entriesToShow.forEach(function(item) {
                rowsHtml += `<tr>
                    <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.created_at ? new Date(item.created_at).toLocaleDateString() : 'No date'}</td>
                    <td class="px-4 py-2 text-xs text-gray-900">${item.assessment || 'N/A'}</td>
                    <td class="px-4 py-2 text-xs text-gray-900">${item.plan || 'N/A'}</td>
                    <td class="px-4 py-2 whitespace-nowrap text-xs text-gray-900">${item.recorded_by || 'Staff'}</td>
                </tr>`;
            });
        } else {
            rowsHtml += '<tr><td colspan="4" class="px-4 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-search-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No results found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
        }
        
        tableBody.html(rowsHtml);
        
        // Update pagination
        const paginationContainer = $('#' + paginationId);
        let paginationHtml = '';
        
        // Previous button
        if (page > 1) {
            paginationHtml += `
                <button type="button" data-page="${page - 1}"
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" 
                        aria-label="Previous">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>
            `;
        } else {
            paginationHtml += `
                <button type="button" disabled 
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                        aria-label="Previous">
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    <span class="sr-only">Previous</span>
                </button>
            `;
        }
        
        // Page numbers
        const startPage = Math.max(1, page - 2);
        const endPage = Math.min(totalPages, page + 2);
        
        if (startPage > 1) {
            paginationHtml += `<button type="button" data-page="1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === page) {
                paginationHtml += `<button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300" aria-current="page">${i}</button>`;
            } else {
                paginationHtml += `<button type="button" data-page="${i}" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">${i}</button>`;
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>`;
            }
            paginationHtml += `<button type="button" data-page="${totalPages}" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">${totalPages}</button>`;
        }
        
        // Next button
        if (page < totalPages) {
            paginationHtml += `
                <button type="button" data-page="${page + 1}"
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" 
                        aria-label="Next">
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
        } else {
            paginationHtml += `
                <button type="button" disabled 
                        class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" 
                        aria-label="Next">
                    <span class="sr-only">Next</span>
                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `;
        }
        
        paginationContainer.html(paginationHtml);
        
        // Update entries info
        const startEntry = startIndex + 1;
        const endEntry = Math.min(endIndex, filteredData.length);
        const entriesInfo = $('#medReferralHistoryContent, #visitorMedReferralHistoryContent, #facultyMedReferralHistoryContent').find('.flex.justify-between.items-center .text-xs.text-gray-600').first();
        entriesInfo.text(`Showing ${startEntry} to ${endEntry} of ${filteredData.length} entries`);
    }
    
    // Medication History Pagination
    $(document).on('click', '[id^="medHistoryPagination"] button[data-page]', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const page = parseInt($(this).data('page'));
        const paginationId = $(this).closest('nav').attr('id');
        const tableBodyId = paginationId.replace('Pagination', 'TableBody');
        const searchId = paginationId.replace('Pagination', 'Search');
        const modal = $(this).closest('.med-history');
        const originalData = modal.data('originalData');
        const searchTerm = $('#' + searchId).val() || '';
        
        goToMedHistoryPage(page, originalData, tableBodyId, paginationId, searchTerm);
    });
});
</script>

<style>
    html,
    body {
        scrollbar-width: none;
        /* Firefox */
        -ms-overflow-style: none;
        /* Internet Explorer 10+ */
    }

  html::-webkit-scrollbar,
  body::-webkit-scrollbar {
        display: none;
        /* Safari and Chrome */
  }

@keyframes fade-in {
        from {
            opacity: 0;
            transform: translateY(-4px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

.animate-fade-in {
  animation: fade-in 0.3s ease;
}

.prescribe-modal-scroll {
  max-height: 80vh;
  overflow-y: scroll;
        border-radius: 0.75rem;
        /* Match modal's rounded-lg */
  /* Always reserve space for scrollbar, so content never shifts */
  scrollbar-gutter: stable both-edges;
}

.prescribe-modal-scroll::-webkit-scrollbar {
  width: 10px;
  border-radius: 0.75rem;
  background: transparent;
}

.prescribe-modal-scroll::-webkit-scrollbar-thumb {
  border-radius: 0.75rem;
        background: #c1c1c1;
        /* Use a neutral default, but let browser override */
  border: 2px solid transparent;
  background-clip: padding-box;
}

.prescribe-modal-scroll::-webkit-scrollbar-thumb:hover {
  background: #a0a0a0;
}

.tabContent {
  min-height: 400px;
  overflow-y: auto;
}

/* For Firefox */
.prescribe-modal-scroll {
  scrollbar-width: auto;
  scrollbar-color: auto;
}

/* Consistent scroll area for modal tab content */
.modal-scroll-area {
    max-height: 350px;
    overflow-y: auto;
    padding-right: 4px;
}

.modal-scroll-area::-webkit-scrollbar {
    width: 8px;
}

.modal-scroll-area::-webkit-scrollbar-thumb {
    background: #e5e7eb;
    border-radius: 4px;
}

.modal-scroll-area::-webkit-scrollbar-thumb:hover {
    background: #cbd5e1;
}
    
/* Table styling with fixed columns and ellipses */
#importedPatientsTable {
    table-layout: fixed;
}

#importedPatientsTable td {
    overflow: hidden;
    text-overflow: ellipsis;
}

/* Ensure proper truncation for table cells */
.table-cell-truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: 0;
}

/* Tooltip for truncated text */
.truncate[title] {
    cursor: help;
}
</style>
</style>