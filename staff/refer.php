<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Database connection
try {
    
    
    // Debug: Let's check the actual data first
    $debug_stmt = $db->query('SELECT patient_id FROM medication_referrals LIMIT 3');
    $referral_ids = $debug_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $debug_stmt2 = $db->query('SELECT student_id FROM imported_patients LIMIT 3');
    $patient_ids = $debug_stmt2->fetchAll(PDO::FETCH_COLUMN);
    
    error_log("Referral patient_ids: " . print_r($referral_ids, true));
    error_log("Imported patient student_ids: " . print_r($patient_ids, true));

    // Check if referral_to column exists, if not add it
    try {
        $stmt = $db->query("SHOW COLUMNS FROM medication_referrals LIKE 'referral_to'");
        if ($stmt->rowCount() == 0) {
            $db->exec("ALTER TABLE medication_referrals ADD COLUMN referral_to VARCHAR(255) DEFAULT NULL");
        }
    } catch (Exception $e) {
        // Column might already exist, continue
    }

    // Fetch all medication referrals with correct vital signs for all entity types
    $stmt = $db->query('
        SELECT mr.*, ip.year_level,
               ip.student_id as matched_student_id,
               mr.patient_id as referral_patient_id,
               COALESCE(u.name, mr.recorded_by) as recorded_by_name,
               COALESCE(vs_patient.body_temp, vs_faculty.body_temp, vs_visitor.body_temp) AS body_temp,
               COALESCE(vs_patient.resp_rate, vs_faculty.resp_rate, vs_visitor.resp_rate) AS resp_rate,
               COALESCE(vs_patient.pulse, vs_faculty.pulse, vs_visitor.pulse) AS pulse,
               COALESCE(vs_patient.blood_pressure, vs_faculty.blood_pressure, vs_visitor.blood_pressure) AS blood_pressure,
               COALESCE(vs_patient.weight, vs_faculty.weight, vs_visitor.weight) AS weight,
               COALESCE(vs_patient.height, vs_faculty.height, vs_visitor.height) AS height,
               COALESCE(vs_patient.oxygen_sat, vs_faculty.oxygen_sat, vs_visitor.oxygen_sat) AS oxygen_sat
        FROM medication_referrals mr 
        LEFT JOIN users u ON mr.recorded_by = u.username
        LEFT JOIN imported_patients ip ON 
            CAST(TRIM(mr.patient_id) AS CHAR) = CAST(TRIM(ip.student_id) AS CHAR)
            OR TRIM(mr.patient_name) = TRIM(ip.name)
        LEFT JOIN (
            SELECT patient_id, body_temp, resp_rate, pulse, blood_pressure, weight, height, oxygen_sat,
                   ROW_NUMBER() OVER (PARTITION BY patient_id ORDER BY created_at DESC) as rn
            FROM vital_signs
        ) vs_patient ON CAST(TRIM(mr.patient_id) AS CHAR) = CAST(TRIM(vs_patient.patient_id) AS CHAR) AND vs_patient.rn = 1
        LEFT JOIN (
            SELECT faculty_id, body_temp, resp_rate, pulse, blood_pressure, weight, height, oxygen_sat,
                   ROW_NUMBER() OVER (PARTITION BY faculty_id ORDER BY created_at DESC) as rn
            FROM vital_signs
        ) vs_faculty ON CAST(TRIM(mr.faculty_id) AS CHAR) = CAST(TRIM(vs_faculty.faculty_id) AS CHAR) AND vs_faculty.rn = 1
        LEFT JOIN (
            SELECT visitor_id, body_temp, resp_rate, pulse, blood_pressure, weight, height, oxygen_sat,
                   ROW_NUMBER() OVER (PARTITION BY visitor_id ORDER BY created_at DESC) as rn
            FROM vital_signs
        ) vs_visitor ON CAST(TRIM(mr.visitor_id) AS CHAR) = CAST(TRIM(vs_visitor.visitor_id) AS CHAR) AND vs_visitor.rn = 1
        ORDER BY mr.created_at DESC
    ');
    $referrals = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Check if we're getting year_level data
    if (!empty($referrals)) {
        error_log("First referral data: " . print_r($referrals[0], true));
    }
    
    // Pagination variables
    $referral_records_per_page = 10;
    $referral_page = isset($_GET['referral_page']) ? (int)$_GET['referral_page'] : 1;
    $referral_page = max($referral_page, 1);
    $referral_offset = ($referral_page - 1) * $referral_records_per_page;
    $referral_total_records = count($referrals);
    $referral_total_pages = ceil($referral_total_records / $referral_records_per_page);
    $referral_start = $referral_offset + 1;
    $referral_end = min($referral_offset + $referral_records_per_page, $referral_total_records);
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}
?>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

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
  
  /* Ensure proper truncation and fixed column widths for Referral Records table */
  #referralTable {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    min-width: 800px;
  }
  
  #referralTable th,
  #referralTable td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: 12px 16px;
    border-bottom: 1px solid #e5e7eb;
  }
  
  #referralTable th {
    background-color: #f9fafb;
    font-weight: 600;
    font-size: 0.875rem;
    color: #374151;
    text-transform: uppercase;
    letter-spacing: 0.05em;
  }
  
  #referralTable tbody tr:hover {
    background-color: #f9fafb;
  }
  
  /* Equal column widths - All columns same size */
  #referralTable th,
  #referralTable td {
    width: 16.67% !important;
    min-width: 0;
    max-width: 16.67%;
  }
  
  /* Specific column targeting for better control */
  #referralTable th:nth-child(1),
  #referralTable td:nth-child(1) {
    width: 16.67% !important; /* Patient Name */
  }
  
  #referralTable th:nth-child(2),
  #referralTable td:nth-child(2) {
    width: 16.67% !important; /* Recorded By */
  }
  
  #referralTable th:nth-child(3),
  #referralTable td:nth-child(3) {
    width: 16.67% !important; /* Status */
  }
  
  #referralTable th:nth-child(4),
  #referralTable td:nth-child(4) {
    width: 16.67% !important; /* Date Created */
  }
  
  #referralTable th:nth-child(5),
  #referralTable td:nth-child(5) {
    width: 16.67% !important; /* Referral To */
  }
  
  #referralTable th:nth-child(6),
  #referralTable td:nth-child(6) {
    width: 16.67% !important; /* Actions */
  }
  
  /* Allow truncation with ellipses for long text */
  .truncate {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  
    /* Clean Actions column styling */
    #referralTable td:last-child {
        text-align: center;
        padding: 8px 16px;
    }
    
    /* Hide scrollbar in modal */
    #referralViewModal {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* Internet Explorer 10+ */
    }
    
    #referralViewModal::-webkit-scrollbar {
        display: none; /* Safari and Chrome */
    }
    
    #referralViewModal .overflow-y-auto {
        scrollbar-width: none; /* Firefox */
        -ms-overflow-style: none; /* Internet Explorer 10+ */
    }
    
    #referralViewModal .overflow-y-auto::-webkit-scrollbar {
        display: none; /* Safari and Chrome */
    }
  
  .viewReferralBtn {
    background: none;
    border: none;
    color: #2563eb;
    font-size: 0.875rem;
    font-weight: 500;
    cursor: pointer;
    padding: 4px 8px;
    border-radius: 4px;
    transition: all 0.2s ease;
  }
  
  .viewReferralBtn:hover {
    background-color: #eff6ff;
    color: #1d4ed8;
  }
</style>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">

    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Referral Records</h1>
        <p class="text-gray-600">Manage and track medical referrals and their status.</p>
    </div>
    <!-- Summary Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <!-- Total Referrals Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="ri-file-list-3-line text-blue-600 text-lg"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Referrals</p>
                    <p class="text-2xl font-bold text-gray-900"><?php echo count($referrals); ?></p>
                </div>
            </div>
        </div>

        <!-- Referrals with Referral To Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="ri-check-line text-green-600 text-lg"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Referred</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php
                        $referred = 0;
                        foreach ($referrals as $referral) {
                            if (!empty($referral['referral_to'])) {
                                $referred++;
                            }
                        }
                        echo $referred;
                        ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- This Month Referrals Card -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i class="ri-calendar-line text-purple-600 text-lg"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">This Month</p>
                    <p class="text-2xl font-bold text-gray-900">
                        <?php
                        $thisMonth = 0;
                        $currentMonth = date('Y-m');
                        foreach ($referrals as $referral) {
                            if (date('Y-m', strtotime($referral['created_at'])) === $currentMonth) {
                                $thisMonth++;
                            }
                        }
                        echo $thisMonth;
                        ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Referral Records Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900">Referral Records</h3>
                    <p class="text-sm text-gray-500 mt-1">A list of all referral records and their details</p>
                </div>
                <!-- Search Bar -->
                <div class="flex items-center">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-search-line text-gray-400"></i>
                        </div>
                        <input type="text" id="referralSearch" name="referralSearch" placeholder="Search by name, ID, status, or referral destination..."
                            class="w-80 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" />
                    </div>
                </div>
            </div>
        </div>

        <!-- Table Content -->
        <div class="overflow-x-auto">
            <div class="overflow-x-auto" style="min-width: 800px;">
                <table id="referralTable" class="min-w-full divide-y divide-gray-200 table-fixed">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Patient Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Recorded By</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Referral To</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Created</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="referralTableBody" class="bg-white divide-y divide-gray-200">
                        <?php
                        // Paginate the referrals array
                        $paginated_referrals = array_slice($referrals, $referral_offset, $referral_records_per_page);
                        $referralCounter = $referral_offset + 1;
                        foreach ($paginated_referrals as $referral):
                            // Generate referral ID
                            $referralId = 'REF' . str_pad($referralCounter, 3, '0', STR_PAD_LEFT);

                            // Determine entity type and display name/id/level
                            $entityType = 'Student';
                            $displayName = $referral['patient_name'] ?? '';
                            $displayId = $referral['patient_id'] ?? '';
                            $yearLevel = $referral['year_level'] ?? '';
                            $email = $displayName ? strtolower(str_replace(' ', '.', $displayName)) . '@email.com' : '';

                            if (!empty($referral['faculty_id'])) {
                                $entityType = 'Teacher';
                                $displayName = $referral['faculty_name'] ?? '';
                                $displayId = $referral['faculty_id'] ?? '';
                                $yearLevel = 'Teacher';
                                $email = $displayName ? strtolower(str_replace(' ', '.', $displayName)) . '@email.com' : '';
                            } elseif (!empty($referral['visitor_id'])) {
                                $entityType = 'Visitor';
                                $displayName = $referral['visitor_name'] ?? '';
                                $displayId = $referral['visitor_id'] ?? '';
                                $yearLevel = 'Visitor';
                                $email = $displayName ? strtolower(str_replace(' ', '.', $displayName)) . '@email.com' : '';
                            }

                            // Generate referrer name (staff member who recorded)
                            $referrerName = $referral['recorded_by_name'] ?? 'Staff Member';

                            // Determine status based on referral_to field
                            $status = !empty($referral['referral_to']) ? 'Referred' : 'Pending';
                            $statusClass = '';
                            switch (strtolower($status)) {
                                case 'referred':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    break;
                                case 'pending':
                                    $statusClass = 'bg-yellow-100 text-yellow-800';
                                    break;
                                case 'cancelled':
                                    $statusClass = 'bg-red-100 text-red-800';
                                    break;
                                default:
                                    $statusClass = 'bg-gray-100 text-gray-800';
                            }

                            // Show referral destination or "Not specified"
                            $referralDestination = !empty($referral['referral_to']) ? $referral['referral_to'] : 'Not specified';
                        ?>
                        <tr
                            data-name="<?php echo htmlspecialchars($displayName); ?>"
                            data-patient-id="<?php echo htmlspecialchars($displayId); ?>"
                            data-recorded-by="<?php echo htmlspecialchars($referral['recorded_by'] ?? 'Staff'); ?>"
                                data-status="<?php echo htmlspecialchars($status); ?>"
                            data-entity-type="<?php echo $entityType; ?>"
                                class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm text-gray-900 truncate max-w-xs" title="<?php echo htmlspecialchars($displayName); ?>">
                                    <div class="truncate"><?php echo htmlspecialchars($displayName); ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 truncate max-w-xs" title="<?php echo htmlspecialchars($referrerName); ?>">
                                    <div class="truncate"><?php echo htmlspecialchars($referrerName); ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 truncate max-w-xs" title="<?php echo htmlspecialchars($referralDestination); ?>">
                                    <div class="truncate"><?php echo htmlspecialchars($referralDestination); ?></div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap"><?php echo date('M j, Y', strtotime($referral['created_at'])); ?></td>
                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo $statusClass; ?>">
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <button class="viewReferralBtn text-blue-600 hover:text-blue-900"
                                        data-id="<?php echo $referral['id']; ?>"
                                        data-patient-name="<?php echo htmlspecialchars($displayName); ?>"
                                        data-patient-id="<?php echo htmlspecialchars($displayId); ?>"
                                        data-year-level="<?php echo htmlspecialchars($yearLevel ?: $entityType); ?>"
                                        data-entity-type="<?php echo $entityType; ?>"
                                        data-debug-referral-id="<?php echo htmlspecialchars($referral['referral_patient_id'] ?? 'NULL'); ?>"
                                        data-debug-matched-id="<?php echo htmlspecialchars($referral['matched_student_id'] ?? 'NULL'); ?>"
                                        data-date="<?php echo date('Y-m-d', strtotime($referral['created_at'])); ?>"
                                        data-recorded-by="<?php echo htmlspecialchars($referral['recorded_by'] ?? 'Staff'); ?>"
                                        data-subjective="<?php echo htmlspecialchars($referral['subjective']); ?>"
                                        data-objective="<?php echo htmlspecialchars($referral['objective']); ?>"
                                        data-assessment="<?php echo htmlspecialchars($referral['assessment']); ?>"
                                        data-plan="<?php echo htmlspecialchars($referral['plan']); ?>"
                                        data-intervention="<?php echo htmlspecialchars($referral['intervention']); ?>"
                                        data-evaluation="<?php echo htmlspecialchars($referral['evaluation']); ?>"
                                        data-referral-to="<?php echo htmlspecialchars($referral['referral_to'] ?? ''); ?>"
                                        data-body-temp="<?php echo htmlspecialchars($referral['body_temp'] ?? ''); ?>"
                                        data-resp-rate="<?php echo htmlspecialchars($referral['resp_rate'] ?? ''); ?>"
                                        data-pulse="<?php echo htmlspecialchars($referral['pulse'] ?? ''); ?>"
                                        data-blood-pressure="<?php echo htmlspecialchars($referral['blood_pressure'] ?? ''); ?>"
                                        data-weight="<?php echo htmlspecialchars($referral['weight'] ?? ''); ?>"
                                        data-height="<?php echo htmlspecialchars($referral['height'] ?? ''); ?>"
                                        data-oxygen-sat="<?php echo htmlspecialchars($referral['oxygen_sat'] ?? ''); ?>">
                                        View Details
                                </button>
                            </td>
                        </tr>
                        <?php
                            $referralCounter++;
                        endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Pagination and Records Info (PHP, matches Patient Records design) -->
        <?php if ($referral_total_records > 0): ?>
        <div class="flex justify-between items-center mt-6 px-6 py-4">
            <div class="text-sm text-gray-600">
                Showing <?php echo $referral_start; ?> to <?php echo $referral_end; ?> of <?php echo $referral_total_records; ?> entries
            </div>
            <?php if ($referral_total_pages > 1): ?>
            <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">
                <?php if ($referral_page > 1): ?>
                    <a href="?referral_page=<?php echo $referral_page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
                        <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
                        <span class="sr-only">Previous</span>
                    </a>
                <?php else: ?>
                    <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Previous">
                        <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m15 18-6-6 6-6"></path>
                        </svg>
                        <span class="sr-only">Previous</span>
                    </button>
                <?php endif; ?>
                <?php 
                $referral_start_page = max(1, $referral_page - 2);
                $referral_end_page = min($referral_total_pages, $referral_page + 2);
                if ($referral_start_page > 1): ?>
                    <a href="?referral_page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">1</a>
                    <?php if ($referral_start_page > 2): ?>
                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                    <?php endif; ?>
                <?php endif; ?>
                <?php for ($i = $referral_start_page; $i <= $referral_end_page; $i++): ?>
                    <?php if ($i == $referral_page): ?>
                        <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300" aria-current="page"><?php echo $i; ?></button>
                    <?php else: ?>
                        <a href="?referral_page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($referral_end_page < $referral_total_pages): ?>
                    <?php if ($referral_end_page < $referral_total_pages - 1): ?>
                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                    <?php endif; ?>
                    <a href="?referral_page=<?php echo $referral_total_pages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $referral_total_pages; ?></a>
                <?php endif; ?>
                <?php if ($referral_page < $referral_total_pages): ?>
                    <a href="?referral_page=<?php echo $referral_page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
                        <span class="sr-only">Next</span>
                        <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </a>
                <?php else: ?>
                    <button type="button" disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Next">
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

    <!-- Referral View Modal -->
    <div id="referralViewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-4xl max-h-[90vh] relative">
            <button id="closeReferralModal" class="absolute top-2 right-4 text-gray-500 hover:text-gray-700 z-10">
                <i class="ri-close-line ri-2x"></i>
            </button>
            <div class="overflow-y-auto p-8 max-h-[90vh]">
            
            <!-- Printable Content -->
            <div id="printableContent" class="space-y-6">
                <!-- Header -->
                <div class="border-b pb-8 mb-6">
                    <div class="relative">
                        <!-- Left Logo -->
                        <div style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%);">
                            <img src="../logo.jpg" alt="Logo" class="w-16 h-16 object-cover rounded-full">
                        </div>
                        
                        <!-- Center Title -->
                        <div class="text-center">
                            <h1 class="text-2xl font-bold text-gray-800">MEDICAL REFERRAL</h1>
                        </div>
                        
                        <!-- Right Logo -->
                        <div style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%);">
                            <img src="../refer.png" alt="Refer" class="w-16 h-16 object-cover rounded-full">
                        </div>
                    </div>
                </div>

                <!-- Form Layout -->
                <div class="space-y-4">
                    <!-- Date Row - Top Right -->
                    <div class="flex justify-end mb-16">
                        <div class="flex items-center">
                            <span class="font-semibold text-sm mr-2">Date:</span>
                            <span id="modalDate" class="text-sm"><?php echo date('Y-m-d'); ?></span>
                        </div>
                    </div>

                    <!-- Name of Student and Grade/Level Row -->
                    <div class="grid grid-cols-2 gap-8">
                        <div>
                            <div class="flex items-center">
                                <span class="font-semibold text-sm mr-2">Name of Student:</span>
                                <span id="modalPatientName" class="text-sm"></span>
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center">
                                <span class="font-semibold text-sm mr-2">Grade/Level:</span>
                                <span id="modalYearLevel" class="text-sm"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Initial Vital Signs Row -->
                    <div class="mt-4">
                        <div class="text-sm">
                            <div class="font-semibold mb-2">Initial Vital Signs</div>
                            <div class="grid grid-cols-4 gap-6 text-xs">
                                <div class="flex items-center">
                                    <span class="font-medium mr-2">BP:</span>
                                    <span id="modalBP"></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-medium mr-2">P:</span>
                                    <span id="modalPulse"></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-medium mr-2">R:</span>
                                    <span id="modalRespRate"></span>
                                </div>
                                <div class="flex items-center">
                                    <span class="font-medium mr-2">Temp:</span>
                                    <span id="modalTemp"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Third Row - Incident Information -->
                    <!-- SOAP Notes Section -->
                    <div class="space-y-4 mt-40 pt-8">
                        <!-- Subjective -->
                        <div>
                            <div class="flex items-start mb-2">
                                <span class="font-semibold text-sm mr-2">Observation / Additional Information</span>
                            </div>
                            <div class="mb-3">
                                <span class="font-semibold text-sm">Subjective =</span>
                                <div class="ml-4">
                                    <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]">
                                        <span id="modalSubjective" class="text-xs"></span>
                                    </div>
                                    <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]"></div>
                                    <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]"></div>
                                </div>
                            </div>
                        </div>
                        <!-- Objective -->
                        <div>
                            <span class="font-semibold text-sm">Objective =</span>
                            <div class="ml-4">
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]">
                                    <span id="modalObjective" class="text-xs"></span>
                                </div>
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]"></div>
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]"></div>
                            </div>
                        </div>
                        <!-- Assessment -->
                        <div>
                            <span class="font-semibold text-sm">Assessment =</span>
                            <div class="ml-4">
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]">
                                    <span id="modalAssessment" class="text-xs"></span>
                                </div>
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]"></div>
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]"></div>
                            </div>
                        </div>
                        <!-- Plan -->
                        <div>
                            <span class="font-semibold text-sm">Plan =</span>
                            <div class="ml-4">
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]">
                                    <span id="modalPlan" class="text-xs"></span>
                                </div>
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]"></div>
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]"></div>
                            </div>
                        </div>
                        <!-- Intervention -->
                        <div>
                            <span class="font-semibold text-sm">Intervention =</span>
                            <div class="ml-4">
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]">
                                    <span id="modalIntervention" class="text-xs"></span>
                                </div>
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]"></div>
                            </div>
                        </div>
                        <!-- Evaluation -->
                        <div>
                            <span class="font-semibold text-sm">Evaluation =</span>
                            <div class="ml-4">
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]">
                                    <span id="modalEvaluation" class="text-xs"></span>
                                </div>
                                <div class="border-b border-gray-300 pb-1 mb-1 min-h-[20px]"></div>
                            </div>
                        </div>
                    </div>

                        

                    <!-- Vital Signs Footer -->
                    

                    <!-- Referral Section -->
                    <div class="mt-6">
                        <div class="flex items-center">
                            <span class="font-medium mr-2">Referral to:</span>
                            <span id="modalReferralTo"></span>
                        </div>
                    </div>

                    <!-- Signature -->
                    <div class="flex justify-start mt-40 pt-8">
                        <div class="text-center">
                            
                            <div class="text-sm">
                                <div class="font-semibold" id="modalRecordedBy">Mrs. Vilma A. Valencia</div>
                                <div class="border-b border-gray-400 w-48 mt-2"></div>
                                <div class="text-xs">School Nurse</div>
                            </div>
                        </div>
                    </div>

                    <!-- Print Button - Bottom Right -->
                    <div class="flex justify-end mt-6">
                        <button id="printReferralBtn" class="px-3 py-1 text-xs bg-green-600 text-white rounded hover:bg-green-700 flex items-center">
                            <i class="ri-printer-line mr-1"></i> Print
                        </button>
                    </div>
                </div>
            </div>
            </div>
        </div>
</main>

<script>
$(document).ready(function() {
    // Referral Records Search functionality (Server-side)
    const referralSearch = document.getElementById('referralSearch');
    
    if (referralSearch) {
        let searchTimeout;
        referralSearch.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Set new timeout for debounced search
            searchTimeout = setTimeout(() => {
                if (searchTerm.length >= 2 || searchTerm.length === 0) {
                    performReferralSearch(searchTerm, 1); // Always start from page 1 for new searches
                }
            }, 300);
        });
    }
    
    function performReferralSearch(searchTerm, page = 1) {
        // No loading state for seamless real-time search
        
        // Store search term for pagination
        window.currentReferralSearchTerm = searchTerm;
        
        // If search is cleared, show all data without page reload
        if (!searchTerm || searchTerm.trim() === '') {
            window.currentReferralSearchTerm = null;
            // Make AJAX request to get all data without search filter
            fetch('search_referrals.php', {
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
                        updateReferralTable(data.referrals, data.pagination);
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
        fetch('search_referrals.php', {
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
                    updateReferralTable(data.referrals, data.pagination);
                } else {
                    console.error('Search error:', data.message);
                    // Show error or fallback
                    if (referralTableBody) {
                        referralTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';
                    }
                }
            } catch (parseError) {
                console.error('JSON parse error:', parseError);
                console.error('Response text:', text);
                if (referralTableBody) {
                    referralTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';
                }
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            if (referralTableBody) {
                referralTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';
            }
        });
    }
    
    function updateReferralTable(referrals, pagination = null) {
        const referralTableBody = document.getElementById('referralTableBody');
        if (!referralTableBody) return;
        
        if (referrals.length === 0) {
            referralTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-file-list-3-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No referral records found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
            // Hide pagination when no results
            const paginationContainer = document.querySelector('.flex.justify-between.items-center.mt-6');
            if (paginationContainer) {
                paginationContainer.style.display = 'none';
            }
            return;
        }
        
        // Show pagination when results are found
        const paginationContainer = document.querySelector('.flex.justify-between.items-center.mt-6');
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
                updateReferralPaginationNumbers(pagination);
            } else {
                // If no pagination data, replace the entire pagination container with simple info
                paginationContainer.innerHTML = `
                    <div class="text-sm text-gray-600">
                        Showing 1 to ${referrals.length} of ${referrals.length} entries
                    </div>
                `;
            }
        }
        
        let html = '';
        referrals.forEach((referral, index) => {
            // Generate referral ID
            const referralId = 'REF' + String(index + 1).padStart(3, '0');
            
            // Determine status class
            const status = referral.status;
            let statusClass = '';
            switch (status.toLowerCase()) {
                case 'referred':
                    statusClass = 'bg-green-100 text-green-800';
                    break;
                case 'pending':
                    statusClass = 'bg-yellow-100 text-yellow-800';
                    break;
                case 'cancelled':
                    statusClass = 'bg-red-100 text-red-800';
                    break;
                default:
                    statusClass = 'bg-gray-100 text-gray-800';
            }
            
            html += `
                <tr data-name="${referral.display_name.toLowerCase()}" 
                    data-patient-id="${referral.display_id.toLowerCase()}" 
                    data-recorded-by="${referral.recorded_by.toLowerCase()}" 
                    data-status="${referral.status.toLowerCase()}" 
                    data-entity-type="${referral.entity_type}" 
                    class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-sm text-gray-900 truncate max-w-xs" title="${referral.display_name}">
                        <div class="truncate">${referral.display_name}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 truncate max-w-xs" title="${referral.recorded_by_name}">
                        <div class="truncate">${referral.recorded_by_name}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-900 truncate max-w-xs" title="${referral.referral_to}">
                        <div class="truncate">${referral.referral_to}</div>
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-500 whitespace-nowrap">${new Date(referral.created_at).toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
                    <td class="px-4 py-3 text-center">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${statusClass}">
                            ${referral.status.charAt(0).toUpperCase() + referral.status.slice(1)}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <button class="viewReferralBtn text-blue-600 hover:text-blue-900"
                            data-id="${referral.id}"
                            data-patient-name="${referral.display_name}"
                            data-patient-id="${referral.display_id}"
                            data-year-level="${referral.year_level}"
                            data-entity-type="${referral.entity_type}"
                            data-date="${new Date(referral.created_at).toISOString().split('T')[0]}"
                            data-recorded-by="${referral.recorded_by}"
                            data-subjective="${referral.subjective}"
                            data-objective="${referral.objective}"
                            data-assessment="${referral.assessment}"
                            data-plan="${referral.plan}"
                            data-intervention="${referral.intervention}"
                            data-evaluation="${referral.evaluation}"
                            data-referral-to="${referral.referral_to}"
                            data-body-temp="${referral.body_temp}"
                            data-resp-rate="${referral.resp_rate}"
                            data-pulse="${referral.pulse}"
                            data-blood-pressure="${referral.blood_pressure}"
                            data-weight="${referral.weight}"
                            data-height="${referral.height}"
                            data-oxygen-sat="${referral.oxygen_sat}">
                            View Details
                        </button>
                    </td>
                </tr>
            `;
        });
        
        referralTableBody.innerHTML = html;
    }
    
    function updateReferralPaginationNumbers(pagination) {
        const paginationNav = document.querySelector('nav[aria-label="Pagination"]');
        if (!paginationNav) return;
        
        const currentPage = pagination.current_page;
        const totalPages = pagination.total_pages;
        
        // Clear existing pagination
        paginationNav.innerHTML = '';
        
        // Previous button
        if (currentPage > 1) {
            const prevBtn = document.createElement('a');
            const searchParam = window.currentReferralSearchTerm ? `&search=${encodeURIComponent(window.currentReferralSearchTerm)}` : '';
            prevBtn.href = `?referral_page=${currentPage - 1}${searchParam}`;
            prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';
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
            prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';
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
            const searchParam = window.currentReferralSearchTerm ? `&search=${encodeURIComponent(window.currentReferralSearchTerm)}` : '';
            firstPage.href = `?referral_page=1${searchParam}`;
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
                const searchParam = window.currentReferralSearchTerm ? `&search=${encodeURIComponent(window.currentReferralSearchTerm)}` : '';
                pageLink.href = `?referral_page=${i}${searchParam}`;
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
            const searchParam = window.currentReferralSearchTerm ? `&search=${encodeURIComponent(window.currentReferralSearchTerm)}` : '';
            lastPage.href = `?referral_page=${totalPages}${searchParam}`;
            lastPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100';
            lastPage.textContent = totalPages;
            paginationNav.appendChild(lastPage);
        }
        
        // Next button
        if (currentPage < totalPages) {
            const nextBtn = document.createElement('a');
            const searchParam = window.currentReferralSearchTerm ? `&search=${encodeURIComponent(window.currentReferralSearchTerm)}` : '';
            nextBtn.href = `?referral_page=${currentPage + 1}${searchParam}`;
            nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';
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
            nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';
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
    
    // Handle pagination clicks for referral search results
    document.addEventListener('click', function(e) {
        // Check if it's a pagination link for referral table
        if (e.target.closest('nav[aria-label="Pagination"] a')) {
            const link = e.target.closest('a');
            const href = link.getAttribute('href');
            
            // Always prevent default and use AJAX for pagination
            if (href.includes('referral_page=')) {
                e.preventDefault();
                
                // Extract page number from href
                const pageMatch = href.match(/referral_page=(\d+)/);
                if (pageMatch) {
                    const page = parseInt(pageMatch[1]);
                    // Use search function with current search term (or empty if no search)
                    const searchTerm = window.currentReferralSearchTerm || '';
                    performReferralSearch(searchTerm, page);
                }
            }
        }
    });

    // View Referral Button Click
    $(document).on('click', '.viewReferralBtn', function() {
        const data = $(this).data();
        
        // Store current referral ID globally for printing
        window.currentReferralId = data.id;
        
        // Show modal first
        $('#referralViewModal').removeClass('hidden');
        
        // Get current date dynamically and set immediately
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        const currentDate = `${year}-${month}-${day}`;
        
        // Set date immediately - no setTimeout
        $('#modalDate').text(currentDate);
        
        // Populate other modal data
        $('#modalPatientName').text(data.patientName || 'N/A');
        
        // Debug: Check all possible ways to get year level
        console.log('All data attributes:', data);
        console.log('data.yearLevel:', data.yearLevel);
        console.log('data["year-level"]:', data["year-level"]);
        console.log('Button element:', this);
        console.log('Raw data-year-level attr:', $(this).attr('data-year-level'));
        
        // Try multiple ways to get the year level
        let yearLevel = data.yearLevel || data["year-level"] || $(this).attr('data-year-level') || 'Still N/A';
        $('#modalYearLevel').text(yearLevel);
        
        $('#modalRecordedBy').text(data.recordedBy || 'Staff');
        $('#modalSubjective').text(data.subjective || 'No information provided');
        $('#modalObjective').text(data.objective || 'No information provided');
        $('#modalAssessment').text(data.assessment || 'No information provided');
        $('#modalPlan').text(data.plan || 'No information provided');
        $('#modalIntervention').text(data.intervention || 'No information provided');
        $('#modalEvaluation').text(data.evaluation || 'No information provided');
        
    // Populate referral_to field in modal as plain text
    const existingReferralTo = data.referralTo || data["referral-to"] || $(this).attr('data-referral-to') || '';
    $('#modalReferralTo').text(existingReferralTo);
        
        // Populate vital signs data from the latest records
        console.log('Debug blood pressure data:', {
            dataBloodPressure: data.bloodPressure,
            dataBloodPressureKebab: data["blood-pressure"],
            attrBloodPressure: $(this).attr('data-blood-pressure')
        });
        
        $('#modalBP').text(data.bloodPressure || data["blood-pressure"] || $(this).attr('data-blood-pressure') || '');
        $('#modalPulse').text(data.pulse || $(this).attr('data-pulse') || '');
        $('#modalRespRate').text(data.respRate || data["resp-rate"] || $(this).attr('data-resp-rate') || '');
        $('#modalTemp').text(data.bodyTemp || data["body-temp"] || $(this).attr('data-body-temp') || '');
        
        console.log('Current date set to:', currentDate);
        console.log('Modal date element text:', $('#modalDate').text());
        console.log('All vital signs data:', {
            bloodPressure: data.bloodPressure || data["blood-pressure"] || $(this).attr('data-blood-pressure'),
            pulse: data.pulse || $(this).attr('data-pulse'),
            respRate: data.respRate || data["resp-rate"] || $(this).attr('data-resp-rate'),
            bodyTemp: data.bodyTemp || data["body-temp"] || $(this).attr('data-body-temp')
        });
    });

    // Close Modal
    $('#closeReferralModal').on('click', function() {
        $('#referralViewModal').addClass('hidden');
        
        // Reset referral section back to input field for next use
        resetReferralSection();
    });

    // Close modal when clicking outside
    $(window).on('click', function(e) {
        if (e.target === document.getElementById('referralViewModal')) {
            $('#referralViewModal').addClass('hidden');
            
            // Reset referral section back to input field for next use
            resetReferralSection();
        }
    });
    
    // Function to reset referral section to input field
    function resetReferralSection() {
        const referralSection = $('#modalReferralTo').parent();
        referralSection.html(`
            <span class="font-semibold text-sm mr-2">Referral to:</span>
            <span id="modalReferralToLine" class="text-sm font-medium text-gray-800"></span>
        `);
    }

    // Print Referral
    $('#printReferralBtn').on('click', function() {
        // Check if "Referral to" field is filled - handle both input field and static text
        let referralToValue = '';
        const referralInput = $('#modalReferralTo');
        
        if (referralInput.length && referralInput.is('input')) {
            // It's still an input field
            referralToValue = referralInput.val().trim();
        } else {
            // It's been converted to static text, get the text content
            const staticReferralSpan = referralInput.parent().find('span').last();
            referralToValue = staticReferralSpan.text().trim();
        }
        
        if (!referralToValue) {
            showErrorModal('Please fill in the "Referral to" field before printing.', 'Validation Error');
            if (referralInput.length && referralInput.is('input')) {
                referralInput.focus(); // Focus on the empty field if it's still an input
            }
            return; // Stop the printing process
        }
        
        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        const printContent = document.getElementById('printableContent').innerHTML;
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Medical Referral Form</title>
                <style>
                    @page {
                        size: A4;
                        margin: 0.5in;
                    }
                    body { 
                        font-family: Arial, sans-serif; 
                        margin: 0;
                        padding: 0;
                        line-height: 1.3;
                        color: #333;
                        font-size: 12px;
                        height: 100vh;
                        display: flex;
                        flex-direction: column;
                    }
                    .text-center { text-align: center; }
                    .font-bold { font-weight: bold; }
                    .text-2xl { font-size: 18px; margin-bottom: 4px; }
                    .text-lg { font-size: 14px; }
                    .text-sm { font-size: 11px; }
                    .text-xs { font-size: 10px; }
                    .mb-2 { margin-bottom: 4px; }
                    .mb-3 { margin-bottom: 6px; }
                    .mb-6 { margin-bottom: 8px; }
                    .mt-2 { margin-top: 4px; }
                    .mt-8 { margin-top: 12px; }
                    .p-3 { padding: 8px; }
                    .pt-4 { padding-top: 8px; }
                    .pb-4 { padding-bottom: 8px; }
                    .border { border: 1px solid #ccc; }
                    .border-b { border-bottom: 1px solid #ccc; }
                    .border-t { border-top: 1px solid #ccc; }
                    .border-gray-400 { border-color: #9ca3af; }
                    .bg-gray-50 { background-color: #f9fafb; }
                    .text-gray-600 { color: #6b7280; }
                    .text-gray-500 { color: #9ca3af; }
                    .text-gray-800 { color: #1f2937; }
                    .rounded { border-radius: 4px; }
                    .space-y-2 > * + * { margin-top: 4px; }
                    .space-y-4 > * + * { margin-top: 6px; }
                    .space-y-6 > * + * { margin-top: 8px; }
                    .grid { display: grid; }
                    .grid-cols-2 { grid-template-columns: repeat(2, 1fr); }
                    .gap-6 { gap: 12px; }
                    .gap-8 { gap: 16px; }
                    .min-h-60 { min-height: 40px; }
                    .w-full { width: 100%; }
                    
                    /* Preserve modal spacing for print */
                    .mt-40 { margin-top: 10rem !important; }
                    .pt-8 { padding-top: 2rem !important; }
                    .mt-6 { margin-top: 1.5rem !important; }
                    .mt-12 { margin-top: 3rem !important; }
                    .mb-16 { margin-bottom: 4rem !important; }
                    .gap-8 { gap: 2rem !important; }
                    .gap-6 { gap: 1.5rem !important; }
                    
                    /* Ensure inline styles are preserved */
                    [style*="margin-top: 4rem"] { margin-top: 4rem !important; }
                    
                    /* Form layout preservation */
                    .grid-cols-2 { 
                        display: grid; 
                        grid-template-columns: repeat(2, 1fr); 
                    }
                    .grid-cols-4 { 
                        display: grid; 
                        grid-template-columns: repeat(4, 1fr); 
                    }
                    .flex { display: flex; }
                    .justify-end { justify-content: flex-end; }
                    .justify-start { justify-content: flex-start; }
                    .justify-center { justify-content: center; }
                    .items-center { align-items: center; }
                    .items-start { align-items: flex-start; }
                    
                    /* Hide print button in print view */
                    #printReferralBtn { display: none !important; }
                    
                    @media print {
                        body { 
                            margin: 0; 
                            font-size: 12px !important;
                            height: auto;
                        }
                        .no-print { display: none; }
                        #printReferralBtn { display: none !important; }
                        
                        /* Preserve exact modal spacing */
                        .mt-40 { margin-top: 10rem !important; }
                        .pt-8 { padding-top: 2rem !important; }
                        .mt-6 { margin-top: 1.5rem !important; }
                        .mt-12 { margin-top: 3rem !important; }
                        .mb-16 { margin-bottom: 4rem !important; }
                        .gap-8 { gap: 2rem !important; }
                        .gap-6 { gap: 1.5rem !important; }
                        .space-y-4 > * + * { margin-top: 1rem !important; }
                        .space-y-6 > * + * { margin-top: 1.5rem !important; }
                        
                        /* Ensure proper layout structure */
                        .grid { display: grid !important; }
                        .grid-cols-2 { grid-template-columns: repeat(2, 1fr) !important; }
                        .grid-cols-4 { grid-template-columns: repeat(4, 1fr) !important; }
                        .flex { display: flex !important; }
                        .justify-end { justify-content: flex-end !important; }
                        .justify-start { justify-content: flex-start !important; }
                        .text-center { text-align: center !important; }
                        
                        /* Preserve border styling */
                        .border-b { border-bottom: 1px solid #9ca3af !important; }
                        .border-gray-400 { border-color: #9ca3af !important; }
                        .w-48 { width: 12rem !important; }
                        
                        /* Font sizes */
                        .text-2xl { font-size: 1.5rem !important; }
                        .text-sm { font-size: 0.875rem !important; }
                        .text-xs { font-size: 0.75rem !important; }
                        
                        /* Color adjustments for print */
                        * {
                            -webkit-print-color-adjust: exact !important;
                            color-adjust: exact !important;
                        }
                        
                        .min-h-60 { 
                            min-height: 20px !important; 
                        }
                    }
                </style>
            </head>
            <body>
                ${printContent}
            </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
        
        // Save the referral data to database after printing
        const referralId = window.currentReferralId;
        
        // Send AJAX request to save referral destination
        $.ajax({
            url: 'update_referral.php',
            type: 'POST',
            data: {
                referral_id: referralId,
                referral_to: referralToValue
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log('Referral destination saved successfully');
                    
                    // Change the input field to display static text after successful save
                    const referralSection = $('#modalReferralTo').parent();
                    referralSection.html(`
                        <span class="font-semibold text-sm mr-2">Referral to:</span>
                        <span class="text-sm font-medium text-gray-800">${referralToValue}</span>
                    `);
                    
                    // Optional: Show success message to user
                    // alert('Referral printed and saved successfully!');
                } else {
                    console.error('Failed to save referral destination:', response.message);
                    showWarningModal('Print completed, but failed to save referral destination.', 'Warning');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
                showErrorModal('Print completed, but there was an error saving the referral destination.', 'Error');
            }
        });
    });
});
</script>
</main>