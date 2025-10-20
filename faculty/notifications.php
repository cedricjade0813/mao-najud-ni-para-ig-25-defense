<?php
// Mark notification as read if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'], $_POST['id'])) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    include '../includes/db_connect.php';
    try {
        $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ?');
        $stmt->execute([$_POST['id']]);
        
        // No need to commit - PDO auto-commits by default
        // $db->commit(); // Removed this line
        
        // Clear all session cache completely
        unset($_SESSION['unread_notifs']);
        unset($_SESSION['unread_cache_time']);
        unset($_SESSION['unread_messages']);
        unset($_SESSION['patient_data']);
        unset($_SESSION['notifications_updated']);
        
        // Set a flag to force fresh data fetch
        $_SESSION['force_fresh_data'] = time();
    } catch (PDOException $e) {
        // Handle error silently
    }
    exit;
}

// Mark all notifications as read if requested
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Suppress any output that might interfere with JSON response
    ob_start();
    
    include '../includes/db_connect.php';
    
    // Debug: Log session data
    error_log("🔴 DEBUG: Session data: " . print_r($_SESSION, true));
    error_log("🔴 DEBUG: Faculty ID in session: " . (isset($_SESSION['faculty_id']) ? $_SESSION['faculty_id'] : 'NOT SET'));
    error_log("🔴 DEBUG: Student ID from request: " . (isset($_POST['student_id']) ? $_POST['student_id'] : 'NOT SET'));
    
    try {
        // Debug: Check database connection
        error_log("🔴 DEBUG: Database connection status: " . (isset($db) ? 'Connected' : 'Not connected'));
        if (isset($db)) {
            error_log("🔴 DEBUG: Database connection type: " . get_class($db));
        }
        
        // Get student ID from session or request
        $faculty_id = $_SESSION['faculty_id'] ?? $_POST['faculty_id'] ?? null;
        
        if ($faculty_id && is_numeric($faculty_id)) {
            
            // Debug: Log before update
            error_log("🔴 DEBUG: Mark all as read - Faculty ID: $faculty_id");
            
            // Test database connection with a simple query
            try {
                $test_query = $db->prepare('SELECT 1');
                $test_query->execute();
                error_log("🔴 DEBUG: Database test query successful");
            } catch (Exception $e) {
                error_log("🔴 DEBUG: Database test query failed: " . $e->getMessage());
            }
            
            // Get count before update
            $count_before = $db->prepare('SELECT COUNT(*) FROM notifications WHERE faculty_id = ? AND is_read = 0');
            $count_before->execute([$faculty_id]);
            $unread_before = $count_before->fetchColumn();
            error_log("🔴 DEBUG: Unread count before update: $unread_before");
            
            // Mark all notifications as read for this student
            $stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE faculty_id = ? AND is_read = 0');
            $stmt->execute([$faculty_id]);
            $affected_rows = $stmt->rowCount();
            error_log("🔴 DEBUG: Affected rows: $affected_rows");
            
            // No need to commit - PDO auto-commits by default
            // $db->commit(); // Removed this line
            
            // Get count after update
            $count_after = $db->prepare('SELECT COUNT(*) FROM notifications WHERE faculty_id = ? AND is_read = 0');
            $count_after->execute([$faculty_id]);
            $unread_after = $count_after->fetchColumn();
            error_log("🔴 DEBUG: Unread count after update: $unread_after");
            
            // Clear all session cache completely
            unset($_SESSION['unread_notifs']);
            unset($_SESSION['unread_cache_time']);
            unset($_SESSION['unread_messages']);
            unset($_SESSION['patient_data']);
            unset($_SESSION['notifications_updated']);
            
            // Set a flag to force fresh data fetch
            $_SESSION['force_fresh_data'] = time();
            
            error_log("🔴 DEBUG: Session cache cleared, force_fresh_data set to: " . $_SESSION['force_fresh_data']);
            
            // Clean any output and send JSON response
            ob_clean();
            echo json_encode(['success' => true, 'message' => 'All notifications marked as read', 'affected_rows' => $affected_rows, 'unread_before' => $unread_before, 'unread_after' => $unread_after]);
        } else {
            error_log("🔴 DEBUG: Student ID not found or invalid");
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Student not found or invalid ID']);
        }
    } catch (PDOException $e) {
        error_log("🔴 DEBUG: Database error: " . $e->getMessage());
        error_log("🔴 DEBUG: Database error code: " . $e->getCode());
        error_log("🔴 DEBUG: Database error trace: " . $e->getTraceAsString());
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    } catch (Exception $e) {
        error_log("🔴 DEBUG: General error: " . $e->getMessage());
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'General error: ' . $e->getMessage()]);
    }
    exit;
}

// Refresh session cache endpoint
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refresh_cache'])) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    include '../includes/db_connect.php';
    try {
        if (isset($_SESSION['faculty_id'])) {
            $faculty_id = $_SESSION['faculty_id'];
            
            // Get fresh unread count from database
            $countStmt = $db->prepare('SELECT COUNT(*) FROM notifications WHERE faculty_id = ? AND is_read = 0');
            $countStmt->execute([$faculty_id]);
            $unread_count = $countStmt->fetchColumn();
            
            // Update session cache with fresh data
            $_SESSION['unread_notifs'] = $unread_count;
            $_SESSION['unread_cache_time'] = time();
            
            echo json_encode(['success' => true, 'unread_count' => $unread_count]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Student not found']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

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
</style>

<?php
$faculty_id = $_SESSION['faculty_id'];
$notifications = [];

// Force fresh data fetch by clearing any notification-related cache
// Always clear cache on notifications page to ensure fresh data
unset($_SESSION['unread_notifs']);
unset($_SESSION['unread_cache_time']);
unset($_SESSION['unread_messages']);
unset($_SESSION['force_fresh_data']);

// Check if this is a refresh request
$is_refresh = isset($_GET['refresh']) && $_GET['refresh'] == '1';
if ($is_refresh) {
    // Clear all session data to force fresh fetch
    unset($_SESSION['unread_notifs']);
    unset($_SESSION['unread_cache_time']);
    unset($_SESSION['unread_messages']);
    unset($_SESSION['force_fresh_data']);
    error_log("🔴 DEBUG: Refresh request detected, session cache cleared");
}

error_log("🔴 DEBUG: Loading notifications page for faculty: $faculty_id");
error_log("🔴 DEBUG: Is refresh request: " . ($is_refresh ? 'yes' : 'no'));

try {
    // Create a fresh database connection to avoid any connection caching
    $fresh_db = new PDO('mysql:host=localhost;dbname=clinic_management_system', 'root', '');
    $fresh_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $fresh_db->prepare('SELECT id, message, is_read, created_at FROM notifications WHERE faculty_id = ? ORDER BY created_at DESC');
    $stmt->execute([$faculty_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $unread_count = count(array_filter($notifications, function($n) { return !$n['is_read']; }));
    $read_count = count(array_filter($notifications, function($n) { return $n['is_read']; }));
    
    error_log("🔴 DEBUG: Notifications loaded - Total: " . count($notifications) . ", Unread: $unread_count, Read: $read_count");
    
    // Close the fresh connection
    $fresh_db = null;
} catch (PDOException $e) {
    error_log("🔴 DEBUG: Notifications page database error: " . $e->getMessage());
    $notifications = [];
}
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px] scrollbar-hide">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Notifications</h1>
        <p class="text-gray-600">Stay updated with your latest activities</p>
    </div>

    <!-- Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="text-sm font-medium text-gray-600 mb-2">Total Notifications</div>
            <div class="text-3xl font-bold text-gray-900"><?= count($notifications) ?></div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="text-sm font-medium text-gray-600 mb-2">Unread</div>
            <div class="text-3xl font-bold text-blue-600"><?= count(array_filter($notifications, function ($n) {
                                                                return !$n['is_read'];
                                                            })) ?></div>
        </div>
        <div class="bg-white border border-gray-200 rounded-lg p-6">
            <div class="text-sm font-medium text-gray-600 mb-2">Read</div>
            <div class="text-3xl font-bold text-green-600"><?= count(array_filter($notifications, function ($n) {
                                                                return $n['is_read'];
                                                            })) ?></div>
        </div>
    </div>

    <!-- Filter/Action Bar -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
            <div class="relative">
                <select id="notificationFilter" class="appearance-none bg-white border border-gray-300 rounded-lg px-4 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" onchange="filterNotifications()">
                    <option value="all">All</option>
                    <option value="unread">Unread</option>
                    <option value="read">Read</option>
                </select>
                <i class="ri-filter-line absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
            </div>
            <span id="notificationCount" class="text-sm text-gray-600"><?= count($notifications) ?> notifications</span>
        </div>
        <button class="text-gray-600 hover:text-gray-900 flex items-center gap-2 text-sm font-medium transition-colors" onclick="markAllAsRead()">
            <i class="ri-check-line"></i>
            <span>Mark all as read</span>
        </button>
    </div>

    <!-- Notifications List -->
    <div class="space-y-4">
            <?php foreach ($notifications as $notif): ?>
            <?php
                $is_appointment = preg_match('/appointment|approved|declined|cancelled|canceled|rescheduled/i', $notif['message']);
                $is_message = preg_match('/message|agenda/i', $notif['message']);
                $redirect_url = '';
            $status_color = 'info';
            $status_icon = 'ri-information-line';
            $priority = 'medium';
            $category = 'System';

                if ($is_appointment) {
                    $redirect_url = 'appointments.php';
                $status_color = 'success';
                $status_icon = 'ri-calendar-check-line';
                $priority = 'high';
                $category = 'Appointments';
                } elseif ($is_message) {
                    $redirect_url = 'inbox.php';
                $status_color = 'info';
                $status_icon = 'ri-message-3-line';
                $priority = 'medium';
                $category = 'Messages';
            }

            // Determine status colors
            $status_classes = [
                'success' => 'bg-green-100 text-green-800 border-green-200',
                'warning' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                'info' => 'bg-blue-100 text-blue-800 border-blue-200',
                'error' => 'bg-red-100 text-red-800 border-red-200'
            ];

            $priority_classes = [
                'high' => 'bg-pink-100 text-pink-800',
                'medium' => 'bg-orange-100 text-orange-800',
                'low' => 'bg-blue-100 text-blue-800'
            ];

            $status_border = [
                'success' => 'border-l-green-500',
                'warning' => 'border-l-yellow-500',
                'info' => 'border-l-blue-500',
                'error' => 'border-l-red-500'
            ];

            $icon_colors = [
                'success' => 'text-green-600',
                'warning' => 'text-yellow-600',
                'info' => 'text-blue-600',
                'error' => 'text-red-600'
            ];
            ?>
            <div class="notification-card bg-white border border-gray-200 rounded-lg p-6 hover:shadow-md transition-shadow <?= $notif['is_read'] ? 'opacity-75' : '' ?> border-l-4 <?= $status_border[$status_color] ?>" data-read-status="<?= $notif['is_read'] ? 'read' : 'unread' ?>">
                <div class="flex items-start gap-4">
                    <!-- Status Icon -->
                    <div class="w-8 h-8 rounded-full <?= $icon_colors[$status_color] ?> bg-gray-100 flex items-center justify-center flex-shrink-0">
                        <i class="<?= $status_icon ?> text-sm"></i>
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-start justify-between">
                <div class="flex-1">
                    <?php if ($redirect_url): ?>
                                    <a href="<?= $redirect_url ?>?notif_id=<?= $notif['id'] ?>" class="block text-gray-900 font-semibold hover:text-blue-600 transition-colors" onclick="markNotificationRead(<?= $notif['id'] ?>)">
                            <?= $notif['message'] ?>
                        </a>
                    <?php else: ?>
                                    <h3 class="text-gray-900 font-semibold"><?= $notif['message'] ?></h3>
                                <?php endif; ?>

                                <!-- Tags -->
                                <div class="flex flex-wrap gap-2 mt-2">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $status_classes[$status_color] ?>">
                                        <?= ucfirst($status_color) ?>
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= $priority_classes[$priority] ?>">
                                        <?= ucfirst($priority) ?>
                                    </span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        <?= $category ?>
                                    </span>
                                </div>

                                <!-- Description -->
                                <p class="text-gray-600 text-sm mt-2">
                                    <?= $is_appointment ? 'Your appointment status has been updated.' : ($is_message ? 'You have received a new message.' : 'System notification received.') ?>
                                </p>

                                <!-- Timestamp -->
                                <div class="flex items-center gap-1 mt-3 text-xs text-gray-500">
                                    <i class="ri-time-line"></i>
                                    <span><?= date('M j, Y \a\t g:i A', strtotime($notif['created_at'])) ?></span>
                                </div>
                            </div>

                            <!-- Options Menu -->
                            <div class="flex items-center gap-2">
                                <?php if (!$notif['is_read']): ?>
                                    <button class="text-gray-400 hover:text-gray-600 p-1 rounded" onclick="markNotificationRead(<?= $notif['id'] ?>)" title="Mark as read">
                                        <i class="ri-check-line text-sm"></i>
                                    </button>
                    <?php endif; ?>
                                <button class="text-gray-400 hover:text-gray-600 p-1 rounded">
                                    <i class="ri-more-2-line text-sm"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <?php if (empty($notifications)): ?>
            <div class="bg-white border border-gray-200 rounded-lg p-12 text-center">
                <div class="flex flex-col items-center">
                    <i class="ri-notification-off-line text-4xl text-gray-300 mb-4"></i>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">No notifications found</h3>
                    <p class="text-gray-500 text-sm">You're all caught up! New notifications will appear here.</p>
                </div>
            </div>
            <?php endif; ?>
    </div>
</main>

        <script>
    // Store original notification data
    const allNotifications = <?= json_encode($notifications) ?>;
    let currentFilter = 'all';

    function filterNotifications() {
        const filter = document.getElementById('notificationFilter').value;
        const notificationCards = document.querySelectorAll('.notification-card');
        const notificationCount = document.getElementById('notificationCount');
        let visibleCount = 0;

        notificationCards.forEach(card => {
            const readStatus = card.getAttribute('data-read-status');
            let shouldShow = false;

            switch (filter) {
                case 'all':
                    shouldShow = true;
                    break;
                case 'unread':
                    shouldShow = readStatus === 'unread';
                    break;
                case 'read':
                    shouldShow = readStatus === 'read';
                    break;
            }

            if (shouldShow) {
                card.style.display = 'block';
                card.style.animation = 'fadeIn 0.3s ease-in-out';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        // Update notification count
        notificationCount.textContent = `${visibleCount} notifications`;

        // Update summary cards
        updateSummaryCards(filter);
    }

     function updateSummaryCards(filter) {
         const totalElement = document.querySelector('.grid .bg-white:nth-child(1) .text-3xl');
         const unreadElement = document.querySelector('.grid .bg-white:nth-child(2) .text-3xl');
         const readElement = document.querySelector('.grid .bg-white:nth-child(3) .text-3xl');

         if (totalElement && unreadElement && readElement) {
             const total = allNotifications.length;
             const unread = allNotifications.filter(n => !n.is_read).length;
             const read = allNotifications.filter(n => n.is_read).length;

             totalElement.textContent = total;
             unreadElement.textContent = unread;
             readElement.textContent = read;
         }

         // Update header count whenever summary cards update
         updateHeaderNotificationCount();
     }

     function updateHeaderNotificationCount() {
         // Get current unread count from the data
         const unreadCount = allNotifications.filter(n => !n.is_read).length;
         
         // Find the notification link in sidebar
         const notifLink = document.querySelector('a[href="notifications.php"]');
         if (notifLink) {
             const iconContainer = notifLink.querySelector('.w-8.h-8');
             if (iconContainer) {
                 // Remove existing badge
                 const existingBadge = iconContainer.querySelector('.bg-red-500');
                 if (existingBadge) {
                     existingBadge.remove();
                 }
                 
                 // Add new badge if there are unread notifications
                 if (unreadCount > 0) {
                     const badge = document.createElement('span');
                     badge.className = 'absolute -top-1 -right-1 w-4 h-4 bg-red-500 text-white text-xs flex items-center justify-center rounded-full';
                     badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                     iconContainer.appendChild(badge);
                 }
             }
         }
     }

     // Initialize header count on page load
     function initializeHeaderCount() {
         updateHeaderNotificationCount();
     }

     // Refresh session cache to ensure header shows correct count
     function refreshSessionCache() {
         fetch('notifications.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/x-www-form-urlencoded'
             },
             body: 'refresh_cache=1'
         }).then(response => response.json())
         .then(data => {
             if (data.success) {
                 console.log('Session cache refreshed, unread count:', data.unread_count);
                 // Update header count immediately
                 updateHeaderNotificationCount();
             }
         }).catch(error => {
             console.error('Error refreshing session cache:', error);
         });
     }

     // Force refresh the entire page to ensure header shows correct count
     function forcePageRefresh() {
         // Add a timestamp to force fresh data
         const url = new URL(window.location);
         url.searchParams.set('t', Date.now());
         window.location.href = url.toString();
     }

        function markNotificationRead(id) {
         // Update UI immediately for real-time experience
         const notificationCards = document.querySelectorAll('.notification-card');
         notificationCards.forEach(card => {
             const markReadBtn = card.querySelector('button[onclick*="' + id + '"]');
             if (markReadBtn) {
                 // Update the card's read status
                 card.setAttribute('data-read-status', 'read');
                 card.classList.add('opacity-75');

                 // Hide the mark as read button
                 markReadBtn.style.display = 'none';

                 // Update the global notifications data immediately
                 allNotifications.forEach(notif => {
                     if (notif.id == id) {
                         notif.is_read = 1;
                     }
                 });

                 // Update summary cards
                 updateSummaryCards(currentFilter);

                 // Update header notification count immediately
                 updateHeaderNotificationCount();

                 // Update notification count if filtering
                 filterNotifications();
             }
         });

         // Send request to server in background
            fetch('notifications.php', {
                method: 'POST',
             headers: {
                 'Content-Type': 'application/x-www-form-urlencoded'
             },
                body: 'mark_read=1&id=' + encodeURIComponent(id)
         }).then(() => {
             // Refresh session cache after marking as read
             refreshSessionCache();
         }).catch(error => {
             console.error('Error updating notification:', error);
         });
     }

     function markAllAsRead() {
         // Get all unread notification IDs
         const unreadNotifications = allNotifications.filter(n => !n.is_read);

         if (unreadNotifications.length === 0) {
             return; // No unread notifications to mark
         }

         // Update UI immediately for real-time experience
         // Update all notification cards to show as read
         const notificationCards = document.querySelectorAll('.notification-card');
         notificationCards.forEach(card => {
             const readStatus = card.getAttribute('data-read-status');
             if (readStatus === 'unread') {
                 // Update the card's read status
                 card.setAttribute('data-read-status', 'read');
                 card.classList.add('opacity-75');
                 
                 // Hide the mark as read button
                 const markReadBtn = card.querySelector('button[onclick*="markNotificationRead"]');
                 if (markReadBtn) {
                     markReadBtn.style.display = 'none';
                 }
             }
         });

         // Update the global notifications data immediately
         allNotifications.forEach(notif => {
             if (!notif.is_read) {
                 notif.is_read = 1;
             }
         });

         // Update summary cards with new counts immediately
         const totalElement = document.querySelector('.grid .bg-white:nth-child(1) .text-3xl');
         const unreadElement = document.querySelector('.grid .bg-white:nth-child(2) .text-3xl');
         const readElement = document.querySelector('.grid .bg-white:nth-child(3) .text-3xl');
         
         if (totalElement && unreadElement && readElement) {
             const total = allNotifications.length;
             const unread = 0; // All are now read
             const read = total; // All are now read
             
             totalElement.textContent = total;
             unreadElement.textContent = unread;
             readElement.textContent = read;
         }

         // Update header notification count immediately
         updateHeaderNotificationCount();

         // Update notification count in filter bar
         const notificationCount = document.getElementById('notificationCount');
         if (notificationCount) {
             const visibleCards = document.querySelectorAll('.notification-card:not([style*="display: none"])');
             notificationCount.textContent = `${visibleCards.length} notifications`;
         }

         // Update notification count if filtering
         filterNotifications();

         // Send single request to mark all as read
         console.log('🔴 DEBUG: Starting mark all as read process');
         console.log('🔴 DEBUG: Current allNotifications:', allNotifications);
         console.log('🔴 DEBUG: Unread count before:', allNotifications.filter(n => !n.is_read).length);
         
         // Get student ID from the page context
         const facultyId = <?php echo json_encode($_SESSION['faculty_id'] ?? null); ?>;
         console.log('🔴 DEBUG: Faculty ID from page:', facultyId);
         
         fetch('notifications.php', {
             method: 'POST',
             headers: {
                 'Content-Type': 'application/x-www-form-urlencoded'
             },
             body: 'mark_all_read=1&faculty_id=' + encodeURIComponent(facultyId)
         }).then(response => {
             console.log('🔴 DEBUG: Server response status:', response.status);
             return response.text().then(text => {
                 console.log('🔴 DEBUG: Raw server response:', text);
                 try {
                     return JSON.parse(text);
                 } catch (e) {
                     console.error('🔴 DEBUG: JSON parse error:', e);
                     console.error('🔴 DEBUG: Raw response:', text);
                     return { success: false, message: 'Invalid JSON response' };
                 }
             });
         })
         .then(data => {
             console.log('🔴 DEBUG: Server response data:', data);
             if (data.success) {
                 console.log('🔴 DEBUG: All notifications marked as read successfully');
                 console.log('🔴 DEBUG: Updated allNotifications:', allNotifications);
                 console.log('🔴 DEBUG: Unread count after:', allNotifications.filter(n => !n.is_read).length);
                 
                 // Update header count immediately
                 updateHeaderNotificationCount();
                 
                 // Force page refresh with cache-busting parameter
                 setTimeout(() => {
                     console.log('🔴 DEBUG: About to refresh page with cache-busting');
                     const url = new URL(window.location);
                     url.searchParams.set('t', Date.now());
                     url.searchParams.set('refresh', '1');
                     console.log('🔴 DEBUG: Redirecting to:', url.toString());
                     window.location.href = url.toString();
                 }, 2000); // Wait 2 seconds to show the success state
             } else {
                 console.error('🔴 DEBUG: Error marking all as read:', data.message);
             }
         })
         .catch(error => {
             console.error('🔴 DEBUG: Error updating notifications:', error);
         });
     }

    // Add CSS for fade-in animation
    const style = document.createElement('style');
    style.textContent = `
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-10px); }
        to { opacity: 1; transform: translateY(0); }
    }
`;
    document.head.appendChild(style);

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial filter
        currentFilter = document.getElementById('notificationFilter').value;
        
        // Initialize header count with real-time data
        initializeHeaderCount();
    });
    
    // Global function to update all header counts (can be called from other pages)
    window.updateAllHeaderCounts = function() {
        // This function can be called from inbox.php to sync counts
        if (typeof updateHeaderNotificationCount !== 'undefined') {
            updateHeaderNotificationCount();
        }
    };
    </script>

<?php
include '../includep/footer.php';
?>