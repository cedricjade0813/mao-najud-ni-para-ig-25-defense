<?php
include '../includep/header.php';

// Get faculty ID from session
$faculty_id = $_SESSION['faculty_id'];

// Get faculty information
$faculty_info = [];
try {
    $stmt = $db->prepare('SELECT * FROM faculty WHERE faculty_id = ?');
    $stmt->execute([$faculty_id]);
    $faculty_info = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $faculty_info = [];
}

// Get appointment counts
$appointment_counts = [
    'total' => 0,
    'pending' => 0,
    'approved' => 0,
    'declined' => 0,
    'cancelled' => 0
];

try {
    // Get total appointments
    $stmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE student_id = ?');
    $stmt->execute([$faculty_id]);
    $appointment_counts['total'] = $stmt->fetchColumn();
    
    // Get pending appointments
    $stmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status = "pending"');
    $stmt->execute([$faculty_id]);
    $appointment_counts['pending'] = $stmt->fetchColumn();
    
    // Get approved appointments
    $stmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status IN ("approved", "confirmed")');
    $stmt->execute([$faculty_id]);
    $appointment_counts['approved'] = $stmt->fetchColumn();
    
    // Get declined appointments
    $stmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status = "declined"');
    $stmt->execute([$faculty_id]);
    $appointment_counts['declined'] = $stmt->fetchColumn();
    
    // Get cancelled appointments
    $stmt = $db->prepare('SELECT COUNT(*) FROM appointments WHERE student_id = ? AND status = "cancelled"');
    $stmt->execute([$faculty_id]);
    $appointment_counts['cancelled'] = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    // Handle error silently
}

// Get recent appointments
$recent_appointments = [];
try {
    $stmt = $db->prepare('SELECT * FROM appointments WHERE student_id = ? ORDER BY date DESC, time DESC LIMIT 5');
    $stmt->execute([$faculty_id]);
    $recent_appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recent_appointments = [];
}
?>

<main class="flex-1 overflow-y-auto bg-gray-50 p-6 ml-16 md:ml-64 mt-[56px]">
    <!-- Page Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Faculty Dashboard</h1>
        <p class="text-gray-600">Welcome back, <?= htmlspecialchars($faculty_info['name'] ?? 'Faculty Member') ?>!</p>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                        <i class="ri-calendar-line text-blue-600 text-lg"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Appointments</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= $appointment_counts['total'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                        <i class="ri-time-line text-yellow-600 text-lg"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Pending</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= $appointment_counts['pending'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
                        <i class="ri-check-line text-green-600 text-lg"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Approved</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= $appointment_counts['approved'] ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div class="w-8 h-8 bg-red-100 rounded-lg flex items-center justify-center">
                        <i class="ri-close-line text-red-600 text-lg"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Declined</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= $appointment_counts['declined'] ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Appointments -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900">Recent Appointments</h3>
                <a href="appointments.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All</a>
            </div>
        </div>
        <div class="p-6">
            <?php if (!empty($recent_appointments)): ?>
                <div class="space-y-4">
                    <?php foreach ($recent_appointments as $appointment): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                            <div class="flex-1">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                            <i class="ri-calendar-line text-blue-600"></i>
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900">
                                            <?= date('M j, Y', strtotime($appointment['date'])) ?> at <?= date('g:i A', strtotime($appointment['time'])) ?>
                                        </p>
                                        <p class="text-sm text-gray-500 truncate">
                                            <?= htmlspecialchars($appointment['reason']) ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex-shrink-0">
                                <?php
                                $status = $appointment['status'];
                                $statusClasses = [
                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                    'approved' => 'bg-green-100 text-green-800',
                                    'declined' => 'bg-red-100 text-red-800',
                                    'cancelled' => 'bg-gray-100 text-gray-800',
                                    'rescheduled' => 'bg-blue-100 text-blue-800'
                                ];
                                $statusClass = $statusClasses[$status] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                    <?= ucfirst($status) ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center py-8">
                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i class="ri-calendar-line text-2xl text-gray-400"></i>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No appointments yet</h3>
                    <p class="text-gray-500 text-sm">Your appointments will appear here once you have them.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php
include '../includep/footer.php';
?>
