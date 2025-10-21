<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include '../includep/header.php';

// Get patient ID from session
$patient_id = $_SESSION['student_row_id'] ?? null;

if (!$patient_id) {
    header('Location: ../index.php');
    exit;
}

// Debug: Log the patient ID for verification
error_log("Logged in Patient ID: " . $patient_id);

// Database connection
$conn = new mysqli('localhost', 'root', '', 'clinic_management_system');
if ($conn->connect_errno) {
    die('Database connection failed: ' . $conn->connect_error);
}

// Handle appointment booking form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_date'])) {
    $appointment_date = $_POST['appointment_date'] ?? '';
    $appointment_time = $_POST['appointment_time'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $email = $_POST['email'] ?? '';
    $parent_email = $_POST['parent_email'] ?? '';

    // Validate required fields
    if (empty($appointment_date) || empty($appointment_time) || empty($reason) || empty($email)) {
        $booking_error = "All fields are required.";
    } else {
        // Check if appointment already exists for this time slot
        $check_stmt = $conn->prepare('SELECT id FROM appointments WHERE date = ? AND time = ? AND status != "declined"');
        $check_stmt->bind_param('ss', $appointment_date, $appointment_time);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();

        if ($existing) {
            $booking_error = "This time slot is already booked. Please choose another time.";
        } else {
            // Check if this patient already has an appointment for this schedule
            $patient_check_stmt = $conn->prepare('SELECT id FROM appointments WHERE student_id = ? AND date = ? AND status != "declined"');
            $patient_check_stmt->bind_param('is', $patient_id, $appointment_date);
            $patient_check_stmt->execute();
            $patient_existing = $patient_check_stmt->get_result()->fetch_assoc();

            if ($patient_existing) {
                $booking_restriction = "You already have an appointment for this date. Only one appointment per schedule is allowed.";
            } else {
            // Set timezone to Philippines for consistent date handling
            date_default_timezone_set('Asia/Manila');
            
            // Get doctor_id for this date and time
            // First try exact time match
            $doctor_stmt = $conn->prepare('SELECT id, doctor_name FROM doctor_schedules WHERE schedule_date = ? AND schedule_time = ? LIMIT 1');
            $doctor_stmt->bind_param('ss', $appointment_date, $appointment_time);
            $doctor_stmt->execute();
            $doctor_result = $doctor_stmt->get_result()->fetch_assoc();
            
            // If no exact match, try to find a schedule that contains this time slot
            if (!$doctor_result) {
                $doctor_stmt = $conn->prepare('SELECT id, doctor_name FROM doctor_schedules WHERE schedule_date = ? LIMIT 1');
                $doctor_stmt->bind_param('s', $appointment_date);
                $doctor_stmt->execute();
                $doctor_result = $doctor_stmt->get_result()->fetch_assoc();
            }
            
            $doctor_id = $doctor_result ? $doctor_result['id'] : null;
            

            // Insert new appointment
            $insert_stmt = $conn->prepare('INSERT INTO appointments (student_id, date, time, reason, status, email, parent_email, doctor_id) VALUES (?, ?, ?, ?, "pending", ?, ?, ?)');
            $insert_stmt->bind_param('isssssi', $patient_id, $appointment_date, $appointment_time, $reason, $email, $parent_email, $doctor_id);

            if ($insert_stmt->execute()) {
                $booking_success = "Appointment booked successfully! You will receive a confirmation email.";
                error_log("Appointment booked successfully for patient ID: $patient_id");
            } else {
                $booking_error = "Failed to book appointment. Please try again.";
                error_log("Failed to book appointment: " . $conn->error);
            }
            }
        }
    }
}

// Fetch doctor schedules (only future dates)
$doctor_schedules = [];
try {
    $schedule_stmt = $conn->prepare('SELECT doctor_name, profession, schedule_date, schedule_time FROM doctor_schedules WHERE schedule_date >= CURDATE() ORDER BY schedule_date ASC, schedule_time ASC');
    $schedule_stmt->execute();
    $result = $schedule_stmt->get_result();
    $doctor_schedules = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    // Ignore errors for schedules
}

// Fetch all booked appointments from ALL patients (to filter out unavailable slots in calendar)
// This is needed for the calendar to show which time slots are taken by ANY patient
$booked_appointments = [];
try {
    $booked_stmt = $conn->prepare('SELECT date, time FROM appointments WHERE status != "declined" ORDER BY date ASC, time ASC');
    $booked_stmt->execute();
    $result = $booked_stmt->get_result();
    $booked_appointments = $result->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    // Ignore errors for booked appointments
}

// Function to convert any time format to 12-hour format
function convertTimeTo12Hour($timeString)
{
    if (empty($timeString)) {
        return $timeString;
    }

    // If it's a time range (contains dash)
    if (strpos($timeString, '-') !== false) {
        $times = explode('-', $timeString);
        if (count($times) === 2) {
            $startTime = trim($times[0]);
            $endTime = trim($times[1]);

            // Convert start time
            $startFormatted = date('g:i A', strtotime($startTime));

            // Convert end time
            $endFormatted = date('g:i A', strtotime($endTime));

            return $startFormatted . '-' . $endFormatted;
        }
    }

    // For single times, convert to 12-hour format
    return date('g:i A', strtotime($timeString));
}

// Function to convert 24-hour time range to 12-hour format (legacy)
function convertTimeRange($timeRange)
{
    return convertTimeTo12Hour($timeRange);
}

// Get appointment counts for each status
$counts = [
    'pending' => 0,
    'approved' => 0,
    'declined' => 0,
    'rescheduled' => 0
];

try {
    // Set timezone to Philippines and get current date
    date_default_timezone_set('Asia/Manila');
    $currentDate = date('Y-m-d');
    
    // Get counts for each status (only pending filtered by current date, rescheduled filtered by time)
    $count_queries = [
        'pending' => "SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status = 'pending' AND date = ?",
        'approved' => "SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status IN ('approved', 'confirmed')",
        'declined' => "SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status = 'declined'",
        'rescheduled' => "SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status = 'rescheduled' AND (date > ? OR (date = ? AND (
            (time NOT LIKE '%-%' AND ADDTIME(time, '01:00:00') >= ?) OR 
            (time LIKE '%-%' AND (
                ADDTIME(SUBSTRING_INDEX(time, '-', 1), '01:00:00') >= ? OR 
                (SUBSTRING_INDEX(time, '-', 1) <= ? AND ADDTIME(SUBSTRING_INDEX(time, '-', -1), '01:00:00') >= ?)
            ))
        )))"
    ];

    foreach ($count_queries as $status => $query) {
        $stmt = $conn->prepare($query);
        if ($status === 'pending') {
            $stmt->bind_param('is', $patient_id, $currentDate);
        } elseif ($status === 'rescheduled') {
            $currentTime = date('H:i:s');
            $stmt->bind_param('issssss', $patient_id, $currentDate, $currentDate, $currentTime, $currentTime, $currentTime, $currentTime);
        } else {
            $stmt->bind_param('i', $patient_id);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $counts[$status] = $result->fetch_row()[0];
        $stmt->close();
    }
} catch (Exception $e) {
    // Handle error silently
}

$conn->close();
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
    html,
    body {
        scrollbar-width: none !important;
        -ms-overflow-style: none !important;
        overflow: -moz-scrollbars-none !important;
    }

    html::-webkit-scrollbar,
    body::-webkit-scrollbar {
        width: 0 !important;
        height: 0 !important;
        display: none !important;
    }

    html::-webkit-scrollbar-track,
    body::-webkit-scrollbar-track {
        display: none !important;
    }

    html::-webkit-scrollbar-thumb,
    body::-webkit-scrollbar-thumb {
        display: none !important;
    }

    html::-webkit-scrollbar-corner,
    body::-webkit-scrollbar-corner {
        display: none !important;
    }

    /* Ensure consistent table layout across all pages */
    .appointment-table {
        table-layout: fixed !important;
        width: 100% !important;
    }

    .appointment-table th,
    .appointment-table td {
        width: 20% !important;
        /* 1/5 = 20% */
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    /* Ensure consistent column widths for all appointment tables */
    #pendingAppointmentsTable,
    #approvedAppointmentsTable,
    #declinedAppointmentsTable,
    #rescheduledAppointmentsTable {
        table-layout: fixed !important;
    }

    #pendingAppointmentsTable th,
    #pendingAppointmentsTable td,
    #approvedAppointmentsTable th,
    #approvedAppointmentsTable td,
    #declinedAppointmentsTable th,
    #declinedAppointmentsTable td,
    #rescheduledAppointmentsTable th,
    #rescheduledAppointmentsTable td {
        width: 20% !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
    }

    /* Mobile responsive styles for appointments */
    @media (max-width: 400px) {
        /* Appointments title and subtitle - matching profile page styling */
        .appointments-title {
            font-size: 18px !important;
            line-height: 1.2 !important;
        }
        
        .appointments-subtitle {
            font-size: 10px !important;
            line-height: 1.2 !important;
        }
    }
</style>

<!-- Dashboard Content -->
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px] scrollbar-hide">
    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex items-center">
            <!-- Mobile menu button -->
            <button id="mobileMenuBtn" class="md:hidden mr-4 text-gray-600 hover:text-gray-900 rounded-md min-w-[44px] min-h-[44px] flex items-center justify-center cursor-pointer" onclick="toggleMobileMenu()">
                <i class="ri-menu-line text-xl pointer-events-none"></i>
            </button>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2 appointments-title">Appointments</h1>
                <p class="text-gray-600 appointments-subtitle">View and manage your appointments</p>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
        <nav class="flex space-x-8 px-6 py-3" role="tablist">
            <button type="button" class="patient-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-tab="calendar-view">
                Calendar View
            </button>
            <button type="button" class="patient-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-tab="appointments">
                Appointments
            </button>
        </nav>
    </div>

    <!-- Calendar View Section -->
    <div id="calendarViewSection" class="hidden">
        <!-- Doctor's Availability Calendar -->
         
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">

            <div class="flex items-center justify-between mb-6">

                <div class="flex items-center space-x-2">

                    <i class="ri-calendar-line text-xl text-blue-600"></i>

                    <h3 class="text-xl font-semibold text-gray-800">Appointment Calendar</h3>

                </div>

                <div class="flex items-center space-x-4">

                    <button id="prevMonthBtn" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">

                        <i class="ri-arrow-left-s-line text-lg"></i>

                    </button>

                    <span id="calendarMonth" class="font-semibold text-lg text-gray-800 min-w-[120px] text-center">May 2025</span>

                    <button id="nextMonthBtn" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">

                        <i class="ri-arrow-right-s-line text-lg"></i>

                    </button>

                </div>

            </div>
            <div id="calendarGrid" class="grid grid-cols-7 gap-2 text-center text-sm">
                <!-- Calendar will be rendered here by JS -->
            </div>
        </div>

        <!-- Book Appointment Modal -->
        <div id="bookApptModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40 hidden">
            <div class="bg-white rounded shadow-lg p-8 max-w-xl w-full relative">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold">Book an Appointment</h3>
                    <button id="closeModalBtn" class="text-gray-400 hover:text-gray-700 text-2xl">&times;</button>
                </div>
                <form id="bookApptForm" method="POST" autocomplete="off">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" id="modalDate" name="appointment_date" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required />
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <select id="modalTime" name="appointment_time" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" required>
                            <option value="" selected disabled>Select time</option>
                        </select>
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                        <input type="text" name="reason" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter reason" required />
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Your Email Address</label>
                        <input type="email" name="email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter your email address" required />
                    </div>
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parent's Email Address</label>
                        <input type="email" name="parent_email" class="w-full border border-gray-300 rounded px-3 py-2 text-sm" placeholder="Enter your parent's email address" required />
                    </div>
                    <button type="submit" id="bookApptBtn" class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition-colors">Book Appointment</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($booking_success)): ?>
        <div id="appointmentToast" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; display: flex; align-items: center; justify-content: center; pointer-events: none; background: rgba(255,255,255,0.18);">
            <div style="background:rgba(255,255,255,0.7); color:#2563eb; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                <span style="font-size:2rem;line-height:1;color:#2563eb;">&#10003;</span>
                <span><?php echo htmlspecialchars($booking_success); ?></span>
            </div>
        </div>
        <script>
            // Auto-dismiss after 1.2 seconds with fade out
            setTimeout(() => {
                const notification = document.getElementById('appointmentToast');
                if (notification) {
                    notification.style.transition = 'opacity 0.3s';
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification && notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 1200);
        </script>
    <?php endif; ?>

    <?php if (isset($booking_restriction)): ?>
        <div id="appointmentRestrictionToast" style="position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; z-index: 9999; display: flex; align-items: center; justify-content: center; pointer-events: none; background: rgba(255,255,255,0.18);">
            <div style="background:rgba(255,255,255,0.7); color:#dc2626; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(220,38,38,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #dc2626; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                <span style="font-size:2rem;line-height:1;color:#dc2626;">🚫</span>
                <span><?php echo htmlspecialchars($booking_restriction); ?></span>
            </div>
        </div>
        <script>
            // Auto-dismiss after 3 seconds with fade out
            setTimeout(() => {
                const notification = document.getElementById('appointmentRestrictionToast');
                if (notification) {
                    notification.style.transition = 'opacity 0.3s';
                    notification.style.opacity = '0';
                    setTimeout(() => {
                        if (notification && notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 3000);
        </script>
    <?php endif; ?>

    <?php if (isset($booking_error)): ?>
        <div class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded z-50">
            <div class="flex items-center">
                <i class="ri-error-warning-fill mr-2"></i>
                <?php echo htmlspecialchars($booking_error); ?>
            </div>
        </div>
    <?php endif; ?>

    <!-- Appointments Section -->
    <div id="appointmentsSection" class="hidden">
        <!-- Pending Appointments Section -->
        <div id="pendingSection" class="bg-white rounded-lg shadow-sm border border-gray-200 appt-tab-section">
            <!-- Header Section -->
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Pending Appointments</h3>
                        <p class="text-gray-600 text-xs mt-1">Appointments awaiting approval</p>
                    </div>
                    <!-- Search Bar -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-search-line text-gray-400"></i>
                        </div>
                        <input type="text" id="pendingSearchInput" class="block w-80 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Search appointments...">
                    </div>
                </div>
            </div>

            <!-- Tab Navigation for Pending -->
            <div class="px-6 py-3 border-b border-gray-200">
                <nav class="flex space-x-8">
                    <button type="button" class="appt-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="pending">
                        Pending (<span id="pendingCount"><?php echo $counts['pending']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="approved">
                        Approved (<span id="approvedCount"><?php echo $counts['approved']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="declined">
                        Declined (<span id="declinedCount"><?php echo $counts['declined']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="rescheduled">
                        Rescheduled (<span id="rescheduledCount"><?php echo $counts['rescheduled']; ?></span>)
                    </button>
                </nav>
            </div>

            <!-- Pending Appointments Table -->
            <div class="overflow-x-auto">
                <table id="pendingAppointmentsTable" class="min-w-full divide-y divide-gray-200 appointment-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DATE</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">TIME</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DOCTOR</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">REASON</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="pendingAppointmentsBody">
                        <!-- Pending appointments will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination for Pending Appointments -->
            <div id="pendingPagination" class="flex justify-between items-center mt-6 px-6 py-4 border-t border-gray-200 bg-gray-50" style="display: none;">
                <div class="text-sm text-gray-600" id="pendingPaginationInfo">
                    <!-- Pagination info will be updated here -->
                </div>
                <nav class="flex justify-end items-center -space-x-px" id="pendingPaginationNav">
                    <!-- Pagination buttons will be updated here -->
                </nav>
            </div>

            <!-- Empty State for Pending -->
            <div id="pendingEmptyState" class="px-4 py-12 text-center text-gray-500 hidden">
                <i class="ri-time-line text-4xl mb-2 block"></i>
                No pending appointments found.
            </div>
        </div>

        <!-- Approved Appointments Section -->
        <div id="approvedSection" class="bg-white rounded-lg shadow-sm border border-gray-200 appt-tab-section hidden">
            <!-- Header Section -->
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Approved Appointments</h3>
                        <p class="text-gray-600 text-xs mt-1">Confirmed appointments ready for consultation</p>
                    </div>
                    <!-- Search Bar -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-search-line text-gray-400"></i>
                        </div>
                        <input type="text" id="approvedSearchInput" class="block w-80 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Search appointments...">
                    </div>
                </div>
            </div>

            <!-- Tab Navigation for Approved -->
            <div class="px-6 py-3 border-b border-gray-200">
                <nav class="flex space-x-8">
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="pending">
                        Pending (<span id="pendingCount"><?php echo $counts['pending']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="approved">
                        Approved (<span id="approvedCount"><?php echo $counts['approved']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="declined">
                        Declined (<span id="declinedCount"><?php echo $counts['declined']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="rescheduled">
                        Rescheduled (<span id="rescheduledCount"><?php echo $counts['rescheduled']; ?></span>)
                    </button>
                </nav>
            </div>

            <!-- Approved Appointments Table -->
            <div class="overflow-x-auto">
                <table id="approvedAppointmentsTable" class="min-w-full divide-y divide-gray-200 appointment-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DATE</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">TIME</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DOCTOR</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">REASON</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="approvedAppointmentsBody">
                        <!-- Approved appointments will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination for Approved Appointments -->
            <div id="approvedPagination" class="flex justify-between items-center mt-6 px-6 py-4 border-t border-gray-200 bg-gray-50" style="display: none;">
                <div class="text-sm text-gray-600" id="approvedPaginationInfo">
                    <!-- Pagination info will be updated here -->
                </div>
                <nav class="flex justify-end items-center -space-x-px" id="approvedPaginationNav">
                    <!-- Pagination buttons will be updated here -->
                </nav>
            </div>

            <!-- Empty State for Approved -->
            <div id="approvedEmptyState" class="px-4 py-12 text-center text-gray-500 hidden">
                <i class="ri-check-line text-4xl mb-2 block"></i>
                No approved appointments found.
            </div>
        </div>

        <!-- Declined Appointments Section -->
        <div id="declinedSection" class="bg-white rounded-lg shadow-sm border border-gray-200 appt-tab-section hidden">
            <!-- Header Section -->
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Declined Appointments</h3>
                        <p class="text-gray-600 text-xs mt-1">Appointments that have been declined</p>
                    </div>
                    <!-- Search Bar -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-search-line text-gray-400"></i>
                        </div>
                        <input type="text" id="declinedSearchInput" class="block w-80 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Search appointments...">
                    </div>
                </div>
            </div>

            <!-- Tab Navigation for Declined -->
            <div class="px-6 py-3 border-b border-gray-200">
                <nav class="flex space-x-8">
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="pending">
                        Pending (<span id="pendingCount"><?php echo $counts['pending']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="approved">
                        Approved (<span id="approvedCount"><?php echo $counts['approved']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="declined">
                        Declined (<span id="declinedCount"><?php echo $counts['declined']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="rescheduled">
                        Rescheduled (<span id="rescheduledCount"><?php echo $counts['rescheduled']; ?></span>)
                    </button>
                </nav>
            </div>

            <!-- Declined Appointments Table -->
            <div class="overflow-x-auto">
                <table id="declinedAppointmentsTable" class="min-w-full divide-y divide-gray-200 appointment-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DATE</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">TIME</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DOCTOR</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">REASON</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="declinedAppointmentsBody">
                        <!-- Declined appointments will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination for Declined Appointments -->
            <div id="declinedPagination" class="flex justify-between items-center mt-6 px-6 py-4 border-t border-gray-200 bg-gray-50" style="display: none;">
                <div class="text-sm text-gray-600" id="declinedPaginationInfo">
                    <!-- Pagination info will be updated here -->
                </div>
                <nav class="flex justify-end items-center -space-x-px" id="declinedPaginationNav">
                    <!-- Pagination buttons will be updated here -->
                </nav>
            </div>

            <!-- Empty State for Declined -->
            <div id="declinedEmptyState" class="px-4 py-12 text-center text-gray-500 hidden">
                <i class="ri-close-line text-4xl mb-2 block"></i>
                No declined appointments found.
            </div>
        </div>

        <!-- Rescheduled Appointments Section -->
        <div id="rescheduledSection" class="bg-white rounded-lg shadow-sm border border-gray-200 appt-tab-section hidden">
            <!-- Header Section -->
            <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Rescheduled Appointments</h3>
                        <p class="text-gray-600 text-xs mt-1">Appointments that have been rescheduled</p>
                    </div>
                    <!-- Search Bar -->
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="ri-search-line text-gray-400"></i>
                        </div>
                        <input type="text" id="rescheduledSearchInput" class="block w-80 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Search appointments...">
                    </div>
                </div>
            </div>

            <!-- Tab Navigation for Rescheduled -->
            <div class="px-6 py-3 border-b border-gray-200">
                <nav class="flex space-x-8">
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="pending">
                        Pending (<span id="pendingCount"><?php echo $counts['pending']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="approved">
                        Approved (<span id="approvedCount"><?php echo $counts['approved']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="declined">
                        Declined (<span id="declinedCount"><?php echo $counts['declined']; ?></span>)
                    </button>
                    <button type="button" class="appt-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="rescheduled">
                        Rescheduled (<span id="rescheduledCount"><?php echo $counts['rescheduled']; ?></span>)
                    </button>
                </nav>
            </div>

            <!-- Rescheduled Appointments Table -->
            <div class="overflow-x-auto">
                <table id="rescheduledAppointmentsTable" class="min-w-full divide-y divide-gray-200 appointment-table">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DATE</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">TIME</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DOCTOR</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">REASON</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">STATUS</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="rescheduledAppointmentsBody">
                        <!-- Rescheduled appointments will be loaded here -->
                    </tbody>
                </table>
            </div>

            <!-- Pagination for Rescheduled Appointments -->
            <div id="rescheduledPagination" class="flex justify-between items-center mt-6 px-6 py-4 border-t border-gray-200 bg-gray-50" style="display: none;">
                <div class="text-sm text-gray-600" id="rescheduledPaginationInfo">
                    <!-- Pagination info will be updated here -->
                </div>
                <nav class="flex justify-end items-center -space-x-px" id="rescheduledPaginationNav">
                    <!-- Pagination buttons will be updated here -->
                </nav>
            </div>

            <!-- Empty State for Rescheduled -->
            <div id="rescheduledEmptyState" class="px-4 py-12 text-center text-gray-500 hidden">
                <i class="ri-refresh-line text-4xl mb-2 block"></i>
                No rescheduled appointments found.
            </div>
        </div>
    </div>
</main>

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

    // Global variables and functions
    let patientId;
    let currentSearchTerms = {
        'pending': '',
        'approved': '',
        'declined': '',
        'rescheduled': ''
    };

    let currentPages = {
        'pending': 1,
        'approved': 1,
        'declined': 1,
        'rescheduled': 1
    };

    // Function to load appointments for a specific status with pagination and search
    function loadAppointments(status, page = 1, searchTerm = '') {
        const tbodyId = status + 'AppointmentsBody';
        const emptyStateId = status + 'EmptyState';
        const paginationId = status + 'Pagination';
        const tbody = document.getElementById(tbodyId);
        const emptyState = document.getElementById(emptyStateId);
        const pagination = document.getElementById(paginationId);

        if (!tbody) return;

        // Show loading state
        tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Loading appointments...</td></tr>';

        // Hide pagination and empty state while loading
        if (pagination) pagination.style.display = 'none';
        emptyState.classList.add('hidden');

        // Fetch appointments
        fetch('get_appointments.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `status=${status}&patient_id=${patientId}&page=${page}&search=${encodeURIComponent(searchTerm)}`
            })
            .then(response => {
                console.log('Fetch response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Received data:', data);
                console.log('Patient ID sent:', patientId);
                console.log('Status filter:', status);
                console.log('Page:', page);
                console.log('Search term:', searchTerm);

                if (data.success && data.appointments.length > 0) {
                    tbody.innerHTML = '';
                    data.appointments.forEach(appt => {
                        console.log('Rendering appointment for student_id:', appt.student_id);
                        const row = createAppointmentRow(appt, status);
                        tbody.appendChild(row);
                    });
                    emptyState.classList.add('hidden');

                    // Update pagination
                    if (data.pagination && data.pagination.total_records > 0) {
                        updatePagination(status, data.pagination);
                        pagination.style.display = 'flex';
                    } else {
                        pagination.style.display = 'none';
                    }
                } else {
                    tbody.innerHTML = '';
                    emptyState.classList.remove('hidden');
                    pagination.style.display = 'none';
                    console.log('No appointments found or request failed');
                }
            })
            .catch(error => {
                console.error('Error loading appointments:', error);
                tbody.innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-red-500">Error loading appointments</td></tr>';
                pagination.style.display = 'none';
            });
    }

    // Function to update pagination UI
    function updatePagination(status, pagination) {
        const paginationInfoId = status + 'PaginationInfo';
        const paginationNavId = status + 'PaginationNav';
        const paginationInfo = document.getElementById(paginationInfoId);
        const paginationNav = document.getElementById(paginationNavId);

        if (!paginationInfo || !paginationNav) return;

        const {
            current_page,
            total_pages,
            total_records,
            per_page
        } = pagination;
        const start = ((current_page - 1) * per_page) + 1;
        const end = Math.min(current_page * per_page, total_records);

        // Update pagination info
        paginationInfo.innerHTML = `Showing ${start} to ${end} of ${total_records} entries`;

        // Update pagination navigation
        let paginationHTML = '';

        // Previous button
        if (current_page > 1) {
            paginationHTML += `<button onclick="loadAppointments('${status}', ${current_page - 1}, currentSearchTerms['${status}'])" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </button>`;
        } else {
            paginationHTML += `<button disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-l-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none">
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </button>`;
        }

        // Page numbers
        const start_page = Math.max(1, current_page - 2);
        const end_page = Math.min(total_pages, current_page + 2);

        if (start_page > 1) {
            paginationHTML += `<button onclick="loadAppointments('${status}', 1, currentSearchTerms['${status}'])" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">1</button>`;
            if (start_page > 2) {
                paginationHTML += `<span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>`;
            }
        }

        for (let i = start_page; i <= end_page; i++) {
            if (i === current_page) {
                paginationHTML += `<button class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-300">${i}</button>`;
            } else {
                paginationHTML += `<button onclick="loadAppointments('${status}', ${i}, currentSearchTerms['${status}'])" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">${i}</button>`;
            }
        }

        if (end_page < total_pages) {
            if (end_page < total_pages - 1) {
                paginationHTML += `<span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>`;
            }
            paginationHTML += `<button onclick="loadAppointments('${status}', ${total_pages}, currentSearchTerms['${status}'])" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm focus:outline-hidden focus:bg-gray-100">${total_pages}</button>`;
        }

        // Next button
        if (current_page < total_pages) {
            paginationHTML += `<button onclick="loadAppointments('${status}', ${current_page + 1}, currentSearchTerms['${status}'])" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </button>`;
        } else {
            paginationHTML += `<button disabled class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm rounded-r-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none">
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </button>`;
        }

        paginationNav.innerHTML = paginationHTML;

        // Update current page tracking
        currentPages[status] = current_page;
    }

    // Function to create appointment row
    function createAppointmentRow(appt, status) {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';

        // Convert time to 12-hour format
        const timeDisplay = convertTimeTo12Hour(appt.time);

        // Status badge
        let statusBadge = '';
        switch (status) {
            case 'pending':
                statusBadge = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">Pending</span>';
                break;
            case 'approved':
                statusBadge = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>';
                break;
            case 'declined':
                statusBadge = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Declined</span>';
                break;
            case 'rescheduled':
                statusBadge = '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Rescheduled</span>';
                break;
        }

        row.innerHTML = `
            <td class="px-4 py-3 whitespace-nowrap w-1/5">
                <div class="text-sm text-gray-900 truncate">${appt.formatted_date}</div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap w-1/5">
                <div class="text-sm text-gray-900 truncate">${timeDisplay}</div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap w-1/5">
                <div class="text-sm text-gray-900 truncate">${appt.doctor_name || 'Dr. Sarah Johnson'}</div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap w-1/5">
                <div class="text-sm text-gray-900 truncate">${appt.reason}</div>
            </td>
            <td class="px-4 py-3 whitespace-nowrap w-1/5">
                ${statusBadge}
            </td>
        `;

        return row;
    }

    // Function to convert any time format to 12-hour format
    function convertTimeTo12Hour(timeString) {
        if (!timeString) {
            return timeString;
        }

        // If it's a time range (contains dash)
        if (timeString.includes('-')) {
            const times = timeString.split('-');
            if (times.length === 2) {
                const startTime = times[0].trim();
                const endTime = times[1].trim();

                // Convert start time
                const startDate = new Date('2000-01-01 ' + startTime);
                const startFormatted = startDate.toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });

                // Convert end time
                const endDate = new Date('2000-01-01 ' + endTime);
                const endFormatted = endDate.toLocaleTimeString([], {
                    hour: 'numeric',
                    minute: '2-digit',
                    hour12: true
                });

                return startFormatted + '-' + endFormatted;
            }
        }

        // For single times, convert to 12-hour format
        const timeDate = new Date('2000-01-01 ' + timeString);
        return timeDate.toLocaleTimeString([], {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        });
    }

    // Function to convert 24-hour time range to 12-hour format in JavaScript (legacy)
    function convertTimeRangeJS(timeRange) {
        return convertTimeTo12Hour(timeRange);
    }

    // Function to initialize the active tab
    function initializePatientTab(tabName) {
        // Hide all main sections first
        document.getElementById('calendarViewSection').classList.add('hidden');
        document.getElementById('appointmentsSection').classList.add('hidden');
        
        // Reset all tab buttons
        document.querySelectorAll('.patient-tab-btn').forEach(function(btn) {
            btn.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
            btn.classList.add('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');
        });
        
        // Show the target section and activate the corresponding tab
        if (tabName === 'calendar-view') {
            document.getElementById('calendarViewSection').classList.remove('hidden');
            const calendarTab = document.querySelector('[data-tab="calendar-view"]');
            if (calendarTab) {
                calendarTab.classList.remove('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');
                calendarTab.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
            }
        } else {
            // Default to appointments
            document.getElementById('appointmentsSection').classList.remove('hidden');
            const appointmentsTab = document.querySelector('[data-tab="appointments"]');
            if (appointmentsTab) {
                appointmentsTab.classList.remove('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');
                appointmentsTab.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-600');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        patientId = <?php echo $patient_id; ?>;

        // Initialize with saved tab or default to appointments
        const activeTab = localStorage.getItem('patientActiveTab') || 'appointments';
        initializePatientTab(activeTab);

        // Tab switching functionality
        const patientTabBtns = document.querySelectorAll('.patient-tab-btn');
        const patientTabSections = {
            'calendar-view': document.getElementById('calendarViewSection'),
            'appointments': document.getElementById('appointmentsSection')
        };

        patientTabBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const tab = this.getAttribute('data-tab');

                // Update tab buttons
                patientTabBtns.forEach(b => {
                    b.classList.remove('active', 'text-blue-600', 'border-blue-600');
                    b.classList.add('text-gray-500', 'border-transparent');
                });
                this.classList.add('active', 'text-blue-600', 'border-blue-600');
                this.classList.remove('text-gray-500', 'border-transparent');

                // Show/hide sections
                Object.values(patientTabSections).forEach(section => {
                    section.classList.add('hidden');
                });
                patientTabSections[tab].classList.remove('hidden');

                // Save active tab to localStorage
                localStorage.setItem('patientActiveTab', tab);
            });
        });

        // Appointment status tab switching
        const apptTabSections = {
            'pending': document.getElementById('pendingSection'),
            'approved': document.getElementById('approvedSection'),
            'declined': document.getElementById('declinedSection'),
            'rescheduled': document.getElementById('rescheduledSection')
        };

        // Handle tab clicks for each section separately
        Object.keys(apptTabSections).forEach(sectionStatus => {
            const section = apptTabSections[sectionStatus];
            const tabBtns = section.querySelectorAll('.appt-tab-btn');

            tabBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const status = this.getAttribute('data-status');

                    // Update ALL tab buttons across ALL sections
                    Object.values(apptTabSections).forEach(sec => {
                        const allTabsInSection = sec.querySelectorAll('.appt-tab-btn');
                        allTabsInSection.forEach(b => {
                            if (b.getAttribute('data-status') === status) {
                                // Set active for tabs matching the clicked status
                                b.classList.add('active', 'text-blue-600', 'border-blue-600');
                                b.classList.remove('text-gray-500', 'border-transparent');
                            } else {
                                // Set inactive for all other tabs
                                b.classList.remove('active', 'text-blue-600', 'border-blue-600');
                                b.classList.add('text-gray-500', 'border-transparent');
                            }
                        });
                    });

                    // Show/hide sections
                    Object.values(apptTabSections).forEach(section => {
                        section.classList.add('hidden');
                    });
                    apptTabSections[status].classList.remove('hidden');

                    // Load appointments for this status
                    loadAppointments(status, currentPages[status], currentSearchTerms[status]);
                });
            });
        });

        // Setup search functionality for each status
        ['pending', 'approved', 'declined', 'rescheduled'].forEach(status => {
            const searchInput = document.getElementById(status + 'SearchInput');
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.trim();
                    currentSearchTerms[status] = searchTerm;
                    currentPages[status] = 1; // Reset to first page when searching
                    loadAppointments(status, 1, searchTerm);
                });
            }
        });

        // Load initial appointments (pending)
        loadAppointments('pending', 1, '');

        // Doctor schedules data for calendar
        const doctorSchedules = <?php echo json_encode($doctor_schedules ?? []); ?>;
        const bookedAppointments = <?php echo json_encode($booked_appointments ?? []); ?>;

        // Function to convert 24-hour format to 12-hour format for display
        function convertTimeFormat(timeRange) {
            const parts = timeRange.split('-');
            if (parts.length === 2) {
                const startTime = parts[0].trim();
                const endTime = parts[1].trim();

                // Convert start time
                const startHour = parseInt(startTime.split(':')[0]);
                const startMinute = startTime.split(':')[1];
                const startAMPM = startHour >= 12 ? 'PM' : 'AM';
                const startDisplayHour = startHour === 0 ? 12 : (startHour > 12 ? startHour - 12 : startHour);

                // Convert end time
                const endHour = parseInt(endTime.split(':')[0]);
                const endMinute = endTime.split(':')[1];
                const endAMPM = endHour >= 12 ? 'PM' : 'AM';
                const endDisplayHour = endHour === 0 ? 12 : (endHour > 12 ? endHour - 12 : endHour);

                return `${startDisplayHour}:${startMinute} ${startAMPM} - ${endDisplayHour}:${endMinute} ${endAMPM}`;
            }
            return timeRange; // Return original if format is unexpected
        }

        const monthNames = [
            'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'
        ];
        let today = new Date();
        let currentMonth = today.getMonth(); // 0-based
        let currentYear = today.getFullYear();

        function getDoctorForDate(date) {
            const dateStr = date.getFullYear() + '-' +
                String(date.getMonth() + 1).padStart(2, '0') + '-' +
                String(date.getDate()).padStart(2, '0');
            return doctorSchedules.find(schedule => schedule.schedule_date === dateStr);
        }

        function renderCalendar(month, year) {
            const calendarGrid = document.getElementById('calendarGrid');
            calendarGrid.innerHTML = '';

            // Weekday headers
            const weekdays = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            weekdays.forEach(day => {
                const div = document.createElement('div');
                div.className = 'font-semibold text-gray-600';
                div.textContent = day;
                calendarGrid.appendChild(div);
            });

            // First day of month
            const firstDay = new Date(year, month, 1);
            const startDay = firstDay.getDay();

            // Days in month
            const daysInMonth = new Date(year, month + 1, 0).getDate();

            // Days in prev month
            const daysInPrevMonth = new Date(year, month, 0).getDate();

            // Fill prev month
            for (let i = 0; i < startDay; i++) {
                const div = document.createElement('div');
                div.className = 'text-gray-400';
                div.textContent = daysInPrevMonth - startDay + i + 1;
                calendarGrid.appendChild(div);
            }

            for (let d = 1; d <= daysInMonth; d++) {
                const dateObj = new Date(year, month, d);
                const isToday = d === new Date().getDate() && month === (new Date().getMonth()) && year === new Date().getFullYear();
                let cellClass = '';
                if (isToday) cellClass += 'bg-primary text-white rounded shadow-lg ring-2 ring-primary ';
                cellClass += 'hover:bg-blue-100 hover:text-black cursor-pointer transition group ';

                // Check if there's a doctor scheduled for this date
                const doctorSchedule = getDoctorForDate(dateObj);
                if (doctorSchedule && !isToday) {
                    cellClass += 'bg-primary text-white rounded shadow-lg ring-2 ring-primary ';
                }

                const div = document.createElement('div');
                div.className = cellClass;
                div.textContent = d;
                if (doctorSchedule) {
                    const docDiv = document.createElement('div');
                    docDiv.className = 'text-xs mt-1 font-medium text-white group-hover:text-blue-600 transition-colors';
                    docDiv.textContent = doctorSchedule.profession || 'Physician';
                    div.appendChild(docDiv);

                    // Add hover popup for time
                    div.addEventListener('mouseenter', function(e) {
                        let popup = document.createElement('div');
                        popup.className = 'fixed z-50 bg-white border border-blue-300 rounded shadow-lg p-3 text-xs text-left text-gray-800';
                        popup.style.top = (e.clientY + 10) + 'px';
                        popup.style.left = (e.clientX + 10) + 'px';

                        // Count available slots for this doctor and date
                        const dateStr = `${year}-${(month + 1).toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}`;
                        const doctorScheduleEntry = doctorSchedules.find(schedule =>
                            schedule.doctor_name === doctorSchedule.doctor_name &&
                            schedule.schedule_date === dateStr
                        );

                        // Generate 10 slots from the doctor schedule
                        let allSlots = [];
                        if (doctorScheduleEntry) {
                            const timeRange = doctorScheduleEntry.schedule_time;
                            const timeParts = timeRange.split('-');
                            if (timeParts.length === 2) {
                                const startTime = timeParts[0].trim();
                                const endTime = timeParts[1].trim();

                                // Parse start time
                                const startHour = parseInt(startTime.split(':')[0]);
                                const startMinute = parseInt(startTime.split(':')[1]);

                                // Generate 10 slots of 30 minutes each
                                for (let i = 0; i < 10; i++) {
                                    const slotStartHour = startHour + Math.floor((startMinute + i * 30) / 60);
                                    const slotStartMinute = (startMinute + i * 30) % 60;
                                    const slotEndHour = startHour + Math.floor((startMinute + (i + 1) * 30) / 60);
                                    const slotEndMinute = (startMinute + (i + 1) * 30) % 60;

                                    const slotStart = `${slotStartHour.toString().padStart(2, '0')}:${slotStartMinute.toString().padStart(2, '0')}`;
                                    const slotEnd = `${slotEndHour.toString().padStart(2, '0')}:${slotEndMinute.toString().padStart(2, '0')}`;

                                    allSlots.push({
                                        schedule_time: `${slotStart}-${slotEnd}`,
                                        doctor_name: doctorSchedule.doctor_name,
                                        schedule_date: dateStr
                                    });
                                }
                            }
                        }

                        // Filter out booked slots
                        const availableSlots = allSlots.filter(slot => {
                            const isBooked = bookedAppointments.some(booked =>
                                booked.date === dateStr && booked.time === slot.schedule_time
                            );
                            return !isBooked;
                        });

                        // Convert time format for display
                        const timeRange = doctorSchedule.schedule_time;
                        const displayTime = convertTimeFormat(timeRange);

                        popup.innerHTML = `<b>${doctorSchedule.profession || 'Physician'}</b><br>Available: <span class='text-blue-600'>${displayTime}</span><br>Slots: <span class='text-green-600'>${availableSlots.length} appointment slots</span>`;
                        popup.id = 'doctorPopup';
                        document.body.appendChild(popup);
                    });
                    div.addEventListener('mousemove', function(e) {
                        const popup = document.getElementById('doctorPopup');
                        if (popup) {
                            popup.style.top = (e.clientY + 10) + 'px';
                            popup.style.left = (e.clientX + 10) + 'px';
                        }
                    });
                    div.addEventListener('mouseleave', function() {
                        const popup = document.getElementById('doctorPopup');
                        if (popup) popup.remove();
                    });

                    // Add click event to open modal and prefill date/time
                    div.addEventListener('click', function() {
                        // Prevent booking for past dates
                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        const clickedDate = new Date(year, month, d);
                        if (clickedDate < today) {
                            showErrorModal('You cannot book an appointment for a past date.', 'Error');
                            return;
                        }

                        const modal = document.getElementById('bookApptModal');
                        modal.classList.remove('hidden');

                        // Set date in modal
                        const modalDate = document.getElementById('modalDate');
                        const modalTime = document.getElementById('modalTime');

                        if (modalDate && modalTime) {
                            // Format date as yyyy-mm-dd
                            const mm = (month + 1).toString().padStart(2, '0');
                            const dd = d.toString().padStart(2, '0');
                            modalDate.value = `${year}-${mm}-${dd}`;

                            // Set time automatically based on doctor schedule
                            modalTime.innerHTML = '';
                        }

                        // Get doctor schedule for this date
                        const dateStr = `${year}-${(month + 1).toString().padStart(2, '0')}-${d.toString().padStart(2, '0')}`;
                        const doctorScheduleEntry = doctorSchedules.find(schedule =>
                            schedule.doctor_name === doctorSchedule.doctor_name &&
                            schedule.schedule_date === dateStr
                        );

                        // Generate 10 slots from the doctor schedule
                        let allSlots = [];
                        if (doctorScheduleEntry) {
                            const timeRange = doctorScheduleEntry.schedule_time;
                            const timeParts = timeRange.split('-');
                            if (timeParts.length === 2) {
                                const startTime = timeParts[0].trim();
                                const endTime = timeParts[1].trim();

                                // Parse start time
                                const startHour = parseInt(startTime.split(':')[0]);
                                const startMinute = parseInt(startTime.split(':')[1]);

                                // Generate 10 slots of 30 minutes each
                                for (let i = 0; i < 10; i++) {
                                    const slotStartHour = startHour + Math.floor((startMinute + i * 30) / 60);
                                    const slotStartMinute = (startMinute + i * 30) % 60;
                                    const slotEndHour = startHour + Math.floor((startMinute + (i + 1) * 30) / 60);
                                    const slotEndMinute = (startMinute + (i + 1) * 30) % 60;

                                    const slotStart = `${slotStartHour.toString().padStart(2, '0')}:${slotStartMinute.toString().padStart(2, '0')}`;
                                    const slotEnd = `${slotEndHour.toString().padStart(2, '0')}:${slotEndMinute.toString().padStart(2, '0')}`;

                                    allSlots.push({
                                        schedule_time: `${slotStart}-${slotEnd}`,
                                        doctor_name: doctorSchedule.doctor_name,
                                        schedule_date: dateStr
                                    });
                                }
                            }
                        }

                        // Filter out booked slots
                        const availableSlots = allSlots.filter(slot => {
                            const isBooked = bookedAppointments.some(booked =>
                                booked.date === dateStr && booked.time === slot.schedule_time
                            );
                            return !isBooked;
                        });

                        // Auto-display the first available slot
                        if (availableSlots.length > 0 && modalTime) {
                            const firstSlot = availableSlots[0];

                            // Create and add the option
                            const option = document.createElement('option');
                            option.value = firstSlot.schedule_time;
                            option.textContent = convertTimeFormat(firstSlot.schedule_time);
                            option.selected = true;
                            modalTime.appendChild(option);
                        } else if (modalTime) {
                            // If no slots available, show message
                            const option = document.createElement('option');
                            option.value = '';
                            option.textContent = 'No available slots';
                            option.disabled = true;
                            option.selected = true;
                            modalTime.appendChild(option);
                        }
                    });
                }

                calendarGrid.appendChild(div);
            }

            // Fill next month
            const totalCells = startDay + daysInMonth;
            for (let i = 0; i < (7 - (totalCells % 7)) % 7; i++) {
                const div = document.createElement('div');
                div.className = 'text-gray-400';
                div.textContent = i + 1;
                calendarGrid.appendChild(div);
            }

            // Set month label
            document.getElementById('calendarMonth').textContent = monthNames[month] + ' ' + year;
        }

        // Function to reset the booking form
        function resetBookingForm() {
            const form = document.getElementById('bookApptForm');
            const modalTime = document.getElementById('modalTime');
            const modalDate = document.getElementById('modalDate');

            // Reset form fields
            if (form) {
                form.reset();
            }

            // Clear time dropdown
            if (modalTime) {
                modalTime.innerHTML = '';
            }

            // Clear date
            if (modalDate) {
                modalDate.value = '';
            }
        }

        // Modal open/close logic
        document.getElementById('closeModalBtn').addEventListener('click', function() {
            document.getElementById('bookApptModal').classList.add('hidden');
            resetBookingForm();
        });

        // Close modal on outside click
        window.addEventListener('click', function(e) {
            const modal = document.getElementById('bookApptModal');
            if (e.target === modal) {
                modal.classList.add('hidden');
                resetBookingForm();
            }
        });

        // Navigation buttons
        document.getElementById('prevMonthBtn').addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            renderCalendar(currentMonth, currentYear);
        });

        document.getElementById('nextMonthBtn').addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            renderCalendar(currentMonth, currentYear);
        });

        // Handle form submission
        document.getElementById('bookApptForm').addEventListener('submit', function(e) {
            // Form will submit normally to the same page
            // The PHP will process it and redirect back with success/error message

            // Show loading state
            const submitBtn = document.getElementById('bookApptBtn');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Booking...';
            submitBtn.disabled = true;

            // Re-enable button after a delay (in case of error)
            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            }, 3000);
        });

        // Auto-hide success/error messages
        setTimeout(() => {
            const messages = document.querySelectorAll('.fixed.top-4.right-4');
            messages.forEach(msg => {
                msg.style.transition = 'opacity 0.5s';
                msg.style.opacity = '0';
                setTimeout(() => msg.remove(), 500);
            });
        }, 5000);

        // Initialize calendar
        renderCalendar(currentMonth, currentYear);

    });
</script>

<?php include '../includep/footer.php'; ?>