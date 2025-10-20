<?php
include '../includes/db_connect.php';
include '../includes/header.php';

// Database connection
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
    
    // Debug: Check total count in database
    $total_count = $db->query('SELECT COUNT(*) as total FROM imported_patients')->fetch(PDO::FETCH_ASSOC);
    $debug_info = "Total records in imported_patients: " . $total_count['total'] . "\n";
    
    // Debug: Check for duplicates in database
    $duplicate_check = $db->query('SELECT id, COUNT(*) as count FROM imported_patients GROUP BY id HAVING COUNT(*) > 1')->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($duplicate_check)) {
        $debug_info .= "Duplicate IDs found in database: " . json_encode($duplicate_check) . "\n";
    }
    
    // Debug: Check unique IDs count
    $unique_count = $db->query('SELECT COUNT(DISTINCT id) as unique_count FROM imported_patients')->fetch(PDO::FETCH_ASSOC);
    $debug_info .= "Unique IDs in imported_patients: " . $unique_count['unique_count'] . "\n";
    
    // Fetch all patients for dropdown (remove duplicates by ID)
    $patients = $db->query('SELECT id, name, student_id, email, year_level, course_program FROM imported_patients GROUP BY id ORDER BY name ASC')->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Check patient count
    $debug_info .= "Total patients fetched: " . count($patients) . "\n";
    
    // Log to error log
    error_log($debug_info);
    
    // Also display debug info in browser (remove this after debugging)
    if (isset($_GET['debug'])) {
        echo "<pre>DEBUG INFO:\n" . $debug_info . "</pre>";
    }
    
    // Fetch all faculty for dropdown
    try {
        $faculty = $db->query('SELECT faculty_id, full_name, email, department, college_course FROM faculty ORDER BY full_name ASC')->fetchAll(PDO::FETCH_ASSOC);
        error_log("Faculty query executed successfully");
    } catch (Exception $e) {
        error_log("Faculty query failed: " . $e->getMessage());
        $faculty = [];
    }
    
    // Debug: Check faculty data after query
    error_log("Faculty query result count: " . count($faculty));
    if (count($faculty) === 1) {
        error_log("WARNING: Only 1 faculty found after query - this may cause the faculty table to be empty");
        error_log("Faculty data: " . json_encode($faculty));
        
        // Check if this is after a form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            error_log("This is a POST request - form was submitted");
            
            // Test database connection
            try {
                $test_query = $db->query('SELECT COUNT(*) as count FROM faculty');
                $test_result = $test_query->fetch(PDO::FETCH_ASSOC);
                error_log("Database test - Total faculty in database: " . $test_result['count']);
            } catch (Exception $e) {
                error_log("Database test failed: " . $e->getMessage());
            }
        }
    }
    
    
    // Get current staff member info
    $staff_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $staff_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Staff';
    
    // Fetch messages sent by current staff member (grouped by batch - same subject and similar timestamp)
    $sent_messages_query = "
        SELECT 
            subject,
            message,
            MIN(created_at) as created_at,
            COUNT(*) as recipient_count,
            GROUP_CONCAT(recipient_name SEPARATOR ', ') as recipients,
            MIN(id) as batch_id
        FROM messages 
        WHERE sender_id = ? 
        GROUP BY subject, DATE(created_at), HOUR(created_at), MINUTE(created_at)
        ORDER BY created_at DESC 
    ";
    $sent_messages = $db->prepare($sent_messages_query);
    $sent_messages->execute([$staff_id]);
    $messages = $sent_messages->fetchAll(PDO::FETCH_ASSOC);
    
    // Debug: Check total messages in database vs fetched messages
    $total_messages_query = "SELECT COUNT(*) as total FROM messages WHERE sender_id = ?";
    $total_stmt = $db->prepare($total_messages_query);
    $total_stmt->execute([$staff_id]);
    $total_count = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    error_log("Total messages in database for sender_id $staff_id: $total_count");
    error_log("Grouped messages fetched: " . count($messages));
    
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_type = $_POST['recipient_type'];
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    
    if ($subject && $message_text) {
        try {
            $sender_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
            $sender_name = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Staff';
            $sender_role = isset($_SESSION['role']) ? $_SESSION['role'] : 'staff';
            
            if ($recipient_type === 'all') {
                // Send to all students and faculty
                $inserted = 0;
                
                // Send to all students
                foreach ($patients as $patient) {
                    $stmt = $db->prepare('INSERT INTO messages (sender_id, sender_name, sender_role, recipient_id, recipient_name, subject, message) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$sender_id, $sender_name, $sender_role, $patient['id'], $patient['name'], $subject, $message_text]);
                    // Also insert notification for each student
                    $notif_stmt = $db->prepare('INSERT INTO notifications (student_id, message, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
                    $notif_message = "New message from staff: " . $subject;
                    $notif_type = "message";
                    $notif_stmt->execute([$patient['id'], $notif_message, $notif_type]);
                    $inserted++;
                }
                
                // Send to all faculty
                foreach ($faculty as $faculty_member) {
                    $stmt = $db->prepare('INSERT INTO messages (sender_id, sender_name, sender_role, recipient_id, recipient_name, subject, message) VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([$sender_id, $sender_name, $sender_role, $faculty_member['faculty_id'], $faculty_member['full_name'], $subject, $message_text]);
                    // Also insert notification for each faculty member
                    $notif_stmt = $db->prepare('INSERT INTO notifications (faculty_id, message, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
                    $notif_message = "New message from staff: " . $subject;
                    $notif_type = "message";
                    $notif_stmt->execute([$faculty_member['faculty_id'], $notif_message, $notif_type]);
                    $inserted++;
                }
                
                $success_message = "Message sent successfully to all " . $inserted . " recipients";
            } else {
                // Send to specific recipients
                $selected_students = isset($_POST['selected_students']) ? $_POST['selected_students'] : [];
                $selected_faculty = isset($_POST['selected_faculty']) ? $_POST['selected_faculty'] : [];
                $total_recipients = count($selected_students) + count($selected_faculty);
                
                if ($total_recipients > 0) {
                    $inserted = 0;
                    
                // Debug: Log selected students count
                error_log("Selected students count: " . count($selected_students));
                error_log("Selected students IDs: " . implode(', ', $selected_students));
                
                // Debug: Log all POST data for students
                if (isset($_POST['selected_students'])) {
                    error_log("POST selected_students array: " . json_encode($_POST['selected_students']));
                    error_log("POST selected_students count: " . count($_POST['selected_students']));
            } else {
                    error_log("No selected_students in POST data");
                }
                
                // Send to selected students
                foreach ($selected_students as $student_id) {
                    $student_stmt = $db->prepare('SELECT name FROM imported_patients WHERE id = ?');
                    $student_stmt->execute([$student_id]);
                    $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($student) {
                    $insert_stmt = $db->prepare('INSERT INTO messages (sender_id, sender_name, sender_role, recipient_id, recipient_name, subject, message) VALUES (?, ?, ?, ?, ?, ?, ?)');
                            $insert_stmt->execute([$sender_id, $sender_name, $sender_role, $student_id, $student['name'], $subject, $message_text]);
                            // Also insert notification for the student
                    $notif_stmt = $db->prepare('INSERT INTO notifications (student_id, message, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
                    $notif_message = "New message from staff: " . $subject;
                    $notif_type = "message";
                            $notif_stmt->execute([$student_id, $notif_message, $notif_type]);
                            $inserted++;
                        }
                    }
                    
                    // Send to selected faculty
                    foreach ($selected_faculty as $faculty_id) {
                        $faculty_stmt = $db->prepare('SELECT full_name FROM faculty WHERE faculty_id = ?');
                        $faculty_stmt->execute([$faculty_id]);
                        $faculty_member = $faculty_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($faculty_member) {
                            $insert_stmt = $db->prepare('INSERT INTO messages (sender_id, sender_name, sender_role, recipient_id, recipient_name, subject, message) VALUES (?, ?, ?, ?, ?, ?, ?)');
                            $insert_stmt->execute([$sender_id, $sender_name, $sender_role, $faculty_id, $faculty_member['full_name'], $subject, $message_text]);
                            // Also insert notification for the faculty member
                            $notif_stmt = $db->prepare('INSERT INTO notifications (faculty_id, message, type, is_read, created_at) VALUES (?, ?, ?, 0, NOW())');
                            $notif_message = "New message from staff: " . $subject;
                            $notif_type = "message";
                            $notif_stmt->execute([$faculty_id, $notif_message, $notif_type]);
                            $inserted++;
                        }
                    }
                    
                    $success_message = "Message sent successfully to " . $inserted . " recipients";
                } else {
                    $error_message = "Please select at least one recipient";
                }
            }
            
            // Refresh messages list
            $sent_messages = $db->prepare($sent_messages_query);
            $sent_messages->execute([$staff_id]);
            $messages = $sent_messages->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error_message = "Failed to send message: " . $e->getMessage();
        }
    } else {
        $error_message = "Please fill in all fields.";
    }
}
?>
<style>
  html, body {
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* Internet Explorer 10+ */
  }
  html::-webkit-scrollbar,
  body::-webkit-scrollbar {
    display: none; /* Safari and Chrome */
  }
  
  /* Line clamp utility for message previews */
  
  /* Fixed column widths and ellipses for Select Recipients tables */
  #studentsTable table,
  #facultyTable table {
    table-layout: fixed;
    width: 100%;
  }
  
  /* Students table column widths */
  #studentsTable table th:nth-child(1),
  #studentsTable table td:nth-child(1) {
    width: 60px; /* Checkbox column */
  }
  
  #studentsTable table th:nth-child(2),
  #studentsTable table td:nth-child(2) {
    width: 25%; /* Name */
  }
  
  #studentsTable table th:nth-child(3),
  #studentsTable table td:nth-child(3) {
    width: 30%; /* Email */
  }
  
  #studentsTable table th:nth-child(4),
  #studentsTable table td:nth-child(4) {
    width: 20%; /* Year Level */
  }
  
  #studentsTable table th:nth-child(5),
  #studentsTable table td:nth-child(5) {
    width: 25%; /* Course */
  }
  
  /* Faculty table column widths */
  #facultyTable table th:nth-child(1),
  #facultyTable table td:nth-child(1) {
    width: 60px; /* Checkbox column */
  }
  
  #facultyTable table th:nth-child(2),
  #facultyTable table td:nth-child(2) {
    width: 25%; /* Name */
  }
  
  #facultyTable table th:nth-child(3),
  #facultyTable table td:nth-child(3) {
    width: 30%; /* Email */
  }
  
  #facultyTable table th:nth-child(4),
  #facultyTable table td:nth-child(4) {
    width: 20%; /* Department */
  }
  
  #facultyTable table th:nth-child(5),
  #facultyTable table td:nth-child(5) {
    width: 25%; /* Course */
  }
  
  /* Text truncation with ellipses */
  #studentsTable table td,
  #facultyTable table td {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
  .line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
</style>
<main class="flex-1 overflow-y-auto bg-gray-50 p-4 sm:p-6 ml-16 md:ml-64 mt-[56px]">
    <div class="w-full">
        <!-- Page Header -->
        <div class="mb-6 sm:mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">Message Center</h1>
            <p class="text-sm sm:text-base text-gray-600">Send messages to students and faculty</p>
        </div>
        
        <?php if (isset($success_message)): ?>
            <?php showSuccessModal(htmlspecialchars($success_message), 'Message Sent'); ?>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <?php showErrorModal(htmlspecialchars($error_message), 'Error'); ?>
        <?php endif; ?>
        
        <!-- Two Column Layout -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 lg:gap-8">
            <!-- Left Column - Compose Message (2/3 width) -->
            <div class="xl:col-span-2">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 lg:p-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Compose Message</h2>
                    
                    <form method="post" class="space-y-6" id="messageForm">
                        <!-- Send To Section -->
                <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Send To</label>
                            <div class="space-y-3">
                        <label class="flex items-center">
                                    <input type="radio" name="recipient_type" value="all" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" checked>
                                    <span class="ml-3 text-sm text-gray-700">Send to All</span>
                        </label>
                        <label class="flex items-center">
                                    <input type="radio" name="recipient_type" value="specific" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300">
                                    <span class="ml-3 text-sm text-gray-700">Send to Specific Recipients</span>
                        </label>
                    </div>
                </div>
                
                        <!-- Specific Patient Selection -->
                <div id="specificPatientDiv" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-4">Select Recipients</label>
                            <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center mb-4 gap-4">
                                <div class="flex space-x-2">
                                    <button type="button" class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-md">Students</button>
                                    <button type="button" class="px-3 py-1 text-sm bg-gray-100 text-gray-600 rounded-md">Faculty</button>
                                </div>
                                <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <i class="ri-search-line text-gray-400"></i>
                                        </div>
                                        <input type="text" class="block w-full sm:w-64 pl-10 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Search students...">
                                    </div>
                                    <p class="text-xs text-gray-500" id="selectedCount">0 selected</p>
                                </div>
                            </div>
                            
                            <!-- Recipients Table -->
                            <div class="mt-4 border border-gray-200 rounded-md overflow-hidden">
                                <!-- Students Table -->
                                <div id="studentsTable">
                                    <div class="overflow-x-auto">
                                        <table class="w-full divide-y divide-gray-200 table-fixed">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="w-12 px-4 py-2 text-left">
                                                        <input type="checkbox" id="selectAllStudents" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                    </th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year Level</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200" id="studentsTableBody">
                                                <!-- Students data will be loaded here via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Students Pagination -->
                                    <div class="mt-3 px-4 py-3 flex justify-between items-center">
                                        <div class="text-xs text-gray-600" id="studentsEntriesInfo">Showing 1 to 5 of 0 entries</div>
                                        <nav class="flex justify-end items-center -space-x-px" id="studentsPagination">
                                            <!-- Pagination will be generated here -->
                                        </nav>
                                    </div>
                                </div>
                                
                                <!-- Faculty Table (Hidden by default) -->
                                <div id="facultyTable" class="hidden">
                                    <div class="overflow-x-auto">
                                        <table class="w-full divide-y divide-gray-200 table-fixed">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="w-12 px-4 py-2 text-left">
                                                        <input type="checkbox" id="selectAllFaculty" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                    </th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200" id="facultyTableBody">
                                                <!-- Faculty data will be loaded here via JavaScript -->
                                            </tbody>
                                        </table>
                                    </div>
                                    <!-- Faculty Pagination -->
                                    <div class="mt-3 px-4 py-3 flex justify-between items-center">
                                        <div class="text-xs text-gray-600" id="facultyEntriesInfo">Showing 1 to 5 of 0 entries</div>
                                        <nav class="flex justify-end items-center -space-x-px" id="facultyPagination">
                                            <!-- Pagination will be generated here -->
                                        </nav>
                                    </div>
                                </div>
                            </div>
                </div>
                
                        <!-- Subject Field -->
                <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Subject</label>
                            <input type="text" id="subject" name="subject" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter message subject" required>
                </div>
                        
                        <!-- Message Field -->
                <div>
                            <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Message</label>
                            <textarea id="message" name="message" rows="4" class="w-full border border-gray-300 rounded-md px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Enter your message here..." required></textarea>
                </div>
                        
                        <!-- Footer with Recipients Count and Send Button -->
                        <div class="flex justify-between items-center pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-500" id="recipientsCount">Recipients: All Students and Faculty (<?php echo count($patients) + count($faculty); ?> total)</p>
                            <button type="submit" name="send_message" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Send Message
                    </button>
                </div>
            </form>
        </div>
            </div>
            
            <!-- Right Column - Previous Messages (1/3 width) -->
            <div class="xl:col-span-1">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 sm:p-6 h-full flex flex-col">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Previous Sent messages
                    </h3>
                    
            <?php if (empty($messages)): ?>
                        <p class="text-gray-500 text-center py-8">No messages sent yet.</p>
            <?php else: ?>
                        <div class="space-y-4 overflow-y-auto flex-1 pr-2" style="max-height: 400px;" id="messagesContainer">
                            <?php 
                            $messagesPerPage = 20; // Increased from 10 to 20 for better performance with large datasets
                            $totalMessages = count($messages);
                            $displayMessages = array_slice($messages, 0, $messagesPerPage);
                            ?>
                            <?php foreach ($displayMessages as $index => $msg): ?>
                            <div class="cursor-pointer hover:bg-gray-50 p-3 rounded-md transition-colors" onclick="showMessageModal(<?php echo $index; ?>)">
                                <div class="flex items-start justify-between mb-2">
                                    <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($msg['subject']); ?></h4>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                            <?php echo $msg['recipient_count']; ?> recipient<?php echo $msg['recipient_count'] > 1 ? 's' : ''; ?>
                                        </span>
                                    </div>
                                <p class="text-sm text-gray-600 mb-2 line-clamp-2"><?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . '...'; ?></p>
                                <div class="flex justify-between items-center text-xs text-gray-500">
                                    <span>To: <?php echo $msg['recipient_count'] <= 5 ? htmlspecialchars($msg['recipients']) : $msg['recipient_count'] . ' recipients'; ?></span>
                                    <span><?php echo date('M j, Y, g:i A', strtotime($msg['created_at'])); ?></span>
                                    </div>
                    </div>
                            <?php endforeach; ?>
                            
                            <?php if ($totalMessages > $messagesPerPage): ?>
                            <div class="text-center pt-4">
                                <button id="seeMoreButton" onclick="loadMoreMessages()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                    See More (<?php echo $totalMessages - $messagesPerPage; ?> remaining)
                                </button>
                            </div>
                            <?php endif; ?>
                    </div>
            <?php endif; ?>
        </div>
            </div>
        </div>
    </div>
</main>

<!-- Message Modal -->
<div id="messageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-xl max-w-2xl w-full max-h-[80vh] overflow-y-auto">
        <div class="flex items-center justify-between p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800" id="modalSubject"></h3>
            <button onclick="closeMessageModal()" class="text-gray-400 hover:text-gray-600">
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="p-6">
            <div class="flex items-center gap-2 mb-4">
                <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded" id="modalRecipients"></span>
                <span class="text-xs text-green-600" id="modalReadCount"></span>
                <span class="text-xs text-gray-400" id="modalDate"></span>
            </div>
            <div class="text-gray-600 mb-4" id="modalMessage"></div>
            <div class="text-xs text-gray-500" id="modalRecipientList"></div>
        </div>
    </div>
</div>

<script>
// Toggle specific patient selection based on radio button
document.addEventListener('DOMContentLoaded', function() {
    const recipientTypeRadios = document.querySelectorAll('input[name="recipient_type"]');
    const specificPatientDiv = document.getElementById('specificPatientDiv');
    
    recipientTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
            const recipientsCount = document.getElementById('recipientsCount');
            
            if (this.value === 'specific') {
                specificPatientDiv.classList.remove('hidden');
                if (recipientsCount) {
                    recipientsCount.textContent = 'Recipients: No recipients selected';
                }
                
                // Automatically show students table and activate Students tab
                studentsTable.classList.remove('hidden');
                facultyTable.classList.add('hidden');
                searchInput.placeholder = 'Search students...';
                
                // Reset search and show all students
                searchInput.value = '';
                filteredStudentsData = [...studentsData];
                loadStudentsPage(1);
                updateSelectedCount();
                
                // Update tab button states
                const studentsTab = document.querySelector('#specificPatientDiv button[data-tab="students"]');
                const facultyTab = document.querySelector('#specificPatientDiv button[data-tab="faculty"]');
                if (studentsTab && facultyTab) {
                    studentsTab.classList.add('bg-blue-600', 'text-white');
                    studentsTab.classList.remove('bg-gray-100', 'text-gray-700');
                    facultyTab.classList.remove('bg-blue-600', 'text-white');
                    facultyTab.classList.add('bg-gray-100', 'text-gray-700');
                }
            } else {
                specificPatientDiv.classList.add('hidden');
                if (recipientsCount) {
                    recipientsCount.textContent = `Recipients: All Students and Faculty (<?php echo count($patients) + count($faculty); ?> total)`;
                }
            }
        });
    });
    
    // Handle tab switching for Students/Faculty
    const tabButtons = document.querySelectorAll('#specificPatientDiv button');
    const studentsTable = document.getElementById('studentsTable');
    const facultyTable = document.getElementById('facultyTable');
    const searchInput = document.querySelector('#specificPatientDiv input[type="text"]');
    
    // Initialize filtered data
    filteredStudentsData = studentsData && Array.isArray(studentsData) ? [...studentsData] : [];
    filteredFacultyData = facultyData && Array.isArray(facultyData) ? [...facultyData] : [];
    
    // Simple faculty data check
    if (facultyData.length === 0) {
        console.warn('⚠️ Faculty data is empty - faculty table will show 0 entries');
    } else if (facultyData.length === 1) {
        console.warn('⚠️ Only 1 faculty entry found - this may cause issues');
        console.log('Faculty data:', facultyData);
    } else {
        console.log('✅ Faculty data loaded:', facultyData.length, 'entries');
    }
    
    
    // Search input event listener
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (studentsTable.classList.contains('hidden')) {
            // Search faculty
            if (facultyData && Array.isArray(facultyData)) {
                filteredFacultyData = facultyData.filter(faculty => 
                    faculty.full_name.toLowerCase().includes(searchTerm) ||
                    faculty.email.toLowerCase().includes(searchTerm) ||
                    faculty.department.toLowerCase().includes(searchTerm) ||
                    (faculty.college_course && faculty.college_course.toLowerCase().includes(searchTerm))
                );
                loadFacultyPage(1);
            }
        } else {
            // Search students
            if (studentsData && Array.isArray(studentsData)) {
                filteredStudentsData = studentsData.filter(student => 
                    student.name.toLowerCase().includes(searchTerm) ||
                    (student.email && student.email.toLowerCase().includes(searchTerm)) ||
                    (student.student_id && student.student_id.toLowerCase().includes(searchTerm)) ||
                    (student.year_level && student.year_level.toLowerCase().includes(searchTerm)) ||
                    (student.course_program && student.course_program.toLowerCase().includes(searchTerm))
                );
                loadStudentsPage(1);
            }
        }
    });
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Remove active state from all buttons
            tabButtons.forEach(btn => {
                btn.classList.remove('bg-blue-100', 'text-blue-800');
                btn.classList.add('bg-gray-100', 'text-gray-600');
            });
            
            // Add active state to clicked button
            this.classList.remove('bg-gray-100', 'text-gray-600');
            this.classList.add('bg-blue-100', 'text-blue-800');
            
            // Show/hide appropriate table
            if (this.textContent.trim() === 'Students') {
                studentsTable.classList.remove('hidden');
                facultyTable.classList.add('hidden');
                searchInput.placeholder = 'Search students...';
                // Reset search and show all students
                searchInput.value = '';
                filteredStudentsData = [...studentsData];
                loadStudentsPage(1); // Reset to first page
                updateSelectedCount();
            } else if (this.textContent.trim() === 'Faculty') {
                studentsTable.classList.add('hidden');
                facultyTable.classList.remove('hidden');
                searchInput.placeholder = 'Search faculty...';
                // Reset search and show all faculty
                searchInput.value = '';
                filteredFacultyData = facultyData && Array.isArray(facultyData) ? [...facultyData] : [];
                loadFacultyPage(1); // Reset to first page
                updateSelectedCount();
            }
        });
    });
    
    // Handle select all checkboxes using event delegation
    document.addEventListener('change', function(e) {
        if (e.target.id === 'selectAllStudents') {
            
            if (e.target.checked) {
                // Clear previous selections first
                if (window.selectedStudents) window.selectedStudents.clear();
                
                // Select all filtered students only
                filteredStudentsData.forEach(student => {
                    if (!window.selectedStudents) window.selectedStudents = new Set();
                    window.selectedStudents.add(student.id);
                });
                
            } else {
                // Deselect all students
                if (window.selectedStudents) window.selectedStudents.clear();
            }
            // Reload current page to update checkboxes
            loadStudentsPage(currentStudentsPage);
            updateSelectedCount();
        }
        
        if (e.target.id === 'selectAllFaculty') {
            if (e.target.checked) {
                // Clear previous selections first
                if (window.selectedFaculty) window.selectedFaculty.clear();
                
                // Select all filtered faculty only
                if (filteredFacultyData && Array.isArray(filteredFacultyData)) {
                    filteredFacultyData.forEach(faculty => {
                        if (!window.selectedFaculty) window.selectedFaculty = new Set();
                        window.selectedFaculty.add(faculty.faculty_id);
                    });
                }
            } else {
                // Deselect all faculty
                if (window.selectedFaculty) window.selectedFaculty.clear();
            }
            // Reload current page to update checkboxes
            loadFacultyPage(currentFacultyPage);
            updateSelectedCount();
        }
        
        // Handle individual checkbox changes
        if (e.target.classList.contains('student-checkbox')) {
            const studentId = parseInt(e.target.value);
            if (!window.selectedStudents) window.selectedStudents = new Set();
            
            if (e.target.checked) {
                window.selectedStudents.add(studentId);
            } else {
                window.selectedStudents.delete(studentId);
            }
            updateSelectedCount();
            updateSelectAllStudentsState();
        }
        
        if (e.target.classList.contains('faculty-checkbox')) {
            const facultyId = parseInt(e.target.value);
            if (!window.selectedFaculty) window.selectedFaculty = new Set();
            
            if (e.target.checked) {
                window.selectedFaculty.add(facultyId);
            } else {
                window.selectedFaculty.delete(facultyId);
            }
            updateSelectedCount();
            updateSelectAllFacultyState();
            }
        });
    });

// Update selected count
function updateSelectedCount() {
    const selectedStudentsCount = window.selectedStudents ? window.selectedStudents.size : 0;
    const selectedFacultyCount = window.selectedFaculty ? window.selectedFaculty.size : 0;
    const totalSelected = selectedStudentsCount + selectedFacultyCount;
    
    // Update selected count display
    const selectedCount = document.getElementById('selectedCount');
    if (selectedCount) {
        selectedCount.textContent = `${totalSelected} selected`;
    }
    
    // Update recipients count
    const recipientsCount = document.getElementById('recipientsCount');
    if (recipientsCount) {
        if (totalSelected === 0) {
            recipientsCount.textContent = 'Recipients: No recipients selected';
        } else {
            recipientsCount.textContent = `Recipients: ${totalSelected} selected`;
        }
    }
}

// Initialize global selection sets
window.selectedStudents = new Set();
window.selectedFaculty = new Set();

// Pagination variables
let currentStudentsPage = 1;
let currentFacultyPage = 1;
const itemsPerPage = 5;

// Filtered data for search functionality
let filteredStudentsData = [];
let filteredFacultyData = [];


// Initialize count
updateSelectedCount();

// Handle form submission to include all selected students and faculty
document.getElementById('messageForm').addEventListener('submit', function(e) {
    
    // Remove any existing hidden inputs for selected students and faculty
    const existingStudentInputs = document.querySelectorAll('input[name="selected_students[]"][type="hidden"]');
    const existingFacultyInputs = document.querySelectorAll('input[name="selected_faculty[]"][type="hidden"]');
    
    
    existingStudentInputs.forEach(input => input.remove());
    existingFacultyInputs.forEach(input => input.remove());
    
    // Add hidden inputs for all selected students
    if (window.selectedStudents && window.selectedStudents.size > 0) {
        
        // Uncheck all visible checkboxes to prevent duplicates
        const visibleCheckboxes = document.querySelectorAll('input[name="selected_students[]"][type="checkbox"]');
        visibleCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        window.selectedStudents.forEach(studentId => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'selected_students[]';
            hiddenInput.value = studentId;
            this.appendChild(hiddenInput);
        });
    }
    
    // Add hidden inputs for all selected faculty
    if (window.selectedFaculty && window.selectedFaculty.size > 0) {
        
        // Uncheck all visible faculty checkboxes to prevent duplicates
        const visibleFacultyCheckboxes = document.querySelectorAll('input[name="selected_faculty[]"][type="checkbox"]');
        visibleFacultyCheckboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        
        window.selectedFaculty.forEach(facultyId => {
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'selected_faculty[]';
            hiddenInput.value = facultyId;
            this.appendChild(hiddenInput);
        });
    }
    
    // Debug: Count all selected_students[] inputs before submission
    const allStudentInputs = document.querySelectorAll('input[name="selected_students[]"]');
    
    // Debug: Count all selected_faculty[] inputs before submission
    const allFacultyInputs = document.querySelectorAll('input[name="selected_faculty[]"]');
    
    // Debug: Check if there are any visible checkboxes that might be checked
    const visibleStudentCheckboxes = document.querySelectorAll('input[name="selected_students[]"][type="checkbox"]:checked');
    const visibleFacultyCheckboxes = document.querySelectorAll('input[name="selected_faculty[]"][type="checkbox"]:checked');
});

// Update select all students checkbox state
function updateSelectAllStudentsState() {
    const selectAllCheckbox = document.getElementById('selectAllStudents');
    const totalStudents = filteredStudentsData.length;
    const selectedStudents = window.selectedStudents ? window.selectedStudents.size : 0;
    
    if (selectAllCheckbox && totalStudents > 0) {
        if (selectedStudents === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (selectedStudents === totalStudents) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }
}

// Update select all faculty checkbox state
function updateSelectAllFacultyState() {
    const selectAllCheckbox = document.getElementById('selectAllFaculty');
    const totalFaculty = filteredFacultyData.length;
    const selectedFaculty = window.selectedFaculty ? window.selectedFaculty.size : 0;
    
    if (selectAllCheckbox && totalFaculty > 0) {
        if (selectedFaculty === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (selectedFaculty === totalFaculty) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }
}

// Message data for modal
const messageData = <?php echo json_encode($messages); ?>;

// Students and Faculty data for pagination
const studentsData = <?php echo json_encode($patients); ?> || [];
let facultyData = <?php echo json_encode($faculty); ?> || [];

// Ensure facultyData is always an array (same logic as students)
if (!Array.isArray(facultyData)) {
    console.warn('facultyData is not an array, converting to array');
    // If it's a single object, wrap it in an array
    if (facultyData && typeof facultyData === 'object') {
        facultyData = [facultyData];
    } else {
        facultyData = [];
    }
}



// Initialize pagination after data is loaded
loadStudentsPage(1);
loadFacultyPage(1);

// Load students page
function loadStudentsPage(page) {
    currentStudentsPage = page;
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageData = filteredStudentsData.slice(startIndex, endIndex);
    
    const tbody = document.getElementById('studentsTableBody');
    let html = '';
    
    pageData.forEach(student => {
        const isSelected = window.selectedStudents && window.selectedStudents.has(student.id);
        html += `
            <tr>
                <td class="px-4 py-2">
                    <input type="checkbox" name="selected_students[]" value="${student.id}" class="student-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" ${isSelected ? 'checked' : ''}>
                </td>
                <td class="px-4 py-2 text-sm text-gray-900 truncate" title="${student.name}">${student.name}</td>
                <td class="px-4 py-2 text-sm text-gray-900 truncate" title="${student.email || student.student_id + '@school.edu'}">${student.email || student.student_id + '@school.edu'}</td>
                <td class="px-4 py-2 text-sm text-gray-900 truncate" title="${student.year_level || 'N/A'}">${student.year_level || 'N/A'}</td>
                <td class="px-4 py-2 text-sm text-gray-900 truncate" title="${student.course_program || 'N/A'}">${student.course_program || 'N/A'}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Update pagination
    updateStudentsPagination();
    updateSelectedCount();
    
    // Update select all checkbox state
    updateSelectAllStudentsState();
}

// Load faculty page
function loadFacultyPage(page) {
    currentFacultyPage = page;
    const startIndex = (page - 1) * itemsPerPage;
    const endIndex = startIndex + itemsPerPage;
    const pageData = filteredFacultyData && Array.isArray(filteredFacultyData) ? filteredFacultyData.slice(startIndex, endIndex) : [];
    
    const tbody = document.getElementById('facultyTableBody');
    let html = '';
    
    pageData.forEach(faculty => {
        const isSelected = window.selectedFaculty && window.selectedFaculty.has(faculty.faculty_id);
        html += `
            <tr>
                <td class="px-4 py-2">
                    <input type="checkbox" name="selected_faculty[]" value="${faculty.faculty_id}" class="faculty-checkbox h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded" ${isSelected ? 'checked' : ''}>
                </td>
                <td class="px-4 py-2 text-sm text-gray-900 truncate" title="${faculty.full_name || 'N/A'}">${faculty.full_name || 'N/A'}</td>
                <td class="px-4 py-2 text-sm text-gray-900 truncate" title="${faculty.email || 'N/A'}">${faculty.email || 'N/A'}</td>
                <td class="px-4 py-2 text-sm text-gray-900 truncate" title="${faculty.department || 'N/A'}">${faculty.department || 'N/A'}</td>
                <td class="px-4 py-2 text-sm text-gray-900 truncate" title="${faculty.college_course || 'N/A'}">${faculty.college_course || 'N/A'}</td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    
    // Update pagination
    updateFacultyPagination();
    updateSelectedCount();
    
    // Update select all checkbox state
    updateSelectAllFacultyState();
}

// Update students pagination
function updateStudentsPagination() {
    const totalPages = Math.ceil(filteredStudentsData.length / itemsPerPage);
    const paginationContainer = document.getElementById('studentsPagination');
    const entriesInfo = document.getElementById('studentsEntriesInfo');
    
    // Update entries info
    const startEntry = (currentStudentsPage - 1) * itemsPerPage + 1;
    const endEntry = Math.min(currentStudentsPage * itemsPerPage, filteredStudentsData.length);
    entriesInfo.textContent = `Showing ${startEntry} to ${endEntry} of ${filteredStudentsData.length} entries`;
    
    // Generate pagination HTML
    let paginationHtml = '';
    
    if (totalPages > 0) {
        // Previous button
        paginationHtml += `
            <button type="button" ${currentStudentsPage === 1 ? 'disabled' : ''} 
                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" 
                    onclick="loadStudentsPage(${currentStudentsPage - 1})" aria-label="Previous">
                <span class="sr-only">Previous</span>
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </button>
        `;
        
        // Page numbers with ellipses
        const startPage = Math.max(1, currentStudentsPage - 2);
        const endPage = Math.min(totalPages, currentStudentsPage + 2);
        
        if (startPage > 1) {
            paginationHtml += `<button onclick="loadStudentsPage(1)" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === currentStudentsPage) {
                paginationHtml += `<button type="button" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-gray-300 text-gray-800 focus:outline-hidden focus:bg-gray-300">${i}</button>`;
            } else {
                paginationHtml += `<button onclick="loadStudentsPage(${i})" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">${i}</button>`;
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">...</span>`;
            }
            paginationHtml += `<button onclick="loadStudentsPage(${totalPages})" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">${totalPages}</button>`;
        }
        
        // Next button
        paginationHtml += `
            <button type="button" ${currentStudentsPage === totalPages ? 'disabled' : ''} 
                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" 
                    onclick="loadStudentsPage(${currentStudentsPage + 1})" aria-label="Next">
                <span class="sr-only">Next</span>
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </button>
        `;
    }
    
    paginationContainer.innerHTML = paginationHtml;
}

// Update faculty pagination
function updateFacultyPagination() {
    const totalPages = Math.ceil(filteredFacultyData.length / itemsPerPage);
    const paginationContainer = document.getElementById('facultyPagination');
    const entriesInfo = document.getElementById('facultyEntriesInfo');
    
    // Update entries info
    const startEntry = (currentFacultyPage - 1) * itemsPerPage + 1;
    const endEntry = Math.min(currentFacultyPage * itemsPerPage, filteredFacultyData.length);
    entriesInfo.textContent = `Showing ${startEntry} to ${endEntry} of ${filteredFacultyData.length} entries`;
    
    // Generate pagination HTML
    let paginationHtml = '';
    
    if (totalPages > 0) {
        // Previous button
        paginationHtml += `
            <button type="button" ${currentFacultyPage === 1 ? 'disabled' : ''} 
                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" 
                    onclick="loadFacultyPage(${currentFacultyPage - 1})" aria-label="Previous">
                <span class="sr-only">Previous</span>
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
            </button>
        `;
        
        // Page numbers with ellipses
        const startPage = Math.max(1, currentFacultyPage - 2);
        const endPage = Math.min(totalPages, currentFacultyPage + 2);
        
        if (startPage > 1) {
            paginationHtml += `<button onclick="loadFacultyPage(1)" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">1</button>`;
            if (startPage > 2) {
                paginationHtml += `<span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            if (i === currentFacultyPage) {
                paginationHtml += `<button type="button" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 bg-gray-300 text-gray-800 focus:outline-hidden focus:bg-gray-300">${i}</button>`;
            } else {
                paginationHtml += `<button onclick="loadFacultyPage(${i})" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">${i}</button>`;
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                paginationHtml += `<span class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-500">...</span>`;
            }
            paginationHtml += `<button onclick="loadFacultyPage(${totalPages})" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100">${totalPages}</button>`;
        }
        
        // Next button
        paginationHtml += `
            <button type="button" ${currentFacultyPage === totalPages ? 'disabled' : ''} 
                    class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100 disabled:opacity-50 disabled:pointer-events-none" 
                    onclick="loadFacultyPage(${currentFacultyPage + 1})" aria-label="Next">
                <span class="sr-only">Next</span>
                <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </button>
        `;
    }
    
    paginationContainer.innerHTML = paginationHtml;
}

// Show message modal
function showMessageModal(index) {
    const msg = messageData[index];
    const modal = document.getElementById('messageModal');
    
    // Populate modal content
    document.getElementById('modalSubject').textContent = msg.subject;
    document.getElementById('modalRecipients').textContent = msg.recipient_count + ' recipient' + (msg.recipient_count > 1 ? 's' : '');
    document.getElementById('modalReadCount').textContent = msg.recipient_count + ' sent';
    document.getElementById('modalDate').textContent = new Date(msg.created_at).toLocaleString();
    document.getElementById('modalMessage').innerHTML = msg.message.replace(/\n/g, '<br>');
    
    if (msg.recipient_count <= 5) {
        document.getElementById('modalRecipientList').innerHTML = '<strong>Sent to:</strong> ' + msg.recipients;
    } else {
        document.getElementById('modalRecipientList').innerHTML = '<strong>Sent to:</strong> ' + msg.recipient_count + ' recipients';
    }
    
    // Show modal
    modal.classList.remove('hidden');
    
    // Prevent body scroll
    document.body.style.overflow = 'hidden';
}

// Close message modal
function closeMessageModal() {
    const modal = document.getElementById('messageModal');
    modal.classList.add('hidden');
    
    // Restore body scroll
    document.body.style.overflow = 'auto';
}

// Close modal when clicking outside
document.getElementById('messageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeMessageModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeMessageModal();
    }
});

// Load more messages functionality
let currentMessagesPage = 1;
const messagesPerPage = 20; // Increased from 10 to 20 for better performance with large datasets
const allMessages = <?php echo json_encode($messages); ?>;

function loadMoreMessages() {
    currentMessagesPage++;
    const startIndex = (currentMessagesPage - 1) * messagesPerPage;
    const endIndex = startIndex + messagesPerPage;
    const newMessages = allMessages.slice(startIndex, endIndex);
    
    const container = document.getElementById('messagesContainer');
    
    // Remove existing button if it exists
    const existingButton = document.getElementById('seeMoreButton');
    if (existingButton) {
        existingButton.remove();
    }
    
    // Add new messages to container
    newMessages.forEach((msg, index) => {
        const actualIndex = startIndex + index;
        const messageHtml = `
            <div class="cursor-pointer hover:bg-gray-50 p-3 rounded-md transition-colors" onclick="showMessageModal(${actualIndex})">
                <div class="flex items-start justify-between mb-2">
                    <h4 class="text-sm font-medium text-gray-900">${msg.subject}</h4>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                        ${msg.recipient_count} recipient${msg.recipient_count > 1 ? 's' : ''}
                    </span>
                </div>
                <p class="text-sm text-gray-600 mb-2 line-clamp-2">${msg.message.substring(0, 100)}...</p>
                <div class="flex justify-between items-center text-xs text-gray-500">
                    <span>To: ${msg.recipient_count <= 5 ? msg.recipients : msg.recipient_count + ' recipients'}</span>
                    <span>${new Date(msg.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' })}</span>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', messageHtml);
    });
    
    // Hide button after loading
    buttonJustHidden = true;
    console.log('Button hidden after loading messages');
    
    // Reset the flag after a delay to allow scroll detection again
    setTimeout(() => {
        buttonJustHidden = false;
        console.log('Button can now be shown again on scroll');
    }, 2000);
    
    // Set up scroll listener to show button when near end
    setupScrollListener();
}

let buttonJustHidden = false;

function setupScrollListener() {
    const container = document.getElementById('messagesContainer');
    
    if (!container) {
        console.log('Container not found');
        return;
    }
    
    console.log('Container found, setting up scroll listener');
    
    let scrollTimeout;
    
    function showButtonIfNeeded() {
        const remainingMessages = allMessages.length - (currentMessagesPage * messagesPerPage);
        const existingButton = document.getElementById('seeMoreButton');
        
        console.log('Checking if button needed:', { remainingMessages, hasButton: !!existingButton });
        
        if (remainingMessages > 0 && !existingButton) {
            const buttonHtml = `
                <div class="text-center pt-4">
                    <button id="seeMoreButton" onclick="loadMoreMessages()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                        See More (${remainingMessages} remaining)
                    </button>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', buttonHtml);
            console.log('Button created with', remainingMessages, 'remaining');
        }
    }
    
    // Show button after any scroll activity
    container.addEventListener('scroll', function() {
        clearTimeout(scrollTimeout);
        scrollTimeout = setTimeout(() => {
            if (!buttonJustHidden) {
                showButtonIfNeeded();
            }
        }, 500);
    });
    
    // Show button immediately on load
    setTimeout(showButtonIfNeeded, 1000);
}

// Clean up any existing buttons on page load
function cleanupExistingButtons() {
    const container = document.getElementById('messagesContainer');
    if (container) {
        const existingButtons = container.querySelectorAll('#seeMoreButton');
        existingButtons.forEach(button => {
            if (button.parentElement) {
                button.parentElement.remove();
            }
        });
        console.log('Cleaned up existing buttons');
    }
}

// Initialize scroll listener on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Setting up scroll listener');
    cleanupExistingButtons();
    
    // Add a small delay to ensure DOM is fully ready
    setTimeout(() => {
        setupScrollListener();
    }, 500);
});
</script>
