<?php
include '../includes/db_connect.php';
// get_patient_med_history.php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_name'])) {
    
    $stmt = $db->prepare('SELECT prescription_date, medicines FROM prescriptions WHERE patient_name = ? ORDER BY prescription_date DESC');
    $stmt->execute([$_POST['patient_name']]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $history = [];
    foreach ($rows as $row) {
        $date = $row['prescription_date'];
        $meds = json_decode($row['medicines'], true);
        if (is_array($meds)) {
            foreach ($meds as $med) {
                $history[] = [
                    'prescription_date' => $date,
                    'medicine' => $med['medicine'] ?? '',
                    'dosage' => $med['dosage'] ?? '',
                    'quantity' => $med['quantity'] ?? ''
                ];
            }
        }
    }
    echo json_encode($history);
    exit;
}
echo json_encode([]);
