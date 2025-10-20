<?php
// AJAX handlers for real-time inbox functionality - MUST be at the very top
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_message_read'])) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Suppress any output that might interfere with JSON response
    ob_start();

    include '../includes/db_connect.php';

    try {
        $message_id = $_POST['message_id'] ?? null;
        $student_id = $_SESSION['student_row_id'] ?? null;

        if ($message_id && is_numeric($message_id) && $student_id && is_numeric($student_id)) {
            // Mark specific message as read
            $stmt = $db->prepare('UPDATE messages SET is_read = 1 WHERE id = ? AND recipient_id = ?');
            $stmt->execute([$message_id, $student_id]);

            // Also mark corresponding notifications as read
            // Find notifications that might be related to this message
            $notif_stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0');
            $notif_stmt->execute([$student_id]);

            // Clear session cache for both messages and notifications
            unset($_SESSION['unread_messages']);
            unset($_SESSION['unread_cache_time']);
            unset($_SESSION['unread_notifs']);
            unset($_SESSION['notifications_updated']);
            $_SESSION['force_fresh_data'] = time();

            ob_clean();
            echo json_encode(['success' => true, 'message' => 'Message marked as read']);
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Invalid message ID or student ID']);
        }
    } catch (PDOException $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Mark all messages as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_messages_read'])) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Suppress any output that might interfere with JSON response
    ob_start();

    include '../includes/db_connect.php';

    try {
        $student_id = $_SESSION['student_row_id'] ?? $_POST['student_id'] ?? null;

        if ($student_id && is_numeric($student_id)) {
            // Mark all messages as read for this student
            $stmt = $db->prepare('UPDATE messages SET is_read = 1 WHERE recipient_id = ? AND is_read = 0');
            $stmt->execute([$student_id]);
            $affected_rows = $stmt->rowCount();

            // Also mark all notifications as read for this student
            $notif_stmt = $db->prepare('UPDATE notifications SET is_read = 1 WHERE student_id = ? AND is_read = 0');
            $notif_stmt->execute([$student_id]);
            $affected_notifs = $notif_stmt->rowCount();

            // Clear session cache for both messages and notifications
            unset($_SESSION['unread_messages']);
            unset($_SESSION['unread_cache_time']);
            unset($_SESSION['unread_notifs']);
            unset($_SESSION['notifications_updated']);
            $_SESSION['force_fresh_data'] = time();

            ob_clean();
            echo json_encode(['success' => true, 'message' => 'All messages and notifications marked as read', 'affected_rows' => $affected_rows, 'affected_notifs' => $affected_notifs]);
        } else {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Student not found or invalid ID']);
        }
    } catch (PDOException $e) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Include header after AJAX handlers to prevent HTML output interference
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

    /* Hide star icons - both desktop and mobile */
    .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .ri-star-line {
        display: none !important;
    }

    .bg-white.rounded-lg.shadow-sm.border.border-gray-200 button[onclick*="toggleStar"] {
        display: none !important;
    }

    .bg-white.rounded-lg.shadow-sm.border.border-gray-200 button[onclick*="toggleStarFromHeader"] {
        display: none !important;
    }

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

    /* Mobile responsive styles for inbox */
    @media (max-width: 400px) {

        /* Inbox title and subtitle - matching profile page styling */
        .inbox-title {
            font-size: 18px !important;
            line-height: 1.2 !important;
        }

        .inbox-subtitle {
            font-size: 10px !important;
            line-height: 1.2 !important;
        }

        /* Make the entire inbox card taller and adjust all font sizes - specific to inbox only */
        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 {
            height: auto !important;
            max-height: none !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex.h-\[600px\] {
            height: auto !important;
            max-height: none !important;
            flex-direction: column !important;
        }

        /* Stack left pane to top and right pane to bottom */
        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .w-1\/3 {
            width: 100% !important;
            height: 200px !important;
            border-right: none !important;
            border-bottom: 1px solid #e5e7eb !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex-1 {
            width: 100% !important;
            height: auto !important;
        }

        /* Allow message content to expand properly */
        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex-1 .flex-1 {
            height: auto !important;
            overflow: visible !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex-1 .overflow-y-auto {
            overflow: visible !important;
            height: auto !important;
        }

        /* Left pane - make shorter with bigger fonts (only in inbox card) */
        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .w-1\/3 {
            padding: 6px !important;
            margin-left: 0 !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .w-1\/3 .p-6 {
            padding: 12px !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .w-1\/3 h2 {
            font-size: 14px !important;
            margin-bottom: 4px !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .w-1\/3 p {
            font-size: 10px !important;
            margin-bottom: 4px !important;
        }

        /* Right pane - make shorter with bigger fonts (only in inbox card) */
        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex-1 {
            padding: 6px !important;
            margin-left: 0 !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex-1 .p-6 {
            padding: 12px !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex-1 h2 {
            font-size: 14px !important;
            margin-bottom: 4px !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex-1 p {
            font-size: 10px !important;
            margin-bottom: 4px !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex-1 .text-xl {
            font-size: 12px !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex-1 .text-lg {
            font-size: 10px !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .flex-1 .text-sm {
            font-size: 8px !important;
        }

        /* Position date and star icon in top-right of message cards - simpler approach */
        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .message-item .flex.items-center.space-x-2 {
            justify-content: flex-end !important;
            margin-top: -6px !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .message-item .flex.items-center.space-x-2 .text-xs {
            font-size: 6px !important;
            margin-right: 2px !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .message-item .flex.items-center.space-x-2 button {
            padding: 2px !important;
            margin: 0 !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .message-item .flex.items-center.space-x-2 .text-sm {
            font-size: 8px !important;
        }

        /* Hide star icons completely - both desktop and mobile */
        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .ri-star-line {
            display: none !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 button[onclick*="toggleStar"] {
            display: none !important;
        }

        main .bg-white.rounded-lg.shadow-sm.border.border-gray-200 button[onclick*="toggleStarFromHeader"] {
            display: none !important;
        }


        /* Messages section header */
        h2.text-lg {
            font-size: 14px !important;
        }

        p.text-sm {
            font-size: 10px !important;
        }

        /* Message items - make cards very small */
        .message-item {
            padding: 2px !important;
            margin-bottom: 1px !important;
        }

        .message-item .p-4 {
            padding: 3px !important;
        }

        .message-item h3 {
            font-size: 8px !important;
            margin-bottom: 1px !important;
            line-height: 1.1 !important;
        }

        .message-item p {
            font-size: 6px !important;
            margin-bottom: 1px !important;
            line-height: 1.1 !important;
        }

        .message-item .text-xs {
            font-size: 4px !important;
        }

        .message-item .font-semibold {
            font-size: 7px !important;
        }

        .message-item .space-x-3 {
            gap: 3px !important;
        }

        .message-item .space-x-2 {
            gap: 2px !important;
        }

        .message-item .mb-1 {
            margin-bottom: 1px !important;
        }

        .message-item .w-4 {
            width: 8px !important;
            height: 8px !important;
        }

        .message-item .h-4 {
            height: 8px !important;
            width: 8px !important;
        }

        /* Message content in right pane - make main card very small */
        .message-content h2 {
            font-size: 12px !important;
        }

        .message-content p {
            font-size: 8px !important;
        }

        .message-content .text-sm {
            font-size: 6px !important;
        }

        /* Main message viewing card - make extremely small and shorter (only target the right pane) */
        .w-2\/3 .bg-white.rounded-lg.shadow-sm.border.border-gray-200 {
            padding: 3px !important;
            margin: 2px !important;
            height: 200px !important;
            max-height: 200px !important;
            overflow: hidden !important;
        }

        .w-2\/3 .bg-white.rounded-lg.shadow-sm.border.border-gray-200 h2 {
            font-size: 7px !important;
            margin-bottom: 2px !important;
            line-height: 1.1 !important;
        }

        .w-2\/3 .bg-white.rounded-lg.shadow-sm.border.border-gray-200 p {
            font-size: 4px !important;
            margin-bottom: 1px !important;
            line-height: 1.1 !important;
        }

        .w-2\/3 .bg-white.rounded-lg.shadow-sm.border.border-gray-200 .text-sm {
            font-size: 3px !important;
        }

        /* Message header in main card (only in right pane) */
        .w-2\/3 .flex.items-center.justify-between {
            padding: 2px !important;
            margin-bottom: 1px !important;
        }

        .w-2\/3 .flex.items-center.justify-between h2 {
            font-size: 6px !important;
        }

        .w-2\/3 .flex.items-center.justify-between p {
            font-size: 3px !important;
        }

        /* Message content area (only in right pane) */
        .w-2\/3 .p-6 {
            padding: 3px !important;
            height: 150px !important;
            max-height: 150px !important;
            overflow: hidden !important;
        }

        .w-2\/3 .p-6 h2 {
            font-size: 7px !important;
            margin-bottom: 2px !important;
        }

        .w-2\/3 .p-6 p {
            font-size: 4px !important;
            margin-bottom: 1px !important;
        }

        .w-2\/3 .p-6 .text-sm {
            font-size: 3px !important;
        }

        /* Make icons smaller in main card */
        .w-2\/3 .text-2xl {
            font-size: 8px !important;
        }

        .w-2\/3 .text-xl {
            font-size: 6px !important;
        }

        .w-2\/3 .text-lg {
            font-size: 5px !important;
        }

        /* Empty state - make "No message selected" card much shorter */
        .w-2\/3 .flex.flex-col.items-center.justify-center {
            height: 80px !important;
            max-height: 80px !important;
            padding: 4px !important;
        }

        .w-2\/3 .flex.flex-col.items-center.justify-center h3 {
            font-size: 6px !important;
            margin-bottom: 2px !important;
            line-height: 1.1 !important;
        }

        .w-2\/3 .flex.flex-col.items-center.justify-center p {
            font-size: 3px !important;
            margin-bottom: 1px !important;
            line-height: 1.1 !important;
        }

        .w-2\/3 .flex.flex-col.items-center.justify-center .text-4xl {
            font-size: 10px !important;
        }

        .w-2\/3 .flex.flex-col.items-center.justify-center .text-3xl {
            font-size: 8px !important;
        }

        .w-2\/3 .flex.flex-col.items-center.justify-center .text-2xl {
            font-size: 6px !important;
        }

        .w-2\/3 .flex.flex-col.items-center.justify-center .text-xl {
            font-size: 5px !important;
        }

        .w-2\/3 .flex.flex-col.items-center.justify-center .text-lg {
            font-size: 4px !important;
        }

        /* Target the actual "No message selected" content */
        .w-2\/3 .text-center {
            height: 80px !important;
            max-height: 80px !important;
            padding: 4px !important;
        }

        .w-2\/3 .text-center h3 {
            font-size: 6px !important;
            margin-bottom: 2px !important;
            line-height: 1.1 !important;
        }

        .w-2\/3 .text-center p {
            font-size: 3px !important;
            margin-bottom: 1px !important;
            line-height: 1.1 !important;
        }

        .w-2\/3 .text-center .text-2xl {
            font-size: 6px !important;
        }

        .w-2\/3 .text-center .text-lg {
            font-size: 4px !important;
        }

        .w-2\/3 .text-center .text-sm {
            font-size: 3px !important;
        }

        /* Target the message content area specifically */
        .w-2\/3 .p-6 .text-center {
            height: 60px !important;
            max-height: 60px !important;
            padding: 2px !important;
        }

        /* Make "Select a message to view" text smaller on mobile */
        .w-2\/3 .text-center h3 {
            font-size: 8px !important;
        }

        .w-2\/3 .text-center p {
            font-size: 6px !important;
        }

        .w-2\/3 .text-center .text-2xl {
            font-size: 6px !important;
        }

        .w-2\/3 .text-center .text-lg {
            font-size: 5px !important;
        }

        .w-2\/3 .text-center .text-sm {
            font-size: 4px !important;
        }
        
        /* Make "No message selected" text smaller on mobile - bottom pane */
        .flex-1 .text-center h3 {
            font-size: 8px !important;
        }
        
        .flex-1 .text-center p {
            font-size: 6px !important;
        }
        
        .flex-1 .text-center .text-2xl {
            font-size: 6px !important;
        }
        
        .flex-1 .text-center .text-lg {
            font-size: 5px !important;
        }
        
        .flex-1 .text-center .text-sm {
            font-size: 4px !important;
        }
        
        /* Reduce spacing between "Select a message" and subtitle */
        #messagePlaceholder .text-center h3 {
            margin-bottom: -8px !important;
        }
        
        #messagePlaceholder .text-center p {
            margin-top: -8px !important;
        }
        
        /* Also reduce the icon container margin */
        #messagePlaceholder .text-center .w-16.h-16.bg-gray-100.rounded-full.flex.items-center.justify-center.mx-auto.mb-4 {
            margin-bottom: 4px !important;
        }
        
        /* Target the specific text elements more directly */
        #messagePlaceholder h3 {
            margin-bottom: -8px !important;
        }
        
        #messagePlaceholder p {
            margin-top: -8px !important;
        }
        
        /* Make message content text bigger on mobile - bottom pane */
        .flex-1 .text-gray-800 {
            font-size: 10px !important;
        }
        
        /* Reduce spacing between message subject and sender in header */
        #messageSubject {
            margin-bottom: -8px !important;
        }
        
        #messageSender {
            margin-top: -8px !important;
        }
        
        /* Also target the Tailwind classes directly */
        #messageSubject.text-xl.font-semibold.text-gray-900.mb-1 {
            margin-bottom: -8px !important;
        }
        
        #messageSender.text-sm.text-gray-500 {
            margin-top: -8px !important;
        }
        
        .flex-1 .text-gray-800.leading-relaxed {
            font-size: 10px !important;
            line-height: 1.3 !important;
        }
        
        .flex-1 .text-gray-800 p {
            font-size: 10px !important;
        }
        
        .flex-1 .text-gray-800 div {
            font-size: 10px !important;
        }
        
        /* Make the mail icon bigger on mobile - message content area */
        #messagePlaceholder .ri-mail-line {
            font-size: 16px !important;
        }
        
        #messagePlaceholder .text-2xl {
            font-size: 16px !important;
        }
        
        /* Make the background circle smaller on mobile */
        #messagePlaceholder .w-16.h-16 {
            width: 40px !important;
            height: 40px !important;
        }
        
        #messagePlaceholder .w-16.h-16.bg-gray-100.rounded-full {
            width: 40px !important;
            height: 40px !important;
        }

        /* Make right pane buttons smaller on mobile */
        .flex-1 .flex.items-center.space-x-2 button {
            padding: 4px !important;
            margin: 0 !important;
            width: 24px !important;
            height: 24px !important;
            min-width: 24px !important;
            min-height: 24px !important;
        }

        .flex-1 .flex.items-center.space-x-2 button i {
            font-size: 10px !important;
        }

        .flex-1 .flex.items-center.space-x-2 button .ri-star-line,
        .flex-1 .flex.items-center.space-x-2 button .ri-star-fill {
            font-size: 10px !important;
        }

        /* Make bulk action buttons smaller on mobile */
        #bulkActions button {
            font-size: 8px !important;
            padding: 2px 4px !important;
            min-width: auto !important;
            white-space: nowrap !important;
            overflow: visible !important;
        }

        /* Specific styling for Select All button on mobile */
        #bulkActions button:first-child {
            width: 60px !important;
            min-width: 60px !important;
            margin-right: 8px !important;
        }

        /* Specific styling for Delete button on mobile */
        #bulkActions button:last-child {
            width: 50px !important;
            min-width: 50px !important;
            margin-left: 0 !important;
        }

        #bulkActions .flex.items-center.space-x-2 {
            gap: 8px !important;
        }

        /* Ensure the bulk actions container doesn't overflow */
        #bulkActions {
            flex-wrap: nowrap !important;
            overflow: visible !important;
        }


        /* Modal content */
        .modal-content h2 {
            font-size: 16px !important;
        }

        .modal-content p {
            font-size: 12px !important;
        }

        .modal-content .text-sm {
            font-size: 10px !important;
        }
    }
</style>

<?php
try {
    // Create messages table if not exists
    $db->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        sender_name VARCHAR(255) NOT NULL,
        sender_role VARCHAR(50) NOT NULL,
        recipient_id INT NOT NULL,
        recipient_name VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_recipient (recipient_id),
        INDEX idx_sender (sender_id),
        INDEX idx_created_at (created_at)
    )");

    // Get patient ID from session
    $patient_id = isset($_SESSION['student_row_id']) ? $_SESSION['student_row_id'] : null;

    if ($patient_id) {
        // Fetch messages for this patient
        $stmt = $db->prepare('SELECT * FROM messages WHERE recipient_id = ? ORDER BY created_at DESC');
        $stmt->execute([$patient_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Messages are only marked as read when user actually views them
        // No automatic marking on page load
    } else {
        $messages = [];
    }
} catch (PDOException $e) {
    $messages = [];
    $error_message = "Database connection failed: " . $e->getMessage();
}
?>
<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px] scrollbar-hide">
    <!-- Modern Email Interface Design -->

    <!-- Header Section -->
    <div class="mb-8">
        <div class="flex items-center">
            <!-- Mobile menu button -->
            <button id="mobileMenuBtn" class="md:hidden mr-4 text-gray-600 hover:text-gray-900 rounded-md min-w-[44px] min-h-[44px] flex items-center justify-center cursor-pointer" onclick="toggleMobileMenu()">
                <i class="ri-menu-line text-xl pointer-events-none"></i>
            </button>
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2 inbox-title">Inbox</h1>
                <p class="text-gray-600 inbox-subtitle">Messages from clinic staff</p>
            </div>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
        <?php showErrorModal(htmlspecialchars($error_message), 'Error'); ?>
    <?php endif; ?>

    <!-- Two-Column Layout -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex h-[600px]">
            <!-- Left Pane - Message List -->
            <div class="w-1/3 border-r border-gray-200 bg-gray-50">
                <div class="p-6 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 mb-1">Messages</h2>
                            <p class="text-sm text-gray-500" id="messageCount"><?php echo count($messages); ?> messages</p>
                        </div>
                        <div class="flex items-center space-x-2" id="bulkActions" style="display: none;">
                            <button onclick="selectAllMessages()" class="text-xs px-2 py-1 bg-blue-100 text-blue-800 rounded hover:bg-blue-200 transition-colors whitespace-nowrap w-24">
                                Select All
                            </button>
                            <button onclick="deleteSelectedMessages()" class="text-xs px-2 py-1 bg-red-100 text-red-800 rounded hover:bg-red-200 transition-colors whitespace-nowrap">
                                Delete
                            </button>
                        </div>
                    </div>
                </div>

                <?php if (empty($messages)): ?>
                    <div class="flex flex-col items-center justify-center h-full text-center p-8">
                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                            <i class="ri-mail-line text-2xl text-gray-400"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No messages yet</h3>
                        <p class="text-sm text-gray-500">Messages from clinic staff will appear here</p>
                    </div>
                <?php else: ?>
                    <div class="overflow-y-auto scrollbar-hide h-full pb-20">
                        <?php foreach ($messages as $index => $msg): ?>
                            <div class="message-item <?php echo $index < count($messages) - 1 ? 'border-b border-gray-200' : 'mb-4'; ?> hover:bg-white transition-colors cursor-pointer" onclick="handleMessageItemClick(event, <?php echo $index; ?>)">
                                <div class="p-4">
                                    <div class="flex items-start space-x-3">
                                        <!-- Checkbox -->
                                        <div class="flex-shrink-0 mt-1">
                                            <input type="checkbox" 
                                                   id="checkbox-<?php echo $index; ?>" 
                                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 message-checkbox" 
                                                   data-message-id="<?php echo $msg['id']; ?>"
                                                   data-message-index="<?php echo $index; ?>"
                                                   onchange="handleCheckboxChange()">
                                        </div>

                                        <!-- Message Content -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center justify-between mb-1">
                                                <div class="flex items-center space-x-2">
                                                    <span class="font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($msg['sender_name']); ?></span>
                                                    <?php if (!$msg['is_read']): ?>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Unread</span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex items-center space-x-2">
                                                    <span class="text-xs text-gray-500"><?php echo date('M j', strtotime($msg['created_at'])); ?></span>
                                                    <button class="text-gray-400 hover:text-yellow-500 transition-colors" onclick="toggleStar(<?php echo $index; ?>, event)">
                                                        <i class="ri-star-line text-sm" id="star-<?php echo $index; ?>"></i>
                                                    </button>
                                                </div>
                                            </div>

                                            <h3 class="font-semibold text-gray-900 mb-1 truncate"><?php echo htmlspecialchars($msg['subject']); ?></h3>

                                            <p class="text-sm text-gray-600 truncate">
                                                <?php
                                                $message_preview = strip_tags($msg['message']);
                                                echo htmlspecialchars(substr($message_preview, 0, 60)) . (strlen($message_preview) > 60 ? '...' : '');
                                                ?>
                                            </p>

                                            <div class="flex items-center justify-between mt-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                    <?php echo ucfirst(htmlspecialchars($msg['sender_role'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Right Pane - Message Content -->
            <div class="flex-1 bg-white">
                <div class="h-full flex flex-col">
                    <!-- Message Header -->
                    <div class="p-6 border-b border-gray-200" id="messageHeader">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-4">
                                <!-- Avatar -->
                                <div class="w-10 h-10 bg-gray-200 rounded-full flex items-center justify-center" id="messageAvatar">
                                    <span class="text-sm font-medium text-gray-600">MS</span>
                                </div>

                                <div class="flex-1">
                                    <h2 class="text-xl font-semibold text-gray-900 mb-1" id="messageSubject">Select a message</h2>
                                    <p class="text-sm text-gray-500" id="messageSender">Choose a message from the list</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-2">
                                <button class="p-2 text-gray-400 hover:text-yellow-500 transition-colors" onclick="toggleStarFromHeader()">
                                    <i class="ri-star-line text-lg" id="headerStar"></i>
                                </button>
                            </div>
                        </div>
                    </div>


                    <!-- Message Content -->
                    <div class="flex-1 p-6 overflow-y-auto scrollbar-hide" id="messageContent">
                        <div class="flex items-center justify-center h-full" id="messagePlaceholder">
                            <div class="text-center">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <i class="ri-mail-line text-2xl text-gray-400"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No message selected</h3>
                                <p class="text-sm text-gray-500">Choose a message from the list to view its content</p>
                            </div>
                        </div>

                        <!-- Actual Message Content (hidden by default) -->
                        <div class="hidden" id="messageBody">
                            <div class="flex items-center gap-2 mb-6">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800" id="messageRole"></span>
                                <span class="text-sm text-gray-500">From clinic staff</span>
                            </div>
                            <div class="text-gray-800 leading-relaxed" id="messageText"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</main>


<script>
    // Message data for modal
    const messageData = <?php echo json_encode($messages, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;

    // Track current selected message index
    let currentSelectedIndex = -1;

    // Initialize starred messages from localStorage
    function initializeStarredMessages() {
        const starredMessages = JSON.parse(localStorage.getItem('starredMessages') || '[]');

        // Apply starred states to message list
        starredMessages.forEach(index => {
            if (index < messageData.length) {
                const starIcon = document.getElementById(`star-${index}`);
                if (starIcon) {
                    starIcon.classList.remove('ri-star-line', 'text-gray-400');
                    starIcon.classList.add('ri-star-fill', 'text-yellow-500');
                }
            }
        });
    }

    // Save starred messages to localStorage
    function saveStarredMessages() {
        const starredMessages = [];
        document.querySelectorAll('.ri-star-fill').forEach(starIcon => {
            const id = starIcon.id;
            if (id.startsWith('star-')) {
                const index = parseInt(id.replace('star-', ''));
                starredMessages.push(index);
            }
        });
        localStorage.setItem('starredMessages', JSON.stringify(starredMessages));
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeStarredMessages();
    });

    // Show message in right pane
    function showMessageModal(index) {
        const msg = messageData[index];
        currentSelectedIndex = index;

        // Remove active state from all message items
        document.querySelectorAll('.message-item').forEach(item => {
            item.classList.remove('bg-blue-50', 'border-blue-200');
            item.classList.add('hover:bg-white');
        });

        // Add active state to selected message
        const selectedItem = document.querySelectorAll('.message-item')[index];
        selectedItem.classList.add('bg-blue-50', 'border-blue-200');
        selectedItem.classList.remove('hover:bg-white');

        // Update message header
        document.getElementById('messageSubject').textContent = msg.subject;
        document.getElementById('messageSender').textContent = `${msg.sender_name} • ${new Date(msg.created_at).toLocaleString()}`;

        // Update avatar with sender initials
        const initials = msg.sender_name.split(' ').map(name => name.charAt(0)).join('').toUpperCase();
        document.getElementById('messageAvatar').innerHTML = `<span class="text-sm font-medium text-gray-600">${initials}</span>`;

        // Update message content
        document.getElementById('messageRole').textContent = msg.sender_role.charAt(0).toUpperCase() + msg.sender_role.slice(1);
        document.getElementById('messageText').innerHTML = msg.message.replace(/\n/g, '<br>');

        // Sync header star with message list star
        syncHeaderStar();

        // Show message content and hide placeholder
        document.getElementById('messagePlaceholder').classList.add('hidden');
        document.getElementById('messageBody').classList.remove('hidden');
    }

    // Toggle star functionality
    function toggleStar(index, event) {
        event.stopPropagation(); // Prevent message selection when clicking star

        const starIcon = document.getElementById(`star-${index}`);
        const isStarred = starIcon.classList.contains('ri-star-fill');

        if (isStarred) {
            // Unstar the message
            starIcon.classList.remove('ri-star-fill', 'text-yellow-500');
            starIcon.classList.add('ri-star-line', 'text-gray-400');
        } else {
            // Star the message
            starIcon.classList.remove('ri-star-line', 'text-gray-400');
            starIcon.classList.add('ri-star-fill', 'text-yellow-500');
        }

        // Save starred state to localStorage
        saveStarredMessages();

        // Sync header star if this is the currently selected message
        if (currentSelectedIndex === index) {
            syncHeaderStar();
        }
    }

    // Sync header star with message list star
    function syncHeaderStar() {
        if (currentSelectedIndex >= 0) {
            const listStar = document.getElementById(`star-${currentSelectedIndex}`);
            const headerStar = document.getElementById('headerStar');

            if (listStar.classList.contains('ri-star-fill')) {
                // Message is starred
                headerStar.classList.remove('ri-star-line', 'text-gray-400');
                headerStar.classList.add('ri-star-fill', 'text-yellow-500');
            } else {
                // Message is not starred
                headerStar.classList.remove('ri-star-fill', 'text-yellow-500');
                headerStar.classList.add('ri-star-line', 'text-gray-400');
            }
        }
    }

    // Toggle star from header
    function toggleStarFromHeader() {
        if (currentSelectedIndex >= 0) {
            // Trigger the same toggle as clicking the star in the message list
            const listStar = document.getElementById(`star-${currentSelectedIndex}`);
            const event = new Event('click');
            listStar.parentElement.click();

            // Save starred state to localStorage
            saveStarredMessages();
        }
    }

    // Delete message functionality
    function deleteMessage() {
        if (currentSelectedIndex >= 0) {
            const msg = messageData[currentSelectedIndex];

            // Show custom confirmation modal
            showConfirmModal(
                `Are you sure you want to delete the message "${msg.subject}"?`,
                function() {
                    // Call delete endpoint
                    deleteMessageFromDatabase(msg.id, function(success) {
                        if (success) {
                            // Remove message from DOM
                            const messageItem = document.querySelectorAll('.message-item')[currentSelectedIndex];
                            if (messageItem) {
                                messageItem.remove();
                            }

                            // Remove from localStorage starred messages if it was starred
                            const starredMessages = JSON.parse(localStorage.getItem('starredMessages') || '[]');
                            const updatedStarredMessages = starredMessages.filter(index => index !== currentSelectedIndex);
                            localStorage.setItem('starredMessages', JSON.stringify(updatedStarredMessages));

                            // Reset to placeholder state
                            resetToPlaceholder();

                            // Update message count
                            updateMessageCount();

                            // Show success message
                            showSimpleSuccessMessage('Message deleted successfully');

                            // Refresh the page after a short delay
                            setTimeout(() => {
                                window.location.reload();
                            }, 1500);
                        } else {
                            // Show error message
                            showSimpleErrorMessage('Failed to delete message');
                        }
                    });
                },
                function() {
                    // User cancelled deletion
                }
            );
        }
    }

    // Delete message from database
    function deleteMessageFromDatabase(messageId, callback) {
        const formData = new FormData();
        formData.append('message_id', messageId);

        fetch('delete_message.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    callback(true);
                } else {
                    console.error('Delete error:', data.message);
                    callback(false);
                }
            })
            .catch(error => {
                console.error('Network error:', error);
                callback(false);
            });
    }

    // Reset to placeholder state
    function resetToPlaceholder() {
        document.getElementById('messagePlaceholder').classList.remove('hidden');
        document.getElementById('messageBody').classList.add('hidden');
        document.getElementById('messageSubject').textContent = 'Select a message to view';
        document.getElementById('messageSender').textContent = 'Choose a message from the list to see its content';
        document.getElementById('messageAvatar').innerHTML = '<span class="text-sm font-medium text-gray-600">MS</span>';
        currentSelectedIndex = -1;
    }

    // Update message count
    function updateMessageCount() {
        const messageCount = document.querySelectorAll('.message-item').length;
        // Update the message count in the left pane header
        const countElement = document.getElementById('messageCount');
        if (countElement) {
            countElement.textContent = `${messageCount} message${messageCount !== 1 ? 's' : ''}`;
        }
        
        // Also update the allMessages array length for consistency
        if (typeof allMessages !== 'undefined') {
            allMessages.length = messageCount;
        }
    }

    // Custom confirmation modal function that matches the design
    function showConfirmModal(message, onConfirm, onCancel) {
        const modalId = 'confirmModal_' + Date.now();
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);';

        modal.innerHTML = `
    <div style='background:rgba(255,255,255,0.95); color:#d97706; min-width:300px; max-width:90vw; padding:24px 32px; border-radius:16px; box-shadow:0 4px 32px rgba(217,119,6,0.15); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #d97706; display:flex; flex-direction:column; gap:16px; pointer-events:auto;'>
        <div style='display:flex; align-items:center; justify-content:center; gap:12px;'>
            <span style='font-size:2rem;line-height:1;color:#d97706;'>&#9888;</span>
            <span style='color:#374151;'>${message}</span>
        </div>
        <div style='display:flex; gap:12px; justify-content:center;'>
            <button id='confirmBtn' style='background:#d97706; color:white; padding:8px 16px; border-radius:8px; font-weight:500; border:none; cursor:pointer;'>Confirm</button>
            <button id='cancelBtn' style='background:#f3f4f6; color:#374151; padding:8px 16px; border-radius:8px; font-weight:500; border:1px solid #d1d5db; cursor:pointer;'>Cancel</button>
        </div>
    </div>
`;

        document.body.appendChild(modal);

        const confirmBtn = modal.querySelector('#confirmBtn');
        const cancelBtn = modal.querySelector('#cancelBtn');

        confirmBtn.onclick = function() {
            modal.style.opacity = '0';
            setTimeout(() => {
                if (modal && modal.parentNode) {
                    modal.parentNode.removeChild(modal);
                }
                if (typeof onConfirm === 'function') onConfirm();
            }, 300);
        };

        cancelBtn.onclick = function() {
            modal.style.transition = 'opacity 0.3s';
            modal.style.opacity = '0';
            setTimeout(() => {
                if (modal && modal.parentNode) {
                    modal.parentNode.removeChild(modal);
                }
                if (typeof onCancel === 'function') onCancel();
            }, 300);
        };
    }

    // Simple success message function (no buttons, auto-dismiss)
    function showSimpleSuccessMessage(message) {
        // Remove any existing notification
        const existingToast = document.getElementById('messageToast');
        if (existingToast) {
            existingToast.remove();
        }

        const notification = document.createElement('div');
        notification.id = 'messageToast';
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

    // Simple error message function
    function showSimpleErrorMessage(message) {
        // Remove any existing notification
        const existingToast = document.getElementById('errorToast');
        if (existingToast) {
            existingToast.remove();
        }

        const notification = document.createElement('div');
        notification.id = 'errorToast';
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
    <div style="background:rgba(255,255,255,0.7); color:#dc2626; min-width:220px; max-width:90vw; padding:20px 36px; border-radius:16px; box-shadow:0 4px 32px rgba(220,38,38,0.10); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #dc2626; display:flex; align-items:center; gap:12px; pointer-events:auto;">
        <span style="font-size:2rem;line-height:1;color:#dc2626;">&#10060;</span>
        <span>${message}</span>
    </div>
`;

        document.body.appendChild(notification);

        // Auto-dismiss after 2 seconds with fade out
        setTimeout(() => {
            notification.style.transition = 'opacity 0.3s';
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification && notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }, 2000);
    }

    // Real-time inbox functionality
    let allMessages = <?php echo json_encode($messages); ?>;
    let currentFilter = 'all';

    // Update header notification count (both messages and notifications)
    function updateHeaderMessageCount() {
        const unreadCount = allMessages.filter(msg => !msg.is_read).length;
        const headerBadge = document.querySelector('.absolute.-top-1.-right-1');
        if (headerBadge) {
            if (unreadCount > 0) {
                headerBadge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                headerBadge.style.display = 'flex';
            } else {
                headerBadge.style.display = 'none';
            }
        }

        // Also update notification count in header (if it exists)
        updateHeaderNotificationCount();
    }

    // Update header notification count
    function updateHeaderNotificationCount() {
        // Since the header fetches both counts together, we need to update both badges
        // Find all notification badges in the header
        const notificationBadges = document.querySelectorAll('a[href*="notifications"] .absolute.-top-1.-right-1, a[href*="inbox"] .absolute.-top-1.-right-1');

        notificationBadges.forEach(badge => {
            const unreadCount = allMessages.filter(msg => !msg.is_read).length;
            if (unreadCount > 0) {
                badge.textContent = unreadCount > 9 ? '9+' : unreadCount;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        });
    }

    // Mark message as read
    function markMessageAsRead(messageId, messageIndex) {
        // Update local data immediately
        if (allMessages[messageIndex]) {
            allMessages[messageIndex].is_read = 1;
        }

        // Update UI immediately
        const messageItem = document.querySelectorAll('.message-item')[messageIndex];
        if (messageItem) {
            const unreadBadge = messageItem.querySelector('.bg-blue-100.text-blue-800');
            if (unreadBadge) {
                unreadBadge.remove();
            }

            // Update message count
            updateMessageCount();
            updateHeaderMessageCount();
        }

        // Send server request
        fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mark_message_read=1&message_id=${messageId}`
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    console.error('Error marking message as read:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    // Mark all messages as read
    function markAllMessagesAsRead() {
        const unreadMessages = allMessages.filter(msg => !msg.is_read);
        if (unreadMessages.length === 0) {
            return;
        }

        // Update local data immediately
        allMessages.forEach(msg => {
            msg.is_read = 1;
        });

        // Update UI immediately
        document.querySelectorAll('.message-item').forEach((item, index) => {
            const unreadBadge = item.querySelector('.bg-blue-100.text-blue-800');
            if (unreadBadge) {
                unreadBadge.remove();
            }
        });

        // Update counts
        updateMessageCount();
        updateHeaderMessageCount();

        // Send server request
        fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mark_all_messages_read=1&student_id=${<?php echo $_SESSION['student_row_id'] ?? 'null'; ?>}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('All messages and notifications marked as read:', data.affected_rows, 'messages updated,', data.affected_notifs, 'notifications updated');

                    // Force refresh of notification count in header
                    setTimeout(() => {
                        // Trigger a page refresh to ensure all counts are synchronized
                        window.location.reload();
                    }, 1000);
                } else {
                    console.error('Error marking all messages as read:', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
    }

    // Initialize header count on page load
    function initializeHeaderCount() {
        updateHeaderMessageCount();
    }

    // Override the showMessageModal function to mark messages as read
    const originalShowMessageModal = showMessageModal;
    showMessageModal = function(index) {
        // Call original function
        originalShowMessageModal(index);

        // Mark message as read if it's unread
        if (allMessages[index] && !allMessages[index].is_read) {
            markMessageAsRead(allMessages[index].id, index);
        }
    };

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

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function() {
        initializeHeaderCount();
    });

    // Global function to update all header counts (can be called from other pages)
    window.updateAllHeaderCounts = function() {
        // This function can be called from notifications.php to sync counts
        if (typeof allMessages !== 'undefined') {
            updateHeaderMessageCount();
        }
    };

    // Handle message item click (prevent checkbox clicks from triggering message selection)
    function handleMessageItemClick(event, index) {
        // Check if the click was on a checkbox or its container
        if (event.target.type === 'checkbox' || event.target.closest('.flex-shrink-0')) {
            // Don't trigger message selection for checkbox clicks
            return;
        }
        
        // For all other clicks, show the message
        showMessageModal(index);
    }

    // Checkbox functionality
    function handleCheckboxChange() {
        const checkedBoxes = document.querySelectorAll('.message-checkbox:checked');
        const bulkActions = document.getElementById('bulkActions');
        
        if (checkedBoxes.length > 0) {
            bulkActions.style.display = 'flex';
        } else {
            bulkActions.style.display = 'none';
        }
    }

    // Select all messages
    function selectAllMessages() {
        const checkboxes = document.querySelectorAll('.message-checkbox');
        const allChecked = Array.from(checkboxes).every(cb => cb.checked);
        
        checkboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });
        
        handleCheckboxChange();
    }

    // Mark selected messages as read
    function markSelectedAsRead() {
        const checkedBoxes = document.querySelectorAll('.message-checkbox:checked');
        const messageIds = Array.from(checkedBoxes).map(cb => cb.dataset.messageId);
        const messageIndices = Array.from(checkedBoxes).map(cb => parseInt(cb.dataset.messageIndex));
        
        if (messageIds.length === 0) {
            showSimpleErrorMessage('No messages selected');
            return;
        }

        // Update local data immediately
        messageIndices.forEach(index => {
            if (allMessages[index]) {
                allMessages[index].is_read = 1;
            }
        });

        // Update UI immediately
        messageIndices.forEach(index => {
            const messageItem = document.querySelectorAll('.message-item')[index];
            if (messageItem) {
                const unreadBadge = messageItem.querySelector('.bg-blue-100.text-blue-800');
                if (unreadBadge) {
                    unreadBadge.remove();
                }
            }
        });

        // Uncheck all checkboxes
        checkedBoxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        handleCheckboxChange();

        // Update counts
        updateMessageCount();
        updateHeaderMessageCount();

        // Send server request for each message
        Promise.all(messageIds.map(messageId => 
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `mark_message_read=1&message_id=${messageId}`
            })
        )).then(() => {
            showSimpleSuccessMessage(`${messageIds.length} message(s) marked as read`);
        }).catch(error => {
            console.error('Error marking messages as read:', error);
            showSimpleErrorMessage('Failed to mark some messages as read');
        });
    }

    // Delete selected messages
    function deleteSelectedMessages() {
        const checkedBoxes = document.querySelectorAll('.message-checkbox:checked');
        const messageIds = Array.from(checkedBoxes).map(cb => cb.dataset.messageId);
        const messageIndices = Array.from(checkedBoxes).map(cb => parseInt(cb.dataset.messageIndex));
        
        if (messageIds.length === 0) {
            showSimpleErrorMessage('No messages selected');
            return;
        }

        const messageCount = messageIds.length;
        const messageText = messageCount === 1 ? 'message' : 'messages';

        showConfirmModal(
            `Are you sure you want to delete ${messageCount} ${messageText}?`,
            function() {
                // Delete messages from database
                Promise.all(messageIds.map(messageId => {
                    const formData = new FormData();
                    formData.append('message_id', messageId);
                    return fetch('delete_message.php', {
                        method: 'POST',
                        body: formData
                    }).then(response => response.json());
                })).then(results => {
                    const successful = results.filter(result => result.success).length;
                    
                    if (successful === messageIds.length) {
                        // Remove messages from DOM
                        messageIndices.sort((a, b) => b - a).forEach(index => {
                            const messageItem = document.querySelectorAll('.message-item')[index];
                            if (messageItem) {
                                messageItem.remove();
                            }
                        });

                        // Update the message data array to reflect deletions
                        messageIndices.sort((a, b) => b - a).forEach(index => {
                            allMessages.splice(index, 1);
                        });

                        // Update message count
                        updateMessageCount();
                        updateHeaderMessageCount();

                        showSimpleSuccessMessage(`${successful} ${messageText} deleted successfully`);
                        
                        // Hide bulk actions if no messages left
                        if (allMessages.length === 0) {
                            document.getElementById('bulkActions').style.display = 'none';
                        }
                        
                        // If no messages left, show empty state
                        if (allMessages.length === 0) {
                            const messageListContainer = document.querySelector('.w-1/3 .overflow-y-auto');
                            if (messageListContainer) {
                                messageListContainer.innerHTML = `
                                    <div class="flex flex-col items-center justify-center h-full text-center p-8">
                                        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mb-4">
                                            <i class="ri-mail-line text-2xl text-gray-400"></i>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">No messages yet</h3>
                                        <p class="text-sm text-gray-500">Messages from clinic staff will appear here</p>
                                    </div>
                                `;
                            }
                        }
                    } else {
                        showSimpleErrorMessage(`Only ${successful} of ${messageIds.length} messages were deleted`);
                    }
                }).catch(error => {
                    console.error('Error deleting messages:', error);
                    showSimpleErrorMessage('Failed to delete messages');
                });
            },
            function() {
                // User cancelled deletion
            }
        );
    }
</script>

<?php includeModalSystem(); ?>

<?php
include '../includep/footer.php';
?>