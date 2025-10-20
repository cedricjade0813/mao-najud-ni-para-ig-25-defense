<?php
include '../includep/header.php';
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
// Function to convert 24-hour time range to 12-hour format
function convertTimeRange($timeRange) {
    if (empty($timeRange) || !strpos($timeRange, '-')) {
        return $timeRange; // Return as is if not a time range
    }
    
    $times = explode('-', $timeRange);
    if (count($times) !== 2) {
        return $timeRange; // Return as is if not a valid range
    }
    
    $startTime = trim($times[0]);
    $endTime = trim($times[1]);
    
    // Convert start time
    $startFormatted = date('g:i A', strtotime($startTime));
    
    // Convert end time
    $endFormatted = date('g:i A', strtotime($endTime));
    
    return $startFormatted . '-' . $endFormatted;
}

// Fetch appointments for this faculty
$faculty_id = $_SESSION['faculty_id'];
error_log("🔴 DEBUG: Faculty ID from session: " . $faculty_id);
$appointments = [];
$appointment_counts = ['total' => 0, 'pending' => 0, 'confirmed' => 0, 'cancelled' => 0];
$upcoming_appointments = [];

try {
    // Calculate date range for last 10 days
    $ten_days_ago = date('Y-m-d', strtotime('-10 days'));
    $today = date('Y-m-d');
    
    // Fetch appointments from last 10 days only (excluding cancelled and other inactive statuses)
    $stmt = $db->prepare('SELECT a.id, a.date, a.time, a.reason, a.status, ds.doctor_name 
                          FROM appointments a 
                          LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id 
                          WHERE a.faculty_id = ? AND a.date >= ? AND a.date <= ? AND a.status IN ("pending", "approved", "confirmed") 
                          ORDER BY a.date DESC, a.time DESC');
    $stmt->execute([$faculty_id, $ten_days_ago, $today]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Log the query results
    error_log("🔴 DEBUG: Faculty appointments query - Faculty ID: $faculty_id, Date range: $ten_days_ago to $today");
    error_log("🔴 DEBUG: Found " . count($appointments) . " appointments in last 10 days");
    if (count($appointments) > 0) {
        error_log("🔴 DEBUG: First appointment: " . json_encode($appointments[0]));
    }
    
    // Debug: Log the SQL query being executed
    error_log("🔴 DEBUG: SQL Query: SELECT a.id, a.date, a.time, a.reason, a.status, ds.doctor_name FROM appointments a LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id WHERE a.faculty_id = $faculty_id AND a.date >= '$ten_days_ago' AND a.date <= '$today' AND a.status IN ('pending', 'approved', 'confirmed') ORDER BY a.date DESC, a.time DESC");
    
    // Debug: Check all appointments for this faculty (not just last 10 days)
    $debug_stmt = $db->prepare('SELECT a.id, a.date, a.time, a.reason, a.status, a.faculty_id 
                                FROM appointments a 
                                WHERE a.faculty_id = ? 
                                ORDER BY a.date DESC, a.time DESC');
    $debug_stmt->execute([$faculty_id]);
    $all_appointments = $debug_stmt->fetchAll(PDO::FETCH_ASSOC);
    error_log("🔴 DEBUG: All appointments for faculty $faculty_id: " . count($all_appointments));
    if (count($all_appointments) > 0) {
        error_log("🔴 DEBUG: All appointments: " . json_encode($all_appointments));
    }
    
    // Fetch upcoming approved appointments (future dates with approved status)
    $stmt = $db->prepare('SELECT a.date, a.time, a.reason, a.status, ds.doctor_name, a.id 
                          FROM appointments a 
                          LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id 
                          WHERE a.faculty_id = ? AND a.status = "approved" AND a.date >= ? 
                          ORDER BY a.date ASC, a.time ASC LIMIT 5');
    $stmt->execute([$faculty_id, $today]);
    $upcoming_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get appointment counts for last 10 days (only active statuses)
    $stmt = $db->prepare('SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = "approved" OR status = "confirmed" THEN 1 ELSE 0 END) as confirmed,
        0 as cancelled
        FROM appointments WHERE faculty_id = ? AND date >= ? AND date <= ? AND status IN ("pending", "approved", "confirmed")');
    $stmt->execute([$faculty_id, $ten_days_ago, $today]);
    $counts = $stmt->fetch(PDO::FETCH_ASSOC);
    $appointment_counts = [
        'total' => (int)$counts['total'],
        'pending' => (int)$counts['pending'],
        'confirmed' => (int)$counts['confirmed'],
        'cancelled' => (int)$counts['cancelled']
    ];
} catch (PDOException $e) {
    $appointments = [];
    $upcoming_appointments = [];
}

// Pagination for appointments (last 10 days only)
$records_per_page = 5; // Show 5 appointments per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);
$offset = ($page - 1) * $records_per_page;
$total_records = count($appointments);
$total_pages = ceil($total_records / $records_per_page);
$appointments_paginated = array_slice($appointments, $offset, $records_per_page);

// Debug: Log pagination info
error_log("🔴 DEBUG: Pagination - Total records: $total_records, Page: $page, Records per page: $records_per_page, Total pages: $total_pages");
error_log("🔴 DEBUG: Paginated appointments count: " . count($appointments_paginated));
?>
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px] scrollbar-hide">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex justify-between items-start">
                        <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Faculty Health Dashboard</h1>
                <p class="text-gray-600">Welcome back! Here's an overview of your health information.</p>
                            </div>
                        </div>
                    </div>
    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                        <div>
                    <div class="text-sm text-gray-600 mb-1">Total Appointments</div>
                    <div class="text-3xl font-bold text-blue-600"><?php echo $appointment_counts['total']; ?></div>
                </div>
                <div class="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center">
                    <i class="ri-calendar-line text-blue-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                        <div>
                    <div class="text-sm text-gray-600 mb-1">Pending</div>
                    <div class="text-3xl font-bold text-green-600"><?php echo $appointment_counts['pending']; ?></div>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-full flex items-center justify-center">
                    <i class="ri-time-line text-green-600 text-xl"></i>
                            </div>
                        </div>
                    </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                        <div>
                    <div class="text-sm text-gray-600 mb-1">Approved</div>
                    <div class="text-3xl font-bold text-purple-600"><?php echo $appointment_counts['confirmed']; ?></div>
                            </div>
                <div class="w-12 h-12 bg-purple-50 rounded-full flex items-center justify-center">
                    <i class="ri-check-line text-purple-600 text-xl"></i>
                        </div>
                    </div>
                </div>
            </div>
    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="lg:col-span-2 space-y-8">
            <!-- Recent Appointments -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900">Recent Appointments</h3>
                    <div class="relative">
                        <input id="appointmentSearch" type="text" placeholder="Search appointments..." 
                               class="w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white h-10">
                        <i class="ri-search-line absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
        </div>
            <div class="overflow-x-auto scrollbar-hide">
                    <table class="w-full divide-y divide-gray-200 table-fixed">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="w-1/4 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                                <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Doctor</th>
                                <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="w-1/3 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason</th>
                                <th class="w-1/6 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (!empty($appointments_paginated)) {
                            foreach ($appointments_paginated as $appt) { ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="w-1/4 px-6 py-4 text-sm text-gray-900">
                                            <div class="truncate" title="<?php echo date('M j, Y', strtotime($appt['date'])); ?> at <?php echo convertTimeRange($appt['time']); ?>">
                                                <?php echo date('M j, Y', strtotime($appt['date'])); ?><br>
                                                <span class="text-gray-500"><?php echo convertTimeRange($appt['time']); ?></span>
                                            </div>
                                        </td>
                                        <td class="w-1/6 px-6 py-4 text-sm text-gray-900">
                                            <?php 
                                            $doctorName = $appt['doctor_name'] ?? 'Dr. Medical Officer';
                                            if ($doctorName !== 'Dr. Medical Officer' && !empty($doctorName)) {
                                                if (!str_starts_with($doctorName, 'Dr.')) {
                                                    $doctorName = 'Dr. ' . ucfirst($doctorName);
                                                }
                                            }
                                            ?>
                                            <div class="truncate" title="<?php echo htmlspecialchars($doctorName); ?>"><?php echo htmlspecialchars($doctorName); ?></div>
                                        </td>
                                        <td class="w-1/6 px-6 py-4">
                                        <?php if ($appt['status'] == 'approved') { ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">approved</span>
                                        <?php } elseif ($appt['status'] == 'pending') { ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">pending</span>
                                        <?php } elseif ($appt['status'] == 'cancelled') { ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">cancelled</span>
                                        <?php } else { ?>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800"><?php echo htmlspecialchars(ucfirst($appt['status'])); ?></span>
                                        <?php } ?>
                                    </td>
                                        <td class="w-1/3 px-6 py-4 text-sm text-gray-900">
                                            <div class="truncate" title="<?php echo htmlspecialchars($appt['reason']); ?>"><?php echo htmlspecialchars($appt['reason']); ?></div>
                                    </td>
                                        <td class="w-1/6 px-6 py-4 text-sm text-gray-900">
                                            <?php if ($appt['status'] == 'pending' || $appt['status'] == 'approved'): ?>
                                                <button class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 cancelBtn" 
                                                        data-appointment-id="<?php echo $appt['id']; ?>"
                                                        data-date="<?php echo $appt['date']; ?>"
                                                        data-time="<?php echo $appt['time']; ?>"
                                                        data-reason="<?php echo htmlspecialchars($appt['reason']); ?>">
                                                    Cancel
                                                </button>
                                            <?php else: ?>
                                                <span class="text-gray-400 text-xs">No action</span>
                                            <?php endif; ?>
                                    </td>
                                </tr>
                            <?php }
                        } else { ?>
                            <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">No appointments found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
                
                <!-- Pagination for Recent Appointments -->
            <?php if ($total_records > 0): ?>
                <div class="flex justify-between items-center mt-6 px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="text-sm text-gray-600">
                    <?php 
                    $start = $offset + 1;
                    $end = min($offset + $records_per_page, $total_records);
                    ?>
                        Showing <?php echo $start; ?> to <?php echo $end; ?> of <?php echo $total_records; ?> entries (Last 10 days)
                </div>
                <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
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
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    if ($start_page > 1): ?>
                        <a href="?page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">1</a>
                        <?php if ($start_page > 2): ?>
                            <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                        <?php if ($i == $page): ?>
                            <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300" aria-current="page"><?php echo $i; ?></button>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    <?php if ($end_page < $total_pages): ?>
                        <?php if ($end_page < $total_pages - 1): ?>
                            <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                        <?php endif; ?>
                        <a href="?page=<?php echo $total_pages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $total_pages; ?></a>
                    <?php endif; ?>
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
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

            <!-- Quick Actions -->
            
        </div>

        <!-- Right Column -->
        <div class="space-y-8">
            <!-- Upcoming Appointments -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                        <i class="ri-calendar-line text-blue-600 mr-2"></i>
                        Upcoming Appointments
                    </h3>
                    <p class="text-sm text-gray-500 mt-1">Your approved appointments</p>
                </div>
                <div class="p-6 space-y-4 scrollbar-hide">
                    <?php if (!empty($upcoming_appointments)): ?>
                        <?php foreach ($upcoming_appointments as $appointment): ?>
                            <div class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-medium text-gray-900 mb-1"><?php echo htmlspecialchars($appointment['reason']); ?></h4>
                                        <?php 
                                        $upcomingDoctorName = $appointment['doctor_name'] ?? 'Dr. Medical Officer';
                                        if ($upcomingDoctorName !== 'Dr. Medical Officer' && !empty($upcomingDoctorName)) {
                                            if (!str_starts_with($upcomingDoctorName, 'Dr.')) {
                                                $upcomingDoctorName = 'Dr. ' . ucfirst($upcomingDoctorName);
                                            }
                                        }
                                        ?>
                                        <p class="text-sm text-gray-600 mb-1"><?php echo htmlspecialchars($upcomingDoctorName); ?></p>
                                        <p class="text-sm text-gray-500"><?php echo convertTimeRange($appointment['time']); ?></p>
                            </div>
                                    <div class="text-right">
                                        <span class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($appointment['date'])); ?></span>
                        </div>
                    </div>
                                <div class="flex justify-end">
                                    <button class="px-4 py-2 text-sm bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors cancelBtn" 
                                            data-appointment-id="<?php echo $appointment['id']; ?>"
                                            data-date="<?php echo $appointment['date']; ?>"
                                            data-time="<?php echo $appointment['time']; ?>"
                                            data-reason="<?php echo htmlspecialchars($appointment['reason']); ?>">
                                        Cancel Appointment
                                    </button>
                    </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="ri-calendar-line text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-sm">No upcoming appointments</p>
                            <p class="text-gray-400 text-xs">Your approved appointments will appear here</p>
                        </div>
                    <?php endif; ?>
                    
                    <button class="w-full flex items-center justify-center gap-2 px-4 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium" 
                            onclick="window.location.href='appointments.php'">
                        <i class="ri-calendar-line"></i>
                        Book New Appointment
                    </button>
                </div>
            </div>

            <!-- Recent Health Records -->
            
        </div>
        </div>
</main>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Appointment search functionality - following inventory.php pattern
        const appointmentSearchInput = document.getElementById('appointmentSearch');
        let currentAppointmentSearchTerm = null;
        let currentAppointmentPage = 1;
        
        if (appointmentSearchInput) {
            appointmentSearchInput.addEventListener('input', function() {
                const searchTerm = this.value.trim();
                currentAppointmentPage = 1; // Reset to first page when searching
                searchAppointments(searchTerm, 1);
            });
        }
        
        function searchAppointments(searchTerm, page = 1) {
            // If search is cleared, show all data without page reload
            if (!searchTerm || searchTerm.trim() === '') {
                currentAppointmentSearchTerm = null;
                // Make AJAX request to get all data without search filter
                fetch('search_appointments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `search=&page=${page}&faculty_id=<?php echo $faculty_id; ?>`
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        updateAppointmentTable(data.appointments, data.pagination);
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
            fetch('search_appointments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `search=${encodeURIComponent(searchTerm)}&page=${page}&faculty_id=<?php echo $faculty_id; ?>`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    currentAppointmentSearchTerm = searchTerm;
                    updateAppointmentTable(data.appointments, data.pagination);
                } else {
                    console.error('Search error:', data.message);
                }
            })
            .catch(error => {
                console.error('Search request failed:', error);
            });
        }
        
        function updateAppointmentTable(appointments, pagination = null) {
            const appointmentTableBody = document.querySelector('tbody');
            if (!appointmentTableBody) return;
            
            if (appointments.length === 0) {
                appointmentTableBody.innerHTML = '<tr><td colspan="4" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-calendar-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No appointments found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';
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
                        if (currentAppointmentSearchTerm) {
                            infoText.textContent = `Showing ${startRecord} to ${endRecord} of ${pagination.total_records} entries (Last 10 days)`;
                        } else {
                            infoText.textContent = `Showing ${startRecord} to ${endRecord} of ${pagination.total_records} entries (Last 10 days)`;
                        }
                    }
                } else {
                    // If no pagination data provided, hide the pagination info
                    const infoText = paginationContainer.querySelector('.text-sm.text-gray-600');
                    if (infoText) {
                        infoText.textContent = '';
                    }
                }
                
                // Force update the pagination info display
                console.log('Updating pagination info:', {
                    currentPage: pagination?.current_page,
                    totalRecords: pagination?.total_records,
                    searchTerm: currentAppointmentSearchTerm
                });
            }
            
            // Build table rows
            let html = '';
            appointments.forEach(appt => {
                // Determine status styling based on the actual status value
                let statusHtml = '';
                if (appt.status === 'approved') {
                    statusHtml = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">approved</span>';
                } else if (appt.status === 'pending') {
                    statusHtml = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">pending</span>';
                } else if (appt.status === 'cancelled') {
                    statusHtml = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">cancelled</span>';
                } else {
                    statusHtml = `<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">${appt.status.charAt(0).toUpperCase() + appt.status.slice(1)}</span>`;
                }
                
                // Determine if cancel button should be shown
                let actionHtml = '';
                if (appt.status === 'pending' || appt.status === 'approved') {
                    actionHtml = `<button class="px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 cancelBtn" 
                                        data-appointment-id="${appt.id || ''}"
                                        data-date="${appt.date}"
                                        data-time="${appt.time}"
                                        data-reason="${appt.reason.replace(/'/g, "\\'")}">
                                    Cancel
                                </button>`;
                } else {
                    actionHtml = '<span class="text-gray-400 text-xs">No action</span>';
                }
                
                html += `
                    <tr class="hover:bg-gray-50">
                        <td class="w-1/4 px-6 py-4 text-sm text-gray-900">
                            <div class="truncate" title="${appt.formatted_date} at ${appt.time}">
                                ${appt.formatted_date}<br>
                                <span class="text-gray-500">${appt.time}</span>
                            </div>
                        </td>
                        <td class="w-1/6 px-6 py-4 text-sm text-gray-900">
                            <div class="truncate" title="${appt.doctor_name || 'Dr. Medical Officer'}">${appt.doctor_name || 'Dr. Medical Officer'}</div>
                        </td>
                        <td class="w-1/6 px-6 py-4">
                            ${statusHtml}
                        </td>
                        <td class="w-1/3 px-6 py-4 text-sm text-gray-900">
                            <div class="truncate" title="${appt.reason}">${appt.reason}</div>
                        </td>
                        <td class="w-1/6 px-6 py-4 text-sm text-gray-900">
                            ${actionHtml}
                        </td>
                    </tr>
                `;
            });
            
            appointmentTableBody.innerHTML = html;
            
            // Reattach event listeners to new cancel buttons
            attachCancelButtonListeners();
            
            // Update pagination numbers if provided
            if (pagination) {
                updateAppointmentPaginationNumbers(pagination);
            }
        }
        
        function updateAppointmentPaginationNumbers(pagination) {
            const paginationNav = document.querySelector('.flex.justify-between.items-center.mt-6.px-6.py-4.border-t.border-gray-200.bg-gray-50 nav[aria-label="Pagination"]');
            if (!paginationNav) return;
            
            const currentPage = pagination.current_page;
            const totalPages = pagination.total_pages;
            
            // Clear existing pagination
            paginationNav.innerHTML = '';
            
            // Previous button - always show
            if (currentPage > 1) {
                const prevBtn = document.createElement('a');
                const searchParam = currentAppointmentSearchTerm ? `&search=${encodeURIComponent(currentAppointmentSearchTerm)}` : '';
                prevBtn.href = `?page=${currentPage - 1}${searchParam}`;
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
                const searchParam = currentAppointmentSearchTerm ? `&search=${encodeURIComponent(currentAppointmentSearchTerm)}` : '';
                firstPage.href = `?page=1${searchParam}`;
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
                    const searchParam = currentAppointmentSearchTerm ? `&search=${encodeURIComponent(currentAppointmentSearchTerm)}` : '';
                    pageLink.href = `?page=${i}${searchParam}`;
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
                const searchParam = currentAppointmentSearchTerm ? `&search=${encodeURIComponent(currentAppointmentSearchTerm)}` : '';
                lastPage.href = `?page=${totalPages}${searchParam}`;
                lastPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100';
                lastPage.textContent = totalPages;
                paginationNav.appendChild(lastPage);
            }
            
            // Next button - always show
            if (currentPage < totalPages) {
                const nextBtn = document.createElement('a');
                const searchParam = currentAppointmentSearchTerm ? `&search=${encodeURIComponent(currentAppointmentSearchTerm)}` : '';
                nextBtn.href = `?page=${currentPage + 1}${searchParam}`;
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
                
                if (href.includes('page=')) {
                    e.preventDefault();
                    const pageMatch = href.match(/page=(\d+)/);
                    if (pageMatch) {
                        const page = parseInt(pageMatch[1]);
                        const searchTerm = currentAppointmentSearchTerm || '';
                        searchAppointments(searchTerm, page);
                    }
                }
            }
        });
        
        // Store original pagination text on page load
        const paginationInfo = document.querySelector('.flex.justify-between.items-center.mt-6.px-6.py-4.border-t.border-gray-200.bg-gray-50 .text-sm.text-gray-600');
        if (paginationInfo) {
            paginationInfo.setAttribute('data-original-text', paginationInfo.textContent);
            console.log('Original pagination text stored:', paginationInfo.textContent);
        }

        // Function to attach cancel button event listeners
        function attachCancelButtonListeners() {
            document.querySelectorAll('.cancelBtn').forEach(function(btn) {
                // Remove existing listeners to prevent duplicates
                btn.removeEventListener('click', handleCancelClick);
                // Add new listener
                btn.addEventListener('click', handleCancelClick);
            });
        }

        // Function to handle cancel button clicks
        function handleCancelClick(e) {
                e.preventDefault();
            
            const appointmentId = this.getAttribute('data-appointment-id');
            const date = this.getAttribute('data-date');
            const time = this.getAttribute('data-time');
            const reason = this.getAttribute('data-reason');
            
            // Show custom confirmation modal
            showCancelModal(appointmentId, date, time, reason, this);
        }

        // Initial attachment of cancel button listeners
        attachCancelButtonListeners();

        // Function to show custom cancel confirmation modal
        function showCancelModal(appointmentId, date, time, reason, originalBtn) {
            // Create modal HTML
            const modalHTML = `
                <div id="cancelModal" class="fixed inset-0 bg-black bg-opacity-50 overflow-y-auto h-full w-full z-50 flex items-center justify-center">
                    <div class="relative bg-white rounded-xl shadow-2xl max-w-md w-full mx-4 transform transition-all">
                        <!-- Modal Header -->
                        <div class="flex items-center justify-between p-6 border-b border-gray-200">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                                    <i class="ri-alert-line text-red-600 text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-semibold text-gray-900">Cancel Appointment</h3>
                                    <p class="text-sm text-gray-500">This action cannot be undone</p>
                                </div>
                            </div>
                            <button onclick="closeCancelModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                                <i class="ri-close-line text-xl"></i>
                            </button>
                        </div>
                        
                        <!-- Modal Body -->
                        <div class="p-6">
                            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                                <h4 class="font-medium text-gray-900 mb-2">Appointment Details</h4>
                                <div class="space-y-1 text-sm text-gray-600">
                                    <p><span class="font-medium">Reason:</span> ${reason}</p>
                                    <p><span class="font-medium">Date:</span> ${new Date(date).toLocaleDateString()}</p>
                                    <p><span class="font-medium">Time:</span> ${convertTimeRangeJS(time)}</p>
                                </div>
                            </div>
                            
                            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                                <div class="flex items-start">
                                    <i class="ri-information-line text-red-500 text-lg mr-2 mt-0.5"></i>
                                    <div>
                                        <p class="text-sm text-red-800 font-medium">Are you sure you want to cancel this appointment?</p>
                                        <p class="text-xs text-red-600 mt-1">This action cannot be undone and you will need to book a new appointment.</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Modal Footer -->
                        <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-gray-50 rounded-b-xl">
                            <button onclick="closeCancelModal()" 
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-colors">
                                Keep Appointment
                            </button>
                            <button onclick="confirmCancel('${appointmentId}', '${date}', '${time}', '${reason.replace(/'/g, "\\'")}')" 
                                    class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 transition-colors">
                                Cancel Appointment
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            // Add modal to page
            document.body.insertAdjacentHTML('beforeend', modalHTML);
            
            // Store reference to original button
            window.currentCancelBtn = originalBtn;
        }

        // Function to close cancel modal
        window.closeCancelModal = function() {
            const modal = document.getElementById('cancelModal');
            if (modal) {
                modal.remove();
            }
        }

        // Function to confirm cancellation
        window.confirmCancel = function(appointmentId, date, time, reason) {
            const btn = window.currentCancelBtn;
            
            // Show loading state
            const originalText = btn.textContent;
            btn.textContent = 'Cancelling...';
            btn.disabled = true;
            
            // Close modal
            closeCancelModal();
            
                    // Send AJAX request to cancel
                    fetch('profile_cancel_appointment.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                        appointment_id: appointmentId,
                        date: date,
                        time: time,
                        reason: reason
                            })
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                        // If it's in the table, remove the row completely
                        const row = btn.closest('tr');
                        if (row) {
                            row.remove();
                        }
                        
                        // If it's in upcoming appointments, remove the card completely
                        const card = btn.closest('.border.border-gray-200.rounded-lg.p-4');
                        if (card) {
                            card.remove();
                        }
                        
                        // Show success message with custom modal design
                        showSimpleSuccessMessage('Appointment cancelled successfully!');
                        
                        // Refresh the page after a short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                            } else {
                        // Reset button state
                        btn.textContent = originalText;
                        btn.disabled = false;
                        showNotification('Failed to cancel appointment: ' + (data.message || 'Unknown error'), 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    // Reset button state
                    btn.textContent = originalText;
                    btn.disabled = false;
                    showNotification('Failed to cancel appointment. Please try again.', 'error');
                });
        }

        // Function to convert 24-hour time range to 12-hour format in JavaScript
        function convertTimeRangeJS(timeRange) {
            if (!timeRange || !timeRange.includes('-')) {
                return timeRange; // Return as is if not a time range
            }
            
            const times = timeRange.split('-');
            if (times.length !== 2) {
                return timeRange; // Return as is if not a valid range
            }
            
            const startTime = times[0].trim();
            const endTime = times[1].trim();
            
            // Convert start time
            const startDate = new Date('2000-01-01 ' + startTime);
            const startFormatted = startDate.toLocaleTimeString([], {hour: 'numeric', minute:'2-digit', hour12: true});
            
            // Convert end time
            const endDate = new Date('2000-01-01 ' + endTime);
            const endFormatted = endDate.toLocaleTimeString([], {hour: 'numeric', minute:'2-digit', hour12: true});
            
            return startFormatted + '-' + endFormatted;
        }

        // Function to show notifications
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 z-50 px-6 py-3 rounded-lg shadow-lg text-white ${
                type === 'success' ? 'bg-green-500' : 
                type === 'error' ? 'bg-red-500' : 
                'bg-blue-500'
            }`;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            // Auto remove after 3 seconds
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Simple success message function (matching inbox design)
        function showSimpleSuccessMessage(message) {
            // Remove any existing notification
            const existingToast = document.getElementById('appointmentToast');
            if (existingToast) {
                existingToast.remove();
            }

            const notification = document.createElement('div');
            notification.id = 'appointmentToast';
            notification.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
            pointer-events: none;
            background: rgba(255,255,255,0.18);
        `;

            notification.innerHTML = `
            <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                <span>${message}</span>
            </div>
        `;

            document.body.appendChild(notification);

            // Auto-dismiss after 1.2 seconds with fade out
            setTimeout(() => {
                notification.style.transition = 'opacity 0.3s';
                notification.style.opacity = '0';
                setTimeout(() => {
                    if (notification && notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 1200);
        }

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target && e.target.id === 'cancelModal') {
                closeCancelModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCancelModal();
            }
        });
    });
</script>
<?php include '../includep/footer.php'; ?>