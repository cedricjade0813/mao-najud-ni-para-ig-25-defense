<?php

if (

    $_SERVER['REQUEST_METHOD'] === 'POST' &&

    isset($_POST['action']) &&

    (

        ($_POST['action'] === 'reschedule' && isset($_POST['name'], $_POST['oldDate'], $_POST['oldTime'], $_POST['reason'], $_POST['newDate'], $_POST['newTime'])) ||

        (in_array($_POST['action'], ['approve', 'decline']) && isset($_POST['date'], $_POST['time'], $_POST['reason'], $_POST['name'])) ||

        ($_POST['action'] === 'add_doctor' && isset($_POST['doctor_name'], $_POST['doctor_date'], $_POST['doctor_time'])) ||

        ($_POST['action'] === 'delete_schedule' && isset($_POST['id'])) ||
        ($_POST['action'] === 'edit_schedule' && isset($_POST['id'], $_POST['doctor_name'], $_POST['doctor_date'], $_POST['doctor_time']))

    )

) {

    include '../includes/db_connect.php';



    // Initialize MySQLi connection for compatibility

    $conn = new mysqli('localhost', 'root', '', 'clinic_management_system');

    if ($conn->connect_errno) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection failed']);
        exit;
    }
    
    // Set timezone to Philippines for consistent date handling
    $conn->query("SET time_zone = '+08:00'");



    // Create doctor_schedules table if not exists

    $db->exec("CREATE TABLE IF NOT EXISTS doctor_schedules (

        id INT AUTO_INCREMENT PRIMARY KEY,

        doctor_name VARCHAR(255) NOT NULL,

        schedule_date DATE NOT NULL,

        schedule_time VARCHAR(100) NOT NULL,

        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

        UNIQUE KEY unique_schedule (doctor_name, schedule_date, schedule_time)

    )");



    $action = $_POST['action'];



    if ($action === 'add_doctor') {

        $doctor_name = trim($_POST['doctor_name']);

        $profession = trim($_POST['profession']);

        $doctor_date = $_POST['doctor_date'];

        $doctor_time = $_POST['doctor_time'];

        // Validate date - prevent yesterday's date
        $selected_date = new DateTime($doctor_date);
        $today = new DateTime();
        $today->setTime(0, 0, 0); // Reset time to start of day
        
        if ($selected_date < $today) {
            echo json_encode(['success' => false, 'error' => 'Cannot add doctor schedule for past dates. Please select today or a future date.']);
            exit;
        }

        if ($doctor_name && $profession && $doctor_date && $doctor_time) {

            // Parse the time range (e.g., "09:00-14:00")

            $time_parts = explode('-', $doctor_time);

            if (count($time_parts) === 2) {

                $start_time = trim($time_parts[0]);

                $end_time = trim($time_parts[1]);



                // Convert to DateTime objects for easier manipulation

                $start_datetime = DateTime::createFromFormat('H:i', $start_time);

                $end_datetime = DateTime::createFromFormat('H:i', $end_time);



                if ($start_datetime && $end_datetime && $start_datetime < $end_datetime) {

                    // Create only 1 doctor schedule entry with the full time range

                    $full_time_range = $start_time . '-' . $end_time;



                    $stmt = $db->prepare('INSERT INTO doctor_schedules (doctor_name, profession, schedule_date, schedule_time) VALUES (?, ?, ?, ?)');

                    $success = $stmt->execute([$doctor_name, $profession, $doctor_date, $full_time_range]);

                    $schedule_id = $db->lastInsertId(); // Get the ID of the newly inserted schedule



                    if ($success) {

                        echo json_encode([

                            'success' => true,

                            'message' => 'Doctor schedule added successfully! This creates 10 appointment slots.',

                            'schedule_id' => $schedule_id

                        ]);

                    } else {

                        echo json_encode(['success' => false, 'error' => 'Failed to add doctor schedule']);

                    }

                    exit;

                } else {

                    echo json_encode(['success' => false, 'error' => 'Invalid time range']);

                    exit;

                }

            } else {

                echo json_encode(['success' => false, 'error' => 'Invalid time format']);

                exit;

            }

        } else {

            echo json_encode(['success' => false, 'error' => 'All fields are required']);

            exit;

        }

    }



    if ($action === 'delete_schedule') {
        // Set proper headers
        header('Content-Type: application/json');

        $schedule_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($schedule_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid schedule ID']);
            exit;
        }

        try {
            $stmt = $db->prepare('DELETE FROM doctor_schedules WHERE id = ?');
            $success = $stmt->execute([$schedule_id]);

            if ($success && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Schedule deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Schedule not found or already deleted']);
            }

        } catch (Exception $e) {
            error_log('Delete schedule error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error occurred']);
        }

        exit;
    }

    if ($action === 'edit_schedule') {
        // Set proper headers
        header('Content-Type: application/json');
        
        $schedule_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $doctor_name = trim($_POST['doctor_name']);
        $doctor_date = $_POST['doctor_date'];
        $doctor_time = $_POST['doctor_time'];

        // Debug logging
        error_log('Edit schedule request - ID: ' . $schedule_id . ', Name: ' . $doctor_name . ', Date: ' . $doctor_date . ', Time: ' . $doctor_time);

        if ($schedule_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid schedule ID: ' . $schedule_id]);
            exit;
        }

        if (empty($doctor_name) || empty($doctor_date) || empty($doctor_time)) {
            echo json_encode(['success' => false, 'error' => 'All fields are required']);
        exit;
        }

        try {
            // First check if the schedule exists
            $checkStmt = $db->prepare('SELECT * FROM doctor_schedules WHERE id = ?');
            $checkStmt->execute([$schedule_id]);
            $existingSchedule = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$existingSchedule) {
                echo json_encode(['success' => false, 'error' => 'Schedule not found with ID: ' . $schedule_id]);
                exit;
            }

            // Compare existing data with new data
            $changes = [];
            if ($existingSchedule['doctor_name'] !== $doctor_name) {
                $changes[] = 'doctor_name: "' . $existingSchedule['doctor_name'] . '" -> "' . $doctor_name . '"';
            }
            if ($existingSchedule['schedule_date'] !== $doctor_date) {
                $changes[] = 'schedule_date: "' . $existingSchedule['schedule_date'] . '" -> "' . $doctor_date . '"';
            }
            if ($existingSchedule['schedule_time'] !== $doctor_time) {
                $changes[] = 'schedule_time: "' . $existingSchedule['schedule_time'] . '" -> "' . $doctor_time . '"';
            }

            if (empty($changes)) {
                echo json_encode(['success' => false, 'error' => 'No changes detected. All values are identical to existing data.']);
                exit;
            }

            // Log the changes
            error_log('Schedule changes: ' . implode(', ', $changes));

            $stmt = $db->prepare('UPDATE doctor_schedules SET doctor_name = ?, schedule_date = ?, schedule_time = ? WHERE id = ?');
            $success = $stmt->execute([$doctor_name, $doctor_date, $doctor_time, $schedule_id]);
            
            if ($success && $stmt->rowCount() > 0) {
                echo json_encode(['success' => true, 'message' => 'Schedule updated successfully']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Update failed - no rows affected']);
            }

        } catch (Exception $e) {
            error_log('Edit schedule error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'error' => 'Database error occurred: ' . $e->getMessage()]);
        }

        exit;
    }

    if ($action === 'reschedule') {

        // Validate newTime format (HH:mm or HH:mm:ss)

        $newTime = $_POST['newTime'];

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $newTime)) {

            echo json_encode(['success' => false, 'error' => 'Invalid time format. Please use HH:mm or HH:mm:ss.']);

            exit;

        }

        // Validate doctor_time format (HH:mm-HH:mm)

        $doctor_time = $_POST['doctor_time'];

        $time_parts = explode('-', $doctor_time);

        if (count($time_parts) === 2) {

            $start_time = trim($time_parts[0]);

            $end_time = trim($time_parts[1]);

            if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $start_time) || !preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $end_time)) {

                echo json_encode(['success' => false, 'error' => 'Invalid doctor time format. Please use HH:mm-HH:mm.']);

                exit;

            }

        }

        // Only update Date and Time, never Reason

        $name = $_POST['name'];

        $oldDate = $_POST['oldDate'];

        $oldTime = $_POST['oldTime'];

        $reason = $_POST['reason']; // Used only for identifying the appointment, not for updating

        $newDate = $_POST['newDate'];

        $newTime = $_POST['newTime'];

        $stmt = $conn->prepare('SELECT a.email, ip.id FROM appointments a JOIN imported_patients ip ON a.student_id = ip.id WHERE ip.name = ? AND a.date = ? AND a.time = ? AND a.reason = ? LIMIT 1');

        $stmt->bind_param('ssss', $name, $oldDate, $oldTime, $reason);

        $stmt->execute();

        $stmt->bind_result($email, $student_id);

        $stmt->fetch();

        $stmt->close();

        if ($student_id && $email) {

            // Only update date and time, not reason

            $stmt2 = $conn->prepare('UPDATE appointments SET date = ?, time = ?, status = ? WHERE student_id = ? AND date = ? AND time = ? AND reason = ?');

            $newStatus = 'rescheduled';

            $stmt2->bind_param('sssisss', $newDate, $newTime, $newStatus, $student_id, $oldDate, $oldTime, $reason);

            $success = $stmt2->execute();

            $stmt2->close();

            // Send email notification

            require_once __DIR__ . '/../mail.php';

            $subject = 'Your Appointment Has Been Rescheduled';

            $msg = "Dear $name,<br>Your appointment for '$reason' has been rescheduled to <b>$newDate</b> at <b>$newTime</b>.<br>If you have questions, please contact the clinic.";

            sendMail($email, $name, $subject, $msg);

            // Insert notification for the patient

            $notif_msg = "Your appointment for $reason has been <span class='text-blue-600 font-semibold'>rescheduled</span> to <b>$newDate</b> at <b>$newTime</b>.";

            $notif_type = 'appointment';

            $stmt3 = $conn->prepare('INSERT INTO notifications (student_id, message, type, created_at) VALUES (?, ?, ?, NOW())');

            $stmt3->bind_param('iss', $student_id, $notif_msg, $notif_type);

            $stmt3->execute();

            $stmt3->close();

            echo json_encode(['success' => $success]);

            exit;

        } else {

            echo json_encode(['success' => false, 'error' => 'Patient not found']);

            exit;

        }

    }

    // Approve/Decline logic

    $date = $_POST['date'];

    $time = $_POST['time'];

    $reason = $_POST['reason'];

    $name = $_POST['name'];



    // Get student_id from imported_patients

    $stmt = $conn->prepare('SELECT id FROM imported_patients WHERE name = ? LIMIT 1');

    $stmt->bind_param('s', $name);

    $stmt->execute();

    $stmt->bind_result($student_id);

    $stmt->fetch();

    $stmt->close();



    if ($student_id) {

        if ($action === 'approve' || $action === 'decline') {

            $status = $action === 'approve' ? 'approved' : 'declined';

            $stmt = $conn->prepare('UPDATE appointments SET status = ? WHERE student_id = ? AND date = ? AND time = ? AND reason = ?');

            $stmt->bind_param('sisss', $status, $student_id, $date, $time, $reason);

            $stmt->execute();

            $stmt->close();



            // Insert notification

            $notif_msg = $status === 'approved'

                ? "Your appointment for $date $time has been <span class='text-green-600 font-semibold'>approved</span>."

                : "Your appointment for $date $time has been <span class='text-red-600 font-semibold'>declined</span>.";

            $notif_type = 'appointment';

            $conn->query("INSERT INTO notifications (student_id, message, type, created_at) VALUES ($student_id, '" . $conn->real_escape_string($notif_msg) . "', '$notif_type', NOW())");



            echo json_encode(['success' => true]);

        } else {

            echo json_encode(['success' => false, 'error' => 'Invalid action']);

        }

    } else {

        echo json_encode(['success' => false, 'error' => 'Student not found']);

    }

    $conn->close();

    exit;

}

?>

<?php

include '../includes/header.php';

?>



<style>

    /* Ensure consistent table layout across all pages */

    .appointment-table {

        table-layout: fixed !important;

        width: 100% !important;

    }



    .appointment-table th,

    .appointment-table td {

        width: 16.666667% !important;

        /* 1/6 = 16.666667% */

        overflow: hidden !important;

        text-overflow: ellipsis !important;

        white-space: nowrap !important;

    }



    /* Ensure consistent column widths for all appointment tables */

    #pendingAppointmentsTable,

    #doneAppointmentsTable,

    #reschedAppointmentsTable,

    #declinedAppointmentsTable {

        table-layout: fixed !important;

    }



    #pendingAppointmentsTable th,

    #pendingAppointmentsTable td,

    #doneAppointmentsTable th,

    #doneAppointmentsTable td,

    #reschedAppointmentsTable th,

    #reschedAppointmentsTable td,

    #declinedAppointmentsTable th,

    #declinedAppointmentsTable td {

        width: 16.666667% !important;

        overflow: hidden !important;

        text-overflow: ellipsis !important;

        white-space: nowrap !important;

    }

</style>



<?php

$conn = new mysqli('localhost', 'root', '', 'clinic_management_system');

if ($conn->connect_errno) {

    die('Database connection failed: ' . $conn->connect_error);

}

// Set timezone to Philippines for consistent date handling
$conn->query("SET time_zone = '+08:00'");



// Create doctor_schedules table if not exists

$conn->query("CREATE TABLE IF NOT EXISTS doctor_schedules (

    id INT AUTO_INCREMENT PRIMARY KEY,

    doctor_name VARCHAR(255) NOT NULL,

    schedule_date DATE NOT NULL,

    schedule_time VARCHAR(100) NOT NULL,

    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY unique_schedule (doctor_name, schedule_date, schedule_time)

)");



$appointments = [];



// Doctor name is now properly fetched from doctor_schedules table via JOIN



// Use only columns that exist in the appointments table

$sql = 'SELECT a.date, a.time, a.reason, a.status, a.email, ip.name, 

        COALESCE(ds.doctor_name, "Dr. Sarah Johnson") as doctor_name 

        FROM appointments a 

        JOIN imported_patients ip ON a.student_id = ip.id 

        LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id

        ORDER BY a.date DESC, a.time DESC';

$result = $conn->query($sql);

if ($result) {

    while ($row = $result->fetch_assoc()) {

        $appointments[] = $row;

    }

    $result->free();

}



// Fetch doctor schedules

// Fetch doctor schedules (include id for delete button)

$doctor_schedules = [];

$schedule_sql = "SELECT id, doctor_name, profession, schedule_date, schedule_time FROM doctor_schedules WHERE schedule_date >= CURDATE() ORDER BY schedule_date ASC, schedule_time ASC";

$schedule_result = $conn->query($schedule_sql);

if ($schedule_result) {

    while ($row = $schedule_result->fetch_assoc()) {

        $doctor_schedules[] = $row;

    }

    $schedule_result->free();

}



$conn->close();

?>

<!-- Dashboard Content -->



<!-- Appointments Content -->

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">

    <!-- Header Section -->

    <div class="mb-8">

        <h1 class="text-3xl font-bold text-gray-900 mb-2">Appointments</h1>

        <p class="text-gray-600">Manage doctor schedules and appointments efficiently</p>

    </div>



    <!-- Navigation Tabs -->

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">

        <nav class="flex space-x-8 px-6 py-3" role="tablist">

            <button type="button" class="staff-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-tab="doctor-schedules">

                Doctor Schedules

            </button>

            <button type="button" class="staff-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-tab="calendar-view">

                Calendar View

            </button>

            <button type="button" class="staff-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-tab="appointments">

                Appointments

            </button>

        </nav>

    </div>





    <!-- Doctor Schedules Section -->

    <div id="doctorSchedulesSection" class="hidden">

        <!-- Add Doctor Schedule Form -->

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">

            <div class="flex items-center space-x-2 mb-6">

                <h3 class="text-xl font-semibold text-gray-800">Add Doctor Schedule</h3>

            </div>

            <form id="addDoctorForm" class="space-y-4">

                <div class="grid grid-cols-6 gap-4 items-end">

                <div>

                        <label for="doctor_name" class="block text-sm font-medium text-gray-700 mb-2">Doctor</label>

                        <input type="text" id="doctor_name" name="doctor_name" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="Select a doctor" required>

                </div>

                <div>

                        <label for="profession" class="block text-sm font-medium text-gray-700 mb-2">Select Profession</label>

                        <select id="profession" name="profession" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors h-10" required>

                            <option value="">Select Profession</option>

                            <option value="Physician">Physician</option>

                            <option value="Dentist">Dentist</option>

                        </select>

                </div>

                <div>

                        <label for="doctor_date" class="block text-sm font-medium text-gray-700 mb-2">Date</label>

                        <input type="date" id="doctor_date" name="doctor_date" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" min="<?php date_default_timezone_set('Asia/Manila'); echo date('Y-m-d'); ?>" required>

                </div>

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Time</label>

                        <input type="time" id="doctor_time_start" name="doctor_time_start" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="--:--" required>

                    </div>

                    <div>

                        <label class="block text-sm font-medium text-gray-700 mb-2">End Time</label>

                        <input type="time" id="doctor_time_end" name="doctor_time_end" class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors" placeholder="--:--" required>

                    </div>

                    <div>

                        <button type="submit" class="w-full bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 font-semibold transition-colors flex items-center justify-center space-x-2">

                    <i class="ri-add-line"></i>

                    <span>Add Schedule</span>

                </button>

                    </div>

                </div>

            </form>

        </div>



        <!-- Doctor Schedules Table -->

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">

            <!-- Header Section -->

            <div class="px-4 py-3 border-b border-gray-200">

                <div class="flex justify-between items-center">

                    <div>

                        <h3 class="text-lg font-semibold text-gray-900">Doctor Schedules</h3>

                        <p class="text-gray-600 text-xs mt-1">Current doctor availability and schedules</p>

                    </div>

                    <!-- Search Bar -->

                    <div class="relative">

                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">

                            <i class="ri-search-line text-gray-400"></i>

                        </div>

                        <input type="text" id="doctorScheduleSearch" class="block w-80 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Search schedules...">

                    </div>

                </div>

            </div>



            <!-- Doctor Schedules Table with Pagination -->

            <?php

            // Pagination for Doctor Schedules

            $ds_records_per_page = 10;

            $ds_page = isset($_GET['ds_page']) ? (int)$_GET['ds_page'] : 1;

            $ds_page = max($ds_page, 1);

            $ds_offset = ($ds_page - 1) * $ds_records_per_page;

            $conn = new mysqli('localhost', 'root', '', 'clinic_management_system');

            if ($conn->connect_errno) {

                die('Database connection failed: ' . $conn->connect_error);

            }
            
            // Set timezone to Philippines for consistent date handling
            $conn->query("SET time_zone = '+08:00'");

            $ds_total_count_stmt = $conn->query("SELECT COUNT(*) FROM doctor_schedules WHERE schedule_date >= CURDATE()");

            $ds_total_records = $ds_total_count_stmt->fetch_row()[0];

            $ds_total_pages = ceil($ds_total_records / $ds_records_per_page);

            $ds_stmt = $conn->prepare("SELECT id, doctor_name, profession, schedule_date, schedule_time FROM doctor_schedules WHERE schedule_date >= CURDATE() ORDER BY schedule_date ASC, schedule_time ASC LIMIT ? OFFSET ?");

            $ds_stmt->bind_param('ii', $ds_records_per_page, $ds_offset);

            $ds_stmt->execute();

            $ds_result = $ds_stmt->get_result();

            ?>



            <?php if ($ds_total_records > 0): ?>

                <!-- Table View -->

                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-gray-200" id="doctorScheduleTable">

                        <thead class="bg-gray-50">

                            <tr>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DOCTOR</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">PROFESSION</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DATE</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">START TIME</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">END TIME</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STATUS</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ACTIONS</th>

                            </tr>

                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200" id="doctorScheduleTableBody">

                            <?php while ($schedule = $ds_result->fetch_assoc()): ?>

                                <tr class="doctor-schedule-row" data-doctor="<?= strtolower(htmlspecialchars($schedule['doctor_name'])) ?>" data-date="<?= strtolower(htmlspecialchars($schedule['schedule_date'])) ?>">

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($schedule['doctor_name']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($schedule['profession'] ?? 'Physician'); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <div class="text-sm text-gray-900"><?php echo date('D, M j, Y', strtotime($schedule['schedule_date'])); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <div class="text-sm text-gray-900"><?php echo date('g:i A', strtotime(explode('-', $schedule['schedule_time'])[0])); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <div class="text-sm text-gray-900"><?php echo date('g:i A', strtotime(explode('-', $schedule['schedule_time'])[1])); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">

                                            Available

                                        </span>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <div class="flex items-center space-x-2">

                                            <button class="edit-schedule-btn p-1.5 text-gray-400 hover:text-blue-600 transition-colors" 
                                                data-id="<?php echo $schedule['id']; ?>" 
                                                data-doctor="<?php echo htmlspecialchars($schedule['doctor_name']); ?>"
                                                data-date="<?php echo $schedule['schedule_date']; ?>"
                                                data-time="<?php echo htmlspecialchars($schedule['schedule_time']); ?>"
                                                title="Edit">
                                                <i class="ri-edit-line text-sm"></i>
                                            </button>

                                            <button class="delete-schedule-btn p-1.5 text-gray-400 hover:text-red-600 transition-colors"

                                                data-id="<?php echo $schedule['id']; ?>" title="Delete">

                                                <i class="ri-delete-bin-line text-sm"></i>

                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>



                <!-- Pagination -->

                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">

                    <div class="flex justify-between items-center">

                        <!-- Records Information -->

                        <div class="text-xs text-gray-500">

                            Showing <span id="dsShowingStart"><?php echo $ds_offset + 1; ?></span> to <span id="dsShowingEnd"><?php echo min($ds_offset + $ds_records_per_page, $ds_total_records); ?></span> of <span id="dsTotalEntries"><?php echo $ds_total_records; ?></span> entries

                        </div>



                        <!-- Pagination Navigation -->

                        <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">

                            <!-- Previous Button -->

                            <?php if ($ds_page > 1): ?>

                                <a href="?ds_page=<?php echo $ds_page - 1; ?>" class="min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">

                                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                                        <path d="m15 18-6-6 6-6"></path>

                                    </svg>

                                    <span class="sr-only">Previous</span>

                                </a>

                            <?php else: ?>

                                <button type="button" disabled class="min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Previous">

                                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                                        <path d="m15 18-6-6 6-6"></path>

                                    </svg>

                                    <span class="sr-only">Previous</span>

                                </button>

                            <?php endif; ?>



                            <!-- Page Numbers -->

                            <div class="flex items-center">

                                <?php

                                $ds_start_page = max(1, $ds_page - 2);

                                $ds_end_page = min($ds_total_pages, $ds_page + 2);

                                if ($ds_start_page > 1): ?>

                                    <a href="?ds_page=1" class="min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-100">1</a>

                                    <?php if ($ds_start_page > 2): ?>

                                        <span class="min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 py-1 px-2 text-xs">...</span>

                                    <?php endif; ?>

                                <?php endif; ?>

                                <?php for ($i = $ds_start_page; $i <= $ds_end_page; $i++): ?>

                                    <?php if ($i == $ds_page): ?>

                                        <button type="button" class="min-h-8 min-w-8 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-400" aria-current="page"><?php echo $i; ?></button>

                                    <?php else: ?>

                                        <a href="?ds_page=<?php echo $i; ?>" class="min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>

                                    <?php endif; ?>

                                <?php endfor; ?>

                                <?php if ($ds_end_page < $ds_total_pages): ?>

                                    <?php if ($ds_end_page < $ds_total_pages - 1): ?>

                                        <span class="min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 py-1 px-2 text-xs">...</span>

                                    <?php endif; ?>

                                    <a href="?ds_page=<?php echo $ds_total_pages; ?>" class="min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-100"><?php echo $ds_total_pages; ?></a>

                                <?php endif; ?>

                            </div>



                            <!-- Next Button -->

                            <?php if ($ds_page < $ds_total_pages): ?>

                                <a href="?ds_page=<?php echo $ds_page + 1; ?>" class="min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">

                                    <span class="sr-only">Next</span>

                                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                                        <path d="m9 18 6-6-6-6"></path>

                                    </svg>

                                </a>

                            <?php else: ?>

                                <button type="button" disabled class="min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none" aria-label="Next">

                                    <span class="sr-only">Next</span>

                                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                                        <path d="m9 18 6-6-6-6"></path>

                                    </svg>

                                </button>

                            <?php endif; ?>

                        </nav>

                    </div>

                </div>

            <?php else: ?>

                <!-- Empty State -->

                <div class="px-4 py-12 text-center text-gray-500">

                    <i class="ri-calendar-line text-4xl mb-2 block"></i>

                    No doctor schedules found.

                </div>

            <?php endif; ?>

        </div>

        <?php

        $ds_stmt->close();

        $conn->close();

        ?>

    </div>



    <!-- Calendar View Section -->

    <div id="calendarSection" class="hidden">

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

                    <span id="calendarMonth" class="font-semibold text-lg text-gray-800 min-w-[120px] text-center">September 2025</span>

                    <button id="nextMonthBtn" class="p-2 text-gray-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">

                        <i class="ri-arrow-right-s-line text-lg"></i>

                    </button>

                </div>

            </div>



            <!-- Days of the week header -->

            



            <div id="calendarGrid" class="grid grid-cols-7 gap-1 text-center text-sm">

                <!-- Calendar will be rendered here by JS -->

            </div>



            <!-- Legend -->

            <div class="flex items-center justify-start space-x-6 mt-6 pt-6 border-t border-gray-200">

                <div class="flex items-center space-x-2">

                    <div class="w-3 h-3 rounded-full bg-green-100 border border-green-300"></div>

                    <span class="text-sm text-gray-600">Approved</span>

                </div>

                <div class="flex items-center space-x-2">

                    <div class="w-3 h-3 rounded-full bg-yellow-100 border border-yellow-300"></div>

                    <span class="text-sm text-gray-600">Pending</span>

                </div>

                <div class="flex items-center space-x-2">

                    <div class="w-3 h-3 rounded-full bg-red-100 border border-red-300"></div>

                    <span class="text-sm text-gray-600">Declined</span>

                </div>

                <div class="flex items-center space-x-2">

                    <div class="w-3 h-3 rounded-full bg-blue-100 border border-blue-300"></div>

                    <span class="text-sm text-gray-600">Rescheduled</span>

                </div>

            </div>



            <!-- Footer -->



        </div>

    </div>



    <!-- Appointments Section -->

    <div id="appointmentsSection" class="hidden">



        <!-- Pending Appointments Table with Pagination -->

        <?php

        // Pagination for Pending Appointments

        $pending_records_per_page = 10;

        $pending_page = isset($_GET['pending_page']) ? (int)$_GET['pending_page'] : 1;

        $pending_page = max($pending_page, 1);

        $pending_offset = ($pending_page - 1) * $pending_records_per_page;

        $conn = new mysqli('localhost', 'root', '', 'clinic_management_system');

        if ($conn->connect_errno) {

            die('Database connection failed: ' . $conn->connect_error);

        }
        
        // Set timezone to Philippines for consistent date handling
        $conn->query("SET time_zone = '+08:00'");

        // Set timezone to Philippines and get current date
        date_default_timezone_set('Asia/Manila');
        $currentDate = date('Y-m-d');
        $pending_total_count_stmt = $conn->query("SELECT COUNT(*) FROM (
            SELECT a.id FROM appointments a 
            JOIN imported_patients ip ON a.student_id = ip.id 
            WHERE a.status = 'pending' AND a.date = '$currentDate'
            UNION ALL
            SELECT a.id FROM appointments a 
            JOIN faculty f ON a.faculty_id = f.faculty_id 
            WHERE a.status = 'pending' AND a.date = '$currentDate'
        ) as combined_appointments");

        $pending_total_records = $pending_total_count_stmt->fetch_row()[0];

        $pending_total_pages = ceil($pending_total_records / $pending_records_per_page);

        $pending_stmt = $conn->prepare("SELECT a.date, a.time, a.reason, a.status, a.email, 
                                        COALESCE(ip.name, f.full_name) as name, 
                                        ds.doctor_name,
                                        CASE WHEN a.student_id IS NOT NULL THEN 'Student' ELSE 'Faculty' END as user_type
                                        FROM appointments a 
                                        LEFT JOIN imported_patients ip ON a.student_id = ip.id 
                                        LEFT JOIN faculty f ON a.faculty_id = f.faculty_id 
                                        LEFT JOIN doctor_schedules ds ON a.doctor_id = ds.id 
                                        WHERE a.status = 'pending' AND a.date = ?
                                        ORDER BY a.date DESC, a.time DESC 
                                        LIMIT ? OFFSET ?");
        $pending_stmt->bind_param('sii', $currentDate, $pending_records_per_page, $pending_offset);

        $pending_stmt->execute();

        $pending_result = $pending_stmt->get_result();

        ?>

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



            <!-- Appointment Status Filter Tabs -->

            <div class="px-6 py-3 border-b border-gray-200">

                <nav class="flex space-x-8">

                    <button type="button" class="appt-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="pending">

                        Pending (<span id="pendingCount">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="approved">

                        Approved (<span id="approvedCount">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="declined">

                        Declined (<span id="declinedCount">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="rescheduled">

                        Rescheduled (<span id="rescheduledCount">0</span>)

                    </button>

                </nav>

            </div>



            <?php if ($pending_result->num_rows > 0): ?>

                <!-- Table View -->

                <div class="overflow-x-auto">

                    <table id="pendingAppointmentsTable" class="min-w-full divide-y divide-gray-200 appointment-table">

                        <thead class="bg-gray-50">

                            <tr>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">PATIENT</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">CONTACT</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DOCTOR</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DATE & TIME</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">REASON</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">ACTIONS</th>

                            </tr>

                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">

                            <?php while ($appt = $pending_result->fetch_assoc()): ?>

                                <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer transition-colors pending-appointment-row"

                                    data-name="<?= strtolower(htmlspecialchars($appt['name'])) ?>"

                                    data-date="<?= strtolower(htmlspecialchars($appt['date'])) ?>"

                                    data-time="<?= strtolower(htmlspecialchars($appt['time'])) ?>"

                                    data-reason="<?= strtolower(htmlspecialchars($appt['reason'])) ?>"

                                    data-email="<?= strtolower(htmlspecialchars($appt['email'])) ?>"

                                    data-status="pending">

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($appt['name']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo htmlspecialchars($appt['email']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <?php 
                                        $doctorName = $appt['doctor_name'] ?? 'Dr. Medical Officer';
                                        if ($doctorName !== 'Dr. Medical Officer' && !empty($doctorName)) {
                                            if (!str_starts_with($doctorName, 'Dr.')) {
                                                $doctorName = 'Dr. ' . ucfirst($doctorName);
                                            }
                                        }
                                        ?>
                                        <div class="text-sm text-gray-900 truncate"><?php echo htmlspecialchars($doctorName); ?></div>
                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo date('D, M j, Y', strtotime($appt['date'])); ?></div>

                                        <div class="text-xs text-gray-500 truncate"><?php echo date('g:i A', strtotime($appt['time'])); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo htmlspecialchars($appt['reason']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <div class="flex items-center space-x-1">

                                            <button class="approveBtn p-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors" title="Approve">

                                                <i class="ri-check-line text-sm"></i>

                                            </button>

                                            <button class="declineBtn p-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors" title="Decline">

                                                <i class="ri-close-line text-sm"></i>

                                            </button>

                                            <button class="reschedBtn p-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors" title="Reschedule">

                                                <i class="ri-refresh-line text-sm"></i>

                                            </button>

                                        </div>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>



                <!-- Pagination -->

                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">

                    <div class="flex justify-between items-center">

                        <!-- Records Information -->

                        <div class="text-xs text-gray-500">

                            Showing <span id="pendingShowingStart"><?php echo $pending_offset + 1; ?></span> to <span id="pendingShowingEnd"><?php echo min($pending_offset + $pending_records_per_page, $pending_total_records); ?></span> of <span id="pendingTotalEntries"><?php echo $pending_total_records; ?></span> entries

                        </div>



                        <!-- Pagination Navigation -->

                        <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">

                            <!-- Previous Button -->

                            <?php if ($pending_page > 1): ?>

                                <a href="?pending_page=<?php echo $pending_page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">

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

                            <div class="flex items-center">

                                <?php

                                $pending_start_page = max(1, $pending_page - 2);

                                $pending_end_page = min($pending_total_pages, $pending_page + 2);

                                if ($pending_start_page > 1): ?>

                                    <a href="?pending_page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100">1</a>

                                    <?php if ($pending_start_page > 2): ?>

                                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm">...</span>

                                    <?php endif; ?>

                                <?php endif; ?>

                                <?php for ($i = $pending_start_page; $i <= $pending_end_page; $i++): ?>

                                    <?php if ($i == $pending_page): ?>

                                        <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-400" aria-current="page"><?php echo $i; ?></button>

                                    <?php else: ?>

                                        <a href="?pending_page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>

                                    <?php endif; ?>

                                <?php endfor; ?>

                                <?php if ($pending_end_page < $pending_total_pages): ?>

                                    <?php if ($pending_end_page < $pending_total_pages - 1): ?>

                                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm">...</span>

                                    <?php endif; ?>

                                    <a href="?pending_page=<?php echo $pending_total_pages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $pending_total_pages; ?></a>

                                <?php endif; ?>

                            </div>



                            <!-- Next Button -->

                            <?php if ($pending_page < $pending_total_pages): ?>

                                <a href="?pending_page=<?php echo $pending_page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">

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

                    </div>

                </div>

            <?php else: ?>

                <!-- Empty State -->

                <div class="px-4 py-12 text-center text-gray-500">

                    <i class="ri-time-line text-4xl mb-2 block"></i>

                    No pending appointments found.

                </div>

            <?php endif; ?>

        </div>



        <?php

        $pending_stmt->close();

        $conn->close();

        ?>



        <!-- Approved Appointments Table with Pagination -->

        <?php

        // Pagination for Done Appointments

        $done_records_per_page = 10;

        $done_page = isset($_GET['done_page']) ? (int)$_GET['done_page'] : 1;

        $done_page = max($done_page, 1);

        $done_offset = ($done_page - 1) * $done_records_per_page;

        $conn = new mysqli('localhost', 'root', '', 'clinic_management_system');

        if ($conn->connect_errno) {

            die('Database connection failed: ' . $conn->connect_error);

        }
        
        // Set timezone to Philippines for consistent date handling
        $conn->query("SET time_zone = '+08:00'");

        // Query for approved appointments only (approved and confirmed)

        $done_total_count_stmt = $conn->query("SELECT COUNT(*) FROM (
            SELECT a.id FROM appointments a 
            JOIN imported_patients ip ON a.student_id = ip.id 
            WHERE a.status IN ('approved', 'confirmed')
            UNION ALL
            SELECT a.id FROM appointments a 
            JOIN faculty f ON a.faculty_id = f.faculty_id 
            WHERE a.status IN ('approved', 'confirmed')
        ) as combined_appointments");

        $done_total_records = $done_total_count_stmt->fetch_row()[0];

        $done_total_pages = ceil($done_total_records / $done_records_per_page);

        $done_stmt = $conn->prepare("SELECT a.date, a.time, a.reason, a.status, a.email, 
                                    COALESCE(ip.name, f.full_name) as name,
                                    CASE WHEN a.student_id IS NOT NULL THEN 'Student' ELSE 'Faculty' END as user_type
                                    FROM appointments a 
                                    LEFT JOIN imported_patients ip ON a.student_id = ip.id 
                                    LEFT JOIN faculty f ON a.faculty_id = f.faculty_id 
                                    WHERE a.status IN ('approved', 'confirmed')
                                    ORDER BY a.date DESC, a.time DESC LIMIT ? OFFSET ?");

        $done_stmt->bind_param('ii', $done_records_per_page, $done_offset);

        $done_stmt->execute();

        $done_result = $done_stmt->get_result();



        // Query for declined appointments

        $declined_records_per_page = 10;

        $declined_page = isset($_GET['declined_page']) ? (int)$_GET['declined_page'] : 1;

        $declined_page = max($declined_page, 1);

        $declined_offset = ($declined_page - 1) * $declined_records_per_page;

        $declined_total_count_stmt = $conn->query("SELECT COUNT(*) FROM (
            SELECT a.id FROM appointments a 
            JOIN imported_patients ip ON a.student_id = ip.id 
            WHERE a.status = 'declined'
            UNION ALL
            SELECT a.id FROM appointments a 
            JOIN faculty f ON a.faculty_id = f.faculty_id 
            WHERE a.status = 'declined'
        ) as combined_appointments");

        $declined_total_records = $declined_total_count_stmt->fetch_row()[0];

        $declined_total_pages = ceil($declined_total_records / $declined_records_per_page);

        $declined_stmt = $conn->prepare("SELECT a.date, a.time, a.reason, a.status, a.email, 
                                        COALESCE(ip.name, f.full_name) as name,
                                        CASE WHEN a.student_id IS NOT NULL THEN 'Student' ELSE 'Faculty' END as user_type
                                        FROM appointments a 
                                        LEFT JOIN imported_patients ip ON a.student_id = ip.id 
                                        LEFT JOIN faculty f ON a.faculty_id = f.faculty_id 
                                        WHERE a.status = 'declined'
                                        ORDER BY a.date DESC, a.time DESC LIMIT ? OFFSET ?");

        $declined_stmt->bind_param('ii', $declined_records_per_page, $declined_offset);

        $declined_stmt->execute();

        $declined_result = $declined_stmt->get_result();

        ?>

        <div id="doneSection" class="bg-white rounded-lg shadow-sm border border-gray-200 appt-tab-section hidden">

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

                        <input type="text" id="doneSearchInput" class="block w-80 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Search appointments...">

                    </div>

                </div>

            </div>



            <!-- Appointment Status Filter Tabs -->

            <div class="px-6 py-3 border-b border-gray-200">

                <nav class="flex space-x-8">

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="pending">

                        Pending (<span id="pendingCountApproved">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="approved">

                        Approved (<span id="approvedCountApproved">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="declined">

                        Declined (<span id="declinedCountApproved">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="rescheduled">

                        Rescheduled (<span id="rescheduledCountApproved">0</span>)

                    </button>

                </nav>

            </div>



            <!-- Selected Done Appointment Details -->

            <div id="doneDetails" class="hidden mb-4 border border-gray-200 rounded-lg p-4 bg-gray-50">

                <h4 class="font-semibold text-gray-800 mb-2">Appointment Details</h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-700" id="doneDetailsBody"></div>

            </div>



            <?php if ($done_result->num_rows > 0): ?>

                <!-- Table View -->

                <div class="overflow-x-auto">

                    <table id="doneAppointmentsTable" class="min-w-full divide-y divide-gray-200 appointment-table">

                        <thead class="bg-gray-50">

                            <tr>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">PATIENT</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DATE</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">TIME</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">REASON</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">EMAIL</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">STATUS</th>

                            </tr>

                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">

                            <?php while ($appt = $done_result->fetch_assoc()): ?>

                                <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer done-appointment-row"

                                    data-name="<?= strtolower(htmlspecialchars($appt['name'])) ?>"

                                    data-date="<?= strtolower(htmlspecialchars($appt['date'])) ?>"

                                    data-time="<?= strtolower(htmlspecialchars($appt['time'])) ?>"

                                    data-reason="<?= strtolower(htmlspecialchars($appt['reason'])) ?>"

                                    data-email="<?= strtolower(htmlspecialchars($appt['email'])) ?>"

                                    data-status="<?= strtolower(htmlspecialchars($appt['status'])) ?>"

                                    style="height: 61px;">

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($appt['name']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo date('D, M j, Y', strtotime($appt['date'])); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo date('g:i A', strtotime($appt['time'])); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo htmlspecialchars($appt['reason']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo htmlspecialchars($appt['email']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <?php if ($appt['status'] === 'approved' || $appt['status'] === 'confirmed'): ?>

                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>

                                        <?php elseif ($appt['status'] === 'declined'): ?>

                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Declined</span>

                                        <?php else: ?>

                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800"><?php echo htmlspecialchars(ucfirst($appt['status'])); ?></span>

                                        <?php endif; ?>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>



                <!-- Pagination -->

                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">

                    <div class="flex justify-between items-center">

                        <!-- Records Information -->

                        <div class="text-xs text-gray-500">

                            Showing <span id="doneShowingStart"><?php echo $done_offset + 1; ?></span> to <span id="doneShowingEnd"><?php echo min($done_offset + $done_records_per_page, $done_total_records); ?></span> of <span id="doneTotalEntries"><?php echo $done_total_records; ?></span> entries

                        </div>



                        <!-- Pagination Navigation -->

                        <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">

                            <!-- Previous Button -->

                            <?php if ($done_page > 1): ?>

                                <a href="?done_page=<?php echo $done_page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">

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

                            <div class="flex items-center">

                                <?php

                                $done_start_page = max(1, $done_page - 2);

                                $done_end_page = min($done_total_pages, $done_page + 2);

                                if ($done_start_page > 1): ?>

                                    <a href="?done_page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100">1</a>

                                    <?php if ($done_start_page > 2): ?>

                                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm">...</span>

                                    <?php endif; ?>

                                <?php endif; ?>

                                <?php for ($i = $done_start_page; $i <= $done_end_page; $i++): ?>

                                    <?php if ($i == $done_page): ?>

                                        <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-400" aria-current="page"><?php echo $i; ?></button>

                                    <?php else: ?>

                                        <a href="?done_page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>

                                    <?php endif; ?>

                                <?php endfor; ?>

                                <?php if ($done_end_page < $done_total_pages): ?>

                                    <?php if ($done_end_page < $done_total_pages - 1): ?>

                                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm">...</span>

                                    <?php endif; ?>

                                    <a href="?done_page=<?php echo $done_total_pages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $done_total_pages; ?></a>

                                <?php endif; ?>

                            </div>



                            <!-- Next Button -->

                            <?php if ($done_page < $done_total_pages): ?>

                                <a href="?done_page=<?php echo $done_page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">

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

                    </div>

                </div>

            <?php else: ?>

                <!-- Empty State -->

                <div class="px-4 py-12 text-center text-gray-500">

                    <i class="ri-check-line text-4xl mb-2 block"></i>

                    No approved appointments found.

                </div>

            <?php endif; ?>

        </div>



        <?php

        $done_stmt->close();

        $conn->close();

        ?>



        <!-- Rescheduled Appointments Table with Pagination -->

        <?php

        // Pagination for Rescheduled Appointments

        $resched_records_per_page = 10;

        $resched_page = isset($_GET['resched_page']) ? (int)$_GET['resched_page'] : 1;

        $resched_page = max($resched_page, 1);

        $resched_offset = ($resched_page - 1) * $resched_records_per_page;

        $conn = new mysqli('localhost', 'root', '', 'clinic_management_system');

        if ($conn->connect_errno) {

            die('Database connection failed: ' . $conn->connect_error);

        }
        
        // Set timezone to Philippines for consistent date handling
        $conn->query("SET time_zone = '+08:00'");

        $currentTime = date('H:i:s');
        $resched_total_count_stmt = $conn->query("SELECT COUNT(*) FROM (
            SELECT a.id FROM appointments a 
            JOIN imported_patients ip ON a.student_id = ip.id 
            WHERE a.status = 'rescheduled' AND (a.date > '$currentDate' OR (a.date = '$currentDate' AND (
                (a.time NOT LIKE '%-%' AND ADDTIME(a.time, '01:00:00') >= '$currentTime') OR 
                (a.time LIKE '%-%' AND (
                    ADDTIME(SUBSTRING_INDEX(a.time, '-', 1), '01:00:00') >= '$currentTime' OR 
                    (SUBSTRING_INDEX(a.time, '-', 1) <= '$currentTime' AND ADDTIME(SUBSTRING_INDEX(a.time, '-', -1), '01:00:00') >= '$currentTime')
                ))
            )))
            UNION ALL
            SELECT a.id FROM appointments a 
            JOIN faculty f ON a.faculty_id = f.faculty_id 
            WHERE a.status = 'rescheduled' AND (a.date > '$currentDate' OR (a.date = '$currentDate' AND (
                (a.time NOT LIKE '%-%' AND ADDTIME(a.time, '01:00:00') >= '$currentTime') OR 
                (a.time LIKE '%-%' AND (
                    ADDTIME(SUBSTRING_INDEX(a.time, '-', 1), '01:00:00') >= '$currentTime' OR 
                    (SUBSTRING_INDEX(a.time, '-', 1) <= '$currentTime' AND ADDTIME(SUBSTRING_INDEX(a.time, '-', -1), '01:00:00') >= '$currentTime')
                ))
            )))
        ) as combined_appointments");

        $resched_total_records = $resched_total_count_stmt->fetch_row()[0];

        $resched_total_pages = ceil($resched_total_records / $resched_records_per_page);

        $resched_stmt = $conn->prepare("SELECT a.date, a.time, a.reason, a.status, a.email, 
                                        COALESCE(ip.name, f.full_name) as name,
                                        CASE WHEN a.student_id IS NOT NULL THEN 'Student' ELSE 'Faculty' END as user_type
                                        FROM appointments a 
                                        LEFT JOIN imported_patients ip ON a.student_id = ip.id 
                                        LEFT JOIN faculty f ON a.faculty_id = f.faculty_id 
                                        WHERE a.status = 'rescheduled' AND (a.date > ? OR (a.date = ? AND (
                                            (a.time NOT LIKE '%-%' AND ADDTIME(a.time, '01:00:00') >= ?) OR 
                                            (a.time LIKE '%-%' AND (
                                                ADDTIME(SUBSTRING_INDEX(a.time, '-', 1), '01:00:00') >= ? OR 
                                                (SUBSTRING_INDEX(a.time, '-', 1) <= ? AND ADDTIME(SUBSTRING_INDEX(a.time, '-', -1), '01:00:00') >= ?)
                                            ))
                                        )))
                                        ORDER BY a.date DESC, a.time DESC LIMIT ? OFFSET ?");

        $resched_stmt->bind_param('ssssssii', $currentDate, $currentDate, $currentTime, $currentTime, $currentTime, $currentTime, $resched_records_per_page, $resched_offset);

        $resched_stmt->execute();

        $resched_result = $resched_stmt->get_result();

        ?>

        <div id="reschedSection" class="bg-white rounded-lg shadow-sm border border-gray-200 appt-tab-section hidden">

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

                        <input type="text" id="reschedSearchInput" class="block w-80 pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-sm" placeholder="Search appointments...">

                    </div>

                </div>

            </div>



            <!-- Appointment Status Filter Tabs -->

            <div class="px-6 py-3 border-b border-gray-200">

                <nav class="flex space-x-8">

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="pending">

                        Pending (<span id="pendingCountRescheduled">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="approved">

                        Approved (<span id="approvedCountRescheduled">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="declined">

                        Declined (<span id="declinedCountRescheduled">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="rescheduled">

                        Rescheduled (<span id="rescheduledCountRescheduled">0</span>)

                    </button>

                </nav>

            </div>



            <!-- Selected Rescheduled Appointment Details -->

            <div id="reschedDetails" class="hidden mb-4 border border-gray-200 rounded-lg p-4 bg-gray-50">

                <h4 class="font-semibold text-gray-800 mb-2">Appointment Details</h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-3 text-sm text-gray-700" id="reschedDetailsBody"></div>

            </div>



            <?php if ($resched_result->num_rows > 0): ?>

                <!-- Table View -->

                <div class="overflow-x-auto">

                    <table id="reschedAppointmentsTable" class="min-w-full divide-y divide-gray-200 appointment-table">

                        <thead class="bg-gray-50">

                            <tr>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">PATIENT</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DATE</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">TIME</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">REASON</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">EMAIL</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">STATUS</th>

                            </tr>

                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">

                            <?php while ($appt = $resched_result->fetch_assoc()): ?>

                                <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer resched-appointment-row"

                                    data-name="<?= strtolower(htmlspecialchars($appt['name'])) ?>"

                                    data-date="<?= strtolower(htmlspecialchars($appt['date'])) ?>"

                                    data-time="<?= strtolower(htmlspecialchars($appt['time'])) ?>"

                                    data-reason="<?= strtolower(htmlspecialchars($appt['reason'])) ?>"

                                    data-email="<?= strtolower(htmlspecialchars($appt['email'])) ?>"

                                    data-status="rescheduled"

                                    style="height: 61px;">

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($appt['name']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo date('D, M j, Y', strtotime($appt['date'])); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo date('g:i A', strtotime($appt['time'])); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo htmlspecialchars($appt['reason']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo htmlspecialchars($appt['email']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Rescheduled</span>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>



                <!-- Pagination -->

                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">

                    <div class="flex justify-between items-center">

                        <!-- Records Information -->

                        <div class="text-xs text-gray-500">

                            Showing <span id="reschedShowingStart"><?php echo $resched_offset + 1; ?></span> to <span id="reschedShowingEnd"><?php echo min($resched_offset + $resched_records_per_page, $resched_total_records); ?></span> of <span id="reschedTotalEntries"><?php echo $resched_total_records; ?></span> entries

                        </div>



                        <!-- Pagination Navigation -->

                        <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">

                            <!-- Previous Button -->

                            <?php if ($resched_page > 1): ?>

                                <a href="?resched_page=<?php echo $resched_page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">

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

                            <div class="flex items-center">

                                <?php

                                $resched_start_page = max(1, $resched_page - 2);

                                $resched_end_page = min($resched_total_pages, $resched_page + 2);

                                if ($resched_start_page > 1): ?>

                                    <a href="?resched_page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100">1</a>

                                    <?php if ($resched_start_page > 2): ?>

                                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm">...</span>

                                    <?php endif; ?>

                                <?php endif; ?>

                                <?php for ($i = $resched_start_page; $i <= $resched_end_page; $i++): ?>

                                    <?php if ($i == $resched_page): ?>

                                        <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-400" aria-current="page"><?php echo $i; ?></button>

                                    <?php else: ?>

                                        <a href="?resched_page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>

                                    <?php endif; ?>

                                <?php endfor; ?>

                                <?php if ($resched_end_page < $resched_total_pages): ?>

                                    <?php if ($resched_end_page < $resched_total_pages - 1): ?>

                                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm">...</span>

                                    <?php endif; ?>

                                    <a href="?resched_page=<?php echo $resched_total_pages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $resched_total_pages; ?></a>

                                <?php endif; ?>

                            </div>



                            <!-- Next Button -->

                            <?php if ($resched_page < $resched_total_pages): ?>

                                <a href="?resched_page=<?php echo $resched_page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">

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

                    </div>

                </div>

            <?php else: ?>

                <!-- Empty State -->

                <div class="px-4 py-12 text-center text-gray-500">

                    <i class="ri-refresh-line text-4xl mb-2 block"></i>

                    No rescheduled appointments found.

                </div>

            <?php endif; ?>

        </div>



        <?php

        $resched_stmt->close();

        $conn->close();

        ?>



        <!-- Declined Appointments Section -->

        <div id="declinedSection" class="bg-white rounded-lg shadow-sm border border-gray-200 appt-tab-section hidden">

            <!-- Header Section -->

            <div class="px-4 py-3 border-b border-gray-200">

                <div class="flex justify-between items-center">

                    <div>

                        <h3 class="text-lg font-semibold text-gray-900">Declined Appointments</h3>

                        <p class="text-gray-600 text-xs mt-1">Appointments that have been declined or cancelled</p>

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



            <!-- Appointment Status Filter Tabs -->

            <div class="px-6 py-3 border-b border-gray-200">

                <nav class="flex space-x-8">

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="pending">

                        Pending (<span id="pendingCountDeclined">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="approved">

                        Approved (<span id="approvedCountDeclined">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn active px-1 py-2 text-sm font-medium text-blue-600 border-b-2 border-blue-600" data-status="declined">

                        Declined (<span id="declinedCountDeclined">0</span>)

                    </button>

                    <button type="button" class="appt-tab-btn px-1 py-2 text-sm font-medium text-gray-500 hover:text-gray-700 border-b-2 border-transparent" data-status="rescheduled">

                        Rescheduled (<span id="rescheduledCountDeclined">0</span>)

                    </button>

                </nav>

            </div>



            <?php if ($declined_result->num_rows > 0): ?>

                <!-- Table View -->

                <div class="overflow-x-auto">

                    <table id="declinedAppointmentsTable" class="min-w-full divide-y divide-gray-200 appointment-table">

                        <thead class="bg-gray-50">

                            <tr>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">PATIENT</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">DATE</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">TIME</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">REASON</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">EMAIL</th>

                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-1/6">STATUS</th>

                            </tr>

                        </thead>

                        <tbody class="bg-white divide-y divide-gray-200">

                            <?php while ($appt = $declined_result->fetch_assoc()): ?>

                                <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer" style="height: 61px;">

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm font-medium text-gray-900 truncate"><?php echo htmlspecialchars($appt['name']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo date('D, M j, Y', strtotime($appt['date'])); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo date('g:i A', strtotime($appt['time'])); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo htmlspecialchars($appt['reason']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                        <div class="text-sm text-gray-900 truncate"><?php echo htmlspecialchars($appt['email']); ?></div>

                                    </td>

                                    <td class="px-4 py-3 whitespace-nowrap">

                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Declined</span>

                                    </td>

                                </tr>

                            <?php endwhile; ?>

                        </tbody>

                    </table>

                </div>



                <!-- Pagination -->

                <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">

                    <div class="flex justify-between items-center">

                        <!-- Records Information -->

                        <div class="text-xs text-gray-500">

                            Showing <span id="declinedShowingStart"><?php echo $declined_offset + 1; ?></span> to <span id="declinedShowingEnd"><?php echo min($declined_offset + $declined_records_per_page, $declined_total_records); ?></span> of <span id="declinedTotalEntries"><?php echo $declined_total_records; ?></span> entries

                        </div>



                        <!-- Pagination Navigation -->

                        <nav class="flex justify-end items-center -space-x-px" aria-label="Pagination">

                            <!-- Previous Button -->

                            <?php if ($declined_page > 1): ?>

                                <a href="?declined_page=<?php echo $declined_page - 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Previous">

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

                            <div class="flex items-center">

                                <?php

                                $declined_start_page = max(1, $declined_page - 2);

                                $declined_end_page = min($declined_total_pages, $declined_page + 2);

                                if ($declined_start_page > 1): ?>

                                    <a href="?declined_page=1" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100">1</a>

                                    <?php if ($declined_start_page > 2): ?>

                                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm">...</span>

                                    <?php endif; ?>

                                <?php endif; ?>

                                <?php for ($i = $declined_start_page; $i <= $declined_end_page; $i++): ?>

                                    <?php if ($i == $declined_page): ?>

                                        <button type="button" class="min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-400" aria-current="page"><?php echo $i; ?></button>

                                    <?php else: ?>

                                        <a href="?declined_page=<?php echo $i; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $i; ?></a>

                                    <?php endif; ?>

                                <?php endfor; ?>

                                <?php if ($declined_end_page < $declined_total_pages): ?>

                                    <?php if ($declined_end_page < $declined_total_pages - 1): ?>

                                        <span class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm">...</span>

                                    <?php endif; ?>

                                    <a href="?declined_page=<?php echo $declined_total_pages; ?>" class="min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100"><?php echo $declined_total_pages; ?></a>

                                <?php endif; ?>

                            </div>



                            <!-- Next Button -->

                            <?php if ($declined_page < $declined_total_pages): ?>

                                <a href="?declined_page=<?php echo $declined_page + 1; ?>" class="min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100" aria-label="Next">

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

                    </div>

                </div>

            <?php else: ?>

                <!-- Empty State -->

                <div class="px-4 py-12 text-center text-gray-500">

                    <i class="ri-close-line text-4xl mb-2 block"></i>

                    No declined appointments found.

                </div>

            <?php endif; ?>

        </div>



        <!-- Footer -->





    </div>





<!-- View Appointment Modal -->

<div id="appointmentViewModal" class="fixed inset-0 bg-black bg-opacity-30 flex items-center justify-center z-50 hidden">

    <div class="w-full max-w-md mx-4 flex flex-col bg-white border border-gray-200 shadow-2xl rounded-xl pointer-events-auto dark:bg-neutral-800 dark:border-neutral-700 dark:shadow-neutral-700/70">

        <div class="flex justify-between items-center py-3 px-4 border-b border-gray-200 dark:border-neutral-700">

            <h3 id="hs-vertically-centered-modal-label" class="font-bold text-gray-800 dark:text-white">

                Appointment Details

            </h3>

            <button id="closeViewModalBtn" type="button" class="size-8 inline-flex justify-center items-center gap-x-2 rounded-full border border-transparent bg-gray-100 text-gray-800 hover:bg-gray-200 focus:outline-hidden focus:bg-gray-200 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-700 dark:hover:bg-neutral-600 dark:text-neutral-400 dark:focus:bg-neutral-600" aria-label="Close">

                <span class="sr-only">Close</span>

                <svg class="shrink-0 size-4" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                    <path d="M18 6 6 18"></path>

                    <path d="m6 6 12 12"></path>

                </svg>

            </button>

        </div>



        <div class="p-4 overflow-y-auto">

            <div class="space-y-3">

                <div class="grid grid-cols-1 gap-3">

                    <div class="grid grid-cols-[120px_1fr] gap-3 items-center">

                        <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Patient Name:</label>

                        <p id="viewPatientName" class="text-sm text-gray-900 dark:text-neutral-200"></p>

                    </div>



                    <div class="grid grid-cols-[120px_1fr] gap-3 items-center">

                        <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Email:</label>

                        <p id="viewPatientEmail" class="text-sm text-gray-900 dark:text-neutral-200"></p>

                    </div>



                    <div class="grid grid-cols-[120px_1fr] gap-3 items-center">

                        <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Date:</label>

                        <p id="viewAppointmentDate" class="text-sm text-gray-900 dark:text-neutral-200"></p>

                    </div>



                    <div class="grid grid-cols-[120px_1fr] gap-3 items-center">

                        <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Time:</label>

                        <p id="viewAppointmentTime" class="text-sm text-gray-900 dark:text-neutral-200"></p>

                    </div>



                    <div class="grid grid-cols-[120px_1fr] gap-3 items-start">

                        <label class="text-sm font-medium text-gray-700 dark:text-neutral-300">Reason for Visit:</label>

                        <p id="viewAppointmentReason" class="text-sm text-gray-900 dark:text-neutral-200"></p>

                    </div>

                </div>

            </div>

        </div>



        <div class="flex justify-end items-center gap-x-2 py-3 px-4 border-t border-gray-200 dark:border-neutral-700">

            <button id="closeViewModalBtnBottom" type="button" class="py-2 px-3 inline-flex items-center gap-x-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-800 shadow-sm hover:bg-gray-50 focus:outline-hidden focus:bg-gray-50 disabled:opacity-50 disabled:pointer-events-none dark:bg-neutral-800 dark:border-neutral-700 dark:text-white dark:hover:bg-neutral-700 dark:focus:bg-neutral-700">

                Close

            </button>

        </div>

    </div>

</div>



<style>

    html,

    body {

        scrollbar-width: none;

        /* Firefox */

        -ms-overflow-style: none;

        /* Internet Explorer 10+ */

    }



    html::-webkit-scrollbar,

    body::-webkit-scrollbar {

        display: none;

        /* Safari and Chrome */

    }

</style>



<!-- Include jQuery and DataTables -->

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">





</main>



<script>

    // Real-time pagination counting function
    function updatePaginationCountsRealTime(action) {
        const paginationContainer = document.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50');
        if (!paginationContainer) return;
        
        const currentInfo = paginationContainer.querySelector('.text-xs.text-gray-500');
        if (!currentInfo) return;
        
        // Get current counts from the info text
        const currentText = currentInfo.textContent;
        const showingMatch = currentText.match(/Showing (\d+) to (\d+) of (\d+) entries/);
        
        if (showingMatch) {
            let startRecord = parseInt(showingMatch[1]);
            let endRecord = parseInt(showingMatch[2]);
            let totalRecords = parseInt(showingMatch[3]);
            
            if (action === 'add') {
                totalRecords += 1;
                // If we're on the last page and it's full, we might need to go to next page
                const recordsPerPage = 10; // Default per page
                const currentPage = Math.ceil(startRecord / recordsPerPage);
                const maxRecordsForCurrentPage = currentPage * recordsPerPage;
                
                if (totalRecords > maxRecordsForCurrentPage) {
                    // Need to go to next page
                    startRecord = maxRecordsForCurrentPage + 1;
                    endRecord = Math.min(totalRecords, (currentPage + 1) * recordsPerPage);
                } else {
                    endRecord = totalRecords;
                }
            } else if (action === 'delete') {
                totalRecords = Math.max(0, totalRecords - 1);
                if (endRecord > totalRecords) {
                    endRecord = totalRecords;
                }
                if (startRecord > totalRecords && totalRecords > 0) {
                    startRecord = Math.max(1, totalRecords - 9);
                }
            }
            
            // Update the display immediately
            currentInfo.innerHTML = 'Showing <span id="dsShowingStart">' + startRecord + '</span> to <span id="dsShowingEnd">' + endRecord + '</span> of <span id="dsTotalEntries">' + totalRecords + '</span> entries';
        }
    }

    // Doctor schedules data for calendar

    const doctorSchedules = <?php echo json_encode($doctor_schedules); ?>;



    // Function to add new schedule to table dynamically

    function addScheduleToTable(schedule) {

        const tbody = document.querySelector('table tbody');



        // Remove "No doctor schedules found" row if it exists

        const noDataRow = tbody.querySelector('td[colspan="7"]');

        if (noDataRow) {

            noDataRow.parentElement.remove();

        }



        // Create new row

        const newRow = document.createElement('tr');

        // Format the date and time properly
        const formattedDate = new Date(schedule.schedule_date).toLocaleDateString('en-US', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
            year: 'numeric'
        });
        
        const timeParts = schedule.schedule_time.split('-');
        const startTime = timeParts[0] ? new Date('2000-01-01 ' + timeParts[0]).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        }) : '';
        const endTime = timeParts[1] ? new Date('2000-01-01 ' + timeParts[1]).toLocaleTimeString('en-US', {
            hour: 'numeric',
            minute: '2-digit',
            hour12: true
        }) : '';

        newRow.innerHTML = 
            '<td class="px-4 py-3 whitespace-nowrap">' +
                '<div class="text-sm font-medium text-gray-900">' + escapeHtml(schedule.doctor_name) + '</div>' +
            '</td>' +
            '<td class="px-4 py-3 whitespace-nowrap">' +
                '<div class="text-sm text-gray-900">' + escapeHtml(schedule.profession || 'Physician') + '</div>' +
            '</td>' +
            '<td class="px-4 py-3 whitespace-nowrap">' +
                '<div class="text-sm text-gray-900">' + formattedDate + '</div>' +
            '</td>' +
            '<td class="px-4 py-3 whitespace-nowrap">' +
                '<div class="text-sm text-gray-900">' + startTime + '</div>' +
            '</td>' +
            '<td class="px-4 py-3 whitespace-nowrap">' +
                '<div class="text-sm text-gray-900">' + endTime + '</div>' +
            '</td>' +
            '<td class="px-4 py-3 whitespace-nowrap">' +
                '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">' +
                    'Available' +
                '</span>' +
            '</td>' +
            '<td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">' +
                '<div class="flex items-center space-x-2">' +
                    '<button class="edit-schedule-btn p-1.5 text-gray-400 hover:text-blue-600 transition-colors" ' +
                        'data-id="' + schedule.id + '" ' +
                        'data-doctor="' + escapeHtml(schedule.doctor_name) + '" ' +
                        'data-date="' + schedule.schedule_date + '" ' +
                        'data-time="' + escapeHtml(schedule.schedule_time) + '" ' +
                        'title="Edit">' +
                        '<i class="ri-edit-line text-sm"></i>' +
                    '</button>' +
                    '<button class="delete-schedule-btn p-1.5 text-gray-400 hover:text-red-600 transition-colors" ' +
                        'data-id="' + schedule.id + '" title="Delete">' +
                        '<i class="ri-delete-bin-line text-sm"></i>' +
                    '</button>' +
                '</div>' +
            '</td>';



        // Add to tbody

        tbody.appendChild(newRow);



        // Add event listener to the new delete button

        const deleteBtn = newRow.querySelector('.delete-schedule-btn');

        if (deleteBtn) {
            deleteBtn.addEventListener('click', function() {

            const scheduleId = this.dataset.id;



            showConfirmModal('Are you sure you want to delete this doctor schedule?',

                function() {

                    // User clicked Yes - Delete the schedule

                    fetch('', {

                            method: 'POST',

                            headers: {

                                'Content-Type': 'application/x-www-form-urlencoded'

                            },

                            body: new URLSearchParams({

                                action: 'delete_schedule',

                                id: scheduleId

                            })

                        })

                        .then(res => {

                            // Check if response is JSON

                            const contentType = res.headers.get('content-type');

                            if (!contentType || !contentType.includes('application/json')) {

                                throw new Error('Response is not JSON');

                            }

                            return res.json();

                        })

                        .then(data => {

                            if (data.success) {

                                // Remove the row from table

                                newRow.remove();



                                // If no rows left, add "No doctor schedules found" row

                                const remainingRows = tbody.querySelectorAll('tr');

                                if (remainingRows.length === 0) {

                                    // Hide the entire table structure and show empty state like static version
                                    let tableContainer = document.querySelector('#doctorSchedulesSection .bg-white.rounded-lg.shadow-sm.border.border-gray-200');
                                    
                                    if (!tableContainer) {
                                        const table = document.getElementById('doctorScheduleTable');
                                        if (table) {
                                            tableContainer = table.closest('.bg-white.rounded-lg.shadow-sm.border.border-gray-200');
                                        }
                                    }
                                    
                                    if (!tableContainer) {
                                        tableContainer = document.querySelector('#doctorSchedulesSection > div:last-child');
                                    }
                                    
                                    if (tableContainer) {
                                        tableContainer.style.display = 'none';
                                    }

                                    // Show empty state inside the card (replace table content)
                                    const tableBody = document.getElementById('doctorScheduleTableBody');
                                    if (tableBody) {
                                        tableBody.innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-gray-500"><i class="ri-calendar-line text-4xl mb-2 block"></i>No doctor schedules found.</td></tr>';
                                    }

                                }



                                showSimpleSuccessMessage('Doctor schedule deleted successfully!');
                                
                                // Auto refresh page after successful deletion, preserving current tab
                                setTimeout(() => {
                                    // Save current tab before refresh
                                    localStorage.setItem('staffActiveTab', 'doctor-schedules');
                                    window.location.reload();
                                }, 200);

                            } else {

                                showErrorModal('Failed to delete doctor schedule: ' + (data.error || 'Unknown error'), 'Error');

                            }

                        })

                        .catch(error => {

                            showErrorModal('Network error: ' + error.message, 'Error');

                        });

                },

                function() {

                    // User clicked No - Do nothing

                    console.log('Delete cancelled by user');

                }

            );

        });
        }

    }



    // Helper function to escape HTML

    function escapeHtml(text) {

        const div = document.createElement('div');

        div.textContent = text;

        return div.innerHTML;

    }



    // Add doctor schedule form

    const addDoctorForm = document.getElementById('addDoctorForm');
    if (addDoctorForm) {
        addDoctorForm.addEventListener('submit', function(e) {

        e.preventDefault();

        const formData = new FormData(this);

        const submitBtn = this.querySelector('button[type="submit"]');

        const originalText = submitBtn.textContent;

        // Validate date - prevent yesterday's date
        const selectedDate = new Date(this.doctor_date.value);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Reset time to start of day
        
        if (selectedDate < today) {
            alert('Cannot add doctor schedule for past dates. Please select today or a future date.');
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
            return;
        }

        // Disable button and show loading state

        submitBtn.disabled = true;

        submitBtn.textContent = 'Adding...';



        formData.append('action', 'add_doctor');

        // Combine start and end time into a range

        const start = this.doctor_time_start.value;

        const end = this.doctor_time_end.value;

        formData.set('doctor_time', start + '-' + end);

        fetch('', {

                method: 'POST',

                body: formData

            })

            .then(res => res.json())

            .then(data => {

                if (data.success) {

                    // Clear the form

                    this.reset();



                    // Add the new schedule to the table without page refresh

                    addScheduleToTable({

                        doctor_name: formData.get('doctor_name'),

                        profession: formData.get('profession'),

                        schedule_date: formData.get('doctor_date'),

                        schedule_time: start + '-' + end,

                        id: data.schedule_id || 'new_' + Date.now() // Use returned ID or generate temporary one

                    });

                    // Also add to calendar data and re-render calendar

                    try {

                        doctorSchedules.push({

                            id: data.schedule_id || 'new_' + Date.now(),

                            doctor_name: formData.get('doctor_name'),

                            schedule_date: formData.get('doctor_date'),

                            schedule_time: start + '-' + end

                        });

                        // If the added date is in the currently displayed month, re-render

                        const addedDate = new Date(formData.get('doctor_date'));

                        if (addedDate && !isNaN(addedDate)) {

                            const isSameMonth = addedDate.getMonth() === currentMonth && addedDate.getFullYear() === currentYear;

                            if (typeof renderCalendar === 'function' && isSameMonth) {

                                renderCalendar(currentMonth, currentYear);

                            }

                        } else if (typeof renderCalendar === 'function') {

                            renderCalendar(currentMonth, currentYear);

                        }

                    } catch (e) {

                        /* no-op */

                    }



                    showSimpleSuccessMessage(data.message || 'Doctor schedule added successfully!');
                    
                    // Refresh the page after a short delay to show the success message
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                    
                    // Update pagination counts immediately (real-time)
                    updatePaginationCountsRealTime('add');
                    
                    // Refresh the doctor schedules table
                    if (typeof performDoctorScheduleSearch === 'function') {
                        performDoctorScheduleSearch('', 1); // Refresh with no search term, page 1
                    }

                } else {

                    showErrorModal('Failed to add doctor schedule: ' + (data.error || 'Unknown error'), 'Error');

                }

            })

            .catch(error => {

                showErrorModal('Network error: ' + error.message, 'Error');

            })

            .finally(() => {

                // Re-enable button and restore text

                submitBtn.disabled = false;

                submitBtn.textContent = originalText;

            });

        });
    }



    // Edit and Delete doctor schedule buttons

    document.addEventListener('DOMContentLoaded', function() {

        // Edit schedule button - REMOVED: Using event delegation instead to avoid duplicate modals

        // Delete schedule button
        document.querySelectorAll('.delete-schedule-btn').forEach(button => {

            button.addEventListener('click', function() {

                const scheduleId = this.dataset.id;

                const row = this.closest('tr');



                showConfirmModal('Are you sure you want to delete this doctor schedule?',

                    function() {

                        // User clicked Yes - Delete the schedule

                        fetch('', {

                                method: 'POST',

                                headers: {

                                    'Content-Type': 'application/x-www-form-urlencoded'

                                },

                                body: new URLSearchParams({

                                    action: 'delete_schedule',

                                    id: scheduleId

                                })

                            })

                            .then(res => res.json())

                            .then(data => {

                                if (data.success) {

                                    // Remove the row from table

                                    row.remove();



                                    // If no rows left, add "No doctor schedules found" row

                                    const tbody = document.querySelector('table tbody');

                                    const remainingRows = tbody.querySelectorAll('tr');

                                    if (remainingRows.length === 0) {

                                        // Hide the entire table structure and show empty state like static version
                                        let tableContainer = document.querySelector('#doctorSchedulesSection .bg-white.rounded-lg.shadow-sm.border.border-gray-200');
                                        
                                        if (!tableContainer) {
                                            const table = document.getElementById('doctorScheduleTable');
                                            if (table) {
                                                tableContainer = table.closest('.bg-white.rounded-lg.shadow-sm.border.border-gray-200');
                                            }
                                        }
                                        
                                        if (!tableContainer) {
                                            tableContainer = document.querySelector('#doctorSchedulesSection > div:last-child');
                                        }
                                        
                                        if (tableContainer) {
                                            tableContainer.style.display = 'none';
                                        }

                                        // Show empty state inside the card (replace table content)
                                        const tableBody = document.getElementById('doctorScheduleTableBody');
                                        if (tableBody) {
                                            tableBody.innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-gray-500"><i class="ri-calendar-line text-4xl mb-2 block"></i>No doctor schedules found.</td></tr>';
                                        }

                                    }



                                    showSimpleSuccessMessage('Doctor schedule deleted successfully!');
                                    
                                    // Auto refresh page after successful deletion, preserving current tab
                                    setTimeout(() => {
                                        // Save current tab before refresh
                                        localStorage.setItem('staffActiveTab', 'doctor-schedules');
                                        window.location.reload();
                                    }, 200);

                                } else {

                                    showErrorModal('Failed to delete doctor schedule: ' + (data.error || 'Unknown error'), 'Error');

                                }

                            })

                            .catch(error => {

                                showErrorModal('Network error: ' + error.message, 'Error');

                            });

                    },

                    function() {

                        // User clicked No - Do nothing

                        console.log('Delete cancelled by user');

                    }

                );

            });

        });

    });



    // Demo action button logic

    const approveBtns = document.querySelectorAll('.approveBtn');

    const declineBtns = document.querySelectorAll('.declineBtn');

    const reschedBtns = document.querySelectorAll('.reschedBtn');



    // Custom confirmation modal function that matches the design

    function showConfirmModal(message, onConfirm, onCancel) {

        const modalId = 'confirmModal_' + Date.now();

        const modal = document.createElement('div');

        modal.id = modalId;

        modal.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);';



        modal.innerHTML = 
            '<div style="background:rgba(255,255,255,0.95); color:#d97706; min-width:300px; max-width:90vw; padding:24px 32px; border-radius:16px; box-shadow:0 4px 32px rgba(217,119,6,0.15); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #d97706; display:flex; flex-direction:column; gap:16px; pointer-events:auto;">' +
                '<div style="display:flex; align-items:center; justify-content:center; gap:12px;">' +
                    '<span style="font-size:2rem;line-height:1;color:#d97706;">&#9888;</span>' +
                    '<span style="color:#374151;">' + message + '</span>' +
                '</div>' +
                '<div style="display:flex; gap:12px; justify-content:center;">' +
                    '<button id="confirmBtn" style="background:#d97706; color:white; padding:8px 16px; border-radius:8px; font-weight:500; border:none; cursor:pointer;">Confirm</button>' +
                    '<button id="cancelBtn" style="background:#f3f4f6; color:#374151; padding:8px 16px; border-radius:8px; font-weight:500; border:1px solid #d1d5db; cursor:pointer;">Cancel</button>' +
                '</div>' +
            '</div>';



        document.body.appendChild(modal);



        const confirmBtn = modal.querySelector('#confirmBtn');

        const cancelBtn = modal.querySelector('#cancelBtn');



        confirmBtn.onclick = function() {

            modal.style.transition = 'opacity 0.3s';

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

    // Edit schedule modal function
    function showEditScheduleModal(scheduleId, doctorName, scheduleDate, scheduleTime) {
        const modalId = 'editScheduleModal_' + Date.now();
        
        // Store original values for comparison
        window.originalScheduleData = {
            id: scheduleId,
            doctor: doctorName,
            date: scheduleDate,
            time: scheduleTime
        };
        
        const modal = document.createElement('div');
        modal.id = modalId;
        modal.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(0,0,0,0.5);';
        
        modal.innerHTML = 
            '<div style="background:white; min-width:400px; max-width:90vw; padding:24px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.2); pointer-events:auto;">' +
                '<h3 style="font-size:1.25rem; font-weight:600; color:#374151; margin-bottom:20px;">Edit Doctor Schedule</h3>' +
                '<form id="editScheduleForm">' +
                    '<div style="margin-bottom:16px;">' +
                        '<label style="display:block; font-size:0.875rem; font-weight:500; color:#374151; margin-bottom:4px;">Doctor Name</label>' +
                        '<input type="text" id="editDoctorName" name="editDoctorName" value="' + doctorName + '" required ' +
                               'style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:0.875rem;">' +
                    '</div>' +
                    '<div style="margin-bottom:16px;">' +
                        '<label style="display:block; font-size:0.875rem; font-weight:500; color:#374151; margin-bottom:4px;">Date</label>' +
                        '<input type="date" id="editScheduleDate" name="editScheduleDate" value="' + scheduleDate + '" required ' +
                               'style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:0.875rem;">' +
                    '</div>' +
                    '<div style="margin-bottom:20px;">' +
                        '<label style="display:block; font-size:0.875rem; font-weight:500; color:#374151; margin-bottom:4px;">Time</label>' +
                        '<input type="text" id="editScheduleTime" name="editScheduleTime" value="' + scheduleTime + '" placeholder="e.g., 09:00-17:00" required ' +
                               'style="width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:6px; font-size:0.875rem;">' +
                    '</div>' +
                    '<div style="display:flex; gap:12px; justify-content:flex-end;">' +
                        '<button type="button" id="cancelEditBtn" style="padding:8px 16px; border:1px solid #d1d5db; background:white; color:#374151; border-radius:6px; cursor:pointer; font-size:0.875rem;">Cancel</button>' +
                        '<button type="submit" id="saveEditBtn" style="padding:8px 16px; background:#3b82f6; color:white; border:none; border-radius:6px; cursor:pointer; font-size:0.875rem;">Save Changes</button>' +
                    '</div>' +
                '</form>' +
            '</div>';
        
        document.body.appendChild(modal);
        
        // Add input event listeners to track changes
        const doctorNameField = modal.querySelector('#editDoctorName');
        const scheduleDateField = modal.querySelector('#editScheduleDate');
        const scheduleTimeField = modal.querySelector('#editScheduleTime');
        
        if (doctorNameField) {
            doctorNameField.addEventListener('input', function() {
                console.log('Doctor name field changed to:', this.value);
            });
        }
        
        if (scheduleDateField) {
            scheduleDateField.addEventListener('input', function() {
                console.log('Schedule date field changed to:', this.value);
            });
        }
        
        if (scheduleTimeField) {
            scheduleTimeField.addEventListener('input', function() {
                console.log('Schedule time field changed to:', this.value);
            });
        }
        
        // Event listeners
        const cancelBtn = modal.querySelector('#cancelEditBtn');
        const form = modal.querySelector('#editScheduleForm');
        
        cancelBtn.onclick = function() {
            modal.style.transition = 'opacity 0.3s';
            modal.style.opacity = '0';
            setTimeout(() => {
                if (modal && modal.parentNode) {
                    modal.parentNode.removeChild(modal);
                }
            }, 300);
        };
        
        form.onsubmit = function(e) {
            e.preventDefault();
            
            // Get form values with better error handling
            const doctorNameField = document.getElementById('editDoctorName');
            const scheduleDateField = document.getElementById('editScheduleDate');
            const scheduleTimeField = document.getElementById('editScheduleTime');
            
            console.log('Form field elements found:');
            console.log('  Doctor field:', doctorNameField);
            console.log('  Date field:', scheduleDateField);
            console.log('  Time field:', scheduleTimeField);
            
            if (!doctorNameField || !scheduleDateField || !scheduleTimeField) {
                alert('Error: Form fields not found');
                return;
            }
            
            // Read values directly from the form elements using multiple methods
            const doctorName = (doctorNameField.value || doctorNameField.getAttribute('value') || '').trim();
            const scheduleDate = scheduleDateField.value || scheduleDateField.getAttribute('value') || '';
            const scheduleTime = (scheduleTimeField.value || scheduleTimeField.getAttribute('value') || '').trim();
            
            // Also try reading from the form data
            const formData = new FormData(form);
            const formDoctorName = formData.get('editDoctorName') || '';
            const formScheduleDate = formData.get('editScheduleDate') || '';
            const formScheduleTime = formData.get('editScheduleTime') || '';
            
            // Use FormData values if they exist, otherwise fall back to field values
            const finalDoctorName = formDoctorName || doctorName;
            const finalScheduleDate = formScheduleDate || scheduleDate;
            const finalScheduleTime = formScheduleTime || scheduleTime;
            
            console.log('Alternative value reading:');
            console.log('  FormData doctor name:', formDoctorName);
            console.log('  FormData date:', formScheduleDate);
            console.log('  FormData time:', formScheduleTime);
            
            console.log('Form field values:');
            console.log('  Doctor field value:', doctorNameField.value);
            console.log('  Date field value:', scheduleDateField.value);
            console.log('  Time field value:', scheduleTimeField.value);
            console.log('  Processed values:');
            console.log('    Doctor name:', doctorName);
            console.log('    Schedule date:', scheduleDate);
            console.log('    Schedule time:', scheduleTime);
            
            if (!finalDoctorName || !finalScheduleDate || !finalScheduleTime) {
                alert('Please fill in all fields');
                return;
            }
            
            // Debug logging
            console.log('Edit schedule - ID:', scheduleId, 'Name:', finalDoctorName, 'Date:', finalScheduleDate, 'Time:', finalScheduleTime);
            console.log('Original values - ID:', window.originalScheduleData.id, 'Doctor:', window.originalScheduleData.doctor, 'Date:', window.originalScheduleData.date, 'Time:', window.originalScheduleData.time);
            
            // Check if values have actually changed
            const hasChanges = 
                window.originalScheduleData.doctor !== finalDoctorName ||
                window.originalScheduleData.date !== finalScheduleDate ||
                window.originalScheduleData.time !== finalScheduleTime;
            
            console.log('Has changes:', hasChanges);
            console.log('Change details:');
            console.log('  Doctor:', window.originalScheduleData.doctor, '->', finalDoctorName, 'Changed:', window.originalScheduleData.doctor !== finalDoctorName);
            console.log('  Date:', window.originalScheduleData.date, '->', finalScheduleDate, 'Changed:', window.originalScheduleData.date !== finalScheduleDate);
            console.log('  Time:', window.originalScheduleData.time, '->', finalScheduleTime, 'Changed:', window.originalScheduleData.time !== finalScheduleTime);
            
            if (!hasChanges) {
                alert('No changes detected. Please modify at least one field before saving.');
                return;
            }
            
            // Submit via AJAX
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    action: 'edit_schedule',
                    id: scheduleId,
                    doctor_name: finalDoctorName,
                    doctor_date: finalScheduleDate,
                    doctor_time: finalScheduleTime
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Close modal
                    modal.style.transition = 'opacity 0.3s';
                    modal.style.opacity = '0';
                    setTimeout(() => {
                        if (modal && modal.parentNode) {
                            modal.parentNode.removeChild(modal);
                        }
                    }, 300);
                    
                    // Show success message
                    showSimpleSuccessMessage('Schedule updated successfully!');
                    
                    // Reload the page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error updating schedule');
            });
        };
    }

    // Simple success message function (no buttons, auto-dismiss)

    function showSimpleSuccessMessage(message) {

        // Remove any existing notification

        const existingToast = document.getElementById('scheduleToast');

        if (existingToast) {

            existingToast.remove();

        }



        const notification = document.createElement('div');

        notification.id = 'scheduleToast';

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



    // Success modal function

    function showSuccessModal(message, title = 'Success') {

        const modalId = 'successModal_' + Date.now();

        const modal = document.createElement('div');

        modal.id = modalId;

        modal.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);';



        modal.innerHTML = `

        <div style='background:rgba(255,255,255,0.95); color:#2563eb; min-width:300px; max-width:90vw; padding:24px 32px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.15); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; align-items:center; gap:12px; pointer-events:auto;'>

            <span style='font-size:2rem;line-height:1;color:#2563eb;'>&#10003;</span>

            <span style='color:#374151;'>${message}</span>

        </div>

    `;



        document.body.appendChild(modal);



        // Auto-remove modal after 3 seconds

        setTimeout(() => {

            if (document.getElementById(modalId)) {

            modal.style.transition = 'opacity 0.05s';

            modal.style.opacity = '0';

            setTimeout(() => {

                if (modal && modal.parentNode) {

                    modal.parentNode.removeChild(modal);

                }

            }, 50);

            }

        }, 3000);

    }



    // Error modal function

    function showErrorModal(message, title = 'Error') {

        const modalId = 'errorModal_' + Date.now();

        const modal = document.createElement('div');

        modal.id = modalId;

        modal.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);';



        modal.innerHTML = `

        <div style='background:rgba(255,255,255,0.95); color:#dc2626; min-width:300px; max-width:90vw; padding:24px 32px; border-radius:16px; box-shadow:0 4px 32px rgba(220,38,38,0.15); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #dc2626; display:flex; flex-direction:column; gap:16px; pointer-events:auto;'>

            <div style='display:flex; align-items:center; justify-content:center; gap:12px;'>

                <span style='font-size:2rem;line-height:1;color:#dc2626;'>&#10060;</span>

                <span style='color:#374151; font-weight:600;'>${title}</span>

            </div>

            <div style='color:#374151; margin:8px 0;'>${message}</div>

            <div style='display:flex; gap:12px; justify-content:center;'>

                <button id='okayBtn' style='background:#dc2626; color:white; padding:8px 16px; border-radius:8px; font-weight:500; border:none; cursor:pointer;'>Okay</button>

                <button id='cancelBtn' style='background:#f3f4f6; color:#374151; padding:8px 16px; border-radius:8px; font-weight:500; border:1px solid #d1d5db; cursor:pointer;'>Cancel</button>

            </div>

        </div>

    `;



        document.body.appendChild(modal);



        const okayBtn = modal.querySelector('#okayBtn');

        const cancelBtn = modal.querySelector('#cancelBtn');



        const closeModal = function() {

            // Much faster error modal close - reduced from 300ms to 50ms

            modal.style.transition = 'opacity 0.05s';

            modal.style.opacity = '0';

            setTimeout(() => {

                if (modal && modal.parentNode) {

                    modal.parentNode.removeChild(modal);

                }

            }, 50);

        };



        okayBtn.onclick = closeModal;

        cancelBtn.onclick = closeModal;

    }



    // Use event delegation for dynamically generated content

    document.addEventListener('click', function(e) {

        // Handle edit schedule buttons
        if (e.target.closest('.edit-schedule-btn')) {
            const btn = e.target.closest('.edit-schedule-btn');
            const scheduleId = btn.dataset.id;
            const doctorName = btn.dataset.doctor;
            const scheduleDate = btn.dataset.date;
            const scheduleTime = btn.dataset.time;

            showEditScheduleModal(scheduleId, doctorName, scheduleDate, scheduleTime);
            return;
        }

        if (e.target.closest('.approveBtn')) {

            const btn = e.target.closest('.approveBtn');

            const row = btn.closest('tr');

            const name = row.getAttribute('data-name'); // Get from data attribute

            const date = row.getAttribute('data-date'); // Get from data attribute (raw format)

            const time = row.getAttribute('data-time'); // Get from data attribute (raw format)

            const reason = row.getAttribute('data-reason'); // Get from data attribute

            const email = row.getAttribute('data-email'); // Get from data attribute

            

            // Format the date and time for display

            const formattedDate = new Date(date).toLocaleDateString('en-US', {

                year: 'numeric',

                month: 'long',

                day: 'numeric'

            });

            const formattedTime = new Date('2000-01-01 ' + time).toLocaleTimeString('en-US', {

                hour: 'numeric',

                minute: '2-digit',

                hour12: true

            });

            

            

        }

    });

    

    // Keep the old code for backward compatibility but it won't work for dynamic content

    approveBtns.forEach(btn => btn.addEventListener('click', function() {

        const row = btn.closest('tr');

        const name = row.getAttribute('data-name'); // Get from data attribute

        const date = row.getAttribute('data-date'); // Get from data attribute (raw format)

        const time = row.getAttribute('data-time'); // Get from data attribute (raw format)

        const reason = row.getAttribute('data-reason'); // Get from data attribute

        showConfirmModal('Are you sure you want to approve this appointment?', function() {

            fetch('', {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/x-www-form-urlencoded'

                    },

                    body: new URLSearchParams({

                        action: 'approve',

                        date,

                        time,

                        reason,

                        name

                    })

                })

                .then(res => res.json())

                .then(data => {

                    if (data.success) {

                        // Show success message and refresh the page

                        showSuccessModal('Appointment approved successfully!', 'Success');

                        // Refresh the page after a short delay to show updated data

                        setTimeout(() => {

                            window.location.reload();

                        }, 1500);

                    } else {

                        showErrorModal('Failed to approve appointment.', 'Error');

                    }

                });

        });

    }));



    // Use event delegation for decline button

    document.addEventListener('click', function(e) {

        if (e.target.closest('.declineBtn')) {

            const btn = e.target.closest('.declineBtn');

            const row = btn.closest('tr');

            const name = row.getAttribute('data-name'); // Get from data attribute

            const date = row.getAttribute('data-date'); // Get from data attribute (raw format)

            const time = row.getAttribute('data-time'); // Get from data attribute (raw format)

            const reason = row.getAttribute('data-reason'); // Get from data attribute

            const email = row.getAttribute('data-email'); // Get from data attribute

            

            // Format the date and time for display

            const formattedDate = new Date(date).toLocaleDateString('en-US', {

                year: 'numeric',

                month: 'long',

                day: 'numeric'

            });

            const formattedTime = new Date('2000-01-01 ' + time).toLocaleTimeString('en-US', {

                hour: 'numeric',

                minute: '2-digit',

                hour12: true

            });

            

            

        }

    });

    

    // Keep the old code for backward compatibility but it won't work for dynamic content

    declineBtns.forEach(btn => btn.addEventListener('click', function() {

        const row = btn.closest('tr');

        const name = row.getAttribute('data-name'); // Get from data attribute

        const date = row.getAttribute('data-date'); // Get from data attribute (raw format)

        const time = row.getAttribute('data-time'); // Get from data attribute (raw format)

        const reason = row.getAttribute('data-reason'); // Get from data attribute

        showConfirmModal('Are you sure you want to decline this appointment?', function() {

            fetch('', {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/x-www-form-urlencoded'

                    },

                    body: new URLSearchParams({

                        action: 'decline',

                        date,

                        time,

                        reason,

                        name

                    })

                })

                .then(res => res.json())

                .then(data => {

                    if (data.success) {

                        // Show success message and refresh the page

                        showSuccessModal('Appointment declined successfully!', 'Success');

                        // Refresh the page after a short delay to show updated data

                        setTimeout(() => {

                            window.location.reload();

                        }, 1500);

                    } else {

                        showErrorModal('Failed to decline appointment.', 'Error');

                    }

                });

        });

    }));



    // Custom reschedule modal function

    function showRescheduleModal(oldDate, oldTime, onConfirm) {

        const modalId = 'rescheduleModal_' + Date.now();

        const modal = document.createElement('div');

        modal.id = modalId;

        modal.style.cssText = 'position:fixed;top:0;left:0;width:100vw;height:100vh;z-index:9999;display:flex;align-items:center;justify-content:center;pointer-events:none;background:rgba(255,255,255,0.18);';



        // If oldTime is a range (e.g., "12:28-12:58"), use only the start time

        let singleTime = oldTime;

        if (oldTime && oldTime.includes('-')) {

            singleTime = oldTime.split('-')[0].trim();

        }

        modal.innerHTML = `

        <div style='background:rgba(255,255,255,0.95); color:#2563eb; min-width:350px; max-width:90vw; padding:24px 32px; border-radius:16px; box-shadow:0 4px 32px rgba(37,99,235,0.15); font-size:1.1rem; font-weight:500; text-align:center; border:1.5px solid #2563eb; display:flex; flex-direction:column; gap:16px; pointer-events:auto;'>

            <div style='display:flex; align-items:center; justify-content:center; gap:12px;'>

                <span style='font-size:2rem;line-height:1;color:#2563eb;'>&#8505;</span>

                <span style='color:#374151; font-weight:600;'>Reschedule Appointment</span>

            </div>

            <div style='text-align:left;'>

                <label style='display:block; margin-bottom:8px; color:#374151; font-weight:500;'>New Date:</label>

                <input id='modalNewDate' type='date' value='${oldDate}' style='width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; margin-bottom:16px;'>

                <label style='display:block; margin-bottom:8px; color:#374151; font-weight:500;'>New Time:</label>

                <input id='modalNewTime' type='time' value='${singleTime}' style='width:100%; padding:8px 12px; border:1px solid #d1d5db; border-radius:8px; margin-bottom:16px;'>

            </div>

            <div style='display:flex; gap:12px; justify-content:center;'>

                <button id='confirmRescheduleBtn' style='background:#2563eb; color:white; padding:8px 16px; border-radius:8px; font-weight:500; border:none; cursor:pointer;'>Reschedule</button>

                <button id='cancelRescheduleBtn' style='background:#f3f4f6; color:#374151; padding:8px 16px; border-radius:8px; font-weight:500; border:1px solid #d1d5db; cursor:pointer;'>Cancel</button>

            </div>

        </div>

    `;



        document.body.appendChild(modal);



        const confirmBtn = modal.querySelector('#confirmRescheduleBtn');

        const cancelBtn = modal.querySelector('#cancelRescheduleBtn');

        const newDateInput = modal.querySelector('#modalNewDate');

        const newTimeInput = modal.querySelector('#modalNewTime');



        confirmBtn.onclick = function() {

            const newDate = newDateInput.value;

            const newTime = newTimeInput.value;



            if (!newDate || !newTime) {

                showErrorModal('Please fill in both date and time.', 'Error');

                return;

            }



            // Much faster modal close - reduced from 300ms to 100ms

            modal.style.transition = 'opacity 0.1s';

            modal.style.opacity = '0';

            setTimeout(() => {

                if (modal && modal.parentNode) {

                    modal.parentNode.removeChild(modal);

                }

                if (typeof onConfirm === 'function') onConfirm(newDate, newTime);

            }, 100);

        };



        cancelBtn.onclick = function() {

            // Much faster modal close - reduced from 300ms to 100ms

            modal.style.transition = 'opacity 0.1s';

            modal.style.opacity = '0';

            setTimeout(() => {

                if (modal && modal.parentNode) {

                    modal.parentNode.removeChild(modal);

                }

            }, 100);

        };

    }



    // Use event delegation for reschedule button

    document.addEventListener('click', function(e) {

        if (e.target.closest('.reschedBtn')) {

            const btn = e.target.closest('.reschedBtn');

            const row = btn.closest('tr');

            const name = row.getAttribute('data-name'); // Get from data attribute

            const oldDate = row.getAttribute('data-date'); // Get from data attribute (raw format)

            const oldTime = row.getAttribute('data-time'); // Get from data attribute (raw format)

            const reason = row.getAttribute('data-reason'); // Get from data attribute

            const email = row.getAttribute('data-email'); // Get from data attribute

            

            // Format the old date and time for display

            const formattedOldDate = new Date(oldDate).toLocaleDateString('en-US', {

                year: 'numeric',

                month: 'long',

                day: 'numeric'

            });

            const formattedOldTime = new Date('2000-01-01 ' + oldTime).toLocaleTimeString('en-US', {

                hour: 'numeric',

                minute: '2-digit',

                hour12: true

            });

            

            // Create reschedule modal

            

            

            document.body.appendChild(modal);

            

            // Close modal handlers

            const closeModal = () => {

                document.body.removeChild(modal);

            };

            

            modal.querySelector('.close-reschedule-modal').onclick = closeModal;

            modal.querySelector('.cancel-reschedule').onclick = closeModal;

            

            // Form submission

            const rescheduleForm = modal.querySelector('#rescheduleForm');
            if (rescheduleForm) {
                rescheduleForm.addEventListener('submit', function(e) {

                e.preventDefault();

                const newDate = this.newDate.value;

                const newTime = this.newTime.value;

                

                if (newDate && newTime) {

                    // Format the new date and time for display

                    const formattedNewDate = new Date(newDate).toLocaleDateString('en-US', {

                        year: 'numeric',

                        month: 'long',

                        day: 'numeric'

                    });

                    const formattedNewTime = new Date('2000-01-01 ' + newTime).toLocaleTimeString('en-US', {

                        hour: 'numeric',

                        minute: '2-digit',

                        hour12: true

                    });

                    

                    // Update the row with new date/time

                    const dateCell = row.querySelector('td:nth-child(4)');

                    if (dateCell) {

                        dateCell.innerHTML = `

                            <div class="text-sm text-gray-900">${formattedNewDate}</div>

                            <div class="text-xs text-gray-500">${formattedNewTime}</div>

                        `;

                    }

                    

                    // Update data attributes

                    row.setAttribute('data-date', newDate);

                    row.setAttribute('data-time', newTime);

                    

                    closeModal();

                    showSuccessModal('Appointment for ' + name + ' has been rescheduled to ' + formattedNewDate + ' at ' + formattedNewTime + '!');

                }

            });
            }

        }

    });

    

    // Keep the old code for backward compatibility but it won't work for dynamic content

    reschedBtns.forEach(btn => btn.addEventListener('click', function() {

        const row = btn.closest('tr');

        const name = row.getAttribute('data-name'); // Get from data attribute

        const oldDate = row.getAttribute('data-date'); // Get from data attribute (raw format)

        const oldTime = row.getAttribute('data-time'); // Get from data attribute (raw format)

        const reason = row.getAttribute('data-reason'); // Get from data attribute

        const email = row.getAttribute('data-email');



        showRescheduleModal(oldDate, oldTime, function(newDate, newTime) {

            // Send to server first

            fetch('', {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/x-www-form-urlencoded'

                    },

                    body: new URLSearchParams({

                        action: 'reschedule',

                        name,

                        oldDate,

                        oldTime,

                        reason,

                        newDate,

                        newTime

                    })

                })

                .then(res => res.text())

                .then(text => {

                    let data;

                    try {

                        data = JSON.parse(text);

                    } catch (e) {

                        // If the response is not valid JSON but the appointment is updated, treat as success

                        showSuccessModal('Appointment rescheduled successfully!', 'Success');

                        // Refresh the page after a short delay to show updated data

                        setTimeout(() => {

                            window.location.reload();

                        }, 1500);

                        return;

                    }

                    if (data.success) {

                        showSuccessModal('Appointment rescheduled successfully!', 'Success');

                        // Refresh the page after a short delay to show updated data

                        setTimeout(() => {

                            window.location.reload();

                        }, 1500);

                    } else {

                        showErrorModal('Failed to reschedule appointment: ' + (data.error || 'Unknown error'), 'Error');

                    }

                })

                .catch(error => {

                    showErrorModal('Network error occurred while rescheduling appointment.', 'Error');

                });

        });

    }));



    // View appointment button functionality

    const viewBtns = document.querySelectorAll('.viewAppointmentBtn');

    const viewModal = document.getElementById('appointmentViewModal');

    const closeViewBtn = document.getElementById('closeViewModalBtn');

    const closeViewBtnBottom = document.getElementById('closeViewModalBtnBottom');



    viewBtns.forEach(btn => btn.addEventListener('click', function() {

        const name = this.dataset.name;

        const date = this.dataset.date;

        const time = this.dataset.time;

        const reason = this.dataset.reason;

        const email = this.dataset.email;



        // Populate modal with appointment data

        document.getElementById('viewPatientName').textContent = name;

        document.getElementById('viewPatientEmail').textContent = email;

        document.getElementById('viewAppointmentDate').textContent = date;

        document.getElementById('viewAppointmentTime').textContent = time;

        document.getElementById('viewAppointmentReason').textContent = reason;



        // Show modal

        viewModal.classList.remove('hidden');

    }));



    // Close modal functionality

    function closeViewModal() {

        viewModal.classList.add('hidden');

    }



    closeViewBtn.addEventListener('click', closeViewModal);

    closeViewBtnBottom.addEventListener('click', closeViewModal);



    // Close modal when clicking outside

    viewModal.addEventListener('click', function(e) {

        if (e.target === viewModal) {

            closeViewModal();

        }

    });



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

                    // Format time to 12-hour format with AM/PM
                    const timeParts = doctorSchedule.schedule_time.split('-');
                    let formattedTime = doctorSchedule.schedule_time;
                    if (timeParts.length === 2) {
                        const startTime = timeParts[0] ? new Date('2000-01-01 ' + timeParts[0]).toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        }) : '';
                        const endTime = timeParts[1] ? new Date('2000-01-01 ' + timeParts[1]).toLocaleTimeString('en-US', {
                            hour: 'numeric',
                            minute: '2-digit',
                            hour12: true
                        }) : '';
                        formattedTime = startTime + ' - ' + endTime;
                    }
                    
                    popup.innerHTML = '<b>' + (doctorSchedule.profession || 'Physician') + '</b><br>Available: <span class="text-blue-600">' + formattedTime + '</span>';

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

        // Set month label safely

        var calendarMonthEl = document.getElementById('calendarMonth');

        if (calendarMonthEl) {

            calendarMonthEl.textContent = monthNames[month] + ' ' + year;

        }

    }



    var prevMonthBtn = document.getElementById('prevMonthBtn');

    if (prevMonthBtn) {

        prevMonthBtn.addEventListener('click', function() {

            currentMonth--;

            if (currentMonth < 0) {

                currentMonth = 11;

                currentYear--;

            }

            renderCalendar(currentMonth, currentYear);

        });

    }



    var nextMonthBtn = document.getElementById('nextMonthBtn');

    if (nextMonthBtn) {

        nextMonthBtn.addEventListener('click', function() {

            currentMonth++;

            if (currentMonth > 11) {

                currentMonth = 0;

                currentYear++;

            }

            renderCalendar(currentMonth, currentYear);

        });

    }



    renderCalendar(currentMonth, currentYear);



    // Main Navigation Tabs Functionality

    // Initialize tab immediately when DOM is ready, before any rendering
    function initializeTabsOnLoad() {
        // Check localStorage for active tab, default to appointments
        const activeTab = localStorage.getItem('staffActiveTab') || 'appointments';
        
        // Initialize with the saved tab active
        initializeActiveTab(activeTab);

        // Update count badges
        updateCountBadges();
    }

    // Run immediately when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeTabsOnLoad);
    } else {
        initializeTabsOnLoad();
    }

    // Keep jQuery for other functionality
    $(document).ready(function() {
        // Additional initialization if needed
        updateCountBadges();
    });

    // Function to initialize the active tab
    function initializeActiveTab(tabName) {
        // Hide all main sections first
        document.getElementById('doctorSchedulesSection').classList.add('hidden');
        document.getElementById('calendarSection').classList.add('hidden');
        document.getElementById('appointmentsSection').classList.add('hidden');
        
        // Reset all tab buttons
        document.querySelectorAll('.staff-tab-btn').forEach(function(btn) {
            btn.classList.remove('active', 'text-blue-600', 'font-semibold', 'border-b-2', 'border-blue-600');
            btn.classList.add('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');
            btn.setAttribute('aria-selected', 'false');
        });
        
        // Show the target section and activate the corresponding tab
        if (tabName === 'doctor-schedules') {
            document.getElementById('doctorSchedulesSection').classList.remove('hidden');
            const doctorTab = document.querySelector('[data-tab="doctor-schedules"]');
            if (doctorTab) {
                doctorTab.classList.remove('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');
                doctorTab.classList.add('active', 'text-blue-600', 'font-semibold', 'border-b-2', 'border-blue-600');
                doctorTab.setAttribute('aria-selected', 'true');
            }
        } else if (tabName === 'calendar-view') {
            document.getElementById('calendarSection').classList.remove('hidden');
            const calendarTab = document.querySelector('[data-tab="calendar-view"]');
            if (calendarTab) {
                calendarTab.classList.remove('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');
                calendarTab.classList.add('active', 'text-blue-600', 'font-semibold', 'border-b-2', 'border-blue-600');
                calendarTab.setAttribute('aria-selected', 'true');
            }
        } else {
            // Default to appointments
            document.getElementById('appointmentsSection').classList.remove('hidden');
            $('#pendingSection').removeClass('hidden');
            const appointmentsTab = document.querySelector('[data-tab="appointments"]');
            if (appointmentsTab) {
                appointmentsTab.classList.remove('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');
                appointmentsTab.classList.add('active', 'text-blue-600', 'font-semibold', 'border-b-2', 'border-blue-600');
                appointmentsTab.setAttribute('aria-selected', 'true');
            }
        }
    }



    // Handle appointment status filter tabs - using same logic as main tabs

    document.addEventListener('click', function(e) {

        var btn = e.target.closest('.appt-tab-btn');

        if (!btn) return;



        const status = btn.getAttribute('data-status');



        // Update button states for ALL tabs across all sections

        document.querySelectorAll('.appt-tab-btn').forEach(function(b) {

            b.classList.remove('active', 'text-blue-600', 'border-b-2', 'border-blue-600');

            b.classList.add('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');

        });



        // Set ALL tabs with the same status as active (across all sections)

        document.querySelectorAll('.appt-tab-btn[data-status="' + status + '"]').forEach(function(b) {

            b.classList.remove('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');

            b.classList.add('active', 'text-blue-600', 'border-b-2', 'border-blue-600');

        });



        // Hide all appointment sections

        document.querySelectorAll('.appt-tab-section').forEach(function(section) {

            section.classList.add('hidden');

        });



        // Show target section based on status

        if (status === 'pending') {

            document.getElementById('pendingSection').classList.remove('hidden');

        } else if (status === 'approved') {

            document.getElementById('doneSection').classList.remove('hidden');

        } else if (status === 'declined') {

            document.getElementById('declinedSection').classList.remove('hidden');

        } else if (status === 'rescheduled') {

            document.getElementById('reschedSection').classList.remove('hidden');

        }

    });



    // Function to update count badges with total counts

    function updateCountBadges() {

        // Use total counts from PHP (all entries across all pages)

        const pendingCount = <?php echo $pending_total_records; ?>;

        const approvedCount = <?php echo $done_total_records; ?>;

        const declinedCount = <?php echo $declined_total_records; ?>;

        const rescheduledCount = <?php echo $resched_total_records; ?>;



        // Update the badges in all sections

        $('#pendingCount').text(pendingCount);

        $('#approvedCount').text(approvedCount);

        $('#declinedCount').text(declinedCount);

        $('#rescheduledCount').text(rescheduledCount);

        

        // Update badges in Approved section

        $('#pendingCountApproved').text(pendingCount);

        $('#approvedCountApproved').text(approvedCount);

        $('#declinedCountApproved').text(declinedCount);

        $('#rescheduledCountApproved').text(rescheduledCount);

        

        // Update badges in Rescheduled section

        $('#pendingCountRescheduled').text(pendingCount);

        $('#approvedCountRescheduled').text(approvedCount);

        $('#declinedCountRescheduled').text(declinedCount);

        $('#rescheduledCountRescheduled').text(rescheduledCount);

        

        // Update badges in Declined section

        $('#pendingCountDeclined').text(pendingCount);

        $('#approvedCountDeclined').text(approvedCount);

        $('#declinedCountDeclined').text(declinedCount);

        $('#rescheduledCountDeclined').text(rescheduledCount);

    }





    // Tabs behavior for staff sections

    document.addEventListener('click', function(e) {

        var btn = e.target.closest('.staff-tab-btn');

        if (!btn) return;



        // Update button states

        document.querySelectorAll('.staff-tab-btn').forEach(function(b) {

            b.classList.remove('active', 'text-blue-600', 'font-semibold', 'border-b-2', 'border-blue-600');

            b.classList.add('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');

            b.setAttribute('aria-selected', 'false');

        });

        btn.classList.remove('text-gray-500', 'hover:text-gray-700', 'border-b-2', 'border-transparent');

        btn.classList.add('active', 'text-blue-600', 'font-semibold', 'border-b-2', 'border-blue-600');

        btn.setAttribute('aria-selected', 'true');



        // Toggle sections

        var targetTab = btn.getAttribute('data-tab');



        // Hide all main sections

        document.getElementById('doctorSchedulesSection').classList.add('hidden');

        document.getElementById('calendarSection').classList.add('hidden');

        document.getElementById('appointmentsSection').classList.add('hidden');



        // Show target section
        if (targetTab === 'doctor-schedules') {
            document.getElementById('doctorSchedulesSection').classList.remove('hidden');
        } else if (targetTab === 'calendar-view') {
            document.getElementById('calendarSection').classList.remove('hidden');
        } else if (targetTab === 'appointments') {
            document.getElementById('appointmentsSection').classList.remove('hidden');
        }

        // Save the active tab to localStorage
        localStorage.setItem('staffActiveTab', targetTab);

    });



    // Row selection to show details (Pending, Done, Rescheduled)

    document.addEventListener('click', function(e) {

        var row = e.target.closest('tr.selectable-appointment');

        if (!row) return;



        // Determine section container

        var section = row.closest('.bg-white.rounded.shadow.p-6.mb-8');

        if (!section) return;



        var detailsBox = null;

        var detailsBody = null;

        if (section.querySelector('#pendingAppointmentsTable')) {

            detailsBox = section.querySelector('#pendingDetails');

            detailsBody = section.querySelector('#pendingDetailsBody');

        } else if (section.querySelector('#doneAppointmentsTable')) {

            detailsBox = section.querySelector('#doneDetails');

            detailsBody = section.querySelector('#doneDetailsBody');

        } else if (section.querySelector('#reschedAppointmentsTable')) {

            detailsBox = section.querySelector('#reschedDetails');

            detailsBody = section.querySelector('#reschedDetailsBody');

        }



        if (!detailsBox || !detailsBody) return;



        // Clear previous highlight in this section

        var rows = section.querySelectorAll('tr.selectable-appointment');

        rows.forEach(function(tr) {

            tr.classList.remove('ring', 'ring-primary', 'ring-offset-1');

        });

        row.classList.add('ring', 'ring-primary', 'ring-offset-1');



        // Populate details

        var name = row.getAttribute('data-name') || '';

        var date = row.getAttribute('data-date') || '';

        var time = row.getAttribute('data-time') || '';

        var reason = row.getAttribute('data-reason') || '';

        var email = row.getAttribute('data-email') || '';

        var status = row.getAttribute('data-status') || '';



        detailsBody.innerHTML = "" +

            '<div><span class=\"font-medium text-gray-600\">Name:</span> ' + escapeHtml(name) + '</div>' +

            '<div><span class=\"font-medium text-gray-600\">Date:</span> ' + escapeHtml(date) + '</div>' +

            '<div><span class=\"font-medium text-gray-600\">Time:</span> ' + escapeHtml(time) + '</div>' +

            '<div><span class=\"font-medium text-gray-600\">Email:</span> ' + escapeHtml(email) + '</div>' +

            '<div class=\"md:col-span-2\"><span class=\"font-medium text-gray-600\">Reason:</span> ' + escapeHtml(reason) + '</div>' +

            '<div><span class=\"font-medium text-gray-600\">Status:</span> ' + escapeHtml(status) + '</div>';

        detailsBox.classList.remove('hidden');

        try {

            detailsBox.scrollIntoView({

                behavior: 'smooth',

                block: 'nearest'

            });

        } catch (e) {}

    });



    // Search functionality for all appointment tables (matching dashboard design)

    $(document).ready(function() {

        // Doctor Schedule Search functionality (Server-side)

        const doctorScheduleSearch = document.getElementById('doctorScheduleSearch');

        

        if (doctorScheduleSearch) {

            let searchTimeout;

            doctorScheduleSearch.addEventListener('input', function() {

                const searchTerm = this.value.trim();

                

                // Clear previous timeout

                clearTimeout(searchTimeout);

                

                // Set new timeout for debounced search

                searchTimeout = setTimeout(() => {

                    if (searchTerm.length >= 2 || searchTerm.length === 0) {

                        performDoctorScheduleSearch(searchTerm, 1); // Always start from page 1 for new searches

                    }

                }, 300);

            });

        }

        

        function performDoctorScheduleSearch(searchTerm, page = 1) {

            // Show loading state

            const doctorScheduleTableBody = document.getElementById('doctorScheduleTableBody');

            // No loading state for seamless real-time search

            

            // Store search term for pagination

            window.currentDoctorScheduleSearchTerm = searchTerm;

            

            // If search is cleared, show all data without page reload

            if (!searchTerm || searchTerm.trim() === '') {

                window.currentDoctorScheduleSearchTerm = null;

                // Make AJAX request to get all data without search filter

                fetch('search_doctor_schedules.php', {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/x-www-form-urlencoded',

                    },

                    body: `search=&page=${page}`

                })

                .then(response => {

                    if (!response.ok) {

                        throw new Error('Network response was not ok');

                    }

                    return response.text();

                })

                .then(text => {

                    try {

                        const data = JSON.parse(text);

                        if (data.success) {

                            updateDoctorScheduleTable(data.schedules, data.pagination);

                        } else {

                            console.error('Search error:', data.message);

                        }

                    } catch (parseError) {

                        console.error('JSON parse error:', parseError);

                    }

                })

                .catch(error => {

                    console.error('Search error:', error);

                });

                return;

            }

            

            // Make AJAX request to server

            fetch('search_doctor_schedules.php', {

                method: 'POST',

                headers: {

                    'Content-Type': 'application/x-www-form-urlencoded',

                },

                body: `search=${encodeURIComponent(searchTerm)}&page=${page}`

            })

            .then(response => {

                if (!response.ok) {

                    throw new Error('Network response was not ok');

                }

                return response.text(); // Get as text first

            })

            .then(text => {

                try {

                    const data = JSON.parse(text);

                    if (data.success) {

                        updateDoctorScheduleTable(data.schedules, data.pagination);

                    } else {

                        console.error('Search error:', data.message);

                        // Show error or fallback

                        if (doctorScheduleTableBody) {

                            doctorScheduleTableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';

                        }

                    }

                } catch (parseError) {

                    console.error('JSON parse error:', parseError);

                    console.error('Response text:', text);

                    if (doctorScheduleTableBody) {

                        doctorScheduleTableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';

                    }

                }

            })

            .catch(error => {

                console.error('Search error:', error);

                if (doctorScheduleTableBody) {

                    doctorScheduleTableBody.innerHTML = '<tr><td colspan="7" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';

                }

            });

        }

        

        function updateDoctorScheduleTable(schedules, pagination = null) {

            const doctorScheduleTableBody = document.getElementById('doctorScheduleTableBody');

            if (!doctorScheduleTableBody) return;

            

            if (schedules.length === 0) {

                // Hide the entire table structure and show empty state like static version
                console.log('=== EMPTY STATE TRIGGERED ===');
                console.log('Attempting to hide table structure...');
                alert('Empty state triggered - JavaScript is running!');
                
                // Try multiple approaches to hide the table
                const approaches = [
                    () => document.querySelector('#doctorSchedulesSection .bg-white.rounded-lg.shadow-sm.border.border-gray-200'),
                    () => document.getElementById('doctorScheduleTable')?.closest('.bg-white.rounded-lg.shadow-sm.border.border-gray-200'),
                    () => document.querySelector('#doctorSchedulesSection > div:last-child'),
                    () => document.querySelector('#doctorSchedulesSection div[class*="bg-white"]'),
                    () => document.querySelector('#doctorSchedulesSection div[class*="rounded-lg"]')
                ];
                
                let tableContainer = null;
                for (let i = 0; i < approaches.length; i++) {
                    tableContainer = approaches[i]();
                    console.log(`Approach ${i + 1} found:`, tableContainer);
                    if (tableContainer) break;
                }
                
                if (tableContainer) {
                    tableContainer.style.display = 'none';
                    console.log('Table container hidden successfully');
                } else {
                    console.log('No table container found - trying to hide individual elements');
                    // Fallback: hide individual table elements
                    const table = document.getElementById('doctorScheduleTable');
                    const pagination = document.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50');
                    if (table) table.style.display = 'none';
                    if (pagination) pagination.style.display = 'none';
                    console.log('Individual elements hidden');
                }

                // Also try to hide table and pagination directly
                const table = document.getElementById('doctorScheduleTable');
                const pagination = document.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50');
                const tableWrapper = document.querySelector('.overflow-x-auto');
                
                if (table) {
                    table.style.display = 'none';
                    console.log('Table hidden directly');
                }
                if (pagination) {
                    pagination.style.display = 'none';
                    console.log('Pagination hidden directly');
                }
                if (tableWrapper) {
                    tableWrapper.style.display = 'none';
                    console.log('Table wrapper hidden directly');
                }

                // Show empty state inside the card (replace table content)
                const tableBody = document.getElementById('doctorScheduleTableBody');
                if (tableBody) {
                    tableBody.innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-gray-500"><i class="ri-calendar-line text-4xl mb-2 block"></i>No doctor schedules found.</td></tr>';
                    console.log('Empty state added to table body');
                } else {
                    // Fallback: add to doctor schedules section
                    const emptyState = document.createElement('div');
                    emptyState.className = 'px-4 py-12 text-center text-gray-500';
                    emptyState.innerHTML = '<i class="ri-calendar-line text-4xl mb-2 block"></i>No doctor schedules found.';
                    
                    const parentContainer = document.getElementById('doctorSchedulesSection');
                    if (parentContainer) {
                        parentContainer.appendChild(emptyState);
                        console.log('Empty state added to doctor schedules section');
                    }
                }

                return;

            }

            // Show table when results are found
            let tableContainer = document.querySelector('#doctorSchedulesSection .bg-white.rounded-lg.shadow-sm.border.border-gray-200');
            
            if (!tableContainer) {
                const table = document.getElementById('doctorScheduleTable');
                if (table) {
                    tableContainer = table.closest('.bg-white.rounded-lg.shadow-sm.border.border-gray-200');
                }
            }
            
            if (!tableContainer) {
                tableContainer = document.querySelector('#doctorSchedulesSection > div:last-child');
            }
            
            if (tableContainer) {
                tableContainer.style.display = '';
            }

            // Remove any existing empty state
            const existingEmptyState = document.querySelector('.px-4.py-12.text-center.text-gray-500');
            if (existingEmptyState) {
                existingEmptyState.remove();
            }

            // Show pagination when results are found

            const paginationContainer = document.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50');

            if (paginationContainer) {

                paginationContainer.style.display = 'block';

                

                // Update pagination info if provided

                if (pagination) {

                    const startRecord = ((pagination.current_page - 1) * pagination.per_page) + 1;

                    const endRecord = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);

                    const infoText = paginationContainer.querySelector('.text-xs.text-gray-500');

                    if (infoText) {

                        infoText.innerHTML = 'Showing <span id="dsShowingStart">' + startRecord + '</span> to <span id="dsShowingEnd">' + endRecord + '</span> of <span id="dsTotalEntries">' + pagination.total_records + '</span> entries';

                    }

                    

                    // Update pagination numbers based on search results

                    updateDoctorSchedulePaginationNumbers(pagination);

                } else {

                    // If no pagination data, replace the entire pagination container with simple info

                    paginationContainer.innerHTML = `

                        <div class="text-xs text-gray-500">

                            Showing 1 to ${schedules.length} of ${schedules.length} entries

                        </div>

                    `;

                }

            }

            

            let html = '';

            schedules.forEach(schedule => {

                html += 
                    '<tr class="doctor-schedule-row" data-doctor="' + schedule.doctor_name.toLowerCase() + '" data-date="' + schedule.schedule_date.toLowerCase() + '">' +
                        '<td class="px-4 py-3 whitespace-nowrap">' +
                            '<div class="text-sm font-medium text-gray-900">' + schedule.doctor_name + '</div>' +
                        '</td>' +
                        '<td class="px-4 py-3 whitespace-nowrap">' +
                            '<div class="text-sm text-gray-900">' + (schedule.profession || 'Physician') + '</div>' +
                        '</td>' +
                        '<td class="px-4 py-3 whitespace-nowrap">' +
                            '<div class="text-sm text-gray-900">' + schedule.formatted_date + '</div>' +
                        '</td>' +
                        '<td class="px-4 py-3 whitespace-nowrap">' +
                            '<div class="text-sm text-gray-900">' + schedule.formatted_start_time + '</div>' +
                        '</td>' +
                        '<td class="px-4 py-3 whitespace-nowrap">' +
                            '<div class="text-sm text-gray-900">' + schedule.formatted_end_time + '</div>' +
                        '</td>' +
                        '<td class="px-4 py-3 whitespace-nowrap">' +
                            '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">' +
                                'Available' +
                            '</span>' +
                        '</td>' +
                        '<td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium">' +
                            '<div class="flex items-center space-x-2">' +
                                '<button class="edit-schedule-btn p-1.5 text-gray-400 hover:text-blue-600 transition-colors" ' +
                                    'data-id="' + schedule.id + '" ' +
                                    'data-doctor="' + schedule.doctor_name + '" ' +
                                    'data-date="' + schedule.schedule_date + '" ' +
                                    'data-time="' + schedule.schedule_time + '" ' +
                                    'title="Edit">' +
                                    '<i class="ri-edit-line text-sm"></i>' +
                                '</button>' +
                                '<button class="p-1.5 text-gray-400 hover:text-red-600 transition-colors delete-schedule-btn" data-id="' + schedule.id + '" title="Delete">' +
                                    '<i class="ri-delete-bin-line text-sm"></i>' +
                                '</button>' +
                            '</div>' +
                        '</td>' +
                    '</tr>';

            });

            

            doctorScheduleTableBody.innerHTML = html;

        }



        // Pending Appointments Search functionality (Server-side)

        const pendingSearchInput = document.getElementById('pendingSearchInput');

        

        if (pendingSearchInput) {

        let pendingSearchTimeout;

            pendingSearchInput.addEventListener('input', function() {

                const searchTerm = this.value.trim();

                

                // Clear previous timeout

            clearTimeout(pendingSearchTimeout);

                

                // Set new timeout for debounced search

            pendingSearchTimeout = setTimeout(() => {

                    if (searchTerm.length >= 2 || searchTerm.length === 0) {

                        performPendingAppointmentSearch(searchTerm, 1); // Always start from page 1 for new searches

                    }

                }, 300);

            });

        }

        

        function performPendingAppointmentSearch(searchTerm, page = 1) {

            // Show loading state

            const pendingTableBody = document.querySelector('#pendingAppointmentsTable tbody');

            // No loading state for seamless real-time search

            

            // Store search term for pagination

            window.currentPendingSearchTerm = searchTerm;

            

            // If search is cleared, show all data without page reload

            if (!searchTerm || searchTerm.trim() === '') {

                window.currentPendingSearchTerm = null;

                // Make AJAX request to get all data without search filter

                fetch('search_pending_appointments.php', {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/x-www-form-urlencoded',

                    },

                    body: `search=&page=${page}`

                })

                .then(response => {

                    if (!response.ok) {

                        throw new Error('Network response was not ok');

                    }

                    return response.text();

                })

                .then(text => {

                    try {

                        const data = JSON.parse(text);

                        if (data.success) {

                            updatePendingAppointmentTable(data.appointments, data.pagination);

                } else {

                            console.error('Search error:', data.message);

                        }

                    } catch (parseError) {

                        console.error('JSON parse error:', parseError);

                    }

                })

                .catch(error => {

                    console.error('Search error:', error);

                });

                return;

            }

            

            // Make AJAX request to server

            fetch('search_pending_appointments.php', {

                method: 'POST',

                headers: {

                    'Content-Type': 'application/x-www-form-urlencoded',

                },

                body: `search=${encodeURIComponent(searchTerm)}&page=${page}`

            })

            .then(response => {

                if (!response.ok) {

                    throw new Error('Network response was not ok');

                }

                return response.text(); // Get as text first

            })

            .then(text => {

                try {

                    const data = JSON.parse(text);

                    if (data.success) {

                        updatePendingAppointmentTable(data.appointments, data.pagination);

                    } else {

                        console.error('Search error:', data.message);

                        // Show error or fallback

                        if (pendingTableBody) {

                            pendingTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';

                        }

                    }

                } catch (parseError) {

                    console.error('JSON parse error:', parseError);

                    console.error('Response text:', text);

                    if (pendingTableBody) {

                        pendingTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';

                    }

                }

            })

            .catch(error => {

                console.error('Search error:', error);

                if (pendingTableBody) {

                    pendingTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';

                }

            });

        }

        

        function updatePendingAppointmentTable(appointments, pagination = null) {

            const pendingTableBody = document.querySelector('#pendingAppointmentsTable tbody');

            if (!pendingTableBody) return;

            

            if (appointments.length === 0) {

                pendingTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-time-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No pending appointments found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';

                // Hide pagination when no results

                const paginationContainer = document.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50');

                if (paginationContainer) {

                    paginationContainer.style.display = 'none';

                }

                return;

            }

            

            // Show pagination when results are found - target the specific pending appointments pagination

            const pendingSection = document.getElementById('pendingSection');

            const paginationContainer = pendingSection ? pendingSection.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50') : null;

            

            if (paginationContainer) {

                paginationContainer.style.display = 'block';

                

                // Update pagination info if provided

                if (pagination) {

                    const startRecord = ((pagination.current_page - 1) * pagination.per_page) + 1;

                    const endRecord = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);

                    const infoText = paginationContainer.querySelector('.text-xs.text-gray-500');

                    if (infoText) {

                        infoText.innerHTML = 'Showing <span id="pendingShowingStart">' + startRecord + '</span> to <span id="pendingShowingEnd">' + endRecord + '</span> of <span id="pendingTotalEntries">' + pagination.total_records + '</span> entries';

                    }

                    

                    // Update pagination numbers based on search results

                    updatePendingAppointmentPaginationNumbers(pagination);

                } else {

                    // If no pagination data, replace the entire pagination container with simple info

                    paginationContainer.innerHTML = `

                        <div class="text-xs text-gray-500">

                            Showing 1 to ${appointments.length} of ${appointments.length} entries

                        </div>

                    `;

                }

            }

            

            let html = '';

            appointments.forEach(appt => {

                html += `

                    <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer transition-colors pending-appointment-row" 

                        data-name="${appt.name.toLowerCase()}" 

                        data-date="${appt.date.toLowerCase()}" 

                        data-time="${appt.time.toLowerCase()}" 

                        data-reason="${appt.reason.toLowerCase()}" 

                        data-email="${appt.email.toLowerCase()}" 

                        data-status="pending">

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm font-medium text-gray-900 truncate">${appt.name}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900 truncate">${appt.email}</div>

                            

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900">${appt.doctor_name || 'Dr. Medical Officer'}</div>
                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900">${appt.formatted_date}</div>

                            <div class="text-xs text-gray-500">${appt.formatted_time}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900 truncate">${appt.reason}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="flex items-center space-x-1">

                                <button class="approveBtn p-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors" title="Approve">

                                    <i class="ri-check-line text-sm"></i>

                                </button>

                                <button class="declineBtn p-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors" title="Decline">

                                    <i class="ri-close-line text-sm"></i>

                                </button>

                                <button class="reschedBtn p-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors" title="Reschedule">

                                    <i class="ri-refresh-line text-sm"></i>

                                </button>

                            </div>

                        </td>

                    </tr>

                `;

            });

            

            pendingTableBody.innerHTML = html;

        }



        // Approved Appointments Search functionality (Server-side)

        const doneSearchInput = document.getElementById('doneSearchInput');

        

        if (doneSearchInput) {

        let approvedSearchTimeout;

            doneSearchInput.addEventListener('input', function() {

                const searchTerm = this.value.trim();

                

                // Clear previous timeout

            clearTimeout(approvedSearchTimeout);

                

                // Set new timeout for debounced search

            approvedSearchTimeout = setTimeout(() => {

                    if (searchTerm.length >= 2 || searchTerm.length === 0) {

                        performApprovedAppointmentSearch(searchTerm, 1); // Always start from page 1 for new searches

                    }

                }, 300);

            });

        }

        

        function performApprovedAppointmentSearch(searchTerm, page = 1) {

            // Show loading state

            const doneTableBody = document.querySelector('#doneAppointmentsTable tbody');

            // No loading state for seamless real-time search

            

            // Store search term for pagination

            window.currentApprovedSearchTerm = searchTerm;

            

            // If search is cleared, show all data without page reload

            if (!searchTerm || searchTerm.trim() === '') {

                window.currentApprovedSearchTerm = null;

                // Make AJAX request to get all data without search filter

                fetch('search_approved_appointments.php', {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/x-www-form-urlencoded',

                    },

                    body: `search=&page=${page}`

                })

                .then(response => {

                    if (!response.ok) {

                        throw new Error('Network response was not ok');

                    }

                    return response.text();

                })

                .then(text => {

                    try {

                        const data = JSON.parse(text);

                        if (data.success) {

                            updateApprovedAppointmentTable(data.appointments, data.pagination);

                } else {

                            console.error('Search error:', data.message);

                        }

                    } catch (parseError) {

                        console.error('JSON parse error:', parseError);

                    }

                })

                .catch(error => {

                    console.error('Search error:', error);

                });

                return;

            }

            

            // Make AJAX request to server

            fetch('search_approved_appointments.php', {

                method: 'POST',

                headers: {

                    'Content-Type': 'application/x-www-form-urlencoded',

                },

                body: `search=${encodeURIComponent(searchTerm)}&page=${page}`

            })

            .then(response => {

                if (!response.ok) {

                    throw new Error('Network response was not ok');

                }

                return response.text(); // Get as text first

            })

            .then(text => {

                try {

                    const data = JSON.parse(text);

                    if (data.success) {

                        updateApprovedAppointmentTable(data.appointments, data.pagination);

                    } else {

                        console.error('Search error:', data.message);

                        // Show error or fallback

                        if (doneTableBody) {

                            doneTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';

                        }

                    }

                } catch (parseError) {

                    console.error('JSON parse error:', parseError);

                    console.error('Response text:', text);

                    if (doneTableBody) {

                        doneTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';

                    }

                }

            })

            .catch(error => {

                console.error('Search error:', error);

                if (doneTableBody) {

                    doneTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';

                }

            });

        }

        

        function updateApprovedAppointmentPaginationNumbers(pagination) {

            // Target the specific pagination navigation within the done section

            const doneSection = document.getElementById('doneSection');

            const paginationNav = doneSection ? doneSection.querySelector('nav[aria-label="Pagination"]') : null;

            if (!paginationNav) return;

            

            const currentPage = pagination.current_page;

            const totalPages = pagination.total_pages;

            

            // Clear existing pagination

            paginationNav.innerHTML = '';

            

            // Previous button

            if (currentPage > 1) {

                const prevBtn = document.createElement('a');

                const searchParam = window.currentApprovedSearchTerm ? `&search=${encodeURIComponent(window.currentApprovedSearchTerm)}` : '';

                prevBtn.href = `?done_page=${currentPage - 1}${searchParam}`;

                prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';

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

                prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';

                prevBtn.disabled = true;

                prevBtn.innerHTML = `

                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m15 18-6-6 6-6"></path>

                    </svg>

                    <span class="sr-only">Previous</span>

                `;

                paginationNav.appendChild(prevBtn);

            }

            

            // Page numbers

            const startPage = Math.max(1, currentPage - 2);

            const endPage = Math.min(totalPages, currentPage + 2);

            

            if (startPage > 1) {

                const firstPage = document.createElement('a');

                const searchParam = window.currentApprovedSearchTerm ? `&search=${encodeURIComponent(window.currentApprovedSearchTerm)}` : '';

                firstPage.href = `?done_page=1${searchParam}`;

                firstPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100';

                firstPage.textContent = '1';

                paginationNav.appendChild(firstPage);

                

                if (startPage > 2) {

                    const ellipsis = document.createElement('span');

                    ellipsis.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm';

                    ellipsis.textContent = '...';

                    paginationNav.appendChild(ellipsis);

                }

            }

            

            for (let i = startPage; i <= endPage; i++) {

                if (i === currentPage) {

                    const currentBtn = document.createElement('button');

                    currentBtn.type = 'button';

                    currentBtn.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-400';

                    currentBtn.setAttribute('aria-current', 'page');

                    currentBtn.textContent = i;

                    paginationNav.appendChild(currentBtn);

                } else {

                    const pageLink = document.createElement('a');

                    const searchParam = window.currentApprovedSearchTerm ? `&search=${encodeURIComponent(window.currentApprovedSearchTerm)}` : '';

                    pageLink.href = `?done_page=${i}${searchParam}`;

                    pageLink.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100';

                    pageLink.textContent = i;

                    paginationNav.appendChild(pageLink);

                }

            }

            

            if (endPage < totalPages) {

                if (endPage < totalPages - 1) {

                    const ellipsis = document.createElement('span');

                    ellipsis.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm';

                    ellipsis.textContent = '...';

                    paginationNav.appendChild(ellipsis);

                }

                

                const lastPage = document.createElement('a');

                const searchParam = window.currentApprovedSearchTerm ? `&search=${encodeURIComponent(window.currentApprovedSearchTerm)}` : '';

                lastPage.href = `?done_page=${totalPages}${searchParam}`;

                lastPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100';

                lastPage.textContent = totalPages;

                paginationNav.appendChild(lastPage);

            }

            

            // Next button

            if (currentPage < totalPages) {

                const nextBtn = document.createElement('a');

                const searchParam = window.currentApprovedSearchTerm ? `&search=${encodeURIComponent(window.currentApprovedSearchTerm)}` : '';

                nextBtn.href = `?done_page=${currentPage + 1}${searchParam}`;

                nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';

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

                nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';

                nextBtn.disabled = true;

                nextBtn.innerHTML = `

                    <span class="sr-only">Next</span>

                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m9 18 6-6-6-6"></path>

                    </svg>

                `;

                paginationNav.appendChild(nextBtn);

            }

        }

        

        function updateApprovedAppointmentTable(appointments, pagination = null) {

            const doneTableBody = document.querySelector('#doneAppointmentsTable tbody');

            if (!doneTableBody) return;

            

            if (appointments.length === 0) {

                doneTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-check-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No approved appointments found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';

                // Hide pagination when no results

                const doneSection = document.getElementById('doneSection');

                const paginationContainer = doneSection ? doneSection.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50') : null;

                if (paginationContainer) {

                    paginationContainer.style.display = 'none';

                }

                return;

            }

            

            // Show pagination when results are found - target the specific done section pagination

            const doneSection = document.getElementById('doneSection');

            const paginationContainer = doneSection ? doneSection.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50') : null;

            

            if (paginationContainer) {

                paginationContainer.style.display = 'block';

                

                // Update pagination info if provided

                if (pagination) {

                    const startRecord = ((pagination.current_page - 1) * pagination.per_page) + 1;

                    const endRecord = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);

                    const infoText = paginationContainer.querySelector('.text-xs.text-gray-500');

                    if (infoText) {

                        infoText.innerHTML = 'Showing <span id="doneShowingStart">' + startRecord + '</span> to <span id="doneShowingEnd">' + endRecord + '</span> of <span id="doneTotalEntries">' + pagination.total_records + '</span> entries';

                    }

                    

                    // Update pagination numbers based on search results

                    updateApprovedAppointmentPaginationNumbers(pagination);

                } else {

                    // If no pagination data, replace the entire pagination container with simple info

                    paginationContainer.innerHTML = `

                        <div class="text-xs text-gray-500">

                            Showing 1 to ${appointments.length} of ${appointments.length} entries

                        </div>

                    `;

                }

            }

            

            let html = '';

            appointments.forEach(appt => {

                html += `

                    <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer transition-colors done-appointment-row" 

                        data-name="${appt.name.toLowerCase()}" 

                        data-date="${appt.date.toLowerCase()}" 

                        data-time="${appt.time.toLowerCase()}" 

                        data-reason="${appt.reason.toLowerCase()}" 

                        data-email="${appt.email.toLowerCase()}" 

                        data-status="${appt.status.toLowerCase()}"

                        style="height: 61px;">

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm font-medium text-gray-900 truncate">${appt.name}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900">${appt.formatted_date}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900">${appt.formatted_time}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900 truncate">${appt.reason}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900 truncate">${appt.email}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${appt.status_class}">

                                ${appt.status_display}

                            </span>

                        </td>

                    </tr>

                `;

            });

            

            doneTableBody.innerHTML = html;

        }



        // Rescheduled Appointments Search functionality (Server-side)

        const reschedSearchInput = document.getElementById('reschedSearchInput');

        

        if (reschedSearchInput) {

        let reschedSearchTimeout;

            reschedSearchInput.addEventListener('input', function() {

                const searchTerm = this.value.trim();

                

                // Clear previous timeout

            clearTimeout(reschedSearchTimeout);

                

                // Set new timeout for debounced search

            reschedSearchTimeout = setTimeout(() => {

                    if (searchTerm.length >= 2 || searchTerm.length === 0) {

                        performRescheduledAppointmentSearch(searchTerm, 1); // Always start from page 1 for new searches

                    }

                }, 300);

            });

        }

        

        function performRescheduledAppointmentSearch(searchTerm, page = 1) {

            // Show loading state

            const reschedTableBody = document.querySelector('#reschedAppointmentsTable tbody');

            // No loading state for seamless real-time search

            

            // Store search term for pagination

            window.currentRescheduledSearchTerm = searchTerm;

            

            // If search is cleared, show all data without page reload

            if (!searchTerm || searchTerm.trim() === '') {

                window.currentRescheduledSearchTerm = null;

                // Make AJAX request to get all data without search filter

                fetch('search_rescheduled_appointments.php', {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/x-www-form-urlencoded',

                    },

                    body: `search=&page=${page}`

                })

                .then(response => {

                    if (!response.ok) {

                        throw new Error('Network response was not ok');

                    }

                    return response.text();

                })

                .then(text => {

                    try {

                        const data = JSON.parse(text);

                        if (data.success) {

                            updateRescheduledAppointmentTable(data.appointments, data.pagination);

                } else {

                            console.error('Search error:', data.message);

                        }

                    } catch (parseError) {

                        console.error('JSON parse error:', parseError);

                    }

                })

                .catch(error => {

                    console.error('Search error:', error);

                });

                return;

            }

            

            // Make AJAX request to server

            fetch('search_rescheduled_appointments.php', {

                method: 'POST',

                headers: {

                    'Content-Type': 'application/x-www-form-urlencoded',

                },

                body: `search=${encodeURIComponent(searchTerm)}&page=${page}`

            })

            .then(response => {

                if (!response.ok) {

                    throw new Error('Network response was not ok');

                }

                return response.text(); // Get as text first

            })

            .then(text => {

                try {

                    const data = JSON.parse(text);

                    if (data.success) {

                        updateRescheduledAppointmentTable(data.appointments, data.pagination);

                    } else {

                        console.error('Search error:', data.message);

                        // Show error or fallback

                        if (reschedTableBody) {

                            reschedTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';

                        }

                    }

                } catch (parseError) {

                    console.error('JSON parse error:', parseError);

                    console.error('Response text:', text);

                    if (reschedTableBody) {

                        reschedTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';

                    }

                }

            })

            .catch(error => {

                console.error('Search error:', error);

                if (reschedTableBody) {

                    reschedTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';

                }

            });

        }

        

        function updateRescheduledAppointmentPaginationNumbers(pagination) {

            // Target the specific pagination navigation within the resched section

            const reschedSection = document.getElementById('reschedSection');

            const paginationNav = reschedSection ? reschedSection.querySelector('nav[aria-label="Pagination"]') : null;

            if (!paginationNav) return;

            

            const currentPage = pagination.current_page;

            const totalPages = pagination.total_pages;

            

            // Clear existing pagination

            paginationNav.innerHTML = '';

            

            // Previous button

            if (currentPage > 1) {

                const prevBtn = document.createElement('a');

                const searchParam = window.currentRescheduledSearchTerm ? `&search=${encodeURIComponent(window.currentRescheduledSearchTerm)}` : '';

                prevBtn.href = `?resched_page=${currentPage - 1}${searchParam}`;

                prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';

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

                prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';

                prevBtn.disabled = true;

                prevBtn.innerHTML = `

                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m15 18-6-6 6-6"></path>

                    </svg>

                    <span class="sr-only">Previous</span>

                `;

                paginationNav.appendChild(prevBtn);

            }

            

            // Page numbers

            const startPage = Math.max(1, currentPage - 2);

            const endPage = Math.min(totalPages, currentPage + 2);

            

            if (startPage > 1) {

                const firstPage = document.createElement('a');

                const searchParam = window.currentRescheduledSearchTerm ? `&search=${encodeURIComponent(window.currentRescheduledSearchTerm)}` : '';

                firstPage.href = `?resched_page=1${searchParam}`;

                firstPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100';

                firstPage.textContent = '1';

                paginationNav.appendChild(firstPage);

                

                if (startPage > 2) {

                    const ellipsis = document.createElement('span');

                    ellipsis.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm';

                    ellipsis.textContent = '...';

                    paginationNav.appendChild(ellipsis);

                }

            }

            

            for (let i = startPage; i <= endPage; i++) {

                if (i === currentPage) {

                    const currentBtn = document.createElement('button');

                    currentBtn.type = 'button';

                    currentBtn.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-400';

                    currentBtn.setAttribute('aria-current', 'page');

                    currentBtn.textContent = i;

                    paginationNav.appendChild(currentBtn);

                } else {

                    const pageLink = document.createElement('a');

                    const searchParam = window.currentRescheduledSearchTerm ? `&search=${encodeURIComponent(window.currentRescheduledSearchTerm)}` : '';

                    pageLink.href = `?resched_page=${i}${searchParam}`;

                    pageLink.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100';

                    pageLink.textContent = i;

                    paginationNav.appendChild(pageLink);

                }

            }

            

            if (endPage < totalPages) {

                if (endPage < totalPages - 1) {

                    const ellipsis = document.createElement('span');

                    ellipsis.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm';

                    ellipsis.textContent = '...';

                    paginationNav.appendChild(ellipsis);

                }

                

                const lastPage = document.createElement('a');

                const searchParam = window.currentRescheduledSearchTerm ? `&search=${encodeURIComponent(window.currentRescheduledSearchTerm)}` : '';

                lastPage.href = `?resched_page=${totalPages}${searchParam}`;

                lastPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100';

                lastPage.textContent = totalPages;

                paginationNav.appendChild(lastPage);

            }

            

            // Next button

            if (currentPage < totalPages) {

                const nextBtn = document.createElement('a');

                const searchParam = window.currentRescheduledSearchTerm ? `&search=${encodeURIComponent(window.currentRescheduledSearchTerm)}` : '';

                nextBtn.href = `?resched_page=${currentPage + 1}${searchParam}`;

                nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';

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

                nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';

                nextBtn.disabled = true;

                nextBtn.innerHTML = `

                    <span class="sr-only">Next</span>

                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m9 18 6-6-6-6"></path>

                    </svg>

                `;

                paginationNav.appendChild(nextBtn);

            }

        }

        

        function updateRescheduledAppointmentTable(appointments, pagination = null) {

            const reschedTableBody = document.querySelector('#reschedAppointmentsTable tbody');

            if (!reschedTableBody) return;

            

            if (appointments.length === 0) {

                reschedTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-refresh-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No rescheduled appointments found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';

                // Hide pagination when no results

                const reschedSection = document.getElementById('reschedSection');

                const paginationContainer = reschedSection ? reschedSection.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50') : null;

                if (paginationContainer) {

                    paginationContainer.style.display = 'none';

                }

                return;

            }

            

            // Show pagination when results are found - target the specific resched section pagination

            const reschedSection = document.getElementById('reschedSection');

            const paginationContainer = reschedSection ? reschedSection.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50') : null;

            

            if (paginationContainer) {

                paginationContainer.style.display = 'block';

                

                // Update pagination info if provided

                if (pagination) {

                    const startRecord = ((pagination.current_page - 1) * pagination.per_page) + 1;

                    const endRecord = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);

                    const infoText = paginationContainer.querySelector('.text-xs.text-gray-500');

                    if (infoText) {

                        infoText.innerHTML = 'Showing <span id="reschedShowingStart">' + startRecord + '</span> to <span id="reschedShowingEnd">' + endRecord + '</span> of <span id="reschedTotalEntries">' + pagination.total_records + '</span> entries';

                    }

                    

                    // Update pagination numbers based on search results

                    updateRescheduledAppointmentPaginationNumbers(pagination);

                } else {

                    // If no pagination data, replace the entire pagination container with simple info

                    paginationContainer.innerHTML = `

                        <div class="text-xs text-gray-500">

                            Showing 1 to ${appointments.length} of ${appointments.length} entries

                        </div>

                    `;

                }

            }

            

            let html = '';

            appointments.forEach(appt => {

                html += `

                    <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer transition-colors resched-appointment-row" 

                        data-name="${appt.name.toLowerCase()}" 

                        data-date="${appt.date.toLowerCase()}" 

                        data-time="${appt.time.toLowerCase()}" 

                        data-reason="${appt.reason.toLowerCase()}" 

                        data-email="${appt.email.toLowerCase()}" 

                        data-status="${appt.status.toLowerCase()}"

                        style="height: 61px;">

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm font-medium text-gray-900 truncate">${appt.name}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900">${appt.formatted_date}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900">${appt.formatted_time}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900 truncate">${appt.reason}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900 truncate">${appt.email}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${appt.status_class}">

                                ${appt.status_display}

                            </span>

                        </td>

                    </tr>

                `;

            });

            

            reschedTableBody.innerHTML = html;

        }



        // Declined Appointments Search functionality (Server-side)

        const declinedSearchInput = document.getElementById('declinedSearchInput');

        

        if (declinedSearchInput) {

        let declinedSearchTimeout;

            declinedSearchInput.addEventListener('input', function() {

                const searchTerm = this.value.trim();

                

                // Clear previous timeout

            clearTimeout(declinedSearchTimeout);

                

                // Set new timeout for debounced search

            declinedSearchTimeout = setTimeout(() => {

                    if (searchTerm.length >= 2 || searchTerm.length === 0) {

                        performDeclinedAppointmentSearch(searchTerm, 1); // Always start from page 1 for new searches

                    }

                }, 300);

            });

        }

        

        function performDeclinedAppointmentSearch(searchTerm, page = 1) {

            // Show loading state

            const declinedTableBody = document.querySelector('#declinedAppointmentsTable tbody');

            // No loading state for seamless real-time search

            

            // Store search term for pagination

            window.currentDeclinedSearchTerm = searchTerm;

            

            // If search is cleared, show all data without page reload

            if (!searchTerm || searchTerm.trim() === '') {

                window.currentDeclinedSearchTerm = null;

                // Make AJAX request to get all data without search filter

                fetch('search_declined_appointments.php', {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/x-www-form-urlencoded',

                    },

                    body: `search=&page=${page}`

                })

                .then(response => {

                    if (!response.ok) {

                        throw new Error('Network response was not ok');

                    }

                    return response.text();

                })

                .then(text => {

                    try {

                        const data = JSON.parse(text);

                        if (data.success) {

                            updateDeclinedAppointmentTable(data.appointments, data.pagination);

                } else {

                            console.error('Search error:', data.message);

                        }

                    } catch (parseError) {

                        console.error('JSON parse error:', parseError);

                    }

                })

                .catch(error => {

                    console.error('Search error:', error);

                });

                return;

            }

            

            // Make AJAX request to server

            fetch('search_declined_appointments.php', {

                method: 'POST',

                headers: {

                    'Content-Type': 'application/x-www-form-urlencoded',

                },

                body: `search=${encodeURIComponent(searchTerm)}&page=${page}`

            })

            .then(response => {

                if (!response.ok) {

                    throw new Error('Network response was not ok');

                }

                return response.text(); // Get as text first

            })

            .then(text => {

                try {

                    const data = JSON.parse(text);

                    if (data.success) {

                        updateDeclinedAppointmentTable(data.appointments, data.pagination);

                    } else {

                        console.error('Search error:', data.message);

                        // Show error or fallback

                        if (declinedTableBody) {

                            declinedTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed: ' + data.message + '</p></div></td></tr>';

                        }

                    }

                } catch (parseError) {

                    console.error('JSON parse error:', parseError);

                    console.error('Response text:', text);

                    if (declinedTableBody) {

                        declinedTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Invalid response</p></div></td></tr>';

                    }

                }

            })

            .catch(error => {

                console.error('Search error:', error);

                if (declinedTableBody) {

                    declinedTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-error-warning-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">Search failed - Network error</p></div></td></tr>';

                }

            });

        }

        

        function updateDeclinedAppointmentPaginationNumbers(pagination) {

            // Target the specific pagination navigation within the declined section

            const declinedSection = document.getElementById('declinedSection');

            const paginationNav = declinedSection ? declinedSection.querySelector('nav[aria-label="Pagination"]') : null;

            if (!paginationNav) return;

            

            const currentPage = pagination.current_page;

            const totalPages = pagination.total_pages;

            

            // Clear existing pagination

            paginationNav.innerHTML = '';

            

            // Previous button

            if (currentPage > 1) {

                const prevBtn = document.createElement('a');

                const searchParam = window.currentDeclinedSearchTerm ? `&search=${encodeURIComponent(window.currentDeclinedSearchTerm)}` : '';

                prevBtn.href = `?declined_page=${currentPage - 1}${searchParam}`;

                prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';

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

                prevBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';

                prevBtn.disabled = true;

                prevBtn.innerHTML = `

                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m15 18-6-6 6-6"></path>

                    </svg>

                    <span class="sr-only">Previous</span>

                `;

                paginationNav.appendChild(prevBtn);

            }

            

            // Page numbers

            const startPage = Math.max(1, currentPage - 2);

            const endPage = Math.min(totalPages, currentPage + 2);

            

            if (startPage > 1) {

                const firstPage = document.createElement('a');

                const searchParam = window.currentDeclinedSearchTerm ? `&search=${encodeURIComponent(window.currentDeclinedSearchTerm)}` : '';

                firstPage.href = `?declined_page=1${searchParam}`;

                firstPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100';

                firstPage.textContent = '1';

                paginationNav.appendChild(firstPage);

                

                if (startPage > 2) {

                    const ellipsis = document.createElement('span');

                    ellipsis.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm';

                    ellipsis.textContent = '...';

                    paginationNav.appendChild(ellipsis);

                }

            }

            

            for (let i = startPage; i <= endPage; i++) {

                if (i === currentPage) {

                    const currentBtn = document.createElement('button');

                    currentBtn.type = 'button';

                    currentBtn.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-400';

                    currentBtn.setAttribute('aria-current', 'page');

                    currentBtn.textContent = i;

                    paginationNav.appendChild(currentBtn);

                } else {

                    const pageLink = document.createElement('a');

                    const searchParam = window.currentDeclinedSearchTerm ? `&search=${encodeURIComponent(window.currentDeclinedSearchTerm)}` : '';

                    pageLink.href = `?declined_page=${i}${searchParam}`;

                    pageLink.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100';

                    pageLink.textContent = i;

                    paginationNav.appendChild(pageLink);

                }

            }

            

            if (endPage < totalPages) {

                if (endPage < totalPages - 1) {

                    const ellipsis = document.createElement('span');

                    ellipsis.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 py-2 px-2.5 text-sm';

                    ellipsis.textContent = '...';

                    paginationNav.appendChild(ellipsis);

                }

                

                const lastPage = document.createElement('a');

                const searchParam = window.currentDeclinedSearchTerm ? `&search=${encodeURIComponent(window.currentDeclinedSearchTerm)}` : '';

                lastPage.href = `?declined_page=${totalPages}${searchParam}`;

                lastPage.className = 'min-h-9.5 min-w-9.5 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-2 px-2.5 text-sm focus:outline-hidden focus:bg-gray-100';

                lastPage.textContent = totalPages;

                paginationNav.appendChild(lastPage);

            }

            

            // Next button

            if (currentPage < totalPages) {

                const nextBtn = document.createElement('a');

                const searchParam = window.currentDeclinedSearchTerm ? `&search=${encodeURIComponent(window.currentDeclinedSearchTerm)}` : '';

                nextBtn.href = `?declined_page=${currentPage + 1}${searchParam}`;

                nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';

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

                nextBtn.className = 'min-h-9.5 min-w-9.5 py-2 px-2.5 inline-flex justify-center items-center gap-x-1.5 text-sm first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';

                nextBtn.disabled = true;

                nextBtn.innerHTML = `

                    <span class="sr-only">Next</span>

                    <svg class="shrink-0 size-3.5" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m9 18 6-6-6-6"></path>

                    </svg>

                `;

                paginationNav.appendChild(nextBtn);

            }

        }

        

        function updateDeclinedAppointmentTable(appointments, pagination = null) {

            const declinedTableBody = document.querySelector('#declinedAppointmentsTable tbody');

            if (!declinedTableBody) return;

            

            if (appointments.length === 0) {

                declinedTableBody.innerHTML = '<tr><td colspan="6" class="px-6 py-8 text-center"><div class="flex flex-col items-center"><i class="ri-close-line text-2xl text-gray-300 mb-2"></i><p class="text-gray-500 text-sm font-medium">No declined appointments found</p><p class="text-gray-400 text-xs">Try adjusting your search terms</p></div></td></tr>';

                // Hide pagination when no results

                const declinedSection = document.getElementById('declinedSection');

                const paginationContainer = declinedSection ? declinedSection.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50') : null;

                if (paginationContainer) {

                    paginationContainer.style.display = 'none';

                }

                return;

            }

            

            // Show pagination when results are found - target the specific declined section pagination

            const declinedSection = document.getElementById('declinedSection');

            const paginationContainer = declinedSection ? declinedSection.querySelector('.px-4.py-3.border-t.border-gray-200.bg-gray-50') : null;

            

            if (paginationContainer) {

                paginationContainer.style.display = 'block';

                

                // Update pagination info if provided

                if (pagination) {

                    const startRecord = ((pagination.current_page - 1) * pagination.per_page) + 1;

                    const endRecord = Math.min(pagination.current_page * pagination.per_page, pagination.total_records);

                    const infoText = paginationContainer.querySelector('.text-xs.text-gray-500');

                    if (infoText) {

                        infoText.innerHTML = 'Showing <span id="declinedShowingStart">' + startRecord + '</span> to <span id="declinedShowingEnd">' + endRecord + '</span> of <span id="declinedTotalEntries">' + pagination.total_records + '</span> entries';

                    }

                    

                    // Update pagination numbers based on search results

                    updateDeclinedAppointmentPaginationNumbers(pagination);

                } else {

                    // If no pagination data, replace the entire pagination container with simple info

                    paginationContainer.innerHTML = `

                        <div class="text-xs text-gray-500">

                            Showing 1 to ${appointments.length} of ${appointments.length} entries

                        </div>

                    `;

                }

            }

            

            let html = '';

            appointments.forEach(appt => {

                html += `

                    <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer transition-colors declined-appointment-row" 

                        data-name="${appt.name.toLowerCase()}" 

                        data-date="${appt.date.toLowerCase()}" 

                        data-time="${appt.time.toLowerCase()}" 

                        data-reason="${appt.reason.toLowerCase()}" 

                        data-email="${appt.email.toLowerCase()}" 

                        data-status="${appt.status.toLowerCase()}"

                        style="height: 61px;">

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm font-medium text-gray-900 truncate">${appt.name}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900">${appt.formatted_date}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900">${appt.formatted_time}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900 truncate">${appt.reason}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <div class="text-sm text-gray-900 truncate">${appt.email}</div>

                        </td>

                        <td class="px-4 py-3 whitespace-nowrap w-1/6">

                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${appt.status_class}">

                                ${appt.status_display}

                            </span>

                        </td>

                    </tr>

                `;

            });

            

            declinedTableBody.innerHTML = html;

        }



        // Search appointments function - following records.php pattern exactly

        function searchAppointments(searchTerm, type) {

            let tbody;

            let tableId;



            // Determine which table to update based on type

            if (type === 'pending') {

                tbody = document.querySelector('#pendingAppointmentsTable tbody');

                tableId = 'pendingAppointmentsTable';

            } else if (type === 'approved') {

                tbody = document.querySelector('#doneAppointmentsTable tbody');

                tableId = 'doneAppointmentsTable';

            } else if (type === 'rescheduled') {

                tbody = document.querySelector('#reschedAppointmentsTable tbody');

                tableId = 'reschedAppointmentsTable';

            } else if (type === 'declined') {

                tbody = document.querySelector('#declinedAppointmentsTable tbody');

                tableId = 'declinedAppointmentsTable';

            }



            if (!tbody) return;



            // Show loading state - exactly like records.php

            tbody.innerHTML = `

                <tr>

                    <td colspan="6" class="px-6 py-12 text-center">

                        <div class="flex flex-col items-center">

                            <!-- No loading state for seamless real-time search -->

                        </div>

                    </td>

                </tr>

            `;



            // Make AJAX request to search - following records.php pattern

            fetch('search_appointments.php', {

                    method: 'POST',

                    headers: {

                        'Content-Type': 'application/x-www-form-urlencoded',

                    },

                    body: `search=${encodeURIComponent(searchTerm)}&type=${type}`

                })

                .then(response => response.json())

                .then(data => {

                    console.log('Search results for type:', type, 'search term:', searchTerm, data);

                    if (data.success) {

                        updateTableWithAppointments(data.appointments, type);

                    } else {

                        showError('Search failed: ' + data.message);

                    }

                })

                .catch(error => {

                    console.error('Error:', error);

                    showError('Search failed. Please try again.');

                });

        }



        // Function to update table with appointments - following records.php pattern

        function updateTableWithAppointments(appointments, type) {

            let tbody;

            let tableId;



            // Determine which table to update

            if (type === 'pending') {

                tbody = document.querySelector('#pendingAppointmentsTable tbody');

                tableId = 'pendingAppointmentsTable';

            } else if (type === 'approved') {

                tbody = document.querySelector('#doneAppointmentsTable tbody');

                tableId = 'doneAppointmentsTable';

            } else if (type === 'rescheduled') {

                tbody = document.querySelector('#reschedAppointmentsTable tbody');

                tableId = 'reschedAppointmentsTable';

            } else if (type === 'declined') {

                tbody = document.querySelector('#declinedAppointmentsTable tbody');

                tableId = 'declinedAppointmentsTable';

            }



            if (!tbody) return;



            // Keep the original total counts - don't update badges during search

            // The badges should always show total entries across all pages



            if (appointments.length === 0) {

                tbody.innerHTML = `

                    <tr>

                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">

                            <div class="flex flex-col items-center">

                                <i class="ri-search-line text-4xl mb-2"></i>

                                <p class="text-lg font-medium">No appointments found</p>

                                <p class="text-sm">Try adjusting your search terms</p>

                            </div>

                        </td>

                    </tr>

                `;

                return;

            }



            // Build table rows based on type

            let rows = '';

            appointments.forEach(function(appt) {

                if (type === 'pending') {

                    const formattedDate = new Date(appt.date).toLocaleDateString('en-US', {

                        weekday: 'short',

                        month: 'short',

                        day: 'numeric',

                        year: 'numeric'

                    });

                    const formattedTime = new Date('2000-01-01 ' + appt.time).toLocaleTimeString('en-US', {

                        hour: 'numeric',

                        minute: '2-digit',

                        hour12: true

                    });



                    rows += `

                        <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer transition-colors pending-appointment-row" 

                            data-name="${appt.name.toLowerCase()}" data-date="${appt.date.toLowerCase()}" data-time="${appt.time.toLowerCase()}" 

                            data-reason="${appt.reason.toLowerCase()}" data-email="${appt.email.toLowerCase()}" data-status="pending">

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm font-medium text-gray-900 truncate">${appt.name}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${appt.email}</div>

                                

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${appt.doctor_name || 'Dr. Medical Officer'}</div>
                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${formattedDate}</div>

                                <div class="text-xs text-gray-500 truncate">${formattedTime}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${appt.reason}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">

                                <div class="flex items-center space-x-1">

                                    <button class="approveBtn p-1.5 bg-green-100 text-green-700 rounded hover:bg-green-200 transition-colors" title="Approve">

                                        <i class="ri-check-line text-sm"></i>

                                    </button>

                                    <button class="declineBtn p-1.5 bg-red-100 text-red-700 rounded hover:bg-red-200 transition-colors" title="Decline">

                                        <i class="ri-close-line text-sm"></i>

                                    </button>

                                    <button class="rescheduleBtn p-1.5 bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition-colors" title="Reschedule">

                                        <i class="ri-refresh-line text-sm"></i>

                                    </button>

                                </div>

                            </td>

                        </tr>

                    `;

                } else if (type === 'approved') {

                    const formattedDate = new Date(appt.date).toLocaleDateString('en-US', {

                        weekday: 'short',

                        month: 'short',

                        day: 'numeric',

                        year: 'numeric'

                    });

                    const formattedTime = new Date('2000-01-01 ' + appt.time).toLocaleTimeString('en-US', {

                        hour: 'numeric',

                        minute: '2-digit',

                        hour12: true

                    });

                    const statusBadge = appt.status === 'approved' || appt.status === 'confirmed' ?

                        '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Approved</span>' :

                        '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Declined</span>';



                    rows += `

                        <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer done-appointment-row" 

                            data-name="${appt.name.toLowerCase()}" data-date="${appt.date.toLowerCase()}" data-time="${appt.time.toLowerCase()}" 

                            data-reason="${appt.reason.toLowerCase()}" data-email="${appt.email.toLowerCase()}" data-status="${appt.status.toLowerCase()}">

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm font-medium text-gray-900 truncate">${appt.name}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${formattedDate}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${formattedTime}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${appt.reason}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${appt.email}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">

                                ${statusBadge}

                            </td>

                        </tr>

                    `;

                } else if (type === 'rescheduled') {

                    const formattedDate = new Date(appt.date).toLocaleDateString('en-US', {

                        weekday: 'short',

                        month: 'short',

                        day: 'numeric',

                        year: 'numeric'

                    });

                    const formattedTime = new Date('2000-01-01 ' + appt.time).toLocaleTimeString('en-US', {

                        hour: 'numeric',

                        minute: '2-digit',

                        hour12: true

                    });



                    rows += `

                        <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer resched-appointment-row" 

                            data-name="${appt.name.toLowerCase()}" data-date="${appt.date.toLowerCase()}" data-time="${appt.time.toLowerCase()}" 

                            data-reason="${appt.reason.toLowerCase()}" data-email="${appt.email.toLowerCase()}" data-status="${appt.status.toLowerCase()}">

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm font-medium text-gray-900 truncate">${appt.name}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${formattedDate}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${formattedTime}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${appt.reason}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${appt.email}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">

                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Rescheduled</span>

                            </td>

                        </tr>

                    `;

                } else if (type === 'declined') {

                    const formattedDate = new Date(appt.date).toLocaleDateString('en-US', {

                        weekday: 'short',

                        month: 'short',

                        day: 'numeric',

                        year: 'numeric'

                    });

                    const formattedTime = new Date('2000-01-01 ' + appt.time).toLocaleTimeString('en-US', {

                        hour: 'numeric',

                        minute: '2-digit',

                        hour12: true

                    });



                    rows += `

                        <tr class="selectable-appointment hover:bg-gray-50 cursor-pointer">

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm font-medium text-gray-900 truncate">${appt.name}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${formattedDate}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${formattedTime}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${appt.reason}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap w-1/6">

                                <div class="text-sm text-gray-900 truncate">${appt.email}</div>

                            </td>

                            <td class="px-4 py-3 whitespace-nowrap">

                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Declined</span>

                            </td>

                        </tr>

                    `;

                }

            });



            tbody.innerHTML = rows;

        }



        // Error handling function - following records.php pattern

        function showError(message) {

            // You can implement a toast notification or modal here

            console.error(message);

            alert(message); // Simple fallback

        }

        


        function updateDoctorSchedulePaginationNumbers(pagination) {

            const paginationNav = document.querySelector('nav[aria-label="Pagination"]');

            if (!paginationNav) return;

            

            const currentPage = pagination.current_page;

            const totalPages = pagination.total_pages;

            

            // Clear existing pagination

            paginationNav.innerHTML = '';

            

            // Previous button

            if (currentPage > 1) {

                const prevBtn = document.createElement('a');

                const searchParam = window.currentDoctorScheduleSearchTerm ? `&search=${encodeURIComponent(window.currentDoctorScheduleSearchTerm)}` : '';

                prevBtn.href = `?ds_page=${currentPage - 1}${searchParam}`;

                prevBtn.className = 'min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';

                prevBtn.setAttribute('aria-label', 'Previous');

                prevBtn.innerHTML = `

                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m15 18-6-6 6-6"></path>

                    </svg>

                    <span class="sr-only">Previous</span>

                `;

                paginationNav.appendChild(prevBtn);

            } else {

                const prevBtn = document.createElement('button');

                prevBtn.type = 'button';

                prevBtn.className = 'min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';

                prevBtn.disabled = true;

                prevBtn.innerHTML = `

                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m15 18-6-6 6-6"></path>

                    </svg>

                    <span class="sr-only">Previous</span>

                `;

                paginationNav.appendChild(prevBtn);

            }

            

            // Page numbers

            const startPage = Math.max(1, currentPage - 2);

            const endPage = Math.min(totalPages, currentPage + 2);

            

            if (startPage > 1) {

                const firstPage = document.createElement('a');

                const searchParam = window.currentDoctorScheduleSearchTerm ? `&search=${encodeURIComponent(window.currentDoctorScheduleSearchTerm)}` : '';

                firstPage.href = `?ds_page=1${searchParam}`;

                firstPage.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-100';

                firstPage.textContent = '1';

                paginationNav.appendChild(firstPage);

                

                if (startPage > 2) {

                    const ellipsis = document.createElement('span');

                    ellipsis.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 py-1 px-2 text-xs';

                    ellipsis.textContent = '...';

                    paginationNav.appendChild(ellipsis);

                }

            }

            

            for (let i = startPage; i <= endPage; i++) {

                if (i === currentPage) {

                    const currentBtn = document.createElement('button');

                    currentBtn.type = 'button';

                    currentBtn.className = 'min-h-8 min-w-8 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-400';

                    currentBtn.setAttribute('aria-current', 'page');

                    currentBtn.textContent = i;

                    paginationNav.appendChild(currentBtn);

                } else {

                    const pageLink = document.createElement('a');

                    const searchParam = window.currentDoctorScheduleSearchTerm ? `&search=${encodeURIComponent(window.currentDoctorScheduleSearchTerm)}` : '';

                    pageLink.href = `?ds_page=${i}${searchParam}`;

                    pageLink.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-100';

                    pageLink.textContent = i;

                    paginationNav.appendChild(pageLink);

                }

            }

            

            if (endPage < totalPages) {

                if (endPage < totalPages - 1) {

                    const ellipsis = document.createElement('span');

                    ellipsis.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 py-1 px-2 text-xs';

                    ellipsis.textContent = '...';

                    paginationNav.appendChild(ellipsis);

                }

                

                const lastPage = document.createElement('a');

                const searchParam = window.currentDoctorScheduleSearchTerm ? `&search=${encodeURIComponent(window.currentDoctorScheduleSearchTerm)}` : '';

                lastPage.href = `?ds_page=${totalPages}${searchParam}`;

                lastPage.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-100';

                lastPage.textContent = totalPages;

                paginationNav.appendChild(lastPage);

            }

            

            // Next button

            if (currentPage < totalPages) {

                const nextBtn = document.createElement('a');

                const searchParam = window.currentDoctorScheduleSearchTerm ? `&search=${encodeURIComponent(window.currentDoctorScheduleSearchTerm)}` : '';

                nextBtn.href = `?ds_page=${currentPage + 1}${searchParam}`;

                nextBtn.className = 'min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';

                nextBtn.setAttribute('aria-label', 'Next');

                nextBtn.innerHTML = `

                    <span class="sr-only">Next</span>

                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m9 18 6-6-6-6"></path>

                    </svg>

                `;

                paginationNav.appendChild(nextBtn);

            } else {

                const nextBtn = document.createElement('button');

                nextBtn.type = 'button';

                nextBtn.className = 'min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';

                nextBtn.disabled = true;

                nextBtn.innerHTML = `

                    <span class="sr-only">Next</span>

                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m9 18 6-6-6-6"></path>

                    </svg>

                `;

                paginationNav.appendChild(nextBtn);

            }

        }

        

        function updatePendingAppointmentPaginationNumbers(pagination) {

            // Target the specific pagination navigation within the pending section

            const pendingSection = document.getElementById('pendingSection');

            const paginationNav = pendingSection ? pendingSection.querySelector('nav[aria-label="Pagination"]') : null;

            if (!paginationNav) return;

            

            const currentPage = pagination.current_page;

            const totalPages = pagination.total_pages;

            

            // Clear existing pagination

            paginationNav.innerHTML = '';

            

            // Previous button

            if (currentPage > 1) {

                const prevBtn = document.createElement('a');

                const searchParam = window.currentPendingSearchTerm ? `&search=${encodeURIComponent(window.currentPendingSearchTerm)}` : '';

                prevBtn.href = `?pending_page=${currentPage - 1}${searchParam}`;

                prevBtn.className = 'min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';

                prevBtn.setAttribute('aria-label', 'Previous');

                prevBtn.innerHTML = `

                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m15 18-6-6 6-6"></path>

                    </svg>

                    <span class="sr-only">Previous</span>

                `;

                paginationNav.appendChild(prevBtn);

            } else {

                const prevBtn = document.createElement('button');

                prevBtn.type = 'button';

                prevBtn.className = 'min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';

                prevBtn.disabled = true;

                prevBtn.innerHTML = `

                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m15 18-6-6 6-6"></path>

                    </svg>

                    <span class="sr-only">Previous</span>

                `;

                paginationNav.appendChild(prevBtn);

            }

            

            // Page numbers

            const startPage = Math.max(1, currentPage - 2);

            const endPage = Math.min(totalPages, currentPage + 2);

            

            if (startPage > 1) {

                const firstPage = document.createElement('a');

                const searchParam = window.currentPendingSearchTerm ? `&search=${encodeURIComponent(window.currentPendingSearchTerm)}` : '';

                firstPage.href = `?pending_page=1${searchParam}`;

                firstPage.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-100';

                firstPage.textContent = '1';

                paginationNav.appendChild(firstPage);

                

                if (startPage > 2) {

                    const ellipsis = document.createElement('span');

                    ellipsis.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 py-1 px-2 text-xs';

                    ellipsis.textContent = '...';

                    paginationNav.appendChild(ellipsis);

                }

            }

            

            for (let i = startPage; i <= endPage; i++) {

                if (i === currentPage) {

                    const currentBtn = document.createElement('button');

                    currentBtn.type = 'button';

                    currentBtn.className = 'min-h-8 min-w-8 flex justify-center items-center bg-gray-300 text-gray-800 border border-gray-300 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-400';

                    currentBtn.setAttribute('aria-current', 'page');

                    currentBtn.textContent = i;

                    paginationNav.appendChild(currentBtn);

                } else {

                    const pageLink = document.createElement('a');

                    const searchParam = window.currentPendingSearchTerm ? `&search=${encodeURIComponent(window.currentPendingSearchTerm)}` : '';

                    pageLink.href = `?pending_page=${i}${searchParam}`;

                    pageLink.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-100';

                    pageLink.textContent = i;

                    paginationNav.appendChild(pageLink);

                }

            }

            

            if (endPage < totalPages) {

                if (endPage < totalPages - 1) {

                    const ellipsis = document.createElement('span');

                    ellipsis.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 py-1 px-2 text-xs';

                    ellipsis.textContent = '...';

                    paginationNav.appendChild(ellipsis);

                }

                

                const lastPage = document.createElement('a');

                const searchParam = window.currentPendingSearchTerm ? `&search=${encodeURIComponent(window.currentPendingSearchTerm)}` : '';

                lastPage.href = `?pending_page=${totalPages}${searchParam}`;

                lastPage.className = 'min-h-8 min-w-8 flex justify-center items-center border border-gray-200 text-gray-800 hover:bg-gray-100 py-1 px-2 text-xs focus:outline-hidden focus:bg-gray-100';

                lastPage.textContent = totalPages;

                paginationNav.appendChild(lastPage);

            }

            

            // Next button

            if (currentPage < totalPages) {

                const nextBtn = document.createElement('a');

                const searchParam = window.currentPendingSearchTerm ? `&search=${encodeURIComponent(window.currentPendingSearchTerm)}` : '';

                nextBtn.href = `?pending_page=${currentPage + 1}${searchParam}`;

                nextBtn.className = 'min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 hover:bg-gray-100 focus:outline-hidden focus:bg-gray-100';

                nextBtn.setAttribute('aria-label', 'Next');

                nextBtn.innerHTML = `

                    <span class="sr-only">Next</span>

                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m9 18 6-6-6-6"></path>

                    </svg>

                `;

                paginationNav.appendChild(nextBtn);

            } else {

                const nextBtn = document.createElement('button');

                nextBtn.type = 'button';

                nextBtn.className = 'min-h-8 min-w-8 py-1 px-2 inline-flex justify-center items-center gap-x-1 text-xs first:rounded-s-lg last:rounded-e-lg border border-gray-200 text-gray-800 disabled:opacity-50 disabled:pointer-events-none';

                nextBtn.disabled = true;

                nextBtn.innerHTML = `

                    <span class="sr-only">Next</span>

                    <svg class="shrink-0 size-3" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">

                        <path d="m9 18 6-6-6-6"></path>

                    </svg>

                `;

                paginationNav.appendChild(nextBtn);

            }

        }

        

        // Handle pagination clicks for doctor schedule search results

        document.addEventListener('click', function(e) {

            // Check if it's a pagination link for doctor schedule table

            if (e.target.closest('nav[aria-label="Pagination"] a')) {

                const link = e.target.closest('a');

                const href = link.getAttribute('href');

                

                // Always prevent default and use AJAX for pagination

                if (href.includes('ds_page=')) {

                    e.preventDefault();

                    

                    // Extract page number from href

                    const pageMatch = href.match(/ds_page=(\d+)/);

                    if (pageMatch) {

                        const page = parseInt(pageMatch[1]);

                        // Use search function with current search term (or empty if no search)

                        const searchTerm = window.currentDoctorScheduleSearchTerm || '';

                        performDoctorScheduleSearch(searchTerm, page);

                    }

                }

            }

            

            // Check if it's a pagination link for pending appointments table

            const pendingSection = document.getElementById('pendingSection');

            if (pendingSection && e.target.closest('#pendingSection nav[aria-label="Pagination"] a')) {

                const link = e.target.closest('a');

                const href = link.getAttribute('href');

                

                // Always prevent default and use AJAX for pagination

                if (href.includes('pending_page=')) {

                    e.preventDefault();

                    

                    // Extract page number from href

                    const pageMatch = href.match(/pending_page=(\d+)/);

                    if (pageMatch) {

                        const page = parseInt(pageMatch[1]);

                        // Use search function with current search term (or empty if no search)

                        const searchTerm = window.currentPendingSearchTerm || '';

                        performPendingAppointmentSearch(searchTerm, page);

                    }

                }

            }

            

            // Check if it's a pagination link for approved appointments table

            const doneSection = document.getElementById('doneSection');

            if (doneSection && e.target.closest('#doneSection nav[aria-label="Pagination"] a')) {

                const link = e.target.closest('a');

                const href = link.getAttribute('href');

                

                // Always prevent default and use AJAX for pagination

                if (href.includes('done_page=')) {

                    e.preventDefault();

                    

                    // Extract page number from href

                    const pageMatch = href.match(/done_page=(\d+)/);

                    if (pageMatch) {

                        const page = parseInt(pageMatch[1]);

                        // Use search function with current search term (or empty if no search)

                        const searchTerm = window.currentApprovedSearchTerm || '';

                        performApprovedAppointmentSearch(searchTerm, page);

                    }

                }

            }

            

            // Check if it's a pagination link for declined appointments table

            const declinedSection = document.getElementById('declinedSection');

            if (declinedSection && e.target.closest('#declinedSection nav[aria-label="Pagination"] a')) {

                const link = e.target.closest('a');

                const href = link.getAttribute('href');

                

                // Always prevent default and use AJAX for pagination

                if (href.includes('declined_page=')) {

                    e.preventDefault();

                    

                    // Extract page number from href

                    const pageMatch = href.match(/declined_page=(\d+)/);

                    if (pageMatch) {

                        const page = parseInt(pageMatch[1]);

                        // Use search function with current search term (or empty if no search)

                        const searchTerm = window.currentDeclinedSearchTerm || '';

                        performDeclinedAppointmentSearch(searchTerm, page);

                    }

                }

            }

            

            // Check if it's a pagination link for rescheduled appointments table

            const reschedSection = document.getElementById('reschedSection');

            if (reschedSection && e.target.closest('#reschedSection nav[aria-label="Pagination"] a')) {

                const link = e.target.closest('a');

                const href = link.getAttribute('href');

                

                // Always prevent default and use AJAX for pagination

                if (href.includes('resched_page=')) {

                    e.preventDefault();

                    

                    // Extract page number from href

                    const pageMatch = href.match(/resched_page=(\d+)/);

                    if (pageMatch) {

                        const page = parseInt(pageMatch[1]);

                        // Use search function with current search term (or empty if no search)

                        const searchTerm = window.currentRescheduledSearchTerm || '';

                        performRescheduledAppointmentSearch(searchTerm, page);

                    }

                }

            }

        });

    });

</script>



<?php include '../includes/footer.php'; ?>