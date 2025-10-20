<?php
// AJAX endpoint for pagination - must be at the very beginning
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax']) && $_GET['ajax'] === 'logs_pagination') {
    // Disable error reporting to prevent HTML output
    error_reporting(0);
    ini_set('display_errors', 0);

    try {
        include '../includes/db_connect.php';

        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $records_per_page = 10;
        $offset = ($page - 1) * $records_per_page;

        // Get filter parameters
        $userFilter = isset($_GET['user']) ? $_GET['user'] : 'all';
        $levelFilter = isset($_GET['level']) ? $_GET['level'] : 'all';
        $fromDateFilter = isset($_GET['from_date']) ? $_GET['from_date'] : '';
        $toDateFilter = isset($_GET['to_date']) ? $_GET['to_date'] : '';
        $searchFilter = isset($_GET['search']) ? $_GET['search'] : '';

        // Build WHERE clause for filters
        $whereConditions = [];
        $params = [];

        if ($userFilter !== 'all') {
            $whereConditions[] = "user_email = ?";
            $params[] = $userFilter;
        }

        if ($levelFilter !== 'all') {
            $whereConditions[] = "level = ?";
            $params[] = $levelFilter;
        }

        if ($fromDateFilter) {
            $whereConditions[] = "DATE(timestamp) >= ?";
            $params[] = $fromDateFilter;
        }

        if ($toDateFilter) {
            $whereConditions[] = "DATE(timestamp) <= ?";
            $params[] = $toDateFilter;
        }

        if ($searchFilter) {
            $whereConditions[] = "(action LIKE ? OR user_email LIKE ? OR message LIKE ?)";
            $params[] = '%' . $searchFilter . '%';
            $params[] = '%' . $searchFilter . '%';
            $params[] = '%' . $searchFilter . '%';
        }

        $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

        // Get total count for pagination
        $countQuery = "SELECT COUNT(*) FROM logs $whereClause";
        $countStmt = $db->prepare($countQuery);
        $countStmt->execute($params);
        $totalLogs = $countStmt->fetchColumn();
        $totalPages = ceil($totalLogs / $records_per_page);

        // Get logs for current page
        $logsQuery = "SELECT * FROM logs $whereClause ORDER BY timestamp DESC LIMIT $records_per_page OFFSET $offset";
        $logsStmt = $db->prepare($logsQuery);
        $logsStmt->execute($params);
        $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Get metrics for dashboard cards
        $metricsQuery = "SELECT 
            COUNT(*) as total_logs,
            COUNT(DISTINCT user_email) as active_users,
            COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as recent_activity,
            (SELECT user_email FROM logs WHERE user_email IS NOT NULL GROUP BY user_email ORDER BY COUNT(*) DESC LIMIT 1) as most_active_user
            FROM logs $whereClause";
        $metricsStmt = $db->prepare($metricsQuery);
        $metricsStmt->execute($params);
        $metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC);

        // Build user map: email => full name (username)
        $userMap = [];
        $userNameMap = []; // email => actual name (name field)
        try {
            $userRows = $db->query('SELECT email, username, name FROM users')->fetchAll(PDO::FETCH_ASSOC);
            foreach ($userRows as $u) {
                $userMap[$u['email']] = $u['username'];
                // Use name field, fallback to username if not available
                $userNameMap[$u['email']] = !empty($u['name']) ? $u['name'] : $u['username'];
            }
        } catch (Exception $e) {
        }

        // Calculate pagination info
        $start_record = $offset + 1;
        $end_record = min($offset + $records_per_page, $totalLogs);

        // Set proper content type
        header('Content-Type: application/json');

        echo json_encode([
            'success' => true,
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_records' => $totalLogs,
                'per_page' => $records_per_page,
                'start_record' => $start_record,
                'end_record' => $end_record
            ],
            'metrics' => $metrics,
            'user_map' => $userMap,
            'user_name_map' => $userNameMap
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

include '../includes/db_connect.php';
include '../includea/header.php';

// Pagination settings
$perPage = 10;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $perPage;

// Get filter parameters
$userFilter = isset($_GET['user']) ? $_GET['user'] : 'all';
$levelFilter = isset($_GET['level']) ? $_GET['level'] : 'all';
$fromDateFilter = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$toDateFilter = isset($_GET['to_date']) ? $_GET['to_date'] : '';
$searchFilter = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch logs from database
try {
    // Build WHERE clause for filters
    $whereConditions = [];
    $params = [];
    
    if ($userFilter !== 'all') {
        $whereConditions[] = "user_email = ?";
        $params[] = $userFilter;
    }
    
    if ($levelFilter !== 'all') {
        $whereConditions[] = "level = ?";
        $params[] = $levelFilter;
    }
    
    if ($fromDateFilter) {
        $whereConditions[] = "DATE(timestamp) >= ?";
        $params[] = $fromDateFilter;
    }
    
    if ($toDateFilter) {
        $whereConditions[] = "DATE(timestamp) <= ?";
        $params[] = $toDateFilter;
    }
    
    if ($searchFilter) {
        $whereConditions[] = "(action LIKE ? OR user_email LIKE ? OR message LIKE ?)";
        $params[] = '%' . $searchFilter . '%';
        $params[] = '%' . $searchFilter . '%';
        $params[] = '%' . $searchFilter . '%';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) FROM logs $whereClause";
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalLogs = $countStmt->fetchColumn();
    $totalPages = ceil($totalLogs / $perPage);
    
    // Get logs for current page
    $logsQuery = "SELECT * FROM logs $whereClause ORDER BY timestamp DESC LIMIT $perPage OFFSET $offset";
    $logsStmt = $db->prepare($logsQuery);
    $logsStmt->execute($params);
    $logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get metrics for dashboard cards
    $metricsQuery = "SELECT 
        COUNT(*) as total_logs,
        COUNT(DISTINCT user_email) as active_users,
        COUNT(CASE WHEN timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as recent_activity,
        (SELECT user_email FROM logs WHERE user_email IS NOT NULL GROUP BY user_email ORDER BY COUNT(*) DESC LIMIT 1) as most_active_user
        FROM logs $whereClause";
    $metricsStmt = $db->prepare($metricsQuery);
    $metricsStmt->execute($params);
    $metrics = $metricsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Build user map: email => full name (username)
    $userMap = [];
    $userNameMap = []; // email => actual name (name field)
    try {
        $userRows = $db->query('SELECT email, username, name FROM users')->fetchAll(PDO::FETCH_ASSOC);
        foreach ($userRows as $u) {
            $userMap[$u['email']] = $u['username'];
            // Use name field, fallback to username if not available
            $userNameMap[$u['email']] = !empty($u['name']) ? $u['name'] : $u['username'];
        }
    } catch (Exception $e) {
    }
} catch (PDOException $e) {
    $logs = [];
    $totalLogs = 0;
    $totalPages = 0;
    $userMap = [];
    $metrics = ['total_logs' => 0, 'active_users' => 0, 'errors' => 0, 'warnings' => 0];
}
?>
<style>
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

/* Enhanced shadow system */
.shadow-soft {
    box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
}

.table-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    border: 1px solid rgba(226, 232, 240, 0.8);
    backdrop-filter: blur(10px);
}
</style>
<main class="flex-1 overflow-y-auto main-content p-6 ml-16 md:ml-64 mt-[56px]">
    <!-- Application Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">System Logs Dashboard</h1>
        <p class="text-gray-600">Monitor and analyze system activity logs</p>
    </div>

    <!-- Metric Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Logs Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Total Logs</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo number_format($metrics['total_logs']); ?></p>
                    <p class="text-sm text-gray-500 mt-1">Filtered results</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                    <i class="ri-database-2-line text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Active Users Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Active Users</p>
                    <p class="text-3xl font-bold text-gray-900"><?php echo number_format($metrics['active_users']); ?></p>
                    <p class="text-sm text-gray-500 mt-1">Unique users</p>
                </div>
                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                    <i class="ri-user-line text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Recent Activity Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 mb-1">Recent Activity</p>
                    <p class="text-3xl font-bold text-purple-600"><?php echo number_format($metrics['recent_activity']); ?></p>
                    <p class="text-sm text-gray-500 mt-1">Last 24 hours</p>
                </div>
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                    <i class="ri-time-line text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Most Active User Card -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
            <div class="flex items-center justify-between">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-600 mb-1">Most Active User</p>
                    <p class="text-sm font-bold text-indigo-600 truncate" title="<?php echo isset($userNameMap[$metrics['most_active_user']]) ? htmlspecialchars($userNameMap[$metrics['most_active_user']]) : 'N/A'; ?>">
                        <?php echo isset($userNameMap[$metrics['most_active_user']]) ? htmlspecialchars($userNameMap[$metrics['most_active_user']]) : 'N/A'; ?>
                    </p>
                    <p class="text-sm text-gray-500 mt-1">Top contributor</p>
                </div>
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center ml-3 flex-shrink-0">
                    <i class="ri-user-star-line text-2xl text-indigo-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters Section -->
    <div class="bg-white rounded-lg border border-gray-200 p-6 mb-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Filters</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- User Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">User</label>
                <select id="userFilter" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-9">
                    <option value="all">All Users</option>
                    <?php
                    try {
                        $userRows = $db->query('SELECT email, username FROM users ORDER BY username ASC')->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($userRows as $u) {
                            $email = $u['email'];
                            $username = $u['username'];
                            $selected = ($userFilter === $email) ? 'selected' : '';
                            echo '<option value="' . htmlspecialchars($email) . '" ' . $selected . '>' . htmlspecialchars($username) . '</option>';
                        }
                    } catch (Exception $e) {
                    }
                    ?>
                </select>
            </div>

            <!-- From Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">From Date</label>
                <input type="date" id="fromDateFilter" value="<?php echo htmlspecialchars($fromDateFilter); ?>" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-9" />
            </div>

            <!-- To Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">To Date</label>
                <input type="date" id="toDateFilter" value="<?php echo htmlspecialchars($toDateFilter); ?>" 
                       class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 h-9" />
            </div>

            <!-- Export Button -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">&nbsp;</label>
                <button id="exportLogsBtn" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg transition-colors duration-200 flex items-center justify-center gap-2 h-9">
                    <i class="ri-download-2-line"></i>
                    Export Logs
                </button>
            </div>
        </div>
    </div>
        <div class="bg-white rounded-lg shadow-soft table-container">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-800">System Logs</h3>
                <!-- Search Bar -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="ri-search-line text-gray-400"></i>
                    </div>
                    <input type="text" id="logsSearch" placeholder="Search logs by timestamp, user, or name..."
                        class="block w-64 pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full table-fixed divide-y divide-gray-200" id="systemLogsTable">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="w-40 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Timestamp
                            </th>
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                User
                            </th>
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Name
                            </th>
                            <th class="w-32 px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($logs)): ?>
                        <?php foreach ($logs as $log): ?>
                        <tr class="table-row"
                            data-user="<?php echo htmlspecialchars(isset($userMap[$log['user_email']]) ? $userMap[$log['user_email']] : ($log['user_email'] ? $log['user_email'] : 'System')); ?>"
                            data-action="<?php echo htmlspecialchars($log['action']); ?>">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="<?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?>">
                                <?php echo date('Y-m-d H:i:s', strtotime($log['timestamp'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars(isset($userMap[$log['user_email']]) ? $userMap[$log['user_email']] : ($log['user_email'] ? $log['user_email'] : 'System')); ?>">
                                <?php
                                    $user = $log['user_email'];
                                    echo htmlspecialchars(isset($userMap[$user]) ? $userMap[$user] : ($user ? $user : 'System'));
                                ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 truncate" title="<?php echo htmlspecialchars(isset($userNameMap[$log['user_email']]) ? $userNameMap[$log['user_email']] : ($log['user_email'] ? $log['user_email'] : 'System')); ?>">
                                <?php
                                    $user = $log['user_email'];
                                    echo htmlspecialchars(isset($userNameMap[$user]) ? $userNameMap[$user] : ($user ? $user : 'System'));
                                ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 truncate" title="<?php echo htmlspecialchars($log['action']); ?>">
                                <?php echo htmlspecialchars($log['action']); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <i class="ri-file-list-line text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-gray-500 text-lg font-medium">No logs found</p>
                                    <p class="text-gray-400 text-sm">Try adjusting your filters to see more results</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            <div class="flex justify-between items-center">
                <!-- Records Information -->
                <div class="text-sm text-gray-500">
                    <?php 
                    $start = $offset + 1;
                    $end = min($offset + $perPage, $totalLogs);
                    ?>
                    Showing <?php echo $start; ?> to <?php echo $end; ?> of <?php echo $totalLogs; ?> entries
                </div>

                <!-- Pagination Navigation -->
                <?php if ($totalPages > 1): ?>
                    <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">
                        <?php
                        // Build query string for pagination links
                        $queryParams = [];
                        if ($userFilter !== 'all') $queryParams['user'] = $userFilter;
                        if ($levelFilter !== 'all') $queryParams['level'] = $levelFilter;
                        if ($fromDateFilter) $queryParams['from_date'] = $fromDateFilter;
                        if ($toDateFilter) $queryParams['to_date'] = $toDateFilter;
                        if ($searchFilter) $queryParams['search'] = $searchFilter;
                        
                            function buildPaginationLink($page, $queryParams)
                            {
                            $queryParams['page'] = $page;
                            return '?' . http_build_query($queryParams);
                        }
                        ?>
                        
                        <!-- Previous Button -->
                        <?php if ($currentPage > 1): ?>
                                 <button onclick="loadLogsPage(<?php echo $currentPage - 1; ?>)" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m15 18-6-6 6-6"></path>
                                </svg>
                                <span class="sr-only">Previous</span>
                                 </button>
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
                        $start_page = max(1, $currentPage - 2);
                        $end_page = min($totalPages, $currentPage + 2);
                        
                        // Show first page if not in range
                        if ($start_page > 1): ?>
                                 <button onclick="loadLogsPage(1)" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">1</button>
                            <?php if ($start_page > 2): ?>
                                <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                            <?php if ($i == $currentPage): ?>
                                <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-200 text-gray-800 border border-gray-200 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-300" aria-current="page"><?php echo $i; ?></button>
                            <?php else: ?>
                                     <button onclick="loadLogsPage(<?php echo $i; ?>)" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></button>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <!-- Show last page if not in range -->
                        <?php if ($end_page < $totalPages): ?>
                            <?php if ($end_page < $totalPages - 1): ?>
                                <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>
                            <?php endif; ?>
                                 <button onclick="loadLogsPage(<?php echo $totalPages; ?>)" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100"><?php echo $totalPages; ?></button>
                        <?php endif; ?>

                        <!-- Next Button -->
                        <?php if ($currentPage < $totalPages): ?>
                                 <button onclick="loadLogsPage(<?php echo $currentPage + 1; ?>)" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">
                                <span class="sr-only">Next</span>
                                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m9 18 6-6-6-6"></path>
                                </svg>
                                 </button>
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
</main>
<script>
    // AJAX Pagination for System Logs
    let currentLogsPage = <?php echo $currentPage; ?>;
    const logsPerPage = 10;

    // Load logs page via AJAX
    function loadLogsPage(page) {
        currentLogsPage = page;

        // Get current filter values
        const searchTerm = document.getElementById('logsSearch') ? document.getElementById('logsSearch').value.trim() : '';
        const userFilter = document.getElementById('userFilter') ? document.getElementById('userFilter').value : 'all';
        const fromDateFilter = document.getElementById('fromDateFilter') ? document.getElementById('fromDateFilter').value : '';
        const toDateFilter = document.getElementById('toDateFilter') ? document.getElementById('toDateFilter').value : '';

        // Build query parameters
        const params = new URLSearchParams({
            ajax: 'logs_pagination',
            page: page,
            user: userFilter,
            from_date: fromDateFilter,
            to_date: toDateFilter,
            search: searchTerm
        });

        fetch(`?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    updateLogsTable(data.logs, data.user_map, data.user_name_map);
                    updateLogsPagination(data.pagination);
                    updateMetrics(data.metrics, data.user_name_map);
                } else {
                    console.error('Server error:', data.error || 'Unknown error');
                    alert('Error loading logs: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error loading logs:', error);
                alert('Error loading logs. Please refresh the page and try again.');
            });
    }

    // Update logs table
    function updateLogsTable(logs, userMap, userNameMap) {
        const tbody = document.getElementById('systemLogsTable').querySelector('tbody');
        if (!tbody) return;

        if (logs.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="4" class="px-6 py-12 text-center">
                    <div class="flex flex-col items-center">
                        <i class="ri-file-list-line text-4xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg font-medium">No logs found</p>
                        <p class="text-gray-400 text-sm">Try adjusting your search terms or filters</p>
                    </div>
                </td>
            </tr>
        `;
            return;
        }

        let html = '';
        logs.forEach(log => {
            const user = log.user_email;
            const username = userMap[user] || (user ? user : 'System');
            const userDisplayName = userNameMap[user] || (user ? user : 'System');

            html += `
            <tr class="table-row"
                data-user="${username}"
                data-action="${log.action}">
                <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${new Date(log.timestamp).toLocaleString()}">
                    ${new Date(log.timestamp).toLocaleString()}
                </td>
                <td class="px-6 py-4 text-sm font-medium text-gray-900 truncate" title="${username}">
                    ${username}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${userDisplayName}">
                    ${userDisplayName}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 truncate" title="${log.action}">
                    ${log.action}
                </td>
            </tr>
        `;
        });
        tbody.innerHTML = html;
    }

    // Update pagination
    function updateLogsPagination(pagination) {
        const paginationContainer = document.querySelector('.px-6.py-4.border-t.border-gray-200.bg-gray-50 nav');
        const entriesInfo = document.querySelector('.px-6.py-4.border-t.border-gray-200.bg-gray-50 .text-sm.text-gray-500');

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
                    onclick="loadLogsPage(${pagination.current_page - 1})" aria-label="Previous">
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
                paginationHtml += `<button onclick="loadLogsPage(1)" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">1</button>`;
                if (startPage > 2) {
                    paginationHtml += `<span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>`;
                }
            }

            for (let i = startPage; i <= endPage; i++) {
                if (i === pagination.current_page) {
                    paginationHtml += `<button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-200 text-gray-800 border border-gray-200 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-300" aria-current="page">${i}</button>`;
                } else {
                    paginationHtml += `<button onclick="loadLogsPage(${i})" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">${i}</button>`;
                }
            }

            if (endPage < pagination.total_pages) {
                if (endPage < pagination.total_pages - 1) {
                    paginationHtml += `<span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-3 text-sm">...</span>`;
                }
                paginationHtml += `<button onclick="loadLogsPage(${pagination.total_pages})" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-3 text-sm first:rounded-s-lg last:rounded-e-lg focus:outline-hidden focus:bg-gray-100">${pagination.total_pages}</button>`;
            }

            // Next button
            paginationHtml += `
            <button type="button" ${pagination.current_page === pagination.total_pages ? 'disabled' : ''} 
                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" 
                    onclick="loadLogsPage(${pagination.current_page + 1})" aria-label="Next">
                <span class="sr-only">Next</span>
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </button>
        `;
        }

        paginationContainer.innerHTML = paginationHtml;
    }

     // Update metrics
     function updateMetrics(metrics, userNameMap) {
         // Update metric cards - be more specific with selectors
         const metricCards = document.querySelectorAll('.bg-white.rounded-lg.p-6');
         
         // Total Logs Card (first card)
         if (metricCards[0]) {
             const totalLogsElement = metricCards[0].querySelector('.text-3xl.font-bold.text-gray-900');
             if (totalLogsElement) {
                 totalLogsElement.textContent = metrics.total_logs.toLocaleString();
             }
         }

         // Active Users Card (second card)
         if (metricCards[1]) {
             const activeUsersElement = metricCards[1].querySelector('.text-3xl.font-bold.text-gray-900');
             if (activeUsersElement) {
                 activeUsersElement.textContent = metrics.active_users.toLocaleString();
             }
         }

         // Recent Activity Card (third card)
         if (metricCards[2]) {
             const recentActivityElement = metricCards[2].querySelector('.text-3xl.font-bold.text-purple-600');
             if (recentActivityElement) {
                 recentActivityElement.textContent = metrics.recent_activity.toLocaleString();
             }
         }

         // Most Active User Card (fourth card)
         if (metricCards[3]) {
             const mostActiveUserElement = metricCards[3].querySelector('.text-sm.font-bold.text-indigo-600');
             if (mostActiveUserElement && metrics.most_active_user) {
                 const displayName = userNameMap[metrics.most_active_user] || metrics.most_active_user;
                 mostActiveUserElement.textContent = displayName;
                 mostActiveUserElement.setAttribute('title', displayName);
             }
         }
     }

     // Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function() {
    // Export logs as CSV
    document.getElementById('exportLogsBtn').addEventListener('click', function() {
        // Get current filter parameters
        const searchTerm = document.getElementById('logsSearch').value.trim();
        const userFilter = document.getElementById('userFilter').value;
        const fromDateFilter = document.getElementById('fromDateFilter').value;
        const toDateFilter = document.getElementById('toDateFilter').value;
        
        // Create export URL with current filters
        let exportUrl = 'export_logs.php?';
        const exportParams = [];
        if (userFilter !== 'all') exportParams.push('user=' + encodeURIComponent(userFilter));
        if (fromDateFilter) exportParams.push('from_date=' + encodeURIComponent(fromDateFilter));
        if (toDateFilter) exportParams.push('to_date=' + encodeURIComponent(toDateFilter));
        if (searchTerm) exportParams.push('search=' + encodeURIComponent(searchTerm));
        
        if (exportParams.length > 0) {
            exportUrl += exportParams.join('&');
        }
        
        // Create download link
        const link = document.createElement('a');
        link.href = exportUrl;
        link.download = 'system_logs_<?php echo date('Ymd_His'); ?>.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    // Search functionality with debouncing
    let searchTimeout;
    document.getElementById('logsSearch').addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
                 loadLogsPage(1); // Reset to first page when searching
        }, 300); // Wait 300ms after user stops typing
    });

    // Filter functionality
    document.getElementById('userFilter').addEventListener('change', function() {
             loadLogsPage(1); // Reset to first page when filtering
    });
    document.getElementById('fromDateFilter').addEventListener('change', function() {
             loadLogsPage(1); // Reset to first page when filtering
    });
    document.getElementById('toDateFilter').addEventListener('change', function() {
             loadLogsPage(1); // Reset to first page when filtering
         });

         // Don't automatically load - let the server-side content display
         // loadLogsPage(currentLogsPage);
});
</script>
<?php
include '../includea/footer.php';
?>