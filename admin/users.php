<?php
session_start();

// Database connection (MySQL) - must be before AJAX endpoint
try {
    $db = new PDO('mysql:host=localhost;dbname=clinic_management_system;charset=utf8mb4', 'root', ''); // Change username/password if needed
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Create table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        username VARCHAR(255) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        role VARCHAR(50) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Active',
        password VARCHAR(255) NOT NULL
    )");
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// AJAX endpoint for adding users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_add_user'])) {
    // Disable error reporting to prevent HTML output
    error_reporting(0);
    ini_set('display_errors', 0);
    
    // Log that we're in the AJAX endpoint
    error_log('AJAX endpoint reached for adding user');
    
    try {
        // Validate required fields
        if (empty($_POST['name']) || empty($_POST['username']) || empty($_POST['email']) || empty($_POST['role'])) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'All fields are required'
            ]);
            exit;
        }
        
        $name = trim($_POST['name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = trim($_POST['role']);
        $status = 'Active';
        
        $stmt = $db->prepare('INSERT INTO users (name, username, email, role, status) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$name, $username, $email, $role, $status]);
        
        // Get the newly added user
        $new_user_id = $db->lastInsertId();
        $new_user_stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $new_user_stmt->execute([$new_user_id]);
        $new_user = $new_user_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Set proper content type
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => true,
            'message' => 'User added successfully!',
            'user' => $new_user
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database error: ' . $e->getMessage()
        ]);
        exit;
    }
}

include '../includea/header.php';

// Pagination settings
$records_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // Ensure page is at least 1
$offset = ($page - 1) * $records_per_page;

// Get total count for pagination
$total_count_stmt = $db->query('SELECT COUNT(*) FROM users');
$total_records = $total_count_stmt->fetchColumn();
$total_pages = ceil($total_records / $records_per_page);
// Old form handler removed - now using AJAX
// Handle update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $id = $_POST['edit_id'];
    $name = $_POST['edit_name'];
    $username = $_POST['edit_username'];
    $email = $_POST['edit_email'];
    $role = $_POST['edit_role'];
    $status = $_POST['edit_status'];
    try {
        $stmt = $db->prepare('UPDATE users SET name=?, username=?, email=?, role=?, status=? WHERE id=?');
        $stmt->execute([$name, $username, $email, $role, $status, $id]);
        $_SESSION['user_message'] = ['type' => 'success', 'text' => 'User updated successfully!'];
    } catch (PDOException $e) {
        $_SESSION['user_message'] = ['type' => 'error', 'text' => 'Failed to update user: ' . $e->getMessage()];
    }
    // header('Location: users.php');
    // exit;
}
// Fetch users with pagination
$stmt = $db->prepare('SELECT * FROM users ORDER BY id DESC LIMIT ' . (int)$records_per_page . ' OFFSET ' . (int)$offset);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
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


    .table-container {
        background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
        border: 1px solid #E5E7EB;
        border-radius: 8px;
        backdrop-filter: blur(10px);
    }

    .table-row {
        transition: all 0.2s ease;
    }


    .shadow-soft {
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
    }

    /* Enhanced shadow system */
    .shadow-medium {
        box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
    }

    .shadow-strong {
        box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
    }

    /* Smooth transitions for all interactive elements */
    button,
    a,
    input,
    select {
        transition: all 0.2s ease;
    }

    /* Focus states for accessibility */
    .focus-visible:focus {
        outline: 2px solid #3b82f6;
        outline-offset: 2px;
    }

    /* Responsive improvements */
    @media (max-width: 768px) {
        .summary-card {
            margin-bottom: 1rem;
        }

        .pagination-nav {
            flex-wrap: wrap;
            justify-content: center;
        }
    }
</style>

<main class="flex-1 overflow-y-auto main-content p-6 ml-16 md:ml-64 mt-[56px]">
    <!-- Success/Error Message -->
    <?php if (isset($_SESSION['user_message'])): ?>
        <?php
        if ($_SESSION['user_message']['type'] === 'success') {
            showSuccessModal(htmlspecialchars($_SESSION['user_message']['text']), 'Success');
        } else {
            showErrorModal(htmlspecialchars($_SESSION['user_message']['text']), 'Error');
        }
        unset($_SESSION['user_message']);
        ?>
    <?php endif; ?>

    <!-- Application Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">User Management</h1>
        <p class="text-gray-600">Manage your users and their permissions</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-lg p-6 summary-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Users</p>
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
                    <p class="text-sm font-medium text-gray-600 mb-1">Active Users</p>
                    <p class="text-3xl font-bold text-green-600"><?php
                                                                    $active_count = $db->query("SELECT COUNT(*) FROM users WHERE status = 'Active'")->fetchColumn();
                                                                    echo $active_count;
                                                                    ?></p>
                </div>
                <div class="w-12 h-12 bg-green-50 rounded-lg flex items-center justify-center">
                    <i class="ri-user-check-line text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg p-6 summary-card">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Search Results</p>
                    <p class="text-3xl font-bold text-purple-600" id="searchResultsCount"><?php echo $total_records; ?></p>
                </div>
                <div class="w-12 h-12 bg-purple-50 rounded-lg flex items-center justify-center">
                    <i class="ri-search-line text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>
    <!-- Users Table -->
    <div class="bg-white rounded-lg table-container">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-gray-800">Users</h3>
            <div class="flex items-center space-x-3">
                <!-- Search Bar -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-search-line text-gray-400"></i>
                    </div>
                    <input type="text" id="userSearch" placeholder="Search users by name, email, or role..."
                        class="block w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                </div>
                <!-- Add User Button -->
                <button id="addUserBtn"
                    class="px-4 py-2 bg-gray-900 text-white font-medium text-sm rounded-lg hover:bg-gray-800 transition-colors flex items-center space-x-2 h-9">
                    <i class="ri-add-line text-lg"></i>
                    <span>Add User</span>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full divide-y divide-gray-200 table-fixed" id="userTable">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/4">
                            Name
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/3">
                            Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                            Role
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                            Status
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="table-row hover:bg-gray-50"
                                data-id="<?= $user['id'] ?>"
                                data-name="<?= htmlspecialchars($user['name']) ?>"
                                data-username="<?= htmlspecialchars($user['username']) ?>"
                                data-email="<?= htmlspecialchars($user['email']) ?>"
                                data-role="<?= htmlspecialchars($user['role']) ?>"
                                data-status="<?= htmlspecialchars($user['status']) ?>">
                                <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                    <div class="truncate" title="<?= htmlspecialchars($user['name']) ?>">
                                        <?= htmlspecialchars($user['name']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    <div class="truncate" title="<?= htmlspecialchars($user['email']) ?>">
                                        <?= htmlspecialchars($user['email']) ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $roleColors = [
                                        'admin' => 'bg-purple-100 text-purple-800',
                                        'doctor/nurse' => 'bg-blue-100 text-blue-800',
                                        'user' => 'bg-gray-100 text-gray-800',
                                        'moderator' => 'bg-blue-100 text-blue-800',
                                        'viewer' => 'bg-yellow-100 text-yellow-800'
                                    ];
                                    $roleClass = $roleColors[$user['role']] ?? 'bg-gray-100 text-gray-800';
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $roleClass ?>">
                                        <?= htmlspecialchars(ucfirst($user['role'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($user['status'] === 'Active'): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Disabled
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button class="editBtn text-blue-600 hover:text-blue-900 mr-4"
                                        data-id="<?= $user['id'] ?>"
                                        data-name="<?= htmlspecialchars($user['name']) ?>"
                                        data-username="<?= htmlspecialchars($user['username']) ?>"
                                        data-email="<?= htmlspecialchars($user['email']) ?>"
                                        data-role="<?= htmlspecialchars($user['role']) ?>"
                                        data-status="<?= htmlspecialchars($user['status']) ?>">
                                        Edit
                                    </button>
                                    <?php if ($user['status'] === 'Active'): ?>
                                        <button class="disableBtn text-red-600 hover:text-red-900">
                                            Disable
                                        </button>
                                    <?php else: ?>
                                        <button class="enableBtn text-green-600 hover:text-green-900">
                                            Enable
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="ri-user-line text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg font-medium">No users found</p>
                                    <p class="text-gray-400 text-sm">Add a new user to get started</p>
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
                    <div class="text-sm text-gray-500" id="paginationInfo">
                        <?php
                        $start = $offset + 1;
                        $end = min($offset + $records_per_page, $total_records);
                        ?>
                        Showing <?php echo $start; ?> to <?php echo $end; ?> of <?php echo $total_records; ?> entries
                    </div>

                    <!-- Pagination Navigation -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">
                            <!-- Previous Button -->
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
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
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);

                            // Show first page if not in range
                            if ($start_page > 1): ?>
                                <a href="?page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">1</a>
                                <?php if ($start_page > 2): ?>
                                    <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                                <?php endif; ?>
                            <?php endif; ?>

                            <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                                <?php if ($i == $page): ?>
                                    <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-200 text-gray-800 border border-gray-200 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-300" aria-current="page"><?php echo $i; ?></button>
                                <?php else: ?>
                                    <a href="?page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <!-- Show last page if not in range -->
                            <?php if ($end_page < $total_pages): ?>
                                <?php if ($end_page < $total_pages - 1): ?>
                                    <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                                <?php endif; ?>
                                <a href="?page=<?php echo $total_pages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100"><?php echo $total_pages; ?></a>
                            <?php endif; ?>

                            <!-- Next Button -->
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
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
            </div>
        <?php endif; ?>
    </div>
    <!-- Add New User Modal -->
    <div id="addUserModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-strong w-full max-w-lg p-6 relative transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] overflow-y-auto" id="addUserModalContent">
            <button id="closeModalBtn" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
            <div class="mb-4">
                <h3 class="text-xl font-bold text-gray-800 mb-1">Add New User</h3>
                <p class="text-sm text-gray-600">Create a new user account with appropriate permissions</p>
            </div>
            <form id="addUserForm" class="space-y-3" method="post" autocomplete="off">
                <input type="hidden" name="ajax_add_user" value="1">

                <!-- Basic Information Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="name"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="username"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required />
                    </div>
                </div>

                <!-- Email and Role Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="user@email.com">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Role</label>
                        <select name="role"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="">Select Role</option>
                            <option value="admin">Administrator</option>
                            <option value="doctor/nurse">Doctor/Nurse</option>
                        </select>
                    </div>
                </div>

                <!-- Password Fields Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Password</label>
                        <input type="password" name="password" id="add_password"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                            required />
                        <span class="absolute right-3 top-8 cursor-pointer" onclick="togglePassword('add_password', this)">
                            <i class="ri-eye-off-line text-sm" id="add_password_icon"></i>
                        </span>
                    </div>
                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="confirm_password" id="add_confirm_password"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                            required />
                        <span class="absolute right-3 top-8 cursor-pointer" onclick="togglePassword('add_confirm_password', this)">
                            <i class="ri-eye-off-line text-sm" id="add_confirm_password_icon"></i>
                        </span>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white font-medium text-sm rounded-lg hover:bg-blue-700 transition-colors">
                        Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-strong w-full max-w-lg p-6 relative transform transition-all duration-300 scale-95 opacity-0 max-h-[90vh] overflow-y-auto" id="editUserModalContent">
            <button id="closeEditModalBtn" class="absolute top-4 right-4 text-gray-400 hover:text-gray-700 transition-colors">
                <i class="ri-close-line text-xl"></i>
            </button>
            <div class="mb-4">
                <h3 class="text-xl font-bold text-gray-800 mb-1">Edit User</h3>
                <p class="text-sm text-gray-600">Update user information and permissions</p>
            </div>
            <form id="editUserForm" class="space-y-3" method="post" autocomplete="off">
                <input type="hidden" name="edit_user" value="1">
                <input type="hidden" name="edit_id" id="edit_id">

                <!-- Basic Information Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Name</label>
                        <input type="text" name="edit_name" id="edit_name"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required />
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Username</label>
                        <input type="text" name="edit_username" id="edit_username"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required />
                    </div>
                </div>

                <!-- Email and Role Row -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" id="edit_email" name="edit_email" required
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            placeholder="user@email.com">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Role</label>
                        <select name="edit_role" id="edit_role"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            required>
                            <option value="">Select Role</option>
                            <option value="admin">Administrator</option>
                            <option value="doctor/nurse">Doctor/Nurse</option>
                        </select>
                    </div>
                </div>

                <!-- Status Row -->
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Status</label>
                    <select name="edit_status" id="edit_status"
                        class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                        required>
                        <option value="Active">Active</option>
                        <option value="Disabled">Disabled</option>
                    </select>
                </div>

                <!-- Password Fields Row (Optional) -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Password (optional)</label>
                        <input type="password" name="edit_password" id="edit_password"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10"
                            placeholder="Leave blank to keep unchanged" />
                        <span class="absolute right-3 top-8 cursor-pointer" onclick="togglePassword('edit_password', this)">
                            <i class="ri-eye-off-line text-sm" id="edit_password_icon"></i>
                        </span>
                    </div>
                    <div class="relative">
                        <label class="block text-xs font-medium text-gray-700 mb-1">Confirm Password</label>
                        <input type="password" name="edit_confirm_password" id="edit_confirm_password"
                            class="w-full border border-gray-300 rounded px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 pr-10" />
                        <span class="absolute right-3 top-8 cursor-pointer" onclick="togglePassword('edit_confirm_password', this)">
                            <i class="ri-eye-off-line text-sm" id="edit_confirm_password_icon"></i>
                        </span>
                    </div>
                </div>

                <!-- Action Button -->
                <div class="flex justify-end pt-2">
                    <button type="submit"
                        class="px-6 py-2 bg-blue-600 text-white font-medium text-sm rounded-lg hover:bg-blue-700 transition-colors">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
    // Modal logic with animations
    const addUserBtn = document.getElementById('addUserBtn');
    const addUserModal = document.getElementById('addUserModal');
    const addUserModalContent = document.getElementById('addUserModalContent');
    const closeModalBtn = document.getElementById('closeModalBtn');

    function openAddModal() {
        addUserModal.classList.remove('hidden');
        setTimeout(() => {
            addUserModalContent.classList.remove('scale-95', 'opacity-0');
            addUserModalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeAddModal() {
        addUserModalContent.classList.remove('scale-100', 'opacity-100');
        addUserModalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            addUserModal.classList.add('hidden');
        }, 300);
    }

    addUserBtn.addEventListener('click', openAddModal);
    closeModalBtn.addEventListener('click', closeAddModal);
    window.addEventListener('click', (e) => {
        if (e.target === addUserModal) closeAddModal();
    });
    // Edit User Modal logic with animations
    const editUserModal = document.getElementById('editUserModal');
    const editUserModalContent = document.getElementById('editUserModalContent');
    const closeEditModalBtn = document.getElementById('closeEditModalBtn');
    const editUserForm = document.getElementById('editUserForm');

    function openEditModal() {
        editUserModal.classList.remove('hidden');
        setTimeout(() => {
            editUserModalContent.classList.remove('scale-95', 'opacity-0');
            editUserModalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeEditModal() {
        editUserModalContent.classList.remove('scale-100', 'opacity-100');
        editUserModalContent.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            editUserModal.classList.add('hidden');
        }, 300);
    }

    document.querySelectorAll('.editBtn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('edit_id').value = this.getAttribute('data-id');
            document.getElementById('edit_name').value = this.getAttribute('data-name');
            document.getElementById('edit_username').value = this.getAttribute('data-username');
            document.getElementById('edit_email').value = this.getAttribute('data-email');
            document.getElementById('edit_role').value = this.getAttribute('data-role');
            document.getElementById('edit_status').value = this.getAttribute('data-status');
            openEditModal();
        });
    });
    closeEditModalBtn.addEventListener('click', closeEditModal);
    window.addEventListener('click', (e) => {
        if (e.target === editUserModal) closeEditModal();
    });
    // Search bar logic with debouncing
    let searchTimeout;
    document.getElementById('userSearch').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            searchUsers();
        }, 300); // Wait 300ms after user stops typing
    });

    function searchUsers(page = 1) {
        const searchTerm = document.getElementById('userSearch').value.trim();
        
        // Show loading state
        const tbody = document.querySelector('#userTable tbody');
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mb-4"></div>
                        <p class="text-gray-500 text-lg font-medium">Searching users...</p>
                    </div>
                </td>
            </tr>
        `;

        // Make AJAX request
        const formData = new FormData();
        formData.append('search', searchTerm);
        formData.append('page', page);

        fetch('search_users.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateTableWithData(data.users, data.total_records, data.current_page, data.total_pages, data.start, data.end);
                updateSearchResultsCount(data.total_records);
            } else {
                showErrorModal(data.message || 'Search failed', 'Error');
            }
        })
        .catch(error => {
            console.error('Search error:', error);
            showErrorModal('Search failed. Please try again.', 'Error');
        });
    }

    function updateTableWithData(users, totalRecords, currentPage, totalPages, start, end) {
        const tbody = document.querySelector('#userTable tbody');
        
        if (users.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center">
                            <i class="ri-user-line text-4xl text-gray-300 mb-4"></i>
                            <p class="text-gray-500 text-lg font-medium">No users found</p>
                            <p class="text-gray-400 text-sm">Try adjusting your search terms</p>
                        </div>
                    </td>
                </tr>
            `;
        } else {
            let tableHTML = '';
            users.forEach(user => {
                const roleColors = {
                    'admin': 'bg-purple-100 text-purple-800',
                    'doctor/nurse': 'bg-blue-100 text-blue-800',
                    'user': 'bg-gray-100 text-gray-800',
                    'moderator': 'bg-blue-100 text-blue-800',
                    'viewer': 'bg-yellow-100 text-yellow-800'
                };
                const roleClass = roleColors[user.role] || 'bg-gray-100 text-gray-800';
                
                tableHTML += `
                    <tr class="table-row hover:bg-gray-50"
                        data-id="${user.id}"
                        data-name="${user.name}"
                        data-username="${user.username}"
                        data-email="${user.email}"
                        data-role="${user.role}"
                        data-status="${user.status}">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">
                            <div class="truncate" title="${user.name}">
                                ${user.name}
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <div class="truncate" title="${user.email}">
                                ${user.email}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${roleClass}">
                                ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            ${user.status === 'Active' ? 
                                '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Active</span>' :
                                '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Disabled</span>'
                            }
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button class="editBtn text-blue-600 hover:text-blue-900 mr-4"
                                data-id="${user.id}"
                                data-name="${user.name}"
                                data-username="${user.username}"
                                data-email="${user.email}"
                                data-role="${user.role}"
                                data-status="${user.status}">
                                Edit
                            </button>
                            ${user.status === 'Active' ? 
                                '<button class="disableBtn text-red-600 hover:text-red-900">Disable</button>' :
                                '<button class="enableBtn text-green-600 hover:text-green-900">Enable</button>'
                            }
                        </td>
                    </tr>
                `;
            });
            tbody.innerHTML = tableHTML;
        }
        
        // Update pagination
        updatePagination(totalRecords, currentPage, totalPages, start, end);
    }

    function updateSearchResultsCount(count) {
        const searchResultsElement = document.getElementById('searchResultsCount');
        if (searchResultsElement) {
            searchResultsElement.textContent = count;
        }
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
                <button onclick="searchUsers(${currentPage - 1})" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
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
                <button onclick="searchUsers(1)" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">1</button>
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
                    <button onclick="searchUsers(${i})" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">${i}</button>
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
                <button onclick="searchUsers(${totalPages})" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">${totalPages}</button>
            `;
        }

        // Next Button
        if (currentPage < totalPages) {
            paginationHTML += `
                <button onclick="searchUsers(${currentPage + 1})" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
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
    // Action buttons with event delegation for dynamically generated content
    document.querySelector('#userTable tbody').addEventListener('click', async function(e) {
        if (e.target.classList.contains('editBtn')) {
            document.getElementById('edit_id').value = e.target.getAttribute('data-id');
            document.getElementById('edit_name').value = e.target.getAttribute('data-name');
            document.getElementById('edit_username').value = e.target.getAttribute('data-username');
            document.getElementById('edit_email').value = e.target.getAttribute('data-email');
            document.getElementById('edit_role').value = e.target.getAttribute('data-role');
            document.getElementById('edit_status').value = e.target.getAttribute('data-status');
            openEditModal();
        }
        if (e.target.classList.contains('disableBtn')) {
            const tr = e.target.closest('tr');
            const userId = tr.getAttribute('data-id');
            // Send AJAX to disable user
            const formData = new FormData();
            formData.append('disable_user', '1');
            formData.append('user_id', userId);
            const res = await fetch('user_actions.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                showSuccessModal('User disabled successfully!', 'Success', true);
                // Auto refresh page after 1.5 seconds to ensure correct styling
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showErrorModal(data.message || 'Failed to disable user.', 'Error');
            }
        }
        if (e.target.classList.contains('enableBtn')) {
            const tr = e.target.closest('tr');
            const userId = tr.getAttribute('data-id');
            // Send AJAX to enable user
            const formData = new FormData();
            formData.append('enable_user', '1');
            formData.append('user_id', userId);
            const res = await fetch('user_actions.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            if (data.success) {
                showSuccessModal('User enabled successfully!', 'Success', true);
                // Auto refresh page after 1.5 seconds to ensure correct styling
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                showErrorModal(data.message || 'Failed to enable user.', 'Error');
            }
        }
    });

    // Password show/hide toggle function
    function togglePassword(inputId, iconSpan) {
        const input = document.getElementById(inputId);
        const icon = iconSpan.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('ri-eye-off-line');
            icon.classList.add('ri-eye-line');
        } else {
            input.type = 'password';
            icon.classList.remove('ri-eye-line');
            icon.classList.add('ri-eye-off-line');
        }
    }

    // Add User AJAX
    document.getElementById('addUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        let valid = true;
        // Remove previous error messages
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        // Name validation
        if (!form.name.value.trim()) {
            showFieldError(form.name, 'Name is required');
            valid = false;
        }
        // Username validation
        if (!form.username.value.trim()) {
            showFieldError(form.username, 'Username is required');
            valid = false;
        }
        // Email validation
        if (!form.email.value.trim()) {
            showFieldError(form.email, 'Email is required');
            valid = false;
        } else if (!validateEmail(form.email.value.trim())) {
            showFieldError(form.email, 'Invalid email format');
            valid = false;
        }
        // Role validation
        if (!form.role.value) {
            showFieldError(form.role, 'Role is required');
            valid = false;
        }
        // Password validation
        if (!form.password.value) {
            showFieldError(form.password, 'Password is required');
            valid = false;
        }
        if (!form.confirm_password.value) {
            showFieldError(form.confirm_password, 'Confirm password is required');
            valid = false;
        }
        if (form.password.value && form.confirm_password.value && form.password.value !== form.confirm_password.value) {
            showFieldError(form.confirm_password, 'Passwords do not match!');
            valid = false;
        }
        if (!valid) return;
        const formData = new FormData(form);
        const res = await fetch('user_actions.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            closeAddModal();
            form.reset();
            setTimeout(() => {
                showSuccessModal(data.message, 'Success');
                // Auto refresh page after 1.5 seconds to ensure correct styling
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }, 200);
        } else {
            showErrorModal(data.message, 'Error');
        }
    });

    // Edit User AJAX
    document.getElementById('editUserForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const form = e.target;
        let valid = true;
        // Remove previous error messages
        form.querySelectorAll('.form-error').forEach(el => el.remove());
        // Name validation
        if (!form.edit_name.value.trim()) {
            showFieldError(form.edit_name, 'Name is required');
            valid = false;
        }
        // Username validation
        if (!form.edit_username.value.trim()) {
            showFieldError(form.edit_username, 'Username is required');
            valid = false;
        }
        // Email validation
        if (!form.edit_email.value.trim()) {
            showFieldError(form.edit_email, 'Email is required');
            valid = false;
        } else if (!validateEmail(form.edit_email.value.trim())) {
            showFieldError(form.edit_email, 'Invalid email format');
            valid = false;
        }
        // Role validation
        if (!form.edit_role.value) {
            showFieldError(form.edit_role, 'Role is required');
            valid = false;
        }
        // Password validation (optional)
        if (form.edit_password.value || form.edit_confirm_password.value) {
            if (!form.edit_password.value) {
                showFieldError(form.edit_password, 'Password is required');
                valid = false;
            }
            if (!form.edit_confirm_password.value) {
                showFieldError(form.edit_confirm_password, 'Confirm password is required');
                valid = false;
            }
            if (form.edit_password.value !== form.edit_confirm_password.value) {
                showFieldError(form.edit_confirm_password, 'Passwords do not match!');
                valid = false;
            }
        }
        if (!valid) return;
        const formData = new FormData(form);
        const res = await fetch('user_actions.php', {
            method: 'POST',
            body: formData
        });
        const data = await res.json();
        if (data.success) {
            closeEditModal();
            setTimeout(() => {
                showSuccessModal(data.message, 'Success', true);
                // Auto refresh page after 1.5 seconds to ensure correct styling
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            }, 200);
        } else {
            showErrorModal(data.message, 'Error');
        }
    });

    // Helper to show error message below a field
    function showFieldError(input, message) {
        const error = document.createElement('div');
        error.className = 'form-error text-xs text-red-600 mt-1';
        error.textContent = message;
        input.parentNode.appendChild(error);
    }



    // Email validation function
    function validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(String(email).toLowerCase());
    }

    // AJAX form submission for adding users
    function attachFormHandler() {
        const addUserForm = document.getElementById('addUserForm');
        if (addUserForm) {
            console.log('Form found, attaching handler');
            
            // Remove any existing event listeners
            addUserForm.removeEventListener('submit', handleFormSubmit);
            
            // Add new event listener
            addUserForm.addEventListener('submit', handleFormSubmit);
            
            // Prevent default form submission
            addUserForm.onsubmit = function(e) {
                e.preventDefault();
                return false;
            };
        } else {
            console.log('Form not found, retrying...');
            setTimeout(attachFormHandler, 100);
        }
    }
    
    async function handleFormSubmit(e) {
        e.preventDefault();
        e.stopPropagation();
        
        console.log('Form submission intercepted');
        
        const form = e.target || this;
        
        // Prevent duplicate submissions
        if (form.getAttribute('data-submitting') === 'true') {
            console.log('Form already submitting, ignoring duplicate submission');
            return;
        }
        
        form.setAttribute('data-submitting', 'true');
        
        const formData = new FormData(form);
        
        // Debug: Log form data
        console.log('Form data:', Object.fromEntries(formData.entries()));
        
        // Prevent any other form handlers from interfering
        form.removeEventListener('submit', handleFormSubmit);
        
        // Prevent default form submission
        form.onsubmit = function() { return false; };
        
        // Additional prevention measures
        form.setAttribute('onsubmit', 'return false;');
        form.setAttribute('data-ajax-handled', 'true');
        
        // Debug: Check table state before submission
        const tbody = document.getElementById('usersTableBody');
        const existingRows = tbody ? tbody.querySelectorAll('tr').length : 0;
        console.log('Table rows before submission:', existingRows);
        
        try {
            console.log('Sending AJAX request...');
            
            // Ensure the ajax_add_user parameter is included
            if (!formData.has('ajax_add_user')) {
                formData.append('ajax_add_user', '1');
            }
            
            const response = await fetch('', {
                method: 'POST',
                body: formData
            });
            
            console.log('Response status:', response.status);
            console.log('Response headers:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text);
                showErrorModal('Server returned invalid response', 'Error');
                return;
            }
            
            const data = await response.json();
            console.log('Response data:', data);
            
            if (data.success) {
                // Add the new user to the table
                addUserToTable(data.user);
                
                // Show success modal (same as medicine addition)
                showSuccessModal('User Added Successfully', 'Success', true);
                
                // Reset form
                form.reset();
                
                // Close modal if it exists
                const modal = document.getElementById('addUserModal');
                if (modal) {
                    modal.classList.add('hidden');
                }
                
                // Prevent any further error handling
                return;
            } else {
                showErrorModal('Error: ' + (data.error || 'Failed to add user'), 'Error');
            }
        } catch (error) {
            console.error('AJAX Error:', error);
            console.error('Error details:', {
                name: error.name,
                message: error.message,
                stack: error.stack
            });
            
            // Don't show the default error modal, use our custom one
            if (error.name === 'SyntaxError' && error.message.includes('JSON')) {
                showErrorModal('Server returned invalid data. Please try again.', 'Error');
            } else {
                showErrorModal('An error occurred while adding the user: ' + error.message, 'Error');
            }
        } finally {
            // Reset the submitting flag
            form.removeAttribute('data-submitting');
        }
    }

    // Add new user to the table
    function addUserToTable(user) {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) {
            console.error('Table body not found');
            return;
        }
        
        // Check if table is empty (no data message)
        const isEmpty = tbody.querySelector('td[colspan]');
        if (isEmpty) {
            // Remove empty state message
            isEmpty.parentElement.remove();
        }
        
        // Get current page info
        const currentPage = getCurrentPage();
        const recordsPerPage = 10;
        
        // Count current rows on the page
        const currentRows = tbody.querySelectorAll('tr:not([data-empty="true"])');
        const currentRowCount = currentRows.length;
        
        // Create role colors mapping
        const roleColors = {
            'admin': 'bg-purple-100 text-purple-800',
            'doctor/nurse': 'bg-blue-100 text-blue-800',
            'user': 'bg-gray-100 text-gray-800',
            'moderator': 'bg-blue-100 text-blue-800',
            'viewer': 'bg-yellow-100 text-yellow-800'
        };
        
        const roleClass = roleColors[user.role] || 'bg-gray-100 text-gray-800';
        const statusClass = user.status === 'Active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800';
        const statusText = user.status === 'Active' ? 'Active' : 'Disabled';
        
        // Create new row
        const newRow = document.createElement('tr');
        newRow.className = 'table-row hover:bg-gray-50';
        newRow.setAttribute('data-id', user.id);
        newRow.setAttribute('data-name', user.name);
        newRow.setAttribute('data-username', user.username);
        newRow.setAttribute('data-email', user.email);
        newRow.setAttribute('data-role', user.role);
        newRow.setAttribute('data-status', user.status);
        
        newRow.innerHTML = `
            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                <div class="truncate" title="${user.name}">
                    ${user.name}
                </div>
            </td>
            <td class="px-6 py-4 text-sm text-gray-500">
                <div class="truncate" title="${user.email}">
                    ${user.email}
                </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${roleClass}">
                    ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                    ${statusText}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button class="editBtn text-blue-600 hover:text-blue-900 mr-4"
                    data-id="${user.id}"
                    data-name="${user.name}"
                    data-username="${user.username}"
                    data-email="${user.email}"
                    data-role="${user.role}"
                    data-status="${user.status}">
                    Edit
                </button>
                ${user.status === 'Active' ? 
                    `<button class="disableBtn text-red-600 hover:text-red-900">Disable</button>` : 
                    `<button class="enableBtn text-green-600 hover:text-green-900">Enable</button>`
                }
            </td>
        `;
        
        // Check if we're on the first page and if adding this user would exceed the page limit
        if (currentPage === 1 && currentRowCount >= recordsPerPage) {
            // If we're on page 1 and already have 10 entries, we need to move the last entry to page 2
            // Remove the last row from current page
            const lastRow = currentRows[currentRows.length - 1];
            if (lastRow) {
                lastRow.remove();
            }
        }
        
        // Insert the new user at the beginning of the table (most recent first)
        tbody.insertBefore(newRow, tbody.firstChild);
        
        // Update pagination count and refresh the display
        updatePaginationCount();
        
        // Update the total users count in the summary card
        updateTotalUsersCount();
        
        console.log('User added to table:', user.name);
    }
    
    // Helper function to get current page number
    function getCurrentPage() {
        const urlParams = new URLSearchParams(window.location.search);
        const page = parseInt(urlParams.get('page')) || 1;
        return page;
    }
    
    // Update total users count in summary card
    function updateTotalUsersCount() {
        // Find the total users count element
        const totalUsersElement = document.querySelector('.summary-card .text-3xl.font-bold.text-gray-800');
        if (totalUsersElement) {
            const currentCount = parseInt(totalUsersElement.textContent) || 0;
            totalUsersElement.textContent = currentCount + 1;
        }
        
        // Also update the search results count
        const searchResultsElement = document.getElementById('searchResultsCount');
        if (searchResultsElement) {
            const currentCount = parseInt(searchResultsElement.textContent) || 0;
            searchResultsElement.textContent = currentCount + 1;
        }
    }

    // Preserve table state and prevent interference
    function preserveTableState() {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) return;
        
        // Store current table state
        const currentRows = Array.from(tbody.querySelectorAll('tr')).map(row => ({
            element: row,
            html: row.outerHTML
        }));
        
        // Return function to restore state if needed
        return function() {
            if (tbody.children.length === 0) {
                currentRows.forEach(({html}) => {
                    tbody.insertAdjacentHTML('beforeend', html);
                });
            }
        };
    }

    // Monitor table changes
    function monitorTableChanges() {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) return;
        
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.type === 'childList') {
                    console.log('Table changed:', tbody.children.length, 'rows');
                }
            });
        });
        
        observer.observe(tbody, {
            childList: true,
            subtree: true
        });
    }

    // Initialize monitoring when page loads
    document.addEventListener('DOMContentLoaded', function() {
        monitorTableChanges();
        attachFormHandler();
    });
    
    // Also try to attach form handler immediately
    attachFormHandler();

    // Prevent default browser error modals
    window.addEventListener('error', function(e) {
        console.error('Global error caught:', e.error);
        // Prevent the default browser error modal
        e.preventDefault();
        e.stopPropagation();
        return false;
    });

    // Prevent unhandled promise rejections from showing error modals
    window.addEventListener('unhandledrejection', function(e) {
        console.error('Unhandled promise rejection:', e.reason);
        // Prevent the default browser error modal
        e.preventDefault();
        e.stopPropagation();
        return false;
    });

    // Override alert function to prevent browser alerts
    window.alert = function(message) {
        console.log('Alert intercepted:', message);
        showErrorModal(message, 'Alert');
    };

    // Override confirm function to prevent browser confirms
    window.confirm = function(message) {
        console.log('Confirm intercepted:', message);
        return true; // Always return true to prevent blocking
    };

    // Prevent any "Invalid request" or similar error messages
    const originalConsoleError = console.error;
    console.error = function(...args) {
        const message = args.join(' ');
        if (message.includes('Invalid request') || message.includes('Unexpected') || message.includes('SyntaxError')) {
            console.log('Error intercepted and prevented:', message);
            return; // Don't show the error
        }
        originalConsoleError.apply(console, args);
    };

    // Override any potential error modals
    const originalOnError = window.onerror;
    window.onerror = function(msg, url, line, col, error) {
        console.log('Global error caught:', msg);
        if (msg && (msg.includes('Invalid request') || msg.includes('Unexpected') || msg.includes('SyntaxError'))) {
            console.log('Error prevented:', msg);
            return true; // Prevent default error handling
        }
        if (originalOnError) {
            return originalOnError.apply(this, arguments);
        }
        return true; // Prevent default error handling
    };

    // Additional form submission prevention
    document.addEventListener('submit', function(e) {
        if (e.target && e.target.id === 'addUserForm') {
            console.log('Form submission caught by document listener');
            e.preventDefault();
            e.stopPropagation();
            
            // Handle the form submission with our AJAX code
            handleFormSubmit(e);
            return false;
        }
    }, true); // Use capture phase

    // Update pagination count in real-time
    function updatePaginationCount() {
        const tbody = document.getElementById('usersTableBody');
        if (!tbody) return;
        
        // Count actual table rows (excluding empty state)
        const tableRows = tbody.querySelectorAll('tr:not([data-empty="true"])');
        const totalRows = tableRows.length;
        
        // Find pagination info element
        const paginationInfo = document.getElementById('paginationInfo');
        if (paginationInfo) {
            // Extract current page info
            const currentText = paginationInfo.textContent;
            const match = currentText.match(/Showing (\d+) to (\d+) of (\d+) entries/);
            
            if (match) {
                const currentStart = parseInt(match[1]);
                const currentEnd = parseInt(match[2]);
                const oldTotal = parseInt(match[3]);
                const newTotal = oldTotal + 1;
                
                // If we're on page 1 and we have exactly 10 rows, keep the display as is
                // The new user was added and the last user was removed to maintain 10 per page
                if (totalRows === 10) {
                paginationInfo.textContent = `Showing ${currentStart} to ${currentEnd} of ${newTotal} entries`;
                } else {
                    // Update the display normally
                    paginationInfo.textContent = `Showing ${currentStart} to ${currentEnd} of ${newTotal} entries`;
                }
            } else {
                // Fallback: just show the total count
                paginationInfo.textContent = `Showing 1 to ${totalRows} of ${totalRows} entries`;
            }
        }
        
        console.log('Pagination count updated:', totalRows, 'total rows');
    }

    // Modal system functions (same as medicine addition)
    function showModal(type, message, title = '', autoClose = false, redirect = null) {
        const modalId = type + 'Modal_' + Date.now();
        const colors = {
            success: { color: '#2563eb', icon: '&#10003;' },
            error: { color: '#dc2626', icon: '&#9888;' },
            warning: { color: '#d97706', icon: '&#9888;' },
            info: { color: '#059669', icon: '&#8505;' }
        };
        
        const config = colors[type] || colors.info;
        
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);';
        
        const buttonsHtml = autoClose ? '' : `
            <div style='display:flex; gap:12px; justify-content:center;'>
                <button id='okBtn' style='background:${config.color}; color:white; padding:8px 16px; border-radius:8px; font-weight:500; border:none; cursor:pointer;'>Okay</button>
                <button id='cancelBtn' style='background:#f3f4f6; color:#374151; padding:8px 16px; border-radius:8px; font-weight:500; border:1px solid #d1d5db; cursor:pointer;'>Cancel</button>
            </div>
        `;
        
        modal.innerHTML = `
            <div style='background:rgba(255,255,255,0.95); color:${config.color}; min-width:300px; max-width:90vw; padding:24px 32px; border-radius:16px; box-shadow:0 4px 32px ${config.color}20; font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid ${config.color}; display:flex; flex-direction:column; gap:16px; pointer-events:auto;'>
                <div style='display:flex; align-items:center; justify-content:center; gap:12px;'>
                    <span style='font-size:2rem;line-height:1;color:${config.color};'>${config.icon}</span>
                    <span style='color:#374151;'>${message}</span>
                </div>
                ${buttonsHtml}
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Auto-close functionality
        if (autoClose) {
            setTimeout(() => {
                modal.style.transition = 'opacity 0.3s';
                modal.style.opacity = '0';
                setTimeout(() => { 
                    if (modal && modal.parentNode) {
                        modal.parentNode.removeChild(modal);
                    }
                    if (redirect) {
                        window.location.href = redirect;
                    }
                }, 300);
            }, 3000);
        } else {
            // Button functionality for non-auto-close modals
            const okBtn = modal.querySelector('#okBtn');
            const cancelBtn = modal.querySelector('#cancelBtn');
            
            if (okBtn) {
                okBtn.onclick = function() {
                    modal.style.transition = 'opacity 0.3s';
                    modal.style.opacity = '0';
                    setTimeout(() => { 
                        if (modal && modal.parentNode) {
                            modal.parentNode.removeChild(modal);
                        }
                        if (redirect) {
                            window.location.href = redirect;
                        }
                    }, 300);
                };
            }
            
            if (cancelBtn) {
                cancelBtn.onclick = function() {
                    modal.style.transition = 'opacity 0.3s';
                    modal.style.opacity = '0';
                    setTimeout(() => { 
                        if (modal && modal.parentNode) {
                            modal.parentNode.removeChild(modal);
                        }
                    }, 300);
                };
            }
        }
        
        return modalId;
    }
    
    // Convenience functions
    function showSuccessModal(message, title = 'Success', autoClose = false, redirect = null) {
        // Prevent any error modals when showing success
        const originalErrorHandler = window.onerror;
        window.onerror = function(msg, url, line, col, error) {
            console.log('Error prevented during success modal:', msg);
            return true; // Prevent default error handling
        };
        
        const modalId = showModal('success', message, title, autoClose, redirect);
        
        // Restore error handler after a short delay
        setTimeout(() => {
            window.onerror = originalErrorHandler;
        }, 100);
        
        return modalId;
    }
    
    function showErrorModal(message, title = 'Error', autoClose = false) {
        return showModal('error', message, title, autoClose);
    }

</script>

<?php
include '../includes/footer.php';
?>