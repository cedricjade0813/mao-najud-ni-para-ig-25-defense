<?php
// Determine user type and name for header
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// Include database connection at the top
require_once __DIR__ . '/../includes/db_connect.php';

$userName = "";
$userRole = "";
$userInitials = "";
$patient = null;
$unread_messages = 0;

// If a student is logged in, always fetch their student_id and name from imported_patients using id from session.
if (isset($_SESSION['student_row_id'])) { // expects 'student_row_id' to be set in session after login
    
    $row_id = $_SESSION['student_row_id'];
    
    // Use session caching to avoid repeated database queries
    if (!isset($_SESSION['patient_data']) || $_SESSION['patient_data']['id'] != $row_id) {
        try {
            // Ensure $db is available
            if (!isset($db) || !($db instanceof PDO)) {
                throw new Exception('Database connection not available');
            }
            
            // Fetch patient data using PDO (cache in session)
            $stmt = $db->prepare("SELECT student_id, name, dob, gender, address, civil_status, year_level, profile_image FROM imported_patients WHERE id = ? LIMIT 1");
            $stmt->execute([$row_id]);
            $patient_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($patient_data) {
                $patient = [
                    'student_id' => $patient_data['student_id'],
                    'name' => $patient_data['name'],
                    'dob' => $patient_data['dob'],
                    'gender' => $patient_data['gender'],
                    'address' => $patient_data['address'],
                    'civil_status' => $patient_data['civil_status'],
                    'year_level' => $patient_data['year_level'],
                    'profile_image' => $patient_data['profile_image']
                ];
                $_SESSION['patient_data'] = $patient;
                $_SESSION['patient_data']['id'] = $row_id;
            }
        } catch (PDOException $e) {
            // Handle error silently
        }
    } else {
        // Use cached patient data
        $patient = $_SESSION['patient_data'];
    }
    
    // Always fetch fresh notification data to avoid cache issues
    // Completely bypass all caching mechanisms
    try {
        // Debug: Log header notification fetch
        error_log("🔴 DEBUG: Header fetching notifications for student: $row_id");
        
        // Create a new database connection to avoid any connection caching
        $fresh_db = new PDO('mysql:host=localhost;dbname=clinic_management_system', 'root', '');
        $fresh_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Add cache-busting to the query
        $cache_buster = time();
        error_log("🔴 DEBUG: Cache buster timestamp: $cache_buster");
        
            // Get unread message and notification count for patient in single query
        $unread_stmt = $fresh_db->prepare('SELECT 
                (SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = FALSE) as messages,
                (SELECT COUNT(*) FROM notifications WHERE student_id = ? AND is_read = 0) as notifications');
            $unread_stmt->execute([$row_id, $row_id]);
            $unread_data = $unread_stmt->fetch(PDO::FETCH_ASSOC);
            
            $unread_messages = (int)$unread_data['messages'];
            $unread_notifs = (int)$unread_data['notifications'];
            
        error_log("🔴 DEBUG: Header fetched - Messages: $unread_messages, Notifications: $unread_notifs");
        
        // Close the fresh connection
        $fresh_db = null;
        
        // Don't cache notification data - always fetch fresh
        // This ensures real-time accuracy
        } catch (PDOException $e) {
        error_log("🔴 DEBUG: Header database error: " . $e->getMessage());
        $unread_messages = 0;
        $unread_notifs = 0;
    }
} elseif (isset($_SESSION['faculty_id'])) {
    // Faculty login
    $userName = $_SESSION['faculty_name'];
    $userRole = "Faculty";
    $userStudentId = $_SESSION['faculty_email'];
    
    // Fetch faculty data including profile image
    try {
        if (!isset($db) || !($db instanceof PDO)) {
            throw new Exception('Database connection not available');
        }
        
        $faculty_id = $_SESSION['faculty_id'];
        $stmt = $db->prepare("SELECT faculty_id, full_name, email, phone, address, department, profile_image FROM faculty WHERE faculty_id = ? LIMIT 1");
        $stmt->execute([$faculty_id]);
        $faculty_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($faculty_data) {
            $patient = [ // Using $patient variable for consistency with the avatar logic
                'faculty_id' => $faculty_data['faculty_id'],
                'name' => $faculty_data['full_name'],
                'email' => $faculty_data['email'],
                'phone' => $faculty_data['phone'],
                'address' => $faculty_data['address'],
                'department' => $faculty_data['department'],
                'profile_image' => $faculty_data['profile_image']
            ];
        }
        
        // Fetch unread messages and notifications for faculty
        $fresh_db = new PDO('mysql:host=localhost;dbname=clinic_management_system', 'root', '');
        $fresh_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $unread_stmt = $fresh_db->prepare('SELECT 
            (SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = FALSE) as messages,
            (SELECT COUNT(*) FROM notifications WHERE faculty_id = ? AND is_read = 0) as notifications');
        $unread_stmt->execute([$faculty_id, $faculty_id]);
        $unread_data = $unread_stmt->fetch(PDO::FETCH_ASSOC);
        
        $unread_messages = (int)$unread_data['messages'];
        $unread_notifs = (int)$unread_data['notifications'];
        
        error_log("🔴 DEBUG: Header fetched for faculty - Messages: $unread_messages, Notifications: $unread_notifs");
        
        // Close the fresh connection
        $fresh_db = null;
        
    } catch (PDOException $e) {
        error_log("🔴 DEBUG: Header database error for faculty: " . $e->getMessage());
        $unread_messages = 0;
        $unread_notifs = 0;
    }
} elseif (isset($_SESSION['user_name'])) {
    // Staff/Admin login
    $userName = $_SESSION['user_name'];
    $userRole = isset($_SESSION['role']) ? ucfirst($_SESSION['role']) : "Administrator";
    $userStudentId = "";
} else {
    $userName = "Guest";
    $userRole = "";
    $userStudentId = "";
}
// Calculate initials
if (!empty($userName)) {
    $parts = explode(' ', trim($userName));
    $userInitials = strtoupper(substr($parts[0], 0, 1));
    if (count($parts) > 1) {
        $userInitials .= strtoupper(substr($parts[1], 0, 1));
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Management Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <!-- Preline.js removed to prevent errors in patient folder -->
    <!-- <script src="https://unpkg.com/preline@latest/dist/preline.js"></script> -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#2B7BE4',
                        secondary: '#4CAF50'
                    },
                    borderRadius: {
                        'none': '0px',
                        'sm': '4px',
                        DEFAULT: '8px',
                        'md': '12px',
                        'lg': '16px',
                        'xl': '20px',
                        '2xl': '24px',
                        '3xl': '32px',
                        'full': '9999px',
                        'button': '8px'
                    }
                }
            }
        }
    </script>
    <!-- Optimized font loading -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <!-- Lightweight SVG icons instead of heavy font library -->
    
    
    <!-- ECharts only loaded when needed -->
    <script>
        // Lazy load ECharts only when charts are needed
        window.loadECharts = function() {
            if (!window.echarts) {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/echarts/5.5.0/echarts.min.js';
                document.head.appendChild(script);
            }
        };
    </script>
    <?php include_once '../includes/modal_system.php'; ?>
    <style>
        /* RemixIcon styles removed - using lightweight SVG icons instead */

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }

        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .custom-checkbox {
            position: relative;
            display: inline-block;
            width: 18px;
            height: 18px;
            border-radius: 4px;
            border: 2px solid #d1d5db;
            background-color: white;
            cursor: pointer;
        }

        .custom-checkbox.checked {
            background-color: #2B7BE4;
            border-color: #2B7BE4;
        }

        .custom-checkbox.checked::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 5px;
            width: 5px;
            height: 10px;
            border: solid white;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }

        .toggle-switch {
            position: relative;
            display: inline-block;
            width: 44px;
            height: 24px;
        }

        .toggle-switch input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: #e5e7eb;
            transition: .4s;
            border-radius: 34px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: .4s;
            border-radius: 50%;
        }

        input:checked+.toggle-slider {
            background-color: #2B7BE4;
        }

        input:checked+.toggle-slider:before {
            transform: translateX(20px);
        }

        .custom-select {
            position: relative;
            display: inline-block;
        }

        .custom-select-trigger {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            background-color: white;
            cursor: pointer;
            min-width: 150px;
        }

        .custom-select-options {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: white;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            margin-top: 0.25rem;
            z-index: 10;
            display: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .custom-select-option {
            padding: 0.5rem 1rem;
            cursor: pointer;
        }

        .custom-select-option:hover {
            background-color: #f3f4f6;
        }

        .custom-select.open .custom-select-options {
            display: block;
        }

        .drop-zone {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: border-color 0.3s ease;
            cursor: pointer;
        }

        .drop-zone:hover {
            border-color: #2B7BE4;
        }

        .drop-zone.active {
            border-color: #2B7BE4;
            background-color: rgba(43, 123, 228, 0.05);
        }

        /* Dark Theme Styles */
        .dark-theme {
            background-color: #1a1a1a !important;
            color: #e5e5e5 !important;
        }

        .dark-theme .bg-white {
            background-color: #2d2d2d !important;
        }

        .dark-theme .text-gray-800 {
            color: #e5e5e5 !important;
        }

        .dark-theme .text-gray-600 {
            color: #a0a0a0 !important;
        }

        .dark-theme .text-gray-500 {
            color: #888888 !important;
        }

        .dark-theme .border-gray-200 {
            border-color: #404040 !important;
        }

        .dark-theme .border-gray-300 {
            border-color: #555555 !important;
        }

        .dark-theme .bg-gray-50 {
            background-color: #333333 !important;
        }

        .dark-theme .bg-gray-100 {
            background-color: #404040 !important;
        }

        .dark-theme input, .dark-theme select, .dark-theme textarea {
            background-color: #2d2d2d !important;
            border-color: #555555 !important;
            color: #e5e5e5 !important;
        }

        .dark-theme input:focus, .dark-theme select:focus, .dark-theme textarea:focus {
            border-color: #3b82f6 !important;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2) !important;
        }
        
        /* Mobile responsive - profile dropdown positioning */
        @media (max-width: 400px) {
            #userDropdown {
                position: fixed !important;
                right: 10px !important;
                left: auto !important;
                top: 60px !important;
                transform: none !important;
            }
        }
    </style>
</head>

<body>

    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-white border-b border-gray-200 fixed top-0 left-0 w-full z-30">
            <div class="flex items-center justify-between px-6 py-3">
                <div class="flex items-center">
                    <img src="../logo.jpg" alt="St. Cecilia's College Logo"
                        class="h-12 w-12 object-contain rounded-full border border-gray-200 bg-white shadow mr-4" />
                    <h1 class="text-xl font-semibold text-gray-800 hidden md:block">Clinic Management System</h1>
                </div>
                <div class="flex items-center space-x-1">

                    

                    <div class="relative">
                        <div id="userAvatarBtn"
                            class="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white mr-2 cursor-pointer select-none overflow-hidden">
                            <?php 
                            // Check if user has a profile image
                            $headerProfileImage = null;
                            if (isset($patient) && $patient && isset($patient['profile_image']) && !empty($patient['profile_image'])) {
                                $headerProfileImage = '../' . $patient['profile_image'];
                                // Use absolute path for file_exists check
                                $absolutePath = dirname(__DIR__) . '/' . $patient['profile_image'];
                            }
                            ?>
                            <?php if ($headerProfileImage && file_exists($absolutePath)): ?>
                                <img src="<?php echo htmlspecialchars($headerProfileImage); ?>" 
                                     alt="Profile" class="w-full h-full object-cover rounded-full" id="headerProfileImage">
                            <?php else: ?>
                                <span class="font-medium">
                                    <?php
                                    if (isset($patient) && $patient) {
                                        // Show initials from patient name
                                        $parts = explode(' ', trim($patient['name']));
                                        $initials = strtoupper(substr($parts[0], 0, 1));
                                        if (count($parts) > 1) {
                                            $initials .= strtoupper(substr($parts[1], 0, 1));
                                        }
                                        echo htmlspecialchars($initials);
                                    } else {
                                        echo htmlspecialchars($userInitials);
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <!-- Dropdown Pop-up -->
                        <div id="userDropdown"
                            class="hidden absolute -right-46 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-100 z-50 mobile-dropdown">
                            <div class="py-2">
                                <button onclick="openProfileModal()" class="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left">
                                    <i class="ri-user-line mr-2 text-lg text-primary"></i> My Profile
                                </button>
                                <button onclick="openSettingsModal()" class="w-full flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 text-left">
                                    <i class="ri-settings-3-line mr-2 text-lg text-primary"></i> Settings & privacy
                                </button>
                                <div class="border-t my-2"></div>
                                <a href="../index.php"
                                    class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    <i class="ri-logout-box-line mr-2 text-lg"></i> Log out
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <?php if (isset($patient) && $patient): ?>
                            <p class="text-sm font-medium text-gray-800">
                                Hello, <?php echo htmlspecialchars($patient['name']); ?>
                            </p>
                            <?php if (isset($_SESSION['student_row_id'])): ?>
                                <!-- Patient login - show Student ID -->
                                <p class="text-xs text-gray-400">ID: <?php echo htmlspecialchars($patient['student_id'] ?? 'N/A'); ?></p>
                            <?php elseif (isset($_SESSION['faculty_id'])): ?>
                                <!-- Faculty login - show Department -->
                                <p class="text-xs text-gray-400">Department: <?php echo htmlspecialchars($patient['department'] ?? 'N/A'); ?></p>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-sm font-medium text-gray-800">Hello, <?php echo htmlspecialchars($userName); ?></p>
                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($userRole); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </header>
        <div class="flex flex-1">
            <!-- Sidebar -->
            <aside
                class="w-16 md:w-64 bg-white border-r border-gray-200 flex flex-col fixed top-[73px] left-0 h-[calc(100vh-56px)] z-40">
                <nav class="flex-1 pt-5 pb-4 overflow-y-auto">
                    <ul class="space-y-1 px-2" id="sidebarMenu">
                        <li>
                            <a href="profile.php"
                                class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-primary hover:bg-opacity-10 hover:text-primary"
                                data-page="profile.php">
                                <div class="w-8 h-8 flex items-center justify-center mr-3 md:mr-4">
                                    <i class="ri-user-line ri-lg"></i>
                                </div>
                                <span class="hidden md:inline">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="inbox.php"
                                class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-primary hover:bg-opacity-10 hover:text-primary"
                                data-page="inbox.php">
                                <div class="w-8 h-8 flex items-center justify-center mr-3 md:mr-4 relative">
                                    <i class="ri-inbox-line ri-lg"></i>
                                    <?php if ($unread_messages > 0): ?>
                                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs flex items-center justify-center rounded-full"><?php echo $unread_messages > 9 ? '9+' : $unread_messages; ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="hidden md:inline">Inbox</span>
                            </a>
                        </li>
                        <li>
                            <a href="appointments.php"
                                class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-primary hover:bg-opacity-10 hover:text-primary"
                                data-page="appointments.php">
                                <div class="w-8 h-8 flex items-center justify-center mr-3 md:mr-4">
                                    <i class="ri-calendar-line ri-lg"></i>
                                </div>
                                <span class="hidden md:inline">Appointments</span>
                            </a>
                        </li>
                        <li>
                            <a href="history.php"
                                class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-primary hover:bg-opacity-10 hover:text-primary"
                                data-page="history.php">
                                <div class="w-8 h-8 flex items-center justify-center mr-3 md:mr-4">
                                    <i class="ri-history-line ri-lg"></i>
                                </div>
                                <span class="hidden md:inline">Medical History</span>
                            </a>
                        </li>
                        <li>
                            <a href="notifications.php"
                                class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-primary hover:bg-opacity-10 hover:text-primary"
                                data-page="notifications.php">
                                <div class="w-8 h-8 flex items-center justify-center mr-3 md:mr-4 relative">
                                    <i class="ri-notification-line ri-lg"></i>
                                    <?php if (isset($unread_notifs) && (int)$unread_notifs > 0): ?>
                                        <span class="absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs flex items-center justify-center rounded-full"><?php echo $unread_notifs > 9 ? '9+' : $unread_notifs; ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="hidden md:inline">Notification</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                


            </aside>

            <script>
                // Sidebar active state logic
                (function() {
                    const sidebarLinks = document.querySelectorAll('#sidebarMenu a');
                    const currentPage = window.location.pathname.split('/').pop();
                    sidebarLinks.forEach(link => {
                        if (link.getAttribute('data-page') === currentPage) {
                            link.classList.add('bg-primary', 'bg-opacity-10', 'text-primary');
                            link.classList.remove('text-gray-600');
                        } else {
                            link.classList.remove('bg-primary', 'bg-opacity-10', 'text-primary');
                            link.classList.add('text-gray-600');
                        }
                        link.addEventListener('click', function() {
                            sidebarLinks.forEach(l => l.classList.remove('bg-primary', 'bg-opacity-10', 'text-primary'));
                            this.classList.add('bg-primary', 'bg-opacity-10', 'text-primary');
                        });
                    });
                })();
            </script>
            <script>
                // User avatar dropdown logic
                const userAvatarBtn = document.getElementById('userAvatarBtn');
                const userDropdown = document.getElementById('userDropdown');
                if (userAvatarBtn && userDropdown) {
                    userAvatarBtn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        userDropdown.classList.toggle('hidden');
                    });
                    document.addEventListener('click', function(e) {
                        if (!userDropdown.classList.contains('hidden')) {
                            userDropdown.classList.add('hidden');
                        }
                    });
                    userDropdown.addEventListener('click', function(e) {
                        e.stopPropagation();
                    });
                }
            </script>
            <?php
            // Patient notification logic (show only approved, cancelled, rescheduled appointment notifications)
            $notifCount = 0;
            $notifList = [];
            if (isset($patient['student_id'])) {
                $connNotif = new mysqli('localhost', 'root', '', 'clinic_management_system');
                if (!$connNotif->connect_errno) {
                    // Fetch notifications for this patient that are only for approved, cancelled, or rescheduled appointments
                    $sql = "SELECT message, created_at, is_read FROM notifications WHERE student_id = ? AND (
                        message LIKE '%approved%' OR message LIKE '%confirmed%' OR message LIKE '%declined%' OR message LIKE '%cancelled%' OR message LIKE '%canceled%' OR message LIKE '%rescheduled%'
                    ) ORDER BY created_at DESC LIMIT 10";
                    $stmt = $connNotif->prepare($sql);
                    $stmt->bind_param('i', $patient['student_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    while ($row = $result->fetch_assoc()) {
                        $notifList[] = [
                            'msg' => strip_tags($row['message']),
                            'created_at' => $row['created_at'],
                            'is_read' => $row['is_read']
                        ];
                    }
                    $stmt->close();
                    $stmt2 = $connNotif->prepare("SELECT COUNT(*) as cnt FROM notifications WHERE student_id = ? AND is_read = 0 AND (
                        message LIKE '%approved%' OR message LIKE '%confirmed%' OR message LIKE '%declined%' OR message LIKE '%cancelled%' OR message LIKE '%canceled%' OR message LIKE '%rescheduled%'
                    )");
                    $stmt2->bind_param('i', $patient['student_id']);
                    $stmt2->execute();
                    $stmt2->bind_result($notifCount);
                    $stmt2->fetch();
                    $stmt2->close();
                    $connNotif->close();
                }
            }
            ?>
            
            <!-- Profile Modal -->
            <div id="profileModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-lg">
                                <?php 
                                if (isset($patient) && $patient) {
                                    $parts = explode(' ', trim($patient['name']));
                                    $initials = strtoupper(substr($parts[0], 0, 1));
                                    if (count($parts) > 1) {
                                        $initials .= strtoupper(substr($parts[1], 0, 1));
                                    }
                                    echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
                                } else {
                                    echo htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8');
                                }
                                ?>
                            </div>
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Patient Profile</h2>
                                <p class="text-gray-600">Manage your profile and contact information</p>
                            </div>
                        </div>
                        <button id="closeProfileModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6">
                        <!-- Profile Photo Section -->
                        <div class="flex items-center space-x-6 mb-8">
                            <div class="relative">
                                <div id="profileImageContainer" class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center text-gray-500 text-2xl font-semibold overflow-hidden">
                                    <?php 
                                    // Check if patient has a profile image
                                    $profileImage = null;
                                    if ($patient && isset($patient['profile_image']) && !empty($patient['profile_image'])) {
                                        $profileImage = '../' . $patient['profile_image'];
                                        // Use absolute path for file_exists check
                                        $modalAbsolutePath = dirname(__DIR__) . '/' . $patient['profile_image'];
                                    }
                                    ?>
                                    <?php if ($profileImage && file_exists($modalAbsolutePath)): ?>
                                        <img id="profileImage" src="<?php echo htmlspecialchars($profileImage, ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="Profile Photo" class="w-full h-full object-cover rounded-full">
                                    <?php else: ?>
                                        <span id="profileInitials">
                                            <?php 
                                            if (isset($patient) && $patient) {
                                                $parts = explode(' ', trim($patient['name']));
                                                $initials = strtoupper(substr($parts[0], 0, 1));
                                                if (count($parts) > 1) {
                                                    $initials .= strtoupper(substr($parts[1], 0, 1));
                                                }
                                                echo htmlspecialchars($initials, ENT_QUOTES, 'UTF-8');
                                            } else {
                                                echo htmlspecialchars($userInitials, ENT_QUOTES, 'UTF-8');
                                            }
                                            ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div id="imageLoading" class="absolute inset-0 bg-black bg-opacity-50 rounded-full flex items-center justify-center hidden">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-white"></div>
                                </div>
                            </div>
                            <div>
                                <input type="file" id="profilePhotoInput" accept="image/jpeg,image/jpg,image/png,image/gif" 
                                       class="hidden" onchange="uploadProfilePhoto()">
                                <button onclick="document.getElementById('profilePhotoInput').click()" 
                                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2">
                                    <i class="ri-camera-line"></i>
                                    <span>Change Photo</span>
                                </button>
                                <p class="text-sm text-gray-500 mt-1">JPG, PNG, GIF up to 5MB</p>
                                <button id="removePhotoBtn" onclick="removeProfilePhoto()" 
                                        class="text-sm text-red-600 hover:text-red-800 mt-1 <?php echo ($profileImage && file_exists($modalAbsolutePath)) ? '' : 'hidden'; ?>">
                                    <i class="ri-delete-bin-line mr-1"></i>Remove Photo
                                </button>
                            </div>
                        </div>

                        <!-- Form Sections -->
                        <form id="profileForm" class="max-w-2xl mx-auto">
                            <div class="space-y-6">
                                <!-- Student ID -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Student ID</label>
                                    <input type="text" value="<?php echo isset($patient['student_id']) ? htmlspecialchars($patient['student_id'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 text-gray-600" readonly>
                                </div>

                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                                    <input type="text" id="profileName" name="name" value="<?php echo isset($patient['name']) ? htmlspecialchars($patient['name'], ENT_QUOTES, 'UTF-8') : ''; ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900" 
                                        placeholder="Enter your full name" required>
                                </div>

                                <!-- Date of Birth -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                                    <input type="date" id="profileDob" name="dob" value="<?php echo isset($patient['dob']) ? htmlspecialchars($patient['dob'], ENT_QUOTES, 'UTF-8') : ''; ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900">
                                </div>

                                <!-- Gender -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Gender</label>
                                    <select id="profileGender" name="gender" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900">
                                        <option value="">Select Gender</option>
                                        <option value="Male" <?php echo (isset($patient['gender']) && $patient['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                        <option value="Female" <?php echo (isset($patient['gender']) && $patient['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                        <option value="Other" <?php echo (isset($patient['gender']) && $patient['gender'] === 'Other') ? 'selected' : ''; ?>>Other</option>
                                    </select>
                                </div>

                                <!-- Year Level -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Year Level</label>
                                    <select id="profileYearLevel" name="year_level" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900">
                                        <option value="">Select Year Level</option>
                                        <option value="1st Year" <?php echo (isset($patient['year_level']) && $patient['year_level'] === '1st Year') ? 'selected' : ''; ?>>1st Year</option>
                                        <option value="2nd Year" <?php echo (isset($patient['year_level']) && $patient['year_level'] === '2nd Year') ? 'selected' : ''; ?>>2nd Year</option>
                                        <option value="3rd Year" <?php echo (isset($patient['year_level']) && $patient['year_level'] === '3rd Year') ? 'selected' : ''; ?>>3rd Year</option>
                                        <option value="4th Year" <?php echo (isset($patient['year_level']) && $patient['year_level'] === '4th Year') ? 'selected' : ''; ?>>4th Year</option>
                                    </select>
                                </div>

                                <!-- Address -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Address</label>
                                    <textarea id="profileAddress" name="address" rows="3" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900" 
                                        placeholder="Enter your address"><?php echo isset($patient['address']) ? htmlspecialchars($patient['address'], ENT_QUOTES, 'UTF-8') : ''; ?></textarea>
                                </div>

                                <!-- Civil Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Civil Status</label>
                                    <select id="profileCivilStatus" name="civil_status" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900">
                                        <option value="">Select Civil Status</option>
                                        <option value="Single" <?php echo (isset($patient['civil_status']) && $patient['civil_status'] === 'Single') ? 'selected' : ''; ?>>Single</option>
                                        <option value="Married" <?php echo (isset($patient['civil_status']) && $patient['civil_status'] === 'Married') ? 'selected' : ''; ?>>Married</option>
                                        <option value="Widowed" <?php echo (isset($patient['civil_status']) && $patient['civil_status'] === 'Widowed') ? 'selected' : ''; ?>>Widowed</option>
                                        <option value="Divorced" <?php echo (isset($patient['civil_status']) && $patient['civil_status'] === 'Divorced') ? 'selected' : ''; ?>>Divorced</option>
                                    </select>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Active</span>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">Student</span>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">Patient</span>
                                    </div>
                                </div>
                            </div>
                        </form>

                        <!-- Action Buttons -->
                        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                            <button type="button" id="cancelProfileBtn" onclick="cancelProfileEdit()" 
                                    class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancel
                            </button>
                            <button type="button" id="editProfileBtn" onclick="editProfile()" 
                                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Edit Profile
                            </button>
                            <button type="button" id="saveProfileBtn" onclick="saveProfile()" 
                                    class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors hidden">
                                Save Changes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            
            <script>
                // Notification icon dropdown logic
                (function() {
                    const notifBtn = document.getElementById('notifIconBtn');
                    const notifDropdown = document.getElementById('notifDropdown');
                    let notifOpen = false;
                    if (notifBtn && notifDropdown) {
                        notifBtn.addEventListener('click', function(e) {
                            e.stopPropagation();
                            notifDropdown.classList.toggle('hidden');
                            notifOpen = !notifOpen;
                        });
                        document.addEventListener('click', function(e) {
                            if (notifOpen && !notifBtn.contains(e.target) && !notifDropdown.contains(e.target)) {
                                notifDropdown.classList.add('hidden');
                                notifOpen = false;
                            }
                        });
                    }
                })();
            </script>

            <!-- Settings Modal -->
            <div id="settingsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-800">Settings</h2>
                            <p class="text-gray-600">Manage your application preferences</p>
                        </div>
                        <button id="closeSettingsModal" class="text-gray-400 hover:text-gray-600 transition-colors">
                            <i class="ri-close-line text-2xl"></i>
                        </button>
                    </div>

                    <!-- Modal Content -->
                    <div class="p-6">
                        <div class="space-y-6">
                            <!-- Theme -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3 flex items-center">
                                    <i class="ri-palette-line mr-2 text-blue-600"></i>
                                    Theme
                                </label>
                                <select id="themeSelect" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900">
                                    <option value="light">Light</option>
                                    <option value="dark">Dark</option>
                                    <option value="auto">Auto (System)</option>
                                </select>
                                <p class="text-sm text-gray-500 mt-2">Choose your preferred color scheme</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-4 p-6 border-t border-gray-200">
                        <button id="cancelSettingsBtn" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancel
                        </button>
                        <button id="saveSettingsBtn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Save Settings
                        </button>
                    </div>
                </div>
            </div>



            <script>
                // Patient Profile and Settings Modal Functionality
                document.addEventListener('DOMContentLoaded', function() {
                    // Get user info for theme storage
                    const userId = <?php echo isset($_SESSION['student_row_id']) ? json_encode($_SESSION['student_row_id']) : 'null'; ?>;
                    const userRole = 'patient';
                    const userKey = userRole + '_' + userId;
                    
                    // Modal Elements
                    const profileModal = document.getElementById('profileModal');
                    const closeProfileModal = document.getElementById('closeProfileModal');
                    const profileLink = document.getElementById('profileLink');
                    const settingsModal = document.getElementById('settingsModal');
                    const closeSettingsModal = document.getElementById('closeSettingsModal');
                    const settingsLink = document.getElementById('settingsLink');
                    const themeSelect = document.getElementById('themeSelect');
                    const saveSettingsBtn = document.getElementById('saveSettingsBtn');
                    const cancelSettingsBtn = document.getElementById('cancelSettingsBtn');
                    
                    // Profile Form Elements
                    const profileForm = document.getElementById('profileForm');
                    const editProfileBtn = document.getElementById('editProfileBtn');
                    const saveProfileBtn = document.getElementById('saveProfileBtn');
                    const cancelProfileBtn = document.getElementById('cancelProfileBtn');
                    
                    // Profile Photo Elements
                    const profilePhotoInput = document.getElementById('profilePhotoInput');
                    const profileImageContainer = document.getElementById('profileImageContainer');
                    const imageLoading = document.getElementById('imageLoading');
                    const removePhotoBtn = document.getElementById('removePhotoBtn');
                    
                    // Initialize profile form as disabled
                    const profileInputs = profileForm.querySelectorAll('input, select, textarea');
                    profileInputs.forEach(input => {
                        if (!input.readOnly) {
                            input.disabled = true;
                        }
                    });
                    
                    // Profile Modal Functions
                    window.openProfileModal = function() {
                        profileModal.classList.remove('hidden');
                        document.getElementById('userDropdown').classList.add('hidden');
                    }
                    
                    window.closeProfileModal = function() {
                        profileModal.classList.add('hidden');
                    }
                    
                    window.editProfile = function() {
                        profileInputs.forEach(input => {
                            if (!input.readOnly) {
                                input.disabled = false;
                            }
                        });
                        editProfileBtn.classList.add('hidden');
                        saveProfileBtn.classList.remove('hidden');
                        cancelProfileBtn.classList.remove('hidden');
                    }
                    
                    window.cancelProfileEdit = function() {
                        location.reload();
                    }
                    
                    window.saveProfile = function() {
                        const formData = new FormData(profileForm);
                        formData.append('update_patient_profile', '1');
                        
                        fetch('../update_patient_profile.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification(data.message, 'success');
                                profileInputs.forEach(input => {
                                    if (!input.readOnly) {
                                        input.disabled = true;
                                    }
                                });
                                editProfileBtn.classList.remove('hidden');
                                saveProfileBtn.classList.add('hidden');
                                cancelProfileBtn.classList.add('hidden');
                            } else {
                                showNotification(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('An error occurred while updating profile', 'error');
                        });
                    }
                    
                    // Make functions globally accessible
                    window.uploadProfilePhoto = function() {
                        const file = profilePhotoInput.files[0];
                        if (!file) return;
                        
                        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                        if (!allowedTypes.includes(file.type)) {
                            showNotification('Please select a valid image file (JPG, PNG, GIF)', 'error');
                            return;
                        }
                        
                        if (file.size > 5 * 1024 * 1024) {
                            showNotification('File size must be less than 5MB', 'error');
                            return;
                        }
                        
                        const formData = new FormData();
                        formData.append('profile_photo', file);
                        formData.append('upload_patient_photo', '1');
                        
                        imageLoading.classList.remove('hidden');
                        
                        fetch('../upload_patient_photo.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            imageLoading.classList.add('hidden');
                            if (data.success) {
                                if (data.image_path) {
                                    const img = document.createElement('img');
                                    img.src = '../' + data.image_path;
                                    img.alt = 'Profile Photo';
                                    img.className = 'w-full h-full object-cover rounded-full';
                                    img.id = 'profileImage';
                                    
                                    profileImageContainer.innerHTML = '';
                                    profileImageContainer.appendChild(img);
                                    removePhotoBtn.classList.remove('hidden');
                                    
                                    // Update header avatar
                                    const headerAvatar = document.getElementById('userAvatarBtn');
                                    if (headerAvatar) {
                                        const headerImg = document.createElement('img');
                                        headerImg.src = '../' + data.image_path;
                                        headerImg.alt = 'Profile';
                                        headerImg.className = 'w-full h-full object-cover rounded-full';
                                        headerImg.id = 'headerProfileImage';
                                        headerAvatar.innerHTML = '';
                                        headerAvatar.appendChild(headerImg);
                                    }
                                    
                                    // Clear session cache to force refresh on next page load
                                    fetch('../clear_patient_cache.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify({ clear_cache: '1' })
                                    }).catch(error => {
                                        console.log('Cache clear request failed:', error);
                                    });
                                }
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            imageLoading.classList.add('hidden');
                            console.error('Error:', error);
                            showNotification('An error occurred while uploading photo', 'error');
                        });
                    }
                    
                    window.removeProfilePhoto = function() {
                        fetch('../remove_patient_photo.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ remove_patient_photo: '1' })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                const initials = <?php echo json_encode($userInitials); ?>;
                                profileImageContainer.innerHTML = `<span id="profileInitials">${initials}</span>`;
                                removePhotoBtn.classList.add('hidden');
                                
                                // Update header avatar to show initials
                                const headerAvatar = document.getElementById('userAvatarBtn');
                                if (headerAvatar) {
                                    headerAvatar.innerHTML = `<span class="font-medium">${initials}</span>`;
                                }
                                
                                showNotification(data.message, 'success');
                            } else {
                                showNotification(data.message, 'error');
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            showNotification('An error occurred while removing photo', 'error');
                        });
                    }
                    
                    // Settings Modal Functions
                    window.openSettingsModal = function() {
                        settingsModal.classList.remove('hidden');
                        document.getElementById('userDropdown').classList.add('hidden');
                        loadCurrentSettings();
                    }
                    
                    window.closeSettingsModal = function() {
                        settingsModal.classList.add('hidden');
                    }
                    
                    window.loadCurrentSettings = function() {
                        const savedTheme = localStorage.getItem('theme_' + userKey);
                        if (savedTheme) {
                            themeSelect.value = savedTheme;
                        } else {
                            if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                                themeSelect.value = 'auto';
                            } else {
                                themeSelect.value = 'light';
                            }
                        }
                        applyTheme(themeSelect.value);
                    }
                    
                    window.saveSettings = function() {
                        const theme = themeSelect.value;
                        localStorage.setItem('theme_' + userKey, theme);
                        applyTheme(theme);
                        showNotification('Settings saved successfully', 'success');
                        closeSettingsModal();
                    }
                    
                    window.applyTheme = function(theme) {
                        const body = document.body;
                        const html = document.documentElement;
                        
                        body.classList.remove('dark-theme');
                        html.classList.remove('dark-theme');
                        
                        if (theme === 'dark' || (theme === 'auto' && window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                            body.classList.add('dark-theme');
                            html.classList.add('dark-theme');
                        }
                    }
                    
                    window.initializeTheme = function() {
                        const savedTheme = localStorage.getItem('theme_' + userKey);
                        if (savedTheme) {
                            applyTheme(savedTheme);
                        } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                            applyTheme('auto');
                        }
                    }
                    
                     window.showNotification = function(message, type = 'success') {
                         // Remove any existing toast
                         const existingToast = document.getElementById('profileToast');
                         if (existingToast) {
                             existingToast.remove();
                         }
                         
                         // Create toast notification (matching faculty/profile.php design)
                         const toastId = 'profileToast';
                         const icon = type === 'success' ? '&#10003;' : type === 'error' ? '&#10007;' : '&#8505;';
                         const color = type === 'success' ? '#2563eb' : type === 'error' ? '#dc2626' : '#2563eb';
                         
                         const notification = document.createElement('div');
                         notification.id = toastId;
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
                             <div style="background:rgba(255,255,255,0.7); color:${color}; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid ${color}; display:flex; align-items:center; gap:12px; pointer-events:auto;">
                                 <span style="font-size:2rem;line-height:1;color:${color};">${icon}</span>
                                 <span>${message}</span>
                             </div>
                         `;

                         document.body.appendChild(notification);
                         
                         // Auto-dismiss after 1.2 seconds with fade out (matching faculty/profile.php timing)
                         setTimeout(() => {
                             notification.style.transition = 'opacity 0.3s';
                             notification.style.opacity = '0';
                             setTimeout(() => {
                                 if (notification && notification.parentNode) {
                                     notification.parentNode.removeChild(notification);
                                 }
                             }, 300);
                         }, 1200);
                         
                         // Auto refresh after success message (only for profile updates)
                         if (type === 'success' && message.includes('Profile updated successfully')) {
                             setTimeout(() => {
                                 window.location.reload();
                             }, 1500);
                         }
                     }
                    
                    // Event Listeners
                    if (profileLink) {
                        profileLink.addEventListener('click', function(e) {
                            e.preventDefault();
                            openProfileModal();
                        });
                    }
                    
                    if (settingsLink) {
                        settingsLink.addEventListener('click', function(e) {
                            e.preventDefault();
                            openSettingsModal();
                        });
                    }
                    
                    closeProfileModal.addEventListener('click', window.closeProfileModal);
                    closeSettingsModal.addEventListener('click', window.closeSettingsModal);
                    
                    editProfileBtn.addEventListener('click', window.editProfile);
                    saveProfileBtn.addEventListener('click', window.saveProfile);
                    cancelProfileBtn.addEventListener('click', window.cancelProfileEdit);
                    
                    saveSettingsBtn.addEventListener('click', window.saveSettings);
                    cancelSettingsBtn.addEventListener('click', window.closeSettingsModal);
                    
                    // Close modals when clicking outside
                    [profileModal, settingsModal].forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === modal) {
                                if (modal === profileModal) {
                                    window.closeProfileModal();
                                } else if (modal === settingsModal) {
                                    window.closeSettingsModal();
                                }
                            }
                        });
                    });
                    
                    // Close modals with Escape key
                    document.addEventListener('keydown', function(e) {
                        if (e.key === 'Escape') {
                            if (!profileModal.classList.contains('hidden')) {
                                window.closeProfileModal();
                            }
                            if (!settingsModal.classList.contains('hidden')) {
                                window.closeSettingsModal();
                            }
                        }
                    });
                    
                    // Initialize theme on page load
                    window.initializeTheme();
                    
                    // Listen for system theme changes
                    if (window.matchMedia) {
                        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                            const savedTheme = localStorage.getItem('theme_' + userKey);
                            if (savedTheme === 'auto') {
                                applyTheme('auto');
                            }
                        });
                    }
                });
            </script>

