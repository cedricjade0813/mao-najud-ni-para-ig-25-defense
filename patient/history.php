<?php
include '../includep/header.php';
?>
<style>
/* Mobile menu button styling - only for mobile */
@media (max-width: 768px) {
    #mobileMenuBtn {
        min-width: 44px !important;
        min-height: 44px !important;
        padding: 2px !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        touch-action: manipulation !important;
        cursor: pointer !important;
        margin-right: 1rem !important;
        flex-shrink: 0 !important;
        align-self: flex-start !important;
    }

    #mobileMenuBtn i {
        pointer-events: none !important;
        user-select: none !important;
    }
}

/* Hide sidebar on mobile */
@media (max-width: 768px) {
    aside {
        transform: translateX(-100%) !important;
        transition: transform 0.3s ease-in-out;
    }
    
    aside.mobile-open {
        transform: translateX(0) !important;
    }
    
    main {
        margin-left: 0 !important;
    }
}

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

/* Custom styles matching the guide design */
.status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    text-transform: capitalize;
}

.status-active {
    background-color: #dcfce7;
    color: #166534;
}

.status-completed {
    background-color: #dbeafe;
    color: #1e40af;
}

.table-row:hover {
    background-color: #f8fafc;
}

.action-button {
    color: #3b82f6;
    transition: color 0.2s ease;
}

.action-button:hover {
    color: #1d4ed8;
}

.filter-section {
    background: white;
    border-radius: 8px;
    padding: 24px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    margin-bottom: 24px;
}

.content-card {
    background: white;
    border-radius: 8px;
    padding: 32px;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
}

.page-header {
    margin-bottom: 32px;
}

.page-title {
    font-size: 28px;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 8px;
}

.page-subtitle {
    color: #6b7280;
    font-size: 16px;
    line-height: 1.5;
}

.section-title {
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.export-button {
    background: #1f2937;
    color: white;
    padding: 8px 16px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    transition: background-color 0.2s ease;
}

.export-button:hover {
    background: #374151;
}

.search-input {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px 16px;
    font-size: 14px;
    color: #374151;
    width: 240px;
    transition: all 0.2s ease;
}

.search-input:focus {
    outline: none;
    border-color: #3b82f6;
    background: white;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.filter-button {
    background: #3b82f6;
    color: white;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.filter-button:hover {
    background: #2563eb;
}

.clear-button {
    background: #f3f4f6;
    color: #374151;
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: background-color 0.2s ease;
}

.clear-button:hover {
    background: #e5e7eb;
}

/* Mobile responsive styles for medical history */
@media (max-width: 400px) {
    /* Medical History title and subtitle - matching profile page styling */
    .history-title {
        font-size: 18px !important;
        line-height: 1.2 !important;
    }
    
    .history-subtitle {
        font-size: 10px !important;
        line-height: 1.2 !important;
    }
}
</style>

<?php
// Get the current logged-in user ID
$student_id = $_SESSION['student_row_id'];

// Display current user information for debugging
echo "<!-- Current User Debug Info -->";
echo "<!-- Student Row ID: " . $student_id . " -->";
echo "<!-- Student ID: " . ($_SESSION['student_id'] ?? 'Not set') . " -->";
echo "<!-- Patient Name: " . ($_SESSION['patient_name'] ?? 'Not set') . " -->";
echo "<!-- Role: " . ($_SESSION['role'] ?? 'Not set') . " -->";

// Fetch prescription data with doctor information
$medicalHistory = [];
try {
    // Get prescriptions with doctor information from doctor_schedules and user names
    $stmt = $db->prepare('
        SELECT 
            p.id,
            p.prescription_date,
            p.reason,
            p.medicines,
            p.prescribed_by,
            p.notes,
            ds.doctor_name,
            u.name as prescribed_by_name
        FROM prescriptions p
        LEFT JOIN doctor_schedules ds ON DATE(p.prescription_date) = ds.schedule_date
        LEFT JOIN users u ON p.prescribed_by = u.username
        WHERE p.patient_id = ? 
        ORDER BY p.prescription_date DESC
    ');
    $stmt->execute([$student_id]);
    $prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Process prescriptions and create medical history records (one row per prescription)
    foreach ($prescriptions as $prescription) {
        $date = date('M j, Y', strtotime($prescription['prescription_date']));
        $time = date('g:i A', strtotime($prescription['prescription_date']));
        $doctor = $prescription['doctor_name'] ?? 'Dr. Medical Officer';
        $reason = $prescription['reason'] ?? 'N/A';
        
        // Get medicines list for display
        $medicine_list = 'No medicine prescribed';
        if (!empty($prescription['medicines'])) {
            $medicines = json_decode($prescription['medicines'], true);
            if (is_array($medicines) && !empty($medicines)) {
                $medicine_names = array_map(function($med) {
                    return $med['medicine'] ?? 'Unknown';
                }, $medicines);
                $medicine_list = implode(', ', $medicine_names);
            }
        }
        
        // Create one record per prescription
        $medicalHistory[] = [
            'id' => $prescription['id'],
            'date' => $date,
            'time' => $time,
            'doctor' => $doctor,
            'reason' => $reason,
            'medicine' => $medicine_list,
            'prescribed_by' => $prescription['prescribed_by_name'] ?? $prescription['prescribed_by'] ?? 'Unknown',
            'quantity' => 'See details',
            'dosage' => 'See details',
            'frequency' => 'See details',
            'instructions' => 'See details',
            'prescription_data' => $prescription
        ];
    }
} catch (PDOException $e) {
    $medicalHistory = [];
}

// Pagination for medical history
$med_records_per_page = 10;
$med_page = isset($_GET['med_page']) ? (int)$_GET['med_page'] : 1;
$med_page = max($med_page, 1);
$med_offset = ($med_page - 1) * $med_records_per_page;
$total_med_records = count($medicalHistory);
$total_med_pages = ceil($total_med_records / $med_records_per_page);
$medicalHistory_paginated = array_slice($medicalHistory, $med_offset, $med_records_per_page);
?>
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px] scrollbar-hide">
    <!-- Page Header -->
    <div class="mb-8">
        <div class="flex items-center">
            <!-- Mobile menu button -->
            <button id="mobileMenuBtn" class="md:hidden mr-4 text-gray-600 hover:text-gray-900 rounded-md min-w-[44px] min-h-[44px] flex items-center justify-center cursor-pointer" onclick="toggleMobileMenu()">
                <i class="ri-menu-line text-xl pointer-events-none"></i>
            </button>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2 history-title">Medical History</h1>
                <p class="text-gray-600 history-subtitle">View and manage patient health records and past consultations.</p>
            </div>
        </div>
    </div>


    <!-- Medical History Section -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-900">History</h3>
            <div class="relative">
                <input id="medicalHistorySearch" type="text" placeholder="Search medical records..." 
                       class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white h-10">
                <i class="ri-search-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
        <div class="overflow-x-auto scrollbar-hide">
            <table class="w-full divide-y divide-gray-200 table-fixed">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="w-1/4 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prescribed By</th>
                        <th class="w-1/3 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                        <th class="w-1/3 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medicine</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php
                    if (!empty($medicalHistory_paginated)) {
                        foreach ($medicalHistory_paginated as $record) {
                            echo "<tr class='hover:bg-gray-50'>";
                            echo "<td class='w-1/4 px-6 py-4 text-sm text-gray-900'>";
                            echo "<div class='flex items-start' title='" . $record['date'] . " at " . $record['time'] . "'>";
                            echo "<button onclick='viewPrescriptionDetails(" . json_encode($record['prescription_data']) . ")' class='action-button hover:bg-blue-50 p-1 rounded mr-2 flex-shrink-0' title='View Details'>";
                            echo "<i class='ri-eye-line text-lg'></i>";
                            echo "</button>";
                            echo "<div class='flex flex-col'>";
                            echo "<span class='font-medium text-sm'>" . $record['date'] . "</span>";
                            echo "<span class='text-gray-500 text-xs'>" . $record['time'] . "</span>";
                            echo "</div>";
                            echo "</div>";
                            echo "</td>";
                            echo "<td class='w-1/6 px-6 py-4 text-sm text-gray-900'>";
                            echo "<div class='truncate' title='" . htmlspecialchars($record['prescribed_by']) . "'>" . htmlspecialchars($record['prescribed_by']) . "</div>";
                            echo "</td>";
                            echo "<td class='w-1/3 px-6 py-4 text-sm text-gray-900'>";
                            echo "<div class='truncate' title='" . htmlspecialchars($record['reason']) . "'>" . htmlspecialchars($record['reason']) . "</div>";
                            echo "</td>";
                            echo "<td class='w-1/3 px-6 py-4 text-sm text-gray-900'>";
                            echo "<div class='truncate' title='" . htmlspecialchars($record['medicine']) . "'>" . htmlspecialchars($record['medicine']) . "</div>";
                            echo "</td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='4' class='px-6 py-8 text-center text-gray-500'>";
                        echo "<div class='flex flex-col items-center'>";
                        echo "<i class='ri-file-list-line text-2xl text-gray-300 mb-2'></i>";
                        echo "<p class='text-gray-500 text-sm font-medium'>No medical history found</p>";
                        echo "<p class='text-gray-400 text-xs'>Your medical records will appear here once you have completed appointments.</p>";
                        echo "</div>";
                        echo "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <!-- Pagination for Medical History -->
        <?php if ($total_med_records > 0): ?>
            <div class="flex justify-between items-center mt-6 px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="text-sm text-gray-600">
                    <?php 
                    $med_start = $med_offset + 1;
                    $med_end = min($med_offset + $med_records_per_page, $total_med_records);
                    ?>
                    Showing <?php echo $med_start; ?> to <?php echo $med_end; ?> of <?php echo $total_med_records; ?> entries
                </div>
                <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">
                    <?php if ($med_page > 1): ?>
                        <a href="?med_page=<?php echo $med_page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
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
                    $med_start_page = max(1, $med_page - 2);
                    $med_end_page = min($total_med_pages, $med_page + 2);
                    if ($med_start_page > 1): ?>
                        <a href="?med_page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">1</a>
                        <?php if ($med_start_page > 2): ?>
                            <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $med_start_page; $i <= $med_end_page; $i++): ?>
                        <?php if ($i == $med_page): ?>
                            <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300" aria-current="page"><?php echo $i; ?></button>
                        <?php else: ?>
                            <a href="?med_page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($med_end_page < $total_med_pages): ?>
                        <?php if ($med_end_page < $total_med_pages - 1): ?>
                            <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                        <?php endif; ?>
                        <a href="?med_page=<?php echo $total_med_pages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $total_med_pages; ?></a>
                    <?php endif; ?>
                    <?php if ($med_page < $total_med_pages): ?>
                        <a href="?med_page=<?php echo $med_page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
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
        <?php endif; ?>
    </div>
</main>

<!-- Prescription Details Modal -->
<div id="prescriptionModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full h-[600px] flex flex-col">
        <!-- Modal Header -->
        <div class="flex justify-between items-center p-6 border-b border-gray-200 bg-gray-50 flex-shrink-0 rounded-t-xl">
            <h3 id="modalTitle" class="text-xl font-semibold text-gray-800">Medication Details</h3>
            <button id="closePrescriptionModalTop" type="button" class="flex items-center justify-center w-8 h-8 rounded-full bg-gray-200 text-gray-600 hover:bg-gray-300 hover:text-gray-800 transition-colors" aria-label="Close">
                <i class="ri-close-line text-lg"></i>
            </button>
        </div>
        
        <!-- Modal Body -->
        <div class="p-6 overflow-y-auto flex-1" id="modalBody">
            <!-- Details will be populated here -->
        </div>
        
        <!-- Modal Footer -->
        <div class="flex justify-end p-6 border-t border-gray-200 bg-gray-50 flex-shrink-0 rounded-b-xl">
            <button id="closePrescriptionModalBottom" type="button" class="px-6 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors font-medium">
                Close
            </button>
        </div>
    </div>
</div>
<script>
    // Mobile menu toggle function
    function toggleMobileMenu() {
        const sidebar = document.querySelector('aside');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        
        if (sidebar.classList.contains('mobile-open')) {
            sidebar.classList.remove('mobile-open');
            mobileMenuBtn.innerHTML = '<i class="ri-menu-line text-xl"></i>';
        } else {
            sidebar.classList.add('mobile-open');
            mobileMenuBtn.innerHTML = '<i class="ri-close-line text-xl"></i>';
        }
    }

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
        const sidebar = document.querySelector('aside');
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        
        if (window.innerWidth <= 768 && 
            !sidebar.contains(event.target) && 
            !mobileMenuBtn.contains(event.target) && 
            sidebar.classList.contains('mobile-open')) {
            sidebar.classList.remove('mobile-open');
            mobileMenuBtn.innerHTML = '<i class="ri-menu-line text-xl"></i>';
        }
    });

document.addEventListener('DOMContentLoaded', function() {
    // Medical history search functionality
    const medicalHistorySearchInput = document.getElementById('medicalHistorySearch');
    let currentMedicalHistorySearchTerm = null;
    let currentMedicalHistoryPage = 1;
    
    if (medicalHistorySearchInput) {
        medicalHistorySearchInput.addEventListener('input', function() {
            const searchTerm = this.value.trim();
            currentMedicalHistoryPage = 1; // Reset to first page when searching
            searchMedicalHistory(searchTerm, 1);
        });
    }
    
    function searchMedicalHistory(searchTerm, page = 1) {
        // If search is cleared, show all data without page reload
        if (!searchTerm || searchTerm.trim() === '') {
            currentMedicalHistorySearchTerm = null;
            // Make AJAX request to get all data without search filter
            fetch('search_medical_history.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `search=&page=${page}&student_id=<?php echo $student_id; ?>`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateMedicalHistoryTable(data.records, data.pagination);
                } else {
                    console.error('Search error:', data.message);
                }
            })
            .catch(error => {
                console.error('Search request failed:', error);
            });
            return;
        }
        
        // Make AJAX request to server
        fetch('search_medical_history.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `search=${encodeURIComponent(searchTerm)}&page=${page}&student_id=<?php echo $student_id; ?>`
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                currentMedicalHistorySearchTerm = searchTerm;
                updateMedicalHistoryTable(data.records, data.pagination);
            } else {
                console.error('Search error:', data.message);
            }
        })
        .catch(error => {
            console.error('Search request failed:', error);
        });
    }
    
    function updateMedicalHistoryTable(records, pagination = null) {
        const medicalHistoryTableBody = document.querySelector('tbody');
        if (!medicalHistoryTableBody) return;
        
        if (records.length === 0) {
            medicalHistoryTableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-file-list-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No medical records found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
            // Hide pagination when no results
            const paginationContainer = document.querySelector('.flex.justify-between.items-center.mt-6.px-6.py-4.border-t.border-gray-200.bg-gray-50');
            if (paginationContainer) {
                paginationContainer.style.display = 'none';
            }
            return;
        }
        
        // Always show pagination
        const paginationContainer = document.querySelector('.flex.justify-between.items-center.mt-6.px-6.py-4.border-t.border-gray-200.bg-gray-50');
        if (paginationContainer) {
            paginationContainer.style.display = 'flex';
            
            // Update pagination info if provided
            if (pagination) {
                const startRecord = ((pagination.current_page - 1) * pagination.per_page) + 1;
                const endRecord = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);
                const infoText = paginationContainer.querySelector('.text-sm.text-gray-600');
                if (infoText) {
                    if (currentMedicalHistorySearchTerm) {
                        infoText.textContent = `Showing ${startRecord} to ${endRecord} of ${pagination.total_records} entries`;
                    } else {
                        infoText.textContent = `Showing ${startRecord} to ${endRecord} of ${pagination.total_records} entries`;
                    }
                }
            }
        }
        
        // Build table rows
        let html = '';
        records.forEach(record => {
            html += `
                <tr class="hover:bg-gray-50">
                    <td class="w-1/4 px-6 py-4 text-sm text-gray-900">
                        <div class="flex items-start" title="${record.date} at ${record.time}">
                            <button onclick="viewPrescriptionDetails(${JSON.stringify(record.prescription_data)})" class="action-button hover:bg-blue-50 p-1 rounded mr-2 flex-shrink-0" title="View Details">
                                <i class="ri-eye-line text-lg"></i>
                            </button>
                            <div class="flex flex-col">
                                <span class="font-medium text-sm">${record.date}</span>
                                <span class="text-gray-500 text-xs">${record.time}</span>
                            </div>
                        </div>
                    </td>
                    <td class="w-1/6 px-6 py-4 text-sm text-gray-900">
                        <div class="truncate" title="${record.prescribed_by}">${record.prescribed_by}</div>
                    </td>
                    <td class="w-1/3 px-6 py-4 text-sm text-gray-900">
                        <div class="truncate" title="${record.reason}">${record.reason}</div>
                    </td>
                    <td class="w-1/3 px-6 py-4 text-sm text-gray-900">
                        <div class="truncate" title="${record.medicine}">${record.medicine}</div>
                    </td>
                </tr>
            `;
        });
        
        medicalHistoryTableBody.innerHTML = html;
        
        // Update pagination numbers if provided
        if (pagination) {
            updateMedicalHistoryPaginationNumbers(pagination);
        }
    }
    
    function updateMedicalHistoryPaginationNumbers(pagination) {
        const paginationNav = document.querySelector('.flex.justify-between.items-center.mt-6.px-6.py-4.border-t.border-gray-200.bg-gray-50 nav[aria-label="Pagination"]');
        if (!paginationNav) return;
        
        const currentPage = pagination.current_page;
        const totalPages = pagination.total_pages;
        
        // Clear existing pagination
        paginationNav.innerHTML = '';
        
        // Previous button - always show
        if (currentPage > 1) {
            const prevBtn = document.createElement('a');
            const searchParam = currentMedicalHistorySearchTerm ? `&search=${encodeURIComponent(currentMedicalHistorySearchTerm)}` : '';
            prevBtn.href = `?med_page=${currentPage - 1}${searchParam}`;
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
            prevBtn.disabled = true;
            prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';
            prevBtn.setAttribute('aria-label', 'Previous');
            prevBtn.innerHTML = `
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                <span class="sr-only">Previous</span>
            `;
            paginationNav.appendChild(prevBtn);
        }
        
        // Calculate page range
        const startPage = Math.max(1, currentPage - 2);
        const endPage = Math.min(totalPages, currentPage + 2);
        
        // Always show page 1 if it's not in the current range
        if (startPage > 1) {
            const firstPage = document.createElement('a');
            const searchParam = currentMedicalHistorySearchTerm ? `&search=${encodeURIComponent(currentMedicalHistorySearchTerm)}` : '';
            firstPage.href = `?med_page=1${searchParam}`;
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
        
        // Show page numbers in range
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
                const searchParam = currentMedicalHistorySearchTerm ? `&search=${encodeURIComponent(currentMedicalHistorySearchTerm)}` : '';
                pageLink.href = `?med_page=${i}${searchParam}`;
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
            const searchParam = currentMedicalHistorySearchTerm ? `&search=${encodeURIComponent(currentMedicalHistorySearchTerm)}` : '';
            lastPage.href = `?med_page=${totalPages}${searchParam}`;
            lastPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100';
            lastPage.textContent = totalPages;
            paginationNav.appendChild(lastPage);
        }
        
        // Next button - always show
        if (currentPage < totalPages) {
            const nextBtn = document.createElement('a');
            const searchParam = currentMedicalHistorySearchTerm ? `&search=${encodeURIComponent(currentMedicalHistorySearchTerm)}` : '';
            nextBtn.href = `?med_page=${currentPage + 1}${searchParam}`;
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
            nextBtn.disabled = true;
            nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';
            nextBtn.setAttribute('aria-label', 'Next');
            nextBtn.innerHTML = `
                <span class="sr-only">Next</span>
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            `;
            paginationNav.appendChild(nextBtn);
        }
    }
    
    // Handle pagination clicks
    document.addEventListener('click', function(e) {
        if (e.target.closest('.flex.justify-between.items-center.mt-6.px-6.py-4.border-t.border-gray-200.bg-gray-50 nav[aria-label="Pagination"] a')) {
            const link = e.target.closest('a');
            const href = link.getAttribute('href');
            
            if (href.includes('med_page=')) {
                e.preventDefault();
                const pageMatch = href.match(/med_page=(\d+)/);
                if (pageMatch) {
                    const page = parseInt(pageMatch[1]);
                    const searchTerm = currentMedicalHistorySearchTerm || '';
                    searchMedicalHistory(searchTerm, page);
                }
            }
        }
    });
});

function viewPrescriptionDetails(prescription) {
    // Set modal title
    document.getElementById('modalTitle').textContent = 'Prescription Details';
    
    // Build modal body with improved styling
    let html = '<div class="space-y-6">';
    
    // Basic Information Card
    html += '<div class="bg-gray-50 rounded-lg p-4">';
    html += '<h4 class="text-lg font-semibold text-gray-800 mb-4">Prescription Information</h4>';
    html += '<div class="grid grid-cols-1 md:grid-cols-2 gap-4">';
    html += `<div>
        <label class="block text-sm font-medium text-gray-600 mb-1">Date Prescribed</label>
        <p class="text-sm text-gray-900 font-medium">${prescription.prescription_date || 'N/A'}</p>
    </div>`;
    html += `<div>
        <label class="block text-sm font-medium text-gray-600 mb-1">Medical Reason</label>
        <p class="text-sm text-gray-900">${prescription.reason || 'N/A'}</p>
    </div>`;
    html += '</div></div>';
    
    // Medicines Information
    html += '<div class="bg-white border border-gray-200 rounded-lg p-4">';
    html += '<h4 class="text-lg font-semibold text-gray-800 mb-4">Prescribed Medications</h4>';
    
    try {
        const medicines = typeof prescription.medicines === 'string' ? JSON.parse(prescription.medicines) : prescription.medicines;
        if (Array.isArray(medicines) && medicines.length > 0) {
            medicines.forEach((med, index) => {
                html += `<div class="border-l-4 border-blue-500 pl-4 mb-4 ${index > 0 ? 'border-t border-gray-200 pt-4' : ''}">`;
                html += `<div class="grid grid-cols-1 md:grid-cols-2 gap-4">`;
                html += `<div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Medicine Name</label>
                    <p class="text-sm text-gray-900 font-medium">${med.medicine || med.name || 'N/A'}</p>
                </div>`;
                html += `<div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Quantity</label>
                    <p class="text-sm text-gray-900">${med.quantity || 'N/A'}</p>
                </div>`;
                html += `<div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Dosage</label>
                    <p class="text-sm text-gray-900">${med.dosage || 'N/A'}</p>
                </div>`;
                html += `<div>
                    <label class="block text-sm font-medium text-gray-600 mb-1">Frequency</label>
                    <p class="text-sm text-gray-900">${med.frequency || 'N/A'}</p>
                </div>`;
                html += `</div>`;
                if (med.instructions) {
                    html += `<div class="mt-3">
                        <label class="block text-sm font-medium text-gray-600 mb-1">Special Instructions</label>
                        <p class="text-sm text-gray-900 bg-yellow-50 p-3 rounded-md border border-yellow-200">${med.instructions}</p>
                    </div>`;
                }
                html += `</div>`;
            });
        } else {
            html += `<div class="text-center py-8 text-gray-500">
                <i class="ri-medicine-bottle-line text-4xl mb-2 block"></i>
                <p>No medications prescribed</p>
            </div>`;
        }
    } catch (error) {
        html += `<div class="text-center py-8 text-red-500">
            <i class="ri-error-warning-line text-4xl mb-2 block"></i>
            <p>Error loading medication data</p>
        </div>`;
    }
    
    html += '</div></div>';
    
    document.getElementById('modalBody').innerHTML = html;
    document.getElementById('prescriptionModal').classList.remove('hidden');
}

function closePrescriptionModal() {
    document.getElementById('prescriptionModal').classList.add('hidden');
}

document.getElementById('closePrescriptionModalTop').addEventListener('click', closePrescriptionModal);
document.getElementById('closePrescriptionModalBottom').addEventListener('click', closePrescriptionModal);

document.getElementById('prescriptionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePrescriptionModal();
    }
});

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closePrescriptionModal();
    }
});
</script>

<?php
include '../includep/footer.php';
?>