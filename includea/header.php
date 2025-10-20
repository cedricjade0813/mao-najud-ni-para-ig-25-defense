<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/../includes/db_connect.php';

$user_display_name = '';
$user_data = null;

// Fetch user data based on session - prioritize admin users
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    try {
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $full_name = $user_data['name'];
            $first_name = explode(' ', $full_name)[0];
            $user_display_name = htmlspecialchars($first_name);
            $current_role = 'admin';
        }
    } catch (PDOException $e) {
        // Handle error silently
    }
} elseif (isset($_SESSION['faculty_id']) && !isset($_SESSION['user_id'])) {
    try {
        $stmt = $db->prepare('SELECT * FROM faculty WHERE faculty_id = ?');
        $stmt->execute([$_SESSION['faculty_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $user_display_name = htmlspecialchars($user_data['full_name']);
            $current_role = 'staff';
        }
    } catch (PDOException $e) {
        // Handle error silently
    }
} elseif (isset($_SESSION['student_row_id']) && !isset($_SESSION['user_id']) && !isset($_SESSION['faculty_id'])) {
    try {
        $stmt = $db->prepare('SELECT * FROM imported_patients WHERE id = ?');
        $stmt->execute([$_SESSION['student_row_id']]);
        $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user_data) {
            $user_display_name = htmlspecialchars($user_data['name']);
            $current_role = 'student';
        }
    } catch (PDOException $e) {
        // Handle error silently
    }
}

// Fallback for display name
if (!$user_display_name) {
    if (isset($_SESSION['username'])) {
        $user_display_name = htmlspecialchars($_SESSION['username']);
    } elseif (isset($_SESSION['student_name'])) {
        $user_display_name = htmlspecialchars($_SESSION['student_name']);
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinic Management Dashboard</title>
    <script src="https://cdn.tailwindcss.com/3.4.16"></script>
    <script>tailwind.config = { theme: { extend: { colors: { primary: '#2B7BE4', secondary: '#4CAF50' }, borderRadius: { 'none': '0px', 'sm': '4px', DEFAULT: '8px', 'md': '12px', 'lg': '16px', 'xl': '20px', '2xl': '24px', '3xl': '32px', 'full': '9999px', 'button': '8px' } } } }</script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pacifico&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/remixicon/4.6.0/remixicon.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/echarts/5.5.0/echarts.min.js"></script>
    <?php include_once '../includes/modal_system.php'; ?>
    <style>
        :where([class^="ri-"])::before {
            content: "\f3c2";
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f9fafb;
        }

        /* Hide scrollbars while maintaining scroll functionality */
        html, body {
            scrollbar-width: none; /* Firefox */
            -ms-overflow-style: none; /* Internet Explorer 10+ */
        }

        html::-webkit-scrollbar,
        body::-webkit-scrollbar,
        *::-webkit-scrollbar {
            display: none; /* Safari and Chrome */
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
                            // Check if user has a profile image for header avatar
                            $headerProfileImage = null;
                            if ($user_data && isset($user_data['profile_image']) && !empty($user_data['profile_image'])) {
                                $headerProfileImage = '../' . $user_data['profile_image'];
                            }
                            ?>
                            <?php if ($headerProfileImage && file_exists($headerProfileImage)): ?>
                                <img id="headerProfileImage" src="<?php echo htmlspecialchars($headerProfileImage, ENT_QUOTES, 'UTF-8'); ?>" 
                                     alt="Profile Photo" class="w-full h-full object-cover rounded-full">
                            <?php else: ?>
                                <span id="headerInitials" class="font-medium">
                                    <?php
                                    // Show initials from user_display_name, fallback to 'U'
                                    $initials = 'U';
                                    if ($user_display_name) {
                                        $parts = explode(' ', $user_display_name);
                                        $initials = strtoupper(substr($parts[0], 0, 1));
                                        if (count($parts) > 1) {
                                            $initials .= strtoupper(substr($parts[1], 0, 1));
                                        }
                                    }
                                    echo htmlspecialchars($initials ?? 'U', ENT_QUOTES, 'UTF-8');
                                    ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <!-- Dropdown Pop-up -->
                        <div id="userDropdown"
                            class="hidden absolute -right-11 mt-2 w-56 bg-white rounded-lg shadow-lg border border-gray-100 z-50">
                            <div class="py-2">
                                <a href="#" id="profileLink" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="ri-user-line mr-2 text-lg text-primary"></i> My Profile
                                </a>
                                <a href="#" id="settingsLink" class="flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                    <i class="ri-settings-3-line mr-2 text-lg text-primary"></i> Settings & privacy
                                </a>
                                <div class="border-t my-2"></div>
                                <a href="../index.php"
                                    class="flex items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                    <i class="ri-logout-box-line mr-2 text-lg"></i> Log out
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="hidden md:block">
                        <p class="text-sm font-medium text-gray-800">
                            Hello, <?php echo htmlspecialchars($user_display_name ?: 'User', ENT_QUOTES, 'UTF-8'); ?>
                        </p>
                        <p class="text-xs text-gray-500">
                            <?php echo isset($current_role) ? htmlspecialchars(ucfirst($current_role), ENT_QUOTES, 'UTF-8') : (isset($_SESSION['role']) ? htmlspecialchars($_SESSION['role'], ENT_QUOTES, 'UTF-8') : ''); ?>
                        </p>
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
                            <a href="dashboard.php"
                                class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-primary hover:bg-opacity-10 hover:text-primary"
                                data-page="dashboard.php">
                                <div class="w-8 h-8 flex items-center justify-center mr-3 md:mr-4">
                                    <i class="ri-dashboard-line ri-lg"></i>
                                </div>
                                <span class="hidden md:inline">Dashboard</span>
                            </a>
                        </li>
                        <li>
                            <a href="import.php"
                                class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-primary hover:bg-opacity-10 hover:text-primary"
                                data-page="import.php">
                                <div class="w-8 h-8 flex items-center justify-center mr-3 md:mr-4">
                                    <i class="ri-import-line ri-lg"></i> <!-- If available in your version -->
                                </div>
                                <span class="hidden md:inline">Upload Patient</span>
                            </a>
                        </li>
                        <li>
                            <a href="users.php"
                                class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-primary hover:bg-opacity-10 hover:text-primary"
                                data-page="users.php">
                                <div class="w-8 h-8 flex items-center justify-center mr-3 md:mr-4">
                                    <i class="ri-user-line ri-lg"></i>
                                </div>
                                <span class="hidden md:inline">Manage Users</span>
                            </a>
                        </li>
                        <li>
                            <a href="reports.php"
                                class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-primary hover:bg-opacity-10 hover:text-primary"
                                data-page="reports.php">
                                <div class="w-8 h-8 flex items-center justify-center mr-3 md:mr-4">
                                    <i class="ri-bar-chart-line ri-lg"></i>
                                </div>
                                <span class="hidden md:inline">Reports</span>
                            </a>
                        </li>
                        <li>
                            <a href="logs.php"
                                class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-600 hover:bg-primary hover:bg-opacity-10 hover:text-primary"
                                data-page="logs.php">
                                <div class="w-8 h-8 flex items-center justify-center mr-3 md:mr-4">
                                    <i class="ri-clipboard-line ri-lg"></i>
                                </div>
                                <span class="hidden md:inline">System Logs</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                

            </aside>

            <script>
                    // Sidebar active state logic
                    (function () {
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
                            link.addEventListener('click', function () {
                                sidebarLinks.forEach(l => l.classList.remove('bg-primary', 'bg-opacity-10', 'text-primary'));
                                this.classList.add('bg-primary', 'bg-opacity-10', 'text-primary');
                            });
                        });
                    })();
            </script>
            <script>
                // User avatar dropdown logic
                document.addEventListener('DOMContentLoaded', function () {
                    const avatarBtn = document.getElementById('userAvatarBtn');
                    const dropdown = document.getElementById('userDropdown');
                    let dropdownOpen = false;
                    avatarBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        dropdown.classList.toggle('hidden');
                        dropdownOpen = !dropdownOpen;
                    });
                    document.addEventListener('click', function (e) {
                        if (dropdownOpen && !avatarBtn.contains(e.target) && !dropdown.contains(e.target)) {
                            dropdown.classList.add('hidden');
                            dropdownOpen = false;
                        }
                    });
                });
            </script>

            <!-- Profile Modal -->
            <div id="profileModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
                <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
                    <!-- Modal Header -->
                    <div class="flex items-center justify-between p-6 border-b border-gray-200">
                        <div class="flex items-center space-x-4">
                            
                            <div>
                                <h2 class="text-2xl font-bold text-gray-800">Profile</h2>
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
                                    // Check if user has a profile image
                                    $profileImage = null;
                                    if ($user_data && isset($user_data['profile_image']) && !empty($user_data['profile_image'])) {
                                        $profileImage = '../' . $user_data['profile_image'];
                                    }
                                    ?>
                                    <?php if ($profileImage && file_exists($profileImage)): ?>
                                        <img id="profileImage" src="<?php echo htmlspecialchars($profileImage, ENT_QUOTES, 'UTF-8'); ?>" 
                                             alt="Profile Photo" class="w-full h-full object-cover rounded-full">
                                    <?php else: ?>
                                        <span id="profileInitials"><?php echo htmlspecialchars($initials ?? 'U', ENT_QUOTES, 'UTF-8'); ?></span>
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
                                        class="text-sm text-red-600 hover:text-red-800 mt-1 <?php echo ($profileImage && file_exists($profileImage)) ? '' : 'hidden'; ?>">
                                    <i class="ri-delete-bin-line mr-1"></i>Remove Photo
                                </button>
                            </div>
                        </div>

                        <!-- Form Sections -->
                        <form id="profileForm" class="max-w-2xl mx-auto">
                            <div class="space-y-6">
                                <!-- ID -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">ID</label>
                                    <input type="text" value="<?php echo isset($user_data['id']) ? htmlspecialchars($user_data['id'], ENT_QUOTES, 'UTF-8') : 'N/A'; ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 text-gray-600" readonly>
                                </div>

                                <!-- Name -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                                    <input type="text" id="profileName" name="name" value="<?php echo isset($user_data['name']) ? htmlspecialchars($user_data['name'], ENT_QUOTES, 'UTF-8') : (isset($user_data['username']) ? htmlspecialchars($user_data['username'], ENT_QUOTES, 'UTF-8') : ''); ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900" 
                                        placeholder="Enter your full name" required>
                                </div>

                                <!-- Username -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                                    <input type="text" id="profileUsername" name="username" value="<?php echo isset($user_data['username']) ? htmlspecialchars($user_data['username'], ENT_QUOTES, 'UTF-8') : ''; ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900" 
                                        placeholder="Enter your username" required>
                                </div>

                                <!-- Email -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                                    <input type="email" id="profileEmail" name="email" value="<?php echo isset($user_data['email']) ? htmlspecialchars($user_data['email'], ENT_QUOTES, 'UTF-8') : ''; ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900" 
                                        placeholder="Enter your email address" required>
                                </div>

                                <!-- Role -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Role</label>
                                    <input type="text" value="<?php echo isset($current_role) ? htmlspecialchars(ucfirst($current_role), ENT_QUOTES, 'UTF-8') : (isset($_SESSION['role']) ? htmlspecialchars(ucfirst($_SESSION['role']), ENT_QUOTES, 'UTF-8') : 'N/A'); ?>" 
                                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-gray-50 text-gray-600" readonly>
                                </div>

                                <!-- Status -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                    <div class="flex flex-wrap gap-2">
                                        <span class="px-3 py-1 bg-green-100 text-green-800 rounded-full text-sm font-medium">Active</span>
                                        <span class="px-3 py-1 bg-blue-100 text-blue-800 rounded-full text-sm font-medium">Verified</span>
                                        <?php if (isset($current_role) && $current_role === 'admin'): ?>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">Administrator</span>
                                        <?php elseif (isset($current_role) && $current_role === 'staff'): ?>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">Medical Staff</span>
                                        <?php elseif (isset($current_role) && $current_role === 'student'): ?>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">Student</span>
                                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">Administrator</span>
                                        <?php elseif (isset($_SESSION['role']) && in_array($_SESSION['role'], ['doctor', 'nurse'])): ?>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">Medical Staff</span>
                                        <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'faculty'): ?>
                                        <span class="px-3 py-1 bg-purple-100 text-purple-800 rounded-full text-sm font-medium">Faculty</span>
                                        <?php endif; ?>
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
                                <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white text-gray-900">
                                    <option value="light" selected>Light</option>
                                    <option value="dark">Dark</option>
                                    <option value="auto">Auto (System)</option>
                                </select>
                                <p class="text-sm text-gray-500 mt-2">Choose your preferred color scheme</p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-gray-50">
                        <button id="cancelSettingsBtn" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors font-medium">
                            Cancel
                        </button>
                        <button id="saveSettingsBtn" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors font-medium">
                            Save Settings
                        </button>
                    </div>
                </div>
            </div>


            <script>
                // Profile photo upload functionality
                function uploadProfilePhoto() {
                    const fileInput = document.getElementById('profilePhotoInput');
                    const file = fileInput.files[0];
                    
                    if (!file) return;
                    
                    // Validate file type
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
                    if (!allowedTypes.includes(file.type)) {
                        showNotification('Invalid file type. Only JPG, PNG, and GIF are allowed.', 'error');
                        return;
                    }
                    
                    // Validate file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        showNotification('File too large. Maximum size is 5MB.', 'error');
                        return;
                    }
                    
                    // Show loading spinner
                    document.getElementById('imageLoading').classList.remove('hidden');
                    
                    // Create FormData
                    const formData = new FormData();
                    formData.append('profile_photo', file);
                    
                    // Upload file
                    fetch('upload_profile_photo.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('imageLoading').classList.add('hidden');
                        
                        if (data.success) {
                            // Update profile image in modal
                            const container = document.getElementById('profileImageContainer');
                            const initials = document.getElementById('profileInitials');
                            const image = document.getElementById('profileImage');
                            
                            if (image) {
                                image.src = '../' + data.image_path + '?t=' + Date.now();
                            } else {
                                // Create new image element
                                const newImage = document.createElement('img');
                                newImage.id = 'profileImage';
                                newImage.src = '../' + data.image_path + '?t=' + Date.now();
                                newImage.alt = 'Profile Photo';
                                newImage.className = 'w-full h-full object-cover rounded-full';
                                
                                if (initials) {
                                    initials.remove();
                                }
                                container.appendChild(newImage);
                            }
                            
                            // Update header avatar
                            const headerContainer = document.getElementById('userAvatarBtn');
                            const headerImage = document.getElementById('headerProfileImage');
                            const headerInitials = document.getElementById('headerInitials');
                            
                            if (headerImage) {
                                headerImage.src = '../' + data.image_path + '?t=' + Date.now();
                            } else {
                                // Create new header image element
                                const newHeaderImage = document.createElement('img');
                                newHeaderImage.id = 'headerProfileImage';
                                newHeaderImage.src = '../' + data.image_path + '?t=' + Date.now();
                                newHeaderImage.alt = 'Profile Photo';
                                newHeaderImage.className = 'w-full h-full object-cover rounded-full';
                                
                                if (headerInitials) {
                                    headerInitials.remove();
                                }
                                headerContainer.appendChild(newHeaderImage);
                            }
                            
                            // Show remove button
                            document.getElementById('removePhotoBtn').classList.remove('hidden');
                            
                            // Show success message
                            showNotification('Profile photo updated successfully!', 'success');
                        } else {
                            showNotification('Error: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        document.getElementById('imageLoading').classList.add('hidden');
                        console.error('Error:', error);
                        showNotification('An error occurred while uploading the photo.', 'error');
                    });
                }
                
                function removeProfilePhoto() {
                    if (!confirm('Are you sure you want to remove your profile photo?')) {
                        return;
                    }
                    
                    // Show loading spinner
                    document.getElementById('imageLoading').classList.remove('hidden');
                    
                    fetch('remove_profile_photo.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('imageLoading').classList.add('hidden');
                        
                        if (data.success) {
                            // Remove image and show initials in modal
                            const container = document.getElementById('profileImageContainer');
                            const image = document.getElementById('profileImage');
                            const initials = document.getElementById('profileInitials');
                            
                            if (image) {
                                image.remove();
                            }
                            
                            if (!initials) {
                                const newInitials = document.createElement('span');
                                newInitials.id = 'profileInitials';
                                newInitials.textContent = <?php echo json_encode($initials ?? 'U'); ?>;
                                container.appendChild(newInitials);
                            }
                            
                            // Remove image and show initials in header
                            const headerContainer = document.getElementById('userAvatarBtn');
                            const headerImage = document.getElementById('headerProfileImage');
                            const headerInitials = document.getElementById('headerInitials');
                            
                            if (headerImage) {
                                headerImage.remove();
                            }
                            
                            if (!headerInitials) {
                                const newHeaderInitials = document.createElement('span');
                                newHeaderInitials.id = 'headerInitials';
                                newHeaderInitials.className = 'font-medium';
                                newHeaderInitials.textContent = <?php echo json_encode($initials ?? 'U'); ?>;
                                headerContainer.appendChild(newHeaderInitials);
                            }
                            
                            // Hide remove button
                            document.getElementById('removePhotoBtn').classList.add('hidden');
                            
                            // Show success message
                            showNotification('Profile photo removed successfully!', 'success');
                        } else {
                            showNotification('Error: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        document.getElementById('imageLoading').classList.add('hidden');
                        console.error('Error:', error);
                        showNotification('An error occurred while removing the photo.', 'error');
                    });
                }
                
                // Profile editing functionality
                let originalProfileData = {};
                
                // Make functions globally accessible
                window.editProfile = function() {
                    // Store original values
                    originalProfileData = {
                        name: document.getElementById('profileName').value,
                        username: document.getElementById('profileUsername').value,
                        email: document.getElementById('profileEmail').value
                    };
                    
                    // Enable form fields
                    document.getElementById('profileName').disabled = false;
                    document.getElementById('profileUsername').disabled = false;
                    document.getElementById('profileEmail').disabled = false;
                    
                    // Show/hide buttons
                    document.getElementById('editProfileBtn').classList.add('hidden');
                    document.getElementById('saveProfileBtn').classList.remove('hidden');
                    document.getElementById('cancelProfileBtn').textContent = 'Cancel';
                };
                
                window.cancelProfileEdit = function() {
                    const cancelBtn = document.getElementById('cancelProfileBtn');
                    const saveBtn = document.getElementById('saveProfileBtn');
                    const editBtn = document.getElementById('editProfileBtn');
                    
                    // Check if we're in edit mode (save button is visible)
                    if (!saveBtn.classList.contains('hidden')) {
                        // In edit mode - restore original values and exit edit mode
                        document.getElementById('profileName').value = originalProfileData.name;
                        document.getElementById('profileUsername').value = originalProfileData.username;
                        document.getElementById('profileEmail').value = originalProfileData.email;
                        
                        // Disable form fields
                        document.getElementById('profileName').disabled = true;
                        document.getElementById('profileUsername').disabled = true;
                        document.getElementById('profileEmail').disabled = true;
                        
                        // Show/hide buttons
                        editBtn.classList.remove('hidden');
                        saveBtn.classList.add('hidden');
                        cancelBtn.textContent = 'Cancel';
                    } else {
                        // Not in edit mode - close the modal
                        document.getElementById('profileModal').classList.add('hidden');
                    }
                };
                
                window.saveProfile = function() {
                    const form = document.getElementById('profileForm');
                    const formData = new FormData(form);
                    
                    // Validate form
                    const name = formData.get('name').trim();
                    const username = formData.get('username').trim();
                    const email = formData.get('email').trim();
                    
                    if (!name || !username || !email) {
                        showNotification('All fields are required', 'error');
                        return;
                    }
                    
                    if (!email.includes('@')) {
                        showNotification('Please enter a valid email address', 'error');
                        return;
                    }
                    
                    // Show loading state
                    const saveBtn = document.getElementById('saveProfileBtn');
                    const originalText = saveBtn.textContent;
                    saveBtn.textContent = 'Saving...';
                    saveBtn.disabled = true;
                    
                    // Send update request
                    fetch('update_profile.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Update original data
                            originalProfileData = {
                                name: data.data.name,
                                username: data.data.username,
                                email: data.data.email
                            };
                            
                            // Disable form fields
                            document.getElementById('profileName').disabled = true;
                            document.getElementById('profileUsername').disabled = true;
                            document.getElementById('profileEmail').disabled = true;
                            
                            // Show/hide buttons
                            document.getElementById('editProfileBtn').classList.remove('hidden');
                            document.getElementById('saveProfileBtn').classList.add('hidden');
                            
                            // Show success message
                            showNotification('Profile updated successfully!', 'success');
                            
                            // Refresh the page after a short delay
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showNotification('Error: ' + data.message, 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('An error occurred while updating profile', 'error');
                    })
                    .finally(() => {
                        // Reset button state
                        saveBtn.textContent = originalText;
                        saveBtn.disabled = false;
                    });
                };
                
                function showNotification(message, type = 'success') {
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
                }

                // Settings functionality
                function loadCurrentSettings() {
                    // Load current theme from localStorage with user-specific key
                    const userKey = '<?php 
                        $key = 'guest';
                        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
                            $role = str_replace(['/', '\\', ' ', '-'], '_', $_SESSION['role']);
                            $key = 'admin_' . $_SESSION['user_id'] . '_' . $role;
                        } elseif (isset($_SESSION['faculty_id'])) {
                            $key = 'faculty_' . $_SESSION['faculty_id'];
                        } elseif (isset($_SESSION['student_row_id'])) {
                            $key = 'student_' . $_SESSION['student_row_id'];
                        }
                        echo $key;
                    ?>';
                    const currentTheme = localStorage.getItem('theme_' + userKey) || 'light';
                    const themeSelect = document.querySelector('#settingsModal select');
                    if (themeSelect) {
                        themeSelect.value = currentTheme;
                    }
                }

                function saveSettings() {
                    const themeSelect = document.querySelector('#settingsModal select');
                    const selectedTheme = themeSelect ? themeSelect.value : 'light';
                    
                    // Show loading state
                    const saveBtn = document.getElementById('saveSettingsBtn');
                    const originalText = saveBtn.textContent;
                    saveBtn.textContent = 'Saving...';
                    saveBtn.disabled = true;
                    
                    // Save theme to localStorage with user-specific key
                    const userKey = '<?php 
                        $key = 'guest';
                        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
                            $role = str_replace(['/', '\\', ' ', '-'], '_', $_SESSION['role']);
                            $key = 'admin_' . $_SESSION['user_id'] . '_' . $role;
                        } elseif (isset($_SESSION['faculty_id'])) {
                            $key = 'faculty_' . $_SESSION['faculty_id'];
                        } elseif (isset($_SESSION['student_row_id'])) {
                            $key = 'student_' . $_SESSION['student_row_id'];
                        }
                        echo $key;
                    ?>';
                    localStorage.setItem('theme_' + userKey, selectedTheme);
                    
                    // Apply theme immediately
                    applyTheme(selectedTheme);
                    
                    // Simulate save delay for better UX
                    setTimeout(() => {
                        // Reset button state
                        saveBtn.textContent = originalText;
                        saveBtn.disabled = false;
                        
                        // Close modal
                        document.getElementById('settingsModal').classList.add('hidden');
                        
                        // Show success message
                        showNotification('Settings saved successfully!', 'success');
                    }, 500);
                }

                function applyTheme(theme) {
                    const body = document.body;
                    const html = document.documentElement;
                    
                    // Remove existing theme classes
                    body.classList.remove('light-theme', 'dark-theme');
                    html.classList.remove('light-theme', 'dark-theme');
                    
                    if (theme === 'dark') {
                        body.classList.add('dark-theme');
                        html.classList.add('dark-theme');
                    } else if (theme === 'auto') {
                        // Check system preference
                        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                            body.classList.add('dark-theme');
                            html.classList.add('dark-theme');
                        } else {
                            body.classList.add('light-theme');
                            html.classList.add('light-theme');
                        }
                    } else {
                        // Light theme (default)
                        body.classList.add('light-theme');
                        html.classList.add('light-theme');
                    }
                }

                // Initialize theme on page load
                function initializeTheme() {
                    const userKey = '<?php 
                        $key = 'guest';
                        if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
                            $role = str_replace(['/', '\\', ' ', '-'], '_', $_SESSION['role']);
                            $key = 'admin_' . $_SESSION['user_id'] . '_' . $role;
                        } elseif (isset($_SESSION['faculty_id'])) {
                            $key = 'faculty_' . $_SESSION['faculty_id'];
                        } elseif (isset($_SESSION['student_row_id'])) {
                            $key = 'student_' . $_SESSION['student_row_id'];
                        }
                        echo $key;
                    ?>';
                    
                    // Debug: Log the user key and current theme
                    console.log('Theme Debug - User Key:', userKey);
                    console.log('Theme Debug - Current Theme:', localStorage.getItem('theme_' + userKey));
                    
                    const savedTheme = localStorage.getItem('theme_' + userKey) || 'light';
                    applyTheme(savedTheme);
                    
                    // Clear any old global theme settings to prevent conflicts
                    localStorage.removeItem('theme');
                }

                // Listen for system theme changes
                if (window.matchMedia) {
                    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function(e) {
                        const userKey = '<?php 
                            $key = 'guest';
                            if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
                                $role = str_replace(['/', '\\', ' ', '-'], '_', $_SESSION['role']);
                                $key = 'admin_' . $_SESSION['user_id'] . '_' . $role;
                            } elseif (isset($_SESSION['faculty_id'])) {
                                $key = 'faculty_' . $_SESSION['faculty_id'];
                            } elseif (isset($_SESSION['student_row_id'])) {
                                $key = 'student_' . $_SESSION['student_row_id'];
                            }
                            echo $key;
                        ?>';
                        const currentTheme = localStorage.getItem('theme_' + userKey);
                        if (currentTheme === 'auto') {
                            applyTheme('auto');
                        }
                    });
                }


                // Modal functionality
                document.addEventListener('DOMContentLoaded', function() {
                    // Initialize theme on page load
                    initializeTheme();
                    
                    // Initialize profile form fields as disabled
                    document.getElementById('profileName').disabled = true;
                    document.getElementById('profileUsername').disabled = true;
                    document.getElementById('profileEmail').disabled = true;
                    // Profile Modal
                    const profileModal = document.getElementById('profileModal');
                    const closeProfileModal = document.getElementById('closeProfileModal');
                    const profileLink = document.getElementById('profileLink');

                    if (profileLink && profileModal) {
                        profileLink.addEventListener('click', function(e) {
                            e.preventDefault();
                            profileModal.classList.remove('hidden');
                        });
                    }

                    if (closeProfileModal) {
                        closeProfileModal.addEventListener('click', function() {
                            profileModal.classList.add('hidden');
                        });
                    }
                    
                    // Make close modal function globally accessible
                    window.closeProfileModal = function() {
                        profileModal.classList.add('hidden');
                    };

                    // Settings Modal
                    const settingsModal = document.getElementById('settingsModal');
                    const closeSettingsModal = document.getElementById('closeSettingsModal');
                    const settingsLink = document.getElementById('settingsLink');

                    if (settingsLink && settingsModal) {
                        settingsLink.addEventListener('click', function(e) {
                            e.preventDefault();
                            settingsModal.classList.remove('hidden');
                            loadCurrentSettings();
                        });
                    }

                    if (closeSettingsModal) {
                        closeSettingsModal.addEventListener('click', function() {
                            settingsModal.classList.add('hidden');
                        });
                    }

                    // Cancel button functionality
                    const cancelSettingsBtn = document.getElementById('cancelSettingsBtn');
                    if (cancelSettingsBtn) {
                        cancelSettingsBtn.addEventListener('click', function() {
                            settingsModal.classList.add('hidden');
                        });
                    }

                    // Save Settings functionality
                    const saveSettingsBtn = document.getElementById('saveSettingsBtn');
                    if (saveSettingsBtn) {
                        saveSettingsBtn.addEventListener('click', function() {
                            saveSettings();
                        });
                    }




                    // Close modals when clicking outside
                    [profileModal, settingsModal].forEach(modal => {
                        modal.addEventListener('click', function(e) {
                            if (e.target === modal) {
                                modal.classList.add('hidden');
                            }
                        });
                    });
                });
            </script>
            <?php includeModalSystem(); ?>